<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Features;
use Lomkit\Rest\Documentation\Schemas\Example;
use Lomkit\Rest\Documentation\Schemas\Header;
use Lomkit\Rest\Documentation\Schemas\MediaType;
use Lomkit\Rest\Documentation\Schemas\OpenAPI;
use Lomkit\Rest\Documentation\Schemas\Operation;
use Lomkit\Rest\Documentation\Schemas\Parameter;
use Lomkit\Rest\Documentation\Schemas\Path;
use Lomkit\Rest\Documentation\Schemas\RequestBody;
use Lomkit\Rest\Documentation\Schemas\Response;
use Lomkit\Rest\Documentation\Schemas\Responses;
use Lomkit\Rest\Documentation\Schemas\SchemaConcrete;
use Lomkit\Rest\Facades\Rest;

class RestDocumentationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Rest::withDocumentationCallback(function (OpenAPI $openAPI) {
            $openAPI
                ->withPaths(
                    [
                        '/api/auth/login' => (new Path)
                            ->withPost(
                                (new Operation)
                                    ->withSummary('Login endpoint')
                                    ->withTags(['Authentication'])
                                    ->withRequestBody(
                                        (new RequestBody)
                                            ->withContent(
                                                [
                                                    'application/json' => (new MediaType)
                                                        ->withExample(
                                                            (new Example)
                                                                ->withValue([
                                                                    'email' => 'john@example.com',
                                                                    'password' => 'password',
                                                                ])
                                                                ->generate()
                                                        )
                                                        ->generate(),
                                                ]
                                            )
                                    )
                                    ->withResponses(
                                        (new Responses)
                                            ->withDefault(
                                                (new Response)
                                                    ->withDescription('Login successful')
                                                    ->withContent([
                                                        'application/json' => [
                                                            'schema' => [
                                                                'type' => 'object',
                                                                'properties' => [
                                                                    'access_token' => [
                                                                        'type' => 'string',
                                                                    ],
                                                                    'token_type' => [
                                                                        'type' => 'string',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ])
                                            )
                                    )
                            ),
                        '/api/auth/logout' => (new Path)
                            ->withGet(
                                (new Operation)
                                    ->withSummary('Logout endpoint')
                                    ->withTags(['Authentication'])
                                    ->withResponses(
                                        (new Responses)
                                            ->withDefault((new Response)
                                                ->withDescription('Logout successful')
                                                ->withContent([
                                                    'application/json' => [
                                                        'schema' => [
                                                            'type' => 'object',
                                                            'properties' => [
                                                                'logout' => [
                                                                    'type' => 'string',
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ])
                                                ->generate())
                                    )
                            ),
                        '/api/auth/register' => (new Path)
                            ->withPost(
                                (new Operation)
                                    ->withSummary('Register endpoint')
                                    ->withTags(['Authentication'])
                                    ->withRequestBody(
                                        (new RequestBody)
                                            ->withContent(
                                                [
                                                    'application/json' => (new MediaType)
                                                        ->withExample(
                                                            (new Example)
                                                                ->withValue([
                                                                    'first_name' => 'John',
                                                                    'last_name' => 'Doe',
                                                                    'username' => 'JDoe',
                                                                    'affiliation' => 'JD',
                                                                    'email' => 'john@example.com',
                                                                    'password' => 'password',
                                                                    'password_confirmation' => 'password',
                                                                ])
                                                                ->generate()
                                                        )
                                                        ->generate(),
                                                ]
                                            )
                                    )
                                    ->withResponses(
                                        (new Responses)->withDefault((new Response)
                                            ->withDescription('Successfully created user')
                                            ->withContent(
                                                [
                                                    'application/json' => [
                                                        'schema' => [
                                                            'type' => 'object',
                                                            'properties' => [
                                                                'success' => [
                                                                    'type' => 'boolean',
                                                                ],
                                                                'message' => [
                                                                    'type' => 'string',
                                                                ],
                                                                'token' => [
                                                                    'type' => 'string',
                                                                ],
                                                                // Add other properties as needed
                                                            ],
                                                        ],
                                                    ],
                                                ]
                                            ))
                                    )
                            ),
                        '/api/search' => (new Path)
                            ->withPost(
                                (new Operation)
                                    ->withSummary('Advanced Molecule Search')
                                    ->withDescription(
                                        "# Advanced Molecule Search\n\n".
                                            "Advanced search supports searching for molecules based on complex criteria.\n\n".

                                            "## 1. Tag-Based Search (type=tags)\n".
                                            "Search molecules based on the following tag types:\n\n".
                                            "* **dataSource**: Search by collection title\n".
                                            "  - Example: `type=tags, tagType=dataSource, query=collection_name`\n\n".
                                            "* **organisms**: Search by organism names (comma-separated)\n".
                                            "  - Example: `type=tags, tagType=organisms, query=organism_name1,organism_name2`\n\n".
                                            "* **citations**: Search by DOI or title (comma-separated)\n".
                                            "  - Example: `type=tags, tagType=citations, query=doi1,doi2,title1,title2`\n\n".

                                            "## 2. Filter-Based Search (type=filters)\n\n".
                                            "### Molecular Properties\n".
                                            "* `tac`: Total Atom Count (1-1071)\n".
                                            "* `hac`: Heavy Atom Count (0-551)\n".
                                            "* `mw`: Molecular Weight (5.01-7860.71)\n".
                                            "* `emw`: Exact Molecular Weight (1.00728-7855.66038)\n".
                                            "* `mrc`: Number of Minimal Rings (0-51)\n".
                                            "* `vdwv`: Van der Walls Volume (10.14-5177.31)\n".
                                            "* `fc`: Formal Charge (-8 to 7)\n\n".

                                            "### Chemical Properties\n".
                                            "* `alogp`: ALogP (-82.67 to 67.03)\n".
                                            "* `topopsa`: Topological Polar Surface Area (0.00-3453.72)\n".
                                            "* `fcsp3`: Fraction CSP3 (0.00-1.00)\n".
                                            "* `np`: NP Likeness (-3.53 to 4.12)\n".
                                            "* `qed`: QED Drug Likeliness (0.00-0.95)\n\n".

                                            "### Structural Features\n".
                                            "* `rbc`: Rotatable Bond Count (0-224)\n".
                                            "* `arc`: Aromatic Rings Count (0-31)\n".
                                            "* `hba`: Hydrogen Bond Acceptors (0-191)\n".
                                            "* `hbd`: Hydrogen Bond Donors (0-116)\n\n".

                                            "### Lipinski Parameters\n".
                                            "* `lhba`: Lipinski H-Bond Acceptors (0-191)\n".
                                            "* `lhbd`: Lipinski H-Bond Donors (0-116)\n".
                                            "* `lro5v`: Lipinski Rule of 5 Violations (0-4)\n\n".

                                            "### Sugar-Related Properties\n".
                                            "* `cs`: Contains Sugar (true/false)\n".
                                            "* `crs`: Contains Ring Sugars (true/false)\n".
                                            "* `cls`: Contains Linear Sugars (true/false)\n\n".

                                            "### Classyfire Classifications\n".
                                            "* `class`: Chemical Class\n".
                                            "  - Example: 1,2-diaryl-2-propen-1-ols\n".
                                            "* `subclass`: Chemical Sub Class\n".
                                            "  - Example: 1,1'-azonaphthalenes\n".
                                            "* `superclass`: Chemical Super Class\n".
                                            "  - Example: Acetylides\n".
                                            "* `parent`: Direct Parent Classification\n".
                                            "  - Example: 1,1'-azonaphthalenes\n\n".

                                            "### Natural Product Classifications\n".
                                            "* `np_pathway`: NP Classifier Pathway\n".
                                            "  - Example: Amino acids and Peptides\n".
                                            "* `np_superclass`: NP Classifier Superclass\n".
                                            "  - Example: Aminosugars and aminoglycosides\n".
                                            "* `np_class`: NP Classifier Class\n".
                                            "  - Example: 2-arylbenzofurans\n".
                                            "* `np_glycoside`: NP Classifier Is Glycoside (true/false)\n\n".

                                            "### Filter Query Examples\n".
                                            "* Range query:\n".
                                            "  ```\n".
                                            "  type=filters&q=tac:4..6\n".
                                            "  ```\n".
                                            "* Boolean query:\n".
                                            "  ```\n".
                                            "  type=filters&q=cs:true\n".
                                            "  ```\n".
                                            "* Classification query:\n".
                                            "  ```\n".
                                            "  type=filters&q=class:1,2-diaryl-2-propen-1-ols\n".
                                            "  ```\n".
                                            "* Multiple conditions:\n".
                                            "  ```\n".
                                            "  type=filters&q=tac:4..6 mw:100..200\n".
                                            "  ```\n".
                                            "* Complex query with OR:\n".
                                            "  ```\n".
                                            "  type=filters&q=tac:4..6 cs:true OR mw:100..200\n".
                                            "  ```\n\n".

                                            "## 3. Basic Search (type not specified)\n".
                                            "Search molecules by name, SMILES, InChI, or InChI Key.\n\n".
                                            "* Simply provide the search term in the query parameter\n".
                                            "* Example: `query=caffeine`\n"
                                    )
                                    ->withTags(['Advanced Search'])
                                    ->withRequestBody(
                                        (new RequestBody)
                                            ->withContent([
                                                'application/json' => (new MediaType)
                                                    ->withExample(
                                                        (new Example)
                                                            ->withValue([
                                                                'type' => 'filters',
                                                                'tagType' => '',
                                                                'query' => 'tac:4..6',
                                                                'limit' => 20,
                                                                'sort' => 'desc',
                                                                'page' => 1,
                                                                'offset' => 0,
                                                            ])
                                                            ->generate()
                                                    )
                                                    ->generate(),
                                            ])
                                    )
                                    ->withResponses(
                                        (new Responses)
                                            ->withDefault(
                                                (new Response)
                                                    ->withDescription('Successful operation')
                                                    ->withContent([
                                                        'application/json' => (new MediaType)
                                                            ->withExample(
                                                                (new Example)
                                                                    ->withValue([
                                                                        'data' => [
                                                                            [
                                                                                'identifier' => 'CNP0228556.0',
                                                                                'canonical_smiles' => 'CN1C(=O)C2=C(N=CN2C)N(C)C1=O',
                                                                                'annotation_level' => 5,
                                                                                'name' => 'caffeine',
                                                                                'iupac_name' => '1,3,7-trimethylpurine-2,6-dione',
                                                                                'organism_count' => 135,
                                                                                'citation_count' => 12,
                                                                                'geo_count' => 3,
                                                                                'collection_count' => 31,
                                                                            ],
                                                                        ],
                                                                        'current_page' => 1,
                                                                        'total' => 100,
                                                                        'per_page' => 24,
                                                                    ])
                                                                    ->generate()
                                                            )
                                                            ->generate(),
                                                    ])
                                            )
                                            ->withOthers([
                                                json_encode('500') => (new Response)
                                                    ->withDescription('Server Error')
                                                    ->withContent([
                                                        'application/json' => (new MediaType)
                                                            ->withExample(
                                                                (new Example)
                                                                    ->withValue([
                                                                        'message' => 'An error occurred during the search operation.',
                                                                    ])
                                                                    ->generate()
                                                            )
                                                            ->generate(),
                                                    ])
                                                    ->generate(),
                                            ])
                                    )
                            )->withParameters([
                                (new Parameter)
                                    ->withName('query')
                                    ->withIn('query')
                                    ->withDescription('Search query string')
                                    ->withSchema((new SchemaConcrete)->withType('string'))
                                    ->withRequired(false),

                                (new Parameter)
                                    ->withName('sort')
                                    ->withIn('query')
                                    ->withDescription('Sorting option')
                                    ->withSchema((new SchemaConcrete)->withType('string'))
                                    ->withRequired(false),

                                (new Parameter)
                                    ->withName('type')
                                    ->withIn('query')
                                    ->withDescription('Type filter')
                                    ->withSchema((new SchemaConcrete)->withType('string'))
                                    ->withRequired(false),

                                (new Parameter)
                                    ->withName('tagType')
                                    ->withIn('query')
                                    ->withDescription('Tag type filter')
                                    ->withSchema((new SchemaConcrete)->withType('string'))
                                    ->withRequired(false),

                                (new Parameter)
                                    ->withName('page')
                                    ->withIn('query')
                                    ->withDescription('Page number for pagination')
                                    ->withSchema((new SchemaConcrete)->withType('integer'))
                                    ->withRequired(false),

                                (new Parameter)
                                    ->withName('limit')
                                    ->withIn('query')
                                    ->withDescription('Number of results per page')
                                    ->withSchema((new SchemaConcrete)->withType('integer'))
                                    ->withRequired(false),

                                (new Parameter)
                                    ->withName('offset')
                                    ->withIn('query')
                                    ->withDescription('Offset for pagination')
                                    ->withSchema((new SchemaConcrete)->withType('integer'))
                                    ->withRequired(false),
                            ]),
                    ] + (Features::enabled(Features::emailVerification()) ? [
                        '/api/auth/verify/{user_id}' => (new Path)
                            ->withGet(
                                (new Operation)
                                    ->withSummary('Email verification endpoint')
                                    ->withTags(['Authentication'])
                                    ->withRequestBody(
                                        (new RequestBody)
                                            ->withDescription('User object and Request Signature')
                                            ->withContent(
                                                [
                                                    'application/json' => (new MediaType)
                                                        ->withExample(
                                                            (new Example)
                                                                ->withValue([
                                                                    'user' => [
                                                                        'id' => 'example_user_id',
                                                                        'name' => 'example_name',
                                                                        'email' => 'example_email',
                                                                        'password' => 'example_password',
                                                                    ],
                                                                    'signature' => 'example_signature',
                                                                ])
                                                        ),
                                                ]
                                            )
                                    )
                                    ->withResponses(
                                        (new Responses)
                                            ->withDefault(
                                                (new Response)
                                                    ->withHeaders([
                                                        'Location' => (new Header)
                                                            ->withDescription('URL to redirect to')
                                                            ->withSchema((new SchemaConcrete)
                                                                ->withType('string')),
                                                        'Http-Response-Code' => (new Header)
                                                            ->withDescription('HTTP Response Code')
                                                            ->withSchema((new SchemaConcrete)
                                                                ->withType('integer')),
                                                    ])
                                                    ->withDescription('Email verification successful and redirected')
                                            )
                                            ->withOthers([
                                                json_encode('401') => (new Response)
                                                    ->withDescription('Invalid/Expired URL provided')
                                                    ->generate(),
                                            ])
                                    )
                            ),

                        '/api/auth/email/resend' => (new Path)
                            ->withGet(
                                (new Operation)
                                    ->withSummary('Resend email verification endpoint')
                                    ->withTags(['Authentication'])
                                    ->withResponses(
                                        (new Responses)
                                            ->withDefault((new Response)
                                                ->withDescription('Email verification link sent to your email address')
                                                ->generate())
                                    )
                            ),

                    ] : [])
                );

            return $openAPI;
        });
    }
}
