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

        // Các logic lấy dữ liệu cho View (Render lần đầu)
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

    /**
     * API LẤY DANH SÁCH SỐ DƯ VÍ JARS (JSON)
     */
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
            error_log("API Get Wallets Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API LẤY DANH SÁCH NGÂN SÁCH (JSON)
     */
    public function api_get_list()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = $this->getCurrentUserId();
            $period = $this->request->get('period') ?? 'monthly';

            $budgets = $this->budgetModel->getBudgetsWithSpending($userId, $period) ?? [];

            echo json_encode(['success' => true, 'data' => $budgets]);
        } catch (\Exception $e) {
            error_log("API Get List Error: " . $e->getMessage());
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
            $amountRaw = $data['amount'] ?? '0';
            $cleanedAmount = preg_replace('/[^\d]/', '', $amountRaw);
            $amount = floatval($cleanedAmount);

            $period = $data['period'] ?? 'monthly';
            $alertThreshold = intval($data['alert_threshold'] ?? 80);

            if ($categoryId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng chọn một danh mục chi tiêu hợp lệ.']);
                exit;
            }
            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Số tiền ngân sách phải lớn hơn 0.']);
                exit;
            }

            // ==============================================================
            // [LOGIC MỚI] 1. TÌM GROUP TYPE VÀ SỐ DƯ (Trừ tiền trước)
            // ==============================================================

            $sqlCategory = "SELECT group_type FROM categories WHERE id = ?";
            $stmtCategory = $this->db->prepare($sqlCategory);
            $stmtCategory->execute([$categoryId]);
            $groupType = $stmtCategory->fetchColumn();

            if (empty($groupType) || $groupType === 'none' || $groupType === null) {
                echo json_encode(['success' => false, 'message' => 'Danh mục này chưa được gán nhãn JARS (NEC, FFA, LTSS...).']);
                exit;
            }

            // Kiểm tra số dư Hũ JARS
            $sqlWallet = "SELECT balance FROM user_wallets WHERE user_id = ? AND jar_code = ?";
            $stmtWallet = $this->db->prepare($sqlWallet);
            $stmtWallet->execute([$userId, $groupType]);
            $currentBalance = floatval($stmtWallet->fetchColumn() ?: 0);

            if ($currentBalance < $amount) {
                $missingAmount = round($amount - $currentBalance, 0);
                echo json_encode([
                    'success' => false,
                    'message' => "Không thể tạo ngân sách!",
                    'data' => [
                        'current_balance' => number_format($currentBalance, 0, ',', '.'),
                        'jar_code' => strtoupper($groupType),
                        'missing_amount' => number_format($missingAmount, 0, ',', '.')
                    ]
                ]);
                exit;
            }

            $this->db->beginTransaction();

            // TRỪ TIỀN VÍ JARS (QUAN TRỌNG THEO YÊU CẦU MỚI)
            $sqlDeduct = "UPDATE user_wallets SET balance = balance - ? WHERE user_id = ? AND jar_code = ?";
            $this->db->prepare($sqlDeduct)->execute([$amount, $userId, $groupType]);

            // Xử lý Ngày bắt đầu và kết thúc
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('last day of this month'));

            if ($period === 'weekly') {
                $endDate = date('Y-m-d', strtotime('next sunday'));
            } elseif ($period === 'yearly') {
                $endDate = date('Y-m-d', strtotime('last day of December this year'));
            }

            // Tạo ngân sách
            $sqlBudget = "INSERT INTO budgets (user_id, category_id, amount, start_date, end_date, period, alert_threshold, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmtBudget = $this->db->prepare($sqlBudget);
            $stmtBudget->execute([$userId, $categoryId, $amount, $startDate, $endDate, $period, $alertThreshold]);

            $this->db->commit();

            echo json_encode(['success' => true, 'message' => 'Đã tạo ngân sách thành công và trừ tiền từ hũ ' . strtoupper($groupType) . '.']);
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Budget Creation Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi Server khi tạo: ' . $e->getMessage()]);
        }
        exit;
    }
    /**
     * API: LƯU CẤU HÌNH TỶ LỆ JARS
     */
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
            $amountRaw = $data['amount'] ?? '0';
            $cleanedAmount = preg_replace('/[^\d]/', '', $amountRaw); 
            $newAmount = floatval($cleanedAmount);
            
            $period = $data['period'] ?? 'monthly';
            $alertThreshold = intval($data['alert_threshold'] ?? 80);

            if ($budgetId <= 0 || $categoryId <= 0 || $newAmount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu cập nhật không hợp lệ.']);
                exit;
            }

            $this->db->beginTransaction();

            // 1. Lấy thông tin ngân sách cũ
            $sqlGetBudget = "SELECT amount, category_id FROM budgets WHERE id = ? AND user_id = ? FOR UPDATE";
            $stmtGetBudget = $this->db->prepare($sqlGetBudget);
            $stmtGetBudget->execute([$budgetId, $userId]);
            $oldBudgetInfo = $stmtGetBudget->fetch(PDO::FETCH_ASSOC);

            if (!$oldBudgetInfo) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy ngân sách cần cập nhật.']);
                exit;
            }

            $oldAmount = floatval($oldBudgetInfo['amount']);
            $amountDifference = $newAmount - $oldAmount; // Số tiền cần thêm/bớt từ hũ
            $oldCategoryId = intval($oldBudgetInfo['category_id']);
            
            // 2. Xử lý logic JARS (Hoàn tiền cũ + Trừ tiền chênh lệch)
            
            // 2a. Lấy group_type
            $sqlCategory = "SELECT group_type FROM categories WHERE id = ?";
            $stmtCategory = $this->db->prepare($sqlCategory);
            $stmtCategory->execute([$categoryId]);
            $groupType = $stmtCategory->fetchColumn();

            if (empty($groupType) || $groupType === 'none' || $groupType === null) {
                 $this->db->rollBack();
                 echo json_encode(['success' => false, 'message' => 'Danh mục này chưa được gán nhãn JARS (NEC, FFA, LTSS...).']);
                 exit;
            }
            
            // 2b. Kiểm tra số dư Hũ JARS cho khoản chênh lệch (Nếu $amountDifference > 0)
            if ($amountDifference > 0) {
                $sqlWallet = "SELECT balance FROM user_wallets WHERE user_id = ? AND jar_code = ?";
                $stmtWallet = $this->db->prepare($sqlWallet);
                $stmtWallet->execute([$userId, $groupType]);
                $currentBalance = floatval($stmtWallet->fetchColumn() ?: 0);
                
                if ($currentBalance < $amountDifference) {
                    $missingAmount = round($amountDifference - $currentBalance, 0);
                    $this->db->rollBack();
                    echo json_encode([
                        'success' => false,
                        'message' => "Không đủ số dư để tăng ngân sách. Hũ " . strtoupper($groupType) . " cần thêm: " . number_format($missingAmount, 0, ',', '.') . "₫."
                    ]);
                    exit;
                }
            }

            // 2c. Cập nhật trừ tiền chênh lệch
            if ($amountDifference !== 0) {
                // UPDATE user_wallets SET balance = balance - amountDifference
                $sqlUpdateWallet = "UPDATE user_wallets SET balance = balance - ? WHERE user_id = ? AND jar_code = ?";
                $this->db->prepare($sqlUpdateWallet)->execute([$amountDifference, $userId, $groupType]);
            }
            
            // 3. Cập nhật Ngân sách
            // Giả định ngày bắt đầu/kết thúc được reset theo chu kỳ mới hoặc giữ nguyên
            $sql = "UPDATE budgets SET category_id = ?, amount = ?, period = ?, alert_threshold = ? 
                    WHERE id = ? AND user_id = ?";
            $this->db->prepare($sql)->execute([$categoryId, $newAmount, $period, $alertThreshold, $budgetId, $userId]);
            
            $this->db->commit();

            echo json_encode(['success' => true, 'message' => 'Đã cập nhật ngân sách thành công!']);

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Update Budget Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi Server khi cập nhật: ' . $e->getMessage()]);
        }
        exit;
    }
    /**
     * API: LẤY DỮ LIỆU XU HƯỚNG CHI TIÊU (TREND CHART)
     */
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

                $sqlBudget = "SELECT SUM(amount) FROM budgets 
                              WHERE user_id = ? AND start_date <= ? AND end_date >= ?";
                $stmtBudget = $this->db->prepare($sqlBudget);
                $stmtBudget->execute([$userId, $month . '-01', $month . '-31']);
                $totalBudget = $stmtBudget->fetchColumn() ?: 0;
                $budgetData[] = round($totalBudget, 0);

                $sqlSpent = "SELECT SUM(ABS(amount)) FROM transactions 
                             WHERE user_id = ? AND type = 'expense' 
                             AND DATE_FORMAT(date, '%Y-%m') = ?";
                $stmtSpent = $this->db->prepare($sqlSpent);
                $stmtSpent->execute([$userId, $month]);
                $totalSpent = $stmtSpent->fetchColumn() ?: 0;
                $spentData[] = round($totalSpent, 0);
            }

            $data = [
                'labels' => $labels,
                'budget' => $budgetData,
                'spent' => $spentData
            ];

            echo json_encode(['success' => true, 'data' => ['trend' => $data]]);
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

            // 1. Lấy thông tin ngân sách bị xóa (amount, category_id)
            $sqlGetBudget = "SELECT amount, category_id FROM budgets WHERE id = ? AND user_id = ? FOR UPDATE";
            $stmtGetBudget = $this->db->prepare($sqlGetBudget);
            $stmtGetBudget->execute([$budgetId, $userId]);
            $budgetInfo = $stmtGetBudget->fetch(PDO::FETCH_ASSOC);

            if (!$budgetInfo) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy ngân sách cần xóa.']);
                exit;
            }

            $amountToRefund = floatval($budgetInfo['amount']);
            $categoryId = intval($budgetInfo['category_id']);

            // 2. Lấy group_type (mã hũ)
            $sqlCategory = "SELECT group_type FROM categories WHERE id = ?";
            $stmtCategory = $this->db->prepare($sqlCategory);
            $stmtCategory->execute([$categoryId]);
            $groupType = $stmtCategory->fetchColumn();

            if ($groupType && $groupType !== 'none') {
                // 3. HOÀN TIỀN VÍ JARS
                $sqlRefund = "UPDATE user_wallets SET balance = balance + ? WHERE user_id = ? AND jar_code = ?";
                $stmtRefund = $this->db->prepare($sqlRefund);
                $stmtRefund->execute([$amountToRefund, $userId, $groupType]);
            }

            // 4. XÓA NGÂN SÁCH
            $sqlDelete = "DELETE FROM budgets WHERE id = ? AND user_id = ?";
            $stmtDelete = $this->db->prepare($sqlDelete);
            $stmtDelete->execute([$budgetId, $userId]);

            $this->db->commit();

            if ($stmtDelete->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Đã xóa ngân sách thành công và hoàn lại tiền!']);
            } else {
                // Should not happen if $budgetInfo was found, but safe measure
                echo json_encode(['success' => false, 'message' => 'Lỗi xóa ngân sách.']);
            }
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Delete Budget Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi Server khi xóa: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: PHÂN BỔ THU NHẬP
     */
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
                echo json_encode(['success' => false, 'message' => 'Số tiền phải lớn hơn 0']);
                exit;
            }

            $stmt = $this->db->prepare("SELECT * FROM user_budget_settings WHERE user_id = ?");
            $stmt->execute([$userId]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$settings) {
                $settings = ['nec_percent' => 55, 'ffa_percent' => 10, 'ltss_percent' => 10, 'edu_percent' => 10, 'play_percent' => 10, 'give_percent' => 5];
            }

            if ($amount >= 100000000) {
                $settings['nec_percent'] = 20;
                $settings['ffa_percent'] = 50;
            }

            $jars = [
                'nec' => $settings['nec_percent'],
                'ffa' => $settings['ffa_percent'],
                'ltss' => $settings['ltss_percent'],
                'edu' => $settings['edu_percent'],
                'play' => $settings['play_percent'],
                'give' => $settings['give_percent']
            ];

            $this->db->beginTransaction();

            $sqlWallet = "INSERT INTO user_wallets (user_id, jar_code, balance) VALUES (?, ?, ?) 
                          ON DUPLICATE KEY UPDATE balance = balance + ?";

            foreach ($jars as $code => $percent) {
                $alloc = round($amount * ($percent / 100), 2);
                $this->db->prepare($sqlWallet)->execute([$userId, $code, $alloc, $alloc]);
            }

            $incomeCatId = 10;
            $sqlTx = "INSERT INTO transactions (user_id, category_id, amount, date, description, type, created_at) 
                      VALUES (?, ?, ?, NOW(), 'Phân bổ thu nhập JARS', 'income', NOW())";
            $this->db->prepare($sqlTx)->execute([$userId, $incomeCatId, $amount]);

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Đã phân bổ thu nhập thành công!']);
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Income Distribution Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
}
