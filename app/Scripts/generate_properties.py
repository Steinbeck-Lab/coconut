#!/usr/bin/env python3
"""
Script to generate molecular descriptors from a CSV of SMILES.
Uses RDKit for most descriptors and NP-score.
Uses CDK (via JPype) for sugar detection, Murcko framework, and molecular formula.

Input CSV format: id,canonical_smiles,identifier
Output TSV format: id,atom_count,heavy_atom_count,...,molecular_formula

Usage:
    python generate_properties.py input.csv --output-tsv output.tsv
"""

import argparse
import gzip
import math
import os
import pickle
from pathlib import Path
from typing import Dict, Tuple, Any

import pandas as pd
import pystow
from jpype import getDefaultJVMPath, isJVMStarted, JClass, JVMNotFoundException, startJVM
from rdkit import Chem
from rdkit.Chem import Descriptors, Lipinski, QED, AllChem, rdMolDescriptors, rdmolops
from tqdm import tqdm


# ============================================================================
# CDK Setup (Java Virtual Machine)
# ============================================================================

def setup_cdk():
    """Initialize CDK by starting JVM and downloading required JAR files."""
    try:
        jvmPath = getDefaultJVMPath()
    except JVMNotFoundException:
        print("❌ JPype cannot find jvm.dll.")
        print("   JAVA_HOME environment variable may not be set properly.")
        raise

    if not isJVMStarted():
        # Define CDK and related JAR files
        paths = {
            "cdk-2.10": "https://github.com/cdk/cdk/releases/download/cdk-2.10/cdk-2.10.jar",
            "SugarRemovalUtility-jar-with-dependencies": "https://github.com/JonasSchaub/SugarRemoval/releases/download/v1.3.2/SugarRemovalUtility-jar-with-dependencies.jar",
        }

        jar_paths = {
            key: str(pystow.join("STOUT-V2")) + f"/{key}.jar" for key in paths.keys()
        }
        
        # Download JARs if not present
        for key, url in paths.items():
            if not os.path.exists(jar_paths[key]):
                print(f"📥 Downloading {key}.jar...")
                pystow.ensure("STOUT-V2", url=url)

        # Start JVM with required classpath
        startJVM("-ea", "-Xmx4096M", classpath=[jar_paths[key] for key in jar_paths])
        print("✅ CDK JVM initialized successfully")


# ============================================================================
# CDK Helper Functions
# ============================================================================

class CDKWrapper:
    """Wrapper for CDK (Chemistry Development Kit) functions."""
    
    def __init__(self):
        """Initialize CDK base package."""
        self.cdk_base = "org.openscience.cdk"
        self.sru_base = "de.unijena.cheminf.deglycosylation"
        self.SCOB = JClass(self.cdk_base + ".silent.SilentChemObjectBuilder")
    
    def smiles_to_iatomcontainer(self, smiles: str):
        """Convert SMILES to CDK IAtomContainer."""
        SmilesParser = JClass(
            self.cdk_base + ".smiles.SmilesParser",
        )(self.SCOB.getInstance())
        molecule = SmilesParser.parseSmiles(smiles)
        return molecule
    
    def get_murcko_framework(self, molecule) -> str:
        """Get Murcko framework SMILES using CDK."""
        try:
            MurckoFragmenter = JClass(self.cdk_base + ".fragment.MurckoFragmenter")(True, 3)
            MurckoFragmenter.generateFragments(molecule)
            if len(MurckoFragmenter.getFrameworks()) == 0:
                return "None"
            return str(MurckoFragmenter.getFrameworks()[0])
        except:
            return "None"
    
    def get_molecular_formula(self, molecule) -> str:
        """Get molecular formula using CDK."""
        try:
            MolecularFormulaManipulator = JClass(
                self.cdk_base + ".tools.manipulator.MolecularFormulaManipulator"
            )
            MolecularFormula = MolecularFormulaManipulator.getMolecularFormula(molecule)
            return MolecularFormulaManipulator.getString(MolecularFormula)
        except:
            return ""
    
    def detect_sugars(self, molecule) -> Tuple[bool, bool]:
        """Detect linear and circular sugars using CDK SugarRemovalUtility."""
        try:
            SugarRemovalUtility = JClass(self.sru_base + ".SugarRemovalUtility")(
                self.SCOB.getInstance(),
            )
            hasCircularOrLinearSugars = SugarRemovalUtility.hasCircularOrLinearSugars(molecule)
            
            if hasCircularOrLinearSugars:
                hasLinearSugar = SugarRemovalUtility.hasLinearSugars(molecule)
                hasCircularSugars = SugarRemovalUtility.hasCircularSugars(molecule)
                return (hasLinearSugar, hasCircularSugars)
            else:
                return (False, False)
        except:
            return (False, False)


