<?php

namespace App;

use App\Mail\register;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use Billable;
    use Notifiable;
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'stripe_id', 'card_brand', 'card_last_four', 'trial_ends_at',
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
        return self::with(['UserSubscriptionPlan'=>function($query){
            return $query->orderBy('id','Desc')->first();
        }])->whereHas('userInstances')->get();
    }

    public function userInstances(){
        return $this->hasMany('App\UserInstances');
    }

    public function sendMail($user){
        try {
            Mail::send('mail.register', ['user' => $user], function($mail) use ($user) {
                $mail->to($user->email, $user->name);
                $mail->subject('Test Register mail');
                $mail->from(env('MAIL_FROM_ADDRESS', '80bots@inforca.com'), env('MAIL_FROM_NAME', '80bots'));
            });
            return "Success";
        } catch (Exception $ex) {
            return "We've got errors!";
        }
    }

    public function updateCredit($credits){
        $this->remaining_credits = $this->remaining_credits + $credits;
        if ($this->save()){
            return true;
        }
        return false;
    }

    public function role()
    {
        return $this->belongsTo('App\Roles');
    }

    /**
     * Check if User has a Role associated.
     *
     * @param string|array $name The role to check.
     *
     * @return bool
     */
    public function hasRole($name)
    {
        $role = $this->role()->pluck('name')->toArray();

        if (! empty($role) && is_array($role)) {
            return $role[0] === $name;
        }

        return false;
    }

    public function UserSubscriptionPlan()
    {
        return $this->hasMany('App\UserSubscriptionPlan','user_id');
    }

    public static function UserCreditSendEmail($user){
        try {
            Mail::send('mail.user_credit', ['user' => $user], function($mail) use ($user) {
                $mail->to($user->email, $user->name);
                $mail->subject('Your credit is low please check your credit.');
                $mail->from(env('MAIL_FROM_ADDRESS', '80bots@inforca.com'), env('MAIL_FROM_NAME', '80bots'));
            });
            return "Success";
        } catch (Exception $ex) {
            return "We've got errors!";
        }
    }

    public function updateUserCreditSendEmail($user){

        try {
            Mail::send('mail.update_user_credit', ['user' => $user], function($mail) use ($user) {
                $mail->to($user->email, $user->name);
                $mail->subject('Your account credit is update by admin.');
                $mail->from(env('MAIL_FROM_ADDRESS', '80bots@inforca.com'), env('MAIL_FROM_NAME', '80bots'));
            });
            return "Success";
        } catch (Exception $ex) {
            return "We've got errors!";
        }
    }

    public function DiscussionLikes() {
        return $this->hasMany('App\DiscussionLikes','user_id');
    }

    public function DiscussionDislikes() {
        return $this->hasMany('App\DiscussionDislikes','user_id');
    }
}
