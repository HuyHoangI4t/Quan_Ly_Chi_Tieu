<?php
namespace App\Models;

use App\Core\ConnectDB;
use App\Services\FinancialUtils;
use \PDO;

class Budget
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    /**
     * Create a new budget
     */
    public function create($userId, $data)
    {
        $sql = "INSERT INTO budgets (user_id, category_id, limit_amount, period) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE limit_amount = VALUES(limit_amount)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId,
            $data['category_id'],
            $data['limit_amount'],
            $data['period']
        ]);
    }

    /**
     * Get all budgets for a user in a specific period
     */
    public function getAllByUser($userId, $period = null)
    {
        if ($period === null) {
            $period = date('Y-m');
        }

        $sql = "SELECT 
                    b.id,
                    b.user_id,
                    b.category_id,
                    c.name AS category_name,
                    c.type AS category_type,
                    b.limit_amount,
                    b.period,
                    COALESCE(SUM(ABS(t.amount)), 0) AS spent_amount,
                    b.limit_amount - COALESCE(SUM(ABS(t.amount)), 0) AS remaining_amount,
                    ROUND((COALESCE(SUM(ABS(t.amount)), 0) / b.limit_amount) * 100, 2) AS percentage_used,
                    b.created_at,
                    b.updated_at
                FROM budgets b
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN transactions t ON t.user_id = b.user_id 
                    AND t.category_id = b.category_id 
                    AND t.amount < 0
                    AND DATE_FORMAT(t.transaction_date, '%Y-%m') = b.period
                WHERE b.user_id = ? AND b.period = ?
                GROUP BY b.id, b.user_id, b.category_id, c.name, c.type, b.limit_amount, b.period, b.created_at, b.updated_at
                ORDER BY percentage_used DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $period]);
        $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate status for each budget
        foreach ($budgets as &$budget) {
            $progress = FinancialUtils::calculateBudgetProgress(
                $budget['spent_amount'],
                $budget['limit_amount']
            );
            $budget['status'] = $progress['status'];
        }

        return $budgets;
    }

    /**
     * Get a specific budget
     */
    public function getById($id, $userId)
    {
        $sql = "SELECT 
                    b.id,
                    b.user_id,
                    b.category_id,
                    c.name AS category_name,
                    c.type AS category_type,
                    b.limit_amount,
                    b.period,
                    COALESCE(SUM(ABS(t.amount)), 0) AS spent_amount,
                    b.created_at,
                    b.updated_at
                FROM budgets b
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN transactions t ON t.user_id = b.user_id 
                    AND t.category_id = b.category_id 
                    AND t.amount < 0
                    AND DATE_FORMAT(t.transaction_date, '%Y-%m') = b.period
                WHERE b.id = ? AND b.user_id = ?
                GROUP BY b.id, b.user_id, b.category_id, c.name, c.type, b.limit_amount, b.period, b.created_at, b.updated_at";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $userId]);
        $budget = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($budget) {
            $progress = FinancialUtils::calculateBudgetProgress(
                $budget['spent_amount'],
                $budget['limit_amount']
            );
            $budget['percentage_used'] = $progress['percentage'];
            $budget['remaining_amount'] = $progress['remaining'];
            $budget['status'] = $progress['status'];
        }

        return $budget;
    }

    /**
     * Update a budget
     */
    public function update($id, $userId, $data)
    {
        $sql = "UPDATE budgets 
                SET category_id = ?, limit_amount = ?, period = ?
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['category_id'],
            $data['limit_amount'],
            $data['period'],
            $id,
            $userId
        ]);
    }

    /**
     * Delete a budget
     */
    public function delete($id, $userId)
    {
        $sql = "DELETE FROM budgets WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Get budget summary for a user
     */
    public function getSummary($userId, $period = null)
    {
        if ($period === null) {
            $period = date('Y-m');
        }

        $budgets = $this->getAllByUser($userId, $period);
        
        $totalLimit = 0;
        $totalSpent = 0;
        $categoriesOverBudget = 0;
        $categoriesWarning = 0;

        foreach ($budgets as $budget) {
            $totalLimit += $budget['limit_amount'];
            $totalSpent += $budget['spent_amount'];
            
            if ($budget['status'] === 'exceeded') {
                $categoriesOverBudget++;
            } elseif ($budget['status'] === 'warning') {
                $categoriesWarning++;
            }
        }

        $overallProgress = FinancialUtils::calculateBudgetProgress($totalSpent, $totalLimit);

        return [
            'total_budgets' => count($budgets),
            'total_limit' => $totalLimit,
            'total_spent' => $totalSpent,
            'total_remaining' => $totalLimit - $totalSpent,
            'overall_percentage' => $overallProgress['percentage'],
            'overall_status' => $overallProgress['status'],
            'categories_over_budget' => $categoriesOverBudget,
            'categories_warning' => $categoriesWarning,
            'period' => $period
        ];
    }

    /**
     * Check if a category is over budget for a specific period
     */
    public function isOverBudget($userId, $categoryId, $period = null)
    {
        if ($period === null) {
            $period = date('Y-m');
        }

        $sql = "SELECT 
                    b.limit_amount,
                    COALESCE(SUM(ABS(t.amount)), 0) AS spent_amount
                FROM budgets b
                LEFT JOIN transactions t ON t.user_id = b.user_id 
                    AND t.category_id = b.category_id 
                    AND t.amount < 0
                    AND DATE_FORMAT(t.transaction_date, '%Y-%m') = b.period
                WHERE b.user_id = ? AND b.category_id = ? AND b.period = ?
                GROUP BY b.limit_amount";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $categoryId, $period]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return false; // No budget set for this category
        }

        return $result['spent_amount'] >= $result['limit_amount'];
    }

    /**
     * Get available periods (months with budgets)
     */
    public function getAvailablePeriods($userId)
    {
        $sql = "SELECT DISTINCT period 
                FROM budgets 
                WHERE user_id = ? 
                ORDER BY period DESC 
                LIMIT 12";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
