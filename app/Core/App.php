<?php
namespace App\Core;

class App
{
    protected $controller = 'Login'; // Default controller (Auth\Login)
    protected $method = 'index'; // Default method
    protected $params = [];
    
    public function __construct()
    {
    }
    
    public function run()
    {
        $url = $this->parseUrl();
        // Use new location 'app/Controllers' with namespace 'App\Controllers'
        $preferredNamespace = 'App\\Controllers';
        $preferredFolder = '/Controllers';

        // Keep track if we are using a fallback
        $isFallback = false;
        $intendedController = '';

        // If no controller is specified, use the default Auth\\Login controller
        if (empty($url[0])) {
            $namespace = $preferredNamespace . '\\Auth';
            $folderPath = $preferredFolder . '/Auth';
            $this->controller = 'Login';
        } else {
            // Check if this is an admin route
            if ($url[0] === 'admin') {
                $namespace = $preferredNamespace . '\\Admin';
                $folderPath = $preferredFolder . '/Admin';
                unset($url[0]);
                $url = array_values($url);
                
                // Set admin controller (default to Users)
                if (empty($url[0])) {
                    $this->controller = 'Users';
                } else {
                    $this->controller = ucfirst($url[0]);
                    unset($url[0]);
                }
            } elseif ($url[0] === 'auth') {
                // Auth routes
                $namespace = $preferredNamespace . '\\Auth';
                $folderPath = $preferredFolder . '/Auth';
                unset($url[0]);
                $url = array_values($url);

                // Default auth controller
                if (empty($url[0])) {
                    $this->controller = 'Login';
                } else {
                    $this->controller = ucfirst($url[0]); // Ví dụ: Logout
                    unset($url[0]);
                }
            } else {
                // User routes
                $namespace = $preferredNamespace . '\\User';
                $folderPath = $preferredFolder . '/User';
                $this->controller = ucfirst($url[0]);
                unset($url[0]);
            }
        }

        // Determine controller file under preferred folder
        $controllerFilePreferred = APP_PATH . $folderPath . '/' . $this->controller . '.php';

        if (file_exists($controllerFilePreferred)) {
            require_once $controllerFilePreferred;
            $controllerClassCandidates = [
                $preferredNamespace . '\\' . trim(str_replace('/', '\\', substr($folderPath, strlen($preferredFolder) + 1)), '\\') . '\\' . $this->controller,
                $preferredNamespace . '\\' . $this->controller
            ];
        } else {
            // Fallback to Auth\Login under preferred folder
            $fallbackFile = APP_PATH . '/Controllers/Auth/Login.php';
            if (file_exists($fallbackFile)) {
                require_once $fallbackFile;
                $controllerClassCandidates = [$preferredNamespace . '\\Auth\\Login'];
                
                // [FIX LOGOUT LOOP]
                // Lưu lại tên controller mà user định gọi (ví dụ: Logout)
                $intendedController = $this->controller;
                $isFallback = true;
                
                $this->controller = 'Login';
            } else {
                throw new \Exception('Controller file not found: ' . $controllerFilePreferred);
            }
        }

        // Instantiate controller: try candidate namespaces until one exists
        $instantiated = false;
        foreach ($controllerClassCandidates as $candidate) {
            if (class_exists($candidate)) {
                $this->controller = new $candidate();
                $instantiated = true;
                break;
            }
        }
        if (!$instantiated) {
            // As last resort, try the raw controller name (no namespace)
            if (class_exists($this->controller)) {
                $this->controller = new $this->controller();
            } else {
                throw new \Exception('Controller class not found for ' . $this->controller);
            }
        }

        // [FIX LOGOUT LOOP & METHOD RESOLUTION]
        if (isset($url[1])) {
            // Check if the method exists in the controller
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            } else {
                $this->method = 'index';
            }
        } elseif ($isFallback) {
            // Nếu đang dùng Fallback (tức là gọi /auth/logout mà ko có file Logout.php)
            // Thì thử tìm hàm 'logout()' trong Login controller
            $fallbackMethod = lcfirst($intendedController); // Logout -> logout
            if (method_exists($this->controller, $fallbackMethod)) {
                $this->method = $fallbackMethod;
            }
        }
        
        $this->params = $url ? array_values($url) : [];

        // Inject params if controller supports it
        if (is_object($this->controller) && method_exists($this->controller, 'setParams')) {
            try {
                $this->controller->setParams($this->params);
            } catch (\TypeError $e) {
                // ignore if signature mismatch
            }
        }

        // [SECURITY FIX] Prevent calling private/protected methods or magic methods
        if (!method_exists($this->controller, $this->method)) {
             // Fallback to index if method doesn't exist (double check)
             $this->method = 'index';
        }

        $reflection = new \ReflectionMethod($this->controller, $this->method);
        if (!$reflection->isPublic() || $reflection->isConstructor() || $reflection->isDestructor()) {
            throw new \Exception("Method not allowed");
        }

        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    public function parseUrl()
    {
        $url = [];
        if (isset($_GET['url'])) {
            $url = explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        } else {
            // Fallback for when $_GET['url'] is not populated by mod_rewrite
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $baseUrl = defined('BASE_URL') ? BASE_URL : ''; 

            // Remove BASE_URL from REQUEST_URI to get the clean path
            if ($baseUrl !== '' && strpos($requestUri, $baseUrl) === 0) {
                $path = substr($requestUri, strlen($baseUrl));
            } else {
                $path = $requestUri;
            }

            // Remove any query string parameters
            $path = strtok($path, '?');
            // Remove leading/trailing slashes
            $path = trim($path, '/');
            
            if (!empty($path)) {
                $url = explode('/', $path);
            }
        }
        return $url;
    }
}