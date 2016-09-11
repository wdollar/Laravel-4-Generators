<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Generators Templates Directory Location
    |--------------------------------------------------------------------------
    |
    | copy the generators templates under app_path()/templates
    | and modify your local copy. Any templates not found in the
    | specified path below will be searched in the package's original
    | template directory.
    |
    */

    'templates' => array(__DIR__ . '/templates'),

    /**
     * Specify export formatting options for translation files:
     *
     * PRESERVE_EMPTY_ARRAYS - preserve first level translations that are empty arrays
     * USE_QUOTES - use " instead of ' for wrapping strings
     * USE_HEREDOC - use <<<'TEXT' for wrapping string that contain \n
     * USE_SHORT_ARRAY - use [] instead of array() for arrays
     * SORT_KEYS - alphabetically sort keys withing an array
     *
     * @type string | array
     */
    'export_format' => array(
        'PRESERVE_EMPTY_ARRAYS',
        //'USE_QUOTES',
        'USE_HEREDOC',
        'USE_SHORT_ARRAY',
        'SORT_KEYS',
    ),

    /**
     *--------------------------------------------------------------------------
     * Path mappings for various types of source files
     *--------------------------------------------------------------------------
     *
     * These are Laravel version dependent
     *
     * when --bench=vendor/package-name is given then the following in the path mappings will be
     * expanded as follows:
     * {{Vendor/Package}} to Vendor/PackageName
     * {{vendor/package}} to vendor/package-name
     *
     */
    // laravel5.3
    'dir_map' => [
        'code' => [
            'app' => 'app/',
            'bench' => 'src/',
        ],
        'commands' => [
            'app' => 'app/Console/Commands/',
            'bench' => 'src/Commands/',
        ],
        'config' => [
            'app' => 'config/',
            'bench' => 'config/',
        ],
        'controllers' => [
            'app' => 'app/Http/Controllers/',
            'bench' => 'src/Controllers',
        ],
        'lang' => [
            'app' => 'resources/lang/',
            'bench' => 'resources/lang/',
        ],
        'migrations' => [
            'app' => 'database/migrations/',
            'bench' => 'database/migrations/',
        ],
        'models' => [
            'app' => 'app/',
            'bench' => 'src/Models/',
        ],
        'public' => [
            'app' => 'public/',
            'bench' => 'public/',
        ],
        'routes' => [
            'app' => 'routes/web.php',
            'bench' => 'src/',
        ],
        'seeds' => [
            'app' => 'database/seeds/',
            'bench' => 'database/seeds/',
        ],
        'templates' => [
            'app' => 'config/templates/',
            'bench' => 'config/templates/',
        ],
        'tests' => [
            'app' => 'tests/',
            'bench' => 'tests/',
        ],
        'views' => [
            'app' => 'resources/views/',
            'bench' => 'resources/views/',
        ],
    ],

    // laravel4
    //'dir_map' => [
    //    'code' => [
    //        'app' => 'app/',
    //        'bench' => 'src/{{Vendor/Package}}/',
    //    ],
    //    'commands' => [
    //        'app' => 'app/commands/',
    //        'bench' => 'src/{{Vendor/Package}}/Commands/',
    //    ],
    //    'config' => [
    //        'app' => 'app/config/',
    //        'bench' => 'src/config/',
    //    ],
    //    'controllers' => [
    //        'app' => 'app/controllers/',
    //        'bench' => 'src/{{Vendor/Package}}/controllers/',
    //    ],
    //    'lang' => [
    //        'app' => 'app/lang/',
    //        'bench' => 'src/lang/',
    //    ],
    //    'migrations' => [
    //        'app' => 'app/database/migrations/',
    //        'bench' => 'src/migrations/',
    //    ],
    //    'models' => [
    //        'app' => 'app/models/',
    //        'bench' => 'src/{{Vendor/Package}}/Models/',
    //    ],
    //    'public' => [
    //        'app' => 'public/',
    //        'bench' => 'src/{{Vendor/Package}}/',
    //    ],
    //    'seeds' => [
    //        'app' => 'app/database/seeds/',
    //        'bench' => 'src/seeds/',
    //    ],
    //    'routes' => [
    //        'app' => 'app/routes.php',
    //        'bench' => 'src/{{Vendor/Package}}/',
    //    ],
    //    'tests' => [
    //        'app' => 'app/tests/',
    //        'bench' => 'tests/',
    //    ],
    //    'templates' => [
    //        'app' => 'app/config/packages/vsch/generators/templates/',
    //        'bench' => 'src/config/templates/',
    //    ],
    //    'views' => [
    //        'app' => 'app/views/',
    //        'bench' => 'src/views/',
    //    ],
    //],
);
