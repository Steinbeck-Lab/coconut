<?php

declare(strict_types=1);

use App\Filament\Dashboard\Resources\Blog\AuthorResource;
use App\Filament\Dashboard\Resources\Blog\CategoryResource;
use App\Filament\Dashboard\Resources\Blog\PostResource;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;

return [

    'shield_resource' => [
        'slug' => 'shield/roles',
        'show_model_path' => true,
        'cluster' => null,
        'tabs' => [
            'pages' => true,
            'widgets' => true,
            'resources' => true,
            'custom_permissions' => false,
        ],
    ],

    'tenant_model' => null,

    'auth_provider_model' => 'App\\Models\\User',

    'super_admin' => [
        'enabled' => true,
        'name' => 'super_admin',
        'define_via_gate' => false,
        'intercept_gate' => 'before',
    ],

    'panel_user' => [
        'enabled' => true,
        'name' => 'panel_user',
    ],

    'permissions' => [
        'separator' => ':',
        'case' => 'pascal',
        'generate' => true,
    ],

    'policies' => [
        'path' => app_path('Policies'),
        'merge' => true,
        'generate' => true,
        'methods' => [
            'viewAny', 'view', 'create', 'update', 'delete', 'deleteAny', 'restore',
            'forceDelete', 'forceDeleteAny', 'restoreAny', 'replicate', 'reorder',
        ],
        'single_parameter_methods' => [
            'viewAny',
            'create',
            'deleteAny',
            'forceDeleteAny',
            'restoreAny',
            'reorder',
        ],
    ],

    'localization' => [
        'enabled' => false,
        'key' => 'filament-shield::filament-shield.resource_permission_prefixes_labels',
    ],

    'resources' => [
        'subject' => 'model',
        'manage' => [
            RoleResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
            ],
        ],
        'exclude' => [
            // Blog is authorized via BlogPlugin on the dashboard panel.
            PostResource::class,
            CategoryResource::class,
            AuthorResource::class,
        ],
    ],

    'pages' => [
        'subject' => 'class',
        'prefix' => 'view',
        'exclude' => [
            Dashboard::class,
        ],
    ],

    'widgets' => [
        'subject' => 'class',
        'prefix' => 'view',
        'exclude' => [
            AccountWidget::class,
            FilamentInfoWidget::class,
        ],
    ],

    'custom_permissions' => [],

    'discovery' => [
        'discover_all_resources' => true,
        'discover_all_widgets' => false,
        'discover_all_pages' => false,
    ],

    'register_role_policy' => false,

];
