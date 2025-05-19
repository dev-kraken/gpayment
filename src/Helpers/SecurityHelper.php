<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Security Helper class for CSRF protection and input validation
 *
 * @author DevKraken <soman@devkraken.com>
 * @copyright 2025 DevKraken
 * @license Proprietary
 */
class SecurityHelper
{
    /**
     * Generate a new CSRF token and store it in the session
     *
     * @return string The generated token
     */
    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
        
        // Generate a cryptographically secure random token
        $token = bin2hex(random_bytes(32));
        
        // Store the token with timestamp (for expiration)
        $_SESSION['csrf_tokens'][$token] = time();
        
        // Clean up old tokens (expire after 2 hours)
        self::cleanupExpiredTokens();
        
        return $token;
    }
    
    /**
     * Verify a CSRF token
     *
     * @param string $token The token to verify
     * @param bool $removeToken Whether to remove the token after verification (default: true)
     * @return bool Whether the token is valid
     */
    public static function verifyCsrfToken(string $token, bool $removeToken = true): bool
    {
        if (!isset($_SESSION['csrf_tokens']) || !isset($_SESSION['csrf_tokens'][$token])) {
            return false;
        }
        
        // Check token expiration (2 hours)
        $timestamp = $_SESSION['csrf_tokens'][$token];
        if (time() - $timestamp > 7200) {
            unset($_SESSION['csrf_tokens'][$token]);
            return false;
        }
        
        // Remove the token if requested (one-time use)
        if ($removeToken) {
            unset($_SESSION['csrf_tokens'][$token]);
        }
        
        return true;
    }
    
    /**
     * Clean up expired CSRF tokens
     */
    private static function cleanupExpiredTokens(): void
    {
        if (!isset($_SESSION['csrf_tokens'])) {
            return;
        }
        
        $now = time();
        
        // Remove tokens older than 2 hours
        foreach ($_SESSION['csrf_tokens'] as $token => $timestamp) {
            if ($now - $timestamp > 7200) {
                unset($_SESSION['csrf_tokens'][$token]);
            }
        }
    }
    
    /**
     * Sanitize input string to prevent XSS
     *
     * @param string $input The input to sanitize
     * @return string The sanitized input
     */
    public static function sanitizeString(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize an array of inputs
     *
     * @param array $inputs The inputs to sanitize
     * @return array The sanitized inputs
     */
    public static function sanitizeArray(array $inputs): array
    {
        $sanitized = [];
        
        foreach ($inputs as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else if (is_string($value)) {
                $sanitized[$key] = self::sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validate that a string is not empty
     *
     * @param string|null $input The input to validate
     * @return bool Whether the input is valid
     */
    public static function validateNotEmpty(?string $input): bool
    {
        return $input !== null && trim($input) !== '';
    }
    
    /**
     * Validate that a string is an email
     *
     * @param string $input The input to validate
     * @return bool Whether the input is valid
     */
    public static function validateEmail(string $input): bool
    {
        return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate that a string is a URL
     *
     * @param string $input The input to validate
     * @return bool Whether the input is valid
     */
    public static function validateUrl(string $input): bool
    {
        return filter_var($input, FILTER_VALIDATE_URL) !== false;
    }
} 