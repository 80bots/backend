<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Billable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use Billable;
    use Notifiable;
    use SoftDeletes;
    use HasApiTokens;

    const ROLE_NAME_USER    = 'User';
    const ROLE_NAME_ADMIN   = 'Admin';

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

    /**
     * Creation of an object for further applying with filters
     *
     * @param $query
     * @return mixed
     */
    public function scopeAjax($query)
    {
        return $query;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userInstances()
    {
        return $this->hasMany(UserInstance::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function UserSubscriptionPlan()
    {
        return $this->hasMany(UserSubscriptionPlan::class,'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function DiscussionLikes()
    {
        return $this->hasMany(DiscussionLikes::class,'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function DiscussionDislikes()
    {
        return $this->hasMany(DiscussionDislikes::class,'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function privateBots()
    {
        return $this->belongsToMany(Bot::class, 'bot_user');
    }

    /**
     * Check if User has a Role associated.
     *
     * @param string $name The role to check.
     *
     * @return bool
     */
    public function hasRole(string $name): bool
    {
        return $this->role()->pluck('name')->first() === $name;
    }

    /**
     * Return users with user role
     * @param $query
     */
    public function scopeOnlyUsers($query)
    {
        $query->whereHas('role', function (Builder $query) {
            $query->where('name', '=', self::ROLE_NAME_USER);
        });
    }

    /**
     * Return users with admin role
     * @param $query
     */
    public function scopeOnlyAdmins($query)
    {
        $query->whereHas('role', function (Builder $query) {
            $query->where('name', '=', self::ROLE_NAME_ADMIN);
        });
    }

    /**
     * Check whether user has admin role
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role()->pluck('name')->first() === self::ROLE_NAME_ADMIN;
    }

    /**
     * Check whether user has user role
     * @return bool
     */
    public function isUser(): bool
    {
        return $this->role()->pluck('name')->first() === self::ROLE_NAME_USER;
    }

    /**
     * TODO:
     * @return User[]|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function findUserInstances()
    {
        return self::with(['UserSubscriptionPlan' => function($query){
            return $query->orderBy('id','Desc')->first();
        }])->whereHas('userInstances')->get();
    }

    /**
     * @param $credits
     * @return bool
     */
    public function updateCredit($credits)
    {
        $this->remaining_credits = $this->remaining_credits + $credits;
        if ($this->save()){
            return true;
        }
        return false;
    }

    public function welcomeEmail()
    {
        try {
            Mail::send('mail.register', ['user' => $this], function($mail) use ($user) {
                $mail->to($this->email, $this->name);
                $mail->subject('Test Register mail');
                $mail->from(env('MAIL_FROM_ADDRESS', '80bots@inforca.com'), env('MAIL_FROM_NAME', '80bots'));
            });
            return "Success";
        } catch (Exception $ex) {
            return "We've got errors!";
        }
    }

    public function updateUserCreditSendEmail($user)
    {

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

    public static function UserCreditSendEmail($user)
    {
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

}
