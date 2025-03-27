import argparse
import os
import requests
import zipfile
from rdkit import Chem


def download_and_extract_sdf(zip_url, extract_to):
    """
    Downloads and extracts an SDF file from a given URL.
    
    Parameters:
        zip_url (str): URL of the zip file containing the SDF.
        extract_to (str): Folder where the zip file should be extracted.
    """
    zip_path = os.path.join(extract_to, "input_sdf.zip")
    sdf_path = None

    if not os.path.exists(extract_to):
        os.makedirs(extract_to)

    print(f"Downloading {zip_url}...")
    response = requests.get(zip_url, stream=True)
    with open(zip_path, "wb") as file:
        for chunk in response.iter_content(chunk_size=1024):
            if chunk:
                file.write(chunk)
    print("Extracting zip file...")
    with zipfile.ZipFile(zip_path, 'r') as zip_ref:
        zip_ref.extractall(extract_to)
        sdf_files = [f for f in zip_ref.namelist() if f.endswith(".sdf")]
        if sdf_files:
            sdf_path = os.path.join(extract_to, sdf_files[0])
    os.remove(zip_path)  # Clean up zip file
    return sdf_path

def split_sdf(input_sdf, output_folder, batch_size=10000):
    """
    Splits a large SDF file into smaller batches.
    Parameters:
        input_sdf (str): Path to the input SDF file.
        output_folder (str): Folder where split files will be saved.
        batch_size (int): Number of compounds per batch.
    """
    if not output_folder:
        output_folder = os.path.join(os.getcwd(), "COCONUT_DATA")
    else:
        output_folder = os.path.join(os.getcwd(), output_folder)
    if not os.path.exists(output_folder):
        os.makedirs(output_folder)
    suppl = Chem.SDMolSupplier(input_sdf)
    batch_count = 1
    writer = None
    mol_count = 0
    for mol in suppl:
        if mol is None:
            continue  # Skip invalid molecules
        if mol_count % batch_size == 0:
            if writer:
                writer.close()
            output_file = os.path.join(output_folder, f"batch_{batch_count}.sdf")
            writer = Chem.SDWriter(output_file)
            batch_count += 1
        writer.write(mol)
        mol_count += 1
    if writer:
        writer.close()
    print(f"Splitting completed. {batch_count - 1} files created in '{output_folder}'.")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Download, extract, and split an SDF file into smaller batches.")
    parser.add_argument("output_folder", type=str, nargs='?', default="COCONUT_DATA", help="Folder where split files will be saved (default: COCONUT_DATA).")
    parser.add_argument("batch_size", type=int, nargs='?', default=10000, help="Number of compounds per batch (default: 10000).")
    args = parser.parse_args()
    zip_url = "https://coconut.s3.uni-jena.de/prod/downloads/2024-10/coconut-10-2024.sdf.zip"
    extract_to = os.path.join(os.getcwd(), "COCONUT_DATA")
    input_sdf = download_and_extract_sdf(zip_url, extract_to)
    if input_sdf:
        split_sdf(input_sdf, args.output_folder, args.batch_size)
    else:
        print("Error: No SDF file found in the extracted content.")
