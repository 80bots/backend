<?php

namespace App\Http\Controllers;

use App\Http\Resources\User\UserResource;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function apiCheckLogin(Request $request)
    {
        if(Auth::check()) {
            $user = (new UserResource(User::find(Auth::id())))->response()->getData();
            return response()->json([ 'user' => $user->data ?? null ], 200);
        } else {
            return response()->json([ 'reason' => 'Forbidden', 'message' => 'Invalid access token'], 401);
        }
    }
}
