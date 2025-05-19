<?php
/**
 * Application configuration file
 */

return [
    'name' => $_ENV['APP_NAME'] ?? 'GPayments 3DS Integration',
    'debug' => ($_ENV['APP_DEBUG'] ?? '') === 'true',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'url' => $_ENV['APP_URL'] ?? $_ENV['BASE_URL'] ?? 'http://localhost:8000',
    
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    'locale' => $_ENV['APP_LOCALE'] ?? 'en',
    
    'session' => [
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 120), // minutes
        'secure' => ($_ENV['SESSION_SECURE'] ?? '') === 'true',
        'same_site' => $_ENV['SESSION_SAME_SITE'] ?? 'lax',
    ],
    
    'error_handling' => [
        'display_errors' => ($_ENV['APP_DEBUG'] ?? '') === 'true',
        'log_errors' => true,
        'error_reporting' => E_ALL,
    ],
    
    'cors' => [
        'allowed_origins' => explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*'),
        'allowed_methods' => explode(',', $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,OPTIONS'),
        'allowed_headers' => explode(',', $_ENV['CORS_ALLOWED_HEADERS'] ?? 'Content-Type,Authorization'),
    ],
]; 