<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiCheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param Request  $request
     * @param Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->hasRole('Admin')) {
            return $next($request);
        } else {
            return response()->json([
                'reason'    => __('auth.forbidden'),
                'message'   => __('auth.forbidden')
            ], 401);
        }
    }
}
