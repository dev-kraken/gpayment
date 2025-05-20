<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Config\Router;
use App\Config\RouteManager;
use PHPUnit\Framework\TestCase;

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class RouterTest extends TestCase
{
    /**
     * Test that Router constructor initializes RouteManager
     */
    public function testRouterConstructorInitializesRouteManager(): void
    {
        // Use reflection to access private property
        $router = new Router();
        $reflection = new \ReflectionClass($router);
        $routeManagerProperty = $reflection->getProperty('routeManager');
        $routeManagerProperty->setAccessible(true);
        
        $routeManager = $routeManagerProperty->getValue($router);
        
        $this->assertInstanceOf(RouteManager::class, $routeManager);
    }
    
    /**
     * Test that a 404 is returned for a non-existent route
     * 
     * This test uses output buffering to capture the output
     */
    public function testHandle404(): void
    {
        // Mock environment where the request URI doesn't match any route
        $_SERVER['REQUEST_URI'] = '/non-existent-route';
        
        // Create router with status codes disabled to avoid headers already sent errors
        $router = new Router(false);
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($router);
        $method = $reflection->getMethod('handle404');
        $method->setAccessible(true);
        
        // Set the current path property (normally done by parseRequestPath)
        $currentPathProperty = $reflection->getProperty('currentPath');
        $currentPathProperty->setAccessible(true);
        $currentPathProperty->setValue($router, 'non-existent-route');
        
        // Start output buffering before calling the method
        ob_start();
        
        // Invoke the method
        $method->invoke($router);
        
        // Capture and clean the output
        $output = ob_get_clean();
        
        // We don't check http_response_code() as it causes "headers already sent" issues in PHPUnit
        // Instead, just verify content indicates a 404 response
        $this->assertStringContainsString('404 Not Found', $output);
        $this->assertStringContainsString('The requested resource was not found', $output);
    }
} 