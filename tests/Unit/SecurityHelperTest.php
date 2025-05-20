<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\SecurityHelper;
use PHPUnit\Framework\TestCase;

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class SecurityHelperTest extends TestCase
{
    /**
     * Setup the test environment
     */
    protected function setUp(): void
    {
        // Capture any output that might be sent before session_start
        ob_start();
        
        // Start a session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        // Clean output buffer
        ob_end_clean();
        
        // Clear session data
        $_SESSION = [];
    }
    
    /**
     * Test that CSRF token generation works
     */
    public function testGenerateCsrfToken(): void
    {
        $token = SecurityHelper::generateCsrfToken();
        
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertArrayHasKey('csrf_tokens', $_SESSION);
        $this->assertArrayHasKey($token, $_SESSION['csrf_tokens']);
    }
    
    /**
     * Test that CSRF token verification works for valid tokens
     */
    public function testVerifyCsrfTokenValidToken(): void
    {
        $token = SecurityHelper::generateCsrfToken();
        
        $isValid = SecurityHelper::verifyCsrfToken($token, false);
        
        $this->assertTrue($isValid);
        $this->assertArrayHasKey($token, $_SESSION['csrf_tokens']);
    }
    
    /**
     * Test that CSRF token verification fails for invalid tokens
     */
    public function testVerifyCsrfTokenInvalidToken(): void
    {
        $token = SecurityHelper::generateCsrfToken();
        $invalidToken = 'invalid-token';
        
        $isValid = SecurityHelper::verifyCsrfToken($invalidToken);
        
        $this->assertFalse($isValid);
    }
    
    /**
     * Test that CSRF token is removed after verification when removeToken is true
     */
    public function testVerifyCsrfTokenRemovesToken(): void
    {
        $token = SecurityHelper::generateCsrfToken();
        
        $isValid = SecurityHelper::verifyCsrfToken($token, true);
        
        $this->assertTrue($isValid);
        $this->assertArrayNotHasKey($token, $_SESSION['csrf_tokens']);
    }
    
    /**
     * Test that sanitizeString properly escapes HTML
     */
    public function testSanitizeString(): void
    {
        $input = '<script>alert("xss")</script>';
        $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;';
        
        $sanitized = SecurityHelper::sanitizeString($input);
        
        $this->assertEquals($expected, $sanitized);
    }
    
    /**
     * Test that validateEmail correctly validates email addresses
     */
    public function testValidateEmail(): void
    {
        $validEmail = 'test@example.com';
        $invalidEmail = 'not-an-email';
        
        $this->assertTrue(SecurityHelper::validateEmail($validEmail));
        $this->assertFalse(SecurityHelper::validateEmail($invalidEmail));
    }
    
    /**
     * Test that validateUrl correctly validates URLs
     */
    public function testValidateUrl(): void
    {
        $validUrl = 'https://example.com';
        $invalidUrl = 'not-a-url';
        
        $this->assertTrue(SecurityHelper::validateUrl($validUrl));
        $this->assertFalse(SecurityHelper::validateUrl($invalidUrl));
    }
} 