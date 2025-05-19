<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ThreeDSException;
use App\Helpers\HttpHelper;
use App\Helpers\LogHelper;
use App\Helpers\TemplateHelper;
use App\Helpers\ConfigHelper;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use JsonException;

/**
 * Controller for handling 3DS notification events
 *
 * @author DevKraken <soman@devkraken.com>
 * @copyright 2025 DevKraken
 * @license Proprietary
 */
class NotificationController
{
    private array $config;

    /**
     * Constructor
     *
     * @param array $config Configuration array
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Set up logging based on configuration
     *
     * @param array $config The configuration array
     * @return void
     */
    private static function setupLogging(array $config): void
    {
        // Set log level if configured
        if (isset($config['3ds']['logging']['level'])) {
            LogHelper::setMinLogLevel($config['3ds']['logging']['level']);
        }
        
        // Set log directory if configured
        if (isset($config['3ds']['logging']['dir'])) {
            LogHelper::setLogDir($config['3ds']['logging']['dir']);
        }
        
        // Enable debug logging in development environment
        if (($config['env'] ?? '') === 'development') {
            LogHelper::setLogToStdout(true);
        }
    }

    /**
     * Factory method to create controller instance from configuration
     *
     * @return self
     */
    public static function createFromConfig(): self
    {
        // Load configuration using ConfigHelper
        $config = ConfigHelper::loadConfig();

        // Initialize logging
        self::setupLogging($config);

        return new self($config);
    }

    /**
     * Handle 3DS notification events
     *
     * @return void
     */
    public function handleNotification(): void
    {
        try {
            // Apply CORS headers for cross-domain notifications
            HttpHelper::setCorsHeaders();

            // Get request data
            $requestData = $_REQUEST;
            $rawPostData = file_get_contents('php://input');

            LogHelper::info("Notification received: " . json_encode($requestData));
            if ($rawPostData) {
                LogHelper::info("Raw notification data: " . $rawPostData);
            }

            // Validate that required parameters are present
            if (empty($requestData['event'])) {
                throw new ThreeDSException("Missing required event parameter", 400, null, null, [], 400);
            }

            // Extract important event data
            $event = $this->sanitizeParameter($this->getParameter($requestData, 'event'));
            $threeDSServerTransID = $this->sanitizeParameter($this->getParameter($requestData, 'threeDSServerTransID'));
            $requestorTransId = $this->sanitizeParameter($this->getParameter($requestData, 'requestorTransId'));
            $param = $this->getParameter($requestData, 'param'); // This contains the browser info

            // Validate transaction ID format if provided
            if ($threeDSServerTransID && !$this->validateTransactionId($threeDSServerTransID)) {
                LogHelper::warning("Invalid 3DS Server Transaction ID format", [
                    'threeDSServerTransID' => $threeDSServerTransID
                ]);
                // Don't stop execution, just log the warning
            }

            // Store browser info in session if received
            if ($param && ($event === '3DSMethodFinished' || $event === '3DSMethodSkipped')) {
                // Validate browser info format before storing
                if ($this->validateBrowserInfo($param)) {
                    $_SESSION['browserInfo'] = $param;
                    LogHelper::info("Browser info stored in session: " . $this->truncateForLog($param));
                } else {
                    LogHelper::warning("Invalid browser info format, not storing", [
                        'browserInfo' => $this->truncateForLog($param)
                    ]);
                }
            }

            // Return HTML response with JavaScript to communicate with parent window
            $this->renderNotificationPage($event, $param, $threeDSServerTransID, $requestorTransId);
        } catch (ThreeDSException $e) {
            LogHelper::error("Notification error: " . $e->getMessage(), $e->getContext());

            // Return a structured error page
            $this->renderErrorPage($e->getMessage(), $e->getHttpStatusCode());
        } catch (Exception $e) {
            LogHelper::error("Unexpected notification error: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Return a generic error page
            $this->renderErrorPage('An unexpected error occurred processing the notification');
        }
    }

    /**
     * Get a parameter from the request data with null fallback
     *
     * @param array $requestData The request data array
     * @param string $paramName Parameter name to retrieve
     * @return string|null Parameter value or null if not found
     */
    private function getParameter(array $requestData, string $paramName): ?string
    {
        return $requestData[$paramName] ?? null;
    }

    /**
     * Sanitize a parameter value
     *
     * @param string|null $value Value to sanitize
     * @return string|null Sanitized value or null
     */
    private function sanitizeParameter(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Remove any control characters and trim whitespace
        return trim(preg_replace('/[\x00-\x1F\x7F]/', '', $value));
    }

    /**
     * Validate a 3DS Server Transaction ID format
     *
     * @param string $transactionId Transaction ID to validate
     * @return bool True if valid format
     */
    private function validateTransactionId(string $transactionId): bool
    {
        // Transaction IDs are typically UUIDs or alphanumeric strings
        // Adjust pattern based on your specific requirements
        return (bool)preg_match('/^[a-zA-Z0-9\-_.]{8,64}$/', $transactionId);
    }

    /**
     * Validate browser info format
     *
     * @param string $browserInfo Browser info to validate
     * @return bool True if valid format
     */
    private function validateBrowserInfo(string $browserInfo): bool
    {
        // Browser info should be Base64 encoded
        $decoded = base64_decode($browserInfo, true);
        if ($decoded === false) {
            return false;
        }

        // Attempt to decode as JSON to validate format
        try {
            $data = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
            return is_array($data);
        } catch (JsonException $e) {
            LogHelper::error("Invalid browser info format" . $e->getMessage());
            return false;
        }
    }

    /**
     * Truncate a string for logging purposes
     *
     * @param string $value Value to truncate
     * @param int $maxLength Maximum length
     * @return string Truncated string
     */
    private function truncateForLog(string $value, int $maxLength = 50): string
    {
        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength) . '...';
    }

    /**
     * Render the notification template
     *
     * @param string|null $event Event type
     * @param string|null $param Event parameter (browser info)
     * @param string|null $threeDSServerTransID Server transaction ID
     * @param string|null $requestorTransId Requestor transaction ID
     * @return void
     */
    private function renderNotificationPage(
        ?string $event,
        ?string $param,
        ?string $threeDSServerTransID,
        ?string $requestorTransId
    ): void
    {
        // Render the template using the template helper
        TemplateHelper::renderPartial('notification.php', [
            'event' => $event,
            'param' => $param,
            'threeDSServerTransID' => $threeDSServerTransID,
            'requestorTransId' => $requestorTransId
        ]);
    }

    /**
     * Render error page
     *
     * @param string $errorMessage Error message to display
     * @param int $statusCode HTTP status code
     * @return void
     */
    #[NoReturn] private function renderErrorPage(string $errorMessage, int $statusCode = 500): void
    {
        http_response_code($statusCode);
        echo '<!DOCTYPE html>
              <html lang="en">
              <head>
                <title>Notification Error</title>
                <script type="text/javascript">
                    // Send error message to parent window
                    if (window.parent) {
                        window.parent.postMessage({
                            event: "Error",
                            message: ' . json_encode($errorMessage) . '
                        }, "*");
                    }
                </script>
              </head>
              <body>
                <h1>Notification Error</h1>
                <p>' . htmlspecialchars($errorMessage) . '</p>
              </body>
              </html>';
        exit;
    }
} 