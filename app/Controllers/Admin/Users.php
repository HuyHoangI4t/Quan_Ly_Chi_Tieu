<?php
namespace App\Controllers\Admin;

use App\Core\Controllers;
use App\Core\Response;
use App\Models\User;
use App\Middleware\AuthCheck;

class Users extends Controllers
{
    protected $userModel;
    protected $logModel;

    public function __construct()
    {
        parent::__construct();
        
        // Kiểm tra quyền admin
        AuthCheck::requireAdmin();
        
        $this->userModel = $this->model('User');
        $this->logModel = $this->model('Log');
    }

    public function index()
    {
        $this->users();
    }

    /**
     * Quản lý người dùng
     */
    public function users()
    {
        // Read pagination & search params from query string
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';

        $limit = 10;
        $offset = ($page - 1) * $limit;

        $totalUsers = $this->userModel->countUsers($q);
        $totalPages = max(1, (int)ceil($totalUsers / $limit));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $limit;
        }

        $users = $this->userModel->getUsersPaginated($limit, $offset, $q);

        $this->view->set('title', 'Quản lý người dùng - Admin');
        $this->view->set('users', $users);
        $this->view->set('current_page', $page);
        $this->view->set('total_pages', $totalPages);
        $this->view->set('q', $q);
        $this->view->render('admin/users');
    }

    /**
     * API: Cập nhật trạng thái người dùng (active/inactive)
     */
    public function api_toggle_user_status()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Invalid request method', null, 405);
            return;
        }

        $data = $this->request->json();
        $userId = $data['user_id'] ?? 0;
        $isActive = $data['is_active'] ?? 1;

        // Không cho phép vô hiệu hóa chính mình
        if ($userId == $this->getCurrentUserId()) {
            Response::errorResponse('Không thể vô hiệu hóa tài khoản của chính bạn');
            return;
        }

        $result = $this->userModel->updateUserStatus($userId, $isActive);
        
        if ($result) {
            // Log admin action
            try { $this->logModel->logAction($this->getCurrentUserId(), 'toggle_user_status', $userId); } catch (\Exception $e) {}
            Response::successResponse('Cập nhật trạng thái thành công');
        } else {
            Response::errorResponse('Có lỗi xảy ra');
        }
    }

    /**
     * API: Cập nhật vai trò người dùng (user/admin)
     */
    public function api_update_user_role()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Invalid request method', null, 405);
            return;
        }

        $data = $this->request->json();
        $userId = $data['user_id'] ?? 0;
        $role = $data['role'] ?? 'user';

        // Không cho phép thay đổi role của super admin (using DB flag instead of hardcoded ID)
        if ($this->userModel->isSuperAdmin($userId)) {
            Response::errorResponse('Không thể thay đổi quyền của tài khoản Super Admin');
            return;
        }

        // Không cho phép tự thay đổi role của chính mình
        if ($userId == $this->getCurrentUserId()) {
            Response::errorResponse('Không thể thay đổi quyền của chính bạn');
            return;
        }

        if (!in_array($role, ['user', 'admin'])) {
            Response::errorResponse('Vai trò không hợp lệ');
            return;
        }

        $result = $this->userModel->updateUserRole($userId, $role);
        
        if ($result) {
            // Log admin role change
            try { $this->logModel->logAction($this->getCurrentUserId(), 'update_user_role:'.$role, $userId); } catch (\Exception $e) {}
            Response::successResponse('Cập nhật vai trò thành công');
        } else {
            Response::errorResponse('Không thể cập nhật vai trò của Super Admin');
        }
    }
}
