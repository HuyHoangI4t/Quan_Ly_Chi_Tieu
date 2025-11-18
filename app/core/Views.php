<?php
/**
 * View Rendering Class
 * Handles template rendering and data passing
 */

class Views
{
    private $data = [];

    /**
     * Set data for the view
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Render a view file
     * @param string $view
     * @param array $data
     */
    public function render($view, $data = [])
    {
        // Merge data
        $this->data = array_merge($this->data, $data);

        // Extract data to variables
        extract($this->data);

        // Include the view file
        $viewFile = APP_PATH . '/views/' . $view . '.php';

        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die('View file not found: ' . $view);
        }
    }

    /**
     * Include a partial view
     * @param string $partial
     * @param array $data
     */
    public function partial($partial, $data = [])
    {
        // Merge data
        $partialData = array_merge($this->data, $data);
        extract($partialData);

        $partialFile = APP_PATH . '/views/partials/' . $partial . '.php';

        if (file_exists($partialFile)) {
            require_once $partialFile;
        } else {
            die('Partial view file not found: ' . $partial);
        }
    }

    /**
     * Get asset URL
     * @param string $path
     * @return string
     */
    public function asset($path)
    {
        return '/public/' . $path;
    }

    /**
     * Escape HTML output
     * @param string $string
     * @return string
     */
    public function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
