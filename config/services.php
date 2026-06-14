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

    'ai' => [
        'provider' => env('AI_PROVIDER', env('GROQ_API_KEY') ? 'groq' : 'opencode'),
        'api_key' => env('AI_API_KEY', env('GROQ_API_KEY')),
        'base_url' => env('AI_BASE_URL', env('GROQ_BASE_URL', 'https://opencode.ai/zen/v1')),
        'model' => env('AI_MODEL', env('GROQ_MODEL')),
        'connect_timeout' => env('AI_CONNECT_TIMEOUT', env('GROQ_CONNECT_TIMEOUT', 10)),
        'timeout' => env('AI_TIMEOUT', env('GROQ_TIMEOUT', 120)),
        'max_tokens' => env('AI_MAX_TOKENS', env('GROQ_MAX_TOKENS', 8192)),
        'document_character_limit' => env('AI_DOCUMENT_CHARACTER_LIMIT', env('GROQ_DOCUMENT_CHARACTER_LIMIT', 60000)),
        'reviewer_comment_character_limit' => env('AI_REVIEWER_COMMENT_CHARACTER_LIMIT', env('GROQ_REVIEWER_COMMENT_CHARACTER_LIMIT', 8000)),
        'author_response_comment_character_limit' => env('AI_AUTHOR_RESPONSE_COMMENT_CHARACTER_LIMIT', env('GROQ_AUTHOR_RESPONSE_COMMENT_CHARACTER_LIMIT', 4000)),
        'author_response_revision_character_limit' => env('AI_AUTHOR_RESPONSE_REVISION_CHARACTER_LIMIT', env('GROQ_AUTHOR_RESPONSE_REVISION_CHARACTER_LIMIT', 4000)),
    ],

];
