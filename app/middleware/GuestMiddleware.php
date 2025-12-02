<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Response $response, callable $next)
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is already logged in
        if (isset($_SESSION['user_id'])) {
            // Redirect to dashboard
            header('Location: ' . BASE_URL . '/dashboard');
            exit();
        }

        // User is not logged in, continue to next middleware or route handler
        return $next();
    }
}
