#!/usr/bin/env python3
import os
from pathlib import Path
from rdkit import Chem
from collections import defaultdict
from tqdm import tqdm
import argparse
import sys
import requests
import zipfile
from datetime import datetime
import shutil
import boto3
from botocore.config import Config
import mimetypes
from dotenv import load_dotenv
from slugify import slugify

def download_file(url, local_filename):
    """
    Download a file from URL showing progress bar
    """
    response = requests.get(url, stream=True)
    total_size = int(response.headers.get('content-length', 0))
    
    with open(local_filename, 'wb') as f:
        with tqdm(total=total_size, unit='B', unit_scale=True, desc="Downloading") as pbar:
            for chunk in response.iter_content(chunk_size=8192):
                if chunk:
                    f.write(chunk)
                    pbar.update(len(chunk))
    return local_filename

def setup_directories(base_dir, month, year):
   
    """
    Create year-month based directory structure
    Returns paths for temp and output directories
    """
    # Create year-month folder structure
    year_month = f"{year}-{month}"
    month_year = f"{month}-{year}"

    # remove existing directorys
    shutil.rmtree(os.path.join(base_dir, year_month, 'temp'), ignore_errors=True)
    shutil.rmtree(os.path.join(base_dir, year_month, 'collections'), ignore_errors=True)
    
    # Create directory structure
    temp_dir = os.path.join(base_dir, year_month, 'temp')
    collections_dir = os.path.join(base_dir, year_month, 'collections')
    
    os.makedirs(temp_dir, exist_ok=True)
    os.makedirs(collections_dir, exist_ok=True)
    
    return temp_dir, collections_dir, year_month, month_year

def get_collections(mol):
    """Extract collections from a molecule and return as a list."""
    if mol.HasProp('collections'):
        collections = mol.GetProp('collections').split('|')
        return [c.strip() for c in collections]
    return []

def process_sdf_files(input_file, output_dir, year_month):
    """
    Process SDF file and split it by collections into output directory.
    """
    if not os.path.exists(input_file):
        print(f"Error: Input file not found: {input_file}")
        return
    
    writers = defaultdict(lambda: None)
    collection_counts = defaultdict(int)
    total_molecules_processed = 0
    
    try:
        supplier = Chem.ForwardSDMolSupplier(input_file, sanitize=False)
        
        for mol in supplier:
            if mol is not None:
                collections = get_collections(mol)
                for collection in collections:
                    safe_collection = "".join(c for c in collection if c.isalnum() or c in (' ', '-', '_')).strip()
                    output_file = os.path.join(output_dir, f"{safe_collection}-{year_month}.sdf")
                    
                    if writers[collection] is None:
                        try:
                            writers[collection] = Chem.SDWriter(output_file)
                        except Exception as e:
                            print(f"\nError creating writer for {collection}: {str(e)}")
                            continue
                    
                    try:
                        writers[collection].write(mol)
                        collection_counts[collection] += 1
                    except Exception as e:
                        print(f"\nError writing molecule to {collection}: {str(e)}")
                total_molecules_processed += 1
                    
    except Exception as e:
        print(f"Error processing SDF file: {str(e)}")
        return
    finally:
        for writer in writers.values():
            if writer is not None:
                writer.close()
    
    print("\nProcessing complete!")
    print(f"Total molecules processed: {total_molecules_processed:,}")
    print("\nCollection statistics:")
    
    total_size = 0
    for collection, count in sorted(collection_counts.items(), key=lambda x: x[1], reverse=True):
        filename = slugify(f"{collection}-{year_month}.sdf", separator='-', lowercase=True) 
        safe_filename = "".join(c for c in filename if c.isalnum() or c in (' ', '-', '_', '.')).strip()
        file_path = os.path.join(output_dir, safe_filename)
        try:
            file_size = os.path.getsize(file_path)
            total_size += file_size
            print(f"- {safe_filename}:")
            print(f"  - Molecules: {count:,}")
            print(f"  - File size: {file_size/1024/1024:.1f} MB")
        except OSError as e:
            print(f"- {safe_filename}: Error getting file size - {str(e)}")
    
    print(f"\nTotal file size: {total_size/1024/1024:.1f} MB")
    print(f"Total unique collections: {len(collection_counts)}")

def load_laravel_env():
    """
    Load environment variables from Laravel's .env file
    Returns the path to .env file for error checking
    """
    # Get the script's location
    current_dir = Path(__file__).resolve().parent
    
    # Navigate to Laravel root (from resources/scripts/python to root)
    laravel_root = current_dir.parent.parent.parent
    
    # Path to .env file
    env_path = laravel_root / '.env'
    
    if not env_path.exists():
        raise FileNotFoundError(f"Laravel .env file not found at {env_path}")
    
    # Load the .env file
    load_dotenv(env_path)
    
    # Verify required AWS credentials exist
    required_vars = ['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_DEFAULT_REGION', 'AWS_BUCKET']
    missing_vars = [var for var in required_vars if not os.getenv(var)]
    
    if missing_vars:
        raise ValueError(f"Missing required AWS credentials in .env: {', '.join(missing_vars)}")
    
    return env_path

