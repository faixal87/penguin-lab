<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'guacamole' => [
        'base_url' => env('GUACAMOLE_BASE_URL', '#'),
        'mode' => env('GUACAMOLE_MODE', 'placeholder'),
        'db_host' => env('GUAC_DB_HOST', '192.168.68.99'),
        'db_port' => env('GUAC_DB_PORT', 3306),
        'db_database' => env('GUAC_DB_DATABASE', 'guacamole_db'),
        'db_username' => env('GUAC_DB_USERNAME', 'guacamole_user'),
        'db_password' => env('GUAC_DB_PASSWORD'),
        'default_password' => env('GUAC_DEFAULT_PASSWORD', '123456'),
        'user_default_password' => env('GUAC_USER_DEFAULT_PASSWORD', '123456'),
    ],

    'terminal' => [
        'automation_enabled' => env('TERMINAL_AUTOMATION_ENABLED', false),
        'server_host' => env('TERMINAL_SERVER_HOST', '192.168.68.99'),
        'server_user' => env('TERMINAL_SERVER_USER'),
        'server_ssh_key_path' => env('TERMINAL_SERVER_SSH_KEY_PATH'),
        'docker_image' => env('TERMINAL_DOCKER_IMAGE', 'ubuntu:22.04'),
        'container_prefix' => env('TERMINAL_CONTAINER_PREFIX', 'penguinlab_'),
        'memory_limit' => env('TERMINAL_MEMORY_LIMIT', '512m'),
        'cpu_limit' => env('TERMINAL_CPU_LIMIT', '0.5'),
    ],

];
