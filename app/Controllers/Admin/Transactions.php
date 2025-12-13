<?php
namespace App\Controllers\Admin;

use App\Core\Controllers;
use App\Middleware\AuthCheck;

class Transactions extends Controllers
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireAdmin();
        $this->db = (new \App\Core\ConnectDB())->getConnection();
    }

    public function index()
    {
        $this->transactions();
    }

    public function transactions()
    {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if (!empty($_GET['q'])) {
            $where[] = "(t.description LIKE ? OR u.username LIKE ? OR c.name LIKE ?)";
            $q = '%' . trim($_GET['q']) . '%';
            $params[] = $q; $params[] = $q; $params[] = $q;
        }
        if (!empty($_GET['category_id'])) {
            $where[] = "t.category_id = ?";
            $params[] = (int)$_GET['category_id'];
        }
        if (!empty($_GET['type'])) {
            $where[] = "t.type = ?";
            $params[] = $_GET['type'];
        }

        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) as cnt FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN categories c ON t.category_id = c.id
            $whereSql";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch(\PDO::FETCH_ASSOC)['cnt'] ?? 0;

        $totalPages = max(1, (int)ceil($total / $limit));
        if ($page > $totalPages) { $page = $totalPages; $offset = ($page-1)*$limit; }

        $sql = "SELECT t.*, u.username, c.name as category_name FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN categories c ON t.category_id = c.id
            $whereSql
            ORDER BY t.date DESC, t.id DESC
            LIMIT ? OFFSET ?";

        $paramsWithLimit = $params;
        $paramsWithLimit[] = (int)$limit;
        $paramsWithLimit[] = (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($paramsWithLimit);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Fetch categories for filter and form
        $catStmt = $this->db->query("SELECT id, name FROM categories ORDER BY name ASC");
        $categories = $catStmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->view->set('title', 'Quản lý giao dịch - Admin');
        $this->view->set('transactions', $rows);
        $this->view->set('categories', $categories);
        $this->view->set('current_page', $page);
        $this->view->set('total_pages', $totalPages);
        $this->view->render('admin/transactions');
    }

    public function new()
    {
        $catStmt = $this->db->query("SELECT id, name FROM categories ORDER BY name ASC");
        $categories = $catStmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->view->set('title', 'Thêm giao dịch mới');
        $this->view->set('categories', $categories);
        $this->view->render('admin/transaction_form');
    }

    public function edit()
    {
        $id = isset($this->params[0]) ? (int)$this->params[0] : 0;
        if (!$id) { header('Location: ' . BASE_URL . '/admin/transactions'); exit; }

        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$id]);
        $tx = $stmt->fetch(\PDO::FETCH_ASSOC);

        $catStmt = $this->db->query("SELECT id, name FROM categories ORDER BY name ASC");
        $categories = $catStmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->view->set('title', 'Chỉnh sửa giao dịch');
        $this->view->set('transaction', $tx);
        $this->view->set('categories', $categories);
        $this->view->render('admin/transaction_form');
    }

    public function save()
    {
        // Handle create or update based on POST
        if ($this->request->method() !== 'POST') { header('Location: ' . BASE_URL . '/admin/transactions'); exit; }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null; // optional
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $amount = $_POST['amount'] ?? 0;
        $type = $_POST['type'] ?? 'expense';
        $date = $_POST['date'] ?? date('Y-m-d');
        $desc = trim($_POST['description'] ?? '');

        // Normalize amount: remove commas
        $amount = str_replace([',',' '], ['', ''], $amount);

        if ($id) {
            $stmt = $this->db->prepare("UPDATE transactions SET category_id = ?, amount = ?, description = ?, date = ?, type = ? WHERE id = ?");
            $stmt->execute([$categoryId, $amount, $desc, $date, $type, $id]);
        } else {
            // require user_id
            $uid = $userId ? $userId : 1; // fallback to admin user if not provided
            $stmt = $this->db->prepare("INSERT INTO transactions (user_id, category_id, amount, date, description, type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$uid, $categoryId, $amount, $date, $desc, $type]);
        }

        header('Location: ' . BASE_URL . '/admin/transactions');
        exit;
    }

    public function delete()
    {
        $id = isset($this->params[0]) ? (int)$this->params[0] : 0;
        if ($id) {
            $stmt = $this->db->prepare("DELETE FROM transactions WHERE id = ?");
            $stmt->execute([$id]);
        }
        header('Location: ' . BASE_URL . '/admin/transactions');
        exit;
    }
}
