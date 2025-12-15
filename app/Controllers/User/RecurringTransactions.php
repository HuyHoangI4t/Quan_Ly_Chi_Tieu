<?php
namespace App\Controllers\User;

use App\Core\Controllers;
use App\Core\Response;
use App\Middleware\AuthCheck;
use App\Middleware\CsrfProtection;

/**
 * Recurring Transactions Controller
 * Manages automatic recurring transactions (monthly salary, rent, subscriptions)
 */
class RecurringTransactions extends Controllers
{
    private $recurringModel;
    private $categoryModel;
    private $budgetModel;

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireUser();
        
        $this->recurringModel = $this->model('RecurringTransaction');
        $this->categoryModel = $this->model('Category');
        $this->budgetModel = $this->model('Budget');
    }

    /**
     * Display recurring transactions page
     */
    public function index()
    {
        $userId = $this->getCurrentUserId();
        $categories = $this->categoryModel->getByUser($userId);
        
        $this->view->render('user/recurring', [
            'categories' => $categories,
            'title' => 'Giao dịch Định kỳ'
        ]);
    }

    /**
     * API: Get all recurring transactions
     */
   public function api_get_all()
    {
        $userId = $this->getCurrentUserId();
        // Lấy tham số period từ URL (GET param), mặc định là 'monthly'
        $period = isset($_GET['period']) ? $_GET['period'] : 'monthly';

        $budgets = $this->budgetModel->getBudgetsWithSpending($userId, $period);
        
        // Trả về JSON đúng chuẩn để JS không bị lỗi
        Response::successResponse('Lấy dữ liệu thành công', [
            'budgets' => $budgets
        ]);
    }

    /**
     * API: Create recurring transaction
     */
 public function api_create()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            CsrfProtection::verify();
        } catch (\Exception $e) {
            Response::errorResponse('CSRF token invalid', null, 403);
            return;
        }

        $userId = $this->getCurrentUserId();
        $data = $this->request->json();

        $categoryId = $data['category_id'] ?? null;
        $amount = $data['amount'] ?? 0;
        $period = $data['period'] ?? 'monthly';

        if (!$categoryId || $amount <= 0) {
            Response::errorResponse('Vui lòng nhập đầy đủ thông tin');
            return;
        }

        // Gọi Model để tạo (giả sử model có hàm createBudget)
        $result = $this->budgetModel->createBudget($userId, $categoryId, $amount, $period);

        if ($result) {
            Response::successResponse('Tạo ngân sách thành công');
        } else {
            Response::errorResponse('Ngân sách cho danh mục này đã tồn tại hoặc lỗi hệ thống');
        }
    }
    /**
     * API: Update recurring transaction
     */
    public function api_update()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $data = $this->request->json();

            if (empty($data['id'])) {
                Response::errorResponse('ID is required', null, 400);
                return;
            }

            // Validation
            $errors = [];
            if (isset($data['amount']) && (!is_numeric($data['amount']) || $data['amount'] <= 0)) {
                $errors['amount'] = 'Số tiền phải lớn hơn 0';
            }
            if (isset($data['frequency']) && !in_array($data['frequency'], ['daily', 'weekly', 'monthly', 'yearly'])) {
                $errors['frequency'] = 'Tần suất không hợp lệ';
            }

            if (!empty($errors)) {
                Response::errorResponse('Validation failed', $errors, 400);
                return;
            }

            // Recalculate next occurrence if frequency changed
            if (isset($data['frequency']) || isset($data['start_date'])) {
                $existing = $this->recurringModel->getById($data['id'], $userId);
                if (!$existing) {
                    Response::errorResponse('Not found', null, 404);
                    return;
                }

                $startDate = $data['start_date'] ?? $existing['start_date'];
                $frequency = $data['frequency'] ?? $existing['frequency'];
                $data['next_occurrence'] = $this->calculateNextOccurrence($startDate, $frequency);
            }

            $updateData = array_filter($data, function($key) {
                return in_array($key, ['category_id', 'amount', 'type', 'description', 'frequency', 'start_date', 'end_date', 'next_occurrence', 'is_active']);
            }, ARRAY_FILTER_USE_KEY);

            $success = $this->recurringModel->update($data['id'], $userId, $updateData);
            
            if ($success) {
                Response::successResponse('Cập nhật thành công');
            } else {
                Response::errorResponse('Update failed', null, 400);
            }
        } catch (\Exception $e) {
            Response::errorResponse('Error: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Delete recurring transaction
     */
    public function api_delete()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $data = $this->request->json();

            if (empty($data['id'])) {
                Response::errorResponse('ID is required', null, 400);
                return;
            }

            $success = $this->recurringModel->delete($data['id'], $userId);
            
            if ($success) {
                Response::successResponse('Xóa thành công');
            } else {
                Response::errorResponse('Delete failed', null, 400);
            }
        } catch (\Exception $e) {
            Response::errorResponse('Error: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Toggle active status
     */
    public function api_toggle()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $data = $this->request->json();

            if (empty($data['id'])) {
                Response::errorResponse('ID is required', null, 400);
                return;
            }

            $existing = $this->recurringModel->getById($data['id'], $userId);
            if (!$existing) {
                Response::errorResponse('Not found', null, 404);
                return;
            }

            $newStatus = $existing['is_active'] ? 0 : 1;
            $success = $this->recurringModel->update($data['id'], $userId, ['is_active' => $newStatus]);
            
            if ($success) {
                Response::successResponse('Cập nhật thành công', ['is_active' => $newStatus]);
            } else {
                Response::errorResponse('Toggle failed', null, 400);
            }
        } catch (\Exception $e) {
            Response::errorResponse('Error: ' . $e->getMessage(), null, 500);
        }
    }

    public function api_get_trend()
    {
        $userId = $this->getCurrentUserId();
        
        // Giả sử model có hàm getTrendData
        // Nếu model chưa có, bạn cần viết thêm trong Model/Budget.php
        $trendData = $this->budgetModel->getTrendData($userId);

        Response::successResponse('Success', [
            'trend' => $trendData
        ]);
    }
    /**
     * Calculate next occurrence date
     */
    private function calculateNextOccurrence($startDate, $frequency)
    {
        $date = new \DateTime($startDate);
        $now = new \DateTime();

        // If start date is in the future, return it
        if ($date > $now) {
            return $date->format('Y-m-d');
        }

        // Otherwise calculate next occurrence from today
        switch ($frequency) {
            case 'daily':
                $date->modify('+1 day');
                break;
            case 'weekly':
                $date->modify('+1 week');
                break;
            case 'monthly':
                $date->modify('+1 month');
                break;
            case 'yearly':
                $date->modify('+1 year');
                break;
        }

        return $date->format('Y-m-d');
    }

    protected function getCurrentUserId()
    {
        return $this->session->get('user_id');
    }
}
