#!/usr/bin/env python3
"""
Script to generate 2D and 3D coordinates from a CSV of SMILES using RDKit.

Input CSV format: id,canonical_smiles,identifier
Output JSON format: {id: {smiles: ..., 2d: molblock, 3d: molblock}}

Usage:
    python generate_coordinates.py input.csv --output-json output.json
"""

import argparse
import json
from pathlib import Path
from typing import Optional, Dict, List, Any
import os

import pandas as pd
from rdkit import Chem
from rdkit.Chem import AllChem
from tqdm import tqdm


class CoordinateGenerator:
    """Generates 2D and 3D coordinates using RDKit."""
    
    def __init__(self):
        """Initialize the generator with API defaults."""
        pass
    
    def generate_3d_coordinates(self, smiles: str) -> Dict[str, Any]:
        """
        Generate 3D coordinates for a single SMILES string.
        
        Args:
            smiles: SMILES string
            
        Returns:
            Dictionary with keys:
                - success: bool
                - molblock: str (if successful)
                - error: str (if failed)
                - num_atoms: int (if successful)
        """
        try:
            # Parse SMILES
            mol = Chem.MolFromSmiles(smiles)
            if mol is None:
                return {
                    "success": False,
                    "error": "Invalid SMILES - could not parse"
                }
            
            # Add hydrogens for 3D generation
            mol = Chem.AddHs(mol)
            num_atoms = mol.GetNumAtoms()
            
            # Reduce attempts for large molecules to avoid hanging
            max_attempts = 100 if num_atoms > 100 else 5000
            
            # Generate 3D coordinates
            result = AllChem.EmbedMolecule(mol, maxAttempts=max_attempts, useRandomCoords=True)
            
            if result != 0:
                return {
                    "success": False,
                    "error": f"Could not embed molecule ({num_atoms} atoms)"
                }
            
            # Optimize with MMFF
            try:
                AllChem.MMFFOptimizeMolecule(mol)
            except Exception:
                # If MMFF fails, try re-embedding once
                result = AllChem.EmbedMolecule(mol, maxAttempts=max_attempts, useRandomCoords=True)
                if result != 0:
                    return {
                        "success": False,
                        "error": f"MMFF optimization failed and re-embedding failed ({num_atoms} atoms)"
                    }
            
            # Remove hydrogens from output
            mol = Chem.RemoveHs(mol)
            
            # Convert to molblock
            molblock = Chem.MolToMolBlock(mol)
            
            return {
                "success": True,
                "molblock": molblock,
                "num_atoms": mol.GetNumAtoms()
            }
            
        except Exception as e:
            return {
                "success": False,
                "error": f"Unexpected error: {str(e)}"
            }
    
    def generate_2d_coordinates(self, smiles: str) -> Dict[str, Any]:
        """
        Generate 2D coordinates for a single SMILES string.
        
        Args:
            smiles: SMILES string
            
        Returns:
            Dictionary with keys:
                - success: bool
                - molblock: str (if successful)
                - error: str (if failed)
                - num_atoms: int (if successful)
        """
        try:
            # Parse SMILES
            mol = Chem.MolFromSmiles(smiles)
            if mol is None:
                return {
                    "success": False,
                    "error": "Invalid SMILES - could not parse"
                }
            
            # Generate 2D coordinates
            AllChem.Compute2DCoords(mol)
            
            # Convert to molblock
            molblock = Chem.MolToMolBlock(mol)
            
            return {
                "success": True,
                "molblock": molblock,
                "num_atoms": mol.GetNumAtoms()
            }
            
        except Exception as e:
            return {
                "success": False,
                "error": f"Unexpected error: {str(e)}"
            }
    
    def process_csv(
        self,
        input_csv: str,
        output_json: str = "coordinates.json"
    ) -> None:
        """
        Process a CSV file containing SMILES strings.
        Expected CSV format: id,canonical_smiles,identifier
        Supports resume: If output JSON exists, skips already processed molecules.
        
        Args:
            input_csv: Path to input CSV file
            output_json: Path to output JSON file
            
        Returns:
            DataFrame with results summary
        """
        # Read CSV (expected columns: id, canonical_smiles, identifier)
        df = pd.read_csv(input_csv)
        print(f"📁 Loaded {len(df)} rows from {input_csv}")
        
        # Sort by SMILES length (shortest first) for faster initial processing
        df['smiles_length'] = df['canonical_smiles'].str.len()
        df = df.sort_values('smiles_length')
        df = df.drop('smiles_length', axis=1)
        print(f"📊 Sorted by SMILES length (shortest first)")
        
        # Load existing results if file exists (for resume capability)
        results_json = {}
        if Path(output_json).exists():
            with open(output_json, 'r') as f:
                results_json = json.load(f)
            print(f"📂 Found existing results: {len(results_json)} molecules already processed")
        
        # Process each SMILES
        success_count = 0
        fail_count = 0
        skipped_count = 0
        total_to_process = len(df)
        processed_count = 0
        
        print(f"\n🔄 Generating coordinates using RDKit...\n")
        
        for _, row in tqdm(df.iterrows(), total=len(df), desc="Processing"):
            smiles = row['canonical_smiles']
            mol_id = str(row['id'])
            
            # Skip if already processed
            if mol_id in results_json:
                skipped_count += 1
                # Count existing result as success or failure
                if "error" not in results_json[mol_id]:
                    success_count += 1
                else:
                    fail_count += 1
                continue
            
            # Initialize result structure
            mol_result = {"smiles": smiles}
            success = True
            error_msgs = []
            
            # Generate 2D coordinates (fast)
            result_2d = self.generate_2d_coordinates(smiles)
            if result_2d["success"]:
                mol_result["2d"] = result_2d["molblock"] + "$$$$\n"
            else:
                mol_result["2d"] = None
                success = False
                error_msgs.append(f"2D: {result_2d.get('error', '')}")
            
            # Generate 3D coordinates (can be slow for large molecules)
            result_3d = self.generate_3d_coordinates(smiles)
            if result_3d["success"]:
                mol_result["3d"] = result_3d["molblock"] + "$$$$\n"
            else:
                mol_result["3d"] = None
                success = False
                error_msgs.append(f"3D: {result_3d.get('error', '')}")
            
            # Add error if any
            if error_msgs:
                mol_result["error"] = "; ".join(error_msgs)
            
            results_json[mol_id] = mol_result
            
            # Update counters
            if success:
                success_count += 1
            else:
                fail_count += 1
            
            processed_count += 1
            
            # Save progress periodically (every 10 molecules)
            if processed_count % 10 == 0:
                with open(output_json, 'w') as f:
                    json.dump(results_json, f, indent=2)
        
        # Final save of JSON output
        with open(output_json, 'w') as f:
            json.dump(results_json, f, indent=2)
        print(f"\n✅ JSON output saved to: {output_json}")
        
        # Print statistics
        total = len(df)
        print(f"\n📊 Results:")
        if skipped_count > 0:
            print(f"   ⏭️  Skipped (already processed): {skipped_count}/{total}")
        print(f"   ✓ Successful: {success_count}/{total}")
        print(f"   ✗ Failed: {fail_count}/{total}")
        
        return None


