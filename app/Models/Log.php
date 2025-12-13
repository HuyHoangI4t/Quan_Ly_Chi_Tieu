<?php
namespace App\Models;

use App\Core\ConnectDB;

class Log {
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    /**
     * Insert a log record
     * @param int|null $userId
     * @param string $action
     * @param int|null $targetId
     * @return bool
     */
    public function logAction($userId, $action, $targetId = null)
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $stmt = $this->db->prepare("INSERT INTO system_logs (user_id, action, target_id, ip_address, created_at) VALUES (:uid, :action, :target, :ip, NOW())");
            return $stmt->execute([
                ':uid' => $userId,
                ':action' => $action,
                ':target' => $targetId,
                ':ip' => $ip
            ]);
        } catch (\Exception $e) {
            // suppress to avoid breaking admin actions; optionally log to error log
            error_log('Log::logAction error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get paginated logs
     */
    public function getLogsPaginated($limit, $offset)
    {
        $sql = "SELECT l.*, u.username FROM system_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countLogs()
    {
        $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM system_logs");
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }
}
