<?php

namespace App;

use App\Notifications\SaasVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Passport\HasApiTokens;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class User extends Authenticatable
{
    use Billable, Notifiable, SoftDeletes, HasApiTokens;

    const ROLE_NAME_USER    = 'User';
    const ROLE_NAME_ADMIN   = 'Admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'timezone_id',
        'region_id',
        'verification_token',
        'credits',
        'stripe_id',
        'card_brand',
        'card_last_four',
        'trial_ends_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
        'password_reset_token',
        'auth_token',
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
    public function instances()
    {
        return $this->hasMany(BotInstance::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function scopeRunningInstances($query)
    {
        return $query->whereHas('instances', function (Builder $query) {
            $query->where('aws_status', '=', BotInstance::STATUS_RUNNING);
        })->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function timezone()
    {
        return $this->belongsTo(Timezone::class);
    }

    public function region()
    {
        return $this->belongsTo(AwsRegion::class);
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
        return $query->whereHas('role', function (Builder $query) {
            $query->where('name', '=', self::ROLE_NAME_USER);
        });
    }

    /**
     * Return users with admin role
     * @param $query
     */
    public function scopeOnlyAdmins($query)
    {
        return $query->whereHas('role', function (Builder $query) {
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
            return $query->orderBy('id', 'desc')->first();
        }])->whereHas('instances')->get();
    }

    /**
     * Send the email verification notification.
     *
     * @param $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        //$this->notify(new ResetPasswordNotification($token));
        $this->notify(new SaasVerifyEmail($token));
    }

    public function createPaymentMethod($token)
    {
        Stripe::setApiKey(config('services.stripe.key'));

        return PaymentMethod::create([
            'type' => 'card',
            'card' => [
                'token' => $token
            ]
        ]);
    }
}
