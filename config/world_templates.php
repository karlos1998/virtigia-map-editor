<?php

return [
    'default' => env('WORLD_TEMPLATE_DEFAULT', 'retro'),
    'default_remote_database_server' => env('WORLD_TEMPLATE_DEFAULT_REMOTE_DATABASE_SERVER', 'main1'),
    'database_prefix' => env('WORLD_TEMPLATE_DATABASE_PREFIX', 'template_'),

    'remote_database_servers' => [
        'main1' => [
            'label' => env('REMOTE_DB_LABEL', 'Main 1'),
            'driver' => env('REMOTE_DB_CONNECTION', 'mysql'),
            'host' => env('REMOTE_DB_HOST', '46.105.131.250'),
            'port' => env('REMOTE_DB_PORT', '3436'),
            'username' => env('REMOTE_DB_USERNAME', ''),
            'password' => env('REMOTE_DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
    ],

    'legacy_templates' => [
        'retro' => [
            'name' => 'Retro',
            'connection_name' => 'retro',
            'remote_database_server' => 'main1',
            'database_name' => env('RETRO_DB_DATABASE', 'retro_database'),
            'is_active' => true,
            'is_visible' => true,
        ],
        'classic' => [
            'name' => 'Classic',
            'connection_name' => 'classic',
            'remote_database_server' => 'main1',
            'database_name' => env('CLASSIC_DB_DATABASE', 'classic_database'),
            'is_active' => true,
            'is_visible' => true,
        ],
        'legacy' => [
            'name' => 'Legacy',
            'connection_name' => 'legacy',
            'remote_database_server' => 'main1',
            'database_name' => env('LEGACY_DB_DATABASE', 'legacy_database'),
            'is_active' => true,
            'is_visible' => true,
        ],
        'demo' => [
            'name' => 'Demo',
            'connection_name' => 'demo',
            'remote_database_server' => 'main1',
            'database_name' => env('DEMO_DB_DATABASE', 'demo_database'),
            'is_active' => true,
            'is_visible' => true,
        ],
        'test' => [
            'name' => 'Test',
            'connection_name' => 'test',
            'remote_database_server' => 'main1',
            'database_name' => env('TEST_DB_DATABASE', 'test_database'),
            'is_active' => true,
            'is_visible' => false,
        ],
    ],
];