def initialize_s3_client():
    """
    Initialize S3 client with credentials and endpoint from .env
    """
    # Load environment variables
    env_path = load_laravel_env()
    print(f"Loaded environment from: {env_path}")
    
    # Get AWS credentials from environment
    aws_access_key = os.getenv('AWS_ACCESS_KEY_ID')
    aws_secret_key = os.getenv('AWS_SECRET_ACCESS_KEY')
    aws_region = os.getenv('AWS_DEFAULT_REGION')
    aws_endpoint = os.getenv('AWS_URL')
    
    # Configure the client
    s3_config = Config(
        s3={'addressing_style': 'virtual'},
        signature_version='s3v4',
        retries={
            'max_attempts': 3,  # Number of retry attempts
            'mode': 'standard'
        }
    )
    
    # Initialize client with all configurations
    s3_client = boto3.client(
        's3',
        aws_access_key_id=aws_access_key,
        aws_secret_access_key=aws_secret_key,
        region_name=aws_region,
        endpoint_url=aws_endpoint,
        config=s3_config
    )
    
    return s3_client

def upload_directory_to_s3(local_directory, s3_prefix):
    """
    Upload a directory to S3
    
    Args:
        local_directory (str): Local directory path containing files to upload
        bucket_name (str): Name of the S3 bucket
        s3_prefix (str): Prefix (folder path) in the S3 bucket
    """
    try:
        # Initialize S3 client
        s3_client = initialize_s3_client()
        
        # Get list of all files in directory
        files_to_upload = []
        total_size = 0
        
        for root, dirs, files in os.walk(local_directory):
            for filename in files:
                local_path = os.path.join(root, filename)
                relative_path = os.path.relpath(local_path, local_directory)
                s3_path = os.path.join(s3_prefix, relative_path).replace("\\", "/")
                
                # Get file size
                file_size = os.path.getsize(local_path)
                total_size += file_size
                
                files_to_upload.append({
                    'local_path': local_path,
                    's3_path': s3_path,
                    'size': file_size
                })
        
        # Print summary before upload
        print(f"\nPreparing to upload {len(files_to_upload)} files")
        print(f"Total size: {total_size / (1024*1024*1024):.2f} GB")
        
        # Upload files with progress bar
        with tqdm(total=total_size, unit='B', unit_scale=True, desc="Uploading") as pbar:
            for file_info in files_to_upload:
                bucket_name = os.getenv('AWS_BUCKET')

                # Upload file
                try:
                    content_type = mimetypes.guess_type(file_info['local_path'])[0]
                    if content_type is None:
                        content_type = 'application/octet-stream'
                    
                    # Print debug information
                    print(f"\nUploading: {file_info['local_path']}")
                    print(f"To: s3://{bucket_name}/{file_info['s3_path']}")
                    print(f"Content Type: {content_type}")
                    
                    # Upload file
                    s3_client.upload_file(
                        Filename=file_info['local_path'],  # Use explicit parameter name
                        Bucket=bucket_name,                # Use explicit parameter name
                        Key=file_info['s3_path'],          # Use explicit parameter name
                        ExtraArgs={
                            'ContentType': content_type,
                            'ACL': 'public-read'
                        },
                        Callback=lambda bytes_transferred: pbar.update(bytes_transferred)
                    )
                except Exception as e:
                    print(f"\nError uploading {file_info['local_path']}")
                    print(f"Error details: {str(e)}")
                    print(f"Bucket: {bucket_name}")
                    print(f"Key: {file_info['s3_path']}")
                    continue
        
        print("\nUpload completed successfully!")
        print(f"Files are available in s3://{bucket_name}/{s3_prefix}/")
        
    except Exception as e:
        print(f"Error during upload: {str(e)}")

def main():
    parser = argparse.ArgumentParser(description='Download and process COCONUT SDF files')
    parser.add_argument('--month', '-m', 
                       help='Month to download (e.g., 01 for January). Defaults to current month',
                       default=datetime.now().strftime('%m'))
    parser.add_argument('--year', '-y',
                       help='Year to download (e.g., 2024). Defaults to current year',
                       default=datetime.now().strftime('%Y'))
    
    args = parser.parse_args()
    
    # Setup directories
    base_dir = "/Users/sagar/Downloads/"
    temp_dir, collections_dir, year_month, month_year = setup_directories(base_dir, args.month, args.year)
    
    # Download file
    zip_url = base_dir + year_month + "/coconut_complete-" + month_year + ".sdf.zip"
    zip_url = "https://coconut.s3.uni-jena.de/prod/downloads/" + year_month + "/coconut_complete-" + month_year + ".sdf.zip"
    zip_file = os.path.join(temp_dir, "coconut_complete.zip")
    
    print("Starting download...")
    download_file(zip_url, zip_file)
    
    # Unzip file
    print("\nExtracting ZIP file...")
    with zipfile.ZipFile(zip_file, 'r') as zip_ref:
        zip_ref.extractall(temp_dir)
    
    # Find the SDF file
    sdf_file = None
    for file in os.listdir(temp_dir):
        if file.endswith('.sdf'):
            sdf_file = os.path.join(temp_dir, file)
            break
    
    if not sdf_file:
        print("Error: No SDF file found in ZIP archive")
        return
    
    # Process the SDF file
    print("\nProcessing SDF file...")
    process_sdf_files(sdf_file, collections_dir, year_month)

    # Set folder for Upload
    s3_prefix = "prod/downloads/" + year_month + "/collections/"


    # Upload to S3
    print("\nUploading to S3...")
    upload_directory_to_s3(collections_dir, s3_prefix)
    
    # Cleanup
    print("\nCleaning up temporary files...")
    try:
        os.remove(zip_file)
        os.remove(sdf_file)
        # os.rmdir(temp_dir)
        shutil.rmtree(temp_dir, ignore_errors=True)

        print("Cleanup complete!")
    except Exception as e:
        print(f"Error during cleanup: {str(e)}")

if __name__ == "__main__":
    main()