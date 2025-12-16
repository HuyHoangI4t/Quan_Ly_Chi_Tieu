<?php
namespace App\Controllers\Admin;

use App\Core\Controllers;
use App\Core\Response;
use App\Middleware\AuthCheck;
use App\Middleware\CsrfProtection;

class Settings extends Controllers
{
    protected $db;
    protected $budgetModel;
    protected $userModel;

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireAdmin();
        $this->db = (new \App\Core\ConnectDB())->getConnection();
        $this->budgetModel = $this->model('Budget');
        $this->userModel = $this->model('User');
    }

    /**
     * Trang cài đặt chung (Thông tin cá nhân & Đổi mật khẩu)
     */
    public function index()
    {
        $user = $this->userModel->getUserById($this->getCurrentUserId());
        $this->view->set('title', 'Cài đặt tài khoản');
        $this->view->set('user', $user);
        $this->view->render('admin/settings');
    }

    /**
     * API Đổi mật khẩu
     */
    public function api_change_password()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method invalid');
            return;
        }

        CsrfProtection::verify();
        $data = $this->request->json();
        
        $currentPass = $data['current_password'] ?? '';
        $newPass = $data['new_password'] ?? '';
        $confirmPass = $data['confirm_password'] ?? '';
        $userId = $this->getCurrentUserId();

        if (strlen($newPass) < 6) {
            Response::errorResponse('Mật khẩu mới phải từ 6 ký tự trở lên');
            return;
        }
        if ($newPass !== $confirmPass) {
            Response::errorResponse('Mật khẩu xác nhận không khớp');
            return;
        }

        $user = $this->userModel->getUserById($userId);
        if (!password_verify($currentPass, $user['password'])) {
            Response::errorResponse('Mật khẩu hiện tại không đúng');
            return;
        }

        if ($this->userModel->updatePassword($userId, $newPass)) {
            Response::successResponse('Đổi mật khẩu thành công!');
        } else {
            Response::errorResponse('Lỗi hệ thống');
        }
    }

    // --- PHẦN QUẢN LÝ NGÂN SÁCH (BUDGETS) ---

    public function budgets()
    {
        $stmt = $this->db->query("SELECT b.*, u.username, c.name as category_name FROM budgets b LEFT JOIN users u ON b.user_id = u.id LEFT JOIN categories c ON b.category_id = c.id ORDER BY b.id DESC");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->view->set('title', 'Quản lý Ngân sách người dùng');
        $this->view->set('budgets', $rows);
        $this->view->render('admin/settings_budgets');
    }

    public function new()
    {
        $cats = $this->db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $users = $this->db->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $this->view->set('title', 'Thêm ngân sách');
        $this->view->set('categories', $cats);
        $this->view->set('users', $users);
        $this->view->render('admin/setting_budget_form');
    }

    public function edit()
    {
        $id = isset($this->params[0]) ? (int)$this->params[0] : 0;
        if (!$id) { header('Location: ' . BASE_URL . '/admin/settings/budgets'); exit; }
        
        $stmt = $this->db->prepare("SELECT * FROM budgets WHERE id = ?");
        $stmt->execute([$id]);
        $b = $stmt->fetch(\PDO::FETCH_ASSOC);

        $cats = $this->db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $users = $this->db->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->view->set('title', 'Chỉnh sửa ngân sách');
        $this->view->set('budget', $b);
        $this->view->set('categories', $cats);
        $this->view->set('users', $users);
        $this->view->render('admin/setting_budget_form');
    }

    public function save()
    {
        if ($this->request->method() !== 'POST') { header('Location: ' . BASE_URL . '/admin/settings/budgets'); exit; }
        
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $data = [
            'user_id' => (int)($_POST['user_id'] ?? 0),
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'amount' => floatval(str_replace([',', ' '], ['', ''], $_POST['amount'] ?? 0)),
            'period' => $_POST['period'] ?? 'monthly',
            'start_date' => $_POST['start_date'] ?? date('Y-m-01'),
            'end_date' => $_POST['end_date'] ?? date('Y-m-t'),
            'alert_threshold' => intval($_POST['alert_threshold'] ?? 80)
        ];

        if ($id) {
            // Update logic manual or via model
            $sql = "UPDATE budgets SET user_id=?, category_id=?, amount=?, period=?, start_date=?, end_date=?, alert_threshold=? WHERE id=?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$data['user_id'], $data['category_id'], $data['amount'], $data['period'], $data['start_date'], $data['end_date'], $data['alert_threshold'], $id]);
        } else {
            $sql = "INSERT INTO budgets (user_id, category_id, amount, period, start_date, end_date, alert_threshold) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$data['user_id'], $data['category_id'], $data['amount'], $data['period'], $data['start_date'], $data['end_date'], $data['alert_threshold']]);
        }
        header('Location: ' . BASE_URL . '/admin/settings/budgets');
        exit;
    }

    public function delete()
    {
        $id = isset($this->params[0]) ? (int)$this->params[0] : 0;
        if ($id) {
            $stmt = $this->db->prepare("DELETE FROM budgets WHERE id = ?");
            $stmt->execute([$id]);
        }
        header('Location: ' . BASE_URL . '/admin/settings/budgets');
        exit;
    }
}