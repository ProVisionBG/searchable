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
     * Table name
     */
    'table_name' => 'searchable_index',

    /**
     * Command prefix
     */
    'command_prefix' => 'searchable',

    /*
     *
     */
    'weight' => [
        'title' => 1.5,
        'content' => 1,
    ],
];
