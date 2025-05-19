<?php
/**
 * 3DS configuration file
 * @package 3DS Integration
 * @version 1.0
 * @author DevKraken
 * @email soman@devkraken.com
 */

return [
    'server' => [
        'url' => $_ENV['ACTIVE_SERVER_URL'] ?? 'https://dotoperatingauthority-test.api.as1.gpayments.net',
    ],
    'merchant' => [
        'id' => $_ENV['MERCHANT_ID'] ?? '123456789012345',
        'name' => $_ENV['MERCHANT_NAME'] ?? '3DS Test Merchant',
    ],
    'test_card' => [
        'number' => $_ENV['TEST_CARD_NUMBER'] ?? '4100000000000100',
        'expiry' => $_ENV['TEST_CARD_EXPIRY'] ?? '2508', // format: YYMM
    ],
    'transaction' => [
        'currency' => $_ENV['DEFAULT_CURRENCY'] ?? '840', // USD: 840
    ],
    'ssl' => [
        'cert_file' => $_ENV['CERT_FILE'] ?? __DIR__ . '/../certs/cert.pem',
        'key_file' => $_ENV['KEY_FILE'] ?? __DIR__ . '/../certs/key.pem',
        'ca_file' => $_ENV['CA_FILE'] ?? __DIR__ . '/../certs/ca.pem',
    ],
    'api' => [
        'init_endpoint' => '/api/v2/auth/brw/init',
        'auth_result_endpoint' => '/api/v2/auth/brw/result',
        'challenge_status_endpoint' => '/api/v2/auth/challenge/status',
    ],
    'logging' => [
        'enabled' => ($_ENV['DEBUG_MODE'] ?? '') === 'true',
        'file' => $_ENV['LOG_FILE'] ?? __DIR__ . '/../logs/3ds.log',
    ],
    'notification_url' => $_ENV['NOTIFICATION_URL'] ?? ($_ENV['APP_URL'] ?? '') . '/notify',
    'base_url' => $_ENV['APP_URL'] ?? $_ENV['BASE_URL'] ?? '',
]; 