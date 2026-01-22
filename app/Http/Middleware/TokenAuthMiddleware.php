<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('token');
        $expectedToken = 'VG@123';
        if (!$token || $token !== $expectedToken) {
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized',
                'data' => null,
                'errors' => ['Invalid or missing authentication token']
            ], 401);
        }
        return $next($request);
    }
}
