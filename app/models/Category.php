<?php
namespace App\Models;

use App\Core\ConnectDB;
use App\Services\FinancialUtils;
use \PDO;

class Category
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    /**
     * Get all categories (default + user-specific)
     */
    public function getAll($userId = null)
    {
        if ($userId === null) {
            // Only default categories
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE user_id IS NULL ORDER BY type, name");
            $stmt->execute();
        } else {
            // Default categories + user's custom categories
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE user_id IS NULL OR user_id = ? ORDER BY type, name");
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get expense categories only
     */
    public function getExpenseCategories($userId = null)
    {
        if ($userId === null) {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE type = 'expense' AND user_id IS NULL ORDER BY name");
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE type = 'expense' AND (user_id IS NULL OR user_id = ?) ORDER BY name");
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get income categories only
     */
    public function getIncomeCategories($userId = null)
    {
        if ($userId === null) {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE type = 'income' AND user_id IS NULL ORDER BY name");
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE type = 'income' AND (user_id IS NULL OR user_id = ?) ORDER BY name");
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user's custom categories
     */
    public function getUserCategories($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY type, name");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a category by ID
     */
    public function getById($id, $userId = null)
    {
        if ($userId === null) {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            // User can only access default categories or their own custom categories
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ? AND (user_id IS NULL OR user_id = ?)");
            $stmt->execute([$id, $userId]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new custom category
     */
    public function create($userId, $data)
    {
        $sql = "INSERT INTO categories (user_id, name, type, is_default) VALUES (?, ?, ?, 0)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId,
            $data['name'],
            $data['type']
        ]);
    }

    /**
     * Update a custom category
     * Only user's own categories can be updated (not default ones)
     */
    public function update($id, $userId, $data)
    {
        $sql = "UPDATE categories SET name = ?, type = ? WHERE id = ? AND user_id = ? AND is_default = 0";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['type'],
            $id,
            $userId
        ]);
    }

    /**
     * Delete a custom category
     * Only user's own categories can be deleted (not default ones)
     * Check if category is being used before deleting
     */
    public function delete($id, $userId)
    {
        // Check if category is being used
        $checkStmt = $this->db->prepare("SELECT COUNT(*) as count FROM transactions WHERE category_id = ?");
        $checkStmt->execute([$id]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return false; // Category is being used, cannot delete
        }

        // Delete only if it's user's own category and not default
        $sql = "DELETE FROM categories WHERE id = ? AND user_id = ? AND is_default = 0";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Check if a category name already exists for a user
     */
    public function nameExists($name, $userId, $excludeId = null)
    {
        if ($excludeId) {
            $sql = "SELECT COUNT(*) as count FROM categories WHERE name = ? AND user_id = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$name, $userId, $excludeId]);
        } else {
            $sql = "SELECT COUNT(*) as count FROM categories WHERE name = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$name, $userId]);
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Get categories grouped by type
     */
    public function getAllGrouped($userId = null)
    {
        $categories = $this->getAll($userId);
        $grouped = [
            'income' => [],
            'expense' => []
        ];

        foreach ($categories as $category) {
            $grouped[$category['type']][] = $category;
        }

        return $grouped;
    }
}
