#!/usr/bin/env python3
import os
from pathlib import Path
from rdkit import Chem
from collections import defaultdict
from tqdm import tqdm
import argparse
import requests
import zipfile
from datetime import datetime
import shutil
import boto3
from botocore.config import Config
import mimetypes
from dotenv import load_dotenv
from slugify import slugify

"""
COCONUT SDF File Processor and S3 Uploader
This script downloads, processes, and uploads COCONUT database SDF files to S3.
It splits the main SDF file into collection-specific files and maintains a year-month
based directory structure.
"""

def download_file(url, local_filename):
    """
    Download a file from URL with progress tracking
    
    Args:
        url (str): Source URL of the file
        local_filename (str): Destination path for downloaded file
    
    Returns:
        str: Path to downloaded file
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
    Create and manage year-month based directory structure
    
    Args:
        base_dir (str): Base directory path
        month (str): Month in MM format
        year (str): Year in YYYY format
    
    Returns:
        tuple: Paths for temp and collections directories, formatted dates
    """
    year_month = f"{year}-{month}"
    month_year = f"{month}-{year}"
    
    shutil.rmtree(os.path.join(base_dir, year_month, 'temp'), ignore_errors=True)
    shutil.rmtree(os.path.join(base_dir, year_month, 'collections'), ignore_errors=True)
    
    temp_dir = os.path.join(base_dir, year_month, 'temp')
    collections_dir = os.path.join(base_dir, year_month, 'collections')
    
    os.makedirs(temp_dir, exist_ok=True)
    os.makedirs(collections_dir, exist_ok=True)
    
    return temp_dir, collections_dir, year_month, month_year

def get_collections(mol):
    """
    Extract collections from a molecule
    
    Args:
        mol: RDKit molecule object
    
    Returns:
        list: Collection names
    """
    if mol.HasProp('collections'):
        return [c.strip() for c in mol.GetProp('collections').split('|')]
    return []

def process_sdf_files(input_file, output_dir, year_month):
    """
    Process SDF file and split by collections
    
    Args:
        input_file (str): Input SDF file path
        output_dir (str): Output directory for collection files
        year_month (str): Year-month string for file naming
    """
    if not os.path.exists(input_file):
        raise FileNotFoundError(f"Input file not found: {input_file}")
    
    writers = defaultdict(lambda: None)
    collection_counts = defaultdict(int)
    total_molecules_processed = 0
    
    try:
        supplier = Chem.ForwardSDMolSupplier(input_file, sanitize=False)
        
        for mol in tqdm(supplier, desc="Processing molecules"):
            if mol is not None:
                total_molecules_processed += 1
                for collection in get_collections(mol):
                    collection = slugify(collection, separator='-', lowercase=True)
                    filename = f"{collection}-{year_month}.sdf"
                    output_file = os.path.join(output_dir, filename)
                    
                    if writers[collection] is None:
                        writers[collection] = Chem.SDWriter(output_file)
                    
                    writers[collection].write(mol)
                    collection_counts[collection] += 1
                    
    finally:
        for writer in writers.values():
            if writer is not None:
                writer.close()
    
    # total_size = sum(os.path.getsize(os.path.join(output_dir, slugify(f"{c}-{year_month}.sdf", separator='-', lowercase=True)))
    #                  for c in collection_counts)
    
    print(f"Processed {total_molecules_processed:,} molecules into {len(collection_counts)} collections")
    # print(f"Total size: {total_size/1024/1024:.1f} MB")

def load_laravel_env():
    """
    Load and validate Laravel environment variables
    
    Returns:
        Path: Path to .env file
    
    Raises:
        FileNotFoundError: If .env file is not found
        ValueError: If required AWS credentials are missing
    """
    env_path = Path(__file__).resolve().parent.parent.parent.parent / '.env'
    
    if not env_path.exists():
        raise FileNotFoundError(f"Laravel .env file not found at {env_path}")
    
    load_dotenv(env_path)
    
    required_vars = ['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_DEFAULT_REGION', 'AWS_BUCKET']
    missing_vars = [var for var in required_vars if not os.getenv(var)]
    
    if missing_vars:
        raise ValueError(f"Missing required AWS credentials: {', '.join(missing_vars)}")
    
    return env_path

