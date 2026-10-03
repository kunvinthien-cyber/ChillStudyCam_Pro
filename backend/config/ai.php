<?php

return [
    'ca_bundle' => env('AI_CA_BUNDLE', ini_get('curl.cainfo') ?: null),

    /*
    |--------------------------------------------------------------------------
    | Default AI Model
    |--------------------------------------------------------------------------
    */
    'default' => 'gemini',

    /*
    |--------------------------------------------------------------------------
    | Supported AI Models
    |--------------------------------------------------------------------------
    */
    'supported_models' => [
        'gemini' => [
            'name' => 'Gemini Flash (Latest)',
            'description' => 'Google Gemini AI model for natural language processing.',
            'api_key' => env('GEMINI_API_KEY'),
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent',
        ],
        // អ្នកអាចបន្ថែម Claude ឬ OpenAI នៅទីនេះពេលក្រោយបាន
    ],
];
