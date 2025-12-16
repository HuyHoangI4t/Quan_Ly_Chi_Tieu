<?php
namespace App\Models;

use App\Core\ConnectDB;
use App\Models\Transaction; // Import Transaction Model
use PDO;

class RecurringTransactions
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    // ... (Giữ nguyên các hàm get, create, update, delete cũ) ...
    // ... Chỉ thay đổi hàm process bên dưới ...

    public function getByUser($userId) { /* Code cũ giữ nguyên hoặc copy từ file cũ */ 
        $sql = "SELECT rt.*, c.name as category_name, c.type, c.color FROM recurring_transactions rt JOIN categories c ON rt.category_id = c.id WHERE rt.user_id = ?";
        $stmt = $this->db->prepare($sql); $stmt->execute([$userId]); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function create($data) { /* Code cũ giữ nguyên */ 
         $sql = "INSERT INTO recurring_transactions (user_id, category_id, amount, type, description, frequency, start_date, end_date, next_occurrence, is_active) VALUES (?,?,?,?,?,?,?,?,?,?)";
         $stmt=$this->db->prepare($sql); $stmt->execute(array_values($data)); return $this->db->lastInsertId();
    }

    public function getDueTransactions() {
        $sql = "SELECT * FROM recurring_transactions WHERE is_active = 1 AND next_occurrence <= CURDATE() AND (end_date IS NULL OR end_date >= CURDATE())";
        $stmt = $this->db->prepare($sql); $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Xử lý giao dịch định kỳ (Đã nâng cấp Auto Deposit)
     */
    public function process($recurring)
    {
        // 1. Tạo giao dịch mới thông qua Transaction Model
        // Việc này đảm bảo logic Auto Deposit được kích hoạt
        $transModel = new Transaction();
        
        $transData = [
            'user_id' => $recurring['user_id'],
            'category_id' => $recurring['category_id'],
            'amount' => $recurring['amount'], // Giả sử amount trong recurring đã đúng dấu
            'date' => $recurring['next_occurrence'],
            'description' => $recurring['description'] . ' (Tự động)',
            'type' => $recurring['type']
        ];
        
        $transModel->create($transData); // Gọi hàm create của Transaction để nó tự lo việc cộng tiền

        // 2. Cập nhật ngày tiếp theo
        $nextOccurrence = $this->calculateNextOccurrence($recurring['next_occurrence'], $recurring['frequency']);
        $updateSql = "UPDATE recurring_transactions SET next_occurrence = ? WHERE id = ?";
        $this->db->prepare($updateSql)->execute([$nextOccurrence, $recurring['id']]);
        
        return true;
    }

    private function calculateNextOccurrence($currentDate, $frequency)
    {
        $date = new \DateTime($currentDate);
        switch ($frequency) {
            case 'daily': $date->modify('+1 day'); break;
            case 'weekly': $date->modify('+1 week'); break;
            case 'monthly': $date->modify('+1 month'); break;
            case 'yearly': $date->modify('+1 year'); break;
        }
        return $date->format('Y-m-d');
    }
}