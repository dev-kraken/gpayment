<?php
/**
 * 3DS Payment Integration
 * Main entry point with routing
 *
 * @author DevKraken <soman@devkraken.com>
 * @copyright 2025 DevKraken
 * @license Proprietary
 */

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Router;
use App\Helpers\ConfigHelper;
use App\Helpers\ErrorHandler;
use App\Helpers\TemplateHelper;
use Dotenv\Dotenv;

// Start session
session_start();

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Register error handlers
$displayErrors = ConfigHelper::env('APP_DEBUG') === 'true';
ErrorHandler::register($displayErrors);

// Define required environment variables
$requiredEnvVars = [
    'ACTIVE_SERVER_URL',
    'MERCHANT_ID',
    'APP_URL', // Changed from BASE_URL to APP_URL
    'CERT_FILE',
    'KEY_FILE',
    'CA_FILE'
];

// Validate required environment variables
$missingVars = [];
foreach ($requiredEnvVars as $var) {
    if (empty($_ENV[$var])) {
        $missingVars[] = $var;
    }
}

// For backward compatibility, check if APP_URL needs to be set from BASE_URL
if (empty($_ENV['APP_URL']) && !empty($_ENV['BASE_URL'])) {
    $_ENV['APP_URL'] = $_ENV['BASE_URL'];
    putenv("APP_URL=" . $_ENV['BASE_URL']);
}

// Show configuration error if any required variables are missing
if (!empty($missingVars)) {
    http_response_code(500);
    // Use the configuration error template
    TemplateHelper::renderPartial('errors/configuration_error.php', [
        'missingVars' => $missingVars
    ]);
    exit;
}

// Initialize the router and process the request
$router = new Router();
$router->process(); 