<?php
namespace App\Controllers\Admin;

use App\Core\Controllers;
use App\Core\Response;
use App\Middleware\AuthCheck;

class Logs extends Controllers
{
    protected $logModel;

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireAdmin();
        $this->logModel = $this->model('Log');
    }

    /**
     * Display paginated logs
     */
    public function index()
    {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $total = $this->logModel->countLogs();
        $totalPages = max(1, (int)ceil($total / $limit));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $limit;
        }

        $logs = $this->logModel->getLogsPaginated($limit, $offset);

        $this->view->set('title', 'Nhật ký hoạt động - Admin');
        $this->view->set('logs', $logs);
        $this->view->set('current_page', $page);
        $this->view->set('total_pages', $totalPages);
        $this->view->render('admin/logs');
    }
}
