<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleAccess
{
    private $allowedAll = [
        'GET' => [
        ],
        'POST' => [
        ],
        'PUT' => [
        ],
        'PATCH' => [
        ],
        'DELETE' => [
        ],
    ];

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            foreach ($this->allowedAll[$request->method()] as $allowed) {
                if ($allowed !== '/') {
                    $allowed = trim($allowed, '/');
                }

                if ($request->fullUrlIs($allowed) || $request->is($allowed)) {
                    return $next($request);
                }
            }
        }

        return $next($request);
    }
}
