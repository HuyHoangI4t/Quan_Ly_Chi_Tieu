<?php
namespace App\Models;

use App\Core\ConnectDB;
use App\Services\FinancialUtils;
use \PDO;

class RecurringTransaction
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    /**
     * Create a new recurring transaction
     */
    public function create($userId, $data)
    {
        // Get category type to normalize amount
        $categoryStmt = $this->db->prepare("SELECT type FROM categories WHERE id = ?");
        $categoryStmt->execute([$data['category_id']]);
        $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);
        
        $finalAmount = FinancialUtils::normalizeAmount($data['amount'], $category['type'] ?? 'expense');
        
        $sql = "INSERT INTO recurring_transactions 
                (user_id, category_id, amount, description, frequency, start_date, end_date, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId,
            $data['category_id'],
            $finalAmount,
            $data['description'] ?? '',
            $data['frequency'],
            $data['start_date'],
            $data['end_date'] ?? null
        ]);
    }

    /**
     * Get all recurring transactions for a user
     */
    public function getAllByUser($userId)
    {
        $sql = "SELECT rt.*, c.name as category_name, c.type as category_type
                FROM recurring_transactions rt
                JOIN categories c ON rt.category_id = c.id
                WHERE rt.user_id = ?
                ORDER BY rt.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a specific recurring transaction
     */
    public function getById($id, $userId)
    {
        $sql = "SELECT rt.*, c.name as category_name, c.type as category_type
                FROM recurring_transactions rt
                JOIN categories c ON rt.category_id = c.id
                WHERE rt.id = ? AND rt.user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update a recurring transaction
     */
    public function update($id, $userId, $data)
    {
        // Get category type to normalize amount
        $categoryStmt = $this->db->prepare("SELECT type FROM categories WHERE id = ?");
        $categoryStmt->execute([$data['category_id']]);
        $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);
        
        $finalAmount = FinancialUtils::normalizeAmount($data['amount'], $category['type'] ?? 'expense');
        
        $sql = "UPDATE recurring_transactions 
                SET category_id = ?, amount = ?, description = ?, 
                    frequency = ?, start_date = ?, end_date = ?
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['category_id'],
            $finalAmount,
            $data['description'] ?? '',
            $data['frequency'],
            $data['start_date'],
            $data['end_date'] ?? null,
            $id,
            $userId
        ]);
    }

    /**
     * Delete a recurring transaction
     */
    public function delete($id, $userId)
    {
        $sql = "DELETE FROM recurring_transactions WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Toggle active status
     */
    public function toggleActive($id, $userId)
    {
        $sql = "UPDATE recurring_transactions 
                SET is_active = NOT is_active 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Get recurring transactions that need to be generated
     */
    public function getPendingGenerations($today = null)
    {
        if ($today === null) {
            $today = date('Y-m-d');
        }

        $sql = "SELECT rt.*, c.type as category_type
                FROM recurring_transactions rt
                JOIN categories c ON rt.category_id = c.id
                WHERE rt.is_active = 1 
                AND rt.start_date <= ?
                AND (rt.end_date IS NULL OR rt.end_date >= ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$today, $today]);
        $recurring = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pending = [];
        foreach ($recurring as $item) {
            if ($this->shouldGenerate($item, $today)) {
                $pending[] = $item;
            }
        }

        return $pending;
    }

    /**
     * Check if a recurring transaction should generate a new transaction
     */
    private function shouldGenerate($recurring, $today)
    {
        $lastGenerated = $recurring['last_generated'];
        
        // If never generated, check if start date has passed
        if ($lastGenerated === null) {
            return strtotime($recurring['start_date']) <= strtotime($today);
        }

        $lastDate = new \DateTime($lastGenerated);
        $currentDate = new \DateTime($today);
        
        switch ($recurring['frequency']) {
            case 'daily':
                $interval = $lastDate->diff($currentDate)->days;
                return $interval >= 1;
            
            case 'weekly':
                $interval = $lastDate->diff($currentDate)->days;
                return $interval >= 7;
            
            case 'monthly':
                // Generate if it's a new month
                return $lastDate->format('Y-m') < $currentDate->format('Y-m');
            
            case 'yearly':
                // Generate if it's a new year
                return $lastDate->format('Y') < $currentDate->format('Y');
            
            default:
                return false;
        }
    }

    /**
     * Generate actual transactions from recurring ones
     */
    public function generateTransactions($transactionModel)
    {
        $pending = $this->getPendingGenerations();
        $generated = 0;
        $today = date('Y-m-d');

        foreach ($pending as $recurring) {
            // Create the actual transaction
            $success = $transactionModel->createTransaction(
                $recurring['user_id'],
                $recurring['category_id'],
                abs($recurring['amount']),
                $recurring['category_type'],
                $today,
                $recurring['description'] . ' (Tự động)'
            );

            if ($success) {
                // Update last_generated date
                $updateSql = "UPDATE recurring_transactions 
                             SET last_generated = ? 
                             WHERE id = ?";
                $stmt = $this->db->prepare($updateSql);
                $stmt->execute([$today, $recurring['id']]);
                $generated++;
            }
        }

        return $generated;
    }
}
