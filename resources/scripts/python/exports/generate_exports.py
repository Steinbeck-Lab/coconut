import os
import datetime
import subprocess
import argparse
import sys
import psycopg2
import csv
import zipfile
from tqdm import tqdm
import pandas as pd
from datetime import datetime
from rdkit import Chem
from rdkit.Chem import AllChem, SDWriter

# Additional imports
from collections import defaultdict
from slugify import slugify

# ------------- NEW IMPORTS FOR S3 -------------
import boto3
from botocore.config import Config
import mimetypes
from pathlib import Path

# ----------------------------------------------------------------------
# Argument Parsing
# ----------------------------------------------------------------------

def parse_arguments():
    """Parse command-line arguments."""
    parser = argparse.ArgumentParser(description="PostgreSQL Backup and Processing Script")
    parser.add_argument("container_id", help="Docker container ID")
    parser.add_argument("--env-file", default="/app/coconut/.env", help="Path to the .env file")
    return parser.parse_args()

# ----------------------------------------------------------------------
# Environment Loading
# ----------------------------------------------------------------------

def load_env(file_path):
    """
    Load environment variables from a .env file into a dictionary.
    The same file should contain both DB credentials and AWS credentials.
    """
    env_vars = {}
    if os.path.exists(file_path):
        with open(file_path) as f:
            for line in f:
                if line.strip() and not line.startswith("#"):
                    key, value = line.strip().split("=", 1)
                    env_vars[key] = value
    return env_vars

def get_db_params(env_vars):
    """Extract database connection parameters from environment variables."""
    return {
        "dbname": env_vars.get("DB_NAME", "coconut"),
        "user": env_vars.get("DB_USER", "sail"),
        "password": env_vars.get("DB_PASSWORD", "password"),
        "host": env_vars.get("DB_HOST", "localhost"),
        "port": env_vars.get("DB_PORT", "5432")
    }

def get_backup_path():
    """
    Returns the backup directory path based on the current year and month.
    Creates the directory if it does not exist.
    Example: 2025-02
    """
    current_date = datetime.now()
    folder_name = f"{current_date.year}-{current_date.month:02d}"
    backup_path = os.path.join(os.getcwd(), folder_name)
    os.makedirs(backup_path, exist_ok=True)
    return backup_path

# ----------------------------------------------------------------------
# Utility & Cleanup
# ----------------------------------------------------------------------

