<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Response $response, callable $next)
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            // Redirect to login page
            header('Location: ' . BASE_URL . '/login_signup');
            exit();
        }

        // User is authenticated, continue to next middleware or route handler
        return $next();
    }
}
