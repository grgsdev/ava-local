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
        'Master',
        'System Engineering',
        'System Developer',
        'Admin',
        'Panel User',
        'Sponser',
    ],

    'notSelect' => [
        // 'Master',
        'System Engineering',
        'System Developer',
        'Panel User',
        'Sponser',

    ],

    'notAdmin' => [
        //        'Master',
        'System Engineering',
        'System Developer',
        'Panel User',
        'Sponser',
    ],
];
