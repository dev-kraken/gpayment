<?php
declare(strict_types=1);

namespace App\Config;

use App\Helpers\ConfigHelper;
use App\Helpers\TemplateHelper;
use Exception;

/**
 * Router class responsible for handling HTTP requests and routing
 *
 * @author DevKraken <soman@devkraken.com>
 * @copyright 2025 DevKraken
 * @license Proprietary
 */
class Router
{
    /**
     * @var RouteManager Route manager instance
     */
    private RouteManager $routeManager;
    
    /**
     * @var string Current path being processed
     */
    private string $currentPath;
    
    /**
     * Router constructor
     */
    public function __construct()
    {
        $this->routeManager = new RouteManager();
        $this->loadRoutes();
    }
    
    /**
     * Load routes from configuration file
     */
    private function loadRoutes(): void
    {
        $routes = ConfigHelper::loadRoutesConfig();
        
        foreach ($routes as $path => $config) {
            if (isset($config['alias'])) {
                // Register alias
                $this->routeManager->registerAlias($path, $config['alias']);
            } elseif (isset($config['controller'])) {
                // Register controller route
                $this->routeManager->registerControllerRoute(
                    $path, 
                    $config['controller'], 
                    $config['action']
                );
            } elseif (isset($config['template'])) {
                // Register template route
                $this->routeManager->registerTemplateRoute(
                    $path, 
                    $config['template'], 
                    $config['data'] ?? null
                );
            }
        }
    }
    
    /**
     * Process the request and route to the appropriate handler
     * 
     * @return void
     */
    public function process(): void
    {
        try {
            $this->parseRequestPath();
            $this->routeRequest();
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Parse the requested path from URL
     * 
     * @return void
     */
    private function parseRequestPath(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->currentPath = trim($path, '/');
        
        // Extract the base path if needed
        if (str_starts_with($this->currentPath, '/')) {
            $basePath = '/';
            $this->currentPath = substr($this->currentPath, strlen($basePath));
        }
    }
    
    /**
     * Route the request to the appropriate handler
     * 
     * @return void
     */
    private function routeRequest(): void
    {
        // Check if the route exists
        if ($this->routeManager->hasRoute($this->currentPath)) {
            $this->handleDefinedRoute($this->routeManager->getRoute($this->currentPath));
        } else {
            $this->handleStaticAssetOrNotFound();
        }
    }
    
    /**
     * Handle a defined route
     * 
     * @param array $routeConfig Route configuration
     * @return void
     */
    private function handleDefinedRoute(array $routeConfig): void
    {
        // If controller is defined, instantiate and call the action
        if (isset($routeConfig['controller'])) {
            $controller = $routeConfig['controller']::createFromConfig();
            $action = $routeConfig['action'];
            $controller->$action();
        } 
        // If template is defined, render it
        elseif (isset($routeConfig['template'])) {
            $data = isset($routeConfig['data']) && is_callable($routeConfig['data']) 
                ? ['config' => $routeConfig['data']()] 
                : [];
            
            TemplateHelper::render($routeConfig['template'], $data);
        }
    }
    
    /**
     * Handle static assets or return 404 if not found
     * 
     * @return void
     */
    private function handleStaticAssetOrNotFound(): void
    {
        $filePath = __DIR__ . '/../../public/' . $this->currentPath;
        
        if (file_exists($filePath)) {
            $this->serveStaticFile($filePath);
        } else {
            $this->handle404();
        }
    }
    
    /**
     * Serve a static file
     * 
     * @param string $filePath Path to the file
     * @return void
     */
    private function serveStaticFile(string $filePath): void
    {
        // Determine content type
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $contentTypes = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'json' => 'application/json',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'svg'  => 'image/svg+xml',
            'html' => 'text/html',
            'txt'  => 'text/plain',
        ];
        
        $contentType = $contentTypes[$extension] ?? 'application/octet-stream';
        header("Content-Type: $contentType");
        
        // Output file contents
        readfile($filePath);
    }
    
    /**
     * Handle 404 Not Found
     * 
     * @return void
     */
    private function handle404(): void
    {
        http_response_code(404);
        TemplateHelper::renderPartial('errors/not_found.php', [
            'requestedPath' => $this->currentPath
        ]);
    }
    
    /**
     * Handle exceptions
     * 
     * @param Exception $e Exception object
     * @return void
     */
    private function handleException(Exception $e): void
    {
        http_response_code(500);
        $debug = isset($_ENV['DEBUG_MODE']) && $_ENV['DEBUG_MODE'] === 'true';
        
        TemplateHelper::renderPartial('errors/server_error.php', [
            'errorMessage' => $e->getMessage(),
            'stackTrace' => $e->getTraceAsString(),
            'debug' => $debug
        ]);
    }
} 