<?php
declare(strict_types=1);

namespace App\Helpers;

use JetBrains\PhpStorm\NoReturn;
use JsonException;

/**
 * Helper for HTTP operations and responses
 */
class HttpHelper
{
    /**
     * Send a JSON response
     *
     * @param mixed $data The response data
     * @param int $statusCode HTTP status code
     * @param bool $compress Whether to enable compression
     * @return void
     * @throws JsonException
     */
    #[NoReturn] public static function sendJsonResponse(mixed $data, int $statusCode = 200, bool $compress = true): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        // Apply compression if requested and supported
        if ($compress && self::shouldCompress()) {
            self::enableCompression();
        }

        echo json_encode($data, JSON_THROW_ON_ERROR);
        exit;
    }

    /**
     * Send a JSON error response
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param bool $compress Whether to enable compression
     * @return void
     * @throws JsonException
     */
    #[NoReturn] public static function sendJsonError(string $message, int $statusCode = 400, bool $compress = true): void
    {
        self::sendJsonResponse(['error' => $message], $statusCode, $compress);
    }

    /**
     * Get JSON post data from request body
     * 
     * @return array|null The parsed JSON data or null on error
     */
    public static function getJsonPostData(): ?array
    {
        $jsonData = file_get_contents('php://input');

        if (empty($jsonData)) {
            return null;
        }

        try {
            $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : null;
        } catch (JsonException $e) {
            LogHelper::error("Failed to decode JSON data: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Set CORS headers for cross-domain requests
     *
     * @param string $allowOrigin Allowed origin domain
     * @param string $allowMethods Allowed HTTP methods
     * @param string $allowHeaders Allowed HTTP headers
     * @return void
     */
    public static function setCorsHeaders(
        string $allowOrigin = '*',
        string $allowMethods = 'GET, POST, OPTIONS',
        string $allowHeaders = 'Content-Type, Authorization'
    ): void
    {
        header("Access-Control-Allow-Origin: $allowOrigin");
        header("Access-Control-Allow-Methods: $allowMethods");
        header("Access-Control-Allow-Headers: $allowHeaders");
        header("Access-Control-Max-Age: 3600");
    }

    /**
     * Handle preflight OPTIONS request for CORS
     *
     * @return void
     */
    public static function handlePreflight(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }


    /**
     * Get client IP address
     *
     * @return string The client IP address
     */
    public static function getClientIp(): string
    {
        $ipAddress = '';

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // In case of proxy, get the first IP
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ipAddress = trim($ipList[0]);
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }

        return $ipAddress;
    }

    /**
     * Implement basic rate limiting with multiple backend support
     *
     * @param string $key Rate limit key (like IP address)
     * @param int $maxRequests Maximum requests allowed
     * @param int $period Time period in seconds
     * @return bool True if request is allowed, false if rate limited
     */
    public static function checkRateLimit(string $key, int $maxRequests = 60, int $period = 60): bool
    {
        // Create a standardized key for the rate limiter
        $rateLimitKey = 'rate_limit_' . md5($key);
        
        // Try different backends in order of preference
        
        // 1. Try APCu if available
        if (function_exists('apcu_enabled') && apcu_enabled()) {
            return self::checkRateLimitApcu($rateLimitKey, $maxRequests, $period);
        }
        
        // 2. Try Memcached if available
        if (class_exists('Memcached') && self::isMemcachedAvailable()) {
            return self::checkRateLimitMemcached($rateLimitKey, $maxRequests, $period);
        }
        
        // 3. Fallback to file-based rate limiting
        return self::checkRateLimitFile($rateLimitKey, $maxRequests, $period);
    }
    
    /**
     * Check if Memcached is available
     * 
     * @return bool True if Memcached is available
     */
    private static function isMemcachedAvailable(): bool
    {
        static $memcached = null;
        static $available = null;
        
        if ($available !== null) {
            return $available;
        }
        
        try {
            $memcached = new \Memcached();
            $memcached->addServer('127.0.0.1', 11211);
            $available = $memcached->getVersion() !== false;
            return $available;
        } catch (\Exception $e) {
            LogHelper::warning("Memcached not available: " . $e->getMessage());
            $available = false;
            return false;
        }
    }
    
    /**
     * Check rate limit using APCu
     * 
     * @param string $key Rate limit key
     * @param int $maxRequests Maximum requests allowed
     * @param int $period Time period in seconds
     * @return bool True if request is allowed
     */
    private static function checkRateLimitApcu(string $key, int $maxRequests, int $period): bool
    {
        // Get current data or create a new entry
        $success = false;
        $data = apcu_fetch($key, $success);
        
        $now = time();
        
        if (!$success) {
            // No data yet, create a new entry
            $data = [
                'count' => 1,
                'reset_time' => $now + $period
            ];
            apcu_store($key, $data, $period);
            return true;
        }
        
        // Reset counter if period expired
        if ($now > $data['reset_time']) {
            $data = [
                'count' => 1,
                'reset_time' => $now + $period
            ];
            apcu_store($key, $data, $period);
            return true;
        }
        
        // Check if limit exceeded
        if ($data['count'] >= $maxRequests) {
            return false;
        }
        
        // Increment counter
        $data['count']++;
        apcu_store($key, $data, $period);
        
        return true;
    }
    
    /**
     * Check rate limit using Memcached
     * 
     * @param string $key Rate limit key
     * @param int $maxRequests Maximum requests allowed
     * @param int $period Time period in seconds
     * @return bool True if request is allowed
     */
    private static function checkRateLimitMemcached(string $key, int $maxRequests, int $period): bool
    {
        $memcached = new \Memcached();
        $memcached->addServer('127.0.0.1', 11211);
        
        // Get current data or create a new entry
        $data = $memcached->get($key);
        $now = time();
        
        if ($memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
            // No data yet, create a new entry
            $data = [
                'count' => 1,
                'reset_time' => $now + $period
            ];
            $memcached->set($key, $data, $period);
            return true;
        }
        
        // Reset counter if period expired
        if ($now > $data['reset_time']) {
            $data = [
                'count' => 1,
                'reset_time' => $now + $period
            ];
            $memcached->set($key, $data, $period);
            return true;
        }
        
        // Check if limit exceeded
        if ($data['count'] >= $maxRequests) {
            return false;
        }
        
        // Increment counter
        $data['count']++;
        $memcached->set($key, $data, $period);
        
        return true;
    }
    
    /**
     * Check rate limit using file-based storage
     * 
     * @param string $key Rate limit key
     * @param int $maxRequests Maximum requests allowed
     * @param int $period Time period in seconds
     * @return bool True if request is allowed
     */
    private static function checkRateLimitFile(string $key, int $maxRequests, int $period): bool
    {
        $cacheFile = sys_get_temp_dir() . '/' . $key . '.json';

        // Default data structure
        $data = [
            'count' => 0,
            'reset_time' => time() + $period
        ];

        // Load existing data if available
        if (file_exists($cacheFile)) {
            $fileData = json_decode(file_get_contents($cacheFile), true);

            if (is_array($fileData)) {
                $data = $fileData;
            }
        }

        // Reset counter if period expired
        if (time() > $data['reset_time']) {
            $data = [
                'count' => 1,
                'reset_time' => time() + $period
            ];
            file_put_contents($cacheFile, json_encode($data));
            return true;
        }

        // Check if limit exceeded
        if ($data['count'] >= $maxRequests) {
            return false;
        }

        // Increment counter
        $data['count']++;
        file_put_contents($cacheFile, json_encode($data));

        return true;
    }

    /**
     * Send rate limit exceeded response
     *
     * @param int $retryAfter Seconds until rate limit resets
     * @return void
     * @throws JsonException
     */
    #[NoReturn] public static function sendRateLimitedResponse(int $retryAfter = 60): void
    {
        header('Retry-After: ' . $retryAfter);
        self::sendJsonError('Rate limit exceeded. Please try again later.', 429);
    }

    /**
     * Check if response compression should be used
     *
     * @return bool True if compression is supported and accepted
     */
    private static function shouldCompress(): bool
    {
        return extension_loaded('zlib') &&
            !ini_get('zlib.output_compression') &&
            isset($_SERVER['HTTP_ACCEPT_ENCODING']) &&
            str_contains($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip');
    }

    /**
     * Enable output compression
     *
     * @return void
     */
    private static function enableCompression(): void
    {
        if (!headers_sent()) {
            ini_set('zlib.output_compression', '1');
            header('Content-Encoding: gzip');
        }
    }
} 