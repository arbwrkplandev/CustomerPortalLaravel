<?php

/**
 * WrkPlan Platform Configuration
 *
 * AUTH PROVIDER SWITCHING GUIDE:
 * ===============================
 * To switch from Laravel auth to .NET auth:
 * 1. Set AUTH_PROVIDER=dotnet in .env
 * 2. Set DOTNET_AUTH_API_BASE_URL to your .NET auth service URL
 * 3. Set DOTNET_AUTH_API_KEY to your API key
 * 4. No frontend changes required.
 * ===============================
 */
return [

    'app_mode' => env('WRKPLAN_APP_MODE', 'customer_portal'),

    'auth' => [
        // Current auth provider: 'laravel' or 'dotnet'
        'provider' => env('AUTH_PROVIDER', 'laravel'),

        // DANGER: Only enable plain_text_passwords in non-production migration scenarios.
        // This allows .NET migration tool to read passwords before hashing them.
        'plain_text_passwords' => env('AUTH_PLAIN_TEXT_PASSWORDS', false),

        // .NET Auth API Configuration (used only when provider = 'dotnet')
        'dotnet_api_base_url' => env('DOTNET_AUTH_API_BASE_URL', ''),
        'dotnet_api_key'      => env('DOTNET_AUTH_API_KEY', ''),
    ],

    'pagination' => [
        'per_page' => 15,
    ],

    'contract' => [
        'storage_disk' => 'local',
        'base_path'    => 'contracts',
    ],

    'invoice' => [
        'prefix'      => 'INV',
        'tax_rate'    => 0.00,
        'currency'    => 'USD',
    ],

    'ticket' => [
        'prefix'     => 'TKT',
        'auto_close_days' => 7,
    ],
];
