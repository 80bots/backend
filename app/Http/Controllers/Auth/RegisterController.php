<?php

namespace App\Http\Controllers\Auth;

use App\Mail\register;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */

    protected function redirectTo(){
        return '/login';
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        $user = new User();
        $user->name = $data['email'];
        $user->email = $data['email'];
        $user->password = bcrypt($data['password']);
        $user->timezone = $data['timezone'];
        $user->verification_token = str_random();
        $user->role_id = 2;
        $user->remaining_credits = 8;
        $user->temp_remaining_credits = 8;
        if($user->save()){
           $sendMail = $user->welcomeEmail($user);
        }
        return $user;
    }

    public function register(Request $request)
    {
        $this->validator($request->all())->validate();
        event(new Registered($user = $this->create($request->all())));
        if(!$request->wantsJson()) {
            return redirect($this->redirectPath())->with('success', 'Please check your Mail for activate your account');
        } else {
            return $this->success(null, __('auth.registered'));
        }

    }
}