def main():
    """Main entry point for the script."""
    parser = argparse.ArgumentParser(
        description="Generate 2D and 3D coordinates from CSV of SMILES using RDKit",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Example:
  python fetch_3d_coordinates.py molecules.csv --output-json output.json
  
Input CSV format: id,canonical_smiles,identifier
Output JSON format: {id: {smiles: ..., 2d: ..., 3d: ...}}
        """
    )
    
    parser.add_argument(
        "input_csv",
        help="Path to input CSV file (format: id,canonical_smiles,identifier)"
    )
    base_temp = os.path.join(os.path.dirname(os.path.abspath(__file__)), '../../storage/app/tmp')
    os.makedirs(base_temp, exist_ok=True)
    parser.add_argument(
        "--output-json",
        default=os.path.join(base_temp, "coordinates.json"),
        help=f"Output JSON file path (default: {base_temp}/coordinates.json)"
    )
    
    args = parser.parse_args()
    # Always force output_json to be in tmp folder
    output_json_filename = os.path.basename(args.output_json)
    args.output_json = os.path.join(base_temp, output_json_filename)
    
    # Create generator
    generator = CoordinateGenerator()
    
    # Process CSV
    try:
        generator.process_csv(
            input_csv=args.input_csv,
            output_json=args.output_json
        )
        print("\n✨ Processing complete!")
        
    except FileNotFoundError:
        print(f"❌ Error: Input file '{args.input_csv}' not found")
        return 1
    except Exception as e:
        print(f"❌ Error: {str(e)}")
        import traceback
        traceback.print_exc()
        return 1
    
    return 0


if __name__ == "__main__":
    exit(main())
