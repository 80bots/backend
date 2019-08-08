<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\MailHelper;
use App\Http\Controllers\Controller;
use App\Role;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
        $user = User::create([
            'name'                      => $data['name'] ?? '',
            'email'                     => $data['email'],
            'password'                  => bcrypt($data['password']),
            'timezone'                  => $data['timezone'] ?? '',
            'verification_token'        => Str::random(16),
            'role_id'                   => Role::getUserRole()->id ?? null,
            'remaining_credits'         => config('auth.register.remaining_credits'),
            'temp_remaining_credits'    => config('auth.register.remaining_credits'),
        ]);

        if (! empty($user)) {
            MailHelper::welcomeEmail($user);
        }

        return $user;
    }

    public function register(Request $request)
    {
        $this->validator($request->all())->validate();
        event(new Registered($user = $this->create($request->all())));

        if (! $request->wantsJson()) {
            return redirect($this->redirectPath())->with('success', 'Please check your Mail for activate your account');
        } else {
            return $this->success(null, __('auth.registered'));
        }
    }
}
