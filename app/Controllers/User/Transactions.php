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
        
        // Lọc theo tháng
        $currentMonth = date('Y-m');
        $filterMonth = isset($_GET['month']) ? $_GET['month'] : $currentMonth;

        $filters = ['range' => $filterMonth];
        $transactions = $this->transactionModel->getAllByUser($userId, $filters);
        
        // Lấy danh mục để hiển thị trong modal thêm mới
        $categories = $this->categoryModel->getAll($userId);

        $data = [
            'title' => 'Quản lý thu chi',
            'transactions' => $transactions,
            'categories' => $categories,
            'current_month' => $filterMonth,
            'csrf_token' => CsrfProtection::generateToken()
        ];

        $this->view('user/transactions', $data);
    }

    /**
     * API: Tạo giao dịch mới
     */
    public function api_create()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method not allowed');
            return;
        }

        CsrfProtection::verify();

        $data = $this->request->all();

        // Validate cơ bản
        if (empty($data['amount']) || empty($data['category_id']) || empty($data['date'])) {
            Response::errorResponse('Vui lòng nhập đủ thông tin');
            return;
        }

        // Chuẩn bị dữ liệu
        $cleanAmount = floatval(str_replace([',', '.'], '', $data['amount']));
        
        // Xác định loại (income/expense) dựa trên category nếu form không gửi lên
        $type = $data['type'] ?? 'expense';
        // Nếu là expense thì lưu số âm (tuỳ logic DB của đại ca, nhưng thường lưu số gốc và dùng cột type)
        // Ở đây giả định lưu số thực, cột type quyết định âm dương khi hiển thị
        
        $transData = [
            'user_id' => $this->getCurrentUserId(),
            'category_id' => $data['category_id'],
            'amount' => $data['type'] == 'expense' ? -abs($cleanAmount) : abs($cleanAmount),
            'date' => $data['date'],
            'description' => htmlspecialchars($data['description'] ?? ''),
            'type' => $type
        ];

        // Gọi Model (Model sẽ tự động + tiền vào Goal nếu có link)
        if ($this->transactionModel->create($transData)) {
            Response::successResponse('Thêm giao dịch thành công!');
        } else {
            Response::errorResponse('Có lỗi xảy ra, vui lòng thử lại.');
        }
    }

    /**
     * API: Xóa giao dịch
     */
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