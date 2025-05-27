# COCONUT API Documentation

COCONUT API provides a variety of end points to extensively query the database and get the required details. The API documentation can be found [here](https://coconut.naturalproducts.net/api-documentation)
- Current Version: 2.0.0 (Open API Standard 3.1)

## General Notes
- All endpoints return data in application/json format
- Authentication is required for accessing the API
- The API uses standard HTTP response codes

## Base Information
- Base Path: https://coconut.naturalproducts.net/

## Authentication Endpoints

### Login
```
POST /api/auth/login
```

**Request Body (application/json):**
```json
{
  "email": "john@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "access_token": "string",
  "token_type": "string"
}
```

:::info
The token is of **Bearer** type. This has to be supplied in the headers of the requests. In case you are using the [swagger UI of the API](https://coconut.naturalproducts.net/api-documentation), click on the **Authorize** button and provide the token as per the instruction.
:::
### Logout
```
GET /api/auth/logout
```

**Response:**
```json
{
  "logout": "string"
}
```

### Register
```
POST /api/auth/register
```

**Request Body (application/json):**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "username": "JDoe",
  "affiliation": "JD",
  "email": "john@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "string",
  "token": "string"
}
```

## Molecules Endpoints

### Get Molecules
```
GET /api/molecules
```
Get every detail about the molecule at hand.


### Search Molecules
```
POST /api/molecules/search
```
Search Molecules using various attributes. There are two tables invovled in this: *molecules* and *properties*.

#### Searchable fields
::: info molecules
'standard_inchi', 'standard_inchi_key', 'canonical_smiles',  'sugar_free_smiles', 'identifier', 'name', 'cas', 'iupac_name', 'murko_framework', 'structural_comments', 'name_trust_level', 'annotation_level', 'variants_count', 'status', 'active', 'has_variants', 'has_stereo', 'is_tautomer', 'is_parent', 'is_placeholder'
:::
::: info properties
'total_atom_count', 'heavy_atom_count', 'molecular_weight', 'exact_molecular_weight', 'molecular_formula', 'alogp', 'topological_polar_surface_area', 'rotatable_bond_count', 'hydrogen_bond_acceptors', 'hydrogen_bond_donors', 'hydrogen_bond_acceptors_lipinski', 'hydrogen_bond_donors_lipinski', 'lipinski_rule_of_five_violations', 'aromatic_rings_count', 'qed_drug_likeliness', 'formal_charge', 'fractioncsp3', 'number_of_minimal_rings', 'van_der_walls_volume', 'contains_sugar', 'contains_ring_sugars', 'contains_linear_sugars', 'murcko_framework', 'np_likeness', 'chemical_class', 'chemical_sub_class', 'chemical_super_class', 'direct_parent_classification', 'np_classifier_pathway', 'np_classifier_superclass', 'np_classifier_class', 'np_classifier_is_glycoside'
:::

::: warning Note
The fields in the *molecules* table can be accessed directly with their column names. The fields in the *properties* table are to be accessed prefixing them with the table name. Ex: **properties.field-name**.
:::


**Request Body Example (application/json):**
```json
{
  "search": {
    "scopes": [],
    "filters": [
      {
        "field": "standard_inchi",
        "operator": "=",
        "value": ""
      },
      {
        "field": "standard_inchi_key",
        "operator": "=",
        "value": ""
      },
      {
        "field": "canonical_smiles",
        "operator": "=",
        "value": ""
      },
      {
        "field": "sugar_free_smiles",
        "operator": "=",
        "value": ""
      }
    ]
  }
}
```

### Mutate Molecules
```
POST /api/molecules/mutate
```
Update the molecule properties.
::: warning Note
General users cannot perform this. Special access privileges and vetting are done by the Scientific Advisory Board before anyone can be granted permission to perform this operation. For any queries, please contact: info.COCONUT@uni-jena.de 
:::

**Request Body Example (application/json):**
```json
{
  "mutate": [
    {
      "operation": "create",
      "attributes": {
        "standard_inchi": "",
        "standard_inchi_key": "",
        "canonical_smiles": "",
        "sugar_free_smiles": "",
        "identifier": "",
        "name": "",
        "cas": "",
        "iupac_name": "",
        "murko_framework": "",
        "structural_comments": "",
        "name_trust_level": "",
        "annotation_level": "",
        "variants_count": "",
        "status": "",
        "active": "",
        "has_variants": "",
        "has_stereo": "",
        "is_tautomer": "",
        "is_parent": ""
      }
    }
  ]
}
```

### Molecule Actions
```
POST /api/molecules/actions/{action}
```
Launch actions.

## Collections Endpoints

### Get Collections
```
GET /api/collections
```
Get details about the collections hosted on COCONUT.


### Search Collections
```
POST /api/collections/search
```
Search Collections using various attributes.

**Request Body Fields:**
- title
- description
- identifier
- url

### Mutate Collections
```
POST /api/collections/mutate
```
Update the collection details.
::: warning Note
General users cannot perform this. Special access privileges and vetting are done by the Scientific Advisory Board before anyone can be granted permission to perform this operation. For any queries, please contact: info.COCONUT@uni-jena.de 
:::

## Citations Endpoints

### Get Citations
```
GET /api/citations
```
Get details about the citations connected to various resources on COCONUT.


### Search Citations
```
POST /api/citations/search
```
Search Citations using various attributes.

**Search Fields:**
- doi
- title
- authors
- citation_text

### Mutate Citations
```
POST /api/citations/mutate
```
Update citation details.
::: warning Note
General users cannot perform this. Special access privileges and vetting are done by the Scientific Advisory Board before anyone can be granted permission to perform this operation. For any queries, please contact: info.COCONUT@uni-jena.de 
:::

## Organisms Endpoints

### Get Organisms
```
GET /api/organisms
```
Get the detials of organisms where the molecules are reported to be found in.


### Search Organisms
```
POST /api/organisms/search
```
Search Organisms using various attributes.

**Search Fields:**
- name
- iri
- rank
- molecule_count

### Mutate Organisms
```
POST /api/organisms/mutate
```
Update organism details.
::: warning Note
General users cannot perform this. Special access privileges and vetting are done by the Scientific Advisory Board before anyone can be granted permission to perform this operation. For any queries, please contact: info.COCONUT@uni-jena.de 
:::

## Properties Endpoints

### Get Properties
```
GET /api/properties
```
Exclusively search for molecule properties.

### Search Properties
```
POST /api/properties/search
```
Search properties based on various attributes.

**Available Fields:**
- total_atom_count
- heavy_atom_count
- molecular_weight
- exact_molecular_weight
- molecular_formula
- alogp
- topological_polar_surface_area
- rotatable_bond_count
- hydrogen_bond_acceptors
- hydrogen_bond_donors
- hydrogen_bond_acceptors_lipinski
- hydrogen_bond_donors_lipinski
- lipinski_rule_of_five_violations
- aromatic_rings_count
- qed_drug_likeliness
- formal_charge
- fractioncsp3
- number_of_minimal_rings
- van_der_walls_volume

### Mutate Properties
```
POST /api/properties/mutate
```
Update molecule properties.
::: warning Note
General users cannot perform this. Special access privileges and vetting are done by the Scientific Advisory Board before anyone can be granted permission to perform this operation. For any queries, please contact: info.COCONUT@uni-jena.de 
:::

## Reports Endpoints

### Search

Search for reports with various filtering, sorting, and pagination options.

**URL:** `/api/reports/search`  
**Method:** `POST`  
**Content-Type:** `application/json`

#### Basic Example

```json
{
  "search": {
    "filters": [
      {
        "field": "title",
        "operator": "like",
        "value": "%molecule%"
      }
    ],
    "sorts": [
      {
        "field": "title",
        "direction": "desc"
      }
    ],
    "page": 1,
    "limit": 10
  }
}
```


### Create

Create a new report.

**URL:** `/api/reports`  
**Method:** `POST`  
**Content-Type:** `application/json`

#### Request

```json
{
  "mutate": [
    {
      "operation": "create",
      "attributes": {
        "title": "Isolation of Berberine from Berberis vulgaris",
        "evidence": "The compound was isolated from the root bark extract using column chromatography and structure confirmed by NMR and MS analysis.",
        "comment": "This alkaloid has shown significant antimicrobial activity against gram-positive bacteria.",
        "suggested_changes": {
          "new_molecule_data": {
            "canonical_smiles": "COc1ccc2cc3[n+](cc2c1OC)CCc1cc2c(cc1-3)OCO2",
            "reference_id": "ID if it has one",
            "name": "Berberine",
            "link": "https://pubchem.ncbi.nlm.nih.gov/compound/2353",
            "mol_filename": "berberine.mol",
            "structural_comments": "Quaternary isoquinoline alkaloid with a tetracyclic skeleton",
            "references": [
              {
                "doi": "10.1021/np50123a002",
                "organisms": [
                  {
                    "name": "Berberis vulgaris",
                    "parts": ["root", "bark", "rhizome"],
                    "locations": [
                      { "name": "Eastern Europe", "ecosystems": ["temperate forest", "woodland"] },
                      { "name": "Western Asia", "ecosystems": ["mountain slopes", "rocky terrain"] }
                    ]
                  },
                  {
                    "name": "Hydrastis canadensis",
                    "parts": ["rhizome", "roots"],
                    "locations": [
                      { "name": "Eastern North America", "ecosystems": ["deciduous forest", "shaded woodland"] }
                    ]
                  }
                ]
              },
              {
                "doi": "10.1016/j.jep.2019.112124",
                "organisms": [
                  {
                    "name": "Coptis chinensis",
                    "parts": ["rhizome", "roots"],
                    "locations": [
                      { "name": "Southern China", "ecosystems": ["mountain forests", "hillsides"] },
                      { "name": "Eastern Asia", "ecosystems": ["humid forest", "river valleys"] }
                    ]
                  },
                  {
                    "name": "Phellodendron amurense",
                    "parts": ["bark", "stem"],
                    "locations": [{ "name": "Northeast Asia", "ecosystems": ["deciduous forest", "mixed forest"] }]
                  }
                ]
              }
            ]
          }
        }
      },
      "relations": []
    }
  ]
}
```
::: info Note: *Only one molecule per submission.*
Required fileds: 
  - **Title** 
  - **canonical_smiles** (inside new_molecule_data)
  - **doi** (inside references array)
:::


### Update
::: warning Note
General users cannot perform this. Special access privileges and vetting are done by the Scientific Advisory Board before anyone can be granted permission to perform this operation. For any queries, please contact: info.COCONUT@uni-jena.de 
:::
Update an existing report.

**URL:** `/api/reports/{id}`  
**Method:** `PATCH`  
**Content-Type:** `application/json`

#### Request

```json
{
    "mutate": [
        {
            "operation": "update",
            "key": 43,
            "attributes": {"title": "new name"}
        }
    ]
}
```


### Delete

::: warning Note
General users cannot perform this. Special access privileges and vetting are done by the Scientific Advisory Board before anyone can be granted permission to perform this operation. For any queries, please contact: info.COCONUT@uni-jena.de 
:::

Permanently delete a report.

**URL:** `/api/reports`  
**Method:** `DELETE`

#### Request

```json
{
  "resources": [
    42,
    43
  ]
}
```
