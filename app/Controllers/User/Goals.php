<?php

namespace App\Controllers\User;

use App\Core\Controllers;
use App\Core\Response;
use App\Middleware\CsrfProtection;
use App\Middleware\AuthCheck;
use App\Services\Validator;
use Exception;

/**
 * Goals Controller
 * Quản lý mục tiêu tiết kiệm
 */
class Goals extends Controllers
{

    private $goalModel;
    private $transactionModel;

    public function __construct()
    {
        parent::__construct();
        // Kiểm tra quyền user
        AuthCheck::requireUser();
        $this->goalModel = $this->model('Goal');
        $this->transactionModel = $this->model('Transaction');
    }

    /**
     * Trang danh sách mục tiêu
     */
    public function index()
    {
        $userId = $this->getCurrentUserId();

        // Lấy danh sách mục tiêu
        $goals = $this->goalModel->getByUserId($userId);
        $statistics = $this->goalModel->getStatistics($userId);

        // *** THÊM MỚI: Lấy danh sách Categories để hiển thị trong Modal ***
        $categoryModel = $this->model('Category');
        // Use existing Category::getAll which accepts an optional $userId
        $categories = $categoryModel->getAll($userId);

        $data = [
            'title' => 'Mục Tiêu Tiết Kiệm',
            'goals' => $goals,
            'statistics' => $statistics,
            'categories' => $categories, // Truyền biến này sang View
            'csrf_token' => CsrfProtection::generateToken()
        ];

        $this->view('user/goals', $data);
    }


/**
     * API: Nạp tiền vào mục tiêu (Manual Deposit)
     */
    public function api_deposit() {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method not allowed', null, 405);
            return;
        }
        
        CsrfProtection::verify();
        
