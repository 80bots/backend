<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\UserHelper;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function sendResetResponse(Request $request, $response)
    {
        if ($request->wantsJson()) {
            $result = UserHelper::getUserToken(Auth::user());
            return $this->success($result);
        } else {
            return redirect($this->redirectPath())
                ->with('status', trans($response));
        }
    }

    protected function sendResetFailedResponse(Request $request, $response)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'errors' => [
                    'credentials' => __('auth.wrong_credentials')
                ]
            ], 422);
        } else {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => trans($response)]);
        }
    }
}
