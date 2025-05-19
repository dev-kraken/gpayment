<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ThreeDSException;
use App\Helpers\HttpHelper;
use App\Helpers\LogHelper;
use App\Helpers\SecurityHelper;
use App\Helpers\CacheHelper;
use App\Helpers\ConfigHelper;
use App\Services\ThreeDSService;
use Exception;
use JsonException;

/**
 * Controller for handling API requests
 *
 * @author DevKraken <soman@devkraken.com>
 * @copyright 2025 DevKraken
 * @license Proprietary
 */
class ApiController
{
    private ThreeDSService $threeDSService;
    private array $config;

    /**
     * Constructor
     *
     * @param ThreeDSService $threeDSService
     * @param array $config
     */
    public function __construct(ThreeDSService $threeDSService, array $config)
    {
        $this->threeDSService = $threeDSService;
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
     * @throws ThreeDSException
     */
    public static function createFromConfig(): self
    {
        // Load configuration using ConfigHelper
        $config = ConfigHelper::loadConfig();

        // Initialize logging
        self::setupLogging($config);

        // Initialize threeDSService
        $threeDSService = new ThreeDSService($config['3ds']);

        return new self($threeDSService, $config);
    }

    /**
     * Handle API requests
     *
     * @return void
     * @throws JsonException
     */
    public function handleRequest(): void
    {
        try {
            LogHelper::info("API request received", ['method' => $_SERVER['REQUEST_METHOD']]);
            
            // Set CORS headers
            HttpHelper::setCorsHeaders();

            // Handle preflight OPTIONS request
            HttpHelper::handlePreflight();

            // Apply rate limiting based on IP address
            $clientIp = HttpHelper::getClientIp();
            if (!HttpHelper::checkRateLimit($clientIp, 60, 60)) {
                LogHelper::warning("Rate limit exceeded", ['ip' => $clientIp]);
                HttpHelper::sendRateLimitedResponse(60);
            }

            // Ensure request is POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                LogHelper::warning("Method not allowed", ['method' => $_SERVER['REQUEST_METHOD']]);
                HttpHelper::sendJsonError('Method not allowed', 405);
            }

            // Get POST data
            $requestData = HttpHelper::getJsonPostData();
            if (!$requestData) {
                LogHelper::warning("Invalid JSON data received");
                HttpHelper::sendJsonError('Invalid JSON data', 400);
            }
            
            // Sanitize input data to prevent XSS
            $requestData = SecurityHelper::sanitizeArray($requestData);

            // Get the action
            $action = $requestData['action'] ?? null;
            if (!$action) {
                LogHelper::warning("Missing action parameter");
                HttpHelper::sendJsonError('Missing action parameter', 400);
            }

            // Generate CSRF token for responses that require it
            if (in_array($action, ['init', 'auth'])) {
                $requestData['csrfToken'] = SecurityHelper::generateCsrfToken();
            }

            // Check cache for relevant actions
            $cacheKey = null;
            if (in_array($action, ['getAuthResult'])) {
                $cacheKey = $action . '_' . ($requestData['threeDSServerTransID'] ?? '');
                $cachedResponse = CacheHelper::get($cacheKey);
                
                if ($cachedResponse !== null) {
                    LogHelper::debug("Cache hit for $action", ['transactionId' => $requestData['threeDSServerTransID'] ?? '']);
                    HttpHelper::sendJsonResponse($cachedResponse);
                    return;
                }
            }
            
            // Process requested action
            $response = $this->processAction($action, $requestData);
            
            // Cache the response if applicable
            if ($cacheKey !== null) {
                CacheHelper::set($cacheKey, $response, 300); // Cache for 5 minutes
            }
            
            HttpHelper::sendJsonResponse($response);
        } catch (ThreeDSException $e) {
            LogHelper::error("API Error: " . $e->getMessage(), [
                'code' => $e->getCode(),
                'transactionId' => $e->getTransactionId(),
                'context' => $e->getContext()
            ]);
            HttpHelper::sendJsonError($e->getMessage(), $e->getHttpStatusCode());
        } catch (Exception $e) {
            LogHelper::error("Unexpected Error: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            HttpHelper::sendJsonError('An unexpected error occurred', 500);
        }
    }

    /**
     * Process requested action
     *
     * @param string $action Action to perform
     * @param array $requestData Request data
     * @return array Response data
     * @throws ThreeDSException
     */
    private function processAction(string $action, array $requestData): array
    {
        return match ($action) {
            'init' => $this->handleInitAction($requestData),
            'auth' => $this->handleAuthAction($requestData),
            'getAuthResult' => $this->handleGetAuthResultAction($requestData),
            'updateChallengeStatus' => $this->handleUpdateChallengeStatusAction($requestData),
            default => throw new ThreeDSException("Unknown action: $action")
        };
    }

    /**
     * Handle init action
     *
     * @param array $requestData Request data
     * @return array Response data
     * @throws ThreeDSException
     */
    private function handleInitAction(array $requestData): array
    {
        $authData = $requestData['authData'] ?? [];

        return $this->threeDSService->initialize(
            $authData['acctNumber']
        );
    }

    /**
     * Handle auth action
     *
     * @param array $requestData Request data
     * @return array Response data
     * @throws ThreeDSException
     */
    private function handleAuthAction(array $requestData): array
    {
        $additionalData = [
            'merchantId' => $requestData['merchantId'] ?? null,
            'purchaseAmount' => $requestData['purchaseAmount'] ?? null,
            'purchaseDate' => $requestData['purchaseDate'] ?? null,
            'cardExpiryDate' => $requestData['cardExpiryDate'] ?? null
        ];

        return $this->threeDSService->authenticate(
            $requestData['threeDSServerTransID'] ?? '',
            $requestData['threeDSRequestorTransID'] ?? '',
            $requestData['browserInfo'] ?? '',
            $requestData['acctNumber'] ?? '',
            $additionalData
        );
    }

    /**
     * Handle getAuthResult action
     *
     * @param array $requestData Request data
     * @return array Response data
     * @throws ThreeDSException
     */
    private function handleGetAuthResultAction(array $requestData): array
    {
        return $this->threeDSService->getAuthResult(
            $requestData['threeDSServerTransID'] ?? ''
        );
    }

    /**
     * Handle updateChallengeStatus action
     *
     * @param array $requestData Request data
     * @return array Response data
     * @throws ThreeDSException
     */
    private function handleUpdateChallengeStatusAction(array $requestData): array
    {
        return $this->threeDSService->updateChallengeStatus(
            $requestData['threeDSServerTransID'] ?? '',
            $requestData['status'] ?? '01'
        );
    }
} 