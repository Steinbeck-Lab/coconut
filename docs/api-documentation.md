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
        "field": "created_at",
        "direction": "desc"
      }
    ],
    "page": 1,
    "limit": 10
  }
}
```

#### Response

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "title": "New molecule discovery",
      "evidence": "Evidence content here",
      "doi": "10.1234/5678",
      "status": "submitted",
      "comment": "Initial submission",
      "user_id": 42,
      "created_at": "2025-04-20T10:30:00",
      "updated_at": "2025-04-20T10:30:00",
      "gates": {
        "authorized_to_view": true,
        "authorized_to_update": true,
        "authorized_to_delete": true,
        "authorized_to_restore": false,
        "authorized_to_force_delete": false
      }
    }
    // Additional reports...
  ],
  "from": 1,
  "last_page": 3,
  "per_page": 10,
  "to": 10,
  "total": 25,
  "meta": {
    "gates": {
      "authorized_to_create": true
    }
  }
}
```

#### Available Filter Operators

- `=` (equal)
- `!=` (not equal)
- `>` (greater than)
- `>=` (greater than or equal)
- `<` (less than)
- `<=` (less than or equal)
- `like` (SQL LIKE pattern)
- `not like` (SQL NOT LIKE pattern)
- `in` (in array)
- `not in` (not in array)

#### Supported Filter Types

- `and` (default) - All conditions must be met
- `or` - Any condition can be met

#### Searching JSON Fields

For the `suggested_changes` JSON field, use dot notation to access nested properties:

```json
{
  "search": {
    "filters": [
      {
        "field": "suggested_changes.new_molecule_data.name",
        "operator": "=",
        "value": "Nareline"
      }
    ]
  }
}
```

#### Full Text Search

If enabled, you can use full text search:

```json
{
  "search": {
    "text": {
      "value": "molecule discovery"
    }
  }
}
```

### Details

Get a single report by its ID.

**URL:** `/api/reports/{id}`  
**Method:** `GET`

#### Response

```json
{
  "id": 1,
  "title": "New molecule discovery",
  "evidence": "Evidence content here",
  "doi": "10.1234/5678",
  "status": "submitted",
  "comment": "Initial submission",
  "user_id": 42,
  "created_at": "2025-04-20T10:30:00",
  "updated_at": "2025-04-20T10:30:00",
  "suggested_changes": {
    "new_molecule_data": {
      "canonical_smiles": "CC=C1C2CC3C4=NC5=CC=CC=C5C4(C2C(=O)OC)C2C(O)ON3C12",
      "name": "Nareline"
    }
  },
  "report_type": "molecule",
  "report_category": "new_molecule",
  "query": null,
  "assigned_to": null,
  "gates": {
    "authorized_to_view": true,
    "authorized_to_update": true,
    "authorized_to_delete": true,
    "authorized_to_restore": false,
    "authorized_to_force_delete": false
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
  "mutate": {
    "operation": "create",
    "attributes": {
      "title": "New molecule discovery",
      "evidence": "Evidence content here",
      "doi": "10.1234/5678",
      "comment": "Initial submission",
      "user_id": 42,
      "suggested_changes": {
        "new_molecule_data": {
          "canonical_smiles": "CC=C1C2CC3C4=NC5=CC=CC=C5C4(C2C(=O)OC)C2C(O)ON3C12",
          "name": "Nareline"
        }
      }
    }
  }
}
```

Note: The fields `report_type`, `report_category`, and `status` will be automatically set to their default values ("molecule", "new_molecule", and "submitted" respectively) and do not need to be included in the request.

#### Response

```json
{
  "id": 1,
  "title": "New molecule discovery",
  "evidence": "Evidence content here",
  "doi": "10.1234/5678",
  "status": "submitted",
  "comment": "Initial submission",
  "user_id": 42,
  "created_at": "2025-04-28T16:45:00",
  "updated_at": "2025-04-28T16:45:00",
  "suggested_changes": {
    "new_molecule_data": {
      "canonical_smiles": "CC=C1C2CC3C4=NC5=CC=CC=C5C4(C2C(=O)OC)C2C(O)ON3C12",
      "name": "Nareline"
    }
  },
  "report_type": "molecule",
  "report_category": "new_molecule",
  "query": null,
  "assigned_to": null
}
```

### Update
:::info
Updates are only allowed for authorised users.
:::
Update an existing report.

**URL:** `/api/reports/{id}`  
**Method:** `PATCH`  
**Content-Type:** `application/json`

#### Request

```json
{
  "mutate": {
    "operation": "update",
    "attributes": {
      "title": "Updated molecule discovery",
      "evidence": "Updated evidence content",
      "status": "in_review"
    }
  }
}
```

#### Response

```json
{
  "id": 1,
  "title": "Updated molecule discovery",
  "evidence": "Updated evidence content",
  "doi": "10.1234/5678",
  "status": "in_review",
  "comment": "Initial submission",
  "user_id": 42,
  "created_at": "2025-04-20T10:30:00",
  "updated_at": "2025-04-28T16:50:00",
  "suggested_changes": {
    "new_molecule_data": {
      "canonical_smiles": "CC=C1C2CC3C4=NC5=CC=CC=C5C4(C2C(=O)OC)C2C(O)ON3C12",
      "name": "Nareline"
    }
  },
  "report_type": "molecule",
  "report_category": "new_molecule",
  "query": null,
  "assigned_to": null
}
```

### Delete
::: info
Deletes are only allowed for authorised users.
:::
### Force Delete

Permanently delete a report.

**URL:** `/api/reports/{id}/force`  
**Method:** `DELETE`


### Advanced Usage

#### JSON Field Searching

The `suggested_changes` field is a JSON type field that can contain complex nested structures. You can search through this field using dot notation:

##### Basic Property

```json
{
  "search": {
    "filters": [
      {
        "field": "suggested_changes.new_molecule_data.name",
        "operator": "=",
        "value": "Nareline"
      }
    ]
  }
}
```

##### Searching Arrays in JSON

```json
{
  "search": {
    "filters": [
      {
        "field": "suggested_changes.new_molecule_data.references.0.doi",
        "operator": "=",
        "value": "10.1145/2783446.2783605"
      }
    ]
  }
}
```

##### Searching Deeply Nested Structures

```json
{
  "search": {
    "filters": [
      {
        "field": "suggested_changes.new_molecule_data.references.0.organisms.0.name",
        "operator": "=",
        "value": "o1"
      }
    ]
  }
}
```