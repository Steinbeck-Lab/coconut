<?php

return [

    'forms' => [

        'heading' => 'Neue Ansicht',
        'name' => 'Name',
        'user' => 'Besitzer',
        'resource' => 'Ressource',
        'note' => 'Notiz',

        'status' => [

            'label' => 'Status',

        ],

        'name' => [

            'label' => 'Name',
            'helper_text' => 'Wähle einen kurzen aber leicht identifizerbaren Namen für deine Ansicht',

        ],

        'filters' => [

            'label' => 'Ansichtszusammenfassung',
            'helper_text' => 'Diese Konfigurationen werden zusammen mit dieser Ansicht gespeichert',

        ],

        'panels' => [

            'label' => 'Panels',

        ],

        'preset_view' => [

            'label' => 'Voreingestellte Ansicht',
            'query_label' => 'Voreingestellte Ansichtsquery',
            'helper_text_start' => 'Die Voreingestellte Ansicht ',
            'helper_text_end' => ' wird als Basis für diese Ansicht verwendet. Voreingestellte Ansichten haben, zusätzlich zu der ausgewählten Konfiguration, ihre eigenen Konfigurationen.',

        ],

        'icon' => [

            'label' => 'Symbol',
            'placeholder' => 'Wähle ein Symbol aus',

        ],

        'color' => [

            'label' => 'Farbe',

        ],

        'public' => [

            'label' => 'Veröffentlichen',
            'toggle_label' => 'Ist veröffentlicht',
            'helper_text' => 'Stelle diese Ansicht für alle Benutzer bereit',

        ],

        'favorite' => [

            'label' => 'Zu den Favoriten hinzufügen',
            'toggle_label' => 'Ist mein Favorit',
            'helper_text' => 'Füge diese Ansicht zu den Favoriten hinzu',

        ],

        'global_favorite' => [

            'label' => 'Globalen favoriten erstellen',
            'toggle_label' => 'Ist globaler Favorit',
            'helper_text' => 'Füge diese Ansicht zu den Favoriten von allen Benutzern hinzu',

        ],

    ],

    'notifications' => [

        'preset_views' => [

            'title' => 'Ansicht konnte nicht erstellt werden',
            'body' => 'Aus einer voreingestellten Ansicht können keine neuen Ansichten erstellt werden. Bitte erstellen Sie Ihre Ansicht mit einer voreingestellten Ansicht oder einer beliebigen Benutzeransicht.',

        ],

        'save_view' => [

            'saved' => [

                'title' => 'Gespeichert',

            ],

        ],

        'edit_view' => [

            'saved' => [

                'title' => 'Gespeichert',

            ],

        ],

        'replace_view' => [

            'replaced' => [

                'title' => 'Ersetzt',

            ],

        ],

    ],

    'quick_save' => [

        'save' => [

            'modal_heading' => 'Ansicht speichern',
            'submit_label' => 'Ansicht speichern',

        ],

    ],

    'select' => [

        'label' => 'Ansichten',
        'placeholder' => 'Ansicht auswählen',

    ],

    'status' => [

        'approved' => 'Genehmigt',
        'pending' => 'Ausstehend',
        'rejected' => 'Abgelehnt',

    ],

    'tables' => [

        'favorites' => [

            'default' => 'Standard',

        ],

        'columns' => [

            'user' => 'Besitzer',
            'icon' => 'Symbol',
            'color' => 'Farbe',
            'name' => 'Ansichtsname',
            'panel' => 'Panel',
            'resource' => 'Ressource',
            'status' => 'Status',
            'filters' => 'Filter',
            'is_public' => 'Öffentlich',
            'is_user_favorite' => 'Favorit',
            'is_global_favorite' => 'Global',
            'sort_order' => 'Sortierreihenfolge',
            'users_favorite_sort_order' => 'Favoriten Sortierreihenfolge',

        ],

        'tooltips' => [

            'is_user_favorite' => [

                'unfavorite' => 'Entfavorisieren',
                'favorite' => 'Favorisieren',

            ],

            'is_public' => [

                'make_private' => 'Privat stellen',
                'make_public' => 'Öffentlich stellen',

            ],

            'is_global_favorite' => [

                'make_personal' => 'Persönlich stellen',
                'make_global' => 'Global stellen',

            ],

        ],

        'actions' => [

            'buttons' => [

                'open' => 'Öffnen',
                'approve' => 'Genehmigen',

            ],

        ],

    ],

    'toggled_columns' => [

        'visible' => 'Sichtbar',
        'hidden' => 'Versteckt',

    ],

    'user_view_resource' => [

        'model_label' => 'Benutzeransicht',
        'plural_model_label' => 'Benutzeransichten',
        'navigation_label' => 'Benutzeransichten',

    ],

    'view_manager' => [

        'actions' => [

            'add_view_to_favorites' => 'Zu den Favoriten hinzufügen',
            'apply_view' => 'Ansicht anwenden',
            'save' => 'Speichern',
            'save_view' => 'Ansicht speichern',
            'delete_view' => 'Ansicht löschen',
            'delete_view_description' => 'Diese Ansicht ist eine :type. Other users will lose access to your view. Are you sure you would like to proceed?',
            'delete_view_modal_submit_label' => 'Löschen',
            'remove_view_from_favorites' => 'Aus den Favoriten entfernen',
            'edit_view' => 'Ansicht bearbeiten',
            'replace_view' => 'Ansicht ersetzen',
            'replace_view_modal_description' => 'Sie sind dabei, diese gespeicherte Ansicht durch die aktuelle Konfiguration der Tabelle zu ersetzen. Sind Sie sicher, dass Sie dies tun möchten?',
            'replace_view_modal_submit_label' => 'Ersetzen',
            'show_view_manager' => 'Ansichtsmanager anzeigen',

        ],

        'badges' => [

            'active' => 'Aktiv',
            'preset' => 'Voreingestellt',
            'user' => 'Benutzer',
            'global' => 'Global',
            'public' => 'Öffentlich',

        ],

        'heading' => 'Ansichtsmanager',

        'table_heading' => 'Ansichten',

        'no_views' => 'Keine Ansichten',

        'subheadings' => [

            'user_favorites' => 'Benutzerfavoriten',
            'user_views' => 'Benutzeransichten',
            'preset_views' => 'Voreingestellte Ansichten',
            'global_views' => 'Globale Ansichten',
            'public_views' => 'Öffentliche Ansichten',

        ],

    ],
];
