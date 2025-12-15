<?php
namespace App\Core;

class ConnectDB
{
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $connection;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $this->host = $config['host'] ?? 'localhost';
        $this->dbname = $config['dbname'] ?? 'quan_ly_chi_tieu';
        $this->username = $config['username'] ?? 'root';
        $this->password = $config['password'] ?? '';

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $this->connection = new \PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("DB Connection Error: " . $e->getMessage()); 
            // Không die() để App có thể catch và hiển thị trang lỗi đẹp hơn
            throw new \Exception("Không thể kết nối cơ sở dữ liệu.");
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }
}