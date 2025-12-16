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

    // =========================================================================
    // 1. "CON ROBOT" TỰ ĐỘNG TÍNH TIỀN HŨ (CORE LOGIC)
    // =========================================================================

    /**
     * Hàm này tự động chạy ngầm để cộng/trừ tiền vào hũ
     */
    private function syncWallet($userId, $amount, $type, $categoryId, $isRevert = false)
    {
        // Nếu là hoàn tác (khi xóa/sửa), nó sẽ làm ngược lại
        // VD: Lỡ tiêu nhầm 50k -> Hoàn tác là trả lại 50k vào hũ
        if ($isRevert) {
            // For revert operations we want to undo effects safely:
            // - If original was income, subtract the allocations (pass negative amount to distributeIncome)
            // - If original was expense, simply credit the target jar back (do NOT call deductExpense which may redistribute)
            if ($type === 'income') {
                $this->distributeIncome($userId, -$amount);
            } else {
                // For reverting an expense, return the spent amount back to jars
                // according to the user's allocation ratios (so balances reflect ratios)
                $this->distributeIncome($userId, abs($amount));
            }
            return;
        }

        if ($type === 'income') {
            // NẾU LÀ THU NHẬP: Tự động chia vào 6 hũ
            $this->distributeIncome($userId, $amount);
        } else {
            // NẾU LÀ CHI TIÊU: Tự động trừ hũ tương ứng
            $this->deductExpense($userId, $amount, $categoryId);
        }
    }

    /**
     * Credit a single jar (used when reverting an expense)
     */
    private function creditJar($userId, $amount, $categoryId)
    {
        $stmt = $this->db->prepare("SELECT group_type FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $cat = $stmt->fetch(PDO::FETCH_ASSOC);
        $targetJar = ($cat && $cat['group_type'] !== 'none') ? $cat['group_type'] : 'nec';

        $sql = "UPDATE user_wallets SET balance = balance + ? WHERE user_id = ? AND jar_code = ?";
        $this->db->prepare($sql)->execute([$amount, $userId, $targetJar]);
    }

    private function distributeIncome($userId, $amount)
    {
        // Lấy tỷ lệ cài đặt (Nếu chưa cài thì dùng mặc định)
        $stmt = $this->db->prepare("SELECT * FROM user_budget_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            $settings = [
                'nec_percent' => 55, 'ffa_percent' => 10, 'ltss_percent' => 10,
                'edu_percent' => 10, 'play_percent' => 10, 'give_percent' => 5
            ];
        }

        // Tính toán chia tiền
        $allocations = [
            'nec'  => $amount * ($settings['nec_percent'] / 100),
            'ffa'  => $amount * ($settings['ffa_percent'] / 100),
            'ltss' => $amount * ($settings['ltss_percent'] / 100),
            'edu'  => $amount * ($settings['edu_percent'] / 100),
            'play' => $amount * ($settings['play_percent'] / 100),
            'give' => $amount * ($settings['give_percent'] / 100),
        ];

        // SQL Tự động cập nhật số dư
        $sql = "UPDATE user_wallets SET balance = balance + ? WHERE user_id = ? AND jar_code = ?";
        $stmt = $this->db->prepare($sql);

        foreach ($allocations as $code => $money) {
            $stmt->execute([$money, $userId, $code]);
        }
    }

    private function deductExpense($userId, $amount, $categoryId)
    {
        // Tìm xem danh mục này thuộc hũ nào
        $stmt = $this->db->prepare("SELECT group_type FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $cat = $stmt->fetch(PDO::FETCH_ASSOC);

        $targetJar = ($cat && $cat['group_type'] !== 'none') ? $cat['group_type'] : 'nec';

        // amount is negative for expense; compute positive expense value
        $expense = abs($amount);

        // Lấy số dư của tất cả các hũ
        $stmt = $this->db->prepare("SELECT jar_code, balance FROM user_wallets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $wallets = [];
        foreach ($rows as $r) {
            $wallets[$r['jar_code']] = floatval($r['balance']);
        }

        $targetBalance = isset($wallets[$targetJar]) ? $wallets[$targetJar] : 0.0;

        // Nếu target đủ, trừ đơn giản
        if ($targetBalance >= $expense) {
            $sql = "UPDATE user_wallets SET balance = balance - ? WHERE user_id = ? AND jar_code = ?";
            $this->db->prepare($sql)->execute([$expense, $userId, $targetJar]);
            return true;
        }

        // Nếu không đủ ở hũ target: cố gắng lấy từ các hũ khác (KHÔNG dùng 'give')
        $needed = $expense - max(0, $targetBalance);

        // Các hũ ưu tiên lấy bù (loại trừ target và 'give')
        $priority = ['nec','ffa','ltss','edu','play'];
        // Loại bỏ target nếu ở trong danh sách
        $sources = array_values(array_filter($priority, function($c) use ($targetJar) { return $c !== $targetJar; }));

        $availableSum = 0.0;
        foreach ($sources as $s) {
            $availableSum += isset($wallets[$s]) ? max(0, $wallets[$s]) : 0;
        }

        if ($availableSum < $needed) {
            // Không đủ tổng cộng -> chặn giao dịch bằng cách ném exception để rollback
            throw new \Exception('Không đủ số dư trong các hũ để thực hiện giao dịch.');
        }

        // Bắt đầu trừ: trước hết trừ hết phần có ở target
        $sqlUpd = $this->db->prepare("UPDATE user_wallets SET balance = balance - ? WHERE user_id = ? AND jar_code = ?");
        if ($targetBalance > 0) {
            $deductFromTarget = min($targetBalance, $expense);
            $sqlUpd->execute([$deductFromTarget, $userId, $targetJar]);
        }

        $remaining = $expense - min($targetBalance, $expense);

        // Lấy từ các hũ nguồn theo thứ tự cho tới khi đủ
        foreach ($sources as $s) {
            if ($remaining <= 0) break;
            $avail = isset($wallets[$s]) ? max(0, $wallets[$s]) : 0;
            if ($avail <= 0) continue;
            $take = min($avail, $remaining);
            $sqlUpd->execute([$take, $userId, $s]);
            $remaining -= $take;
        }

        if ($remaining > 0.0001) {
            // an extra safety check
            throw new \Exception('Không thể hoàn tất giao dịch do lỗi số dư');
        }

        return true;
    }

    // =========================================================================
    // 2. CÁC HÀM THÊM / SỬA / XÓA (ĐÃ GẮN ROBOT VÀO)
    // =========================================================================

    public function create($data)
    {
        try {
            $this->db->beginTransaction();

            // 1. Lưu giao dịch vào lịch sử
            $sql = "INSERT INTO transactions (user_id, category_id, amount, date, description, type, created_at) 
                    VALUES (:user_id, :category_id, :amount, :date, :description, :type, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':category_id' => $data['category_id'],
                ':amount' => $data['amount'],
                ':date' => $data['date'],
                ':description' => $data['description'],
                ':type' => $data['type']
            ]);
            $newId = $this->db->lastInsertId();

            // 2. KÍCH HOẠT ROBOT: Cập nhật ví ngay lập tức!
            $this->syncWallet($data['user_id'], $data['amount'], $data['type'], $data['category_id'], false);

            $this->db->commit();
            return $newId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update($id, $data)
    {
        try {
            $this->db->beginTransaction();

            // 1. Lấy giao dịch cũ
            $oldTrans = $this->getById($id);
            if (!$oldTrans) return false;

            // 2. ROBOT: Trả lại tiền cũ vào ví (Hoàn tác)
            $this->syncWallet($oldTrans['user_id'], $oldTrans['amount'], $oldTrans['type'], $oldTrans['category_id'], true);

            // 3. Cập nhật thông tin mới
            $sql = "UPDATE transactions SET 
                    amount = :amount, 
                    category_id = :category_id, 
                    date = :date, 
                    description = :description,
                    type = :type
                    WHERE id = :id";
            $data['id'] = $id;
            $this->db->prepare($sql)->execute($data);

            // 4. ROBOT: Trừ tiền mới từ ví
            $userId = $oldTrans['user_id'];
            $this->syncWallet($userId, $data['amount'], $data['type'], $data['category_id'], false);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function deleteTransaction($id, $userId)
    {
        try {
            $this->db->beginTransaction();

            // 1. Lấy giao dịch cũ
            $oldTrans = $this->getById($id);
            if (!$oldTrans) return false;

            // 2. ROBOT: Trả lại tiền vào ví như chưa từng tiêu
            $this->syncWallet($userId, $oldTrans['amount'], $oldTrans['type'], $oldTrans['category_id'], true);

            // 3. Xóa giao dịch
            $stmt = $this->db->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
            $result = $stmt->execute([$id, $userId]);

            $this->db->commit();
            return $result;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // =========================================================================
    // 3. CÁC HÀM LẤY DỮ LIỆU (KHÔNG THAY ĐỔI)
    // =========================================================================

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

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
        $sql .= ($sort === 'oldest') ? " ORDER BY t.date ASC, t.created_at ASC" : " ORDER BY t.date DESC, t.created_at DESC";

        if (isset($filters['limit']) && isset($filters['offset'])) {
            $sql .= " LIMIT " . intval($filters['limit']) . " OFFSET " . intval($filters['offset']);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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
        // Only apply type filter when it's explicitly 'income' or 'expense'
        if ($type === 'income' || $type === 'expense') {
            $sql .= " AND t.type = ?";
            $params[] = $type;
        }
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

    /**
     * Plan how an expense would be covered without applying DB changes.
     * Returns ['enough'=>bool, 'needs_redistribution'=>bool, 'shortfall'=>float, 'plan'=>[jar=>amount_to_take]]
     */
    public function planExpenseCoverage($userId, $expense, $categoryId)
    {
        $expense = floatval($expense);
        // find target jar
        $stmt = $this->db->prepare("SELECT group_type FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $cat = $stmt->fetch(PDO::FETCH_ASSOC);
        $targetJar = ($cat && $cat['group_type'] !== 'none') ? $cat['group_type'] : 'nec';

        // load wallets
        $stmt = $this->db->prepare("SELECT jar_code, balance FROM user_wallets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $wallets = [];
        foreach ($rows as $r) $wallets[$r['jar_code']] = floatval($r['balance']);

        $targetBalance = isset($wallets[$targetJar]) ? max(0, $wallets[$targetJar]) : 0;

        if ($targetBalance >= $expense) {
            return ['enough' => true, 'needs_redistribution' => false, 'shortfall' => 0.0, 'plan' => [$targetJar => $expense]];
        }

        $needed = $expense - $targetBalance;
        $priority = ['nec','ffa','ltss','edu','play'];
        $sources = array_values(array_filter($priority, function($c) use ($targetJar) { return $c !== $targetJar; }));

        $availableSum = 0;
        foreach ($sources as $s) $availableSum += isset($wallets[$s]) ? max(0, $wallets[$s]) : 0;

        if ($availableSum < $needed) {
            return ['enough' => false, 'needs_redistribution' => true, 'shortfall' => $needed - $availableSum, 'plan' => []];
        }

        // build plan: take from target first (its full balance), then from sources in order
        $plan = [];
        if ($targetBalance > 0) {
            $plan[$targetJar] = $targetBalance;
        }
        $remaining = $needed;
        foreach ($sources as $s) {
            if ($remaining <= 0) break;
            $avail = isset($wallets[$s]) ? max(0, $wallets[$s]) : 0;
            if ($avail <= 0) continue;
            $take = min($avail, $remaining);
            $plan[$s] = ($plan[$s] ?? 0) + $take;
            $remaining -= $take;
        }

        return ['enough' => true, 'needs_redistribution' => true, 'shortfall' => 0.0, 'plan' => $plan];
    }
}