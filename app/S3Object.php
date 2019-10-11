<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class S3Object extends Model
{
    const TYPE_SCREENSHOTS  = 'screenshots';
    const TYPE_IMAGES       = 'images';
    const TYPE_LOGS         = 'logs';
    const TYPE_JSON         = 'json';

    protected $table = "s3_objects";

    protected $fillable = [
        'instance_id',
        'folder',
        'link',
        'expires'
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

    public function instance()
    {
        return $this->belongsTo(BotInstance::class, 'instance_id', 'id');
    }

    public function scopeRemoveOldLinks($query, $instanceId)
    {
        $expires = Carbon::now()->addMinutes(10)->toDateTimeString();

        return $query->where('instance_id', '=', $instanceId)
            ->where('expires', '<=', $expires)
            ->delete();
    }
}
