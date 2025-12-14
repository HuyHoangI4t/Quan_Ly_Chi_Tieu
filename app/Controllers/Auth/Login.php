<?php

namespace App\Controllers\Auth;

use App\Core\Controllers;
use App\Core\Response;
use App\Core\ConnectDB;

class Login extends Controllers
{
    protected $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = $this->model('User');
    }

    public function index()
    {
        // Nếu đã login thì chuyển hướng
        if ($this->request->session('user_id')) {
            if (($this->request->session('role') ?? 'user') === 'admin') {
                $this->redirect('/admin/dashboard');
            } else {
                $this->redirect('/dashboard');
            }
            return;
        }

        $data = ['title' => 'Đăng nhập & Đăng ký - Smart Spending'];
        $this->view('auth/login', $data);
    }

    /**
     * API: Đăng ký tài khoản mới
     */
    public function api_register()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $data = $this->request->json();

            $fullName = trim($data['full_name'] ?? '');
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $confirmPassword = $data['confirm_password'] ?? '';

            // 1. Validate dữ liệu
            $errors = [];
            if (empty($fullName)) $errors[] = 'Họ tên không được để trống.';
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
            if (empty($password) || strlen($password) < 6) $errors[] = 'Mật khẩu phải từ 6 ký tự.';
            if ($password !== $confirmPassword) $errors[] = 'Mật khẩu nhập lại không khớp.';

            // Kiểm tra email tồn tại
            if ($this->userModel->findByEmail($email)) {
                $errors[] = 'Email này đã được sử dụng.';
            }

            if (!empty($errors)) {
                Response::errorResponse('Đăng ký thất bại', ['errors' => $errors], 400);
                return;
            }

            // 2. Tạo User mới
            $userId = $this->userModel->createUser($username = $email, $email, $password, $fullName);

            if ($userId) {
                // ============================================================
                // [NÂNG CẤP] KHỞI TẠO DỮ LIỆU MẶC ĐỊNH CHO NGƯỜI DÙNG MỚI
                // ============================================================
                
                try {
                    // A. Khởi tạo Cài đặt tỷ lệ 6 hũ (Budget Settings)
                    // Load model Budget để dùng hàm init có sẵn
                    $budgetModel = $this->model('Budget');
                    if ($budgetModel) {
                        $budgetModel->initUserSmartSettings($userId);
                    }

                    // B. Khởi tạo 6 Ví thực tế (User Wallets)
                    // Sử dụng kết nối DB trực tiếp để đảm bảo chạy được ngay cả khi chưa có Wallet Model
                    $db = (new ConnectDB())->getConnection();
                    $jars = ['nec', 'ffa', 'ltss', 'edu', 'play', 'give'];
                    
                    $sql = "INSERT IGNORE INTO user_wallets (user_id, jar_code, balance) VALUES (?, ?, 0)";
                    $stmt = $db->prepare($sql);
                    
                    foreach ($jars as $jarCode) {
                        $stmt->execute([$userId, $jarCode]);
                    }

                } catch (\Exception $e) {
                    // Log lỗi (nếu có) nhưng không chặn user đăng ký thành công
                    // Có thể ghi vào file log của hệ thống
                    error_log("Init data error for user $userId: " . $e->getMessage());
                }
                
                // ============================================================

                Response::successResponse('Đăng ký tài khoản thành công!', [
                    'redirect_url' => '/login' // Frontend sẽ chuyển hướng về trang login
                ]);
            } else {
                Response::errorResponse('Có lỗi xảy ra khi tạo tài khoản.');
            }

        } catch (\Exception $e) {
            Response::errorResponse('Lỗi hệ thống: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Đăng nhập
     */
    public function api_login()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $data = $this->request->json();
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';

            if (empty($email) || empty($password)) {
                Response::errorResponse('Vui lòng nhập email và mật khẩu.', null, 400);
                return;
            }

            $user = $this->userModel->findByEmail($email);

            if ($user && password_verify($password, $user['password'])) {
                // Kiểm tra active
                if (isset($user['is_active']) && $user['is_active'] == 0) {
                     Response::errorResponse('Tài khoản đã bị khóa.');
                     return;
                }

                // Lưu session
                $this->request->setSession('user_id', $user['id']);
                $this->request->setSession('email', $user['email']);
                $this->request->setSession('full_name', $user['full_name']);
                $this->request->setSession('role', $user['role']);
                $this->request->setSession('avatar', $user['avatar'] ?? 'default_avatar.png');

                // Điều hướng dựa trên role
                $redirectUrl = ($user['role'] === 'admin') ? '/admin/dashboard' : '/dashboard';

                Response::successResponse('Đăng nhập thành công!', [
                    'redirect_url' => $redirectUrl,
                    'user' => [
                        'id' => $user['id'],
                        'full_name' => $user['full_name'],
                        'role' => $user['role']
                    ]
                ]);
            } else {
                Response::errorResponse('Email hoặc mật khẩu không đúng.', null, 401);
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi hệ thống: ' . $e->getMessage(), null, 500);
        }
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        $this->redirect('/login');
    }

    // =========================================================================
    // GOOGLE LOGIN HELPERS (Giữ nguyên logic cũ nếu bạn có dùng)
    // =========================================================================

    public function google_login()
    {
        $code = $_GET['code'] ?? null;
        if (!$code) {
            $this->redirect('/login');
            return;
        }

        try {
            // 1. Get Access Token
            $tokenData = $this->getAccessToken($code);
            if (!$tokenData || !isset($tokenData['access_token'])) {
                 throw new \Exception('Không thể lấy Access Token từ Google.');
            }

            // 2. Get User Info
            $googleUser = $this->getGoogleUserInfo($tokenData['access_token']);
            if (!$googleUser || !isset($googleUser['email'])) {
                throw new \Exception('Không thể lấy thông tin người dùng từ Google.');
            }

            $email = $googleUser['email'];
            $name = $googleUser['name'] ?? 'Google User';
            $avatar = $googleUser['picture'] ?? '';
            
            // 3. Check or Create User
            $user = $this->userModel->findByEmail($email);
            if (!$user) {
                // Auto register
                // Password ngẫu nhiên vì login bằng Google
                $randomPass = bin2hex(random_bytes(8)); 
                $userId = $this->userModel->createUser($email, $email, $randomPass, $name);
                
                if ($userId) {
                    // Cập nhật avatar
                    $this->userModel->updateAvatar($userId, $avatar);
                    
                    // [IMPORTANT] Cũng phải khởi tạo ví cho user Google mới
                    $db = (new ConnectDB())->getConnection();
                    $jars = ['nec', 'ffa', 'ltss', 'edu', 'play', 'give'];
                    $sql = "INSERT IGNORE INTO user_wallets (user_id, jar_code, balance) VALUES (?, ?, 0)";
                    $stmt = $db->prepare($sql);
                    foreach ($jars as $jarCode) {
                        $stmt->execute([$userId, $jarCode]);
                    }
                    
                    // Init Settings
                    $budgetModel = $this->model('Budget');
                    if($budgetModel) $budgetModel->initUserSmartSettings($userId);
                    
                    $user = $this->userModel->getUserById($userId);
                } else {
                    throw new \Exception('Lỗi tạo tài khoản Google.');
                }
            }

            // 4. Set Session
            $this->request->setSession('user_id', $user['id']);
            $this->request->setSession('email', $user['email']);
            $this->request->setSession('full_name', $user['full_name']);
            $this->request->setSession('role', $user['role']);
            $this->request->setSession('avatar', $user['avatar']);

            $redirectUrl = ($user['role'] === 'admin') ? '/admin/dashboard' : '/dashboard';
            $this->redirect($redirectUrl);

        } catch (\Exception $e) {
            // Có thể redirect về login kèm thông báo lỗi
            $this->redirect('/login?error=' . urlencode($e->getMessage()));
        }
    }

    private function getAccessToken($code)
    {
        // Kiểm tra xem Constants đã được load chưa, nếu chưa thì define tạm để tránh lỗi
        if (!defined('GOOGLE_CLIENT_ID')) return null;

        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $redirectUri = BASE_URL . '/login/google_login'; // Đảm bảo khớp cấu hình Google Console

        $params = [
            'code' => $code,
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Localhost fix
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        return json_decode($response, true);
    }

    private function getGoogleUserInfo($accessToken)
    {
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        return json_decode($response, true);
    }
}