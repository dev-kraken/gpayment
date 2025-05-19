<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Configuration Helper for loading and managing application configuration
 *
 * @author DevKraken <soman@devkraken.com>
 * @copyright 2025 DevKraken
 * @license Proprietary
 */
class ConfigHelper
{
    /**
     * @var array Cache for loaded configurations
     */
    private static array $configCache = [];
    
    /**
     * Load the complete application configuration
     *
     * @return array The merged configuration array
     */
    public static function loadConfig(): array
    {
        // Check cache first
        if (isset(self::$configCache['full_config'])) {
            return self::$configCache['full_config'];
        }
        
        // Load individual configuration files
        $appConfig = self::loadAppConfig();
        $threeDsConfig = self::load3DSConfig();
        
        // Merge configs
        $config = array_merge($appConfig, ['3ds' => $threeDsConfig]);
        
        // Cache the result
        self::$configCache['full_config'] = $config;
        
        return $config;
    }
    
    /**
     * Load application configuration
     *
     * @return array Application configuration
     */
    public static function loadAppConfig(): array
    {
        // Check cache first
        if (isset(self::$configCache['app'])) {
            return self::$configCache['app'];
        }
        
        $appConfig = require __DIR__ . '/../../config/app.php';
        
        // Cache the result
        self::$configCache['app'] = $appConfig;
        
        return $appConfig;
    }
    
    /**
     * Load 3DS configuration
     *
     * @return array 3DS configuration
     */
    public static function load3DSConfig(): array
    {
        // Check cache first
        if (isset(self::$configCache['3ds'])) {
            return self::$configCache['3ds'];
        }
        
        $threeDsConfig = require __DIR__ . '/../../config/3ds.php';
        
        // Cache the result
        self::$configCache['3ds'] = $threeDsConfig;
        
        return $threeDsConfig;
    }
    
    /**
     * Load routes configuration
     *
     * @return array Routes configuration
     */
    public static function loadRoutesConfig(): array
    {
        // Check cache first
        if (isset(self::$configCache['routes'])) {
            return self::$configCache['routes'];
        }
        
        $routesConfig = require __DIR__ . '/../../config/routes.php';
        
        // Cache the result
        self::$configCache['routes'] = $routesConfig;
        
        return $routesConfig;
    }
    
    /**
     * Get an environment variable with a default fallback
     *
     * @param string $key Environment variable key
     * @param mixed $default Default value if not found
     * @return mixed Environment variable value or default
     */
    public static function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
    
    /**
     * Clear the configuration cache
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$configCache = [];
    }
} 