<?php

namespace App;

use App\Helpers\QueryHelper;
use Illuminate\Database\Eloquent\Model;

class CreditUsage extends Model
{
    const ACTION_ADDED  = 'added';
    const ACTION_USED   = 'used';

    const FILTER_ALL    = 'all';
    const FILTER_MY     = 'my';

    const ORDER_FIELDS      = [
        'credits' => [
            'entity'    => QueryHelper::ENTITY_CREDIT_USAGE,
            'field'     => 'credits'
        ],
        'total' => [
            'entity'    => QueryHelper::ENTITY_CREDIT_USAGE,
            'field'     => 'total'
        ],
        'action' => [
            'entity'    => QueryHelper::ENTITY_CREDIT_USAGE,
            'field'     => 'action'
        ],
        'description' => [
            'entity'    => QueryHelper::ENTITY_CREDIT_USAGE,
            'field'     => 'subject'
        ],
        'date' => [
            'entity'    => QueryHelper::ENTITY_CREDIT_USAGE,
            'field'     => 'created_at'
        ],
    ];

    protected $table = "credit_usages";

    protected $fillable = [
        'instance_id',
        'user_id',
        'credits',
        'total',
        'action',
        'subject'
    ];

    /**
     * @param $query
     * @param $userId
     * @return mixed
     */
    public function scopeFindByUserId($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Return only added action
     * @param $query
     */
    public function scopeOnlyAdded($query)
    {
        $query->where('action', '=', self::ACTION_ADDED);
    }

    /**
     * Return only used action
     * @param $query
     */
    public function scopeOnlyUsed($query)
    {
        $query->where('action', '=', self::ACTION_USED);
    }

    public function instance()
    {
        return $this->belongsTo(BotInstance::class, 'instance_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
