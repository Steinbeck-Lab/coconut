# COCONUT online - Data analysis

- As shown in the [sources](collections.html) section, COCONUT data has been extracted from 53 data sources and several have been manually collected from literature sets.

- Currently, the COCONUT release (June 2023) contains 406,919 unique "flat" (without stereochemistry) NPs, and 730,441 NPs whose stereochemistry is preserved. Please refer to the original [paper](https://doi.org/10.1186/s13321-020-00478-9) for more details.

- We extensively use the [ChEMBL](https://www.ebi.ac.uk/chembl/) [structure curation pipeline](https://jcheminf.biomedcentral.com/articles/10.1186/s13321-020-00456-1) developed with [RDKit](https://www.rdkit.org/) to clean the data and curate the database.

## Curation steps

- The snapshot of the mongoDB database form the COCONUT release 2022 was taken as the primary source,
  * Polyfluorinated compounds (64 in total) were removed
  * Structures that cannot be parsed by the ChEMBL structure curation pipeline (113 in total) have been removed.
  * Duplicates have been merged into one entry and the highly annotated entry has been made the parent entry, and the remainder is now included in the parent entry.