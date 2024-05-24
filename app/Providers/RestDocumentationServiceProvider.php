<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Features;
use Lomkit\Rest\Documentation\Schemas\Example;
use Lomkit\Rest\Documentation\Schemas\Header;
use Lomkit\Rest\Documentation\Schemas\MediaType;
use Lomkit\Rest\Documentation\Schemas\OpenAPI;
use Lomkit\Rest\Documentation\Schemas\Operation;
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
                        '/api/v1/auth/login' => (new Path)
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
                                                                    'email' => 'user@example.com',
                                                                    'password' => 'password',
                                                                ])
                                                                ->generate()
                                                        )
                                                        ->generate(),
                                                ]
                                            )
                                    )
                                    ->withResponses(
                                        (new Responses())
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
                                            ->withOthers([
                                                json_encode('422') => (new Response)
                                                    ->withDescription('Account is not yet verified. Please verify your email address by clicking on the link we just emailed to you.')
                                                    ->generate(),
                                                json_encode('401') => (new Response)
                                                    ->withDescription('Invalid login details')
                                                    ->generate(),

                                            ]))),
                        '/api/v1/auth/logout' => (new Path)
                            ->withGet(
                                (new Operation)
                                    ->withSummary('Logout endpoint')
                                    ->withTags(['Authentication'])
                                    ->withResponses(
                                        (new Responses())
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
                        '/api/v1/auth/register' => (new Path)
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
                                                                    'name' => 'John Doe',
                                                                    'email' => 'john@example.com',
                                                                    'mobile_phone' => '9876543210',
                                                                    'password' => 'password',
                                                                ])
                                                                ->generate()
                                                        )
                                                        ->generate(),
                                                ]
                                            )
                                    )
                                    ->withResponses(
                                        (new Responses())->withDefault((new Response)
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
                        '/api/v1/auth/otp/login' => (new Path)
                            ->withPost(
                                (new Operation)
                                    ->withSummary('OTP Login endpoint')
                                    ->withTags(['Authentication'])
                                    ->withRequestBody(
                                        (new RequestBody)
                                            ->withContent(
                                                [
                                                    'application/json' => (new MediaType)
                                                        ->withExample(
                                                            (new Example)
                                                                ->withValue([
                                                                    'mobile_number' => '9876543210',
                                                                ])
                                                                ->generate()
                                                        )
                                                        ->generate(),
                                                ]
                                            )
                                    )
                                    ->withResponses(
                                        (new Responses())
                                            ->withDefault(
                                                (new Response)
                                                    ->withDescription('Login successful')
                                                    ->withContent([
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
                                                                ],
                                                            ],
                                                        ],
                                                    ])
                                            )
                                            ->withOthers([
                                                json_encode('422') => (new Response)
                                                    ->withDescription('Validation error')
                                                    ->withContent([
                                                        'application/json' => [
                                                            'schema' => [
                                                                'type' => 'object',
                                                                'properties' => [
                                                                    'error' => [
                                                                        'type' => 'string',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ])
                                                    ->generate(),
                                            ])
                                    )
                            ),
                        '/api/v1/auth/otp/verify' => (new Path)
                            ->withPost(
                                (new Operation)
                                    ->withSummary('Verify OTP endpoint')
                                    ->withTags(['Authentication'])
                                    ->withRequestBody(
                                        (new RequestBody)
                                            ->withContent(
                                                [
                                                    'application/json' => (new MediaType)
                                                        ->withExample(
                                                            (new Example)
                                                                ->withValue([
                                                                    'passcode' => '123456',
                                                                    'reference_token' => '<token>',
                                                                ])
                                                                ->generate()
                                                        )
                                                        ->generate(),
                                                ]
                                            )
                                    )
                                    ->withResponses(
                                        (new Responses())
                                            ->withDefault(
                                                (new Response)
                                                    ->withDescription('OTP verification successful')
                                                    ->withContent([
                                                        'application/json' => [
                                                            'schema' => [
                                                                'type' => 'object',
                                                                'properties' => [
                                                                    'success' => [
                                                                        'type' => 'boolean',
                                                                    ],
                                                                    'access_token' => [
                                                                        'type' => 'string',
                                                                    ],
                                                                    'token_type' => [
                                                                        'type' => 'string',
                                                                    ],
                                                                    'message' => [
                                                                        'type' => 'string',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ])
                                            )
                                            ->withOthers([
                                                json_encode('401') => (new Response)
                                                    ->withDescription('Invalid OTP')
                                                    ->withContent([
                                                        'application/json' => [
                                                            'schema' => [
                                                                'type' => 'object',
                                                                'properties' => [
                                                                    'msg' => [
                                                                        'type' => 'string',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ])
                                                    ->generate(),
                                            ])
                                    )
                            ),
                    ] + (Features::enabled(Features::emailVerification()) ? [
                        '/api/v1/auth/verify/{user_id}' => (new Path)
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
                                        (new Responses())
                                            ->withDefault(
                                                (new Response)
                                                    ->withHeaders([
                                                        'Location' => (new Header)
                                                            ->withDescription('URL to redirect to')
                                                            ->withSchema((new SchemaConcrete())
                                                                ->withType('string')),
                                                        'Http-Response-Code' => (new Header)
                                                            ->withDescription('HTTP Response Code')
                                                            ->withSchema((new SchemaConcrete())
                                                                ->withType('integer')),
                                                    ])
                                                    ->withDescription('Email verification successful and redirected'))
                                            ->withOthers([
                                                json_encode('401') => (new Response)
                                                    ->withDescription('Invalid/Expired URL provided')
                                                    ->generate(),
                                            ]))),

                        '/api/v1/auth/email/resend' => (new Path)
                            ->withGet(
                                (new Operation)
                                    ->withSummary('Resend email verification endpoint')
                                    ->withTags(['Authentication'])
                                    ->withResponses(
                                        (new Responses())
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
