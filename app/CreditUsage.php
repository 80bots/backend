<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CreditUsage extends Model
{
    const ACTION_ADDED  = 'added';
    const ACTION_USED   = 'used';

    const FILTER_ALL    = 'all';
    const FILTER_MY     = 'my';

    protected $table = "credit_usages";

    protected $fillable = [
        'user_id',
        'credits',
        'total',
        'action',
        'subject'
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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
