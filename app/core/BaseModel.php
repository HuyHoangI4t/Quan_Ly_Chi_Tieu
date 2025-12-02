<?php
namespace App\Core;

use PDO;
use PDOStatement;

abstract class BaseModel
{
    protected PDO $db;

    public function __construct(?PDO $connection = null)
    {
        if ($connection instanceof PDO) {
            $this->db = $connection;
        } else {
            $connectDB = new ConnectDB();
            $this->db = $connectDB->getConnection();
        }
    }

    protected function prepare(string $sql): PDOStatement
    {
        return $this->db->prepare($sql);
    }
}
