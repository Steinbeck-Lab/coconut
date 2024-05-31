<?php

return [

    'forms' => [

        'heading' => 'New view',
        'name' => 'Name',
        'user' => 'Owner',
        'resource' => 'Resource',
        'note' => 'Note',

        'status' => [

            'label' => 'Status',

        ],

        'name' => [

            'label' => 'Name',
            'helper_text' => 'Choose a short, but easily identifiable name for your view',

        ],

        'filters' => [

            'label' => 'View summary',
            'helper_text' => 'These configurations will be saved with this view',

        ],

        'panels' => [

            'label' => 'Panels',

        ],

        'preset_view' => [

            'label' => 'Preset view',
            'query_label' => 'Preset view query',
            'helper_text_start' => 'You are using the preset view ',
            'helper_text_end' => ' as the base for this view. Preset views may have their own independent configuration in addition to the configurations you have selected.',

        ],

        'icon' => [

            'label' => 'Icon',
            'placeholder' => 'Select an icon',

        ],

        'color' => [

            'label' => 'Color',

        ],

        'public' => [

            'label' => 'Make public',
            'toggle_label' => 'Is public',
            'helper_text' => 'Make this view available to all users',

        ],

        'favorite' => [

            'label' => 'Add to favorites',
            'toggle_label' => 'Is my favorite',
            'helper_text' => 'Add this view to your favorites',

        ],

        'global_favorite' => [

            'label' => 'Make global favorite',
            'toggle_label' => 'Is global favorite',
            'helper_text' => 'Add this view to the favorite list of all users',

        ],

    ],

    'notifications' => [

        'preset_views' => [

            'title' => 'Unable to create view',
            'body' => 'New views cannot be created from a preset view. Please build your view using the Default view or any user-created view.',

        ],

        'save_view' => [

            'saved' => [

                'title' => 'Saved',

            ],

        ],

        'edit_view' => [

            'saved' => [

                'title' => 'Saved',

            ],

        ],

        'replace_view' => [

            'replaced' => [

                'title' => 'Replaced',

            ],

        ],

    ],

    'quick_save' => [

        'save' => [

            'modal_heading' => 'Save view',
            'submit_label' => 'Save view',

        ],

    ],

    'select' => [

        'label' => 'Views',
        'placeholder' => 'Select view',

    ],

    'status' => [

        'approved' => 'approved',
        'pending' => 'pending',
        'rejected' => 'rejected',

    ],

    'tables' => [

        'favorites' => [

            'default' => 'Default',

        ],

        'columns' => [

            'user' => 'Owner',
            'icon' => 'Icon',
            'color' => 'Color',
            'name' => 'View name',
            'panel' => 'Panel',
            'resource' => 'Resource',
            'status' => 'Status',
            'filters' => 'Filters',
            'is_public' => 'Public',
            'is_user_favorite' => 'My favorite',
            'is_global_favorite' => 'Global',
            'sort_order' => 'Sort order',
            'users_favorite_sort_order' => 'Favorite sort order',

        ],

        'tooltips' => [

            'is_user_favorite' => [

                'unfavorite' => 'Unfavorite',
                'favorite' => 'Favorite',

            ],

            'is_public' => [

                'make_private' => 'Make private',
                'make_public' => 'Make public',

            ],

            'is_global_favorite' => [

                'make_personal' => 'Make personal',
                'make_global' => 'Make global',

            ],

        ],

        'actions' => [

            'buttons' => [

                'open' => 'Open',
                'approve' => 'Approve',

            ],

        ],

    ],

    'toggled_columns' => [

        'visible' => 'Visible',
        'hidden' => 'Hidden',

    ],

    'user_view_resource' => [

        'model_label' => 'User view',
        'plural_model_label' => 'User views',
        'navigation_label' => 'User Views',

    ],

    'view_manager' => [

        'actions' => [

            'add_view_to_favorites' => 'Add to favorites',
            'apply_view' => 'Apply view',
            'save' => 'Save',
            'save_view' => 'Save view',
            'delete_view' => 'Delete view',
            'delete_view_description' => 'This view is a :type view. Other users will lose access to your view. Are you sure you would like to proceed?',
            'delete_view_modal_submit_label' => 'Delete',
            'remove_view_from_favorites' => 'Remove from favorites',
            'edit_view' => 'Edit view',
            'replace_view' => 'Replace view',
            'replace_view_modal_description' => 'You are about to replace this saved view with the table\'s current configuration. Are you sure you would like to do this?',
            'replace_view_modal_submit_label' => 'Replace',
            'show_view_manager' => 'Show view manager',

        ],

        'badges' => [

            'active' => 'active',
            'preset' => 'preset',
            'user' => 'user',
            'global' => 'global',
            'public' => 'public',

        ],

        'heading' => 'View manager',

        'table_heading' => 'Views',

        'no_views' => 'No views',

        'subheadings' => [

            'user_favorites' => 'User favorites',
            'user_views' => 'User views',
            'preset_views' => 'Preset views',
            'global_views' => 'Global views',
            'public_views' => 'Public views',

        ],

    ],
];
