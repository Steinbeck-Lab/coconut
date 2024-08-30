# COCONUT online - Browse

![Cheming and Computational Metabolomics logo](/browse-page.png)

## Compound Cards

![Caffeine compound card](/caffeine-compound-card.png)

For user convenience, a quick overview of the compounds is displayed using the Compound-Cards as shown above. Each card holds the following information:

- **2D Structure:** The compound's 2D structure 
- **Annotation score:** Indicates the quality of the compound's annotation
- **Coconut ID:** The Coconut internal ID uniquely identifies each compound in the database
- **Compound Name:** The most commonly used name of the compound
- **Organisms count:** Number of organisms in which the compound is reported to be found
- **Collections count:** Number of collections which reported this compound
- **Geo-locations count:** Number of reported geographical locations where the organisms containing this compound can be found
- **Citations count:** Number of citations available

## Molecule Search Functionality

Our advanced molecule search engine provides a robust set of features for users to search for and identify chemical compounds through various methods. Below is a detailed overview of the search functionalities available.

### Simple Search
- **Name Search:** Users can search for molecules by entering any widely recognised name, such as IUPAC name, trivial name, or synonym names. The search engine will identify compounds that contain the inputted name in their title.

- **InChI Search:** The InChI is a non-proprietary identifier for chemical substances that are widely used in electronic data sources. It expresses chemical structures in terms of atomic connectivity, tautomeric state, isotopes, stereochemistry, and electronic charge to produce a string of machine-readable characters unique to the particular molecule.

- **InChI-Key Search:** The InChI-Key is a 25-character hashed version of the full InChI, designed to allow for easy web searches of chemical compounds. InChIKeys consist of 14 characters resulting from a hash of the connectivity information, followed by a hyphen, 8 characters from a hash of the remaining layers, a version indicator, and a checksum character.

- **Molecular Formula Search:** The molecular formula shows the kinds of atoms and the number of each kind in a single molecule of a particular compound. By entering a molecular formula, the software will output a group of compounds with the specified atoms and their numbers within a single molecule.

- **COCONUT ID Search:** Each natural product in our database is assigned a unique COCONUT ID, which can be used for quick and precise searches exclusively on COCONUT.

::: tip Search Tip
When using the InChI, InChI-Key, or Molecular Formula search, be sure to enter the exact string for the most accurate results.
:::

<style>
img {
  max-width: 100%;
  height: auto;
  display: block;
  margin: 20px auto;
}
</style>