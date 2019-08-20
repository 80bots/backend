<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function sendResetLinkResponse(Request $request, $response)
    {
        if($request->wantsJson()) {
            return $this->success(null, __('auth.link_sent'));
        } else {
            return back()->with('status', trans($response));
        }
    }

    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        if($request->wantsJson()) {
            return response()->json([
                'errors' => ['email' => [__('auth.not_found')]]
            ], 422);
        } else {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => trans($response)]);
        }
    }

    public function apiShowLinkRequestForm(Request $request)
    {
        return response()->json([]);
    }
}
