<?php

namespace App\Controllers\User;

use App\Core\Controllers;
use App\Core\Response;
use App\Middleware\CsrfProtection;
use App\Middleware\AuthCheck;

class Budgets extends Controllers
{
    protected $db;
    protected $budgetModel;
    protected $categoryModel;
    protected $transactionModel;
    protected $walletModel; // [MỚI] Thêm Wallet Model

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireUser();
        
        $this->db = (new \App\Core\ConnectDB())->getConnection();
        $this->budgetModel = $this->model('Budget');
        $this->categoryModel = $this->model('Category');
        $this->transactionModel = $this->model('Transaction');
        // [MỚI] Load Wallet Model để lấy số dư thực
        $this->walletModel = $this->model('Wallet');
    }

    /**
     * Hiển thị trang danh sách ngân sách
     */
    public function index()
    {
        $userId = $this->getCurrentUserId();

        // [MỚI] Lấy thông tin 6 ví thực tế để hiển thị ngay (Server Side Rendering)
        $wallets = $this->walletModel->getAllWallets($userId);
        $settings = $this->budgetModel->getUserSmartSettings($userId);

        $data = [
            'title' => 'Quản lý Ngân sách',
            'wallets' => $wallets,   // Truyền sang View
            'settings' => $settings  // Truyền cấu hình % sang View
        ];
        $this->view('user/budgets', $data);
    }

    /**
     * API: Lấy danh sách ngân sách (Giữ nguyên logic cũ)
     */
    public function api_get_all()
    {
        if ($this->request->method() !== 'GET') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $userId = $this->getCurrentUserId();
            $period = $_GET['period'] ?? 'monthly';

            $budgets = $this->budgetModel->getBudgetsWithSpending($userId, $period);

            // Chuẩn hóa dữ liệu
            if (is_array($budgets)) {
                foreach ($budgets as &$bb) {
                    $bb['amount'] = (float)($bb['amount'] ?? 0);
                    $bb['spent'] = (float)($bb['spent'] ?? 0);
                    $bb['percentage_used'] = (float)($bb['percentage_used'] ?? 0);
                    $bb['remaining'] = (float)($bb['remaining'] ?? ($bb['amount'] - $bb['spent']));
                    $bb['is_active'] = (int)($bb['is_active'] ?? 0);
                    $bb['category_group'] = $bb['category_group'] ?? 'nec';
                }
            }

            // Summary
            $totalBudget = 0; $totalSpent = 0; $activeCount = 0;
            foreach ($budgets as $budget) {
                $totalBudget += $budget['amount'];
                $totalSpent += $budget['spent'];
                if ($budget['is_active']) $activeCount++;
            }

            Response::successResponse('Thành công', [
                'budgets' => $budgets,
                'summary' => [
                    'total_budget' => $totalBudget,
                    'total_spent' => $totalSpent,
                    'remaining' => $totalBudget - $totalSpent,
                    'active_count' => $activeCount
                ]
            ]);
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Tạo ngân sách mới (NÂNG CẤP: Kiểm tra số dư ví thực)
     */
    public function api_create()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $input = $this->request->json();

            if (empty($input['category_id'])) Response::errorResponse('Vui lòng chọn danh mục', null, 400);
            if (empty($input['amount']) || $input['amount'] < 1) Response::errorResponse('Số tiền không hợp lệ', null, 400);

            // --- [LOGIC MỚI] CHECK SỐ DƯ VÍ THỰC ---
            $stmt = $this->db->prepare("SELECT group_type FROM categories WHERE id = ?");
            $stmt->execute([$input['category_id']]);
            $cat = $stmt->fetch();

            if ($cat) {
                $jarCode = $cat['group_type'] ?? 'nec';
                
                // 1. Lấy số dư THỰC TẾ của hũ
                $wallet = $this->walletModel->getWallet($userId, $jarCode);
                $currentBalance = (float)$wallet['balance'];

                // 2. Tính tổng ngân sách ĐÃ LÊN KẾ HOẠCH (Active) cho hũ này
                $stmt = $this->db->prepare("
                    SELECT SUM(b.amount) as total_planned 
                    FROM budgets b
                    JOIN categories c ON b.category_id = c.id
                    WHERE b.user_id = ? AND c.group_type = ? AND b.is_active = 1
                ");
                $stmt->execute([$userId, $jarCode]);
                $planned = $stmt->fetch();
                $totalPlanned = (float)($planned['total_planned'] ?? 0);

                // 3. Kiểm tra khả dụng
                $availableToPlan = $currentBalance - $totalPlanned;
                $requestAmount = (float)$input['amount'];

                if ($requestAmount > $availableToPlan) {
                    $showAvailable = max(0, $availableToPlan);
                    Response::errorResponse(
                        "Không đủ tiền trong hũ " . strtoupper($jarCode) . "!",
                        [
                            "message" => "Số dư thực tế: " . number_format($currentBalance) . "đ\n" .
                                         "Đã dùng lập ngân sách: " . number_format($totalPlanned) . "đ\n" .
                                         "Còn lại để lập ngân sách: " . number_format($showAvailable) . "đ"
                        ],
                        400
                    );
                    return;
                }
            }
            // --- [END LOGIC MỚI] ---

            // Thiết lập kỳ hạn
            $period = $input['period'] ?? 'monthly';
            $now = new \DateTime();
            if ($period === 'weekly') {
                $startDate = $now->modify('monday this week')->format('Y-m-d');
                $endDate = (clone $now)->modify('sunday this week')->format('Y-m-d');
            } elseif ($period === 'yearly') {
                $startDate = $now->format('Y-01-01');
                $endDate = $now->format('Y-12-31');
            } else {
                $startDate = $now->format('Y-m-01');
                $endDate = $now->format('Y-m-t');
            }

            $data = [
                'user_id' => $userId,
                'category_id' => $input['category_id'],
                'amount' => $input['amount'],
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'alert_threshold' => $input['alert_threshold'] ?? 80,
                'is_active' => 1
            ];

            $budgetId = $this->budgetModel->create($data);

            if ($budgetId) Response::successResponse('Tạo ngân sách thành công');
            else Response::errorResponse('Không thể tạo ngân sách');

        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    // --- Các hàm API phụ trợ giữ nguyên ---
    public function api_get_categories() {
        $userId = $this->getCurrentUserId();
        Response::successResponse('Success', ['categories' => $this->categoryModel->getExpenseCategories($userId)]);
    }

    public function api_update($id) {
         CsrfProtection::verify();
         $input = $this->request->json();
         $this->budgetModel->update($id, [
             'amount' => $input['amount'],
             'is_active' => $input['is_active'] ?? 1,
             'alert_threshold' => $input['alert_threshold'] ?? 80
         ]);
         Response::successResponse('Updated');
    }

    public function api_delete($id) {
        CsrfProtection::verify();
        $this->budgetModel->delete($id);
        Response::successResponse('Deleted');
    }

    public function api_toggle($id) {
        CsrfProtection::verify();
        $budget = $this->budgetModel->getById($id);
        $this->budgetModel->update($id, ['is_active' => !$budget['is_active']]);
        Response::successResponse('Toggled');
    }

    public function api_get_trend() {
        try {
            $trend = $this->budgetModel->getMonthlyTrend($this->getCurrentUserId());
            Response::successResponse('Success', ['trend' => $trend]);
        } catch (\Exception $e) { Response::errorResponse('Error'); }
    }

    public function api_get_jars() {
        try {
            $userId = $this->getCurrentUserId();
            $wallets = $this->walletModel->getAllWallets($userId);
            $settings = $this->budgetModel->getUserSmartSettings($userId);
            Response::successResponse('Success', ['wallets' => $wallets, 'settings' => $settings]);
        } catch (\Exception $e) { Response::errorResponse('Error'); }
    }
    
    public function api_update_ratios() {
        $data = $this->request->json();
        $this->budgetModel->updateUserSmartSettings($this->getCurrentUserId(), $data['nec'], $data['ffa'], $data['ltss'], $data['edu'], $data['play'], $data['give']);
        Response::successResponse('Updated');
    }
}