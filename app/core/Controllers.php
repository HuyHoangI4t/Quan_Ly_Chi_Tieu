<?php
namespace App\Core;

class Controllers
{
    protected $view;
    protected $userModel;

    public function __construct()
    {
        $this->view = new Views();
        // Ensure session is started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function model($model)
    {
        $modelPath = APP_PATH . '/models/' . $model . '.php';
        if (file_exists($modelPath)) {
            require_once $modelPath;
            $modelClass = 'App\Models\\' . $model;
            return new $modelClass();
        }
        return null;
    }

    public function view($view, $data = [])
    {
        $this->view->render($view, $data);
    }

    public function redirect($path)
    {
        header('Location: ' . BASE_URL . $path);
        exit();
    }

    protected function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    protected function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    protected function getCurrentUser()
    {
        if ($this->isLoggedIn()) {
            // Lazy load user model
            if (!$this->userModel) {
                $this->userModel = $this->model('User');
            }
            return $this->userModel->getUserById($this->getCurrentUserId());
        }
        return null;
    }
}