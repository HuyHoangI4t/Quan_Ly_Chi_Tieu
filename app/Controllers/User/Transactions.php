<?php

namespace App\Controllers\User;

use App\Core\Controllers;
use App\Core\Response;
use App\Middleware\CsrfProtection;
use App\Middleware\AuthCheck;

class Transactions extends Controllers
{
    private $transactionModel;
    private $categoryModel;

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireUser();
        $this->transactionModel = $this->model('Transaction');
        $this->categoryModel = $this->model('Category');
    }

    public function index()
    {
        $userId = $this->getCurrentUserId();
        
        $currentMonth = date('Y-m');
        $filterMonth = isset($_GET['month']) ? $_GET['month'] : $currentMonth;

        // Load data sơ bộ
        $filters = ['range' => $filterMonth];
        $transactions = $this->transactionModel->getAllByUser($userId, $filters);
        $categories = $this->categoryModel->getAll($userId);

        $limit = 10;
        $totalRecords = count($transactions); 
        $totalPages = ceil($totalRecords / $limit);

        $data = [
            'title' => 'Quản lý thu chi',
            'transactions' => array_slice($transactions, 0, $limit),
            'categories' => $categories,
            'current_month' => $filterMonth,
            'current_page' => 1,
            'total_pages' => $totalPages,
            'current_range' => $filterMonth,
            'current_category' => 'all',
            'csrf_token' => CsrfProtection::generateToken()
        ];

        $this->view('user/transactions', $data);
    }

    // API lấy dữ liệu cho bảng (Javascript gọi hàm này)
    public function api_get_transactions()
    {
        $userId = $this->getCurrentUserId();
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $filters = [
            'range' => $_GET['range'] ?? date('Y-m'),
            'category_id' => $_GET['category'] ?? 'all',
            'sort' => $_GET['sort'] ?? 'newest',
            'limit' => $limit,
            'offset' => $offset
        ];

        $transactions = $this->transactionModel->getAllByUser($userId, $filters);
        $totalRecords = $this->transactionModel->getCount($userId, $filters);
        $totalPages = ceil($totalRecords / $limit);

        $formatted = [];
        foreach ($transactions as $t) {
            $formatted[] = [
                'id' => $t['id'],
                'date' => $t['date'],
                'formatted_date' => date('d M Y', strtotime($t['date'])),
                'description' => htmlspecialchars($t['description']),
                'amount' => $t['amount'],
                'formatted_amount' => number_format(abs($t['amount']), 0, ',', '.') . ' ₫',
                'type' => isset($t['type']) ? $t['type'] : ($t['amount'] >= 0 ? 'income' : 'expense'),
                'category_name' => $t['category_name'] ?? 'Khác',
                'category_id' => $t['category_id'],
                'transaction_date' => $t['date']
            ];
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'data' => [
                'transactions' => $formatted,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'has_prev' => $page > 1,
                    'has_next' => $page < $totalPages
                ]
            ]
        ]);
        exit;
    }

    // [FIXED] API Thêm giao dịch (Tên hàm phải là api_add để khớp với JS)
    public function api_add()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method not allowed');
            return;
        }

        CsrfProtection::verify();

        $data = $this->request->all();
        if (empty($data)) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true) ?? [];
        }

        // Xử lý ngày (JS có thể gửi key 'date' hoặc 'transaction_date')
        $date = $data['date'] ?? $data['transaction_date'] ?? null;

        if (empty($data['amount']) || empty($data['category_id']) || empty($date)) {
            Response::errorResponse('Vui lòng nhập đủ thông tin');
            return;
        }

        $cleanAmount = floatval(str_replace([',', '.'], '', $data['amount']));
        $type = $data['type'] ?? 'expense';
        
        $transData = [
            'user_id' => $this->getCurrentUserId(),
            'category_id' => $data['category_id'],
            'amount' => $type == 'expense' ? -abs($cleanAmount) : abs($cleanAmount),
            'date' => $date,
            'description' => htmlspecialchars($data['description'] ?? ''),
            'type' => $type
        ];

        // If expense, allow confirmation flow when redistribution from other jars is required
        $userId = $this->getCurrentUserId();
        $confirmed = !empty($data['confirmed']);

        if ($transData['type'] === 'expense' && !$confirmed) {
            $expense = abs($transData['amount']);
            $plan = $this->transactionModel->planExpenseCoverage($userId, $expense, $transData['category_id']);

            if (!$plan['enough']) {
                Response::errorResponse('Không đủ số dư trong các hũ để thực hiện giao dịch.');
                return;
            }

            if ($plan['needs_redistribution']) {
                // Return requires confirmation with plan details
                Response::successResponse('Giao dịch cần lấy tiền bù từ các hũ khác. Vui lòng xác nhận.', [
                    'requires_confirmation' => true,
                    'plan' => $plan['plan'] ?? [],
                    'shortfall' => $plan['shortfall'] ?? 0
                ]);
                return;
            }
            // else no redistribution needed -> proceed
        }

        try {
            $newId = $this->transactionModel->create($transData);
            Response::successResponse('Thêm giao dịch thành công!', ['id' => $newId]);
        } catch (\Exception $e) {
            Response::errorResponse($e->getMessage());
        }
    }

    public function api_update($id)
    {
        if ($this->request->method() !== 'POST') return;
        CsrfProtection::verify();

        $data = $this->request->all();
        if (empty($data)) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true) ?? [];
        }

        $cleanAmount = floatval(str_replace([',', '.'], '', $data['amount']));
        $type = $data['type'] ?? 'expense';
        $date = $data['date'] ?? $data['transaction_date'];

        $updateData = [
            'category_id' => $data['category_id'],
            'amount' => $type == 'expense' ? -abs($cleanAmount) : abs($cleanAmount),
            'date' => $date,
            'description' => htmlspecialchars($data['description'] ?? ''),
            'type' => $type
        ];

        if ($this->transactionModel->update($id, $updateData)) {
            Response::successResponse('Cập nhật thành công');
        } else {
            Response::errorResponse('Lỗi cập nhật');
        }
    }

    public function api_delete($id)
    {
        if ($this->request->method() !== 'POST') return;
        CsrfProtection::verify();

        if ($this->transactionModel->deleteTransaction($id, $this->getCurrentUserId())) {
            Response::successResponse('Đã xóa giao dịch');
        } else {
            Response::errorResponse('Lỗi khi xóa');
        }
    }
}