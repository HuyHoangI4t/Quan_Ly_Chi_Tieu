<?php
namespace App\Controllers\Admin;

use App\Core\Controllers;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Category;
use App\Middleware\AuthCheck;

class Dashboard extends Controllers
{
    protected $userModel;
    protected $transactionModel;
    protected $categoryModel;

    public function __construct()
    {
        parent::__construct();
        
        // Kiểm tra quyền admin
        AuthCheck::requireAdmin();
        
        $this->userModel = $this->model('User');
        $this->transactionModel = $this->model('Transaction');
        $this->categoryModel = $this->model('Category');
    }

    public function index()
    {
        // Get system statistics
        $stats = [
            'total_users' => $this->getTotalUsers(),
            'active_users' => $this->getActiveUsers(),
            'total_transactions' => $this->getTotalTransactions(),
            'total_categories' => $this->getTotalCategories(),
            'recent_users' => $this->getRecentUsers(5),
            'system_activity' => $this->getSystemActivity()
        ];

        // Chart data: monthly totals for last 12 months (income/expense) across all users
        $chart = $this->getMonthlySpendingLast12Months();

        // Category breakdown for last 30 days
        $categoryBreakdown = $this->getCategoryBreakdownLast30Days();

        $data = [
            'title' => 'Admin Dashboard - Quản lý hệ thống',
            'stats' => $stats
            , 'chart' => $chart
            , 'category_breakdown' => $categoryBreakdown
        ];

        $this->view('admin/dashboard', $data);
    }

    private function getMonthlySpendingLast12Months()
    {
        $db = (new \App\Core\ConnectDB())->getConnection();
        $start = date('Y-m-01', strtotime('-11 months'));
        $end = date('Y-m-t');

        $stmt = $db->prepare("SELECT DATE_FORMAT(date, '%Y-%m') as period,
            SUM(CASE WHEN type = 'income' THEN ABS(amount) ELSE 0 END) as income,
            SUM(CASE WHEN type = 'expense' THEN ABS(amount) ELSE 0 END) as expense
            FROM transactions
            WHERE date BETWEEN ? AND ?
            GROUP BY period
            ORDER BY period ASC");

        $stmt->execute([$start, $end]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Build full months array
        $periods = [];
        $labels = [];
        $income = [];
        $expense = [];

        $current = new \DateTime($start);
        $endDate = new \DateTime($end);
        while ($current <= $endDate) {
            $key = $current->format('Y-m');
            $labels[] = $current->format('M Y');
            $periods[$key] = ['income' => 0, 'expense' => 0];
            $current->modify('+1 month');
        }

        foreach ($rows as $r) {
            if (isset($periods[$r['period']])) {
                $periods[$r['period']]['income'] = (float)$r['income'];
                $periods[$r['period']]['expense'] = (float)$r['expense'];
            }
        }

        foreach ($periods as $p) {
            $income[] = $p['income'];
            $expense[] = $p['expense'];
        }

        return ['labels' => $labels, 'income' => $income, 'expense' => $expense];
    }

    private function getCategoryBreakdownLast30Days()
    {
        $db = (new \App\Core\ConnectDB())->getConnection();
        $start = date('Y-m-d', strtotime('-29 days'));
        $end = date('Y-m-d');

        $stmt = $db->prepare("SELECT c.name, SUM(ABS(t.amount)) as total
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.date BETWEEN ? AND ? AND t.type = 'expense'
            GROUP BY c.id, c.name
            ORDER BY total DESC
            LIMIT 10");

        $stmt->execute([$start, $end]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $labels = [];
        $data = [];
        foreach ($rows as $r) {
            $labels[] = $r['name'];
            $data[] = (float)$r['total'];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function getTotalUsers()
    {
        $users = $this->userModel->getAllUsers();
        return count($users);
    }

    private function getActiveUsers()
    {
        $users = $this->userModel->getAllUsers();
        return count(array_filter($users, function($user) {
            return $user['is_active'] == 1;
        }));
    }

    private function getTotalTransactions()
    {
        $db = (new \App\Core\ConnectDB())->getConnection();
        $stmt = $db->query("SELECT COUNT(*) as total FROM transactions");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'];
    }

    private function getTotalCategories()
    {
        $db = (new \App\Core\ConnectDB())->getConnection();
        $stmt = $db->query("SELECT COUNT(*) as total FROM categories WHERE is_default = 1");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'];
    }

    private function getRecentUsers($limit = 5)
    {
        $db = (new \App\Core\ConnectDB())->getConnection();
        $stmt = $db->prepare("SELECT id, username, email, full_name, role, created_at FROM users ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getSystemActivity()
    {
        $db = (new \App\Core\ConnectDB())->getConnection();
        
        // Get transactions stats for last 30 days
        $stmt = $db->query("
            SELECT 
                DATE(date) as activity_date,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
            FROM transactions
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(date)
            ORDER BY DATE(date) DESC
            LIMIT 7
        ");
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
