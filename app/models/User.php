<?php
/**
 * User Model
 * Handles user-related database operations
 */

class User
{
    private $db;

    public function __construct()
    {
        $this->db = ConnectDB::getInstance();
    }

    /**
     * login_signupenticate user
     * @param string $username
     * @param string $password
     * @return array|null
     */
    public function login_signupenticate($username, $password)
    {
        $sql = "SELECT id, username, email, full_name, password FROM users WHERE (username = ? OR email = ?) AND is_active = 1";
        $user = $this->db->fetch($sql, [$username, $username]);

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']); // Remove password from result
            return $user;
        }

        return null;
    }

    /**
     * Get user by ID
     * @param int $id
     * @return array|null
     */
    public function getUserById($id)
    {
        $sql = "SELECT id, username, email, full_name, avatar, created_at FROM users WHERE id = ? AND is_active = 1";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Get user by username
     * @param string $username
     * @return array|null
     */
    public function getUserByUsername($username)
    {
        $sql = "SELECT id, username, email FROM users WHERE username = ?";
        return $this->db->fetch($sql, [$username]);
    }

    /**
     * Get user by email
     * @param string $email
     * @return array|null
     */
    public function getUserByEmail($email)
    {
        $sql = "SELECT id, username, email FROM users WHERE email = ?";
        return $this->db->fetch($sql, [$email]);
    }

    /**
     * Create new user
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $fullName
     * @return int|false
     */
    public function createUser($username, $email, $password, $fullName)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)";
        return $this->db->insert($sql, [$username, $email, $hashedPassword, $fullName]);
    }

    /**
     * Update user profile
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateUser($id, $data)
    {
        $fields = [];
        $params = [];

        if (isset($data['full_name'])) {
            $fields[] = "full_name = ?";
            $params[] = $data['full_name'];
        }

        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $params[] = $data['email'];
        }

        if (isset($data['avatar'])) {
            $fields[] = "avatar = ?";
            $params[] = $data['avatar'];
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->db->update($sql, $params) > 0;
    }

    /**
     * Change user password
     * @param int $id
     * @param string $newPassword
     * @return bool
     */
    public function changePassword($id, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        return $this->db->update($sql, [$hashedPassword, $id]) > 0;
    }

    /**
     * Delete user (soft delete)
     * @param int $id
     * @return bool
     */
    public function deleteUser($id)
    {
        $sql = "UPDATE users SET is_active = 0 WHERE id = ?";
        return $this->db->update($sql, [$id]) > 0;
    }
}
