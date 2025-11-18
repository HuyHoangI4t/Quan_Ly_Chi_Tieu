<?php


class App
{
    private $controller = 'login_signup';
    private $method = 'index';
    private $params = [];

    public function __construct()
    {

        // Tải cấu hình
        require_once CONFIG_PATH . '/database.php';

        // Bao gồm các lớp lõi
        require_once APP_PATH . '/core/Controllers.php';
        require_once APP_PATH . '/core/Views.php';
        require_once APP_PATH . '/core/ConnectDB.php';
    }

    public function run()
    {
        $this->parseUrl();
        $this->loadController();
        $this->callMethod();
    }

    private function parseUrl()
    {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);

            // Chuyển hướng /login và /register tới /login_signup
            if (isset($url[0]) && ($url[0] == 'login' || $url[0] == 'register') && !isset($url[1])) {
                header('Location: ' . BASE_URL . '/login_signup');
                exit();
            }

            // Xử lý trường hợp đặc biệt cho 'login_signup'
            if (isset($url[0]) && $url[0] == 'login_signup' && !isset($url[1])) {
                $url[1] = 'index'; // method = 'index'
            }

            // Bộ điều khiển
            if (!empty($url[0])) {
                $this->controller = ucfirst($url[0]);
            }

            // Phương thức
            if (!empty($url[1])) {
                $this->method = $url[1];
            }

            // Tham số
            if (count($url) > 2) {
                $this->params = array_slice($url, 2);
            }
        } else {
            // Nếu không có URL nào được cung cấp, hãy chuyển hướng tới dashboard
            header('Location: ' . BASE_URL . '/login_signup');
            exit();
        }
    }

    private function loadController()
    {
        $controllerFile = APP_PATH . '/controllers/' . $this->controller . '.php';

        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            $this->controller = new $this->controller();
        } else {
            // Tải bộ điều khiển mặc định
            require_once APP_PATH . '/controllers/Login_signup.php';
            $this->controller = new Login_signup();
        }
    }

    private function callMethod()
    {
        if (method_exists($this->controller, $this->method)) {
            call_user_func_array([$this->controller, $this->method], $this->params);
        } else {
            // Gọi phương thức mặc định
            call_user_func([$this->controller, 'index']);
        }
    }
}
