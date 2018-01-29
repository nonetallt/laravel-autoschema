<?php

return [ 

    /* Where to output the resulting file */
    'output_path' => base_path('schema.md'),

    /* Which folder to look for models extending Illuminate\Database\Eloquent\Model */
    'model_directory' => app_path(),

    /* Which namespace to use when looking for model properties, should match
     * with psr-4 loading in laravel applications 
     * */
    'model_namespace' => 'App',

    /* What to output in the table when property is true, might need to be
     * reconfigured if special characters show incorrectly for the dev machine 
     * */
    'yes_string' => '&#10003;',

    /* What to output in the table when property is false */
    'no_string' => 'no',

    /* Print table name after model name */
    'print_table_name' => true
];
