<?php
namespace App\Helpers;

/**
 * Helper Functions
 * Các hàm tiện ích dùng chung
 */
class Helper
{
    /**
     * Format số tiền VND
     */
    public static function formatMoney($amount, $decimals = 0)
    {
        return number_format($amount, $decimals, ',', '.') . ' ₫';
    }

    /**
     * Format ngày tháng
     */
    public static function formatDate($date, $format = 'd/m/Y')
    {
        return date($format, strtotime($date));
    }

    /**
     * Escape HTML
     */
    public static function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get transaction type badge class
     */
    public static function getTypeBadgeClass($type)
    {
        return $type === 'income' ? 'badge-income' : 'badge-expense';
    }

    /**
     * Get transaction type text
     */
    public static function getTypeText($type)
    {
        return $type === 'income' ? 'Thu nhập' : 'Chi tiêu';
    }

    /**
     * Truncate string
     */
    public static function truncate($string, $length = 50, $suffix = '...')
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        return substr($string, 0, $length) . $suffix;
    }

    /**
     * Get month name in Vietnamese
     */
    public static function getMonthName($month)
    {
        $months = [
            1 => 'Tháng 1', 2 => 'Tháng 2', 3 => 'Tháng 3',
            4 => 'Tháng 4', 5 => 'Tháng 5', 6 => 'Tháng 6',
            7 => 'Tháng 7', 8 => 'Tháng 8', 9 => 'Tháng 9',
            10 => 'Tháng 10', 11 => 'Tháng 11', 12 => 'Tháng 12'
        ];
        return $months[$month] ?? '';
    }

    /**
     * Calculate percentage
     */
    public static function calculatePercentage($part, $total)
    {
        if ($total == 0) return 0;
        return round(($part / $total) * 100, 2);
    }

    /**
     * Get budget status class
     */
    public static function getBudgetStatusClass($percentage)
    {
        if ($percentage >= 100) return 'danger';
        if ($percentage >= 80) return 'warning';
        return 'success';
    }

    /**
     * Get budget status text
     */
    public static function getBudgetStatusText($percentage)
    {
        if ($percentage >= 100) return 'Vượt ngân sách';
        if ($percentage >= 80) return 'Cảnh báo';
        return 'An toàn';
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Redirect
     */
    public static function redirect($path)
    {
        header('Location: ' . BASE_URL . $path);
        exit();
    }

    /**
     * Get current URL
     */
    public static function currentUrl()
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Check if current page
     */
    public static function isCurrentPage($page)
    {
        $current = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($current, $page) !== false;
    }
}
