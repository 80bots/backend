<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class S3Object extends Model
{
    const ENTITY_FOLDER     = 'folder';
    const ENTITY_FILE       = 'file';

    const TYPE_ENTITY       = 'entity';
    const TYPE_SCREENSHOTS  = 'screenshots';
    const TYPE_IMAGES       = 'images';
    const TYPE_LOGS         = 'logs';
    const TYPE_JSON         = 'json';

    protected $table = "s3_objects";

    protected $fillable = [
        'instance_id',
        'parent_id',
        'name',
        'path',
        'link',
        'expires',
        'entity',
        'type',
    ];

    public function getS3Path ()
    {
        $rootDir = $this->instance->baseS3Dir;
        return $rootDir . '/' . $this->attributes['path'];
    }

    public function scopeFolders ($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeLogs ($query)
    {
        return $query->whereNotNull('parent_id')
            ->where('path', 'like', '%logs%');
    }

    public function scopeWorkLogs ($query)
    {
        return $query->logs()
            ->where('path', 'like', '%bot-work.log%');
    }

    public function scopeScreenshots ($query, $instance_id)
    {
        return $query->where('instance_id', $instance_id)
            ->whereNotNull('parent_id')
            ->where('path', 'like', '%output/screenshots%');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function instance()
    {
        return $this->belongsTo(BotInstance::class, 'instance_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(S3Object::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(S3Object::class, 'parent_id');
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
