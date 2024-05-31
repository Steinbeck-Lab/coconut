<?php

return [

    'forms' => [

        'heading' => 'Nouvelle vue',
        'name' => 'Nom',
        'user' => 'Utilisateur',
        'resource' => 'Ressource',
        'note' => 'Note',

        'status' => [

            'label' => 'Statut',

        ],

        'name' => [

            'label' => 'Nom',
            'helper_text' => 'Choisissez un nom court mais facilement identifiable pour votre vue',

        ],

        'panels' => [

            'label' => 'Panneaux',

        ],

        'filters' => [

            'label' => 'résumé des vue',
            'helper_text' => 'Ces configurations seront enregistrées avec cette vue',

        ],

        'preset_view' => [

            'label' => 'Vue prédéfinie',
            'query_label' => 'Requête de vue prédéfinie',
            'helper_text_start' => 'Vous utilisez la vue prédéfinie ',
            'helper_text_end' => ' comme base pour cette vue. Les vues prédéfinies peuvent avoir leur propre configuration indépendante en plus des configurations que vous avez sélectionnées.',

        ],

        'icon' => [

            'label' => 'Icône',
            'placeholder' => 'Sélectionner une icône',
        ],

        'color' => [

            'label' => 'Couleur',

        ],

        'public' => [

            'label' => 'Rendre publique',
            'toggle_label' => 'Est publique',
            'helper_text' => 'Rendre cette vue disponible à tous les utilisateurs',

        ],

        'favorite' => [

            'label' => 'Ajouter aux favoris',
            'toggle_label' => 'Est favori',
            'helper_text' => 'Ajoutez cette vue à vos favoris',

        ],

        'global_favorite' => [

            'label' => 'Rendre favori mondial',
            'toggle_label' => 'Est favori mondial',
            'helper_text' => 'Ajouter cette vue à la liste des favoris de tous les utilisateurs',

        ],

    ],

    'notifications' => [

        'preset_views' => [

            'title' => 'Impossible de créer une vue',
            'body' => "De nouvelles vues ne peuvent pas être créées à partir d'une vue prédéfinie. Veuillez créer votre vue en utilisant la vue par défaut ou toute vue créée par l'utilisateur.",

        ],

        'save_view' => [

            'saved' => [

                'title' => 'Enregistré',

            ],

        ],

        'edit_view' => [

            'saved' => [

                'title' => 'Enregistré',

            ],

        ],

        'replace_view' => [

            'replaced' => [

                'title' => 'Remplacé',

            ],

        ],

    ],

    'quick_save' => [

        'save' => [

            'modal_heading' => 'Enregistrer vue',
            'submit_label' => 'Enregistrer vue',

        ],

    ],

    'select' => [

        'label' => 'Vues',
        'placeholder' => 'Sélectionner une vue',

    ],

    'status' => [

        'approved' => 'approuvé',
        'pending' => 'en attente',
        'rejected' => 'rejeté',

    ],

    'tables' => [

        'favorites' => [

            'default' => 'Défaut',

        ],

        'columns' => [

            'user' => 'Utilisateur',
            'icon' => 'Icône',
            'color' => 'Couleur',
            'name' => 'Nom de la vue',
            'panel' => 'Panneau',
            'resource' => 'Ressource',
            'status' => 'Statut',
            'filters' => 'Filtres',
            'is_public' => 'Publique',
            'is_user_favorite' => 'Favori',
            'is_global_favorite' => 'Mondial',
            'sort_order' => 'Ordre de tri',
            'users_favorite_sort_order' => 'Ordre de tri des favoris',

        ],

        'tooltips' => [

            'is_user_favorite' => [

                'unfavorite' => 'Retirer des favoris',
                'favorite' => 'Ajouter aux favoris',

            ],

            'is_public' => [

                'make_private' => 'Rendre privé',
                'make_public' => 'Rendre publique',

            ],

            'is_global_favorite' => [

                'make_personal' => 'Rendre personnel',
                'make_global' => 'Rendre mondial',

            ],

        ],

        'actions' => [

            'buttons' => [

                'open' => 'Ouvrir',
                'approve' => 'Approuver',

            ],

        ],

    ],

    'toggled_columns' => [

        'visible' => 'Visible',
        'hidden' => 'Cachée',

    ],

    'user_view_resource' => [

        'model_label' => 'Voir',
        'plural_model_label' => 'Vues',
        'navigation_label' => 'Vues',

    ],

    'view_manager' => [

        'actions' => [

            'add_view_to_favorites' => 'Ajouter aux favoris',
            'apply_view' => 'Appliquer vue',
            'save' => 'Enregistré',
            'save_view' => 'Enregistrer vue',
            'delete_view' => 'Supprimer vue',
            'delete_view_modal_submit_label' => 'Supprimer',
            'delete_view_description' => 'Cette vision est une vision :type.  Les autres utilisateurs perdront l\'accès à votre vue. Êtes-vous sûr de vouloir continuer ?',
            'remove_view_from_favorites' => 'Retirer des favoris',
            'edit_view' => 'Modifier vue',
            'replace_view' => 'Remplacer vue',
            'replace_view_modal_description' => 'Vous êtes sur le point de remplacer cette vue enregistrée par la configuration actuelle de la table. Êtes-vous sûr de vouloir faire cela?',
            'replace_view_modal_submit_label' => 'Remplacer',
            'show_view_manager' => 'Afficher gestionnaire de vues',

        ],

        'badges' => [

            'active' => 'active',
            'preset' => 'prédéfinie',
            'user' => 'utilisateur',
            'global' => 'mondiale',
            'public' => 'publique',

        ],

        'heading' => 'Gestionnaire de vues',

        'table_heading' => 'Vues',

        'no_views' => 'Aucune vues',

        'subheadings' => [

            'user_favorites' => 'Vues favoris',
            'user_views' => 'Vues des utilisateurs',
            'preset_views' => 'Vues prédéfinies',
            'global_views' => 'Vues mondiales',
            'public_views' => 'Vues publiques',

        ],

    ],
];
