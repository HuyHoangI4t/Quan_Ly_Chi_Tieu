<?php

namespace App\Models;

use App\Core\ConnectDB;
use PDO;

class Transaction
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy danh sách có bộ lọc (cho trang Giao dịch)
    public function getAllByUser($userId, $filters = [])
    {
        $sql = "SELECT t.*, c.name as category_name, c.icon as category_icon, c.type as category_type 
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = :user_id";

        $params = [':user_id' => $userId];

        if (!empty($filters['range'])) {
            $sql .= " AND DATE_FORMAT(t.date, '%Y-%m') = :range";
            $params[':range'] = $filters['range'];
        }

        if (!empty($filters['category_id']) && $filters['category_id'] !== 'all') {
            $sql .= " AND t.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }

        $sort = $filters['sort'] ?? 'newest';
        if ($sort === 'oldest') {
            $sql .= " ORDER BY t.date ASC, t.created_at ASC";
        } else {
            $sql .= " ORDER BY t.date DESC, t.created_at DESC";
        }

        // Phân trang
        if (isset($filters['limit']) && isset($filters['offset'])) {
            $sql .= " LIMIT " . intval($filters['limit']) . " OFFSET " . intval($filters['offset']);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Đếm tổng số bản ghi (để tính số trang)
    public function getCount($userId, $filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM transactions t WHERE t.user_id = :user_id";
        $params = [':user_id' => $userId];

        if (!empty($filters['range'])) {
            $sql .= " AND DATE_FORMAT(t.date, '%Y-%m') = :range";
            $params[':range'] = $filters['range'];
        }
        if (!empty($filters['category_id']) && $filters['category_id'] !== 'all') {
            $sql .= " AND t.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    // [FIX QUAN TRỌNG] Thêm logic INSERT dữ liệu vào đây
    public function create($data)
    {
        $sql = "INSERT INTO transactions (user_id, category_id, amount, date, description, type, created_at) 
                VALUES (:user_id, :category_id, :amount, :date, :description, :type, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':category_id' => $data['category_id'],
            ':amount' => $data['amount'],
            ':date' => $data['date'],
            ':description' => $data['description'],
            ':type' => $data['type']
        ]);
    }

    public function update($id, $data)
    {
        $sql = "UPDATE transactions SET 
                amount = :amount, 
                category_id = :category_id, 
                date = :date, 
                description = :description,
                type = :type
                WHERE id = :id";
        
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function deleteTransaction($id, $userId)
    {
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    // --- CÁC HÀM THỐNG KÊ (GIỮ NGUYÊN ĐỂ DASHBOARD CHẠY) ---
    public function getTotalBalance($userId) {
        $stmt = $this->db->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    public function getTotalsForPeriod($userId, $startDate, $endDate) {
        $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) as income,
                    COALESCE(SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END),0) as expense
                FROM transactions WHERE user_id = ? AND date BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'income' => floatval($row['income'] ?? 0),
            'expense' => floatval($row['expense'] ?? 0)
        ];
    }

    public function getRecentTransactions($userId, $limit = 5) {
        $sql = "SELECT t.*, c.name as category_name 
                FROM transactions t JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? ORDER BY t.date DESC, t.created_at DESC LIMIT " . intval($limit);
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryBreakdown($userId, $startDate, $endDate, $type = null) {
        $sql = "SELECT c.name, COALESCE(SUM(ABS(t.amount)),0) as total FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? AND t.date BETWEEN ? AND ?";
        $params = [$userId, $startDate, $endDate];
        if ($type) { $sql .= " AND t.type = ?"; $params[] = $type; }
        $sql .= " GROUP BY c.id ORDER BY total DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLineChartData($userId, $months = 6) {
        $labels = []; $income = []; $expense = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $m = new \DateTime("first day of -$i months");
            $labels[] = $m->format('M Y');
            $ym = $m->format('Y-m');
            $sql = "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) as inc,
                           COALESCE(SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END),0) as exp
                    FROM transactions WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $ym]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $income[] = $row['inc']; $expense[] = $row['exp'];
        }
        return ['labels' => $labels, 'income' => $income, 'expense' => $expense];
    }
}