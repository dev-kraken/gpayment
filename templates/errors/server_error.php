<?php
/**
 * Server Error Template
 * Displayed when server errors occur
 * @var string|null $errorMessage Error message to display
 * @var string|null $stackTrace Stack trace for debugging
 * @var bool $debug Whether to show detailed error information
 */
$empty_layout = true; // Signal that this doesn't use a layout
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Server Error</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
        .error { color: #e74c3c; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 4px; overflow: auto; max-height: 400px; }
    </style>
</head>
<body>
    <h1 class="error">Server Error</h1>
    
    <?php if ($debug && $errorMessage): ?>
        <p><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php if ($stackTrace): ?>
            <h3>Stack Trace:</h3>
            <pre><?php echo htmlspecialchars($stackTrace); ?></pre>
        <?php endif; ?>
    <?php else: ?>
        <p>An internal error occurred. Please try again later.</p>
    <?php endif; ?>
</body>
</html> 