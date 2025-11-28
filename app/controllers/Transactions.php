<?php
namespace App\Controllers;

use App\Core\Controllers;

class Transactions extends Controllers
{
    private $transactionModel;
    private $categoryModel;

    public function __construct()
    {
        parent::__construct();
        if (!$this->isLoggedIn()) {
            $this->redirect('/login_signup');
        }
        $this->transactionModel = $this->model('Transaction');
        $this->categoryModel = $this->model('Category');
    }

    public function index($range = 'this_month', $categoryId = 'all')
    {
        $userId = $this->getCurrentUserId();

        $filters = [
            'range' => ($range === 'all') ? null : $range,
            'category_id' => ($categoryId === 'all') ? null : $categoryId,
        ];
        
        $transactions = $this->transactionModel->getAllByUser($userId, $filters);
        $categories = $this->categoryModel->getAll();

        $data = [
            'title' => 'Tất cả Giao dịch',
            'transactions' => $transactions,
            'categories' => $categories,
            'current_range' => $range,
            'current_category' => $categoryId
        ];

        $this->view('transactions/index', $data);
    }

    public function add()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $this->getCurrentUserId();
            
            // Sanitize and prepare data from the form
            $type = $_POST['type'] ?? 'expense';
            $amount = $_POST['amount'] ?? 0;
            $categoryId = $_POST['category_id'] ?? 0;
            $date = $_POST['date'] ?? date('Y-m-d');
            $description = trim($_POST['description'] ?? '');

            // Basic validation
            if ($amount > 0 && !empty($categoryId)) {
                $this->transactionModel->createTransaction(
                    $userId,
                    $categoryId,
                    $amount,
                    $type,
                    $date,
                    $description
                );
            }
        }

        // Redirect back to dashboard to see the result
        $this->redirect('/dashboard');
    }
}
