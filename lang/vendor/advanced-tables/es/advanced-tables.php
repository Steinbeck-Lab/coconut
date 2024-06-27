<?php

return [

    'forms' => [

        'heading' => 'Nuevo vista',
        'name' => 'Nombre',
        'user' => 'Usuario',
        'resource' => 'Recurso',
        'note' => 'Nota',

        'status' => [

            'label' => 'Estado',

        ],

        'name' => [

            'label' => 'Nombre',
            'helper_text' => 'Escoja un nombre corto, pero fácilmente identificable para esta vista',

        ],

        'filters' => [

            'label' => 'Resumen de vista',
            'helper_text' => 'Estas configuraciones se incluirán con la vista',

        ],

        'panels' => [

            'label' => 'Paneles',

        ],

        'preset_view' => [

            'label' => 'Vista predefinida',
            'query_label' => 'Consulta de vista predefinida',
            'helper_text_start' => 'Está usando la vista predefinida ',
            'helper_text_end' => ' como base para esta vista. Las vistas predefinidas tienen su propias configuraciones independientes además de las configuraciones que usted ha seleccionado.',

        ],

        'icon' => [

            'label' => 'Ícono',
            'placeholder' => 'Seleccione un ícono',

        ],

        'color' => [

            'label' => 'Color',

        ],

        'public' => [

            'label' => 'Hacer público',
            'toggle_label' => 'Es público',
            'helper_text' => 'Hacer visible esta vista a todos los usuarios',

        ],

        'favorite' => [

            'label' => 'Agregar a favoritos',
            'toggle_label' => 'Es mi favorito',
            'helper_text' => 'Agregar esta vista a sus favoritos',

        ],

        'global_favorite' => [

            'label' => 'Hacer favorito global',
            'toggle_label' => 'Es favorito global',
            'helper_text' => 'Agregar esta vista a los favoritos de todos los usuarios',

        ],

    ],

    'notifications' => [

        'preset_views' => [

            'title' => 'No se pudo crear la vista',
            'body' => 'Las vistas no se pueden crear a partir de una vista predefinida. Cree su vista utilizando la vista de "Por defecto" o cualquier otro vista creada por un usuario.',

        ],

        'save_view' => [

            'saved' => [

                'title' => 'Guardado',

            ],

        ],

        'edit_view' => [

            'saved' => [

                'title' => 'Guardado',

            ],

        ],

        'replace_view' => [

            'replaced' => [

                'title' => 'Reemplazado',

            ],

        ],

    ],

    'quick_save' => [

        'save' => [

            'modal_heading' => 'Guardar vista',
            'submit_label' => 'Guardar vista',

        ],

    ],

    'select' => [

        'label' => 'Vistas',
        'placeholder' => 'Seleccionar vista',

    ],

    'status' => [

        'approved' => 'aprobado',
        'pending' => 'pendiente',
        'rejected' => 'rechazado',

    ],

    'tables' => [

        'favorites' => [

            'default' => 'Por defecto',

        ],

        'columns' => [

            'user' => 'Usuario',
            'icon' => 'Ícono',
            'color' => 'Color',
            'name' => 'Nombre de vista',
            'panel' => 'Panel',
            'resource' => 'Recurso',
            'status' => 'Estado',
            'filters' => 'Filtros',
            'is_public' => 'Pública',
            'is_user_favorite' => 'Mi favorito',
            'is_global_favorite' => 'Global',
            'sort_order' => 'Orden',
            'user_favorite_sort_order' => 'Orden de favoritos',

        ],

        'tooltips' => [

            'is_user_favorite' => [

                'unfavorite' => 'Remover favorito',
                'favorite' => 'Hacer favorito',

            ],

            'is_public' => [

                'make_private' => 'Hacer privado',
                'make_public' => 'Hacer público',

            ],

            'is_global_favorite' => [

                'make_personal' => 'Hacer personal',
                'make_global' => 'Hacer global',

            ],

        ],

        'actions' => [

            'buttons' => [

                'open' => 'Abrir',
                'approve' => 'Aprobar',

            ],

        ],

    ],

    'toggled_columns' => [

        'visible' => 'Visible',
        'hidden' => 'Escondida',

    ],

    'user_view_resource' => [

        'model_label' => 'Vista',
        'plural_model_label' => 'Vistas',
        'navigation_label' => 'Vistas',

    ],

    'view_manager' => [

        'actions' => [

            'add_view_to_favorites' => 'Agregar a favoritos',
            'apply_view' => 'Aplicar vista',
            'save' => 'Guardar',
            'save_view' => 'Guardar vista',
            'delete_view' => 'Eliminar vista',
            'delete_view_modal_submit_label' => 'Eliminar',
            'delete_view_description' => 'Esta vista es de tipo :type. Otros usuarios perderán acceso a su vista. ¿Está seguro que quiere proceeder?',
            'remove_view_from_favorites' => 'Quitar de favoritos',
            'edit_view' => 'Editar vista',
            'replace_view' => 'Reemplazar vista',
            'replace_view_modal_description' => 'Esta a punto de reemplazar esta vista guardada con la configuración actual de la tabla. ¿Está segura que quiere proceeder?',
            'replace_view_modal_submit_label' => 'Reemplazar',
            'show_view_manager' => 'Mostrar administrador de vistas',

        ],

        'badges' => [

            'active' => 'activa',
            'preset' => 'predefinida',
            'user' => 'usuario',
            'global' => 'global',
            'public' => 'pública',

        ],

        'heading' => 'Administrador de vistas',

        'table_heading' => 'Vistas',

        'no_views' => 'No hay vistas',

        'subheadings' => [

            'user_favorites' => 'Vistas favoritas',
            'user_views' => 'Vistas de usuario',
            'preset_views' => 'Vistas predefinidas',
            'global_views' => 'Vistas globales',
            'public_views' => 'Vistas públicas',

        ],

    ],
];
