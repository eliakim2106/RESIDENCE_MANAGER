<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // Paiement en ligne (Mobile Money, carte). Désactivé tant que la clé API et l'identifiant du site sont vides.
    'cinetpay' => [
        'api_key' => env('CINETPAY_API_KEY'),
        'site_id' => env('CINETPAY_SITE_ID'),
        'secret_key' => env('CINETPAY_SECRET_KEY'),
        'base_url' => env('CINETPAY_BASE_URL', 'https://api-checkout.cinetpay.com/v2'),
        'channels' => env('CINETPAY_CHANNELS', 'ALL'),
    ],

    // Agrégateur de test (sandbox ouverte avec une adresse e-mail). environment : sandbox ou live.
    'fedapay' => [
        'secret_key' => env('FEDAPAY_SECRET_KEY'),
        'environment' => env('FEDAPAY_ENVIRONMENT', 'sandbox'),
    ],

    // Connexion des clients avec Google / Facebook (désactivée tant que l'identifiant et le secret sont vides).
    // Adresse de retour à déclarer chez le fournisseur : APP_URL/connexion/google/retour (ou /facebook/retour).
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/connexion/google/retour'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', '/connexion/facebook/retour'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
