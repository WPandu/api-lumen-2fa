<?php

namespace App\Http\Middleware;

use Closure;

class AdjustRequest
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->has('email')) {
            $request->merge([
                'email' => strtolower($request->input('email')),
            ]);
        }

        return $next($request);
    }
}