class DescriptorGenerator:
    """Generates molecular descriptors using RDKit and CDK."""
    
    def __init__(self):
        """Initialize the generator and load NP-likeness model."""
        # Initialize CDK
        setup_cdk()
        self.cdk = CDKWrapper()
        
        # Load NP-likeness model
        default_path = pystow.join("NP_model")
        model_url = "https://github.com/rdkit/rdkit/blob/master/Contrib/NP_Score/publicnp.model.gz?raw=true"
        model_path = str(default_path) + "/publicnp.model.gz"
        
        if not os.path.exists(model_path):
            print("📥 Downloading NP-likeness model...")
            pystow.ensure("NP_model", url=model_url)
        
        self.np_model = pickle.load(gzip.open(model_path))
        print("✅ NP-likeness model loaded successfully")
    
    def check_RO5_violations(self, molecule) -> int:
        """Check Lipinski's Rule of Five violations."""
        num_of_violations = 0
        if Descriptors.MolLogP(molecule) > 5:
            num_of_violations += 1
        if Descriptors.MolWt(molecule) > 500:
            num_of_violations += 1
        if Lipinski.NumHAcceptors(molecule) > 10:
            num_of_violations += 1
        if Lipinski.NumHDonors(molecule) > 5:
            num_of_violations += 1
        return num_of_violations
    
    def get_MolVolume(self, molecule) -> float:
        """Calculate molecular volume."""
        try:
            mol = Chem.AddHs(molecule)
            AllChem.EmbedMolecule(mol, useRandomCoords=True)
            volume = AllChem.ComputeMolVolume(mol, gridSpacing=0.2)
            return volume
        except:
            return 0.0
    
    def calculate_np_score(self, molecule) -> float:
        """Calculate NP-likeness score using RDKit."""
        try:
            fp = rdMolDescriptors.GetMorganFingerprint(molecule, 2)
            bits = fp.GetNonzeroElements()
            
            score = 0.0
            for bit in bits:
                if bit in self.np_model:
                    score += self.np_model[bit]
            
            score /= float(molecule.GetNumAtoms())
            
            # Prevent score explosion
            if score > 4:
                score = 4.0 + math.log10(score - 4.0 + 1.0)
            elif score < -4:
                score = -4.0 - math.log10(-4.0 - score + 1.0)
            
            return round(score, 2)
        except:
            return 0.0
    
    def generate_descriptors(self, smiles: str) -> Dict[str, Any]:
        """
        Generate molecular descriptors for a single SMILES string.
        
        Args:
            smiles: SMILES string
            
        Returns:
            Dictionary with keys:
                - success: bool
                - descriptors: dict (if successful)
                - error: str (if failed)
        """
        try:
            # Parse SMILES
            mol = Chem.MolFromSmiles(smiles)
            if mol is None:
                return {
                    "success": False,
                    "error": "Invalid SMILES - could not parse"
                }
            
            # Convert to CDK molecule for CDK-specific calculations
            try:
                cdk_mol = self.cdk.smiles_to_iatomcontainer(smiles)
                linear_sugars, circular_sugars = self.cdk.detect_sugars(cdk_mol)
                murcko_framework = self.cdk.get_murcko_framework(cdk_mol)
                molecular_formula = self.cdk.get_molecular_formula(cdk_mol)
            except Exception as e:
                # If CDK fails, use default values
                linear_sugars, circular_sugars = False, False
                murcko_framework = "None"
                molecular_formula = rdMolDescriptors.CalcMolFormula(mol)  # Fallback to RDKit
            
            # Calculate RDKit descriptors
            descriptors = {
                "id": None,  # Will be filled in process_csv
                "atom_count": rdMolDescriptors.CalcNumAtoms(mol),
                "heavy_atom_count": rdMolDescriptors.CalcNumHeavyAtoms(mol),
                "molecular_weight": round(Descriptors.MolWt(mol), 2),
                "exact_molecular_weight": round(Descriptors.ExactMolWt(mol), 5),
                "alogp": round(QED.properties(mol).ALOGP, 2),
                "rotatable_bond_count": rdMolDescriptors.CalcNumRotatableBonds(mol),
                "topological_polar_surface_area": round(rdMolDescriptors.CalcTPSA(mol), 2),
                "hydrogen_bond_acceptors": Descriptors.NumHAcceptors(mol),
                "hydrogen_bond_donors": Descriptors.NumHDonors(mol),
                "hydrogen_bond_acceptors_lipinski": Lipinski.NumHAcceptors(mol),
                "hydrogen_bond_donors_lipinski": Lipinski.NumHDonors(mol),
                "lipinski_rule_of_five_violations": self.check_RO5_violations(mol),
                "aromatic_rings_count": rdMolDescriptors.CalcNumAromaticRings(mol),
                "qed_drug_likeliness": round(QED.qed(mol), 2),
                "formal_charge": rdmolops.GetFormalCharge(mol),
                "fractioncsp3": round(rdMolDescriptors.CalcFractionCSP3(mol), 3),
                "number_of_minimal_rings": rdMolDescriptors.CalcNumRings(mol),
                "van_der_waals_volume": round(self.get_MolVolume(mol), 2),
                "linear_sugars": linear_sugars,
                "circular_sugars": circular_sugars,
                "murcko_framework": murcko_framework,
                "nplikeness": self.calculate_np_score(mol),
                "molecular_formula": molecular_formula
            }
            
            return {
                "success": True,
                "descriptors": descriptors
            }
            
        except Exception as e:
            return {
                "success": False,
                "error": f"Unexpected error: {str(e)}"
            }
    
    def process_csv(
        self,
        input_csv: str,
        output_tsv: str = "descriptors.tsv"
    ):
        """
        Process a CSV file containing SMILES strings.
        Expected CSV format: id,canonical_smiles,identifier
        Supports resume: If output CSV exists, skips already processed molecules.
        
        Args:
            input_csv: Path to input CSV file
            output_csv: Path to output CSV file
        """
        # Read CSV
        df = pd.read_csv(input_csv)
        print(f"📁 Loaded {len(df)} rows from {input_csv}")
        
        # Sort by SMILES length (shortest first)
        df['smiles_length'] = df['canonical_smiles'].str.len()
        df = df.sort_values('smiles_length')
        df = df.drop('smiles_length', axis=1)
        print(f"📊 Sorted by SMILES length (shortest first)")
        
        # Load existing results if file exists (for resume capability)
        processed_ids = set()
        results_list = []
        
        if Path(output_tsv).exists():
            existing_df = pd.read_csv(output_tsv, sep='\t')
            processed_ids = set(existing_df['id'].astype(str))
            results_list = existing_df.to_dict('records')
            print(f"📂 Found existing results: {len(processed_ids)} molecules already processed")
        
        # Process each SMILES
        success_count = len(processed_ids)
        fail_count = 0
        skipped_count = len(processed_ids)
        
        print(f"\n🔄 Generating descriptors using RDKit and CDK...\n")
        
        for idx, row in tqdm(df.iterrows(), total=len(df), desc="Processing"):
            smiles = row['canonical_smiles']
            mol_id = str(row['id'])
            
            # Skip if already processed
            if mol_id in processed_ids:
                continue
            
            # Generate descriptors
            result = self.generate_descriptors(smiles)
            
            # Build result row
            if result["success"]:
                desc = result["descriptors"]
                desc['id'] = mol_id
                results_list.append(desc)
                success_count += 1
                
                # Save progress periodically (every 100 molecules)
                if len(results_list) % 100 == 0:
                    pd.DataFrame(results_list).to_csv(output_tsv, sep='\t', index=False)
            else:
                fail_count += 1
                # Still save failed entries with NULL values
                failed_row = {
                    'id': mol_id,
                    'atom_count': None,
                    'heavy_atom_count': None,
                    'molecular_weight': None,
                    'exact_molecular_weight': None,
                    'alogp': None,
                    'rotatable_bond_count': None,
                    'topological_polar_surface_area': None,
                    'hydrogen_bond_acceptors': None,
                    'hydrogen_bond_donors': None,
                    'hydrogen_bond_acceptors_lipinski': None,
                    'hydrogen_bond_donors_lipinski': None,
                    'lipinski_rule_of_five_violations': None,
                    'aromatic_rings_count': None,
                    'qed_drug_likeliness': None,
                    'formal_charge': None,
                    'fractioncsp3': None,
                    'number_of_minimal_rings': None,
                    'van_der_waals_volume': None,
                    'linear_sugars': None,
                    'circular_sugars': None,
                    'murcko_framework': None,
                    'nplikeness': None,
                    'molecular_formula': None
                }
                results_list.append(failed_row)
        
        # Final save
        final_df = pd.DataFrame(results_list)
        final_df.to_csv(output_tsv, sep='\t', index=False)
        print(f"\n✅ TSV output saved to: {output_tsv}")
        
        # Print statistics
        total = len(df)
        print(f"\n📊 Results:")
        if skipped_count > 0:
            print(f"   ⏭️  Skipped (already processed): {skipped_count}/{total}")
        print(f"   ✓ Successful: {success_count}/{total}")
        print(f"   ✗ Failed: {fail_count}/{total}")


