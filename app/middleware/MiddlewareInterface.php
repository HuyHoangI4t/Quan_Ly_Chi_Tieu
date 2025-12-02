<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

interface MiddlewareInterface
{
    /**
     * Handle an incoming request
     *
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return mixed
     */
    public function handle(Request $request, Response $response, callable $next);
}
