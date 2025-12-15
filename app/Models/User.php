<?php

namespace App\Models;

use App\Core\ConnectDB;

class User
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    public function createUser($username, $email, $password, $fullName)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user'; // Mặc định là user

        $stmt = $this->db->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashedPassword, $fullName, $role])) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function authenticate($emailOrUsername, $password)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND is_active = 1");
        $stmt->execute([$emailOrUsername, $emailOrUsername]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public function isAdmin($userId)
    {
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $user && $user['role'] === 'admin';
    }

    public function getAllUsers()
    {
        $stmt = $this->db->query("SELECT id, username, email, full_name, role, is_active, created_at FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUsersPaginated($limit, $offset, $search = null)
    {
        $sql = "SELECT id, username, email, full_name, role, is_active, created_at FROM users";
        $params = [];

        if (!empty($search)) {
            $sql .= " WHERE username LIKE :s OR email LIKE :s OR full_name LIKE :s";
            $params[':s'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);

        if (!empty($search)) {
            $stmt->bindValue(':s', $params[':s'], \PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countUsers($search = null)
    {
        $sql = "SELECT COUNT(*) as cnt FROM users";
        $params = [];

        if (!empty($search)) {
            $sql .= " WHERE username LIKE :s OR email LIKE :s OR full_name LIKE :s";
            $params[':s'] = '%' . $search . '%';
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($search)) {
            $stmt->bindValue(':s', $params[':s'], \PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }

    public function updateUserStatus($userId, $isActive)
    {
        // Prevent disabling Super Admin
        if ($this->isSuperAdmin($userId) && $isActive == 0) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        return $stmt->execute([$isActive, $userId]);
    }

    public function updateUserRole($userId, $role)
    {
        // FIX: Check ID trực tiếp thay vì cột không tồn tại
        if ($this->isSuperAdmin($userId)) {
            return false; // Cannot change role of super admin
        }

        $stmt = $this->db->prepare("UPDATE users SET role = ? WHERE id = ?");
        return $stmt->execute([$role, $userId]);
    }

    public function isSuperAdmin($userId)
    {
        // Quy ước: ID 1 là Super Admin
        return (int)$userId === 1;
    }

    public function getUserByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getUserByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getUserById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function updateProfile($userId, $data)
    {
        $stmt = $this->db->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        return $stmt->execute([$data['name'], $data['email'], $userId]);
    }

    public function updatePassword($userId, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $userId]);
    }

    public function updateNotificationSetting($userId, $column, $value)
    {
        $allowedColumns = [
            'notify_budget_limit',
            'notify_goal_reminder',
            'notify_weekly_summary'
        ];

        if (!in_array($column, $allowedColumns)) {
            return false;
        }

        $sql = "UPDATE users SET {$column} = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$value, $userId]);
    }

    public function updateAvatar($userId, $avatarPath)
    {
        $stmt = $this->db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        return $stmt->execute([$avatarPath, $userId]);
    }
}