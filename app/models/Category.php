<?php
namespace App\Models;

use App\Core\ConnectDB;
use \PDO;

class Category
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM categories ORDER BY type, name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
