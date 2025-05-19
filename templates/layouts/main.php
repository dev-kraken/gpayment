<?php
/**
 * Main layout template
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? ($config['name'] ?? 'GPayments 3DS Integration'); ?></title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <?php if (isset($extraHeadContent)) echo $extraHeadContent; ?>
</head>
<body>
    <div class="page-wrapper">

        <!-- Header -->
        <?php include __DIR__ . '/../partials/header.php'; ?>

        <!-- Main Content -->
        <main class="content-wrapper container py-4">
            <?php if (isset($content)) echo $content; ?>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <?php include __DIR__ . '/../partials/footer.php'; ?>
        </footer>

    </div>

    <!-- Common scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($scripts)) echo $scripts; ?>
</body>
</html>