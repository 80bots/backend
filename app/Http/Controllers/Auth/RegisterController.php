<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\MailHelper;
use App\Helpers\UserHelper;
use App\Http\Controllers\Controller;
use App\Role;
use App\Timezone;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected function redirectTo(){
        return '/';
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
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
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
        $timezone = Timezone::where('timezone', '=', $data['timezone'] ?? '')->first();

        $name = ! empty($data['name']) ? $data['name'] : $data['email'];

        $user = User::create([
            'name'                  => $name,
            'email'                 => $data['email'],
            'password'              => bcrypt($data['password']),
            'timezone_id'           => $timezone->id ?? null,
            'verification_token'    => Str::random(16),
            'role_id'               => Role::getUserRole()->id ?? null,
            'credits'               => config('auth.register.credits'),
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

    public function apiRegister(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            $user = $this->create($request->all());
            event(new Registered($user));

            $this->guard()->login($user);

            $result = UserHelper::getUserToken(Auth::user());

            return $this->success($result);

        } catch (Throwable $throwable) {
            return $this->error('auth.server_error', $throwable->getMessage());
        }
    }
}
