WITH molecule_properties AS (
    SELECT 
        molecules.id,
        molecules.identifier,
        molecules.canonical_smiles, 
        molecules.standard_inchi, 
        molecules.standard_inchi_key, 
        molecules.name, 
        molecules.iupac_name,     
        molecules.annotation_level,
        properties.total_atom_count,
        properties.heavy_atom_count,
        properties.molecular_weight,
        properties.exact_molecular_weight,
        properties.molecular_formula,
        properties.alogp,
        properties.topological_polar_surface_area,
        properties.rotatable_bond_count,
        properties.hydrogen_bond_acceptors,
        properties.hydrogen_bond_donors,
        properties.hydrogen_bond_acceptors_lipinski,
        properties.hydrogen_bond_donors_lipinski,
        properties.lipinski_rule_of_five_violations,
        properties.aromatic_rings_count,
        properties.qed_drug_likeliness,
        properties.formal_charge,
        properties.fractioncsp3,
        properties.number_of_minimal_rings,
        properties.van_der_walls_volume,
        properties.contains_sugar,
        properties.contains_ring_sugars,
        properties.contains_linear_sugars,
        properties.murcko_framework,
        properties.np_likeness,
        properties.chemical_class,
        properties.chemical_sub_class,
        properties.chemical_super_class,
        properties.direct_parent_classification,
        properties.np_classifier_pathway,
        properties.np_classifier_superclass,
        properties.np_classifier_class,
        properties.np_classifier_is_glycoside
    FROM 
        molecules
    INNER JOIN 
        properties 
    ON 
        molecules.id = properties.molecule_id
    WHERE
        molecules.identifier IS NOT NULL 
        AND molecules.active = TRUE 
        AND (molecules.is_parent = FALSE OR (molecules.is_parent = TRUE AND molecules.has_variants = FALSE))
)
SELECT * FROM molecule_properties;

