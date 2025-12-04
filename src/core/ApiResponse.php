<?php
namespace App\Core;

/**
 * API Response Helper
 * Provides standardized JSON responses
 */
class ApiResponse
{
    /**
     * Send success response
     * @param string $message Success message
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     */
    public static function success($message = 'Success', $data = null, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        if (ob_get_length()) ob_clean();
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send error response
     * @param string $message Error message
     * @param mixed $data Additional error data
     * @param int $statusCode HTTP status code
     */
    public static function error($message = 'Error', $data = null, $statusCode = 400)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        if (ob_get_length()) ob_clean();
        
        echo json_encode([
            'success' => false,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send validation error response
     * @param array $errors Validation errors
     * @param string $message Error message
     */
    public static function validationError($errors, $message = 'Dữ liệu không hợp lệ')
    {
        self::error($message, ['errors' => $errors], 422);
    }

    /**
     * Send unauthorized response
     * @param string $message Error message
     */
    public static function unauthorized($message = 'Unauthorized')
    {
        self::error($message, null, 401);
    }

    /**
     * Send forbidden response
     * @param string $message Error message
     */
    public static function forbidden($message = 'Forbidden')
    {
        self::error($message, null, 403);
    }

    /**
     * Send not found response
     * @param string $message Error message
     */
    public static function notFound($message = 'Not Found')
    {
        self::error($message, null, 404);
    }

    /**
     * Send method not allowed response
     * @param string $message Error message
     */
    public static function methodNotAllowed($message = 'Method Not Allowed')
    {
        self::error($message, null, 405);
    }

    /**
     * Send server error response
     * @param string $message Error message
     * @param mixed $data Additional error data
     */
    public static function serverError($message = 'Internal Server Error', $data = null)
    {
        self::error($message, $data, 500);
    }
}
