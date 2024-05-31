<?php

return [

    'form' => [

        'add_filter' => 'Ajouter un filtre',
        'expand_view' => 'Agrandir',
        'new_filter_group' => 'Nouveau groupe',
        'or' => 'ou',
        'remove_filter' => 'Retirer',
        'recent' => 'Recent',
        'relative' => 'Relative',
        'absolute' => 'Absolute',

    ],

    'filters' => [

        'indicator_name' => 'Groupe',

        'operators' => [

            'and' => 'et',
            'or' => 'ou',

        ],

        'numeric' => [

            'equal_to' => [
                'indicator' => 'est égal à',
                'option' => 'égal à',
            ],

            'not_equal_to' => [
                'indicator' => 'n\'est pas égal à',
                'option' => 'n\'est pas égal à',
            ],

            'greater_than' => [
                'indicator' => 'est supérieur à',
                'option' => 'supérieur à',
            ],

            'greater_than_or_equal_to' => [
                'indicator' => 'est supérieur ou égal à',
                'option' => 'supérieur ou égal à',
            ],

            'less_than' => [
                'indicator' => 'est inférieur à',
                'option' => 'inférieur à',
            ],

            'less_than_or_equal_to' => [
                'indicator' => 'est inférieur ou égal à',
                'option' => 'inférieur ou égal à',
            ],

            'between' => [
                'indicator' => 'est entre',
                'option' => 'entre',
            ],

            'not_between' => [
                'indicator' => 'n\'est pas entre',
                'option' => 'n\'est pas entre',
            ],

            'positive' => [
                'indicator' => 'est positif',
                'option' => 'est positif',
            ],

            'negative' => [
                'indicator' => 'est negatif',
                'option' => 'est negatif',
            ],

        ],

        'text' => [

            'is' => [
                'indicator' => 'est',
                'option' => 'est',
            ],

            'is_not' => [
                'indicator' => 'n\'est pas',
                'option' => 'n\'est pas',
            ],

            'starts_with' => [
                'indicator' => 'commence par',
                'option' => 'commence par',
            ],

            'does_not_start_with' => [
                'indicator' => 'ne commence pas par',
                'option' => 'ne commence pas par',
            ],

            'ends_with' => [
                'indicator' => 'se termine par',
                'option' => 'se termine par',
            ],

            'does_not_end_with' => [
                'indicator' => 'ne se termine pas par',
                'option' => 'ne se termine pas par',
            ],

            'contains' => [
                'indicator' => 'contient',
                'option' => 'contient',
            ],

            'does_not_contain' => [
                'indicator' => 'ne contient pas',
                'option' => 'ne contient pas',
            ],

            'is_empty' => [
                'indicator' => 'est vide',
                'option' => 'est vide',
            ],

            'is_not_empty' => [
                'indicator' => 'n\'est pas vide',
                'option' => 'n\'est pas vide',
            ],

        ],

        'date' => [

            'yesterday' => [
                'indicator' => 'est hier',
                'option' => 'est hier',
            ],

            'today' => [
                'indicator' => 'est aujourd\'hui',
                'option' => 'est aujourd\'hui',
            ],

            'tomorrow' => [
                'indicator' => 'est demain',
                'option' => 'est demain',
            ],

            'in_this' => [
                'indicator' => 'est cette',
                'option' => 'est cette',
            ],

            'is_next' => [
                'indicator' => 'est les prochaines',
                'option' => 'est les prochaines',
            ],

            'is_last' => [
                'indicator' => 'est les dernières',
                'option' => 'est les dernières',
            ],

            'in_the_next' => [
                'indicator' => 'est dans les prochaines',
                'option' => 'est dans les prochaines',
            ],

            'in_the_last' => [
                'indicator' => 'est dans les dernières',
                'option' => 'est dans les dernières',
            ],

            'exactly' => [
                'indicator' => 'est exactement',
                'option' => 'est exactement',
            ],

            'before' => [
                'indicator' => 'est avant',
                'option' => 'est avant',
            ],

            'after' => [
                'indicator' => 'est après',
                'option' => 'est après',
            ],

            'between' => [
                'indicator' => 'est entre',
                'option' => 'est entre',
            ],

            'is_date' => [
                'indicator' => 'est',
                'option' => 'est la date',
            ],

            'before_date' => [
                'indicator' => 'est avant',
                'option' => 'est avant la date',
            ],

            'after_date' => [
                'indicator' => 'est après',
                'option' => 'est après la date',
            ],

            'between_dates' => [
                'indicator' => 'est entre',
                'option' => 'est entre les dates',
            ],

            'unit' => [
                'week' => [
                    'indicator_singular' => 'semaine',
                    'indicator' => 'semaine',
                    'option' => 'semaine',
                ],

                'month' => [
                    'indicator_singular' => 'mois',
                    'indicator' => 'mois',
                    'option' => 'mois',
                ],

                'quarter' => [
                    'indicator_singular' => 'trimestre',
                    'indicator' => 'trimestre',
                    'option' => 'trimestre',
                ],

                'year' => [
                    'indicator_singular' => 'année',
                    'indicator' => 'année',
                    'option' => 'année',
                ],

                'days' => [
                    'indicator_singular' => 'jour',
                    'indicator' => 'jours',
                    'option' => 'jours',
                ],

                'weeks' => [
                    'indicator_singular' => 'semaine',
                    'indicator' => 'semaines',
                    'option' => 'semaines',
                ],

                'months' => [
                    'indicator_singular' => 'mois',
                    'indicator' => 'mois',
                    'option' => 'mois',
                ],

                'quarters' => [
                    'indicator_singular' => 'trimestre',
                    'indicator' => 'trimestres',
                    'option' => 'trimestres',
                ],

                'years' => [
                    'indicator_singular' => 'année',
                    'indicator' => 'années',
                    'option' => 'années',
                ],

                'days_ago' => [
                    'indicator_singular' => 'jour avant',
                    'indicator' => 'jours avant',
                    'option' => 'jours avant',
                ],

                'days_from_now' => [
                    'indicator_singular' => 'jour à partir de maintenant',
                    'indicator' => 'jours à partir de maintenant',
                    'option' => 'jours à partir de maintenant',
                ],

                'weeks_ago' => [
                    'indicator_singular' => 'semaine avant',
                    'indicator' => 'semaines avant',
                    'option' => 'semaines avant',
                ],

                'weeks_from_now' => [
                    'indicator_singular' => 'semaine à partir de maintenant',
                    'indicator' => 'semaines à partir de maintenant',
                    'option' => 'semaines à partir de maintenant',
                ],

                'months_ago' => [
                    'indicator_singular' => 'mois avant',
                    'indicator' => 'mois avant',
                    'option' => 'mois avant',
                ],

                'months_from_now' => [
                    'indicator_singular' => 'mois à partir de maintenant',
                    'indicator' => 'mois à partir de maintenant',
                    'option' => 'mois à partir de maintenant',
                ],

                'quarters_ago' => [
                    'indicator_singular' => 'trimestre avant',
                    'indicator' => 'trimestres avant',
                    'option' => 'trimestres avant',
                ],

                'quarters_from_now' => [
                    'indicator_singular' => 'trimestre à partir de maintenant',
                    'indicator' => 'trimestres à partir de maintenant',
                    'option' => 'trimestres à partir de maintenant',
                ],

                'years_ago' => [
                    'indicator_singular' => 'an avant',
                    'indicator' => 'ans avant',
                    'option' => 'ans avant',
                ],

                'years_from_now' => [
                    'indicator_singular' => 'an à partir de maintenant',
                    'indicator' => 'ans à partir de maintenant',
                    'option' => 'ans à partir de maintenant',
                ],

            ],

        ],

    ],

];
