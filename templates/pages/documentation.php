<?php
/**
 * Documentation page for GPayments 3DS Integration
 *
 * @author DevKraken <soman@devkraken.com>
 * @copyright 2025 DevKraken
 * @license Proprietary
 */
$currentPage = 'documentation';
?>
<div class="container py-4">
    <h1 class="mb-4">GPayments 3DS Integration Documentation</h1>
    <hr>
    <section id="overview">
        <h2>Overview</h2>
        <p>
            This project is a modern, secure, and modular PHP application for handling 3D Secure (3DS) credit card payments using GPayments. It features a clean separation of concerns, robust error handling, and a user-friendly frontend with real-time validation.
        </p>
    </section>
    <hr>
    <section id="structure">
        <h2>Project Structure</h2>
        <pre><code>/config           - Configuration files
/public           - Web-accessible files (entry point, assets)
/src              - Application source code (Controllers, Services, Helpers)
/templates        - HTML templates (layouts, pages, partials)
/tests            - PHPUnit test cases
/cache, /logs     - Runtime directories
</code></pre>
    </section>
    <hr>
    <section id="backend">
        <h2>Backend (PHP)</h2>
        <ul>
            <li><strong>Routing:</strong> Centralized in <code>config/routes.php</code> and handled by a custom <code>Router</code> class.</li>
            <li><strong>Controllers:</strong> <code>ApiController</code> and <code>NotificationController</code> handle API and notification logic.</li>
            <li><strong>Services:</strong> <code>ThreeDSService</code> encapsulates 3DS authentication logic and GPayments API calls.</li>
            <li><strong>Helpers:</strong> Utility classes for logging, HTTP, security (CSRF, sanitization), caching, and templating.</li>
            <li><strong>Error Handling:</strong> Custom exceptions, error templates, and robust logging using <code>LogHelper</code>.</li>
            <li><strong>Testing:</strong> PHPUnit test suite for core helpers and services.</li>
        </ul>
    </section>
    <hr>
    <section id="frontend">
        <h2>Frontend (JavaScript & UI)</h2>
        <ul>
            <li><strong>Modern UI:</strong> Bootstrap 5-based responsive design.</li>
            <li><strong>3DS Logic:</strong> <code>assets/js/3ds.js</code> handles the 3DS payment flow, API calls, and challenge handling.</li>
            <li><strong>Input Validation & Masking:</strong> <code>assets/js/input-validation.js</code> provides real-time validation and masking for credit card number, expiry date, and CVV fields. Features:
                <ul>
                    <li>Credit card number masking (spaces every 4 digits), Luhn check, and max length enforcement.</li>
                    <li>Expiry date masking (MM/YY), prevents past dates, and validates month/year.</li>
                    <li>CVV restricted to 3-4 digits, numeric only.</li>
                    <li>Visual feedback (green/red borders) for valid/invalid input.</li>
                </ul>
            </li>
            <li><strong>Separation of Concerns:</strong> Validation logic is modular and reusable, exposed globally for integration with main payment logic.</li>
        </ul>
    </section>
    <hr>
    <section id="security">
        <h2>Security Features</h2>
        <ul>
            <li>CSRF protection and input sanitization via <code>SecurityHelper</code>.</li>
            <li>Rate limiting and CORS headers in <code>HttpHelper</code>.</li>
            <li>Environment variable validation and secure error handling in <code>index.php</code>.</li>
            <li>Server-side and client-side validation for all sensitive fields.</li>
            <li>Comprehensive logging for all critical actions and errors.</li>
        </ul>
    </section>
    <hr>
    <section id="developer-notes">
        <h2>Developer Notes</h2>
        <ul>
            <li>All code follows PSR-4 autoloading and modern PHP best practices.</li>
            <li>Frontend and backend are decoupled for easier maintenance and testing.</li>
            <li>All sensitive data and credentials are managed via <code>.env</code> files (never committed).</li>
            <li>Contributions and improvements are welcome. See <code>README.md</code> for setup instructions.</li>
            <li>Primary developer: <strong>DevKraken</strong> &lt;soman@devkraken.com&gt;</li>
        </ul>
    </section>
    <hr>
    <section id="changelog">
        <h2>Recent Improvements</h2>
        <ul>
            <li>Added modular input validation and masking for payment form fields.</li>
            <li>Improved error handling and logging throughout the stack.</li>
            <li>Enhanced security with CSRF, rate limiting, and environment validation.</li>
            <li>Refactored codebase for maintainability and extensibility.</li>
        </ul>
    </section>
</div> 