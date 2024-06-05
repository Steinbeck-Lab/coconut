CREATE TEMPORARY TABLE temp_organism_counts AS
SELECT 
    mo.molecule_id, 
    COUNT(mo.organism_id) AS organism_count
FROM 
    molecule_organism mo
GROUP BY 
    mo.molecule_id;

-- Then, update the molecules table using the temporary table
UPDATE molecules m
SET organism_count = toc.organism_count
FROM temp_organism_counts toc
WHERE m.id = toc.molecule_id;

-- Optionally, drop the temporary table if no longer needed
DROP TABLE temp_organism_counts;


-- First, create a temporary table to store the counts
CREATE TEMPORARY TABLE temp_geo_counts AS
SELECT 
    mgl.molecule_id, 
    COUNT(mgl.geo_location_id) AS geo_count
FROM 
    geo_location_molecule mgl
GROUP BY 
    mgl.molecule_id;

-- Then, update the molecules table using the temporary table
UPDATE molecules m
SET geo_count = tg.geo_count
FROM temp_geo_counts tg
WHERE m.id = tg.molecule_id;

-- Optionally, drop the temporary table if no longer needed
DROP TABLE temp_geo_counts;


-- First, create a temporary table to store the counts
CREATE TEMPORARY TABLE temp_collection_counts AS
SELECT 
    mc.molecule_id, 
    COUNT(mc.collection_id) AS collection_count
FROM 
    collection_molecule mc
GROUP BY 
    mc.molecule_id;

-- Then, update the molecules table using the temporary table
UPDATE molecules m
SET collection_count = tc.collection_count
FROM temp_collection_counts tc
WHERE m.id = tc.molecule_id;

-- Optionally, drop the temporary table if no longer needed
DROP TABLE temp_collection_counts;


-- First, create a temporary table to store the counts
CREATE TEMPORARY TABLE temp_citation_counts AS
SELECT 
    mc.citable_id, 
    COUNT(mc.citation_id) AS citation_count
FROM 
    citables mc
GROUP BY 
    mc.citable_id;

-- Then, update the molecules table using the temporary table
UPDATE molecules m
SET citation_count = tc.citation_count
FROM temp_citation_counts tc
WHERE m.id = tc.citable_id;

-- Optionally, drop the temporary table if no longer needed
DROP TABLE temp_citation_counts;

UPDATE molecules
SET synonym_count = json_array_length(synonyms);