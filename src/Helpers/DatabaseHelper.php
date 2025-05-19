<?php
declare(strict_types=1);

namespace App\Helpers;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Database Helper for database operations
 *
 * @author DevKraken <soman@devkraken.com>
 * @copyright 2025 DevKraken
 * @license Proprietary
 */
class DatabaseHelper
{
    /**
     * @var PDO|null PDO instance
     */
    private static ?PDO $pdo = null;
    
    /**
     * @var array PDO options
     */
    private static array $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    /**
     * Initialize PDO connection
     *
     * @param string $dsn DSN connection string
     * @param string|null $username Database username
     * @param string|null $password Database password
     * @param array|null $options PDO options
     * @return bool Whether the connection was successful
     */
    public static function connect(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null): bool
    {
        if ($options !== null) {
            self::$options = array_merge(self::$options, $options);
        }
        
        try {
            self::$pdo = new PDO($dsn, $username, $password, self::$options);
            return true;
        } catch (PDOException $e) {
            LogHelper::error("Database connection error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if database connection is active
     *
     * @return bool True if connected
     */
    public static function isConnected(): bool
    {
        return self::$pdo !== null;
    }
    
    /**
     * Execute a query with parameters
     *
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return PDOStatement|false PDO statement or false on failure
     */
    public static function query(string $query, array $params = [])
    {
        if (!self::isConnected()) {
            LogHelper::error("Database query failed: No active connection");
            return false;
        }
        
        try {
            $statement = self::$pdo->prepare($query);
            $statement->execute($params);
            return $statement;
        } catch (PDOException $e) {
            LogHelper::error("Database query error: " . $e->getMessage(), [
                'query' => $query,
                'params' => $params
            ]);
            return false;
        }
    }
    
    /**
     * Fetch all rows from a query
     *
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return array|false Rows or false on failure
     */
    public static function fetchAll(string $query, array $params = [])
    {
        $statement = self::query($query, $params);
        
        if ($statement === false) {
            return false;
        }
        
        return $statement->fetchAll();
    }
    
    /**
     * Fetch a single row from a query
     *
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return array|false Row or false on failure
     */
    public static function fetchRow(string $query, array $params = [])
    {
        $statement = self::query($query, $params);
        
        if ($statement === false) {
            return false;
        }
        
        return $statement->fetch();
    }
    
    /**
     * Fetch a single column value from a query
     *
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return mixed|false Column value or false on failure
     */
    public static function fetchColumn(string $query, array $params = [])
    {
        $statement = self::query($query, $params);
        
        if ($statement === false) {
            return false;
        }
        
        return $statement->fetchColumn();
    }
    
    /**
     * Insert data into a table
     *
     * @param string $table Table name
     * @param array $data Data to insert (column => value)
     * @return int|false Last insert ID or false on failure
     */
    public static function insert(string $table, array $data)
    {
        if (empty($data)) {
            return false;
        }
        
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $query = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        $statement = self::query($query, array_values($data));
        
        if ($statement === false) {
            return false;
        }
        
        return self::$pdo->lastInsertId();
    }
    
    /**
     * Update data in a table
     *
     * @param string $table Table name
     * @param array $data Data to update (column => value)
     * @param string $where Where clause
     * @param array $whereParams Where parameters
     * @return int|false Number of affected rows or false on failure
     */
    public static function update(string $table, array $data, string $where, array $whereParams = [])
    {
        if (empty($data)) {
            return false;
        }
        
        $sets = [];
        foreach ($data as $column => $value) {
            $sets[] = "$column = ?";
        }
        
        $query = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $sets),
            $where
        );
        
        $params = array_merge(array_values($data), $whereParams);
        $statement = self::query($query, $params);
        
        if ($statement === false) {
            return false;
        }
        
        return $statement->rowCount();
    }
    
    /**
     * Delete data from a table
     *
     * @param string $table Table name
     * @param string $where Where clause
     * @param array $params Where parameters
     * @return int|false Number of affected rows or false on failure
     */
    public static function delete(string $table, string $where, array $params = [])
    {
        $query = sprintf("DELETE FROM %s WHERE %s", $table, $where);
        $statement = self::query($query, $params);
        
        if ($statement === false) {
            return false;
        }
        
        return $statement->rowCount();
    }
    
    /**
     * Begin a transaction
     *
     * @return bool Success state
     */
    public static function beginTransaction(): bool
    {
        if (!self::isConnected()) {
            return false;
        }
        
        return self::$pdo->beginTransaction();
    }
    
    /**
     * Commit a transaction
     *
     * @return bool Success state
     */
    public static function commit(): bool
    {
        if (!self::isConnected()) {
            return false;
        }
        
        return self::$pdo->commit();
    }
    
    /**
     * Rollback a transaction
     *
     * @return bool Success state
     */
    public static function rollback(): bool
    {
        if (!self::isConnected()) {
            return false;
        }
        
        return self::$pdo->rollBack();
    }
    
    /**
     * Close the database connection
     *
     * @return void
     */
    public static function disconnect(): void
    {
        self::$pdo = null;
    }
} 