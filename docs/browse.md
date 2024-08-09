# COCONUT online - Browse

![Cheming and Computational Metabolomics logo](/search-page.png)


# Molecule Search Functionality

Our advanced molecule search engine provides a robust set of features for users to search for and identify chemical compounds through various methods. Below is a detailed overview of the search functionalities available.

### Simple Search
- **Molecule Name:** Users can search for molecules by entering any widely recognized name, such as IUPAC, trivial, or synonym names. The search engine will identify compounds that contain the inputted name in their title.

### InChI-IUPAC (International Chemical Identifier)
- **InChI Search:** The InChI is a non-proprietary identifier for chemical substances that is widely used in electronic data sources. It expresses chemical structures in terms of atomic connectivity, tautomeric state, isotopes, stereochemistry, and electronic charge in order to produce a string of machine-readable characters unique to the particular molecule. Therefore, when InChl name is entered, the software output will be a unique inputted compound with all required characteristics.

### InChIKey Search
- **InChIKey:** The InChIKey is a 25-character hashed version of the full InChI, designed to allow for easy web searches of chemical compounds. InChIKeys consist of 14 characters resulting from a hash of the connectivity information from the full InChI string, followed by a hyphen, followed by 8 characters resulting from a hash of the remaining layers of the InChI, followed by a single character indicating the version of InChI used, followed by single checksum character. Therefore, when the user enters the InChl key, the software output will be a single compound that is recognized by a particular InChl key.

### Molecular Formula Search
- **Molecular Formula:** The molecular formula is a type of chemical formula that shows the kinds of atoms and the number of each kind in a single molecule of a particular compound. The molecular formula doesn’t show any information about the molecule structure. The structures and characteristics of compounds with the same molecular formula may vary significantly. Hence, by entering a molecular formula into the search bar, the software output will be a group of compounds with specified atoms and their numbers within a single molecule.

### Coconut ID
- **Unique Identifier:** Each natural product in our database is assigned a unique Coconut ID, which can be used for quick and precise searches exclusively on COCONUT.

### Structure Search
- **Visual Structure Search:** Users can search for compounds by providing a visual depiction of their structure. The vast number of functional groups often causes issues to name the compound appropriately. Therefore, usage of structure search is a great way to discover all characteristics of a compound just by providing its visual depiction. The search engine recognizes InChI and canonical SMILES formats.

### Exact Match Search
- **InChI Structural Formulas:** The search engine recognizes different types of InChI structural formulas, including expanded, condensed, and skeletal formulas.
    >- **Expanded Structural Formula**: Shows all of the bonds connecting all of the atoms within the compound.
    >- **Condensed Structural Formula**: Shows the symbols of atoms in order as they appear in the molecule's structure while most of the bond dashes are excluded. The vertical bonds are always excluded, while horizontal bonds may be included to specify polyatomic groups. If there is a repetition of a polyatomic group in the chain, parentheses are used to enclose the polyatomic group. The subscript number on the right side of the parentheses represents the number of repetitions of the particular group. The proper condensed structural formula should be written on a single horizontal line without branching in any direction.
    >- **Skeletal Formula**: Represents the carbon skeleton and function groups attached to it. In the skeletal formula, carbon atoms and hydrogen atoms attached to them are not shown. The bonds between carbon lines are presented as well as bonds to functional groups.

- **Canonical SMILES Structural Formulas:** The canonical SMILES structure is a unique string that can be used as a universal identifier for a specific chemical structure including stereochemistry of a compound. Therefore, canonical SMILES provides a unique form for any particular molecule. The user can choose a convenient option and then proceed with the structure drawing.
    > The 3D structure of the molecule is commonly used for the description of simple molecules. In this type of structure drawing, all types of covalent bonds are presented with respect to their spatial orientation. The usage of models is the best way to pursue a 3D structure drawing. The valence shell repulsion pair theory proposes five main models of simple molecules: linear, trigonal planar, tetrahedral, trigonal bipyramidal, and octahedral.


### Substructure Search
- **Partial Structure Search:** Users can search for compounds by entering a known substructure using InChI or SMILES formats. The engine supports three algorithms:
  >- **Default (Ullmann Algorithm):** Utilizes a backtracking procedure with a refinement step to reduce the search space. This refinement is the most important step of the algorithm. It evaluates the surrounding of every node in the database molecules and compares them with the entered substructure.
  >- **Depth-First (DF) Pattern:** The DF algorithm executes the search operation of the entered molecule in a depth-first manner (bond by bond). Therefore, this algorithm utilizes backtracking search iterating over the bonds of entered molecules.
  >- **Vento-Foggia Algorithm:** The Vento-Foggia algorithm iteratively extends a partial solution using a set of feasibility criteria to decide whether to extend or backtrack. In the Ullmann algorithm, the node-atom mapping is fixed in every step. In contrast, the Vento-Foggia algorithm iteratively adds node-atom pairs to a current solution. In that way, this algorithm directly discovers the topology of the substructure and seeks for all natural products that contain the entered substructure.

### Similarity Search
- **Tanimoto Threshold:** The search engine finds compounds with a similarity score (Sab) greater than or equal to the specified Tanimoto coefficient. This allows users to find compounds closely related to the query structure.

### Advanced Search
- **Molecular Descriptors and Structural Properties:** The advanced search feature enables users to search by specific molecular descriptors, which quantify physical and chemical characteristics. Users can also choose to search within specific data sources compiled in our database.

These search functionalities are designed to cater to various needs, from simple name-based searches to complex structural and substructural queries, ensuring comprehensive and accurate retrieval of chemical information.
