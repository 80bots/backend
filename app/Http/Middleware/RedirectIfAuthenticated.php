<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param Closure $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {
            return redirect($this->redirectTo());
        }

        return $next($request);
    }

    private function redirectTo()
    {
        // User role
        $role = Auth::user()->role->name;
        // Check user role
        switch ($role) {
            case 'Admin':
                return route('admin.bots.index');
                break;
            case 'User':
                return '/bots';
                break;
            default:
                return '/login';
                break;
        }
    }
}
