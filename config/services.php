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

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model' => env('GROQ_MODEL', 'llama-3.1-8b-instant'),
        'connect_timeout' => env('GROQ_CONNECT_TIMEOUT', 10),
        'timeout' => env('GROQ_TIMEOUT', 60),
        'max_tokens' => env('GROQ_MAX_TOKENS', 2048),
        'document_character_limit' => env('GROQ_DOCUMENT_CHARACTER_LIMIT', 12000),
        'reviewer_comment_character_limit' => env('GROQ_REVIEWER_COMMENT_CHARACTER_LIMIT', 8000),
        'author_response_comment_character_limit' => env('GROQ_AUTHOR_RESPONSE_COMMENT_CHARACTER_LIMIT', 4000),
        'author_response_revision_character_limit' => env('GROQ_AUTHOR_RESPONSE_REVISION_CHARACTER_LIMIT', 4000),
    ],

];
