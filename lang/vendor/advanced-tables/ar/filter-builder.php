<?php

return [

    'form' => [

        'add_filter' => 'اضافة فلتر',
        'expand_view' => 'توسيع',
        'new_filter_group' => 'مجموعة تقارير جديدة',
        'or' => 'أو',
        'remove_filter' => 'ازالة',
        'recent' => 'مؤخرا',
        'relative' => 'نسبيا',
        'absolute' => 'مطلقا',

    ],

    'filters' => [

        'indicator_name' => 'فلتر',

        'operators' => [

            'and' => 'و',
            'or' => 'أو',

        ],

        'numeric' => [

            'equal_to' => [
                'indicator' => 'يساوي',
                'option' => 'يساوي',
            ],

            'not_equal_to' => [
                'indicator' => 'لا يساوي',
                'option' => 'لا يساوي',
            ],

            'greater_than' => [
                'indicator' => 'اكبر من',
                'option' => 'اكبر من',
            ],

            'greater_than_or_equal_to' => [
                'indicator' => 'اكبر او يساوي',
                'option' => 'اكبر او يساوي',
            ],

            'less_than' => [
                'indicator' => 'اقل من',
                'option' => 'اقل من',
            ],

            'less_than_or_equal_to' => [
                'indicator' => 'اقل او يساوي',
                'option' => 'اقل او يساوي',
            ],

            'between' => [
                'indicator' => 'ضمن',
                'option' => 'ضمن',
            ],

            'not_between' => [
                'indicator' => 'ليس ضمن',
                'option' => 'ليس ضمن',
            ],

            'positive' => [
                'indicator' => 'ايجابي',
                'option' => 'ايجابي',
            ],

            'negative' => [
                'indicator' => 'سلبي',
                'option' => 'سلبي',
            ],

        ],

        'text' => [

            'is' => [
                'indicator' => 'يساوي',
                'option' => 'يساوي',
            ],

            'is_not' => [
                'indicator' => 'لا يساوي',
                'option' => 'لا يساوي',
            ],

            'starts_with' => [
                'indicator' => 'يبدأ بـ',
                'option' => 'يبدأ بـ',
            ],

            'does_not_start_with' => [
                'indicator' => 'لا يبدأ بـ',
                'option' => 'لا يبدأ بـ',
            ],

            'ends_with' => [
                'indicator' => 'ينتهي بـ',
                'option' => 'ينتهي بـ',
            ],

            'does_not_end_with' => [
                'indicator' => 'لا ينتهي بـ',
                'option' => 'لا ينتهي بـ',
            ],

            'contains' => [
                'indicator' => 'يحتوي',
                'option' => 'يحتوي',
            ],

            'does_not_contain' => [
                'indicator' => 'لا يحتوي',
                'option' => 'لا يحتوي',
            ],

            'is_empty' => [
                'indicator' => 'is empty',
                'option' => 'is empty',
            ],

            'is_not_empty' => [
                'indicator' => 'is not empty',
                'option' => 'is not empty',
            ],

        ],

        'date' => [

            'yesterday' => [
                'indicator' => 'بالأمس',
                'option' => 'بالأمس',
            ],

            'today' => [
                'indicator' => 'اليوم',
                'option' => 'اليوم',
            ],

            'tomorrow' => [
                'indicator' => 'غدا',
                'option' => 'غدا',
            ],

            'in_this' => [
                'indicator' => 'خلال',
                'option' => 'خلال',
            ],

            'is_next' => [
                'indicator' => 'بجوار',
                'option' => 'بجوار',
            ],

            'is_last' => [
                'indicator' => 'هو الأخير',
                'option' => 'هو الأخير',
            ],

            'in_the_next' => [
                'indicator' => 'فترة قادمة',
                'option' => 'فترة قادمة',
            ],

            'in_the_last' => [
                'indicator' => 'فترة سابقة',
                'option' => 'فترة سابقة',
            ],

            'exactly' => [
                'indicator' => 'مطابق لـ',
                'option' => 'مطابق لـ',
            ],

            'before' => [
                'indicator' => 'قبل',
                'option' => 'قبل',
            ],

            'after' => [
                'indicator' => 'بعد',
                'option' => 'بعد',
            ],

            'between' => [
                'indicator' => 'بين',
                'option' => 'بين',
            ],

            'is_date' => [
                'indicator' => 'يساوي',
                'option' => 'يساوي',
            ],

            'before_date' => [
                'indicator' => 'قبل',
                'option' => 'قبل',
            ],

            'after_date' => [
                'indicator' => 'بعد',
                'option' => 'بعد',
            ],

            'between_dates' => [
                'indicator' => 'بين',
                'option' => 'بين',
            ],

            'unit' => [
                'week' => [
                    'indicator_singular' => 'اسبوع',
                    'indicator' => 'اسبوع',
                    'option' => 'اسبوع',
                ],

                'month' => [
                    'indicator_singular' => 'شهر',
                    'indicator' => 'شهر',
                    'option' => 'شهر',
                ],

                'quarter' => [
                    'indicator_singular' => 'ربع سنوي',
                    'indicator' => 'ربع سنوي',
                    'option' => 'ربع سنوي',
                ],

                'year' => [
                    'indicator_singular' => 'سنة',
                    'indicator' => 'سنة',
                    'option' => 'سنة',
                ],

                'days' => [
                    'indicator_singular' => 'يوم',
                    'indicator' => 'ايام',
                    'option' => 'ايام',
                ],

                'weeks' => [
                    'indicator_singular' => 'اسبوع',
                    'indicator' => 'اسابيع',
                    'option' => 'اسابيع',
                ],

                'months' => [
                    'indicator_singular' => 'شهر',
                    'indicator' => 'أشهر',
                    'option' => 'أشهر',
                ],

                'quarters' => [
                    'indicator_singular' => 'ربع سنوي',
                    'indicator' => 'ربع سنوي',
                    'option' => 'ربع سنوي',
                ],

                'years' => [
                    'indicator_singular' => 'سنة',
                    'indicator' => 'سنوات',
                    'option' => 'سنوات',
                ],

                'days_ago' => [
                    'indicator_singular' => 'يوم مضى',
                    'indicator' => 'أيام مضت',
                    'option' => 'أيام مضت',
                ],

                'days_from_now' => [
                    'indicator_singular' => 'يوم من الآن',
                    'indicator' => 'ايام من الآن',
                    'option' => 'ايام من الآن',
                ],

                'weeks_ago' => [
                    'indicator_singular' => 'اسبوع مضى',
                    'indicator' => 'أسابيع مضت',
                    'option' => 'أسابيع مضت',
                ],

                'weeks_from_now' => [
                    'indicator_singular' => 'اسبوع من الآن',
                    'indicator' => 'اسابيع من الآن',
                    'option' => 'اسابيع من الآن',
                ],

                'months_ago' => [
                    'indicator_singular' => 'شهر مضى',
                    'indicator' => 'أشهر مضت',
                    'option' => 'أشهر مضت',
                ],

                'months_from_now' => [
                    'indicator_singular' => 'شهر من الآن',
                    'indicator' => 'أشهر من الآن',
                    'option' => 'أشهر من الآن',
                ],

                'quarters_ago' => [
                    'indicator_singular' => 'ربع سنة مضت',
                    'indicator' => 'ربع سنوي',
                    'option' => 'ربع سنوي',
                ],

                'quarters_from_now' => [
                    'indicator_singular' => 'ربع سنة من الأن',
                    'indicator' => 'ربع سنة من الأن',
                    'option' => 'ربع سنة من الأن',
                ],

                'years_ago' => [
                    'indicator_singular' => 'سنة مضت',
                    'indicator' => 'سنوات مضت',
                    'option' => 'سنوات مضت',
                ],

                'years_from_now' => [
                    'indicator_singular' => 'سنة من الأن',
                    'indicator' => 'سنوات من الأن',
                    'option' => 'سنوات من الأن',
                ],

            ],

        ],

    ],

];
