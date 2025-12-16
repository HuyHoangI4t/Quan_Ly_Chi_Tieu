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

    /**
     * HÀM HỖ TRỢ: Tìm hũ bù lỗ (Backup Jar) - ĐÃ CẬP NHẬT ƯU TIÊN PLAY
     */
    private function findBackupJar($userId, $missingAmount, $excludeJarCode)
    {
        // Lấy tất cả các ví
        $wallets = method_exists($this->walletModel, 'getUserWallets') ? $this->walletModel->getUserWallets($userId) : [];

        $candidates = [];
        foreach ($wallets as $w) {
            $code = $w['jar_code'];
            $balance = floatval($w['balance']);

            // 1. Bỏ qua hũ hiện tại
            if ($code === $excludeJarCode) continue;

            // 2. LUẬT CẤM: Không động đến Giáo dục (EDU)
            if ($code === 'edu') continue;

            // 3. Phân loại ưu tiên
            $priority = 1; // Mặc định (FFA, NEC...)

            // [MỚI] ƯU TIÊN 1: PLAY (Hưởng thụ) - Hy sinh ăn chơi trước
            if ($code === 'play') {
                $priority = 2;
            }

            // ƯU TIÊN THẤP: Cho đi (GIVE) và Tiết kiệm (LTSS)
            if ($code === 'give' || $code === 'ltss') {
                $priority = 0;
            }

            // Chỉ lấy hũ có đủ tiền bù
            if ($balance >= $missingAmount) {
                $candidates[] = [
                    'code' => $code,
                    'balance' => $balance,
                    'priority' => $priority
                ];
            }
        }

        if (empty($candidates)) return null;

        // Sắp xếp: Ưu tiên cao trước -> Số dư nhiều trước
        usort($candidates, function ($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $b['priority'] - $a['priority']; // 2 > 1 > 0
            }
            return $b['balance'] - $a['balance']; // Nhiều tiền trước
        });

        return $candidates[0]; // Trả về ứng viên tốt nhất
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

            // 1. Tìm Group Type
            $sqlCategory = "SELECT group_type FROM categories WHERE id = ?";
            $stmtCategory = $this->db->prepare($sqlCategory);
            $stmtCategory->execute([$categoryId]);
            $groupType = $stmtCategory->fetchColumn();

            if (empty($groupType) || $groupType === 'none' || $groupType === null) {
                echo json_encode(['success' => false, 'message' => 'Danh mục này chưa được gán nhãn JARS (NEC, FFA, LTSS...).']);
                exit;
            }

            // Kiểm tra số dư Hũ JARS chính
            $sqlWallet = "SELECT balance FROM user_wallets WHERE user_id = ? AND jar_code = ?";
            $stmtWallet = $this->db->prepare($sqlWallet);
            $stmtWallet->execute([$userId, $groupType]);
            $currentBalance = floatval($stmtWallet->fetchColumn() ?: 0);

            $this->db->beginTransaction();

            // === LOGIC TỰ ĐỘNG BÙ LỖ ===
            if ($currentBalance < $amount) {
                $missingAmount = round($amount - $currentBalance, 0);

                // Tìm hũ bù lỗ (Ưu tiên PLAY)
                $backupJar = $this->findBackupJar($userId, $missingAmount, $groupType);

                if ($backupJar) {
                    // Trừ hết tiền hũ chính (về 0)
                    if ($currentBalance > 0) {
                        $sqlDeductMain = "UPDATE user_wallets SET balance = 0 WHERE user_id = ? AND jar_code = ?";
                        $this->db->prepare($sqlDeductMain)->execute([$userId, $groupType]);
                    }

                    // Trừ phần thiếu từ hũ bù lỗ
                    $sqlDeductBackup = "UPDATE user_wallets SET balance = balance - ? WHERE user_id = ? AND jar_code = ?";
                    $this->db->prepare($sqlDeductBackup)->execute([$missingAmount, $userId, $backupJar['code']]);

                    // Ghi log chuyển tiền ảo (nếu cần thiết, ở đây ta chỉ trừ tiền)
                } else {
                    $this->db->rollBack();
                    echo json_encode([
                        'success' => false,
                        'message' => "Không đủ tiền! (Cần thêm " . number_format($missingAmount) . "₫). Hũ Hưởng thụ (PLAY) và các hũ khác không đủ số dư.",
                        'data' => [
                            'current_balance' => number_format($currentBalance, 0, ',', '.'),
                            'jar_code' => strtoupper($groupType),
                            'missing_amount' => number_format($missingAmount, 0, ',', '.')
                        ]
                    ]);
                    exit;
                }
            } else {
                // Đủ tiền -> Trừ bình thường
                $sqlDeduct = "UPDATE user_wallets SET balance = balance - ? WHERE user_id = ? AND jar_code = ?";
                $this->db->prepare($sqlDeduct)->execute([$amount, $userId, $groupType]);
            }

            // Tạo ngân sách
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('last day of this month'));
            if ($period === 'weekly') $endDate = date('Y-m-d', strtotime('next sunday'));
            elseif ($period === 'yearly') $endDate = date('Y-m-d', strtotime('last day of December this year'));

            $sqlBudget = "INSERT INTO budgets (user_id, category_id, amount, start_date, end_date, period, alert_threshold, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmtBudget = $this->db->prepare($sqlBudget);
            $stmtBudget->execute([$userId, $categoryId, $amount, $startDate, $endDate, $period, $alertThreshold]);

            $this->db->commit();

            $msg = 'Đã tạo ngân sách thành công!';
            if (isset($missingAmount) && isset($backupJar)) {
                $msg .= " (Đã bù " . number_format($missingAmount) . "₫ từ hũ " . strtoupper($backupJar['code']) . ")";
            }

            echo json_encode(['success' => true, 'message' => $msg]);
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
     * API: Cập nhật Ngân sách (Edit Budget) - CÓ LOGIC BÙ LỖ
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

            // 2. Lấy group_type
            $sqlCategory = "SELECT group_type FROM categories WHERE id = ?";
            $stmtCategory = $this->db->prepare($sqlCategory);
            $stmtCategory->execute([$categoryId]);
            $groupType = $stmtCategory->fetchColumn();

            if (empty($groupType) || $groupType === 'none' || $groupType === null) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Danh mục này chưa được gán nhãn JARS.']);
                exit;
            }

            // 3. Xử lý ví JARS
            if ($amountDifference > 0) {
                // Cần thêm tiền -> Check ví
                $sqlWallet = "SELECT balance FROM user_wallets WHERE user_id = ? AND jar_code = ?";
                $stmtWallet = $this->db->prepare($sqlWallet);
                $stmtWallet->execute([$userId, $groupType]);
                $currentBalance = floatval($stmtWallet->fetchColumn() ?: 0);

                if ($currentBalance < $amountDifference) {
                    $missingAmount = round($amountDifference - $currentBalance, 0);

                    // BÙ LỖ (Ưu tiên PLAY)
                    $backupJar = $this->findBackupJar($userId, $missingAmount, $groupType);

                    if ($backupJar) {
                        // Trừ hết hũ chính
                        if ($currentBalance > 0) {
                            $this->db->prepare("UPDATE user_wallets SET balance = balance - ? WHERE user_id = ? AND jar_code = ?")
                                ->execute([$currentBalance, $userId, $groupType]);
                            $remainingDiff = $amountDifference - $currentBalance;
                        } else {
                            $remainingDiff = $amountDifference;
                        }

                        // Trừ hũ backup
                        $this->db->prepare("UPDATE user_wallets SET balance = balance - ? WHERE user_id = ? AND jar_code = ?")
                            ->execute([$remainingDiff, $userId, $backupJar['code']]);
                    } else {
                        $this->db->rollBack();
                        echo json_encode([
                            'success' => false,
                            'message' => "Không đủ tiền! Hũ " . strtoupper($groupType) . " thiếu " . number_format($missingAmount) . "₫ và không tìm được hũ bù lỗ phù hợp."
                        ]);
                        exit;
                    }
                } else {
                    // Đủ tiền
                    $this->db->prepare("UPDATE user_wallets SET balance = balance - ? WHERE user_id = ? AND jar_code = ?")
                        ->execute([$amountDifference, $userId, $groupType]);
                }
            } else if ($amountDifference < 0) {
                // Giảm ngân sách -> Hoàn tiền
                $refundAmount = abs($amountDifference);
                $this->db->prepare("UPDATE user_wallets SET balance = balance + ? WHERE user_id = ? AND jar_code = ?")
                    ->execute([$refundAmount, $userId, $groupType]);
            }

            // 4. Cập nhật Ngân sách
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
            echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
        }
        exit;
    }

    // API: LƯU CẤU HÌNH TỶ LỆ JARS
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

    // API: LẤY DỮ LIỆU XU HƯỚNG CHI TIÊU - [ĐÃ FIX LOGIC HIỂN THỊ]
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
                // Tính tháng (YYYY-mm)
                $month = date('Y-m', strtotime("-$i months"));
                $labels[] = date('m/Y', strtotime($month));

                // FIX LOGIC: Tính ngày đầu tháng và ngày cuối tháng chuẩn (tránh ngày 31 cho tháng thiếu)
                $startOfMonth = $month . '-01';
                $endOfMonth   = date('Y-m-t', strtotime($startOfMonth)); 

                // FIX LOGIC QUERY: Kiểm tra khoảng thời gian giao nhau (Overlap)
                // Điều kiện giao nhau: Start_Budget <= End_Month VÀ End_Budget >= Start_Month
                // Logic cũ (start <= start_month) bị sai nếu tạo ngân sách giữa tháng
                $sqlBudget = "SELECT SUM(amount) FROM budgets 
                              WHERE user_id = ? 
                              AND start_date <= ? 
                              AND end_date >= ?";
                
                $stmtB = $this->db->prepare($sqlBudget);
                $stmtB->execute([$userId, $endOfMonth, $startOfMonth]);
                $budgetData[] = round($stmtB->fetchColumn() ?: 0, 0);

                // Lấy thực chi
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

    // API: XÓA NGÂN SÁCH
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
            $stmtGet = $this->db->prepare("SELECT amount, category_id FROM budgets WHERE id = ? AND user_id = ? FOR UPDATE");
            $stmtGet->execute([$budgetId, $userId]);
            $info = $stmtGet->fetch(PDO::FETCH_ASSOC);
            if (!$info) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy ngân sách.']);
                exit;
            }
            $amt = floatval($info['amount']);
            $catId = intval($info['category_id']);
            $stmtCat = $this->db->prepare("SELECT group_type FROM categories WHERE id = ?");
            $stmtCat->execute([$catId]);
            $gType = $stmtCat->fetchColumn();
            if ($gType && $gType !== 'none') {
                $this->db->prepare("UPDATE user_wallets SET balance = balance + ? WHERE user_id = ? AND jar_code = ?")->execute([$amt, $userId, $gType]);
            }
            $this->db->prepare("DELETE FROM budgets WHERE id = ? AND user_id = ?")->execute([$budgetId, $userId]);
            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Đã xóa ngân sách và hoàn tiền!']);
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Del Budget Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi xóa: ' . $e->getMessage()]);
        }
        exit;
    }

    // API: PHÂN BỔ THU NHẬP
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