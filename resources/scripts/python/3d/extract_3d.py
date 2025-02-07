import os
import json
import pandas as pd
from rdkit import Chem
from rdkit.Chem import SDWriter
from tqdm import tqdm  # For progress bar

# === CONFIGURATION ===
csv_file = "/Users/chandu-jena/Development/nfdi4chem/coconut/resources/scripts/python/3d/coconut_30_01_2025.csv"
input_folder = "/Users/chandu-jena/Development/nfdi4chem/coconut/resources/scripts/python/3d/molecules_3d"
output_sdf = "coconut_30_01_2025_3D.sdf"

# === STEP 1: READ CSV & GET IDENTIFIERS ===
df = pd.read_csv(csv_file, usecols=["identifier"])  # Load only necessary column
identifiers = set(df["identifier"].astype(str))  # Convert to string & store as a set for fast lookup
print(f"✅ Loaded {len(identifiers)} unique identifiers from CSV")

# === STEP 2: PROCESS JSON FILES ===
json_files = [os.path.join(input_folder, f) for f in os.listdir(input_folder) if f.endswith(".json")]
writer = SDWriter(output_sdf)

matched_count = 0
failed_count = 0

for json_path in tqdm(json_files, desc="Processing JSON Files", unit="file"):
    try:
        with open(json_path, "r", encoding="utf-8") as file:
            data = json.load(file)
    except (json.JSONDecodeError, FileNotFoundError) as e:
        print(f"⚠️ Skipping {json_path}: {e}")
        continue

    # Filter JSON keys to only those present in CSV
    matched_keys = identifiers.intersection(data.keys())

    for key in matched_keys:
        mol_block = data[key]
        mol = Chem.MolFromMolBlock(mol_block, sanitize=False, removeHs=False)

        if mol:
            mol.SetProp("coconut_id", key)  # Store key as a property
            writer.write(mol)
            matched_count += 1
        else:
            failed_count += 1

# === STEP 3: CLOSE WRITER & SUMMARY ===
writer.close()
print(f"\n✅ Exported {matched_count} molecules to {output_sdf}")
if failed_count:
    print(f"⚠️ {failed_count} molecules failed to parse.")
