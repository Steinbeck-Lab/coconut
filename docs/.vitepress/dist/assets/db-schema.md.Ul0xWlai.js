import{_ as e,c as s,o as t,a2 as a,j as i}from"./chunks/framework.CpPNxFD6.js";const _=JSON.parse('{"title":"Database Schema Overview","description":"","frontmatter":{},"headers":[],"relativePath":"db-schema.md","filePath":"db-schema.md"}'),o={name:"db-schema.md"},r=a('<h1 id="database-schema-overview" tabindex="-1">Database Schema Overview <a class="header-anchor" href="#database-schema-overview" aria-label="Permalink to &quot;Database Schema Overview&quot;">​</a></h1><p>The below diagram provides a comprehensive structure of the COCONUT database designed to manage Natural Products, along with associated metadata, properties, and relationships with various entities. Below is an overview of the key components:</p><ul><li><strong>Molecules (Properties)</strong>: These central tables stores detailed information about each molecule, including its structure, identifiers (like InChI and SMILES), and various properties.</li><li><strong>Collections</strong>: Groups of molecules are organized within collections, each linked to metadata such as titles, descriptions, and related jobs.</li><li><strong>Citations</strong> and <strong>Citables</strong>: These tables track references to literature or other sources where the molecules are mentioned or described.</li><li><strong>Imports</strong> and <strong>Exports</strong>: These tables manage data transfer processes, capturing essential details like file paths, processing statuses, and validation errors.</li><li><strong>Organisms</strong>: This table links molecules to their biological origins or associations with different organisms.</li><li><strong>Sample Locations</strong>: This hold the details on location within the organims from where the sample of the compound is taken.</li><li><strong>Geo Locations</strong>: This table stores the geographical location where this organism is reported to be found.</li><li><strong>Licenses</strong>: This tracks all the licenses under which the respective collections are made available.</li></ul><p>Additionally, the schema includes relationships between molecules, their geographical origins, and occurrences in various organisms or samples. The inclusion of media and licenses tables ensures proper documentation and management of usage rights.</p><hr><p>This overview captures the main entities and their interrelations within the database structure as depicted in the diagram.</p><p><br><br></p>',7),n=i("iframe",{style:{border:"1px solid rgba(0, 0, 0, 0.1)"},width:"800",height:"450",src:"https://www.figma.com/embed?embed_host=share&url=https%3A%2F%2Fwww.figma.com%2Fboard%2FyVQeNRsqlkXOgI5BIlMlb4%2FCocoDB%3Fnode-id%3D0-1%26t%3DYgVM9dCFvlmc5xhe-1",allowfullscreen:""},null,-1),l=[r,n];function c(d,h,m,g,p,u){return t(),s("div",null,l)}const f=e(o,[["render",c]]);export{_ as __pageData,f as default};