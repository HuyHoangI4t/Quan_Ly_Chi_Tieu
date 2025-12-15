<?php

namespace App\Controllers\User;

use App\Core\Controllers;
use App\Core\Response;
use App\Services\Validator;
use App\Middleware\CsrfProtection;
use App\Middleware\AuthCheck;
use App\Core\ConnectDB;
use App\Services\FinancialUtils;
use PDO;

class Transactions extends Controllers
{
    private $transactionModel;
    private $categoryModel;
    private $budgetModel;
    protected $db;

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireUser();
        $this->transactionModel = $this->model('Transaction');
        $this->categoryModel = $this->model('Category');
        $this->budgetModel = $this->model('Budget');
        $this->db = (new ConnectDB())->getConnection();
    }

    public function index($range = null, $categoryId = 'all', $page = 1)
    {
        $userId = $this->getCurrentUserId();
        if (!$range) {
            $range = date('Y-m');
        }

        $filters = [
            'range' => $range,
            'category_id' => ($categoryId === 'all') ? null : $categoryId,
        ];

        $perPage = 6;
        $offset = ($page - 1) * $perPage;

        $allTransactions = $this->transactionModel->getAllByUser($userId, $filters);
        $totalTransactions = count($allTransactions);
        $totalPages = ceil($totalTransactions / $perPage);
        $transactions = array_slice($allTransactions, $offset, $perPage);
        $categories = $this->categoryModel->getAll();

        $data = [
            'title' => 'Tất cả Giao dịch',
            'transactions' => $transactions,
            'categories' => $categories,
            'current_range' => $range,
            'current_category' => $categoryId,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_transactions' => $totalTransactions,
            'per_page' => $perPage
        ];

        $this->view('user/transactions', $data);
    }

    /**
     * API: Lấy danh sách giao dịch (QUAN TRỌNG: Hàm này bị thiếu gây lỗi JS)
     */
    public function api_get_transactions()
    {
        if ($this->request->method() !== 'GET') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $userId = $this->getCurrentUserId();
            $range = $this->request->get('range', date('Y-m'));
            $categoryId = $this->request->get('category', 'all');
            $page = (int)$this->request->get('page', 1);
            $perPage = (int)$this->request->get('per_page', 6);

            $filters = [
                'range' => $range,
                'category_id' => ($categoryId === 'all') ? null : $categoryId,
            ];

            $allTransactions = $this->transactionModel->getAllByUser($userId, $filters);
            
            // Xử lý sắp xếp (Mặc định mới nhất)
            $sort = $this->request->get('sort', 'newest');
            if ($sort === 'oldest') {
                $allTransactions = array_reverse($allTransactions);
            }

            $totalTransactions = count($allTransactions);
            $totalPages = ceil($totalTransactions / $perPage);
            $offset = ($page - 1) * $perPage;
            $transactions = array_slice($allTransactions, $offset, $perPage);

            // Format dữ liệu trả về
            $formattedTransactions = array_map(function ($t) {
                return [
                    'id' => $t['id'],
                    'amount' => $t['amount'],
                    'description' => $t['description'],
                    'transaction_date' => $t['date'],
                    'category_id' => $t['category_id'],
                    'category_name' => $t['category_name'] ?? 'Không xác định',
                    'type' => $t['type'],
                    'formatted_amount' => number_format(abs($t['amount']), 0, ',', '.') . ' ₫',
                    'formatted_date' => date('d/m/Y', strtotime($t['date']))
                ];
            }, $transactions);

            Response::successResponse('Thành công', [
                'transactions' => $formattedTransactions,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $totalTransactions,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ]);

        } catch (\Exception $e) {
            Response::errorResponse('Lỗi server: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Thêm giao dịch (Đã có logic JARS)
     */
    public function api_add()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            CsrfProtection::verify();
            $userId = $this->getCurrentUserId();
            $data = $this->request->json();

            $validator = new Validator();
            if (!$validator->validateTransaction($data)) {
                Response::errorResponse($validator->getFirstError());
                return;
            }

            $validData = $validator->getData();
            $isConfirmed = isset($data['confirmed']) && $data['confirmed'] === true;

            // 1. Kiểm tra ngân sách (Soft Check)
            if (($validData['type'] ?? 'expense') === 'expense' && !$isConfirmed) {
                $userModel = $this->model('User');
                $currentUser = $userModel->getUserById($userId);
                
                if ($currentUser && ($currentUser['notify_budget_limit'] ?? 0) == 1) {
                    $budgetModel = $this->model('Budget');
                    $budgets = $budgetModel->getBudgetsWithSpending($userId, 'monthly');
                    
                    foreach ($budgets as $budget) {
                        if ($budget['category_id'] == $validData['category_id']) {
                            $spent = abs($budget['spent']);
                            if (($spent + $validData['amount']) > $budget['amount']) {
                                Response::successResponse('Cảnh báo ngân sách', [
                                    'requires_confirmation' => true,
                                    'message' => "Bạn sắp vượt hạn mức chi tiêu cho '" . $budget['category_name'] . "'. Tiếp tục?"
                                ]);
                                return;
                            }
                        }
                    }
                }
            }

            // 2. Tạo giao dịch và xử lý ví
            $result = $this->createTransaction($userId, $validData);

            if ($result['success']) {
                Response::successResponse('Thêm thành công', ['jar_updates' => $this->getJarUpdates($userId, $validData['date'])]);
            } else {
                Response::errorResponse($result['message']);
            }

        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * API: Cập nhật giao dịch (Logic Hoàn tiền -> Trừ mới)
     */
    public function api_update($id)
    {
        if ($this->request->method() !== 'POST') return;

        try {
            CsrfProtection::verify();
            $userId = $this->getCurrentUserId();
            $data = $this->request->json();
            
            // Lấy giao dịch cũ
            $oldTx = $this->transactionModel->getById($id);
            if (!$oldTx || $oldTx['user_id'] != $userId) {
                Response::errorResponse('Giao dịch không tồn tại');
                return;
            }

            $validator = new Validator();
            if (!$validator->validateTransaction($data)) {
                Response::errorResponse($validator->getFirstError());
                return;
            }
            $newData = $validator->getData();

            $this->db->beginTransaction();
            try {
                // 1. Hoàn tác cũ
                $this->processWalletEffect($userId, $oldTx, true);
                // 2. Áp dụng mới
                $this->processWalletEffect($userId, $newData, false);
                
                // 3. Update DB
                $amount = ($newData['type'] == 'expense') ? -abs($newData['amount']) : abs($newData['amount']);
                $this->transactionModel->update($id, [
                    'amount' => $amount,
                    'category_id' => $newData['category_id'],
                    'date' => $newData['date'],
                    'description' => $newData['description'],
                    'type' => $newData['type']
                ]);
                
                $this->db->commit();
                Response::successResponse('Cập nhật thành công', ['jar_updates' => $this->getJarUpdates($userId, $newData['date'])]);
            } catch (\Exception $e) {
                $this->db->rollBack();
                Response::errorResponse('Lỗi cập nhật ví: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            Response::errorResponse($e->getMessage());
        }
    }

    /**
     * API: Xóa giao dịch
     */
    public function api_delete($id)
    {
        if ($this->request->method() !== 'POST') return;
        
        try {
            CsrfProtection::verify();
            $userId = $this->getCurrentUserId();
            $tx = $this->transactionModel->getById($id);
            
            if ($tx && $tx['user_id'] == $userId) {
                $this->db->beginTransaction();
                try {
                    // Hoàn tiền trước khi xóa
                    $this->processWalletEffect($userId, $tx, true);
                    $this->transactionModel->deleteTransaction($id, $userId);
                    $this->db->commit();
                    
                    Response::successResponse('Đã xóa', ['jar_updates' => $this->getJarUpdates($userId, $tx['date'])]);
                } catch (\Exception $e) {
                    $this->db->rollBack();
                    Response::errorResponse('Lỗi xóa: ' . $e->getMessage());
                }
            } else {
                Response::errorResponse('Lỗi quyền truy cập');
            }
        } catch (\Exception $e) {
            Response::errorResponse($e->getMessage());
        }
    }

    /**
     * API: Chuyển tiền giữa các hũ (transfer jar)
     * POST payload: { from: 'play', to: 'nec', amount: 100000 }
     */
    public function api_transfer_jar()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            CsrfProtection::verify();
            $userId = $this->getCurrentUserId();
            $payload = $this->request->json();

            $from = isset($payload['from']) ? trim($payload['from']) : null;
            $to = isset($payload['to']) ? trim($payload['to']) : null;
            $amount = isset($payload['amount']) ? floatval($payload['amount']) : 0;

            $validJars = ['nec','ffa','ltss','edu','play','give'];
            if (!$from || !$to || $from === $to) {
                Response::errorResponse('Tham số không hợp lệ');
                return;
            }
            if (!in_array($from, $validJars) || !in_array($to, $validJars)) {
                Response::errorResponse('Mã hũ không hợp lệ');
                return;
            }
            if ($amount <= 0) {
                Response::errorResponse('Số tiền phải lớn hơn 0');
                return;
            }

            $res = $this->transferBetweenJars($userId, $from, $to, $amount);
            if ($res['success']) {
                Response::successResponse('Chuyển tiền thành công', ['jar_updates' => $this->getJarUpdates($userId, date('Y-m-d'))]);
            } else {
                Response::errorResponse($res['message'] ?? 'Không thể chuyển tiền');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage());
        }
    }

    // --- CÁC HÀM HELPER (Private/Protected) ---

    protected function createTransaction($userId, $data)
    {
        try {
            $this->db->beginTransaction();
            
            // Xử lý cộng/trừ ví
            $this->processWalletEffect($userId, $data, false);

            $amount = ($data['type'] == 'expense') ? -abs($data['amount']) : abs($data['amount']);
            $sql = "INSERT INTO transactions (user_id, category_id, amount, date, description, type, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $this->db->prepare($sql)->execute([$userId, $data['category_id'], $amount, $data['date'], $data['description'], $data['type']]);
            
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return ['success' => true, 'id' => $id];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function processWalletEffect($userId, $data, $isRevert)
    {
        $type = $data['type'];
        $amount = abs($data['amount']);
        $multiplier = $isRevert ? -1 : 1;

        if ($type === 'income') {
            $budgetModel = $this->model('Budget');
            $settings = $budgetModel->getUserSmartSettings($userId);
            
            $jars = [
                'nec' => $settings['nec_percent'], 'ffa' => $settings['ffa_percent'],
                'ltss' => $settings['ltss_percent'], 'edu' => $settings['edu_percent'],
                'play' => $settings['play_percent'], 'give' => $settings['give_percent']
            ];

            // Use upsert to create wallet rows if missing
            $upsert = $this->db->prepare("INSERT INTO user_wallets (user_id, jar_code, balance) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE balance = balance + ?");
            foreach ($jars as $code => $percent) {
                $val = round($amount * ($percent / 100), 2) * $multiplier;
                $upsert->execute([$userId, $code, $val, $val]);
            }
        } else {
            // Expense
            $stmt = $this->db->prepare("SELECT group_type FROM categories WHERE id = ?");
            $stmt->execute([$data['category_id']]);
            $cat = $stmt->fetch(PDO::FETCH_ASSOC);
            $jar = $cat['group_type'] ?? 'nec';
            
            if ($jar && $jar !== 'none') {
                $val = ($isRevert ? $amount : -$amount); // Revert -> Cộng lại, Apply -> Trừ đi
                // Use upsert to ensure row exists; this will add negative balance if necessary
                $upsertExp = $this->db->prepare("INSERT INTO user_wallets (user_id, jar_code, balance) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE balance = balance + ?");
                $upsertExp->execute([$userId, $jar, $val, $val]);
            }
        }
    }

    /**
     * Helper: transfer money between jars atomically
     * Returns ['success'=>bool, 'message'=>string]
     */
    private function transferBetweenJars($userId, $fromJar, $toJar, $amount)
    {
        try {
            $this->db->beginTransaction();

            // Lock both rows for update (ensure deterministic order to avoid deadlocks)
            $ordered = [$fromJar, $toJar];
            sort($ordered);
            $balances = [];
            $stmt = $this->db->prepare("SELECT jar_code, balance FROM user_wallets WHERE user_id = ? AND jar_code = ? FOR UPDATE");
            foreach ($ordered as $code) {
                $stmt->execute([$userId, $code]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $balances[$code] = $row ? floatval($row['balance']) : 0.0;
            }

            $fromBalance = $balances[$fromJar] ?? 0.0;
            if ($fromBalance < $amount) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Số dư Hũ ' . strtoupper($fromJar) . ' không đủ'];
            }

            // Update balances
            $up = $this->db->prepare("INSERT INTO user_wallets (user_id, jar_code, balance) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE balance = ?");
            // subtract from source
            $newFrom = $fromBalance - $amount;
            $up->execute([$userId, $fromJar, $newFrom, $newFrom]);

            $toBalance = $balances[$toJar] ?? 0.0;
            $newTo = $toBalance + $amount;
            $up->execute([$userId, $toJar, $newTo, $newTo]);

            $this->db->commit();
            return ['success' => true];
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function getJarUpdates($userId, $date)
    {
        try {
            $stmt = $this->db->prepare("SELECT jar_code, balance FROM user_wallets WHERE user_id = ?");
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $defaults = ['nec' => 0, 'ffa' => 0, 'ltss' => 0, 'edu' => 0, 'play' => 0, 'give' => 0];
            foreach ($rows as $r) {
                $code = $r['jar_code'] ?? null;
                if ($code && array_key_exists($code, $defaults)) {
                    $defaults[$code] = floatval($r['balance']);
                }
            }

            return $defaults;
        } catch (\Exception $e) {
            return null;
        }
    }
}