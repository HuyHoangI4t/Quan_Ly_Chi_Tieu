<?php

class Transaction
{
    private $db;

    public function __construct()
    {
        // Use the singleton DB instance to match ConnectDB API
        $this->db = ConnectDB::getInstance();
    }

    public function addTransaction($userId, $type, $amount, $categoryId, $date, $description)
    {
        $sql = "INSERT INTO transactions (user_id, type, amount, category_id, transaction_date, description) VALUES (?, ?, ?, ?, ?, ?)";

        // Use ConnectDB->insert which returns lastInsertId on success
        try {
            $lastId = $this->db->insert($sql, [$userId, $type, $amount, $categoryId, $date, $description]);
            return $lastId ? true : false;
        } catch (Exception $e) {
            // Optionally log $e->getMessage()
            return false;
        }
    }

    // You might add other methods here like getTransactions, updateTransaction, deleteTransaction
}
