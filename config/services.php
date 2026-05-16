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

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'eur'),
        'payment_link_ttl_hours' => (int) env('STRIPE_PAYMENT_LINK_TTL_HOURS', 72),
    ],

    'ai_call_agent' => [
        'enabled' => env('AI_CALL_AGENT_ENABLED', false),
        'mode' => env('AI_CALL_AGENT_MODE', 'disabled'), // disabled | shadow | live
        'default_callback_hour' => (int) env('AI_CALL_AGENT_DEFAULT_CALLBACK_HOUR', 10),
        'default_callback_minute' => (int) env('AI_CALL_AGENT_DEFAULT_CALLBACK_MINUTE', 0),
    ],

    'telnyx' => [
        'api_key' => env('TELNYX_API_KEY'),
        'api_base' => env('TELNYX_API_BASE', 'https://api.telnyx.com/v2'),
        'from_number' => env('TELNYX_FROM_NUMBER'),
        'webhook_url' => env('TELNYX_WEBHOOK_URL'),
        'connection_id' => env('TELNYX_CONNECTION_ID'),
        'public_key' => env('TELNYX_PUBLIC_KEY'),
        'webhook_tolerance' => (int) env('TELNYX_WEBHOOK_TOLERANCE', 300),
        'voice_bridge_ws_url' => env('TELNYX_VOICE_BRIDGE_WS_URL'),
        'tts_voice' => env('TELNYX_TTS_VOICE', 'Telnyx.KokoroTTS.af'),
        'tts_language' => env('TELNYX_TTS_LANGUAGE', 'it-IT'),
    ],

    'whatsapp' => [
        'url'     => env('WHATSAPP_API_URL'),
        'api_key' => env('WHATSAPP_API_KEY'),
        'prefix'  => env('WHATSAPP_DEFAULT_PREFIX', '39'),
    ],

    'deepseek' => [
        'enabled'  => env('DEEPSEEK_ENABLED', false),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'api_key'  => env('DEEPSEEK_API_KEY', ''),
        'model'    => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'timeout'  => env('DEEPSEEK_TIMEOUT', 30),
    ],

    'ai_gateway' => [
        'key' => env('AI_GATEWAY_KEY'),
    ],

    /*'openclaw' => [
        'enabled'        => env('OPENCLAW_ENABLED', false),
        'base_url'       => env('OPENCLAW_BASE_URL', ''),
        'endpoint'       => env('OPENCLAW_ENDPOINT', '/v1/chat/completions'),
        'basic_username' => env('OPENCLAW_BASIC_USERNAME', ''),
        'basic_password' => env('OPENCLAW_BASIC_PASSWORD', ''),
        'gateway_token'  => env('OPENCLAW_GATEWAY_TOKEN', ''),
        'model'          => env('OPENCLAW_MODEL', 'deepseek/deepseek-reasoner'),
        'timeout'        => env('OPENCLAW_TIMEOUT', 20),
        'verify_ssl'     => env('OPENCLAW_VERIFY_SSL', true),
    ],*/
    'openclaw' => [
        'enabled'        => env('OPENCLAW_ENABLED', false),
        'base_url'       => env('OPENCLAW_BASE_URL', ''),
        'endpoint'       => env('OPENCLAW_ENDPOINT', '/v1/chat/completions'),
        'basic_username' => env('OPENCLAW_BASIC_USERNAME', ''),
        'basic_password' => env('OPENCLAW_BASIC_PASSWORD', ''),
        'gateway_token'  => env('OPENCLAW_GATEWAY_TOKEN', ''),
        'model'          => env('OPENCLAW_MODEL', 'deepseek/deepseek-reasoner'),
        'timeout'        => env('OPENCLAW_TIMEOUT', 20),
        'verify_ssl'     => env('OPENCLAW_VERIFY_SSL', true),
        'agent_id' => env('OPENCLAW_AGENT_ID', ''),
    ],


    'openclaw_whatsapp' => [
        'url'      => env('OPENCLAW_WA_URL', 'https://wa.r4software.it/send'),
        'api_key'  => env('OPENCLAW_WA_API_KEY'),
        'username' => env('OPENCLAW_WA_USERNAME'),
        'password' => env('OPENCLAW_WA_PASSWORD'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
