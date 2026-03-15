<?php
/**
 * Database Connection Singleton
 * 
 * Provides a single database connection instance across the application.
 * Usage: $db = Database::getInstance()->getConnection();
 */

namespace App\Models;

class Database
{
    private static ?Database $instance = null;
    private \mysqli $connection;

    private string $host;
    private string $username;
    private string $password;
    private string $database;

    private function __construct()
    {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';
        $this->database = getenv('DB_NAME') ?: 'document';

        $this->connection = new \mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database
        );

        if ($this->connection->connect_error) {
            throw new \Exception("Database connection failed: " . $this->connection->connect_error);
        }

        $this->connection->set_charset('utf8mb4');
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get mysqli connection
     */
    public function getConnection(): \mysqli
    {
        return $this->connection;
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
