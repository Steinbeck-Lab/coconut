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
                                            ))),
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
                                                ])))),
                        '/api/search' => (new Path)
                            ->withGet(
                                (new Operation)
                                    ->withSummary('Search endpoints')
                                    ->withTags(['Search'])
                                    ->withResponses(
                                        (new Responses)
                                            ->withDefault((new Response)
                                                ->withDescription('Search based on various query parameters')
                                                ->withContent([
                                                    'application/json' => [
                                                        'schema' => [
                                                            'type' => 'object',
                                                            'properties' => [
                                                                'data' => [
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ])
                                                ->generate())
                                            ->withOthers([
                                                json_encode('401') => (new Response)
                                                    ->withDescription('Unauthenticated')
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
                                    ->withDescription('Number of results per page (default: 24)')
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
                                            ))
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
                                                    ->withDescription('Email verification successful and redirected'))
                                            ->withOthers([
                                                json_encode('401') => (new Response)
                                                    ->withDescription('Invalid/Expired URL provided')
                                                    ->generate(),
                                            ]))),

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
                                    )),

                    ] : [])
                );

            return $openAPI;
        });

    }
}
