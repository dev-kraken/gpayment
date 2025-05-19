<?php
declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Custom exception for 3DS-related errors
 */
class ThreeDSException extends Exception
{
    /**
     * @var string|null Transaction ID associated with the error
     */
    private ?string $transactionId;
    
    /**
     * @var array Additional error context data
     */
    private array $context;
    
    /**
     * @var int HTTP status code for API responses
     */
    private int $httpStatusCode;
    
    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param int $code Error code
     * @param Throwable|null $previous Previous exception
     * @param string|null $transactionId Associated transaction ID
     * @param array $context Additional error context
     * @param int $httpStatusCode HTTP status code (default 500)
     */
    public function __construct(
        string     $message = "",
        int        $code = 0,
        ?Throwable $previous = null,
        ?string    $transactionId = null,
        array      $context = [],
        int        $httpStatusCode = 500
    ) {
        parent::__construct($message, $code, $previous);
        $this->transactionId = $transactionId;
        $this->context = $context;
        $this->httpStatusCode = $httpStatusCode;
    }
    
    /**
     * Get transaction ID
     * 
     * @return string|null Transaction ID
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }
    
    /**
     * Get error context
     * 
     * @return array Error context
     */
    public function getContext(): array
    {
        return $this->context;
    }
    
    /**
     * Get HTTP status code
     * 
     * @return int HTTP status code
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
    
    /**
     * Set HTTP status code
     * 
     * @param int $httpStatusCode HTTP status code
     * @return self
     */
    public function setHttpStatusCode(int $httpStatusCode): self
    {
        $this->httpStatusCode = $httpStatusCode;
        return $this;
    }
    
    /**
     * Add data to error context
     * 
     * @param string $key Context key
     * @param mixed $value Context value
     * @return self
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }
    
    /**
     * Convert exception to array for logging or API responses
     * 
     * @param bool $includeTrace Whether to include stack trace
     * @return array Exception data as array
     */
    public function toArray(bool $includeTrace = false): array
    {
        $data = [
            'message' => $this->getMessage(),
            'code' => $this->getCode()
        ];
        
        if ($this->transactionId) {
            $data['transactionId'] = $this->transactionId;
        }
        
        if (!empty($this->context)) {
            $data['context'] = $this->context;
        }
        
        if ($includeTrace) {
            $data['file'] = $this->getFile();
            $data['line'] = $this->getLine();
            $data['trace'] = $this->getTraceAsString();
        }
        
        return $data;
    }
} 