<?php
namespace App\Models;

use App\Core\ConnectDB;
use \PDO;

class Category
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    public function getAll($userId = null)
    {
        if ($userId === null) {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE user_id IS NULL ORDER BY type, name");
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE user_id IS NULL OR user_id = ? ORDER BY type, name");
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id, $userId = null)
    {
        if ($userId === null) {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ? AND (user_id IS NULL OR user_id = ?)");
            $stmt->execute([$id, $userId]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // [FIX LỖI 500 TẠI ĐÂY]
    public function create($userId, $data)
    {
        $isDefault = ($userId === null) ? 1 : 0;
        
        // Fix: Thêm cột group_type vào SQL cho đủ 7 cột
        $sql = "INSERT INTO categories (user_id, name, type, group_type, color, icon, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        // Mặc định group_type nếu không truyền vào
        $defaultGroup = ($data['type'] === 'income') ? 'none' : 'nec';
        $groupType = $data['group_type'] ?? $defaultGroup;

        $result = $stmt->execute([
            $userId,
            $data['name'],
            $data['type'],
            $groupType, // Đã thêm cột này vào SQL ở trên
            $data['color'] ?? '#3498db',
            $data['icon'] ?? 'fa-circle',
            $isDefault
        ]);
        return $result ? $this->db->lastInsertId() : false;
    }

    // [FIX] Update cũng phải cho sửa group_type
    public function update($id, $userId, $data)
    {
        $groupType = $data['group_type'] ?? 'none';
        
        if ($userId === null) {
            // Admin update
            $sql = "UPDATE categories SET name = ?, type = ?, group_type = ?, color = ?, icon = ? WHERE id = ? AND user_id IS NULL";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['name'],
                $data['type'],
                $groupType,
                $data['color'] ?? '#3498db',
                $data['icon'] ?? 'fa-circle',
                $id
            ]);
        } else {
            // User update
            $sql = "UPDATE categories SET name = ?, type = ?, group_type = ?, color = ?, icon = ? WHERE id = ? AND user_id = ? AND is_default = 0";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['name'],
                $data['type'],
                $groupType,
                $data['color'] ?? '#3498db',
                $data['icon'] ?? 'fa-circle',
                $id,
                $userId
            ]);
        }
    }

    public function delete($id, $userId)
    {
        try {
            if ($userId === null) {
                $sql = "DELETE FROM categories WHERE id = ? AND user_id IS NULL";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$id]);
            } else {
                $sql = "DELETE FROM categories WHERE id = ? AND user_id = ? AND is_default = 0";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$id, $userId]);
            }
            return $result;
        } catch (\PDOException $e) {
            if ($e->getCode() == '23000') return 'Không thể xóa danh mục đang có giao dịch';
            return 'Lỗi database: ' . $e->getMessage();
        }
    }
}