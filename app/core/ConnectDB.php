<?php
/**
 * Database Connection Class
 * Handles MySQL database connections using PDO
 */

class ConnectDB
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new ConnectDB();
        }
        return self::$instance;
    }

    /**
     * Get PDO instance
     * @return PDO
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Execute a query
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Get single row
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public function fetch($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Get all rows
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insert data and return last insert ID
     * @param string $sql
     * @param array $params
     * @return int
     */
    public function insert($sql, $params = [])
    {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update data
     * @param string $sql
     * @param array $params
     * @return int
     */
    public function update($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete data
     * @param string $sql
     * @param array $params
     * @return int
     */
    public function delete($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
}