def run_command(command):
    """Executes a shell command and displays progress."""
    print(f"Executing: {command}")
    result = subprocess.run(command, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    if result.returncode == 0:
        print("Success!")
    else:
        print(f"Error: {result.stderr.decode().strip()}")
        sys.exit(1)

def load_query(file_path):
    """Load an SQL query from a file."""
    if not os.path.exists(file_path):
        print(f"Error: Query file {file_path} not found.")
        sys.exit(1)
    with open(file_path, "r", encoding="utf-8") as file:
        return file.read().strip()

def zip_file(input_filename, output_filename=None):
    """
    Compress any file (CSV or SDF) into a ZIP archive.
    If output_filename is not specified, it replaces the existing extension with .zip.
    """
    if output_filename is None:
        base, _ = os.path.splitext(input_filename)
        output_filename = base + ".zip"

    if os.path.exists(output_filename):
        print(f"ZIP file already exists: {output_filename}")
        return

    with zipfile.ZipFile(output_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
        zipf.write(input_filename, os.path.basename(input_filename))
    print(f"Zipped {input_filename} to {output_filename}")

def unzip_file(zip_filename, extract_dir):
    """Utility to unzip a file into a specified directory."""
    print(f"Unzipping {zip_filename} into {extract_dir}...")
    with zipfile.ZipFile(zip_filename, 'r') as zip_ref:
        zip_ref.extractall(extract_dir)
    print(f"Unzipped successfully.")

def cleanup_files(*files):
    """Remove a list of files (or directories) if they exist."""
    for file in files:
        if os.path.isdir(file):
            import shutil
            shutil.rmtree(file, ignore_errors=True)
            print(f"Removed directory {file}")
        elif os.path.isfile(file):
            os.remove(file)
            print(f"Removed file {file}")

# ----------------------------------------------------------------------
# Database Dump Functions
# ----------------------------------------------------------------------

def export_dump(container_id, db_name, user, password, output_file, tables=None):
    """
    Perform a PostgreSQL dump to output_file. If `tables` is provided,
    it will only dump those tables. Skips dump if file already exists.
    """
    if os.path.exists(output_file):
        print(f"Dump file already exists: {output_file}")
        return

    print(f"Starting database dump: {output_file}")
    dump_command = (
        f'docker exec -i {container_id} /bin/bash -c "PGPASSWORD={password} pg_dump --username {user} '
    )
    if tables:
        table_args = " ".join([f"-t {table}" for table in tables])
        dump_command += f'{table_args} '
    dump_command += f'{db_name}" > {output_file}'

    with tqdm(total=100, desc=f"Dumping {output_file}") as pbar:
        run_command(dump_command)
        pbar.update(100)
    print(f"Dump saved to {output_file}")

def export_pg_dumps(container_id, db_params, backup_path):
    """
    Perform the full and selected tables database dump only if
    files do not already exist. The full dump is created directly
    as coconut-dump-DD-MM-YYYY.sql.
    """
    db_name = db_params["dbname"]
    user = db_params["user"]
    password = db_params["password"]

    # Create the full dump name with day-month-year
    today = datetime.now()
    new_dump_filename = f"coconut-dump-{today.day:02d}-{today.month:02d}-{today.year}.sql"
    full_dump_file = os.path.join(backup_path, new_dump_filename)
    export_dump(container_id, db_name, user, password, full_dump_file)

    # Selected tables dump (static name example)
    selected_tables_dump_file = os.path.join(backup_path, "coconut-dump-02-2025.sql")
    selected_tables = [
        "citables", "citations", "collection_molecule", "collections", "entries",
        "geo_location_molecule", "geo_locations", "molecule_organism", "molecule_related",
        "molecules", "organism_parts", "organisms", "properties", "structures",
        "taggables", "tags"
    ]
    export_dump(container_id, db_name, user, password, selected_tables_dump_file, selected_tables)

# ----------------------------------------------------------------------
# CSV Export Functions
# ----------------------------------------------------------------------

def export_csv(query, output_filename, db_params):
    """
    Export the results of `query` to CSV at `output_filename`.
    Skips export if CSV already exists. Then zips it (but does NOT remove).
    """
    if os.path.exists(output_filename):
        print(f"CSV file already exists: {output_filename}")
        return

    conn = None
    cursor = None

    try:
        conn = psycopg2.connect(**db_params)
        conn.set_client_encoding('UTF8')
        cursor = conn.cursor()

        print(f"Executing query for {output_filename}...")
        cursor.execute(query)
        rows = cursor.fetchall()
        headers = [desc[0] for desc in cursor.description]

        print(f"Writing results to {output_filename}...")
        with open(output_filename, mode="w", newline="", encoding="utf-8") as file:
            writer = csv.writer(file)
            writer.writerow(headers)
            with tqdm(total=len(rows), desc=f"Writing {output_filename}") as pbar:
                for row in rows:
                    writer.writerow(row)
                    pbar.update(1)

        # Zip the resulting CSV
        zip_file(output_filename)
        print(f"Data successfully exported to {output_filename} and zipped.")

    except Exception as e:
        print(f"Error exporting {output_filename}: {e}")

    finally:
        if cursor:
            cursor.close()
        if conn:
            conn.close()

    # DO NOT remove the CSV here. We need it for SDF creation.
    # We'll remove them in `main()` AFTER process_csv_to_sdf() is done.

# ----------------------------------------------------------------------
# CSV -> 2D SDF
# ----------------------------------------------------------------------

def process_csv_to_sdf(csv_file, output_sdf, light_sdf):
    """
    Convert a CSV of molecules (containing 'canonical_smiles' and 'identifier')
    into two SDF files:
      1) A full SDF (output_sdf) with all properties
      2) A lite SDF (light_sdf) containing only 'identifier'
    Skips creation if both SDF files already exist.
    """
    if os.path.exists(output_sdf) and os.path.exists(light_sdf):
        print(f"SDF files already exist: {output_sdf}, {light_sdf}")
        return

    print(f"Reading CSV file: {csv_file}")
    df = pd.read_csv(csv_file, low_memory=False, dtype=str)

    print(f"Creating SDF files:\n  - {output_sdf}\n  - {light_sdf}")
    with SDWriter(output_sdf) as full_writer, SDWriter(light_sdf) as lite_writer:
        with tqdm(total=len(df), desc="Converting CSV to SDF") as pbar:
            for _, row in df.iterrows():
                mol = Chem.MolFromSmiles(str(row.get("canonical_smiles", "")))
                if mol:
                    AllChem.Compute2DCoords(mol)

                    # Full SDF with all properties
                    for col in df.columns:
                        value = row[col] if row[col] is not None else ""
                        mol.SetProp(col, str(value))
                    full_writer.write(mol)

                    # Lite SDF with only identifier
                    for prop in mol.GetPropNames():
                        mol.ClearProp(prop)
                    mol.SetProp("identifier", str(row.get("identifier", "")))
                    lite_writer.write(mol)

                pbar.update(1)

def generate_sdf_filenames():
    """
    Generate filenames for the two SDF outputs based on the current date.
    New naming:
      - coconut_sdf_2d-{timestamp}.sdf
      - coconut_sdf_2d_lite-{timestamp}.sdf
    """
    timestamp = datetime.now().strftime("%m-%Y")
    backup_path = get_backup_path()
    return {
        "output_sdf": os.path.join(backup_path, f"coconut_sdf_2d-{timestamp}.sdf"),
        "light_sdf": os.path.join(backup_path, f"coconut_sdf_2d_lite-{timestamp}.sdf")
    }

# ----------------------------------------------------------------------
# Per-Collection SDF Generation
# ----------------------------------------------------------------------

def split_sdf_into_collections(sdf_file, backup_path):
    """
    Read the (uncompressed) 2D SDF file and split molecules
    into separate SDFs based on 'collections' property.
    Then zip each SDF, remove the .sdf, leaving only zips.
    """
    if not os.path.exists(sdf_file):
        raise FileNotFoundError(f"Cannot split by collections: SDF not found: {sdf_file}")

    collections_dir = os.path.join(backup_path, "collections")
    if os.path.exists(collections_dir):
        cleanup_files(collections_dir)
    os.makedirs(collections_dir, exist_ok=True)

    # We'll derive the timestamp from the SDF's filename
    base_name = os.path.basename(sdf_file)
    # e.g. coconut_sdf_2d-03-2025.sdf -> "03-2025"
    timestamp = base_name.split(".sdf")[0].replace("coconut_sdf_2d-", "")

    print(f"Splitting SDF by collections into: {collections_dir}")

    writers = defaultdict(lambda: None)
    collection_counts = defaultdict(int)
    total_molecules_processed = 0

    def get_collections(mol):
        if mol.HasProp("collections"):
            return [c.strip() for c in mol.GetProp("collections").split("|")]
        return []

    supplier = Chem.ForwardSDMolSupplier(sdf_file, sanitize=False)
    for mol in tqdm(supplier, desc="Splitting by collections"):
        if mol is None:
            continue
        total_molecules_processed += 1
        coll_list = get_collections(mol)

        for collection in coll_list:
            slug = slugify(collection, separator='-', lowercase=True)
            out_filename = f"{slug}-{timestamp}.sdf"
            out_path = os.path.join(collections_dir, out_filename)

            if writers[collection] is None:
                writers[collection] = SDWriter(out_path)

            writers[collection].write(mol)
            collection_counts[collection] += 1

    # Close all writers
    for writer in writers.values():
        if writer is not None:
            writer.close()

    print(f"Processed {total_molecules_processed:,} molecules into {len(collection_counts)} collections")

    # Now zip each .sdf in the collections folder, remove the .sdf
    for root, _, files in os.walk(collections_dir):
        for sdf_name in files:
            if sdf_name.endswith(".sdf"):
                sdf_path = os.path.join(root, sdf_name)
                zip_file(sdf_path)  # creates .zip
                cleanup_files(sdf_path)  # remove original .sdf

# ----------------------------------------------------------------------
# 3D SDF Export
# ----------------------------------------------------------------------

def export_3d_sdf(db_params, output_file):
    """
    Exports 3D structures (MolBlocks) from PostgreSQL to an SDF file, 
    including the 'identifier'. Skips creation if the file already exists.
    """
    if os.path.exists(output_file):
        print(f"3D SDF file already exists: {output_file}")
        return

    SQL_QUERY_3D = '''
    SELECT m.identifier, s."3d"
    FROM structures s
    JOIN molecules m ON s.molecule_id = m.id
    WHERE m.identifier IS NOT NULL
      AND s."3d" IS NOT NULL
      AND m.active = TRUE
      AND (
        m.is_parent = FALSE
        OR (m.is_parent = TRUE AND m.has_variants = FALSE)
      );
    '''

    conn = None
    cursor = None
    rows = []
    try:
        conn = psycopg2.connect(**db_params)
        conn.set_client_encoding('UTF8')
        cursor = conn.cursor()

        print("Executing 3D query...")
        cursor.execute(SQL_QUERY_3D)
        rows = cursor.fetchall()
        print(f"Total 3D molecules to process: {len(rows)}")

    except Exception as e:
        print(f"Database Error: {e}")
    finally:
        if cursor:
            cursor.close()
        if conn:
            conn.close()

    def process_molecule(row):
        identifier, mol_block = row
        if not mol_block or mol_block.strip() == "":
            return None
        mol = Chem.MolFromMolBlock(mol_block)
        if mol:
            mol.SetProp("IDENTIFIER", identifier)
        return mol

    print("Processing 3D molecules...")
    molecules = []
    for row in tqdm(rows, desc="Processing 3D", unit="mol"):
        mol = process_molecule(row)
        if mol:
            molecules.append(mol)

    print(f"Writing 3D molecules to SDF file: {output_file}")
    with SDWriter(output_file) as writer:
        for mol in tqdm(molecules, desc="Writing 3D", unit="mol"):
            writer.write(mol)

    print(f"3D SDF file successfully generated: {output_file}")

# ----------------------------------------------------------------------
# S3 Upload Functions
# ----------------------------------------------------------------------

def initialize_s3_client(env_vars):
    """
    Initialize and return an S3 client using credentials from env_vars.
    env_vars should contain:
        AWS_ACCESS_KEY_ID
        AWS_SECRET_ACCESS_KEY
        AWS_DEFAULT_REGION
        AWS_BUCKET
        (and optionally AWS_URL if using a custom endpoint)
    """
    s3_config = Config(
        s3={'addressing_style': 'virtual'},
        signature_version='s3v4',
        retries={'max_attempts': 3, 'mode': 'standard'}
    )

    return boto3.client(
        's3',
        aws_access_key_id=env_vars.get('AWS_ACCESS_KEY_ID'),
        aws_secret_access_key=env_vars.get('AWS_SECRET_ACCESS_KEY'),
        region_name=env_vars.get('AWS_DEFAULT_REGION'),
        endpoint_url=env_vars.get('AWS_URL'),  # Some setups define a custom S3 endpoint
        config=s3_config
    )

def upload_file_to_s3(local_file, s3_key, env_vars):
    """
    Upload a single file to S3 at the given s3_key.
    The ACL is set to 'public-read' by default.
    """
    if not os.path.exists(local_file):
        print(f"Error: local file not found {local_file}")
        return

    s3_client = initialize_s3_client(env_vars)
    bucket_name = env_vars.get('AWS_BUCKET')
    file_size = os.path.getsize(local_file)
    content_type = mimetypes.guess_type(local_file)[0] or 'application/octet-stream'

    print(f"Uploading {local_file} -> s3://{bucket_name}/{s3_key}")
    with tqdm(total=file_size, unit='B', unit_scale=True, desc=f"Uploading {os.path.basename(local_file)}") as pbar:
        s3_client.upload_file(
            Filename=local_file,
            Bucket=bucket_name,
            Key=s3_key,
            ExtraArgs={'ContentType': content_type, 'ACL': 'public-read'},
            Callback=lambda bytes_transferred: pbar.update(bytes_transferred)
        )
    print(f"Uploaded {local_file} to s3://{bucket_name}/{s3_key}")

def upload_directory_to_s3(local_directory, s3_prefix, env_vars, skip_filename=None):
    """
    Recursively upload all files from local_directory to S3 under s3_prefix.
    If `skip_filename` is provided, that exact filename will be skipped.
    """
    if not os.path.exists(local_directory):
        print(f"Error: Local directory '{local_directory}' does not exist.")
        return

    s3_client = initialize_s3_client(env_vars)
    bucket_name = env_vars.get('AWS_BUCKET')

    files_to_upload = []
    total_size = 0

    for root, _, files in os.walk(local_directory):
        for filename in files:
            # If skip_filename is set, skip that file
            if skip_filename and filename == skip_filename:
                continue

            local_path = os.path.join(root, filename)
            relative_path = os.path.relpath(local_path, local_directory)
            s3_path = os.path.join(s3_prefix, relative_path).replace("\\", "/")

            files_to_upload.append({
                'local_path': local_path,
                's3_path': s3_path,
                'size': os.path.getsize(local_path)
            })
            total_size += files_to_upload[-1]['size']

    print(f"Uploading directory '{local_directory}' to s3://{bucket_name}/{s3_prefix}")
    with tqdm(total=total_size, unit='B', unit_scale=True, desc="Uploading directory") as pbar:
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

# ----------------------------------------------------------------------
# main
# ----------------------------------------------------------------------

def main():
    args = parse_arguments()
    env_vars = load_env(args.env_file)
    db_params = get_db_params(env_vars)

    # 1. Prepare backup directory
    backup_path = get_backup_path()
    print(f"Backup directory: {backup_path}")

    # 2. Export full and selected-table dumps (inside container)
    export_pg_dumps(args.container_id, db_params, backup_path)

    # The name of the full dump file we just created:
    today = datetime.now()
    full_dump_filename = f"coconut-dump-{today.day:02d}-{today.month:02d}-{today.year}.sql"
    full_dump_path = os.path.join(backup_path, full_dump_filename)

    # Override the host so subsequent CSV exports connect from the host
    db_params["host"] = "127.0.0.1"

    # 3. Load queries
    QUERY_WITH_COLLECTION = load_query("query_with_collection.sql")
    QUERY_WITHOUT_COLLECTION = load_query("query_without_collection.sql")

    # 4. Export CSV
    #    - coconut_csv-{timestamp}.csv
    #    - coconut_csv_lite-{timestamp}.csv
    timestamp = datetime.now().strftime("%m-%Y")
    csv_with_collection = os.path.join(backup_path, f"coconut_csv-{timestamp}.csv")
    csv_without_collection = os.path.join(backup_path, f"coconut_csv_lite-{timestamp}.csv")

    export_csv(QUERY_WITH_COLLECTION, csv_with_collection, db_params)
    export_csv(QUERY_WITHOUT_COLLECTION, csv_without_collection, db_params)
    print("Backup and CSV export completed successfully.")

    # 5. Convert CSV -> 2D SDF
    #    - coconut_sdf_2d-{timestamp}.sdf
    #    - coconut_sdf_2d_lite-{timestamp}.sdf
    sdf_filenames = generate_sdf_filenames()
    process_csv_to_sdf(
        csv_with_collection,            # CSV that has the 'collections' data
        sdf_filenames["output_sdf"],   # e.g. coconut_sdf_2d-03-2025.sdf
        sdf_filenames["light_sdf"]     # e.g. coconut_sdf_2d_lite-03-2025.sdf
    )

    # Now we can safely remove the CSV files (since we've read them into SDF)
    cleanup_files(csv_with_collection, csv_without_collection)

    # Zip the 2D SDF files, remove originals
    zip_file(sdf_filenames["output_sdf"])
    cleanup_files(sdf_filenames["output_sdf"])
    zip_file(sdf_filenames["light_sdf"])
    cleanup_files(sdf_filenames["light_sdf"])
    print("2D SDF export completed and cleaned up successfully.")

    # 6. Generate per-collection SDFs from the "complete" 2D SDF
    coconut_complete_sdf = sdf_filenames["output_sdf"]
    coconut_complete_zip = coconut_complete_sdf.replace(".sdf", ".zip")

    temp_extraction_dir = os.path.join(backup_path, "temp_sdf_extract")
    os.makedirs(temp_extraction_dir, exist_ok=True)

    if not os.path.exists(coconut_complete_sdf) and os.path.exists(coconut_complete_zip):
        # Unzip so we can read the SDF
        unzip_file(coconut_complete_zip, temp_extraction_dir)
        extracted_sdf = None
        for f in os.listdir(temp_extraction_dir):
            if f.endswith(".sdf"):
                extracted_sdf = os.path.join(temp_extraction_dir, f)
                break
        if not extracted_sdf:
            raise FileNotFoundError("No SDF file found after unzipping coconut_sdf_2d archive")

        split_sdf_into_collections(extracted_sdf, backup_path)
        cleanup_files(temp_extraction_dir)
    else:
        # If the file still exists, we can process directly
        if os.path.exists(coconut_complete_sdf):
            split_sdf_into_collections(coconut_complete_sdf, backup_path)
        else:
            print("No unzipped coconut_sdf_2d found. Skipping collections splitting.")

    print("Collections have been generated, zipped, and cleaned up successfully.")

    # 7. 3D SDF Export
    #    - coconut_sdf_3d-{timestamp}.sdf
    sdf_3d_file = os.path.join(backup_path, f"coconut_sdf_3d-{timestamp}.sdf")
    export_3d_sdf(db_params, sdf_3d_file)
    # Zip the 3D SDF, remove original
    zip_file(sdf_3d_file)
    cleanup_files(sdf_3d_file)
    print("3D SDF export completed and cleaned up successfully.")

    # 8. Upload to S3
    #    - Full dump => /backups
    #    - Everything else => prod/downloads/<year-month>/
    current_date = datetime.now()
    folder_name = f"{current_date.year}-{current_date.month:02d}"

    # 8a. Upload the full dump file (if it exists) to /backups
    if os.path.exists(full_dump_path):
        s3_key_for_full_dump = f"backups/{os.path.basename(full_dump_path)}"
        upload_file_to_s3(full_dump_path, s3_key_for_full_dump, env_vars)
    else:
        print("Warning: The full dump file does not exist; skipping S3 upload for full dump.")

    # 8b. Upload the rest (partial dump, zip files, collections folder, etc.) => prod/downloads/<year-month>/
    s3_prefix_for_rest = f"prod/downloads/{folder_name}/"
    skip_filename = None
    if os.path.exists(full_dump_path):
        skip_filename = os.path.basename(full_dump_path)

    upload_directory_to_s3(
        local_directory=backup_path,
        s3_prefix=s3_prefix_for_rest,
        env_vars=env_vars,
        skip_filename=skip_filename
    )

    print("All files have been successfully uploaded to S3.")

if __name__ == "__main__":
    main()
