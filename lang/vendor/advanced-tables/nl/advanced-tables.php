<?php

return [

    'forms' => [

        'heading' => 'Nieuwe weergave',
        'name' => 'Naam',
        'user' => 'Eigenaar',
        'resource' => 'Bron',
        'note' => 'Notitie',

        'status' => [

            'label' => 'Status',

        ],

        'name' => [

            'label' => 'Naam',
            'helper_text' => 'Kies een korte, maar gemakkelijk herkenbare naam voor uw weergave',

        ],

        'filters' => [

            'label' => 'Weergavesamenvatting',
            'helper_text' => 'Deze configuraties worden opgeslagen met deze weergave',

        ],

        'panels' => [

            'label' => 'Panelen',

        ],

        'preset_view' => [

            'label' => 'Vooraf ingestelde weergave',
            'query_label' => 'Query voor vooraf ingestelde weergave',
            'helper_text_start' => 'U gebruikt de vooraf ingestelde weergave ',
            'helper_text_end' => ' als basis voor deze weergave. Vooraf ingestelde weergaven kunnen hun eigen onafhankelijke configuratie hebben naast de configuraties die u hebt geselecteerd.',

        ],

        'icon' => [

            'label' => 'Pictogram',
            'placeholder' => 'Selecteer een pictogram',

        ],

        'color' => [

            'label' => 'Kleur',

        ],

        'public' => [

            'label' => 'Openbaar maken',
            'toggle_label' => 'Is openbaar',
            'helper_text' => 'Maak deze weergave beschikbaar voor alle gebruikers',

        ],

        'favorite' => [

            'label' => 'Toevoegen aan favorieten',
            'toggle_label' => 'Is mijn favoriet',
            'helper_text' => 'Voeg deze weergave toe aan uw favorieten',

        ],

        'global_favorite' => [

            'label' => 'Maak globale favoriet',
            'toggle_label' => 'Is globale favoriet',
            'helper_text' => 'Voeg deze weergave toe aan de favorietenlijst van alle gebruikers',

        ],

    ],

    'notifications' => [

        'preset_views' => [

            'title' => 'Kan weergave niet maken',
            'body' => 'Nieuwe weergaven kunnen niet worden gemaakt vanuit een vooraf ingestelde weergave. Bouw alstublieft uw weergave met behulp van de Standaard weergave of een door de gebruiker gemaakte weergave.',

        ],

        'save_view' => [

            'saved' => [

                'title' => 'Opgeslagen',

            ],

        ],

        'edit_view' => [

            'saved' => [

                'title' => 'Opgeslagen',

            ],

        ],

        'replace_view' => [

            'replaced' => [

                'title' => 'Vervangen',

            ],

        ],

    ],

    'quick_save' => [

        'save' => [

            'modal_heading' => 'Weergave opslaan',
            'submit_label' => 'Weergave opslaan',

        ],

    ],

    'select' => [

        'label' => 'Weergaven',
        'placeholder' => 'Selecteer weergave',

    ],

    'status' => [

        'approved' => 'goedgekeurd',
        'pending' => 'in afwachting',
        'rejected' => 'afgewezen',

    ],

    'tables' => [

        'favorites' => [

            'default' => 'Standaard',

        ],

        'columns' => [

            'user' => 'Eigenaar',
            'icon' => 'Pictogram',
            'color' => 'Kleur',
            'name' => 'Weergavenaam',
            'panel' => 'Paneel',
            'resource' => 'Bron',
            'status' => 'Status',
            'filters' => 'Filters',
            'is_public' => 'Openbaar',
            'is_user_favorite' => 'Mijn favoriet',
            'is_global_favorite' => 'Globaal',
            'sort_order' => 'Sorteervolgorde',
            'users_favorite_sort_order' => 'Sorteervolgorde favorieten van gebruikers',

        ],

        'tooltips' => [

            'is_user_favorite' => [

                'unfavorite' => 'Niet meer favoriet',
                'favorite' => 'Favoriet',

            ],

            'is_public' => [

                'make_private' => 'Maak privé',
                'make_public' => 'Maak openbaar',

            ],

            'is_global_favorite' => [

                'make_personal' => 'Maak persoonlijk',
                'make_global' => 'Maak globaal',

            ],

        ],

        'actions' => [

            'buttons' => [

                'open' => 'Openen',
                'approve' => 'Goedkeuren',

            ],

        ],

    ],

    'toggled_columns' => [

        'visible' => 'Zichtbaar',
        'hidden' => 'Verborgen',

    ],

    'user_view_resource' => [

        'model_label' => 'Gebruikersweergave',
        'plural_model_label' => 'Gebruikersweergaven',
        'navigation_label' => 'Gebruikersweergaven',

    ],

    'view_manager' => [

        'actions' => [

            'add_view_to_favorites' => 'Toevoegen aan favorieten',
            'apply_view' => 'Weergave toepassen',
            'save' => 'Opslaan',
            'save_view' => 'Weergave opslaan',
            'delete_view' => 'Weergave verwijderen',
            'delete_view_description' => 'Deze weergave is een :type weergave. Andere gebruikers verliezen toegang tot uw weergave. Weet u zeker dat u door wilt gaan?',
            'delete_view_modal_submit_label' => 'Verwijderen',
            'remove_view_from_favorites' => 'Verwijderen uit favorieten',
            'edit_view' => 'Weergave bewerken',
            'replace_view' => 'Weergave vervangen',
            'replace_view_modal_description' => 'U staat op het punt deze opgeslagen weergave te vervangen door de huidige configuratie van de tabel. Weet u zeker dat u dit wilt doen?',
            'replace_view_modal_submit_label' => 'Vervangen',
            'show_view_manager' => 'Weergavebeheer tonen',

        ],

        'badges' => [

            'active' => 'actief',
            'preset' => 'vooraf ingesteld',
            'user' => 'gebruiker',
            'global' => 'Globaal',
            'public' => 'openbaar',

        ],

        'heading' => 'Weergavebeheer',

        'table_heading' => 'Weergaven',

        'no_views' => 'Geen weergaven',

        'subheadings' => [

            'user_favorites' => 'Gebruikersfavorieten',
            'user_views' => 'Gebruikersweergaven',
            'preset_views' => 'Vooraf ingestelde weergaven',
            'global_views' => 'Globale weergaven',
            'public_views' => 'Openbare weergaven',

        ],

    ],
];
