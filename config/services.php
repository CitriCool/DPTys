<?php
$schema='DPTYS_';
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

    'mailgun' => [
        'domain' => env($schema.'MAILGUN_DOMAIN'),
        'secret' => env($schema.'MAILGUN_SECRET'),
        'endpoint' => env($schema.'MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env($schema.'POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env($schema.'AWS_ACCESS_KEY_ID'),
        'secret' => env($schema.'AWS_SECRET_ACCESS_KEY'),
        'region' => env($schema.'AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
