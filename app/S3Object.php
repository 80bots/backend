<?php

namespace App;

use Carbon\Carbon;

class S3Object extends BaseModel
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
        'expires',
        'type',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function instance()
    {
        return $this->belongsTo(BotInstance::class, 'instance_id', 'id');
    }

    /**
     * @param $query
     * @param $instanceId
     * @return mixed
     */
    public function scopeRemoveOldLinks($query, $instanceId)
    {
        $expires = Carbon::now()->addMinutes(10)->toDateTimeString();

        return $query->where('instance_id', '=', $instanceId)
            ->where('expires', '<=', $expires)
            ->delete();
    }
}
