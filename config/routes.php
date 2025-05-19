<?php
/**
 * Routes Configuration
 * 
 * This file defines all application routes. It is loaded by the Router.
 *
 * @author DevKraken <soman@devkraken.com>
 * @copyright 2025 DevKraken
 * @license Proprietary
 */

return [
    // API routes
    'api' => [
        'controller' => '\App\Controllers\ApiController',
        'action' => 'handleRequest'
    ],
    
    // Notification route
    'notify' => [
        'controller' => '\App\Controllers\NotificationController',
        'action' => 'handleNotification'
    ],
    
    // Documentation page
    'docs' => [
        'template' => 'pages/documentation.php',
        'data' => function() {
            $appConfig = require __DIR__ . '/app.php';
            $threeDsConfig = require __DIR__ . '/3ds.php';
            return array_merge($appConfig, ['3ds' => $threeDsConfig]);
        }
    ],
    
    // Home page (default route)
    '' => [
        'template' => 'pages/payment.php',
        'data' => function() {
            $appConfig = require __DIR__ . '/app.php';
            $threeDsConfig = require __DIR__ . '/3ds.php';
            return array_merge($appConfig, ['3ds' => $threeDsConfig]);
        }
    ],
    
    // Alias for home page
    'index.php' => [
        'alias' => ''
    ]
]; 