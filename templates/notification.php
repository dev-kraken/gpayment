<?php
/**
 * 3DS Notification template
 * This template intentionally doesn't use a layout
 * @var string|null $event Event type
 * @var string|null $param Parameter value (browser info)
 * @var string|null $threeDSServerTransID Server transaction ID
 * @var string|null $requestorTransId Requestor transaction ID
 */
$empty_layout = true; // Signal that this doesn't use a layout
?>
<!DOCTYPE html>
<html>
<head>
    <title>3DS Notification</title>
    <script type="text/javascript">
        // Log event
        console.log('3DS Event received: <?php echo $event ?? 'Unknown'; ?>');
        
        // Prepare data to send to parent window
        const eventData = {
            event: '<?php echo $event ?? 'Unknown'; ?>'
        };

        <?php if (isset($param) && $param): ?>
        // Add browser info if available
        eventData.param = '<?php echo $param; ?>';
        <?php endif; ?>
        
        <?php if (isset($threeDSServerTransID) && $threeDSServerTransID): ?>
        // Add transaction ID if available
        eventData.threeDSServerTransID = '<?php echo $threeDSServerTransID; ?>';
        <?php endif; ?>
        
        <?php if (isset($requestorTransId) && $requestorTransId): ?>
        // Add requestor transaction ID if available
        eventData.requestorTransId = '<?php echo $requestorTransId; ?>';
        <?php endif; ?>
        
        // Send message to parent window
        if (window.parent) {
            window.parent.postMessage(eventData, '*');
        }
    </script>
</head>
<body>
    <p>3DS Event: <?php echo htmlspecialchars($event ?? 'Unknown'); ?></p>
    <?php if (isset($param) && $param): ?>
    <p>Browser Info: Received</p>
    <?php endif; ?>
</body>
</html> 