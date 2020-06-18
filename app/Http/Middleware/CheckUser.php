<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class checkUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->isUser()) {
            if(Auth::user()->status == 'active'){
                return $next($request);
            } else {
                Auth::logout();
                return redirect('/login')->with('error','Please Active Your Account First!!');
            }
        } else {
            Auth::logout();
            return redirect('/login')->with('error','Unauthorized');
        }
    }
}
