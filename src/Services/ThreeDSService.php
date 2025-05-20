<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ThreeDSException;
use App\Helpers\LogHelper;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service for handling 3DS authentication operations
 *
 * @author DevKraken <soman@devkraken.com>
 * @copyright 2025 DevKraken
 * @license Proprietary
 */
class ThreeDSService
{
    private Client $client;
    private array $config;

    /**
     * Constructor
     *
     * @param array $config Configuration array
     * @throws ThreeDSException If SSL certificate files are missing
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = $this->createApiClient();
    }

    /**
     * Initialize 3DS authentication process
     *
     * @param string $cardNumber Card number
     * @return array Response from 3DS server
     * @throws ThreeDSException
     */
    public function initialize(string $cardNumber): array
    {
        if (empty($cardNumber)) {
            throw new ThreeDSException("Account number is required");
        }

        // Generate or validate threeDSRequestorTransID
        $threeDSRequestorTransID = $requestorTransID ?? $this->generateUUID();

        // Store important data in session
        $_SESSION['acctNumber'] = self::getOnlyNumbers($cardNumber);
        $_SESSION['merchantId'] = self::getOnlyNumbers($this->config['merchant']['id']);
        $_SESSION['threeDSRequestorTransID'] = $threeDSRequestorTransID;

        $payload = [
            'merchantId' => $_SESSION['merchantId'],
            'acctNumber' => $_SESSION['acctNumber'],
            'eventCallbackUrl' => $this->config['notification_url'],
            'threeDSRequestorTransID' => $threeDSRequestorTransID,
        ];

        LogHelper::debug("Sending init payload: " . json_encode($payload));

        // Make the request to ActiveServer
        try {
            $response = $this->client->post($this->config['api']['init_endpoint'], [
                'json' => $payload
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            if ($responseBody === null) {
                throw new ThreeDSException("Failed to decode response from 3DS server");
            }

            LogHelper::debug("Init response ($statusCode): " . json_encode($responseBody));

            // Store important response data in session
            if ($statusCode === 200) {
                if (isset($responseBody['threeDSServerTransID'])) {
                    $_SESSION['threeDSServerTransID'] = $responseBody['threeDSServerTransID'];
                }

                if (isset($responseBody['threeDSServerCallbackUrl'])) {
                    $_SESSION['threeDSServerCallbackUrl'] = $responseBody['threeDSServerCallbackUrl'];
                }

                if (isset($responseBody['authUrl'])) {
                    $_SESSION['authUrl'] = $responseBody['authUrl'];
                }
            }

            return $responseBody;

        } catch (GuzzleException $e) {
            LogHelper::error("Init request failed: " . $e->getMessage());
            throw new ThreeDSException("Init request failed: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Authenticate a card with 3DS
     *
     * @param string $threeDSServerTransID Server transaction ID
     * @param string $threeDSRequestorTransID Requestor transaction ID
     * @param string $browserInfo Browser information
     * @param string $cardNumber Card number
     * @param array $additionalData Additional data for authentication
     * @return array Response from 3DS server
     * @throws ThreeDSException
     */
    public function authenticate(
        string $threeDSServerTransID,
        string $threeDSRequestorTransID,
        string $browserInfo,
        string $cardNumber,
        array  $additionalData = []
    ): array
    {
        // Validate transaction IDs
        if (empty($threeDSServerTransID) || empty($threeDSRequestorTransID)) {
            throw new ThreeDSException(
                "Missing required transaction IDs. Please complete initialization first.",
                400,
                null,
                null,
                ['threeDSServerTransID' => $threeDSServerTransID],
                400
            );
        }

        // Validate transaction ID format
        if (!$this->isValidUuid($threeDSServerTransID)) {
            throw new ThreeDSException(
                "Invalid transaction ID format.",
                400,
                null,
                $threeDSServerTransID,
                ['threeDSServerTransID' => $threeDSServerTransID],
                400
            );
        }

        // Get account and merchant info
        $merchantId = $additionalData['merchantId'] ?? $_SESSION['merchantId'] ?? null;

        if (empty($cardNumber) || empty($merchantId)) {
            throw new ThreeDSException(
                "Missing required account data. Please complete initialization first.",
                400,
                null,
                $threeDSServerTransID,
                ['cardNumber' => $this->maskCardNumber($cardNumber)],
                400
            );
        }

        // Validate card number with Luhn algorithm
        if (!$this->isValidCardNumber($cardNumber)) {
            throw new ThreeDSException(
                "Invalid card number format.",
                400,
                null,
                $threeDSServerTransID,
                ['cardNumber' => $this->maskCardNumber($cardNumber)],
                400
            );
        }

        if (empty($browserInfo)) {
            throw new ThreeDSException(
                "Missing required browser information.",
                400,
                null,
                $threeDSServerTransID,
                [],
                400
            );
        }

        LogHelper::debug("Using browserInfo: " . $browserInfo);

        // Get authUrl from session
        $authUrl = $_SESSION['authUrl'] ?? null;
        if (empty($authUrl)) {
            throw new ThreeDSException(
                "Missing authentication URL. Please complete initialization first.",
                400,
                null,
                $threeDSServerTransID,
                [],
                400
            );
        }

        // Decode base64 browser info
        try {
            $browserInfoJson = base64_decode($browserInfo);
            if ($browserInfoJson === false) {
                throw new ThreeDSException(
                    "Failed to decode browser information from base64.",
                    400,
                    null,
                    $threeDSServerTransID,
                    [],
                    400
                );
            }

            $browserInfoObj = json_decode($browserInfoJson, true);

            if (!is_array($browserInfoObj)) {
                throw new ThreeDSException(
                    "Invalid browser information format.",
                    400,
                    null,
                    $threeDSServerTransID,
                    [],
                    400
                );
            }

            LogHelper::debug("Decoded browserInfo: " . json_encode($browserInfoObj));
        } catch (ThreeDSException $e) {
            throw $e;
        } catch (Exception $e) {
            LogHelper::error("Failed to decode browserInfo: " . $e->getMessage());
            throw new ThreeDSException(
                "Failed to decode browser information: " . $e->getMessage(),
                400,
                $e,
                $threeDSServerTransID,
                [],
                400
            );
        }

        // Add individual browser fields to the payload with validation
        $browserInfoCollected = [
            'browserAcceptHeader' => $this->sanitizeString($browserInfoObj['browserAcceptHeader'] ?? ''),
            'browserColorDepth' => $this->validateBrowserColorDepth($browserInfoObj['browserColorDepth'] ?? ''),
            'browserIP' => $this->sanitizeIP($browserInfoObj['browserIP'] ?? ''),
            'browserJavaEnabled' => isset($browserInfoObj['browserJavaEnabled']) && $browserInfoObj['browserJavaEnabled'],
            'browserJavascriptEnabled' => true, // This must be true
            'browserLanguage' => $this->sanitizeString($browserInfoObj['browserLanguage'] ?? ''),
            'browserScreenHeight' => $this->validateScreenDimension($browserInfoObj['browserScreenHeight'] ?? ''),
            'browserScreenWidth' => $this->validateScreenDimension($browserInfoObj['browserScreenWidth'] ?? ''),
            'browserTZ' => $this->validateTimezone($browserInfoObj['browserTZ'] ?? ''),
            'browserUserAgent' => $this->sanitizeString($browserInfoObj['browserUserAgent'] ?? '')
        ];

        // Prepare authentication payload
        $payload = [
            'acctNumber' => self::getOnlyNumbers($cardNumber),
            'authenticationInd' => '01', // Payment transaction
            'browserInfo' => $browserInfo,
            'browserInfoCollected' => $browserInfoCollected,
            'cardExpiryDate' => $additionalData['cardExpiryDate'] ?? $this->config['test_card']['expiry'],
            'merchantId' => self::getOnlyNumbers($merchantId),
            'merchantName' => $additionalData['merchantName'] ?? $this->config['merchant']['name'],
            'messageCategory' => 'pa',
            'purchaseAmount' => $additionalData['purchaseAmount'] ?? '1000',
            'purchaseCurrency' => $this->config['transaction']['currency'],
            'purchaseDate' => date('YmdHis'),
            'threeDSServerTransID' => $threeDSServerTransID
        ];

        LogHelper::debug("Auth payload: " . json_encode($payload));

        // Extract the path from the authUrl
        $parsedUrl = parse_url($authUrl);
        if ($parsedUrl === false) {
            throw new ThreeDSException("Invalid authentication URL format.");
        }

        $authPath = $parsedUrl['path'] . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');

        LogHelper::debug("Using auth path: " . $authPath);

        // Make the authentication request
        try {
            $response = $this->client->post($authPath, [
                'json' => $payload
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            if ($responseBody === null) {
                throw new ThreeDSException("Failed to decode response from 3DS server");
            }

            LogHelper::debug("Auth response ($statusCode): " . json_encode($responseBody));

            // Store challenge URL if available
            if ($statusCode === 200 && isset($responseBody['challengeUrl'])) {
                $_SESSION['challengeUrl'] = $responseBody['challengeUrl'];
                $_SESSION['acsTransID'] = $responseBody['acsTransID'] ?? null;
                $_SESSION['transStatus'] = $responseBody['transStatus'] ?? null;
            }

            return $responseBody;

        } catch (GuzzleException $e) {
            LogHelper::error("Auth request failed: " . $e->getMessage());
            throw new ThreeDSException("Auth request failed: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get authentication result after challenge
     *
     * @param string $threeDSServerTransID Server transaction ID
     * @return array Authentication result
     * @throws ThreeDSException
     */
    public function getAuthResult(string $threeDSServerTransID): array
    {
        if (empty($threeDSServerTransID)) {
            throw new ThreeDSException("Missing required threeDSServerTransID.");
        }

        // Make the request
        try {
            $response = $this->client->get($this->config['api']['auth_result_endpoint'] . "?threeDSServerTransID=$threeDSServerTransID");

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            if ($responseBody === null) {
                throw new ThreeDSException("Failed to decode response from 3DS server");
            }

            LogHelper::debug("Auth result response ($statusCode): " . json_encode($responseBody));

            // Get transaction status
            $transStatus = $responseBody['transStatus'] ?? 'Unknown';
            
            // Map transaction status to appropriate response
            $statusMap = [
                'Y' => ['status' => 'success', 'message' => 'Payment Authenticated Successfully'],
                'N' => ['status' => 'failed', 'message' => 'Authentication Failed - Not Authenticated'],
                'U' => ['status' => 'error', 'message' => 'Authentication Error - Technical Issue'],
                'A' => ['status' => 'partial', 'message' => 'Authentication Attempted but Not Verified'],
                'C' => ['status' => 'challenge', 'message' => 'Challenge Required'],
                'D' => ['status' => 'decoupled', 'message' => 'Decoupled Authentication Required'],
                'R' => ['status' => 'rejected', 'message' => 'Authentication Rejected by Issuer']
            ];
            
            // Get mapped status or default to completed if unknown
            $statusInfo = $statusMap[$transStatus] ?? ['status' => 'completed', 'message' => 'Authentication Completed'];

            // Standardize the response format
            return [
                'status' => $statusInfo['status'],
                'message' => $statusInfo['message'],
                'transStatus' => $transStatus,
                'details' => $responseBody
            ];

        } catch (GuzzleException $e) {
            LogHelper::error("Get auth result failed: " . $e->getMessage());
            throw new ThreeDSException("Get auth result failed: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Update challenge status after challenge completion
     *
     * @param string $threeDSServerTransID Server transaction ID
     * @param string $status Challenge status (default '01')
     * @return array Response from server
     * @throws ThreeDSException
     */
    public function updateChallengeStatus(string $threeDSServerTransID, string $status = '01'): array
    {
        if (empty($threeDSServerTransID)) {
            throw new ThreeDSException("Missing required threeDSServerTransID.");
        }

        // Make the request
        try {
            $response = $this->client->post($this->config['api']['challenge_status_endpoint'], [
                'json' => [
                    'threeDSServerTransID' => $threeDSServerTransID,
                    'status' => $status
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            if ($responseBody === null) {
                throw new ThreeDSException("Failed to decode response from 3DS server");
            }

            LogHelper::debug("Challenge status response ($statusCode): " . json_encode($responseBody));

            return $responseBody;

        } catch (GuzzleException $e) {
            // Check if error is "Transaction was already completed" (error code 1013)
            $errorData = $this->extractErrorFromResponse($e);

            if (isset($errorData['errorCode']) && $errorData['errorCode'] === '1013') {
                LogHelper::info("Transaction already completed, skipping status update: " . $threeDSServerTransID);
                // Return a successful response to allow processing to continue
                return [
                    'status' => 'success',
                    'message' => 'Transaction already completed'
                ];
            }

            LogHelper::error("Update challenge status failed: " . $e->getMessage());
            throw new ThreeDSException("Update challenge status failed: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Extract error data from a Guzzle exception response
     *
     * @param GuzzleException $exception The Guzzle exception
     * @return array Error data or empty array if not parseable
     */
    private function extractErrorFromResponse(GuzzleException $exception): array
    {
        try {
            if (method_exists($exception, 'getResponse') && $exception->getResponse()) {
                $responseBody = $exception->getResponse()->getBody()->getContents();
                $errorData = json_decode($responseBody, true);

                if (is_array($errorData)) {
                    return $errorData;
                }
            }
        } catch (Exception $e) {
            LogHelper::debug("Failed to extract error data: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Create API client with SSL certificates
     *
     * @return Client Guzzle client for API requests
     * @throws ThreeDSException
     */
    private function createApiClient(): Client
    {
        // Validate certificate files
        if (!file_exists($this->config['ssl']['cert_file'])) {
            throw new ThreeDSException("Certificate file not found: " . $this->config['ssl']['cert_file']);
        }

        if (!file_exists($this->config['ssl']['key_file'])) {
            throw new ThreeDSException("Private key file not found: " . $this->config['ssl']['key_file']);
        }

        if (!file_exists($this->config['ssl']['ca_file'])) {
            throw new ThreeDSException("CA certificate file not found: " . $this->config['ssl']['ca_file']);
        }

        // Create client with SSL certificates
        return new Client([
            'base_uri' => $this->config['server']['url'],
            'timeout' => 30,
            'cert' => $this->config['ssl']['cert_file'],
            'ssl_key' => $this->config['ssl']['key_file'],
            'verify' => $this->config['ssl']['ca_file'],
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    /**
     * Generate a UUID v4 following RFC 4122
     *
     * @return string UUID string
     */
    private function generateUUID(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Validate if a string is a valid UUID
     *
     * @param string $uuid String to check
     * @return bool True if valid UUID
     */
    private function isValidUuid(string $uuid): bool
    {
        return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Get the card type based on card number
     * 
     * @param string $cardNumber Card number
     * @return string|null Card type or null if not recognized
     */
    private function getCardType(string $cardNumber): ?string
    {
        // Remove spaces and non-numeric characters
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        
        // Define patterns for each card type
        $cardPatterns = [
            'visa' => '/^4\d{12}(?:\d{3})?$/',
            'mastercard' => '/^5[1-5]\d{14}$/',
            'amex' => '/^3[47]\d{13}$/',
            'discover' => '/^6(?:011|5\d{2})\d{12}$/',
            'jcb' => '/^(?:2131|1800|35\d{3})\d{11}$/',
            'diners' => '/^3(?:0[0-5]|[68]\d)\d{11}$/',
            'maestro' => '/^(?:5[0678]\d\d|6304|6390|67\d\d)\d{8,15}$/'
        ];
        
        // Check each pattern
        foreach ($cardPatterns as $type => $pattern) {
            if (preg_match($pattern, $cardNumber)) {
                return $type;
            }
        }
        
        return null;
    }

    /**
     * Validate credit card number using Luhn algorithm
     * and check if it's a recognized card type
     *
     * @param string $cardNumber Card number to validate
     * @return bool True if valid
     */
    private function isValidCardNumber(string $cardNumber): bool
    {
        // Remove any non-digit characters
        $cardNumber = preg_replace('/\D/', '', $cardNumber);

        // Check length
        $length = strlen($cardNumber);
        if ($length < 13 || $length > 19) {
            return false;
        }
        
        // Check if it's a recognized card type
        $cardType = $this->getCardType($cardNumber);
        if ($cardType === null) {
            LogHelper::warning("Unrecognized card type for number: " . $this->maskCardNumber($cardNumber));
            // We still proceed with Luhn validation even if card type is not recognized
        } else {
            LogHelper::debug("Card type detected: " . $cardType);
        }

        // Luhn algorithm
        $sum = 0;
        $double = false;

        // Starting from the right
        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int)$cardNumber[$i];

            if ($double) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $double = !$double;
        }

        return ($sum % 10) === 0;
    }

    /**
     * Mask a card number for logging
     *
     * @param string $cardNumber Card number to mask
     * @return string Masked card number
     */
    private function maskCardNumber(string $cardNumber): string
    {
        // Keep first 6 and last 4 digits visible
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        $length = strlen($cardNumber);

        if ($length <= 10) {
            return str_repeat('*', $length);
        }

        $firstVisible = 6;
        $lastVisible = 4;
        $maskedLength = $length - $firstVisible - $lastVisible;

        if ($maskedLength < 0) {
            $maskedLength = 0;
        }

        return substr($cardNumber, 0, $firstVisible) .
            str_repeat('*', $maskedLength) .
            substr($cardNumber, -$lastVisible);
    }

    /**
     * Sanitize a string
     *
     * @param string $input String to sanitize
     * @return string Sanitized string
     */
    private function sanitizeString(string $input): string
    {
        // Remove control characters
        return trim(preg_replace('/[\x00-\x1F\x7F]/', '', $input));
    }

    /**
     * Sanitize an IP address
     *
     * @param string $ip IP address to sanitize
     * @return string Sanitized IP address
     */
    private function sanitizeIP(string $ip): string
    {
        // Use filter_var to validate and sanitize IP
        $filteredIp = filter_var($ip, FILTER_VALIDATE_IP);
        return $filteredIp !== false ? $filteredIp : '';
    }

    /**
     * Validate browser color depth
     *
     * @param string $depth Color depth to validate
     * @return string Valid color depth
     */
    private function validateBrowserColorDepth(string $depth): string
    {
        // Valid values for 3DS are 1, 4, 8, 15, 16, 24, 32, 48
        $validDepths = ['1', '4', '8', '15', '16', '24', '32', '48'];
        return in_array($depth, $validDepths) ? $depth : '24'; // Default to 24 if invalid
    }

    /**
     * Validate screen dimension
     *
     * @param string $dimension Dimension to validate
     * @return string Valid dimension
     */
    private function validateScreenDimension(string $dimension): string
    {
        // Ensure it's a positive integer
        $intDimension = (int)$dimension;
        return $intDimension > 0 ? (string)$intDimension : '1024'; // Default to 1024 if invalid
    }

    /**
     * Validate timezone
     *
     * @param string $timezone Timezone offset to validate
     * @return string Valid timezone
     */
    private function validateTimezone(string $timezone): string
    {
        // Ensure it's a valid timezone offset (between -840 and 720 minutes)
        $intTimezone = (int)$timezone;
        if ($intTimezone >= -840 && $intTimezone <= 720) {
            return (string)$intTimezone;
        }
        return '0'; // Default to UTC if invalid
    }

    /**
     * Extracts only numeric characters from a string.
     *
     * @param string $input The input string.
     * @return string A string containing only numbers.
     */
    public static function getOnlyNumbers(string $input): string
    {
        return preg_replace('/\D/', '', $input);
    }
} 