<?php
/**
 * Configuration Error Template
 * Displayed when required environment variables are missing
 * @var array $missingVars List of missing environment variables
 */
$empty_layout = true; // Signal that this doesn't use a layout
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Configuration Error</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
        .error { color: #e74c3c; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1 class="error">Configuration Error</h1>
    <p>The following required environment variables are missing:</p>
    <pre><?php echo implode(', ', $missingVars); ?></pre>
    <p>Please check your .env file configuration.</p>
</body>
</html> 