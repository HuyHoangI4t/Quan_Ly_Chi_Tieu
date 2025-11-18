<?php
/**
 * login_signupentication Controller
 * Handles user login, registration, and logout
 */

class login_signup extends Controllers
{
    public function index()
    {
        // When accessing the login_signup page, just show the view.
        // The redirect logic for already-logged-in users should be handled
        // by the pages they try to access, not the login page itself.
        $this->view->set('title', 'Đăng nhập & Đăng ký - SmartSpending');
        // Render the existing login_signup view file (app/views/login_signup/index.php)
        $this->view->render('login_signup/index');
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleLogin();
        } else {
            $this->redirect('/login_signup');
        }
    }

    private function handleLogin()
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->view->set('error', 'Vui lòng nhập đầy đủ thông tin');
            $this->index();
            return;
        }

        $userModel = $this->model('User');
        $user = $userModel->login_signupenticate($username, $password);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $this->redirect('/dashboard');
        } else {
            $this->view->set('error', 'Tên đăng nhập hoặc mật khẩu không đúng');
            $this->index();
        }
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleRegister();
        } else {
            $this->redirect('/login_signup');
        }
    }

    private function handleRegister()
    {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');

        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
            $this->view->set('error', 'Vui lòng nhập đầy đủ thông tin');
            $this->index();
            return;
        }

        if ($password !== $confirmPassword) {
            $this->view->set('error', 'Mật khẩu xác nhận không khớp');
            $this->index();
            return;
        }

        if (strlen($password) < 6) {
            $this->view->set('error', 'Mật khẩu phải có ít nhất 6 ký tự');
            $this->index();
            return;
        }

        $userModel = $this->model('User');

        if ($userModel->getUserByUsername($username)) {
            $this->view->set('error', 'Tên đăng nhập đã tồn tại');
            $this->index();
            return;
        }

        if ($userModel->getUserByEmail($email)) {
            $this->view->set('error', 'Email đã được sử dụng');
            $this->index();
            return;
        }

        // Create user
        $userId = $userModel->createUser($username, $email, $password, $fullName);

        if ($userId) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $fullName;
            $this->redirect('/dashboard');
        } else {
            $this->view->set('error', 'Có lỗi xảy ra, vui lòng thử lại');
            $this->index();
        }
    }

    public function logout()
    {
        session_destroy();
        $this->redirect('/login_signup/login');
    }

    /**
     * API endpoint for user registration
     */
    public function api_register()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Yêu cầu không hợp lệ.'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);

            $username = trim($data['username'] ?? '');
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $confirmPassword = $data['confirm_password'] ?? '';
            
            // Basic Validation
            if (empty($username) || empty($email) || empty($password)) {
                $response['message'] = 'Vui lòng điền đầy đủ các trường bắt buộc.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Địa chỉ email không hợp lệ.';
            } elseif (strlen($password) < 8) {
                $response['message'] = 'Mật khẩu phải có ít nhất 8 ký tự.';
            } elseif ($password !== $confirmPassword) {
                $response['message'] = 'Mật khẩu xác nhận không khớp.';
            } else {
                $userModel = $this->model('User');
                if ($userModel->getUserByUsername($username)) {
                    $response['message'] = 'Tên người dùng này đã tồn tại.';
                } elseif ($userModel->getUserByEmail($email)) {
                    $response['message'] = 'Địa chỉ email này đã được sử dụng.';
                } else {
                    // fullName can be the same as username initially
                    $userId = $userModel->createUser($username, $email, $password, $username);
                    if ($userId) {
                        // Log the user in immediately
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['username'] = $username;
                        $response['success'] = true;
                        $response['message'] = 'Đăng ký thành công!';
                        $response['redirect_url'] = BASE_URL . '/dashboard';
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
        $response = ['success' => false, 'message' => 'Yêu cầu không hợp lệ.'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);

            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';

            if (empty($email) || empty($password)) {
                $response['message'] = 'Vui lòng nhập email và mật khẩu.';
            } else {
                $userModel = $this->model('User');
                // The login_signupenticate method in the model already checks for username OR email
                $user = $userModel->login_signupenticate($email, $password);

                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $response['success'] = true;
                    $response['message'] = 'Đăng nhập thành công!';
                    $response['redirect_url'] = BASE_URL . '/dashboard';
                } else {
                    $response['message'] = 'Email hoặc mật khẩu không chính xác.';
                }
            }
        }

        echo json_encode($response);
        exit();
    }
}
