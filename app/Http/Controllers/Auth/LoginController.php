<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Helpers\UserHelper;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * @return string
     */
    protected function redirectTo()
    {
        if(Auth::user()->isAdmin()) {
            return route('admin.bots.index');
        } else {
            return route('bots.index');
        }
    }

    /**
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function apiLogin(Request $request)
    {
        $data = $request->only('email', 'password');

        $user = User::where('email', '=', $data['email'])->first();

        if (empty($user)) {
            return $this->notFound(__('auth.forbidden'), __('auth.not_found'));
        }

        if ($user->status !== 'active') {
            return $this->forbidden(__('auth.forbidden'),  __('auth.inactive'));
        }

        if (! empty($user->deleted_at)) {
            return $this->forbidden(__('auth.forbidden'),  __('auth.deleted'));
        }

        if (Auth::attempt($data)) {
            $result = UserHelper::getUserToken(Auth::user());
            return $this->success($result);
        } else {
            return $this->forbidden(__('auth.forbidden'), __('auth.failed'));
        }
    }
}
