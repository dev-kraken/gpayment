<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\CacheHelper;
use PHPUnit\Framework\TestCase;

class CacheHelperTest extends TestCase
{
    /**
     * @var string Temporary cache directory for testing
     */
    private string $tempCacheDir;
    
    /**
     * Setup the test environment
     */
    protected function setUp(): void
    {
        // Create a temporary directory for cache files
        $this->tempCacheDir = sys_get_temp_dir() . '/gpayments_test_cache_' . uniqid();
        mkdir($this->tempCacheDir, 0755, true);
        
        // Set the cache directory to the temporary directory
        CacheHelper::setCacheDir($this->tempCacheDir);
    }
    
    /**
     * Teardown the test environment
     */
    protected function tearDown(): void
    {
        // Clear and remove the temporary cache directory
        CacheHelper::clear();
        
        $files = glob($this->tempCacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        rmdir($this->tempCacheDir);
    }
    
    /**
     * Test that we can set and get an item from the cache
     */
    public function testSetAndGet(): void
    {
        $key = 'test_key';
        $value = ['test' => 'value', 'number' => 42];
        
        $result = CacheHelper::set($key, $value);
        $cached = CacheHelper::get($key);
        
        $this->assertTrue($result);
        $this->assertEquals($value, $cached);
    }
    
    /**
     * Test that non-existent keys return null
     */
    public function testGetNonExistentKey(): void
    {
        $key = 'non_existent_key';
        
        $cached = CacheHelper::get($key);
        
        $this->assertNull($cached);
    }
    
    /**
     * Test that we can delete an item from the cache
     */
    public function testDelete(): void
    {
        $key = 'test_key';
        $value = 'test value';
        
        CacheHelper::set($key, $value);
        
        $result = CacheHelper::delete($key);
        $cached = CacheHelper::get($key);
        
        $this->assertTrue($result);
        $this->assertNull($cached);
    }
    
    /**
     * Test that we can clear the entire cache
     */
    public function testClear(): void
    {
        $keys = ['key1', 'key2', 'key3'];
        
        foreach ($keys as $key) {
            CacheHelper::set($key, 'value for ' . $key);
        }
        
        $result = CacheHelper::clear();
        
        $this->assertTrue($result);
        
        foreach ($keys as $key) {
            $this->assertNull(CacheHelper::get($key));
        }
    }
    
    /**
     * Test that expired items are removed from the cache
     */
    public function testExpiration(): void
    {
        $key = 'expiring_key';
        $value = 'expiring value';
        
        // Set with 1 second expiration
        CacheHelper::set($key, $value, 1);
        
        // Verify it's in the cache
        $this->assertEquals($value, CacheHelper::get($key));
        
        // Wait for expiration
        sleep(2);
        
        // Verify it's expired
        $this->assertNull(CacheHelper::get($key));
    }
} 