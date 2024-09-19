# Data analysis

- As shown in the [sources](collections.html) section, COCONUT data has been extracted from 63 data sources and several have been manually collected from literature sets.

- Currently, The database catalogs a total of 695,133 chemical molecules, with 89,593 being non-stereo (lacking stereoisomers) and 555,897 stereo molecules (exhibiting stereoisomerism). Of the stereo molecules, 415,768 are unique parent structures without stereochemical modifications. The platform organizes these molecules into 63 distinct collections and has been cited 24,272 times in scientific literature. It includes data on 55,252 organisms and 2,653 geographical locations, highlighting a broad diversity in sources. Currently, there are no available reports on the platform. Please refer to the original [paper](https://doi.org/10.1186/s13321-020-00478-9) for more details.

- We extensively use the [ChEMBL](https://www.ebi.ac.uk/chembl/) [structure curation pipeline](https://jcheminf.biomedcentral.com/articles/10.1186/s13321-020-00456-1) developed with [RDKit](https://www.rdkit.org/) to clean the data and curate the database.

![Dashboard](/dashboard-analysis.png)

## Curation steps

- The snapshot of the PostgreSQL database form the COCONUT release 2024 was taken as the primary source,
  * Polyfluorinated compounds (64 in total) were removed
  * Structures that cannot be parsed by the ChEMBL structure curation pipeline (113 in total) have been removed.
  * Duplicates have been merged into one entry and the highly annotated entry has been made the parent entry, and the remainder is now included in the parent entry.

