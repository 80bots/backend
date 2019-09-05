<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CreditUsage extends Model
{
    const ACTION_ADDED  = 'added';
    const ACTION_USED   = 'used';

    protected $table = "credit_usages";

    protected $fillable = [
        'user_id',
        'credit',
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
}
