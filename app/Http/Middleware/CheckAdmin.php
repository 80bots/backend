<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAdmin
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
        if (Auth::check() && $request->user()->role->name == 'Admin') {
            return $next($request);
        } else {
            Auth::logout();
            return redirect('/login')->with('error', 'Unauthorized');
        }
    }
}
