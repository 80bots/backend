<?php

namespace App\Http\Controllers;

use App\Http\Resources\User\UserResource;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckController extends Controller
{
    public function apiCheckLogin(Request $request)
    {
        if(Auth::check()) {
            $user = new UserResource(User::find(Auth::id()));
            return response()->json([ 'user' => $user->response()->getData() ], 200);
        } else {
            return response()->json([ 'reason' => 'Forbidden', 'message' => 'Invalid access token'], 401);
        }
    }
}
