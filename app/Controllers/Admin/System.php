<?php
namespace App\Controllers\Admin;

use App\Core\Controllers;
use App\Core\Response;
use App\Core\ConnectDB;
use App\Middleware\AuthCheck;

class System extends Controllers
{
    protected $db;
    protected $logModel;

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireAdmin();
        $this->db = (new ConnectDB())->getConnection();
        $this->logModel = $this->model('Log');
    }

    public function index()
    {
        $stmt = $this->db->query("SELECT * FROM system_settings");
        $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        
        $data = [
            'title' => 'Cấu hình hệ thống',
            'settings' => $settings
        ];
        $this->view('admin/system', $data);
    }

    public function save()
    {
        if ($this->request->method() !== 'POST') return;
        $data = $_POST;

        // Load existing values to record changes
        $stmtOld = $this->db->query("SELECT key_name, value FROM system_settings");
        $old = $stmtOld->fetchAll(\PDO::FETCH_KEY_PAIR);

        $changes = [];
        foreach ($data as $key => $value) {
            $oldVal = isset($old[$key]) ? (string)$old[$key] : null;
            $newVal = (string)$value;
            if ($oldVal !== $newVal) {
                $changes[] = $key . ':' . ($oldVal === null ? 'null' : $oldVal) . '->' . $newVal;
            }

            $stmt = $this->db->prepare("INSERT INTO system_settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$key, $value, $value]);
        }

        if (!empty($changes)) {
            try {
                $action = 'update_system:' . implode(';', $changes);
                $this->logModel->logAction($this->getCurrentUserId(), $action, null);
            } catch (\Throwable $e) {
                // suppress logging errors
            }
        }

        header('Location: ' . BASE_URL . '/admin/system?msg=saved');
    }
}