        try {
            $data = $this->request->all();
            
            // Validate
            if (empty($data['goal_id']) || empty($data['amount']) || empty($data['date'])) {
                Response::errorResponse('Thiếu thông tin nạp tiền');
                return;
            }

            $userId = $this->getCurrentUserId();
            $goalId = intval($data['goal_id']);
            $amount = floatval(str_replace([',','.'], '', $data['amount'])); // Xử lý 1.000.000 -> 1000000
            $date = $data['date'];
            $note = htmlspecialchars($data['note'] ?? '');

            // Gọi model xử lý transaction
            if ($this->goalModel->deposit($userId, $goalId, $amount, $date, $note)) {
                Response::successResponse('Đã nạp tiền vào mục tiêu thành công!');
            } else {
                Response::errorResponse('Lỗi khi nạp tiền, vui lòng thử lại.');
            }
            
        } catch (Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage());
        }
    }
    
    /**
     * API: Lấy danh sách mục tiêu
     */
    public function api_get_goals()
    {
        try {
            $userId = $this->getCurrentUserId();
            $goals = $this->goalModel->getByUserId($userId);
            $statistics = $this->goalModel->getStatistics($userId);

            Response::successResponse('Goals retrieved successfully', [
                'goals' => $goals,
                'statistics' => $statistics
            ]);
        } catch (Exception $e) {
            Response::errorResponse('Failed to retrieve goals: ' . $e->getMessage());
        }
    }

    /**
     * API: Tạo mục tiêu mới
     */
    public function api_create_goal()
    {
        // ... (Check method & CSRF giữ nguyên) ...

        try {
            $validator = new Validator();
            $data = $this->request->all();

            // Validate cơ bản
            if (empty($data['name']) || empty($data['target_amount']) || empty($data['deadline'])) {
                Response::errorResponse('Vui lòng điền đầy đủ thông tin bắt buộc');
                return;
            }

            $goalData = [
                'user_id' => $this->getCurrentUserId(),
                'name' => htmlspecialchars($data['name']),
                'description' => htmlspecialchars($data['description'] ?? ''),
                'target_amount' => floatval(str_replace([',', '.'], '', $data['target_amount'])), // Xử lý format tiền tệ nếu có
                'deadline' => $data['deadline'],
                // *** THÊM 2 TRƯỜNG MỚI ***
                'start_date' => !empty($data['start_date']) ? $data['start_date'] : date('Y-m-d'), // Mặc định là hôm nay nếu không chọn
                'category_id' => !empty($data['category_id']) ? intval($data['category_id']) : null,
                'status' => 'active'
            ];

            if ($this->goalModel->create($goalData)) {
                Response::successResponse('Đã tạo mục tiêu thành công');
            } else {
                Response::errorResponse('Lỗi khi tạo mục tiêu');
            }
        } catch (Exception $e) {
            Response::errorResponse('Lỗi hệ thống: ' . $e->getMessage());
        }
    }
    /**
     * API: Cập nhật mục tiêu
     */ public function api_update_goal($id = null)
    {
        // ... (Check method & CSRF giữ nguyên) ...

        try {
            $userId = $this->getCurrentUserId();
            $data = $this->request->all();

            $updateData = [
                'name' => htmlspecialchars($data['name']),
                'description' => htmlspecialchars($data['description'] ?? ''),
                'target_amount' => floatval(str_replace([',', '.'], '', $data['target_amount'])),
                'deadline' => $data['deadline'],
                // *** THÊM 2 TRƯỜNG MỚI ***
                'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
                'category_id' => !empty($data['category_id']) ? intval($data['category_id']) : null,
                'status' => $data['status'] ?? 'active'
            ];

            if ($this->goalModel->update($id, $userId, $updateData)) {
                Response::successResponse('Cập nhật mục tiêu thành công');
            } else {
                Response::errorResponse('Lỗi khi cập nhật mục tiêu');
            }
        } catch (Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage());
        }
    }
    /**
     * API: Xóa mục tiêu
     */
    public function api_delete_goal($id = null)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method not allowed', null, 405);
            return;
        }

        if (!$id) {
            Response::errorResponse('Goal ID is required', null, 400);
            return;
        }

        // Verify CSRF token
        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();

            // Kiểm tra quyền sở hữu
            $goal = $this->goalModel->getById($id, $userId);
            if (!$goal) {
                Response::errorResponse('Goal not found', null, 404);
                return;
            }

            // Xóa mục tiêu
            if ($this->goalModel->delete($id, $userId)) {
                Response::successResponse('Goal deleted successfully');
            } else {
                Response::errorResponse('Failed to delete goal');
            }
        } catch (Exception $e) {
            Response::errorResponse('Error: ' . $e->getMessage());
        }
    }

    /**
     * API: Liên kết transaction với goal
     */
    public function api_link_transaction()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method not allowed', null, 405);
            return;
        }

        // Verify CSRF token
        CsrfProtection::verify();

        try {
            $goalId = $this->request->post('goal_id');
            $transactionId = $this->request->post('transaction_id');

            if (!$goalId || !$transactionId) {
                Response::errorResponse('Goal ID and Transaction ID are required', null, 400);
                return;
            }

            $userId = $this->getCurrentUserId();

            // Verify ownership
            $goal = $this->goalModel->getById($goalId, $userId);
            if (!$goal) {
                Response::errorResponse('Goal not found', null, 404);
                return;
            }

            // Link transaction
            if ($this->goalModel->linkTransaction($goalId, $transactionId)) {
                Response::successResponse('Transaction linked to goal successfully');
            } else {
                Response::errorResponse('Failed to link transaction');
            }
        } catch (Exception $e) {
            Response::errorResponse('Error: ' . $e->getMessage());
        }
    }

    /**
     * API: Cập nhật trạng thái mục tiêu
     */
    public function api_update_status($id = null)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method not allowed', null, 405);
            return;
        }

        if (!$id) {
            Response::errorResponse('Goal ID is required', null, 400);
            return;
        }

        // Verify CSRF token
        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $status = $this->request->post('status');

            if (!in_array($status, ['active', 'completed', 'cancelled'])) {
                Response::errorResponse('Invalid status', null, 400);
                return;
            }

            // Verify ownership
            $goal = $this->goalModel->getById($id, $userId);
            if (!$goal) {
                Response::errorResponse('Goal not found', null, 404);
                return;
            }

            // Update status
            if ($this->goalModel->updateStatus($id, $userId, $status)) {
                Response::successResponse('Goal status updated successfully');
            } else {
                Response::errorResponse('Failed to update goal status');
            }
        } catch (Exception $e) {
            Response::errorResponse('Error: ' . $e->getMessage());
        }
    }
}
