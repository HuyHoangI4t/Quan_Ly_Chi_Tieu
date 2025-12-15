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
        
        $expenseCategories = array_values(array_filter($allCategories, function($cat) {
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
     * [MỚI] API LẤY DANH SÁCH NGÂN SÁCH (JSON)
     * Hàm này phục vụ cho budgets.js load lại dữ liệu mà không cần F5
     */
    public function api_get_list()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = $this->getCurrentUserId();
            // Lấy danh sách ngân sách kèm thông tin chi tiêu
            $budgets = $this->budgetModel->getBudgetsWithSpending($userId, 'monthly') ?? [];

            echo json_encode(['success' => true, 'data' => $budgets]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: LƯU CẤU HÌNH TỶ LỆ JARS
     */
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

            $sql = "INSERT INTO user_budget_settings 
                    (user_id, nec_percent, ffa_percent, ltss_percent, edu_percent, play_percent, give_percent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    nec_percent = VALUES(nec_percent),
                    ffa_percent = VALUES(ffa_percent),
                    ltss_percent = VALUES(ltss_percent),
                    edu_percent = VALUES(edu_percent),
                    play_percent = VALUES(play_percent),
                    give_percent = VALUES(give_percent)";

            $params = array_merge([$userId], array_values($settings));
            $this->db->prepare($sql)->execute($params);
            
            echo json_encode(['success' => true, 'message' => 'Đã lưu cấu hình thành công!']);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
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
                $settings['nec_percent'] = 20; $settings['ffa_percent'] = 50; 
            }

            $jars = [
                'nec' => $settings['nec_percent'], 'ffa' => $settings['ffa_percent'],
                'ltss' => $settings['ltss_percent'], 'edu' => $settings['edu_percent'],
                'play' => $settings['play_percent'], 'give' => $settings['give_percent']
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
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
}