<?php
/**
 * Copyright (c) ProVision Media Group Ltd. <https://provision.bg>. All Rights Reserved, 2019
 * Written by Venelin Iliev <venelin@provision.bg>
 */

return [
    /*
     * The database connection to be used
     * Defaults to the default database connection
     */
    'db_connection' => null,

    /*
     * Database table name
     */
    'table_name' => 'searchable_index',

    /**
     * Artisan commands prefix
     */
    'command_prefix' => 'searchable',

    /*
     * Weight on fields for search/indexing
     */
    'weight' => [
        'title' => 1.5,
        'content' => 1,
    ],

    /*
     * Keyword cleaners
     * Mode => [ 'cleaners to apply in specific mode' ]
     */
    'cleaners' => [
        \ProVision\Laravel\Searchable\SearchableModes::Boolean => [
            \ProVision\Laravel\Searchable\Cleaners\EmailCleaner::class
        ]
    ]
];
