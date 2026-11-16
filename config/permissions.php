<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Protected Roles
    |--------------------------------------------------------------------------
    |
    | Defina aqui os nomes dos papéis que são considerados essenciais para o
    | sistema. Esses papéis não serão exibidos na listagem principal
    | e não poderão ser editados ou excluídos através da interface.
    |
    */
    'protected' => [
        'create permissions',
        'update permissions',
        'delete permissions',
        'delete users logs',
        'view generator module', // Permissão geral para ver o módulo de engenharia
        'view generator',
        'create generator',
        'update generator',
        'delete generator',
        'run generator',
        'view system_module',
        'access system panel',

        'view system_developers module',
        'view system_developers',
        'create system_developers',
        'update system_developers',
        'delete system_developers',


        'force delete users',


    ],

    'notSelect' => [
        'create permissions',
        'update permissions',
        'delete permissions',
        'delete users logs',
        'view generator module', // Permissão geral para ver o módulo de engenharia
        'view generator',
        'create generator',
        'update generator',
        'delete generator',
        'run generator',
        'view system_module',
        'access system panel',

        'view system_developers module',
        'view system_developers',
        'create system_developers',
        'update system_developers',
        'delete system_developers',
        'view permissions module',
        'force delete users',

    ],
];
