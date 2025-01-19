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
                                    ->withDescription("Advanced search using filters. Support the following filters:\n\n".
                                        "Molecular Properties:\n".
                                        "- tac: Total Atom Count (range: 1 to 1071)\n".
                                        "- hac: Heavy Atom Count (range: 0 to 551)\n".
                                        "- mw: Molecular Weight (range: 5.01 to 7860.71)\n".
                                        "- emw: Exact Molecular Weight (range: 1.00728 to 7855.66038)\n".
                                        "- mrc: Number of Minimal Rings (range: 0 to 51)\n".
                                        "- vdwv: Van der Walls Volume (range: 10.14 to 5177.31)\n".
                                        "- fc: Formal Charge (range: -8 to 7)\n".
                                        "\nChemical Properties:\n".
                                        "- alogp: ALogP (range: -82.67 to 67.03)\n".
                                        "- topopsa: Topological Polar Surface Area (range: 0.00 to 3453.72)\n".
                                        "- fcsp3: Fraction CSP3 (range: 0.00 to 1.00)\n".
                                        "- np: NP Likeness (range: -3.53 to 4.12)\n".
                                        "- qed: QED Drug Likeliness (range: 0.00 to 0.95)\n".
                                        "\nStructural Features:\n".
                                        "- rbc: Rotatable Bond Count (range: 0 to 224)\n".
                                        "- arc: Aromatic Rings Count (range: 0 to 31)\n".
                                        "- hba: Hydrogen Bond Acceptors (range: 0 to 191)\n".
                                        "- hbd: Hydrogen Bond Donors (range: 0 to 116)\n".
                                        "\nLipinski Parameters:\n".
                                        "- lhba: Lipinski H-Bond Acceptors (range: 0 to 191)\n".
                                        "- lhbd: Lipinski H-Bond Donors (range: 0 to 116)\n".
                                        "- lro5v: Lipinski Rule of 5 Violations (range: 0 to 4)\n".
                                        "\nSugar-Related Properties:\n".
                                        "- cs: Contains Sugar (true/false)\n".
                                        "- crs: Contains Ring Sugars (true/false)\n".
                                        "- cls: Contains Linear Sugars (true/false)\n".
                                        "\nClassifications:\n".
                                        "- class: Chemical Class (e.g., 1,2-diaryl-2-propen-1-ols)\n".
                                        "- subclass: Chemical Sub Class (e.g., 1,1'-azonaphthalenes)\n".
                                        "- superclass: Chemical Super Class (e.g., Acetylides)\n".
                                        "- parent: Direct Parent Classification (e.g., 1,1'-azonaphthalenes)\n".
                                        "\nNatural Product Classifications:\n".
                                        "- np_pathway: NP Classifier Pathway (e.g., Amino acids and Peptides)\n".
                                        "- np_superclass: NP Classifier Superclass (e.g., Aminosugars and aminoglycosides)\n".
                                        "- np_class: NP Classifier Class (e.g., 2-arylbenzofurans)\n".
                                        "- np_glycoside: NP Classifier Is Glycoside (true/false)\n".
                                        "\nUsage Examples:\n".
                                        "1. Range query: type=filters&q=tac:4..6\n".
                                        "2. Boolean query: type=filters&q=cs:true\n".
                                        "3. Classification query: type=filters&q=class:1,2-diaryl-2-propen-1-ols\n".
                                        "4. Multiple conditions: type=filters&q=tac:4..6 mw:100..200\n".
                                        '5. Complex query: type=filters&q=tac:4..6 cs:true OR mw:100..200')
                                    ->withTags(['Advanced Search'])
                                    ->withRequestBody(
                                        (new RequestBody)
                                            ->withContent([
                                                'application/json' => (new MediaType)
                                                    ->withExample(
                                                        (new Example)
                                                            ->withValue([
                                                                'type' => 'filters',
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
                                                                                'identifier' => 'CNP0000001',
                                                                                'name' => 'Example Molecule',
                                                                                'molecular_formula' => 'C4H10',
                                                                                'total_atom_count' => 5,
                                                                                'annotation_level' => 3,
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
                                                    ->withDescription('Example queries:\n'.
                                                        '1. Range query: /search?type=filters&q=tac:4..6\n'.
                                                        '2. Multiple conditions: /search?type=filters&q=tac:4..6 mw:100..200\n'.
                                                        '3. Boolean query: /search?type=filters&q=cs:true\n'.
                                                        '4. Database query: /search?type=filters&q=ds:pubchem|chembl\n'.
                                                        '5. Complex query: /search?type=filters&q=tac:4..6 cs:true OR mw:100..200')
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
