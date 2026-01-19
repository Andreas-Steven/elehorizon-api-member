<?php

/**
 * Yii2 Boilerplate Core Parameters
 *
 * This file contains the base configuration parameters for all Yii2 projects.
 * Do not modify this file directly. Instead, override values in params_app.php or params_local.php.
 *
 * Sections include:
 * - Application metadata (title, version, code)
 * - Default timezone and date formats
 * - Language settings
 * - JWT authentication configuration
 * - CORS policy
 * - Request configuration
 * - Development domain exceptions
 * - Pagination defaults
 * - HTTP verb rules for controllers
 * - Mailer defaults
 * - Standardized service metadata
 *
 * @package app\core
 * @version 1.0.0
 * @since 2025-11-04
 */

return [
    // Application title, version, and code
    'titleService' => 'Yii2 Boilerplate Core Service', // Human-readable service name
    'serviceVersion' => 'V1', // Service version
    'codeApp' => 'boilerplateCore', // Internal application code
    'migrateDb' => false,

    // Default timezone and timestamp formats
    'timestamp' => [
        'timeZone' => 'Asia/Jakarta',          // Default time zone
        'UTC' => 'Y-m-d\TH:i:s\Z',             // ISO 8601 UTC format
        'local' => 'Y-m-d H:i:s',              // Local format for DB or display
    ],

    // Default language and supported languages
    'language' => [
        'default' => 'en',                     // Default language
        'list' => ['en', 'id'],                // Supported language list
    ],

    // JWT configuration for API authentication
    'jwt' => [
        'key' => 'boilerplate-secret-key-256-bit', // Secret key
        'algorithm' => 'HS256',                     // Hashing algorithm
        'expire' => '+2 hours',                     // Token expiration time
        'issuer' => 'https://sso.example.com',     // JWT issuer
        'audience' => 'https://sso.example.com',   // JWT audience
        'id' => 'boilerplate-sso-core',            // JWT ID
        'request_time' => '+5 minutes',            // Allowed time offset for requests
        'except' => YII_ENV_DEV ? ['*'] : ['index'], // Actions excluded from JWT check
    ],

    // Default CORS policy configuration
    'cors' => [
        'allowCredentials' => true,                       // Allow credentials (cookies, headers)
        'requestMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'], // Allowed HTTP methods
        'allowHeaders' => ['Origin', 'Content-Type', 'Authorization', 'Accept-Language'], // Allowed headers
        'requestHeaders' => ['*'],                        // Allowed request headers
        'requestOrigin' => ['*'],                         // Allow requests from all origins
        'origins' => YII_ENV_DEV ? [
            'http://localhost',
            'http://example.com',
            'http://subdomain.example.com',
        ] : [
            'http://example.com',
            'http://subdomain.example.com',
        ],
    ],

    // Default request configuration
    'request' => [
        'extraCookies' => 'boilerplate-cookie-session',   // Extra cookie prefix
        'cookieValidationKey' => 'boilerplateCoreCookieKey123456', // Secret key for cookie validation
        'enableCookieValidation' => !YII_ENV_DEV,         // Enable validation in non-dev environments
        'enableCsrfValidation' => false,                  // CSRF disabled for API requests
    ],

    // Development domain exceptions (for local or staging environments)
    'developmentOnly' => [
        'http://localhost',
        'http://localhost:5173',
        'https://example.com',
        'https://subdomain.example.com',
    ],

    // Default pagination settings
    'pagination' => [
        'pageSize' => 10,         // Default items per page
        'sortDir' => SORT_DESC,   // Default sorting direction
    ],

    // Default HTTP verb rules for controllers
    'verbsAction' => [
        'index'  => ['get'],
        'data'   => ['post'],
        'list'   => ['post'],
        'create' => ['post'],
        'update' => ['put'],
        'delete' => ['delete'],
        'view'   => ['post'],
    ],

    // Default mailer configuration
    'mailer' => [
        'adminEmail' => 'admin@example.com',        // Admin email address
        'senderEmail' => 'noreply@example.com',    // Default sender email
        'senderName' => 'Example Mailer',          // Default sender name
    ],

    // Standardized service metadata
    'meta' => [
        'organization' => 'Example',               // Organization name
        'developer' => 'Example Dev Team',         // Developer or team responsible
        'contact' => 'developer@example.com',      // Developer contact
        'support' => 'support@example.com',        // Support contact
    ],
];
