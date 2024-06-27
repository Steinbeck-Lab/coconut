<?php

return [

    'forms' => [

        'heading' => 'تقرير جديد',
        'name' => 'الإسم',
        'user' => 'المالك',
        'resource' => 'المصدر',
        'note' => 'ملاحظات',

        'status' => [

            'label' => 'الحالة',

        ],

        'name' => [

            'label' => 'الاسم',
            'helper_text' => 'اختر اسم مختصر لتمييز التقرير الخاص بك',

        ],

        'filters' => [

            'label' => 'عرض الملخص',
            'helper_text' => 'سيتم حفظ هذه الإعدادات للتقرير',

        ],

        'panels' => [

            'label' => 'لوحة التحكم',

        ],

        'preset_view' => [

            'label' => 'تقارير النظام',
            'query_label' => 'استعلام تقرير النظام',
            'helper_text_start' => 'انت تسخدم التقرير المعرف مسبقا ',
            'helper_text_end' => ' كأساس للتقرير الحالي، التقرير المعرف مسبقا له اعدادات خاصة به بالإضافة للإعدادات الحالية',

        ],

        'icon' => [

            'label' => 'ايقونة',
            'placeholder' => 'اختر ايقونة',

        ],

        'color' => [

            'label' => 'اللون',

        ],

        'public' => [

            'label' => 'السماح بعرضه للكل',
            'toggle_label' => 'ظاهر للكل',
            'helper_text' => 'اظهر هذا التقرير لكافة المستخدمين',

        ],

        'favorite' => [

            'label' => 'اضافة للمفضلة',
            'toggle_label' => 'في مفضلتي',
            'helper_text' => 'اضافة هذا التقرير للمفضلة الخاصة بك',

        ],

        'global_favorite' => [

            'label' => 'اضافة للمفضلة العامة',
            'toggle_label' => 'مفضلة شاملة',
            'helper_text' => 'أضف هذا التقرير إلى القائمة المفضلة لجميع المستخدمين',

        ],

    ],

    'notifications' => [

        'preset_views' => [

            'title' => 'لايمكن انشاء التقرير',
            'body' => 'لا يمكن انشاء تقرير من التقارير المعرفة مسبقا، فضلا اختر تقرير اخر او انشى التقرير الخاص بك',

        ],

        'save_view' => [

            'saved' => [

                'title' => 'تم الحفظ',

            ],

        ],

        'edit_view' => [

            'saved' => [

                'title' => 'تم الحفظ',

            ],

        ],

        'replace_view' => [

            'replaced' => [

                'title' => 'استبدال',

            ],

        ],

    ],

    'quick_save' => [

        'save' => [

            'modal_heading' => 'اضافة تقرير',
            'submit_label' => 'حفظ التقرير',

        ],

    ],

    'select' => [

        'label' => 'التقارير',
        'placeholder' => 'اختر تقرير',

    ],

    'status' => [

        'approved' => 'معتمد',
        'pending' => 'بالانتظار',
        'rejected' => 'مرفوض',

    ],

    'tables' => [

        'favorites' => [

            'default' => 'افتراضي',

        ],

        'columns' => [

            'user' => 'المالك',
            'icon' => 'الأيقونة',
            'color' => 'اللون',
            'name' => 'اسم التقرير',
            'panel' => 'لوحة التحكم',
            'resource' => 'التطبيق',
            'status' => 'الحالة',
            'filters' => 'الفلاتر',
            'is_public' => 'للكل',
            'is_user_favorite' => 'مفضلتي',
            'is_global_favorite' => 'عام',
            'sort_order' => 'الترتيب',
            'users_favorite_sort_order' => 'ترتيب المفضلة',

        ],

        'tooltips' => [

            'is_user_favorite' => [

                'unfavorite' => 'ازالة من المفضلة',
                'favorite' => 'المفضلة',

            ],

            'is_public' => [

                'make_private' => 'تعيين خاص',
                'make_public' => 'تعيين عام',

            ],

            'is_global_favorite' => [

                'make_personal' => 'تعيين شخصي',
                'make_global' => 'تعيين شامل',

            ],

        ],

        'actions' => [

            'buttons' => [

                'open' => 'عرض',
                'approve' => 'اعتماد',

            ],

        ],

    ],

    'toggled_columns' => [

        'visible' => 'ظاهر',
        'hidden' => 'مخفي',

    ],

    'user_view_resource' => [

        'model_label' => 'تقرير المستخدم',
        'plural_model_label' => 'التقارير الخاصة بك',
        'navigation_label' => 'التقارير',

    ],

    'view_manager' => [

        'actions' => [

            'add_view_to_favorites' => 'اضافة للمفضلة',
            'apply_view' => 'تطبيق التقرير',
            'save' => 'حفظ',
            'save_view' => 'حفظ التقرير',
            'delete_view' => 'حذف التقرير',
            'delete_view_description' => 'هذا التقرير من نوع :type سيتم ازالة التقرير من كافة المستخدمين، هل أنت متأكد من رغبتك في حذف التقرير',
            'delete_view_modal_submit_label' => 'حذف',
            'remove_view_from_favorites' => 'ازالة من المفضلة',
            'edit_view' => 'تعديل التقرير',
            'replace_view' => 'استبدال التقرير',
            'replace_view_modal_description' => 'انت على وشك استبدال التقرير المحفوظ بالاعدادات الحالية المطبقة على الجدول، هل أنت متأكد من رغبتك بالاستمرار؟',
            'replace_view_modal_submit_label' => 'استبدال',
            'show_view_manager' => 'عرض ادارة التقارير',

        ],

        'badges' => [

            'active' => 'مفعل',
            'preset' => 'النظام',
            'user' => 'مستخدم',
            'global' => 'شامل',
            'public' => 'عام',

        ],

        'heading' => 'ادارة التقارير',

        'table_heading' => 'التقارير',

        'no_views' => 'لا توجد تقارير',

        'subheadings' => [

            'user_favorites' => 'التقارير المفضلة',
            'user_views' => 'التقارير الخاصة بك',
            'preset_views' => 'تقارير النظام',
            'global_views' => 'تقارير شاملة',
            'public_views' => 'التقارير العامة',

        ],

    ],
];
