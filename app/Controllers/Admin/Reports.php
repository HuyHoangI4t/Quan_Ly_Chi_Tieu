<?php
namespace App\Controllers\Admin;

use App\Core\Controllers;
use App\Middleware\AuthCheck;

class Reports extends Controllers
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
        // date range
        $start = $_GET['start'] ?? date('Y-m-d', strtotime('-29 days'));
        $end = $_GET['end'] ?? date('Y-m-d');

        // category breakdown (expenses) across all users
        $stmt = $this->db->prepare("SELECT c.name, SUM(ABS(t.amount)) as total
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.type = 'expense' AND t.date BETWEEN ? AND ?
            GROUP BY c.id, c.name
            ORDER BY total DESC");
        $stmt->execute([$start, $end]);
        $catRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // trend: monthly totals for last 12 months
        $months = [];
        $labels = [];
        $income = [];
        $expense = [];
        $cur = new \DateTime(date('Y-m-01', strtotime('-11 months')));
        $endMonth = new \DateTime(date('Y-m-t'));
        while ($cur <= $endMonth) {
            $key = $cur->format('Y-m');
            $labels[] = $cur->format('M Y');
            $months[] = $key;
            $cur->modify('+1 month');
        }

        $trendStmt = $this->db->prepare("SELECT DATE_FORMAT(date,'%Y-%m') as period,
            SUM(CASE WHEN type='income' THEN ABS(amount) ELSE 0 END) as income,
            SUM(CASE WHEN type='expense' THEN ABS(amount) ELSE 0 END) as expense
            FROM transactions WHERE date BETWEEN ? AND ? GROUP BY period ORDER BY period ASC");
        $trendStart = date('Y-m-01', strtotime('-11 months'));
        $trendEnd = date('Y-m-t');
        $trendStmt->execute([$trendStart, $trendEnd]);
        $trendRows = $trendStmt->fetchAll(\PDO::FETCH_ASSOC);
        $map = [];
        foreach ($trendRows as $r) $map[$r['period']] = $r;
        foreach ($months as $m) {
            $income[] = isset($map[$m]) ? (float)$map[$m]['income'] : 0;
            $expense[] = isset($map[$m]) ? (float)$map[$m]['expense'] : 0;
        }

        // CSV export
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="report_category_' . $start . '_' . $end . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Category', 'Total']);
            foreach ($catRows as $r) fputcsv($out, [$r['name'], $r['total']]);
            fclose($out);
            exit;
        }

        $this->view->set('title', 'Báo cáo - Admin');
        $this->view->set('category_breakdown', $catRows);
        $this->view->set('trend', ['labels' => $labels, 'income' => $income, 'expense' => $expense]);
        $this->view->set('range', ['start' => $start, 'end' => $end]);
        $this->view->render('admin/reports');
    }
}
