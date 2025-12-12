<?php
namespace App\Controllers\User;

use App\Core\Controllers;
use App\Core\Response;
use App\Services\Validator;
use App\Middleware\CsrfProtection;
use App\Middleware\AuthCheck;

class Budgets extends Controllers
{
    protected $db;
    protected $budgetModel;
    protected $categoryModel;
    protected $transactionModel;

    public function __construct()
    {
        parent::__construct();
        // Kiểm tra quyền user (ngăn admin truy cập)
        $this->db = (new \App\Core\ConnectDB())->getConnection();
        $this->budgetModel = new \App\Models\Budget();
        $this->categoryModel = new \App\Models\Category();
        $this->transactionModel = new \App\Models\Transaction();
    }
    

    /**
     * Display budgets index page (Money Lover style)
     */
    public function index()
    {
        $data = [
            'title' => 'Quản lý Ngân sách'
        ];
        $this->view('user/budgets', $data);
    }

    /**
     * API: Get all budgets with spending data
     * GET /budgets/api_get_all
     */
    public function api_get_all()
    {
        if ($this->request->method() !== 'GET') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $userId = $this->getCurrentUserId();
            $period = $_GET['period'] ?? 'monthly'; // monthly, weekly, yearly
            
            // Get budgets with spending data
            $budgets = $this->budgetModel->getBudgetsWithSpending($userId, $period);

            // Normalize numeric fields to ensure client receives numbers (not localized strings)
            if (is_array($budgets)){
                foreach ($budgets as &$bb) {
                    $bb['amount'] = isset($bb['amount']) ? (float)$bb['amount'] : 0.0;
                    $bb['spent'] = isset($bb['spent']) ? (float)$bb['spent'] : 0.0;
                    // percentage_used may come as string from SQL ROUND(), ensure float
                    $bb['percentage_used'] = isset($bb['percentage_used']) ? (float)$bb['percentage_used'] : 0.0;
                    $bb['remaining'] = isset($bb['remaining']) ? (float)$bb['remaining'] : ($bb['amount'] - $bb['spent']);
                    $bb['is_active'] = isset($bb['is_active']) ? (int)$bb['is_active'] : 0;
                    $bb['alert_threshold'] = isset($bb['alert_threshold']) ? (float)$bb['alert_threshold'] : 80.0;
                    // Ensure category metadata keys exist (avoid undefined index in client)
                    $bb['category_name'] = $bb['category_name'] ?? '';
                    $bb['category_color'] = $bb['category_color'] ?? '';
                    $bb['category_icon'] = $bb['category_icon'] ?? '';
                    $bb['category_group'] = $bb['category_group'] ?? 'needs';
                }
                unset($bb);
            }
            
            // Calculate summary
            $totalBudget = 0;
            $totalSpent = 0;
            $activeCount = 0;
            
            foreach ($budgets as $budget) {
                $totalBudget += $budget['amount'];
                $totalSpent += $budget['spent'];
                if ($budget['is_active']) {
                    $activeCount++;
                }
            }
            
            Response::successResponse('Lấy danh sách ngân sách thành công', [
                'budgets' => $budgets,
                'summary' => [
                    'total_budget' => $totalBudget,
                    'total_spent' => $totalSpent,
                    'remaining' => $totalBudget - $totalSpent,
                    'active_count' => $activeCount,
                    'period' => $period
                ]
            ]);
        } catch (\Exception $e) {
            // Write detailed error to a log file for debugging
            try {
                $logDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
                if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
                $msg = '[' . date('Y-m-d H:i:s') . '] api_get_all error: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n";
                @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'budgets_error.log', $msg, FILE_APPEND);
            } catch (\Exception $ex) {
                // ignore logging failures
            }
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Get expense categories for budget creation
     * GET /budgets/api_get_categories
     */
    public function api_get_categories()
    {
        if ($this->request->method() !== 'GET') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $userId = $this->getCurrentUserId();
            $categories = $this->categoryModel->getExpenseCategories($userId);
            
            Response::successResponse('Lấy danh sách danh mục thành công', [
                'categories' => $categories
            ]);
        } catch (\Exception $e) {
            // Write detailed error to a log file for debugging
            try {
                $logDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
                if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
                $msg = '[' . date('Y-m-d H:i:s') . '] api_get_categories error: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n";
                @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'budgets_error.log', $msg, FILE_APPEND);
            } catch (\Exception $ex) {
                // ignore logging failures
            }
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Get monthly trend data for budgets and spending
     * GET /budgets/api_get_trend?months=6
     */
    public function api_get_trend()
    {
        if ($this->request->method() !== 'GET') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $userId = $this->getCurrentUserId();
            $months = isset($_GET['months']) && is_numeric($_GET['months']) ? intval($_GET['months']) : 6;

            $trend = $this->budgetModel->getMonthlyTrend($userId, $months);

            Response::successResponse('Lấy dữ liệu xu hướng thành công', [
                'trend' => $trend
            ]);
        } catch (\Exception $e) {
            try {
                $logDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
                if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
                $msg = '[' . date('Y-m-d H:i:s') . '] api_get_trend error: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n";
                @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'budgets_error.log', $msg, FILE_APPEND);
            } catch (\Exception $ex) {}
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Create a new budget
     * POST /budgets/api_create
     */
    public function api_create()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $input = $this->request->json();
            