def initialize_s3_client():
    """
    Initialize S3 client with credentials from environment
    
    Returns:
        boto3.client: Configured S3 client
    """
    load_laravel_env()
    
    s3_config = Config(
        s3={'addressing_style': 'virtual'},
        signature_version='s3v4',
        retries={'max_attempts': 3, 'mode': 'standard'}
    )
    
    return boto3.client(
        's3',
        aws_access_key_id=os.getenv('AWS_ACCESS_KEY_ID'),
        aws_secret_access_key=os.getenv('AWS_SECRET_ACCESS_KEY'),
        region_name=os.getenv('AWS_DEFAULT_REGION'),
        endpoint_url=os.getenv('AWS_URL'),
        config=s3_config
    )

def upload_directory_to_s3(local_directory, s3_prefix):
    """
    Upload directory contents to S3
    
    Args:
        local_directory (str): Source directory path
        s3_prefix (str): S3 destination prefix
    """
    s3_client = initialize_s3_client()
    bucket_name = os.getenv('AWS_BUCKET')
    
    files_to_upload = []
    total_size = 0
    
    for root, _, files in os.walk(local_directory):
        for filename in files:
            local_path = os.path.join(root, filename)
            relative_path = os.path.relpath(local_path, local_directory)
            s3_path = os.path.join(s3_prefix, relative_path).replace("\\", "/")
            
            files_to_upload.append({
                'local_path': local_path,
                's3_path': s3_path,
                'size': os.path.getsize(local_path)
            })
            total_size += files_to_upload[-1]['size']
    
    with tqdm(total=total_size, unit='B', unit_scale=True, desc="Uploading") as pbar:
        for file_info in files_to_upload:
            content_type = mimetypes.guess_type(file_info['local_path'])[0] or 'application/octet-stream'
            
            try:
                s3_client.upload_file(
                    Filename=file_info['local_path'],
                    Bucket=bucket_name,
                    Key=file_info['s3_path'],
                    ExtraArgs={'ContentType': content_type, 'ACL': 'public-read'},
                    Callback=lambda bytes_transferred: pbar.update(bytes_transferred)
                )
            except Exception as e:
                print(f"Error uploading {file_info['local_path']}: {str(e)}")

def main():
    """
    Main execution function
    """
    parser = argparse.ArgumentParser(description='Download and process COCONUT SDF files')
    parser.add_argument('--basedir', '-bdr', default=os.getcwd())
    parser.add_argument('--month', '-m', default=datetime.now().strftime('%m'))
    parser.add_argument('--year', '-y', default=datetime.now().strftime('%Y'))
    args = parser.parse_args()
    
    base_dir = args.basedir
    temp_dir, collections_dir, year_month, month_year = setup_directories(base_dir, args.month, args.year)
    
    zip_url = f"https://coconut.s3.uni-jena.de/prod/downloads/{year_month}/coconut_complete-{month_year}.sdf.zip"
    zip_file = os.path.join(temp_dir, "coconut_complete.zip")
    
    download_file(zip_url, zip_file)
    
    with zipfile.ZipFile(zip_file, 'r') as zip_ref:
        zip_ref.extractall(temp_dir)
    
    sdf_file = next((os.path.join(temp_dir, f) for f in os.listdir(temp_dir) if f.endswith('.sdf')), None)
    if not sdf_file:
        raise FileNotFoundError("No SDF file found in ZIP archive")
    
    process_sdf_files(sdf_file, collections_dir, year_month)
    upload_directory_to_s3(collections_dir, f"prod/downloads/{year_month}/collections/")
    
    shutil.rmtree(temp_dir, ignore_errors=True)

if __name__ == "__main__":
    main()