def main():
    """Main entry point for the script."""
    parser = argparse.ArgumentParser(
        description="Generate molecular descriptors from CSV of SMILES using RDKit and CDK",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Example:
  python generate_properties.py molecules.csv --output-tsv descriptors.tsv
  
Input CSV format: id,canonical_smiles,identifier
Output TSV format: id,atom_count,heavy_atom_count,...,molecular_formula

Note: This script requires:
  - RDKit for most descriptors and NP-score
  - CDK (via JPype) for sugar detection, Murcko framework, and molecular formula
  - Java Runtime Environment (JRE) must be installed
        """
    )
    
    parser.add_argument(
        "input_csv",
        help="Path to input CSV file (format: id,canonical_smiles,identifier)"
    )
    base_temp = os.path.join(os.path.dirname(os.path.abspath(__file__)), '../../storage/app/public')
    os.makedirs(base_temp, exist_ok=True)
    parser.add_argument(
        "--output-tsv",
        default=os.path.join(base_temp, "descriptors.tsv"),
        help=f"Output TSV file path (default: {base_temp}/descriptors.tsv)"
    )

    args = parser.parse_args()

    # Always force output_tsv to be in tmp folder
    base_temp = os.path.join(os.path.dirname(os.path.abspath(__file__)), '../../storage/app/public')
    os.makedirs(base_temp, exist_ok=True)
    output_tsv_filename = os.path.basename(args.output_tsv)
    args.output_tsv = os.path.join(base_temp, output_tsv_filename)

    # Create generator
    try:
        print("🚀 Initializing descriptor generator...")
        generator = DescriptorGenerator()
    except Exception as e:
        print(f"❌ Error initializing generator: {str(e)}")
        print("\nMake sure you have:")
        print("  1. Java Runtime Environment (JRE) installed")
        print("  2. JAVA_HOME environment variable set")
        print("  3. Required Python packages: rdkit, jpype1, pystow, pandas, tqdm")
        return 1

    # Process CSV
    try:
        generator.process_csv(
            input_csv=args.input_csv,
            output_tsv=args.output_tsv
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
