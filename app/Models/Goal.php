<?php
namespace App\Models;

use App\Core\ConnectDB;
use PDO;

class Goal {
    private $db;
    
    public function __construct() {
        $connectDB = new ConnectDB();
        $this->db = $connectDB->getConnection();
        // Ensure schema has current_amount column (added when removing goal_transactions)
        $this->ensureCurrentAmountColumn();
    }

    /**
     * Ensure goals table has `current_amount` column; add if missing.
     */
    private function ensureCurrentAmountColumn()
    {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM goals LIKE 'current_amount'");
            $stmt->execute();
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$col) {
                // Add column safely
                $this->db->exec("ALTER TABLE goals ADD COLUMN current_amount DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER target_amount");
            }
        } catch (\Exception $e) {
            // ignore failures; higher-level code will handle errors
        }
    }
    
    /**
     * Lấy danh sách mục tiêu (Logic THỦ CÔNG: Tính tổng từ bảng goal_transactions)
     */
    public function getByUserId($userId) {
        $sql = "SELECT g.*, COALESCE(g.current_amount, 0) as current_amount,
                       CASE WHEN g.target_amount > 0 THEN ROUND((COALESCE(g.current_amount,0) / g.target_amount) * 100, 2) ELSE 0 END as progress_percentage
                FROM goals g
                WHERE g.user_id = :user_id
                ORDER BY g.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy chi tiết 1 Goal (Logic thủ công)
     */
    public function getById($id, $userId) {
        $sql = "SELECT g.*, COALESCE(g.current_amount,0) as current_amount,
                       CASE WHEN g.target_amount > 0 THEN ROUND((COALESCE(g.current_amount,0) / g.target_amount) * 100, 2) ELSE 0 END as progress_percentage
                FROM goals g
                WHERE g.id = :id AND g.user_id = :user_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        $goal = $stmt->fetch(PDO::FETCH_ASSOC);
        return $goal ?: false;
    }
    
    /**
     * Hàm nạp tiền vào mục tiêu (Quan trọng nhất)
     */
    public function deposit($userId, $goalId, $amount, $date, $note) {
        try {
            // Prevent rapid duplicate deposits (double-submit protection)
            $checkSql = "SELECT t.id FROM transactions t
                         WHERE t.user_id = :uid AND t.amount = :amount AND t.date = :date
                         AND t.description LIKE 'Nạp mục tiêu:%' AND t.created_at >= DATE_SUB(NOW(), INTERVAL 5 SECOND) LIMIT 1";
            $chk = $this->db->prepare($checkSql);
            $chk->execute([':uid' => $userId, ':amount' => -abs($amount), ':date' => $date]);
            $existing = $chk->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                return true;
            }

            $this->db->beginTransaction();

            // 1. Lấy thông tin goal để biết target và tên
            $stmtGoal = $this->db->prepare("SELECT target_amount, current_amount, name FROM goals WHERE id = ? FOR UPDATE");
            $stmtGoal->execute([$goalId]);
            $goal = $stmtGoal->fetch(PDO::FETCH_ASSOC);
            if (!$goal) {
                $this->db->rollBack();
                return false;
            }

            // Normalize and limit deposit so it doesn't exceed remaining
            $current = floatval($goal['current_amount'] ?? 0);
            $target = floatval($goal['target_amount'] ?? 0);
            $remaining = max(0, $target - $current);
            $originalAmount = $amount;
            $wasAdjusted = false;
            if ($amount > $remaining) {
                $amount = $remaining;
                $wasAdjusted = true;
            }

            // 2. Tạo giao dịch Expense (để trừ tiền ví chính)
            $transactionAmount = -abs($amount);
            $catId = $goal['category_id'] ?? 1;
            $sqlTrans = "INSERT INTO transactions (user_id, category_id, amount, date, description, type, created_at) 
                         VALUES (:uid, :cid, :amount, :date, :desc, 'expense', NOW())";
            $stmtTrans = $this->db->prepare($sqlTrans);
            $stmtTrans->execute([
                ':uid' => $userId,
                ':cid' => $catId,
                ':amount' => $transactionAmount,
                ':date' => $date,
                ':desc' => "Nạp mục tiêu: " . ($note ? $note : $goal['name'])
            ]);
            $transactionId = $this->db->lastInsertId();

            // 3. Cập nhật trực tiếp vào cột current_amount của bảng goals
            $incStmt = $this->db->prepare("UPDATE goals SET current_amount = COALESCE(current_amount,0) + :inc WHERE id = :id");
            $incStmt->execute([':inc' => $amount, ':id' => $goalId]);

            // Nếu đã đạt mục tiêu thì đặt trạng thái completed
            if ($target > 0) {
                $newCurrent = $current + $amount;
                if ($newCurrent >= $target) {
                    $this->db->prepare("UPDATE goals SET status = 'completed' WHERE id = ?")->execute([$goalId]);
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    // Giữ nguyên các hàm create, update, delete cũ...
    public function create($data) {
        // Guard against rapid duplicate goal creation (double-submit)
        $dupSql = "SELECT id FROM goals WHERE user_id = :user_id AND name = :name AND target_amount = :target_amount
                   AND deadline = :deadline AND created_at >= DATE_SUB(NOW(), INTERVAL 5 SECOND) LIMIT 1";
        $dupStmt = $this->db->prepare($dupSql);
        $dupStmt->execute([
            ':user_id' => $data['user_id'],
            ':name' => $data['name'],
            ':target_amount' => $data['target_amount'],
            ':deadline' => $data['deadline']
        ]);
        if ($dupStmt->fetch(PDO::FETCH_ASSOC)) {
            return true; // consider duplicate as success to avoid creating twice
        }

        // Detect existing columns in `goals` table so we only insert supported fields
        $availableCols = [];
        try {
            $colStmt = $this->db->query("SHOW COLUMNS FROM goals");
            $cols = $colStmt->fetchAll(PDO::FETCH_COLUMN, 0);
            if (is_array($cols)) $availableCols = $cols;
        } catch (\Exception $e) {
            // If table doesn't exist or SHOW failed, fallback to expected set
            $availableCols = ['user_id','name','description','target_amount','start_date','deadline','category_id','status','created_at'];
        }

        $fields = [];
        $placeholders = [];
        $bindings = [];

        $candidates = [
            'user_id','name','description','target_amount','start_date','deadline','category_id','status'
        ];

        foreach ($candidates as $col) {
            if (in_array($col, $availableCols, true) && isset($data[$col])) {
                $fields[] = $col;
                $placeholders[] = ':' . $col;
                $bindings[':' . $col] = $data[$col];
            }
        }

        // Always add created_at if availableCols contains it (handled by DB default otherwise)
        if (in_array('created_at', $availableCols, true) && !in_array('created_at', $fields, true)) {
            // do not bind created_at, let DB default NOW() if column has default
        }

        if (empty($fields)) return false;

        $sql = "INSERT INTO goals (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        foreach ($bindings as $k => $v) {
            // Bind types: numeric vs string
            if (is_int($v)) $stmt->bindValue($k, $v, PDO::PARAM_INT);
            else $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        return $stmt->execute();
    }

    public function update($id, $userId, $data) {
        // Build dynamic UPDATE SET based on available columns
        $availableCols = [];
        try {
            $colStmt = $this->db->query("SHOW COLUMNS FROM goals");
            $cols = $colStmt->fetchAll(PDO::FETCH_COLUMN, 0);
            if (is_array($cols)) $availableCols = $cols;
        } catch (\Exception $e) {
            $availableCols = ['name','description','target_amount','start_date','deadline','category_id','status','updated_at'];
        }

        $setParts = [];
        $bindings = [':id' => $id, ':user_id' => $userId];
        $candidates = ['name','description','target_amount','start_date','deadline','category_id','status'];
        foreach ($candidates as $col) {
            if (in_array($col, $availableCols, true) && array_key_exists($col, $data)) {
                $setParts[] = "$col = :$col";
                $bindings[':' . $col] = $data[$col];
            }
        }

        if (empty($setParts)) return false;

        // Add updated_at if column exists
        if (in_array('updated_at', $availableCols, true)) {
            $setParts[] = "updated_at = NOW()";
        }

        $sql = "UPDATE goals SET " . implode(', ', $setParts) . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        foreach ($bindings as $k => $v) {
            if ($k === ':id' || $k === ':user_id' || (is_int($v) && $v !== null)) $stmt->bindValue($k, $v, PDO::PARAM_INT);
            else $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        return $stmt->execute();
    }

    public function delete($id, $userId) {
        try {
            $this->db->beginTransaction();
            // Chỉ xóa bản ghi mục tiêu; không xóa giao dịch trong lịch sử
            $delGoal = $this->db->prepare("DELETE FROM goals WHERE id = :id AND user_id = :user_id");
            $delGoal->execute([':id' => $id, ':user_id' => $userId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            try { $this->db->rollBack(); } catch (\Exception $ex) {}
            return false;
        }
    }
    
    public function getStatistics($userId) {
        // Logic thống kê đơn giản
        $goals = $this->getByUserId($userId);
        $totalSaved = 0;
        $active = 0; $completed = 0; $target = 0;
        foreach($goals as $g) {
            $totalSaved += $g['current_amount'];
            $target += $g['target_amount'];
            if($g['status'] == 'active') $active++;
            if($g['status'] == 'completed') $completed++;
        }
        return ['total_goals'=>count($goals), 'active_goals'=>$active, 'completed_goals'=>$completed, 'total_target'=>$target, 'total_saved'=>$totalSaved];
    }

    /**
     * Withdraw full saved amount from a goal back to main balance.
     * This will create a positive income transaction for the user and remove
     * linked "deposit" transactions that were used to fund the goal.
     */
    public function withdraw($userId, $goalId)
    {
        try {
            $goal = $this->getById($goalId, $userId);
            if (!$goal) return false;

            $currentAmount = isset($goal['current_amount']) ? floatval($goal['current_amount']) : 0.0;
            if ($currentAmount <= 0) return false;

            $this->db->beginTransaction();

            // Use the goal's category if available, otherwise fallback to 1
            $catId = $goal['category_id'] ?? 1;

            // 1) Create income transaction to represent returning money to main balance
            $sqlInc = "INSERT INTO transactions (user_id, category_id, amount, date, description, type, created_at)
                       VALUES (:uid, :cid, :amount, :date, :desc, 'income', NOW())";
            $stmtInc = $this->db->prepare($sqlInc);
            $stmtInc->execute([
                ':uid' => $userId,
                ':cid' => $catId,
                ':amount' => abs($currentAmount),
                ':date' => date('Y-m-d'),
                ':desc' => 'Rút mục tiêu: ' . ($goal['name'] ?? '')
            ]);

            // 2) Remove linked deposit transactions (those tied to this goal and with description 'Nạp mục tiêu:%')
            // Clear goal's saved amount without deleting user's transaction history
            $update = $this->db->prepare("UPDATE goals SET current_amount = 0 WHERE id = ?");
            $update->execute([$goalId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            try { $this->db->rollBack(); } catch (\Exception $ex) {}
            return false;
        }
    }
}