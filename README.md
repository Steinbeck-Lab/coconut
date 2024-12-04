
<p align="center">
  <a href="https://COCONUT.naturalproducts.net/" target="_blank">
    <img src="/public/img/logo.png" width="400" alt="COCONUT Logo">
  </a>
    <h3 align="center">COlleCtion of Open Natural prodUcTs</h3>
</p>

<div align="center">

[![License](https://img.shields.io/badge/License-MIT%202.0-blue.svg)](https://opensource.org/licenses/MIT)
[![Maintenance](https://img.shields.io/badge/Maintained%3F-yes-green.svg)](https://GitHub.com/Steinbeck-Lab/coconut/graphs/commit-activity)
[![GitHub issues](https://img.shields.io/github/issues/Steinbeck-Lab/coconut.svg)](https://GitHub.com/Steinbeck-Lab/coconut/issues/)
[![GitHub contributors](https://img.shields.io/github/contributors/Steinbeck-Lab/coconut.svg)](https://GitHub.com/Steinbeck-Lab/coconut/graphs/contributors/)
[![RDKit badge](https://img.shields.io/badge/Powered%20by-RDKit-3838ff.svg?logo=data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQBAMAAADt3eJSAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAFVBMVEXc3NwUFP8UPP9kZP+MjP+0tP////9ZXZotAAAAAXRSTlMAQObYZgAAAAFiS0dEBmFmuH0AAAAHdElNRQfmAwsPGi+MyC9RAAAAQElEQVQI12NgQABGQUEBMENISUkRLKBsbGwEEhIyBgJFsICLC0iIUdnExcUZwnANQWfApKCK4doRBsKtQFgKAQC5Ww1JEHSEkAAAACV0RVh0ZGF0ZTpjcmVhdGUAMjAyMi0wMy0xMVQxNToyNjo0NyswMDowMDzr2J4AAAAldEVYdGRhdGU6bW9kaWZ5ADIwMjItMDMtMTFUMTU6MjY6NDcrMDA6MDBNtmAiAAAAAElFTkSuQmCC)](https://www.rdkit.org/)
![Workflow](https://GitHub.com/Steinbeck-Lab/coconut/actions/workflows/dev-build.yml/badge.svg)
[![Powered by Laravel](https://img.shields.io/badge/Powered%20by-Laravel-red.svg?style=flat&logo=Laravel)](https://laravel.com)
[![DOI](https://zenodo.org/badge/DOI/10.5281/zenodo.13897048.svg)](https://doi.org/10.5281/zenodo.13382750)

</div>

## ![About](https://www.google.com/s2/favicons?domain=coconut.naturalproducts.net) About

A comprehensive platform facilitating natural product research by providing data, tools, and services for deposition, curation, and reuse. It aims to provide researchers, scientists, and enthusiasts with comprehensive and easily accessible data on a wide variety of natural compounds. The database includes detailed information on the chemical structures, literature references and sources of these compounds, facilitating research and discovery in natural products.

[https://coconut.naturalproducts.net/](https://coconut.naturalproducts.net/)

## ![Features](https://www.google.com/s2/favicons?domain=github.com) Features

- **Standardised data aggregation**: COCONUT 2.0 aggregates data from [more than 63 sources](https://coconut.naturalproducts.net/collections?q=) using ChEMBL curation pipeline with RDKit post-processing, standardizing molecular structures and metadata while preserving stereochemistry from sources.
    - *Descriptor calculations*: COCONUT data curation and descriptors calculation are performed using [Cheminformatics microservice](https://docs.api.naturalproducts.net/).
- **Comprehensive download options**: The database offers downloads in CSV, SDF, and SQL dump formats, with specialized CSV files for mass spectrometry, molecular descriptors, and substructure analyses accessible [here](https://coconut.naturalproducts.net/download).
- **Search and Filtering**: Users can search by chemical structure (exact, substructure, similarity), text-based queries for names/SMILES, and filter by organism, chemical class, or literature references.
- **Online Submission and Curation**: Features community-driven data submission and curation through a web interface, allowing users to submit new structures, report issues, and request changes with full audit trail tracking.
- **API Access**: Provides a REST API compliant with OpenAPI specifications for programmatic access to chemical structures, metadata, and audit information with real-time data updates - [API documentation](https://coconut.naturalproducts.net/api-documentation).

## ![License](https://www.google.com/s2/favicons?domain=opensource.org) License

COCONUT infrastructure code is licensed under the MIT license - see the [LICENSE](https://GitHub.com/Steinbeck-Lab/coconut/blob/documentation/LICENSE). Every source on COCONUT comes with its own specific license. It is essential to review the license details for each dataset before using it.

## ![Citations](https://www.google.com/s2/favicons?domain=doi.org)Citations

### COCONUT 2.0
-  Venkata Chandrasekhar, Kohulan Rajan, Sri Ram Sagar Kanakam, Nisha Sharma, Viktor Weißenborn, Jonas Schaub, Christoph Steinbeck, COCONUT 2.0: a comprehensive overhaul and curation of the collection of open natural products database, Nucleic Acids Research, 2024;, gkae1063, https://doi.org/10.1093/nar/gkae1063

### COCONUT (Legacy)
-  Sorokina, M., Merseburger, P., Rajan, K. et al. (2021). COCONUT online: COlleCtion of Open Natural prodUcTs database. *Journal of Cheminformatics*, 13, 2. 
https://doi.org/10.1186/s13321-020-00478-9

## ![Maintained](https://www.google.com/s2/favicons?domain=uni-jena.de) Maintained by

The COCONUT database and its infrastructure are developed and maintained by the [Steinbeck group](https://cheminf.uni-jena.de) at the [Friedrich Schiller University](https://www.uni-jena.de/en/) Jena, Germany.

The code for this web application is released under the [MIT license](https://opensource.org/licenses/MIT). Copyright © CC-BY-SA 2024

<p align="center">
  <a href="https://cheminf.uni-jena.de/" target="_blank">
    <img src="https://github.com/Kohulan/DECIMER-Image-to-SMILES/blob/master/assets/CheminfGit.png" width="400" alt="cheminf Logo">
  </a>
</p>

## ![Acknowledgments](https://www.google.com/s2/favicons?domain=dfg.de) Acknowledgments

Funded by the [Deutsche Forschungsgemeinschaft (DFG, German Research Foundation)](https://www.dfg.de/) under the [ChemBioSys](https://www.chembiosys.de/en/) (Project INF) - Project number: **239748522 - SFB 1127**.

<div style="display: flex; justify-content: space-between;">
  <a href="https://www.dfg.de/" target="_blank">
    <img src="https://github.com/Steinbeck-Lab/cheminformatics-microservice/blob/main/docs/public/dfg_logo_schriftzug_blau_foerderung_en.gif" width="40%" alt="DFG Logo">
  </a>
  <a href="https://www.chembiosys.de/en/welcome.html" target="_blank">
    <img src="https://github.com/Steinbeck-Lab/cheminformatics-microservice/assets/30716951/45c8e153-8322-4563-a51d-cbdbe4e08627" width="40%" alt="Chembiosys Logo">
  </a>
</div>
