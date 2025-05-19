<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Exceptions\ThreeDSException;
use ErrorException;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Throwable;

/**
 * Global Error Handler to manage errors and exceptions
 *
 * @author DevKraken <soman@devkraken.com>
 * @copyright 2025 DevKraken
 * @license Proprietary
 */
class ErrorHandler
{
    /**
     * Register all error handlers
     *
     * @param bool $displayErrors Whether to display errors
     * @return void
     */
    public static function register(bool $displayErrors = false): void
    {
        // Set error reporting level
        error_reporting(E_ALL);
        ini_set('display_errors', $displayErrors ? '1' : '0');
        
        // Register exception handler
        set_exception_handler([self::class, 'handleException']);
        
        // Register error handler
        set_error_handler([self::class, 'handleError']);
        
        // Register shutdown function to catch fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    /**
     * Handle uncaught exceptions
     *
     * @param Throwable $exception The uncaught exception
     * @return void
     */
    #[NoReturn] public static function handleException(Throwable $exception): void
    {
        // Log the exception
        if ($exception instanceof ThreeDSException) {
            LogHelper::error("3DS Exception: " . $exception->getMessage(), $exception->getContext());
        } else {
            LogHelper::error("Uncaught Exception: " . $exception->getMessage(), [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]);
        }
        
        // Check if this is an API request
        $isApi = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api') === 0);
        
        if ($isApi) {
            // For API requests, return a JSON error response
            HttpHelper::sendJsonError(
                'An unexpected error occurred',
                $exception instanceof ThreeDSException ? $exception->getHttpStatusCode() : 500
            );
        } else {
            // For web requests, render an error page
            try {
                http_response_code(500);
                $debug = isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true';
                
                TemplateHelper::renderPartial('errors/server_error.php', [
                    'errorMessage' => $exception->getMessage(),
                    'stackTrace' => $exception->getTraceAsString(),
                    'debug' => $debug
                ]);
            } catch (Exception $e) {
                // Fallback if template rendering fails
                echo "Server Error: " . ($debug ? $exception->getMessage() : "An unexpected error occurred");
            }
        }
        
        exit(1);
    }
    
    /**
     * Handle PHP errors
     *
     * @param int $level Error level
     * @param string $message Error message
     * @param string $file File where the error occurred
     * @param int $line Line number where the error occurred
     * @return bool Whether the error was handled
     * @throws ErrorException
     */
    public static function handleError(int $level, string $message, string $file, int $line): bool
    {
        // Convert errors to ErrorException
        throw new ErrorException($message, 0, $level, $file, $line);
    }
    
    /**
     * Handle shutdown and catch fatal errors
     *
     * @return void
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            // Log fatal error
            LogHelper::critical("Fatal Error: " . $error['message'], [
                'file' => $error['file'],
                'line' => $error['line']
            ]);
            
            // Check if this is an API request
            $isApi = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api') === 0);
            
            if ($isApi) {
                // For API requests, return a JSON error response
                HttpHelper::sendJsonError('A fatal error occurred', 500);
            } else {
                // For web requests, render an error page
                try {
                    http_response_code(500);
                    $debug = isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true';
                    
                    TemplateHelper::renderPartial('errors/server_error.php', [
                        'errorMessage' => $error['message'],
                        'stackTrace' => '',
                        'debug' => $debug
                    ]);
                } catch (Exception $e) {
                    // Fallback if template rendering fails
                    echo "Fatal Error: " . ($debug ? $error['message'] : "A fatal error occurred");
                }
            }
        }
    }
} 