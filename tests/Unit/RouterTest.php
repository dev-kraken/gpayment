<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Config\Router;
use App\Config\RouteManager;
use PHPUnit\Framework\TestCase;

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
        
        // Use reflection to access private method
        $router = new Router();
        $reflection = new \ReflectionClass($router);
        $method = $reflection->getMethod('handle404');
        $method->setAccessible(true);
        
        // Start output buffering
        ob_start();
        $method->invoke($router);
        $output = ob_get_clean();
        
        // Assert HTTP response code is set to 404
        $this->assertEquals(404, http_response_code());
        
        // Check that output contains "404" text
        $this->assertStringContainsString('404', $output);
    }
} 