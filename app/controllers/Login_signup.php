<?php
namespace App\Controllers;

use App\Core\Controllers;
use App\Models\User; // Use the User model
use App\Middleware\AuthCheck;

class Login_signup extends Controllers
{
    protected $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = $this->model('User');
    }

    public function index()
    {
        // If already logged in, redirect based on role
        if ($this->isLoggedIn()) {
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                $this->redirect('/admin/dashboard');
            } else {
                $this->redirect('/dashboard');
            }
            exit();
        }
        $this->view->set('title', 'Đăng nhập & Đăng ký - Smart Spending');
        $this->view->render('login_signup');
    }

    /**
     * API endpoint for user registration
     */
    public function api_register()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Yêu cầu không hợp lệ.', 'redirect_url' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);

            $fullName = trim($data['full_name'] ?? '');
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $confirmPassword = $data['confirm_password'] ?? '';
            
            // --- Validation ---
            if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
                $response['message'] = 'Vui lòng điền đầy đủ các trường bắt buộc.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Địa chỉ email không hợp lệ.';
            } elseif (strlen($password) < 8) { // Consistent password length
                $response['message'] = 'Mật khẩu phải có ít nhất 8 ký tự.';
            } elseif ($password !== $confirmPassword) {
                $response['message'] = 'Mật khẩu xác nhận không khớp.';
            } else {
                // Use email as username since frontend doesn't provide a separate username field
                $username = $email; 

                if ($this->userModel->getUserByUsername($username)) {
                    $response['message'] = 'Tên người dùng này (email) đã tồn tại.';
                } elseif ($this->userModel->getUserByEmail($email)) {
                    $response['message'] = 'Địa chỉ email này đã được sử dụng.';
                } else {
                    $userId = $this->userModel->createUser($username, $email, $password, $fullName);
                    if ($userId) {
                        $response['success'] = true;
                        $response['message'] = 'Đăng ký thành công! Vui lòng đăng nhập.';
                        $response['switch_to_login'] = true;
                        $response['login_email'] = $email;
                        $response['login_password'] = $password;
                    } else {
                        $response['message'] = 'Đã có lỗi xảy ra trong quá trình đăng ký. Vui lòng thử lại.';
                    }
                }
            }
        }
        
        echo json_encode($response);
        exit();
    }

    /**
     * API endpoint for user login
     */
    public function api_login()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Yêu cầu không hợp lệ.', 'redirect_url' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);

            $email = trim($data['email'] ?? ''); // Frontend only provides email, not username
            $password = $data['password'] ?? '';

            if (empty($email) || empty($password)) {
                $response['message'] = 'Vui lòng nhập email và mật khẩu.';
            } else {
                // The authenticate method in the model already checks for username OR email
                // So, passing email for both parameters works if email is used as username during registration
                $user = $this->userModel->authenticate($email, $password);

                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'] ?? 'user';
                    $response['success'] = true;
                    $response['message'] = 'Đăng nhập thành công!';
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        $response['redirect_url'] = BASE_URL . '/admin/dashboard';
                    } else {
                        $response['redirect_url'] = BASE_URL . '/dashboard';
                    }
                } else {
                    $response['message'] = 'Email hoặc mật khẩu không chính xác.';
                }
            }
        }

        echo json_encode($response);
        exit();
    }

    public function logout()
    {
        session_destroy();
        $this->redirect('/'); // Redirect to the main login/signup page
    }
}