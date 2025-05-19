<?php
declare(strict_types=1);

namespace App\Config;

use InvalidArgumentException;

/**
 * Route Manager for registering and managing application routes
 *
 * @author DevKraken <soman@devkraken.com>
 * @copyright 2025 DevKraken
 * @license Proprietary
 */
class RouteManager
{
    /**
     * @var array Routes configuration
     */
    private array $routes = [];

    /**
     * Register a controller route
     *
     * @param string $path Route path
     * @param string $controller Controller class name
     * @param string $action Controller method to call
     * @return void
     */
    public function registerControllerRoute(string $path, string $controller, string $action): void
    {
        $this->routes[$path] = [
            'controller' => $controller,
            'action' => $action
        ];
    }

    /**
     * Register a template route
     *
     * @param string $path Route path
     * @param string $template Template file path
     * @param callable|null $dataProvider Data provider function
     * @return void
     */
    public function registerTemplateRoute(string $path, string $template, ?callable $dataProvider = null): void
    {
        $this->routes[$path] = [
            'template' => $template,
            'data' => $dataProvider
        ];
    }

    /**
     * Register a route alias
     *
     * @param string $path New route path
     * @param string $targetPath Existing route path
     * @return void
     */
    public function registerAlias(string $path, string $targetPath): void
    {
        if (!isset($this->routes[$targetPath])) {
            throw new InvalidArgumentException("Target route '$targetPath' does not exist");
        }

        $this->routes[$path] = $this->routes[$targetPath];
    }

    /**
     * Get all registered routes
     *
     * @return array Routes array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get a specific route configuration
     *
     * @param string $path Route path
     * @return array|null Route configuration or null if not found
     */
    public function getRoute(string $path): ?array
    {
        return $this->routes[$path] ?? null;
    }

    /**
     * Check if a route exists
     *
     * @param string $path Route path
     * @return bool True if route exists
     */
    public function hasRoute(string $path): bool
    {
        return isset($this->routes[$path]);
    }
} 