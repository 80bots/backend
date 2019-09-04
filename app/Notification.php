<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    const STATUS_ACTIVE     = 'active';
    const STATUS_INACTIVE   = 'inactive';

    const TYPE_EMAIL        = 'email';
    const TYPE_PUSH         = 'push';
    const TYPE_SMS          = 'sms';

    const DELIVERY_QUEUED   = 'queued';
    const DELIVERY_SENT     = 'sent';
    const DELIVERY_ERROR    = 'error';

    protected $table = "notifications";

    protected $fillable = [
        'subject',
        'message',
        'payload',
        'icon',
        'type',
        'status',
        'delivery',
        'instance_stop_time'
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

    public function setDeliverySent()
    {
        $this->update(['delivery' => self::DELIVERY_SENT]);
    }

    public function setDeliveryError()
    {
        $this->update(['delivery' => self::DELIVERY_ERROR]);
    }
}
