<?php
namespace App\Controllers\Admin;

use App\Core\Controllers;
use App\Middleware\AuthCheck;

class Settings extends Controllers
{
    protected $db;
    protected $budgetModel;

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireAdmin();
        $this->db = (new \App\Core\ConnectDB())->getConnection();
        $this->budgetModel = $this->model('Budget');
    }

    public function index()
    {
        $this->budgets();
    }

    public function budgets()
    {
        // list all budgets with user and category info
        $stmt = $this->db->query("SELECT b.*, u.username, c.name as category_name FROM budgets b LEFT JOIN users u ON b.user_id = u.id LEFT JOIN categories c ON b.category_id = c.id ORDER BY b.id DESC");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->view->set('title', 'Thiết lập - Budgets');
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
        $b = $this->budgetModel->getById($id);
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
            'amount' => floatval(str_replace([',',' '], ['',''], $_POST['amount'] ?? 0)),
            'period' => $_POST['period'] ?? 'monthly',
            'start_date' => $_POST['start_date'] ?? date('Y-m-01'),
            'end_date' => $_POST['end_date'] ?? date('Y-m-t'),
            'alert_threshold' => intval($_POST['alert_threshold'] ?? 80),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        if ($id) {
            $this->budgetModel->update($id, $data);
        } else {
            $this->budgetModel->create($data);
        }

        header('Location: ' . BASE_URL . '/admin/settings/budgets');
        exit;
    }

    public function delete()
    {
        $id = isset($this->params[0]) ? (int)$this->params[0] : 0;
        if ($id) $this->budgetModel->delete($id);
        header('Location: ' . BASE_URL . '/admin/settings/budgets');
        exit;
    }
}
