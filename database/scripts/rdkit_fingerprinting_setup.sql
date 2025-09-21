-- RDKit Extension and Fingerprinting Setup
-- This script sets up RDKit extension and creates fingerprinting tables and indexes
-- for molecular similarity searches and substructure matching

-- Enable RDKit extension
CREATE EXTENSION IF NOT EXISTS rdkit;

-- Create molecules table with RDKit mol objects
-- Extract valid molecules from the molecules table
SELECT * INTO mols 
FROM (
    SELECT id, mol_from_smiles(canonical_smiles::cstring) m  
    FROM molecules
) tmp 
WHERE m IS NOT NULL;

-- Create GiST index on molecular structures for substructure searches
CREATE INDEX molidx ON mols USING gist(m);

-- Add primary key to mols table
ALTER TABLE mols ADD PRIMARY KEY (id);

-- Create fingerprints table with various fingerprint types
-- torsionbv_fp: Torsion fingerprints for conformational analysis
-- morganbv_fp: Morgan fingerprints (circular fingerprints) for similarity
-- featmorganbv_fp: Feature-based Morgan fingerprints for pharmacophore similarity
SELECT 
    id, 
    torsionbv_fp(m) as torsionbv,
    morganbv_fp(m) as mfp2, 
    featmorganbv_fp(m) as ffp2 
INTO fps 
FROM mols;

-- Create GiST indexes on fingerprints for fast similarity searches
CREATE INDEX fps_ttbv_idx ON fps USING gist(torsionbv);
CREATE INDEX fps_mfp2_idx ON fps USING gist(mfp2);
CREATE INDEX fps_ffp2_idx ON fps USING gist(ffp2);

-- Add primary key to fps table
ALTER TABLE fps ADD PRIMARY KEY (id);
