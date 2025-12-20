<?php
namespace App\Controllers\User;

use App\Core\Controllers;
use App\Services\DashboardService;
use App\Middleware\AuthCheck;
use App\Core\ConnectDB; 
use PDO;

class Dashboard extends Controllers
{
    private $dashboardService;

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireUser();
        
        $transactionModel = $this->model('Transaction');
        if (!$transactionModel) {
            throw new \RuntimeException("Transaction model could not be loaded.");
        }
        $this->dashboardService = new DashboardService($transactionModel);
    }

    public function index($range = null)
    {
        $userId = $this->getCurrentUserId();
        
        if (!$range) {
            $range = date('Y-m');
        }
        
        $dashboardData = $this->dashboardService->getDashboardData($userId, $range);
        $walletModel = $this->model('Wallet');
        $rawBalances = $walletModel->getWalletBalances($userId);
        $budgetModel = $this->model('Budget');
        $settings = $budgetModel->getUserSmartSettings($userId);

        $jars = [
            'nec'  => ['name' => 'Thiết yếu', 'desc' => 'Ăn uống, sinh hoạt', 'color' => 'primary',   'percent' => $settings['nec_percent'] ?? 55],
            'ffa'  => ['name' => 'Tự do TC',  'desc' => 'Đầu tư, tiết kiệm',  'color' => 'success',   'percent' => $settings['ffa_percent'] ?? 10],
            'ltss' => ['name' => 'TK dài hạn','desc' => 'Mua xe, mua nhà',    'color' => 'info',      'percent' => $settings['ltss_percent'] ?? 10],
            'edu'  => ['name' => 'Giáo dục',  'desc' => 'Sách, khóa học',     'color' => 'warning',   'percent' => $settings['edu_percent'] ?? 10],
            'play' => ['name' => 'Hưởng thụ', 'desc' => 'Du lịch, giải trí',  'color' => 'danger',    'percent' => $settings['play_percent'] ?? 10],
            'give' => ['name' => 'Cho đi',    'desc' => 'Từ thiện',           'color' => 'secondary', 'percent' => $settings['give_percent'] ?? 5],
        ];

        foreach ($jars as $code => &$jar) {
            $jar['balance'] = round($rawBalances[$code] ?? 0); 
        }

        // $lineChartSubtitle = '3 tháng gần nhất';

        $data = [
            'title' => 'Tổng quan',
            'range' => $range,
            'totals' => $dashboardData['totals'],
            'recentTransactions' => $dashboardData['recentTransactions'],
            'pieChartData' => json_encode($dashboardData['pieChartData']),
            'lineChartData' => json_encode($dashboardData['lineChartData']),
            // 'lineChartSubtitle' => $lineChartSubtitle,
            'jars' => $jars
        ];

        $this->view->render('user/dashboard', $data);
    }

    /**
     * [FIX CUỐI CÙNG] Lấy TỔNG SỐ DƯ LŨY KẾ (SUM of all transactions.amount) để phân bổ.
     * URL: /dashboard/sync_jars
     */
    public function sync_jars()
    {
        $userId = $this->getCurrentUserId();
        $db = (new ConnectDB())->getConnection(); 
        
        echo "<body style='font-family: sans-serif; padding: 20px; line-height: 1.6;'>";
        echo "<h1>🛠️ Đang đồng bộ lại ví JARS cho Siêu cấp vip pro...</h1>";

        try {
            // [FIX] Lấy TỔNG SỐ DƯ LŨY KẾ: Tổng của tất cả các giao dịch (Income là +, Expense là -)
            $stmt = $db->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $cumulativeBalance = $stmt->fetchColumn() ?: 0;
            
            // Số dư đầu kỳ (ví gốc) - Đặt về 0 do bảng 'accounts' không tìm thấy
            $initialBalance = 0; 
            
            // Tổng nguồn tiền phân bổ chính là Tổng số dư lũy kế + Số dư đầu kỳ
            $totalNetBalance = $cumulativeBalance + $initialBalance;
            
            // ------------------------------------------------------------
            echo "--------------<br>";
            echo "🏦 Số dư đầu kỳ: " . number_format($initialBalance) . " đ <small>(Mặc định 0)</small><br>";
            echo "<b>👉 Tổng SỐ DƯ LŨY KẾ để PHÂN BỔ: " . number_format($totalNetBalance) . " đ</b><br>";
            echo "--------------<br>";
            // ------------------------------------------------------------

            // Nếu tổng nguồn tiền vẫn bằng 0 hoặc âm, thông báo và dừng
            if ($totalNetBalance <= 0) {
                echo "<br><h3 style='color:red'>🛑 LỖI! Số dư lũy kế để phân bổ <= 0 (Hãy kiểm tra lại giao dịch thu nhập và chi tiêu).</h3>";
                echo "</body>";
                exit;
            }

            // Reset dữ liệu cũ
            $db->prepare("DELETE FROM user_wallets WHERE user_id = ?")->execute([$userId]);
            echo "✅ Đã xóa dữ liệu hũ cũ.<br>";

            // 3. Lấy tỷ lệ cài đặt
            $budgetModel = $this->model('Budget');
            $settings = $budgetModel->getUserSmartSettings($userId);

            // 4. Chia TỔNG SỐ DƯ LŨY KẾ theo tỷ lệ 6 hũ.
            $balances = [
                'nec'  => round($totalNetBalance * ($settings['nec_percent'] / 100), 0),
                'ffa'  => round($totalNetBalance * ($settings['ffa_percent'] / 100), 0),
                'ltss' => round($totalNetBalance * ($settings['ltss_percent'] / 100), 0),
                'edu'  => round($totalNetBalance * ($settings['edu_percent'] / 100), 0),
                'play' => round($totalNetBalance * ($settings['play_percent'] / 100), 0),
                'give' => round($totalNetBalance * ($settings['give_percent'] / 100), 0),
            ];
            
            // 5. Lưu lại vào DB
            $sqlInsert = "INSERT INTO user_wallets (user_id, jar_code, balance) VALUES (?, ?, ?)";
            foreach ($balances as $code => $bal) {
                $db->prepare($sqlInsert)->execute([$userId, $code, $bal]); 
                $color = $bal < 0 ? 'red' : 'green';
                echo "Hũ <b>" . strtoupper($code) . "</b>: <span style='color:$color'>" . number_format($bal) . " đ</span><br>";
            }

            echo "<br><h3 style='color:green'>🎉 Đã Fix Xong! <a href='" . BASE_URL . "/dashboard'>Bấm vào đây để về trang chủ tận hưởng</a></h3>";
        
        } catch (\PDOException $e) {
            echo "<br><h3 style='color:red'>❌ LỖI DATABASE NGHIÊM TRỌNG</h3>";
            echo "<p>Đồng bộ bị dừng do lỗi truy vấn.</p>";
            echo "<p><b>Chi tiết lỗi:</b> " . $e->getMessage() . "</p>";
        } catch (\Exception $e) {
            echo "<br><h3 style='color:red'>❌ LỖI KHÔNG XÁC ĐỊNH</h3>";
            echo "<p><b>Chi tiết lỗi:</b> " . $e->getMessage() . "</p>";
        }
        
        echo "</body>";
        exit;
    }

    public function api_get_wallets()
    {
        // 1. Clean buffer & Set Header JSON
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = $this->getCurrentUserId();
            
            // 2. Lấy số dư từ DB
            $db = (new ConnectDB())->getConnection();
            $stmt = $db->prepare("SELECT jar_code, balance FROM user_wallets WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Trả về dạng Key-Value: ['nec' => 100000, 'play' => 50000, ...]
            $wallets = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            echo json_encode([
                'success' => true,
                'data' => $wallets
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}