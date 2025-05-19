<?php
/**
 * 404 Not Found Error Template
 * Displayed when a requested resource cannot be found
 * @var string|null $requestedPath The path that was requested
 */
$empty_layout = true; // Signal that this doesn't use a layout
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>404 Not Found</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
        .error { color: #e74c3c; }
        .back-link { margin-top: 20px; }
    </style>
</head>
<body>
    <h1 class="error">404 Not Found</h1>
    <p>The requested resource was not found on this server.</p>
    <?php if (isset($requestedPath)): ?>
    <p>Path: <code><?php echo htmlspecialchars($requestedPath); ?></code></p>
    <?php endif; ?>
    <div class="back-link">
        <a href="/">&laquo; Back to Home</a>
    </div>
</body>
</html> 