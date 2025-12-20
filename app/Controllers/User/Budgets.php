<?php

namespace App\Controllers\User;

use App\Core\Controllers;
use App\Core\Response;
use App\Middleware\AuthCheck;
use App\Core\ConnectDB;
use PDO;

class Budgets extends Controllers
{
    private $budgetModel;
    private $categoryModel;
    private $walletModel;
    protected $db;

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireUser();

        $this->db = (new ConnectDB())->getConnection();
        $this->budgetModel = $this->model('Budget');
        $this->categoryModel = $this->model('Category');
        $this->walletModel = $this->model('Wallet');
    }

    public function index()
    {
        $userId = $this->getCurrentUserId();

        $wallets = method_exists($this->walletModel, 'getUserWallets') ? $this->walletModel->getUserWallets($userId) : [];

        $stmt = $this->db->prepare("SELECT * FROM user_budget_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            $settings = ['nec_percent' => 55, 'ffa_percent' => 10, 'ltss_percent' => 10, 'edu_percent' => 10, 'play_percent' => 10, 'give_percent' => 5];
        }

        $budgets = $this->budgetModel->getBudgetsWithSpending($userId, 'monthly') ?? [];
        $allCategories = $this->categoryModel->getAll($userId);

        $expenseCategories = array_values(array_filter($allCategories, function ($cat) {
            return isset($cat['type']) && $cat['type'] === 'expense' && $cat['id'] != 1;
        }));

        $this->view('user/budgets', [
            'title' => 'Quản lý ngân sách',
            'budgets' => $budgets,
            'categories' => $expenseCategories,
            'wallets' => $wallets,
            'settings' => $settings
        ]);
    }

    public function api_get_wallets()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = $this->getCurrentUserId();
            $wallets = method_exists($this->walletModel, 'getUserWallets') ? $this->walletModel->getUserWallets($userId) : [];

            $budgetModel = $this->model('Budget');
            $settings = $budgetModel->getUserSmartSettings($userId);

            $data = [];
            $jarCodes = ['nec', 'ffa', 'ltss', 'edu', 'play', 'give'];

            foreach ($jarCodes as $code) {
                $wallet = array_filter($wallets, fn($w) => $w['jar_code'] === $code);
                $balance = count($wallet) > 0 ? (float)current($wallet)['balance'] : 0.0;

                $data[] = [
                    'jar_code' => $code,
                    'balance' => round($balance, 0),
                    'percent' => $settings[$code . '_percent'] ?? 0
                ];
            }

            echo json_encode(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * [QUAN TRỌNG] API lấy danh sách ngân sách KÈM SỐ DƯ HŨ
     */
    public function api_get_list()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = $this->getCurrentUserId();
            $period = $this->request->get('period') ?? 'monthly';

            // 1. Lấy danh sách ngân sách
            $budgets = $this->budgetModel->getBudgetsWithSpending($userId, $period) ?? [];

            // 2. Lấy số dư ví thực tế để so sánh
            $wallets = method_exists($this->walletModel, 'getUserWallets') ? $this->walletModel->getUserWallets($userId) : [];
            
            // Map số dư theo mã hũ (nec, play...)
            $walletMap = [];
            foreach ($wallets as $w) {
                $walletMap[$w['jar_code']] = floatval($w['balance']);
            }

            // 3. Gắn số dư hũ vào từng ngân sách
            foreach ($budgets as &$b) {
                $jarCode = strtolower($b['category_group'] ?? 'none');
                // Gắn thêm trường 'current_jar_balance' vào dữ liệu trả về
                $b['current_jar_balance'] = $walletMap[$jarCode] ?? 0;
            }

            echo json_encode(['success' => true, 'data' => $budgets]);
        } catch (\Exception $e) {
            error_log("API Get List Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API trả về các ngân sách đã đạt ngưỡng cảnh báo
     */
    public function api_get_alerts()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = $this->getCurrentUserId();
            $period = $this->request->get('period') ?? 'monthly';

            $alerts = $this->budgetModel->getAlerts($userId, $period) ?? [];

            echo json_encode(['success' => true, 'data' => $alerts]);
        } catch (\Exception $e) {
            error_log("API Get Alerts Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function api_create()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        if ($this->request->method() !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            exit;
        }

        try {
            $userId = $this->getCurrentUserId();
            $data = $this->request->json();

            $categoryId = intval($data['category_id'] ?? 0);
            $amount = floatval(preg_replace('/[^\d]/', '', $data['amount'] ?? '0'));
            $period = $data['period'] ?? 'monthly';
            $alertThreshold = intval($data['alert_threshold'] ?? 80);

            if ($categoryId <= 0 || $amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
                exit;
            }

            // Kiểm tra số dư để CẢNH BÁO (nhưng vẫn cho tạo)
            $warningMsg = "";
            $sqlCategory = "SELECT group_type FROM categories WHERE id = ?";
            $stmtCategory = $this->db->prepare($sqlCategory);
            $stmtCategory->execute([$categoryId]);
            $groupType = $stmtCategory->fetchColumn();

            if ($groupType && $groupType !== 'none') {
                $sqlWallet = "SELECT balance FROM user_wallets WHERE user_id = ? AND jar_code = ?";
                $stmtWallet = $this->db->prepare($sqlWallet);
                $stmtWallet->execute([$userId, $groupType]);
                $currentBalance = floatval($stmtWallet->fetchColumn() ?: 0);

                if ($currentBalance < $amount) {
                    $shortfall = number_format($amount - $currentBalance);
                    $warningMsg = "\n⚠️ Lưu ý: Hũ " . strtoupper($groupType) . " hiện chỉ còn " . number_format($currentBalance) . "đ (Thiếu $shortfall đ).";
                }
            }

            $this->db->beginTransaction();

            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('last day of this month'));
            if ($period === 'weekly') $endDate = date('Y-m-d', strtotime('next sunday'));
            elseif ($period === 'yearly') $endDate = date('Y-m-d', strtotime('last day of December this year'));

            $sqlBudget = "INSERT INTO budgets (user_id, category_id, amount, start_date, end_date, period, alert_threshold, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmtBudget = $this->db->prepare($sqlBudget);
            $stmtBudget->execute([$userId, $categoryId, $amount, $startDate, $endDate, $period, $alertThreshold]);

            $this->db->commit();

            echo json_encode(['success' => true, 'message' => 'Đã tạo ngân sách thành công!' . $warningMsg]);
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }

    public function api_update()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        if ($this->request->method() !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            exit;
        }

        try {
            $userId = $this->getCurrentUserId();
            $data = $this->request->json();

            $budgetId = intval($data['budget_id'] ?? 0);
            $categoryId = intval($data['category_id'] ?? 0);
            $amount = floatval(preg_replace('/[^\d]/', '', $data['amount'] ?? '0'));
            $period = $data['period'] ?? 'monthly';
            $alertThreshold = intval($data['alert_threshold'] ?? 80);

            // Kiểm tra số dư để CẢNH BÁO
            $warningMsg = "";
            $sqlCategory = "SELECT group_type FROM categories WHERE id = ?";
            $stmtCategory = $this->db->prepare($sqlCategory);
            $stmtCategory->execute([$categoryId]);
            $groupType = $stmtCategory->fetchColumn();

            if ($groupType && $groupType !== 'none') {
                $sqlWallet = "SELECT balance FROM user_wallets WHERE user_id = ? AND jar_code = ?";
                $stmtWallet = $this->db->prepare($sqlWallet);
                $stmtWallet->execute([$userId, $groupType]);
                $currentBalance = floatval($stmtWallet->fetchColumn() ?: 0);

                if ($currentBalance < $amount) {
                    $shortfall = number_format($amount - $currentBalance);
                    $warningMsg = "\n⚠️ Lưu ý: Hũ " . strtoupper($groupType) . " không đủ tiền ngân sách này (Thiếu $shortfall đ).";
                }
            }

            $this->db->beginTransaction();

            $sql = "UPDATE budgets SET category_id = ?, amount = ?, period = ?, alert_threshold = ? 
                    WHERE id = ? AND user_id = ?";
            $this->db->prepare($sql)->execute([$categoryId, $amount, $period, $alertThreshold, $budgetId, $userId]);

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Cập nhật thành công!' . $warningMsg]);
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }

    public function api_update_ratios()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        if ($this->request->method() !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            exit;
        }
        try {
            $userId = $this->getCurrentUserId();
            $data = $this->request->json();
            $settings = [
                'nec_percent'  => intval($data['nec'] ?? 0),
                'ffa_percent'  => intval($data['ffa'] ?? 0),
                'ltss_percent' => intval($data['ltss'] ?? 0),
                'edu_percent'  => intval($data['edu'] ?? 0),
                'play_percent' => intval($data['play'] ?? 0),
                'give_percent' => intval($data['give'] ?? 0),
            ];
            $total = array_sum($settings);
            if ($total !== 100) {
                echo json_encode(['success' => false, 'message' => "Tổng tỷ lệ phải là 100%. Hiện tại: {$total}%"]);
                exit;
            }
            $sql = "INSERT INTO user_budget_settings (user_id, nec_percent, ffa_percent, ltss_percent, edu_percent, play_percent, give_percent) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nec_percent=VALUES(nec_percent),ffa_percent=VALUES(ffa_percent),ltss_percent=VALUES(ltss_percent),edu_percent=VALUES(edu_percent),play_percent=VALUES(play_percent),give_percent=VALUES(give_percent)";
            $this->db->prepare($sql)->execute(array_merge([$userId], array_values($settings)));
            echo json_encode(['success' => true, 'message' => 'Đã lưu cấu hình thành công!']);
        } catch (\Exception $e) {
            error_log("Update Ratios Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
        }
        exit;
    }

    public function api_get_trend()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $userId = $this->getCurrentUserId();
            $months = intval($this->request->get('months') ?? 6);
            $labels = [];
            $budgetData = [];
            $spentData = [];

            for ($i = $months - 1; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months"));
                $labels[] = date('m/Y', strtotime($month));

                $startOfMonth = $month . '-01';
                $endOfMonth   = date('Y-m-t', strtotime($startOfMonth)); 

                $sqlBudget = "SELECT SUM(amount) FROM budgets 
                              WHERE user_id = ? 
                              AND start_date <= ? 
                              AND end_date >= ?";
                
                $stmtB = $this->db->prepare($sqlBudget);
                $stmtB->execute([$userId, $endOfMonth, $startOfMonth]);
                $budgetData[] = round($stmtB->fetchColumn() ?: 0, 0);

                $stmtS = $this->db->prepare("SELECT SUM(ABS(amount)) FROM transactions WHERE user_id = ? AND type = 'expense' AND DATE_FORMAT(date, '%Y-%m') = ?");
                $stmtS->execute([$userId, $month]);
                $spentData[] = round($stmtS->fetchColumn() ?: 0, 0);
            }
            echo json_encode(['success' => true, 'data' => ['trend' => ['labels' => $labels, 'budget' => $budgetData, 'spent' => $spentData]]]);
        } catch (\Exception $e) {
            error_log("Trend Data Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi lấy dữ liệu trend: ' . $e->getMessage()]);
        }
        exit;
    }

    public function api_delete_budget()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        if ($this->request->method() !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            exit;
        }
        try {
            $userId = $this->getCurrentUserId();
            $data = $this->request->json();
            $budgetId = intval($data['id'] ?? 0);
            if ($budgetId === 0) {
                echo json_encode(['success' => false, 'message' => 'Thiếu ID ngân sách']);
                exit;
            }
            $this->db->beginTransaction();
            $this->db->prepare("DELETE FROM budgets WHERE id = ? AND user_id = ?")->execute([$budgetId, $userId]);
            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Đã xóa ngân sách!']);
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Del Budget Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi xóa: ' . $e->getMessage()]);
        }
        exit;
    }

    public function api_distribute_income()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        if ($this->request->method() !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            exit;
        }
        try {
            $userId = $this->getCurrentUserId();
            $data = $this->request->json();
            $amount = floatval($data['amount'] ?? 0);
            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Số tiền > 0']);
                exit;
            }
            $stmt = $this->db->prepare("SELECT * FROM user_budget_settings WHERE user_id = ?");
            $stmt->execute([$userId]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$settings) $settings = ['nec_percent' => 55, 'ffa_percent' => 10, 'ltss_percent' => 10, 'edu_percent' => 10, 'play_percent' => 10, 'give_percent' => 5];
            if ($amount >= 100000000) {
                $settings['nec_percent'] = 20;
                $settings['ffa_percent'] = 50;
            }
            $jars = ['nec' => $settings['nec_percent'], 'ffa' => $settings['ffa_percent'], 'ltss' => $settings['ltss_percent'], 'edu' => $settings['edu_percent'], 'play' => $settings['play_percent'], 'give' => $settings['give_percent']];
            $this->db->beginTransaction();
            $sqlW = "INSERT INTO user_wallets (user_id, jar_code, balance) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE balance = balance + ?";
            foreach ($jars as $c => $p) {
                $al = round($amount * ($p / 100), 2);
                $this->db->prepare($sqlW)->execute([$userId, $c, $al, $al]);
            }
            $this->db->prepare("INSERT INTO transactions (user_id, category_id, amount, date, description, type, created_at) VALUES (?, 10, ?, NOW(), 'Phân bổ thu nhập JARS', 'income', NOW())")->execute([$userId, $amount]);
            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Đã phân bổ thu nhập!']);
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Inc Dist Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
}