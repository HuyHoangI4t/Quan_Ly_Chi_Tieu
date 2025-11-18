<?php

class Transactions extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        // Ensure user is logged in to access transaction features
        if (!$this->isLoggedIn()) {
            $this->redirect('/login_signup');
        }
    }

    public function add()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $response = ['success' => false, 'message' => ''];

            // Get current user ID
            $userId = $this->getCurrentUserId();
            if (!$userId) {
                $response['message'] = 'Người dùng chưa đăng nhập.';
                echo json_encode($response);
                exit();
            }

            // Sanitize POST data
            $type = filter_var($_POST['type'] ?? '', FILTER_SANITIZE_STRING);
            $amount = filter_var($_POST['amount'] ?? '', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $categoryId = filter_var($_POST['category_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
            $date = filter_var($_POST['date'] ?? '', FILTER_SANITIZE_STRING);
            $description = filter_var($_POST['description'] ?? '', FILTER_SANITIZE_STRING);

            // Basic validation
            if (empty($type) || empty($amount) || empty($categoryId) || empty($date)) {
                $response['message'] = 'Vui lòng điền đầy đủ các trường bắt buộc.';
                echo json_encode($response);
                exit();
            }

            if (!is_numeric($amount) || $amount < 0) {
                $response['message'] = 'Số tiền không hợp lệ.';
                echo json_encode($response);
                exit();
            }
            
            if (!is_numeric($categoryId) || $categoryId <= 0) {
                $response['message'] = 'Danh mục không hợp lệ.';
                echo json_encode($response);
                exit();
            }

            $transactionModel = $this->model('Transaction');
            if ($transactionModel->addTransaction($userId, $type, $amount, $categoryId, $date, $description)) {
                $response['success'] = true;
                $response['message'] = 'Giao dịch đã được thêm thành công!';
            } else {
                $response['message'] = 'Có lỗi xảy ra khi thêm giao dịch.';
            }

            echo json_encode($response);
            exit();

        } else {
            // If not a POST request, redirect or show an error
            $this->redirect('/'); // Or to a specific transactions page
        }
    }

    // You might add an index method to display transactions here later
    public function index()
    {
        $this->view->set('title', 'Giao Dịch - SmartSpending');

        // Static sample transactions for display
        $transactions = [
            ['date' => '15/10/2025', 'category' => 'Ăn uống', 'description' => 'Bữa trưa tại quán', 'amount' => -450000, 'type' => 'expense'],
            ['date' => '12/10/2025', 'category' => 'Thu nhập', 'description' => 'Lương', 'amount' => 25000000, 'type' => 'income'],
            ['date' => '10/10/2025', 'category' => 'Di chuyển', 'description' => 'Taxi về nhà', 'amount' => -78000, 'type' => 'expense'],
        ];

        $this->view->set('transactions', $transactions);
        $this->view->render('transactions/index');
    }
}
