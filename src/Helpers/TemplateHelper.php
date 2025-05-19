<?php
declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

/**
 * Helper for template rendering
 */
class TemplateHelper
{
    /**
     * Render a view with the specified layout
     *
     * @param string $viewPath Path to the view file (relative to templates directory)
     * @param array $data Variables to pass to the view
     * @param string $layout Layout to use (relative to templates/layouts directory)
     * @return void
     */
    public static function render(string $viewPath, array $data = [], string $layout = 'main.php'): void
    {
        // Convert the data to variables
        extract($data);
        
        // Include the view file but capture its output
        ob_start();
        $fullViewPath = __DIR__ . '/../../templates/' . $viewPath;
        if (!file_exists($fullViewPath)) {
            throw new RuntimeException("View file not found: $fullViewPath");
        }
        include $fullViewPath;
        $content = ob_get_clean();
        
        // Now render the layout with the view content
        $layoutPath = __DIR__ . '/../../templates/layouts/' . $layout;
        if (!file_exists($layoutPath)) {
            throw new RuntimeException("Layout file not found: $layoutPath");
        }
        include $layoutPath;
    }
    
    /**
     * Render a view without a layout
     *
     * @param string $viewPath Path to the view file (relative to templates directory)
     * @param array $data Variables to pass to the view
     * @return void
     */
    public static function renderPartial(string $viewPath, array $data = []): void
    {
        // Convert the data to variables
        extract($data);
        
        // Include the view file directly
        $fullViewPath = __DIR__ . '/../../templates/' . $viewPath;
        if (!file_exists($fullViewPath)) {
            throw new RuntimeException("View file not found: $fullViewPath");
        }
        include $fullViewPath;
    }
} 