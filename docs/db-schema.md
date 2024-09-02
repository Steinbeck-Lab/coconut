# Database Schema Overview

The below diagram provides a comprehensive structure of the COCONUT database designed to manage Natural Products, along with associated metadata, properties, and relationships with various entities. Below is an overview of the key components:

- **Molecules (Properties)**: These central tables stores detailed information about each molecule, including its structure, identifiers (like InChI and SMILES), and various properties.
- **Collections**: Groups of molecules are organized within collections, each linked to metadata such as titles, descriptions, and related jobs.
- **Citations** and **Citables**: These tables track references to literature or other sources where the molecules are mentioned or described.
- **Imports** and **Exports**: These tables manage data transfer processes, capturing essential details like file paths, processing statuses, and validation errors.
- **Organisms**: This table links molecules to their biological origins or associations with different organisms.
- **Sample Locations**: This hold the details on location within the organims from where the sample of the compound is taken.
- **Geo Locations**: This table stores the geographical location where this organism is reported to be found.
- **Licenses**: This tracks all the licenses under which the respective collections are made available.

Additionally, the schema includes relationships between molecules, their geographical origins, and occurrences in various organisms or samples. The inclusion of media and licenses tables ensures proper documentation and management of usage rights.

---

This overview captures the main entities and their interrelations within the database structure as depicted in the diagram.

<br/><br/>
<iframe style="border: 1px solid rgba(0, 0, 0, 0.1);" width="800" height="450" src="https://www.figma.com/embed?embed_host=share&url=https%3A%2F%2Fwww.figma.com%2Fboard%2FyVQeNRsqlkXOgI5BIlMlb4%2FCocoDB%3Fnode-id%3D0-1%26t%3DYgVM9dCFvlmc5xhe-1" allowfullscreen></iframe>