<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Log Helper class for comprehensive application logging
 *
 * @author DevKraken <soman@devkraken.com>
 * @copyright 2025 DevKraken
 * @license Proprietary
 */
class LogHelper
{
    // Log levels
    public const DEBUG = 'DEBUG';
    public const INFO = 'INFO';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';
    public const CRITICAL = 'CRITICAL';
    
    /**
     * @var string The log directory
     */
    private static string $logDir = __DIR__ . '/../../logs/';
    
    /**
     * @var string The log file format
     */
    private static string $logFileFormat = 'Y-m-d';
    
    /**
     * @var bool Whether to log to stdout
     */
    private static bool $logToStdout = false;
    
    /**
     * @var string The minimum log level to record
     */
    private static string $minLogLevel = self::DEBUG;
    
    /**
     * Set the log directory
     *
     * @param string $dir The directory to use for logging
     * @return void
     */
    public static function setLogDir(string $dir): void
    {
        self::$logDir = rtrim($dir, '/') . '/';
    }
    
    /**
     * Set whether to log to stdout
     *
     * @param bool $logToStdout Whether to log to stdout
     * @return void
     */
    public static function setLogToStdout(bool $logToStdout): void
    {
        self::$logToStdout = $logToStdout;
    }
    
    /**
     * Set the minimum log level
     *
     * @param string $level The minimum log level
     * @return void
     */
    public static function setMinLogLevel(string $level): void
    {
        self::$minLogLevel = $level;
    }
    
    /**
     * Log a debug message
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log an info message
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        self::log(self::INFO, $message, $context);
    }
    
    /**
     * Log a warning message
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log(self::WARNING, $message, $context);
    }
    
    /**
     * Log an error message
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        self::log(self::ERROR, $message, $context);
    }
    
    /**
     * Log a critical message
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log a message with a specific level
     *
     * @param string $level The log level
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        // Check if level is sufficient to log
        if (!self::shouldLog($level)) {
            return;
        }
        
        self::ensureLogDirectoryExists();
        
        // Format log entry
        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? json_encode($context) : '';
        $logEntry = "[$timestamp] [$level] $message" . ($contextString ? " $contextString" : "") . PHP_EOL;
        
        // Write to log file
        $logFile = self::getLogFilename();
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        // Write to stdout if enabled
        if (self::$logToStdout) {
            echo $logEntry;
        }
    }
    
    /**
     * Determine if a message with the given level should be logged
     *
     * @param string $level The log level
     * @return bool Whether the message should be logged
     */
    private static function shouldLog(string $level): bool
    {
        $levels = [
            self::DEBUG => 0,
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3,
            self::CRITICAL => 4
        ];
        
        return $levels[$level] >= $levels[self::$minLogLevel];
    }
    
    /**
     * Get the log filename for today
     *
     * @return string The log filename
     */
    private static function getLogFilename(): string
    {
        return self::$logDir . date(self::$logFileFormat) . '.log';
    }
    
    /**
     * Ensure that the log directory exists
     *
     * @return void
     */
    private static function ensureLogDirectoryExists(): void
    {
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }
} 