            // Validate input (manual)
            $errors = [];

            if (!isset($input['category_id']) || !is_numeric($input['category_id'])) {
                $errors['category_id'][] = 'category_id phải là số và không được để trống';
            }
            if (!isset($input['amount']) || !is_numeric($input['amount']) || floatval($input['amount']) < 1) {
                $errors['amount'][] = 'amount phải là số >= 1';
            }
            if (!isset($input['period']) || !in_array($input['period'], ['weekly', 'monthly', 'yearly'], true)) {
                $errors['period'][] = 'period phải là một trong: weekly, monthly, yearly';
            }

            if (!empty($errors)) {
                Response::errorResponse('Dữ liệu không hợp lệ', $errors, 400);
                return;
            }

            // Calculate start_date and end_date based on period
            $period = $input['period'];
            $now = new \DateTime();
            
            switch ($period) {
                case 'weekly':
                    $startDate = $now->modify('monday this week')->format('Y-m-d');
                    $endDate = (clone $now)->modify('sunday this week')->format('Y-m-d');
                    break;
                case 'yearly':
                    $startDate = $now->format('Y-01-01');
                    $endDate = $now->format('Y-12-31');
                    break;
                case 'monthly':
                default:
                    $startDate = $now->format('Y-m-01');
                    $endDate = $now->format('Y-m-t');
                    break;
            }

            $data = [
                'user_id' => $userId,
                'category_id' => $input['category_id'],
                'amount' => floatval($input['amount']),
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'alert_threshold' => $input['alert_threshold'] ?? 80,
                'is_active' => 1
            ];

