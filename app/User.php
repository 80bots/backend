<?php

namespace App;

use App\Helpers\QueryHelper;
use App\Notifications\SaasVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes, HasApiTokens;

    const STATUS_PENDING    = 'pending';
    const STATUS_ACTIVE     = 'active';
    const STATUS_INACTIVE   = 'inactive';

    const ORDER_FIELDS      = [
        'name' => [
            'entity'    => QueryHelper::ENTITY_USER,
            'field'     => 'name'
        ],
        'email' => [
            'entity'    => QueryHelper::ENTITY_USER,
            'field'     => 'email'
        ],
        'date' => [
            'entity'    => QueryHelper::ENTITY_USER,
            'field'     => 'created_at'
        ],
        'status' => [
            'entity'    => QueryHelper::ENTITY_USER,
            'field'     => 'status'
        ],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'visitor',
        'password',
        'timezone_id',
        'region_id',
        'verification_token',
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
     * @return HasMany
     */
    public function instances()
    {
        return $this->hasMany(BotInstance::class);
    }

    /**
     * @return HasMany
     */
    public function visitors()
    {
        return $this->hasMany(Visitor::class);
    }

    /**
     * @return HasMany
     */
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
     * @return BelongsTo
     */
    public function timezone()
    {
        return $this->belongsTo(Timezone::class);
    }

    /**
     * @return BelongsTo
     */
    public function region()
    {
        return $this->belongsTo(AwsRegion::class);
    }

    /**
     * @return BelongsToMany
     */
    public function privateBots()
    {
        return $this->belongsToMany(Bot::class, 'bot_user');
    }

    /**
     * Send the email verification notification.
     *
     * @param $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new SaasVerifyEmail($token));
    }


    /**
     * Checking access of the authenticated user to specified instance
     * @param $aws_instance_id
     * @return bool
     */
    public function hasAccessToInstance ($aws_instance_id) {
        return $this
                ->instances()
                ->withTrashed()
                ->whereAwsInstanceId($aws_instance_id)
                ->count() > 0;
    }
}
