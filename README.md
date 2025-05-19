# GPayments 3DS Integration

This is a modern PHP implementation of the GPayments 3DS (3D Secure) protocol for secure credit card processing.

## Features

- Complete 3DS protocol implementation (3DS 2.x)
- Browser-based credit card payment form
- Support for challenge flow
- Modern PHP application structure
- JavaScript with robust error handling

## Requirements

- PHP 8.0 or higher
- Composer
- SSL certificates for API communication with GPayments
- Web server with SSL support

## Installation

1. Clone the repository

```bash
git clone https://github.com/yourusername/gpayments-3ds.git
cd gpayments-3ds
```

2. Install dependencies

```bash
composer install
```

3. Set up environment variables

```bash
cp .env.example .env
```

4. Edit `.env` file with your GPayments API credentials and other settings

5. Start the application (for development)

```bash
composer start
```

## Project Structure

```
/config           - Configuration files
  /3ds.php        - 3DS-specific configuration
  /app.php        - Application configuration
  /routes.php     - Route definitions
/public           - Web-accessible files
  /assets         - Static assets (CSS, JS, images)
  /.htaccess      - Apache configuration
  /index.php      - Main entry point
/src              - Application source code
  /Config         - Configuration components (Router, RouteManager)
  /Controllers    - Request handling
  /Exceptions     - Custom exceptions
  /Helpers        - Utility classes
  /Models         - Data models
  /Repositories   - Data access
  /Services       - Business logic
/templates        - HTML templates
  /layouts        - Layout templates
  /pages          - Page templates
  /partials       - Partial templates
  /errors         - Error page templates
/tests            - Unit and integration tests
/vendor           - Composer dependencies
/logs             - Application logs
/cache            - Cache storage
.env              - Environment variables
.env.example      - Example environment variables
composer.json     - Composer configuration
phpunit.xml       - PHPUnit configuration
```

## Code Quality

The codebase follows PHP best practices:

- PSR-4 autoloading standards
- PSR-12 coding style
- Type declarations (PHP 8.0)
- Comprehensive error handling
- Dependency injection
- Separation of concerns
- Unit testing with PHPUnit
- Proper caching for performance optimization

## JavaScript

The client-side implementation:

- Uses ES6+ features
- Includes JSDoc type annotations
- Has comprehensive error handling
- Follows modular design patterns

## API Endpoints

- `/api` - Main API endpoint for 3DS operations
- `/notify` - Notification endpoint for 3DS callbacks

## Key Operations

1. **Initialization** (`action: init`)

   - Prepares a new 3DS transaction
   - Returns 3DS server information

2. **Authentication** (`action: auth`)

   - Authenticates a card with browser information
   - Handles frictionless flow or triggers challenge flow

3. **Challenge Status Update** (`action: updateChallengeStatus`)

   - Updates the status of a challenge flow

4. **Get Authentication Result** (`action: getAuthResult`)
   - Retrieves the final authentication result

## Security

- All API communications use SSL
- Environmental configuration through `.env` files
- Input validation on both client and server
- Proper error handling and logging
- No sensitive information in logs

## Additional Resources

- [3DS 2.0 Specification](https://www.emvco.com/emv-technologies/3-d-secure/)
- [GPayments API Documentation](https://docs.activeserver.cloud/en/api/auth/)

## License

Proprietary - All rights reserved

---

## Developer

This project is maintained by:

**DevKraken**  
Email: soman@devkraken.com

_Last updated: May 19, 2025_

## Security Features

- CSRF protection for all form submissions
- Input sanitization to prevent XSS attacks
- Rate limiting on API endpoints
- Request validation
- Secure error handling that doesn't expose sensitive information
- Environment configuration validation

## Caching System

The application includes a flexible file-based caching system that:

- Caches API responses to improve performance
- Supports customizable expiration times
- Automatically handles cache invalidation
- Can be configured for different environments

## Logging

Comprehensive logging system with:

- Multiple log levels (DEBUG, INFO, WARNING, ERROR, CRITICAL)
- Contextual information in log entries
- Daily log rotation
- Configurable log location
- Optional stdout logging for development

## Testing

The application includes a test suite using PHPUnit:

- Unit tests for isolated component testing
- Integration tests for testing component interaction
- Test fixtures for repeatable test execution
- Mock objects for testing without dependencies
