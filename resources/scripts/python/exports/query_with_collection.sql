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
),
molecule_organisms AS (
    SELECT 
        m.id,
        STRING_AGG(DISTINCT o.name, '|' ORDER BY o.name) AS organisms
    FROM 
        molecules m
    INNER JOIN 
        molecule_organism mo ON m.id = mo.molecule_id
    INNER JOIN 
        organisms o ON mo.organism_id = o.id
    WHERE 
        m.identifier IS NOT NULL 
        AND m.active = TRUE 
        AND (m.is_parent = FALSE OR (m.is_parent = TRUE AND m.has_variants = FALSE))
    GROUP BY 
        m.id
),
molecule_collections AS (
    SELECT 
        m.id,
        STRING_AGG(c.title, '|' ORDER BY c.title) AS collections
    FROM 
        molecules m
    INNER JOIN 
        collection_molecule cm ON m.id = cm.molecule_id
    INNER JOIN 
        collections c ON cm.collection_id = c.id
    WHERE 
        m.identifier IS NOT NULL 
        AND m.active = TRUE 
        AND (m.is_parent = FALSE OR (m.is_parent = TRUE AND m.has_variants = FALSE))
    GROUP BY 
        m.id
),
molecule_citations AS (
    SELECT  
        m.id,
        STRING_AGG(c.doi, '|' ORDER BY c.doi) AS dois
    FROM 
        molecules m
    INNER JOIN 
        citables ct ON m.id = ct.citable_id AND ct.citable_type = 'App\Models\Molecule'
    INNER JOIN 
        citations c ON ct.citation_id = c.id
    WHERE 
        m.identifier IS NOT NULL 
        AND m.active = TRUE 
        AND (m.is_parent = FALSE OR (m.is_parent = TRUE AND m.has_variants = FALSE))
        AND c.doi IS NOT NULL
        AND c.doi <> ''
    GROUP BY 
        m.id
),
molecule_synonyms_cas AS (
    SELECT 
        m.id,
        REGEXP_REPLACE(REPLACE(m.synonyms::text, ',', '|'), '\[|\]|"', '', 'g') AS synonyms, 
        REGEXP_REPLACE(REPLACE(m.cas::text, ',', '|'), '\[|\]|"', '', 'g') AS cas
    FROM 
        molecules m
    WHERE 
        m.identifier IS NOT NULL 
        AND m.active = TRUE 
        AND (m.is_parent = FALSE OR (m.is_parent = TRUE AND m.has_variants = FALSE))
)
SELECT 
    mp.identifier, 
    mp.canonical_smiles, 
    mp.standard_inchi, 
    mp.standard_inchi_key, 
    mp.name, 
    mp.iupac_name, 
    mp.total_atom_count,
    mp.heavy_atom_count,
    mp.molecular_weight,
    mp.exact_molecular_weight,
    mp.molecular_formula,
    mp.alogp,
    mp.topological_polar_surface_area,
    mp.rotatable_bond_count,
    mp.hydrogen_bond_acceptors,
    mp.hydrogen_bond_donors,
    mp.hydrogen_bond_acceptors_lipinski,
    mp.hydrogen_bond_donors_lipinski,
    mp.lipinski_rule_of_five_violations,
    mp.aromatic_rings_count,
    mp.qed_drug_likeliness,
    mp.formal_charge,
    mp.fractioncsp3,
    mp.number_of_minimal_rings,
    mp.van_der_walls_volume,
    mp.contains_sugar,
    mp.contains_ring_sugars,
    mp.contains_linear_sugars,
    mp.murcko_framework,
    mp.np_likeness,
    mp.chemical_class,
    mp.chemical_sub_class,
    mp.chemical_super_class,
    mp.direct_parent_classification,
    mp.np_classifier_pathway,
    mp.np_classifier_superclass,
    mp.np_classifier_class,
    mp.np_classifier_is_glycoside,
    mo.organisms,
    mc.collections,
    cit.dois,
    sc.synonyms,
    sc.cas
FROM 
    molecule_properties mp
LEFT JOIN 
    molecule_organisms mo ON mp.id = mo.id
LEFT JOIN 
    molecule_collections mc ON mp.id = mc.id
LEFT JOIN 
    molecule_citations cit ON mp.id = cit.id
LEFT JOIN 
    molecule_synonyms_cas sc ON mp.id = sc.id;
