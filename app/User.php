<?php

namespace App;

use App\Mail\register;
use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'verification_token', 'password_reset_token', 'auth_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function findUserInstances()
    {
        return self::with('userInstances')->whereHas('userInstances')->get();
    }

    public function userInstances(){
        return $this->hasMany('App\UserInstances');
    }

    public function sendMail($user){
        try {
            Mail::send('mail.register', ['user' => $user], function($mail) use ($user) {
                $mail->to($user->email, $user->name);
                $mail->subject('Test Register mail');
                $mail->from(env('MAIL_FROM_ADDRESS', 'test@technostacks.com'), env('MAIL_FROM_NAME', 'technostacks'));
            });
            return "Success";
        } catch (Exception $ex) {
//            dd($ex->getMessage());
            return "We've got errors!";
        }
    }

    public function role()
    {
        return $this->belongsTo('App\Roles');
    }
}
