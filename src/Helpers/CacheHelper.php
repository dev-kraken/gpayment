<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Cache Helper class for data caching
 *
 * @author DevKraken <soman@devkraken.com>
 * @copyright 2025 DevKraken
 * @license Proprietary
 */
class CacheHelper
{
    /**
     * @var string The cache directory
     */
    private static string $cacheDir = __DIR__ . '/../../cache/';
    
    /**
     * @var int Default cache expiration time in seconds (1 hour)
     */
    private static int $defaultExpiration = 3600;
    
    /**
     * @var string|null Encryption key
     */
    private static ?string $encryptionKey = null;
    
    /**
     * Set the cache directory
     *
     * @param string $dir The directory to use for caching
     * @return void
     */
    public static function setCacheDir(string $dir): void
    {
        self::$cacheDir = rtrim($dir, '/') . '/';
    }
    
    /**
     * Set encryption key for secure caching
     * 
     * @param string $key Encryption key
     * @return void
     */
    public static function setEncryptionKey(string $key): void 
    {
        self::$encryptionKey = $key;
    }
    
    /**
     * Get an item from the cache
     *
     * @param string $key The cache key
     * @param bool $encrypted Whether the data is encrypted
     * @return mixed|null The cached data or null if not found/expired
     */
    public static function get(string $key, bool $encrypted = false)
    {
        $filename = self::getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $content = file_get_contents($filename);
        if ($content === false) {
            return null;
        }
        
        // Decrypt if needed
        if ($encrypted) {
            if (self::$encryptionKey === null) {
                LogHelper::warning("Attempted to decrypt cache data with no encryption key set");
                return null;
            }
            
            $content = self::decrypt($content);
            if ($content === false) {
                LogHelper::error("Failed to decrypt cache data for key: $key");
                return null;
            }
        }
        
        $data = unserialize($content);
        
        // Check if data is expired
        if ($data['expiration'] < time()) {
            self::delete($key);
            return null;
        }
        
        return $data['content'];
    }
    
    /**
     * Store an item in the cache
     *
     * @param string $key The cache key
     * @param mixed $content The data to cache
     * @param int|null $expiration Expiration time in seconds, null for default
     * @param bool $encrypt Whether to encrypt the data
     * @return bool Whether the data was successfully stored
     */
    public static function set(string $key, $content, ?int $expiration = null, bool $encrypt = false): bool
    {
        self::ensureCacheDirectoryExists();
        
        $expirationTime = time() + ($expiration ?? self::$defaultExpiration);
        
        $data = [
            'expiration' => $expirationTime,
            'content' => $content
        ];
        
        $serialized = serialize($data);
        
        // Encrypt if needed
        if ($encrypt) {
            if (self::$encryptionKey === null) {
                LogHelper::warning("Attempted to encrypt cache data with no encryption key set");
                return false;
            }
            
            $serialized = self::encrypt($serialized);
            if ($serialized === false) {
                LogHelper::error("Failed to encrypt cache data for key: $key");
                return false;
            }
        }
        
        $filename = self::getCacheFilename($key);
        
        return file_put_contents($filename, $serialized) !== false;
    }
    
    /**
     * Encrypt data using AES-256-CBC
     * 
     * @param string $data Data to encrypt
     * @return string|false Encrypted data or false on failure
     */
    private static function encrypt(string $data)
    {
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-cbc',
            self::$encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($encrypted === false) {
            return false;
        }
        
        // Prepend IV to the encrypted data
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt data using AES-256-CBC
     * 
     * @param string $data Data to decrypt
     * @return string|false Decrypted data or false on failure
     */
    private static function decrypt(string $data)
    {
        $data = base64_decode($data);
        if ($data === false) {
            return false;
        }
        
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        
        // Extract IV from the beginning of the data
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        return openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            self::$encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
    
    /**
     * Delete an item from the cache
     *
     * @param string $key The cache key
     * @return bool Whether the data was successfully deleted
     */
    public static function delete(string $key): bool
    {
        $filename = self::getCacheFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }
    
    /**
     * Clear all cached items
     *
     * @return bool Whether the cache was successfully cleared
     */
    public static function clear(): bool
    {
        self::ensureCacheDirectoryExists();
        
        $files = glob(self::$cacheDir . '*.cache');
        
        if ($files === false) {
            return false;
        }
        
        $success = true;
        
        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Get the cache filename for a key
     *
     * @param string $key The cache key
     * @return string The cache filename
     */
    private static function getCacheFilename(string $key): string
    {
        return self::$cacheDir . md5($key) . '.cache';
    }
    
    /**
     * Ensure that the cache directory exists
     *
     * @return void
     */
    private static function ensureCacheDirectoryExists(): void
    {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }
} 