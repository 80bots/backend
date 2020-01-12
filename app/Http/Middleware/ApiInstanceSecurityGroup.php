<?php

namespace App\Http\Middleware;

use App\Jobs\UpdateInstanceSecurityGroup;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ApiInstanceSecurityGroup
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
//        Log::info("Starting ApiInstanceSecurityGroup");

//        if (Auth::check()) {
//            Log::info("ApiInstanceSecurityGroup 2");
//            $user = Auth::user();
//            $ip = $request->ip();
//
//            $visitors = $user->visitors->map(function ($item, $key) {
//                return $item['ip'] ?? null;
//            })->toArray();
//
//            if (!in_array($ip, $visitors)) {
//                dispatch(new UpdateInstanceSecurityGroup($user, $ip));
//            }
//        }

        return $next($request);
    }
}
