<?php
/**
 * Base Controller Class
 * All controllers should extend this class
 */

class Controllers
{
    protected $view;

    public function __construct()
    {
        $this->view = new Views();
    }

    /**
     * Load a model
     * @param string $model
     * @return object
     */
    protected function model($model)
    {
        $modelFile = APP_PATH . '/models/' . $model . '.php';

        if (file_exists($modelFile)) {
            require_once $modelFile;
            return new $model();
        }

        return null;
    }

    /**
     * Redirect to another URL
     * @param string $url
     */
    protected function redirect($url)
    {
        header('Location: ' . BASE_URL . $url);
        exit();
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    protected function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user ID
     * @return int|null
     */
    protected function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }
}
