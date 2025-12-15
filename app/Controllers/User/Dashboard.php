<?php
namespace App\Controllers\User;

use App\Core\Controllers;
use App\Services\DashboardService;
use App\Middleware\AuthCheck;
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
        
        // Default to current month
        if (!$range) {
            $range = date('Y-m');
        }
        
        // 1. Lấy dữ liệu thống kê cơ bản
        $dashboardData = $this->dashboardService->getDashboardData($userId, $range);

        // 2. Xử lý dữ liệu JARS (6 Hũ)
        $walletModel = $this->model('Wallet');
        $rawBalances = $walletModel->getWalletBalances($userId);
        
        $budgetModel = $this->model('Budget');
        $settings = $budgetModel->getUserSmartSettings($userId);

        // Cấu hình hiển thị 6 hũ
        $jars = [
            'nec'  => ['name' => 'Thiết yếu', 'desc' => 'Ăn uống, sinh hoạt', 'color' => 'primary',   'percent' => $settings['nec_percent'] ?? 55],
            'ffa'  => ['name' => 'Tự do TC',  'desc' => 'Đầu tư, tiết kiệm',  'color' => 'success',   'percent' => $settings['ffa_percent'] ?? 10],
            'ltss' => ['name' => 'TK dài hạn','desc' => 'Mua xe, mua nhà',    'color' => 'info',      'percent' => $settings['ltss_percent'] ?? 10],
            'edu'  => ['name' => 'Giáo dục',  'desc' => 'Sách, khóa học',     'color' => 'warning',   'percent' => $settings['edu_percent'] ?? 10],
            'play' => ['name' => 'Hưởng thụ', 'desc' => 'Du lịch, giải trí',  'color' => 'danger',    'percent' => $settings['play_percent'] ?? 10],
            'give' => ['name' => 'Cho đi',    'desc' => 'Từ thiện',           'color' => 'secondary', 'percent' => $settings['give_percent'] ?? 5],
        ];

        // Gán số dư thực tế từ DB
        foreach ($jars as $code => &$jar) {
            $jar['balance'] = $rawBalances[$code] ?? 0;
        }

        $lineChartSubtitle = '3 tháng gần nhất';

        $data = [
            'title' => 'Tổng quan',
            'range' => $range,
            'totals' => $dashboardData['totals'],
            'recentTransactions' => $dashboardData['recentTransactions'],
            'pieChartData' => json_encode($dashboardData['pieChartData']),
            'lineChartData' => json_encode($dashboardData['lineChartData']),
            'lineChartSubtitle' => $lineChartSubtitle,
            'jars' => $jars
        ];

        $this->view->render('user/dashboard', $data);
    }

    /**
     * [FIX LỖI] Chạy hàm này 1 lần để đồng bộ lại tiền trong các hũ
     * URL: /dashboard/sync_jars
     */
    /**
     * [FIX LỖI] Đã thêm cộng SỐ DƯ ĐẦU KỲ vào tổng thu nhập
     * URL: /dashboard/sync_jars
     */
    public function sync_jars()
    {
        $userId = $this->getCurrentUserId();
        $db = (new \App\Core\ConnectDB())->getConnection();
        
        echo "<body style='font-family: sans-serif; padding: 20px; line-height: 1.6;'>";
        echo "<h1>🛠️ Đang đồng bộ lại ví JARS cho Siêu cấp vip pro...</h1>";

        // --- PHẦN QUAN TRỌNG: LẤY NGUỒN TIỀN ĐỂ PHÂN BỔ ---
        // Try to use existing jar balances as the source total. If none exist, fallback to transactions + initial account balances.

        // 1. Read current total of user_wallets BEFORE resetting
        $stmt = $db->prepare("SELECT COALESCE(SUM(balance),0) FROM user_wallets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $walletTotal = $stmt->fetchColumn() ?: 0;

        if ($walletTotal > 0) {
            $totalIncome = $walletTotal;
            echo "✅ Sử dụng tổng số dư hiện tại của 6 hũ làm nguồn: " . number_format($totalIncome) . " đ<br>";
        } else {
            // If no existing jar balances, fallback to compute from income transactions + initial account balances
            $stmt = $db->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'income'");
            $stmt->execute([$userId]);
            $txIncome = $stmt->fetchColumn() ?: 0;

            $stmt = $db->prepare("SELECT SUM(initial_balance) FROM accounts WHERE user_id = ?"); 
            $stmt->execute([$userId]);
            $initialBalance = $stmt->fetchColumn() ?: 0;

            $totalIncome = $txIncome + $initialBalance;

            echo "--------------<br>";
            echo "💰 Thu nhập từ giao dịch: " . number_format($txIncome) . " đ<br>";
            echo "🏦 Số dư đầu kỳ (ví gốc): " . number_format($initialBalance) . " đ<br>";
            echo "<b>👉 Tổng nguồn tiền phân bổ: " . number_format($totalIncome) . " đ</b><br>";
            echo "--------------<br>";
        }

        // Now reset existing jars (we already sampled walletTotal above)
        $db->prepare("DELETE FROM user_wallets WHERE user_id = ?")->execute([$userId]);
        echo "✅ Đã xóa dữ liệu hũ cũ.<br>";

        // ------------------------------------

        // 3. Lấy tỷ lệ cài đặt
        $budgetModel = $this->model('Budget');
        $settings = $budgetModel->getUserSmartSettings($userId);

        // 4. Chia tiền vào hũ (Logic phân bổ)
        $balances = [
            'nec'  => $totalIncome * ($settings['nec_percent'] / 100),
            'ffa'  => $totalIncome * ($settings['ffa_percent'] / 100),
            'ltss' => $totalIncome * ($settings['ltss_percent'] / 100),
            'edu'  => $totalIncome * ($settings['edu_percent'] / 100),
            'play' => $totalIncome * ($settings['play_percent'] / 100),
            'give' => $totalIncome * ($settings['give_percent'] / 100),
        ];

        // 5. Trừ tiền đã chi tiêu (Logic cũ giữ nguyên)
        $sqlSpent = "SELECT t.amount, c.name as cat_name 
                     FROM transactions t 
                     JOIN categories c ON t.category_id = c.id 
                     WHERE t.user_id = ? AND t.type = 'expense'";
        $stmt = $db->prepare($sqlSpent);
        $stmt->execute([$userId]);
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($expenses as $tx) {
            $amount = $tx['amount'];
            $name = mb_strtolower($tx['cat_name']);

            // Map đơn giản theo từ khóa
            $target = 'nec'; // Mặc định
            if (strpos($name, 'học') !== false || strpos($name, 'sách') !== false) $target = 'edu';
            elseif (strpos($name, 'chơi') !== false || strpos($name, 'du lịch') !== false || strpos($name, 'giải trí') !== false) $target = 'play';
            elseif (strpos($name, 'từ thiện') !== false || strpos($name, 'biếu') !== false) $target = 'give';
            elseif (strpos($name, 'đầu tư') !== false) $target = 'ffa';
            elseif (strpos($name, 'tiết kiệm') !== false) $target = 'ltss';

            $balances[$target] -= $amount;
        }

        // 6. Lưu lại vào DB
        $sqlInsert = "INSERT INTO user_wallets (user_id, jar_code, balance) VALUES (?, ?, ?)";
        foreach ($balances as $code => $bal) {
            $db->prepare($sqlInsert)->execute([$userId, $code, $bal]);
            // Format màu mè tí cho dễ nhìn
            $color = $bal < 0 ? 'red' : 'green';
            echo "Hũ <b>" . strtoupper($code) . "</b>: <span style='color:$color'>" . number_format($bal) . " đ</span><br>";
        }

        echo "<br><h3 style='color:green'>🎉 Đã Fix Xong! <a href='/Quan_Ly_Chi_Tieu/dashboard'>Bấm vào đây để về trang chủ tận hưởng</a></h3>";
        echo "</body>";
        exit;
    }
}