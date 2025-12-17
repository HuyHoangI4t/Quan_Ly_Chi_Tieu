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
        $stmt = $this->db->prepare("SELECT c.id, c.name, c.parent_id, c.type as category_type, p.name as parent_name, p.type as parent_type, SUM(ABS(t.amount)) as total
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            LEFT JOIN categories p ON c.parent_id = p.id
            WHERE t.type = 'expense' AND t.date BETWEEN ? AND ?
            GROUP BY c.id, c.name, c.parent_id, p.name, c.type, p.type
            ORDER BY total DESC");
        $stmt->execute([$start, $end]);
        $catRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Group categories by parent for hierarchical display (parent totals + children)
        $category_groups = [];
        foreach ($catRows as $r) {
            $parentName = !empty($r['parent_name']) ? $r['parent_name'] : $r['name'];
            if (!isset($category_groups[$parentName])) {
                $category_groups[$parentName] = [
                    'parent_name' => $parentName,
                    'own_total' => 0.0,
                    'children' => [],
                    'total' => 0.0
                ];
            }

            if (!empty($r['parent_name'])) {
                // this row is a child category
                $category_groups[$parentName]['children'][] = $r;
                $category_groups[$parentName]['total'] += (float)$r['total'];
            } else {
                // this row is a parent category that may have its own transactions
                $category_groups[$parentName]['own_total'] += (float)$r['total'];
                $category_groups[$parentName]['total'] += (float)$r['total'];
            }
            // store type info for later rendering
            if (!empty($r['parent_name'])) {
                // child's type already in $r['category_type']
            } else {
                // parent row: keep parent_type if present
                $category_groups[$parentName]['parent_type'] = $r['parent_type'] ?? ($r['category_type'] ?? null);
            }
        }

        // compute max total across parent groups for progress bar scaling
        $maxTotal = 0;
        foreach ($category_groups as $g) {
            if ($g['total'] > $maxTotal) $maxTotal = $g['total'];
        }
        if ($maxTotal <= 0) $maxTotal = 1;

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

        // CSV export (flat list) - accept GET/POST via $_REQUEST and clean buffers before sending
        if (isset($_REQUEST['export']) && $_REQUEST['export'] === 'csv') {
            if (ob_get_level()) ob_clean();
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename="report_category_' . $start . '_' . $end . '.csv"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            // output BOM for utf-8 so Excel opens correctly
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Parent', 'Category', 'Type', 'Total']);
            foreach ($catRows as $r) fputcsv($out, [$r['parent_name'] ?? $r['name'], $r['name'], $r['category_type'] ?? '', $r['total']]);
            fflush($out);
            fclose($out);
            exit;
        }

        $this->view->set('title', 'Báo cáo - Admin');
        $this->view->set('category_breakdown', $catRows);
        $this->view->set('category_groups', $category_groups);
        $this->view->set('category_max_total', $maxTotal);
        $this->view->set('trend', ['labels' => $labels, 'income' => $income, 'expense' => $expense]);
        $this->view->set('range', ['start' => $start, 'end' => $end]);
        $this->view->render('admin/reports');
    }
}