            // Server-side check: total budgets for the period must not exceed total income for that period
            try {
                $summary = $this->budgetModel->getSummary($userId, $period);
                $existingTotal = isset($summary['total_budget_amount']) ? floatval($summary['total_budget_amount']) : 0.0;

                $totals = $this->transactionModel->getTotalsForPeriod($userId, $startDate, $endDate);
                $totalIncome = isset($totals['income']) ? floatval($totals['income']) : 0.0;

                $attemptedTotal = $existingTotal + $data['amount'];

                if ($totalIncome > 0 && $attemptedTotal > $totalIncome) {
                    Response::errorResponse('Tổng giới hạn ngân sách trong kỳ không được vượt tổng thu nhập', [
                        'total_income' => $totalIncome,
                        'existing_budgets_total' => $existingTotal,
                        'attempted_total' => $attemptedTotal
                    ], 400);
                    return;
                }
            } catch (\Exception $e) {
                // If summary/totals calculation fails, log and continue — do not block creation
                try {
                    $logDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
                    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
                    $msg = '[' . date('Y-m-d H:i:s') . '] budget_limit_check error: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n";
                    @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'budgets_error.log', $msg, FILE_APPEND);
                } catch (\Exception $ex) {
                }
            }

            $budgetId = $this->budgetModel->create($data);
            
            if ($budgetId) {
                Response::successResponse('Tạo ngân sách thành công', ['budget_id' => $budgetId]);
            } else {
                Response::errorResponse('Không thể tạo ngân sách');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Update a budget
     * POST /budgets/api_update/{id}
     */
    public function api_update($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $input = $this->request->json();
            
            // Validate input (manual)
            $errors = [];
            if (!isset($input['amount']) || !is_numeric($input['amount']) || floatval($input['amount']) < 1) {
                $errors['amount'][] = 'amount phải là số >= 1';
            }

            if (!empty($errors)) {
                Response::errorResponse('Dữ liệu không hợp lệ', $errors, 400);
                return;
            }

            // Check if budget exists
            $budget = $this->budgetModel->getById($id);
            if (!$budget || $budget['user_id'] != $userId) {
                Response::errorResponse('Không tìm thấy ngân sách', null, 404);
                return;
            }

            $data = [
                'amount' => floatval($input['amount']),
                'alert_threshold' => $input['alert_threshold'] ?? $budget['alert_threshold'],
                'is_active' => isset($input['is_active']) ? intval($input['is_active']) : $budget['is_active']
            ];

            $result = $this->budgetModel->update($id, $data);
            
            if ($result) {
                Response::successResponse('Cập nhật ngân sách thành công');
            } else {
                Response::errorResponse('Không thể cập nhật ngân sách');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Delete a budget
     * POST /budgets/api_delete/{id}
     */
    public function api_delete($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            
            // Check if budget exists
            $budget = $this->budgetModel->getById($id);
            if (!$budget || $budget['user_id'] != $userId) {
                Response::errorResponse('Không tìm thấy ngân sách', null, 404);
                return;
            }
            
            $result = $this->budgetModel->delete($id);
            
            if ($result) {
                Response::successResponse('Xóa ngân sách thành công');
            } else {
                Response::errorResponse('Không thể xóa ngân sách');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Toggle budget active status
     * POST /budgets/api_toggle/{id}
     */
    public function api_toggle($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            
            // Check if budget exists
            $budget = $this->budgetModel->getById($id);
            if (!$budget || $budget['user_id'] != $userId) {
                Response::errorResponse('Không tìm thấy ngân sách', null, 404);
                return;
            }
            
            $result = $this->budgetModel->update($id, [
                'is_active' => $budget['is_active'] ? 0 : 1
            ]);
            
            if ($result) {
                Response::successResponse('Cập nhật trạng thái thành công', [
                    'is_active' => !$budget['is_active']
                ]);
            } else {
                Response::errorResponse('Không thể cập nhật trạng thái');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Lấy dữ liệu Ngân sách thông minh (50/30/20)
     * GET /budgets/api_get_smart_budget
     */
    public function api_get_smart_budget()
    {
        if ($this->request->method() !== 'GET') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $userId = $this->getCurrentUserId();
            
            // 1. Xác định thời gian (Tháng này)
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');

            // 2. Lấy Tổng thu nhập thực tế trong tháng
            $totals = $this->transactionModel->getTotalsForPeriod($userId, $startDate, $endDate);
            $totalIncome = (float)($totals['income'] ?? 0);

           // 3. Lấy Cài đặt tỷ lệ
            $settings = $this->budgetModel->getUserSmartSettings($userId);
            
            // 4. Lấy Chi tiêu thực tế (Model Transaction trả về theo nhóm lớn: needs/wants/savings)
            $actualSpending = $this->transactionModel->getSpendingByGroup($userId, $startDate, $endDate);

            // If transaction model returned 3-way groups (needs/wants/savings), map/allocate them into 6 jars
            // so that the front-end can display spent amounts per jar. If model already returns per-jar keys,
            // keep them as-is.
            $normalizedSpending = [];
            $jarKeys = ['nec','ffa','ltss','edu','play','give'];

            $hasJarKeys = true;
            foreach ($jarKeys as $k) { if (!array_key_exists($k, $actualSpending)) { $hasJarKeys = false; break; } }

            if ($hasJarKeys) {
                $normalizedSpending = $actualSpending;
            } else {
                // Expecting needs/wants/savings
                $needsTotal = floatval($actualSpending['needs'] ?? 0);
                $wantsTotal = floatval($actualSpending['wants'] ?? 0);
                $savingsTotal = floatval($actualSpending['savings'] ?? 0);

                // Read configured percents (fallback to defaults)
                $necPct = intval($settings['nec_percent'] ?? 55);
                $ffaPct = intval($settings['ffa_percent'] ?? 10);
                $ltssPct = intval($settings['ltss_percent'] ?? 10);
                $eduPct = intval($settings['edu_percent'] ?? 10);
                $playPct = intval($settings['play_percent'] ?? 10);
                $givePct = intval($settings['give_percent'] ?? 5);

                // Helper to split a group total into two jars based on their percentage ratio
                $split = function($total, $aPct, $bPct) {
                    $aShare = ($aPct + $bPct) > 0 ? ($aPct / ($aPct + $bPct)) : 0.5;
                    $bShare = 1 - $aShare;
                    return [ $total * $aShare, $total * $bShare ];
                };

                list($necSpent, $ffaSpent) = $split($needsTotal, $necPct, $ffaPct);
                list($ltssSpent, $eduSpent) = $split($wantsTotal, $ltssPct, $eduPct);
                list($playSpent, $giveSpent) = $split($savingsTotal, $playPct, $givePct);

                $normalizedSpending = [
                    'nec' => $necSpent,
                    'ffa' => $ffaSpent,
                    'ltss' => $ltssSpent,
                    'edu' => $eduSpent,
                    'play' => $playSpent,
                    'give' => $giveSpent
                ];
            }

            // Use normalized spending for subsequent output
            $actualSpending = $normalizedSpending;

            // 5. Tính toán dữ liệu so sánh
            $jars = [
                'nec'  => ['label' => 'Thiết yếu (NEC)', 'color' => '#dc3545'], // Red
                'ffa'  => ['label' => 'Tự do TC (FFA)', 'color' => '#ffc107'],  // Yellow
                'ltss' => ['label' => 'Tiết kiệm dài hạn (LTSS)', 'color' => '#0d6efd'], // Blue
                'edu'  => ['label' => 'Giáo dục (EDU)', 'color' => '#0dcaf0'],  // Cyan
                'play' => ['label' => 'Hưởng thụ (PLAY)', 'color' => '#d63384'], // Pink
                'give' => ['label' => 'Cho đi (GIVE)', 'color' => '#198754']   // Green
            ];

            $groupsData = [];
            foreach ($jars as $key => $info) {
                $percent = intval($settings[$key . '_percent'] ?? 0);
                $groupsData[$key] = [
                    'label'     => $info['label'],
                    'color'     => $info['color'],
                    'percent'   => $percent,
                    'allocated' => ($totalIncome * $percent) / 100,
                    'spent'     => floatval($actualSpending[$key] ?? 0)
                ];
            }

            Response::successResponse('Success', [
                'income' => $totalIncome,
                'settings' => $settings,
                'groups' => $groupsData
            ]);

        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * API: Cập nhật tỷ lệ ngân sách
     * POST /budgets/api_update_ratios
     */
    public function api_update_ratios()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

       try {
            $data = $this->request->json();
            $nec  = intval($data['nec'] ?? 0);
            $ffa  = intval($data['ffa'] ?? 0);
            $ltss = intval($data['ltss'] ?? 0);
            $edu  = intval($data['edu'] ?? 0);
            $play = intval($data['play'] ?? 0);
            $give = intval($data['give'] ?? 0);

            if (($nec + $ffa + $ltss + $edu + $play + $give) !== 100) {
                Response::errorResponse('Tổng tỷ lệ phải bằng 100%');
                return;
            }

            $userId = $this->getCurrentUserId();
            $result = $this->budgetModel->updateUserSmartSettings($userId, $nec, $ffa, $ltss, $edu, $play, $give);

            if ($result) Response::successResponse('Cập nhật thành công');
            else Response::errorResponse('Cập nhật thất bại');

        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * API: Lấy 6 lọ (jars) của user
     * GET /budgets/api_get_jars
     */
    public function api_get_jars()
    {
        if ($this->request->method() !== 'GET') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $userId = $this->getCurrentUserId();
            $jars = $this->budgetModel->getUserJars($userId);
            Response::successResponse('Success', ['jars' => $jars]);
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Cập nhật 6 lọ (jars)
     * POST /budgets/api_update_jars
     */
    public function api_update_jars()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $data = $this->request->json();
            $jars = isset($data['jars']) && is_array($data['jars']) ? $data['jars'] : null;
            if (!$jars || count($jars) !== 6) {
                Response::errorResponse('Dữ liệu không hợp lệ: cần mảng 6 phần tử');
                return;
            }
            $jars = array_map('intval', $jars);
            $sum = array_sum($jars);
            if ($sum !== 100) {
                Response::errorResponse('Tổng phần trăm phải bằng 100%');
                return;
            }

            $userId = $this->getCurrentUserId();
            $ok = $this->budgetModel->updateUserJars($userId, $jars);
            if ($ok) {
                Response::successResponse('Cập nhật thành công');
            } else {
                Response::errorResponse('Cập nhật thất bại');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }
}
