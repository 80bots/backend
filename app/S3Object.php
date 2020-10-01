<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class S3Object extends BaseModel
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
        'difference'
    ];

    /**
     * @return string
     */
    public function getS3Path()
    {
        $rootDir = $this->instance->baseS3Dir;
        return $rootDir . '/' . $this->attributes['path'];
    }

    /**
     * @return string
     */
    public function getLinkAttribute ()
    {
        $expires = Carbon::now()->addMinutes(10);
        $base = $this->instance->baseS3Dir;
        $key = "{$base}/{$this->path}";
        $s3Url = Storage::disk('s3')->temporaryUrl($key, $expires);
        $parse = parse_url($s3Url);
        $cdn = config('aws.instance_cloudfront');
        if(!$cdn) {
            return $s3Url;
        }
        $query = $parse['query'];
        $path = $parse['path'];
        return $cdn . $path  . '?' . $query;
    }

    /**
     * @param $query
     * @return array
     */
    public function scopeFolders ($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * @param $query
     * @return array
     */
    public function scopeLogs ($query)
    {
        return $query->whereNotNull('parent_id')
            ->where('path', 'like', '%logs%');
    }

    /**
     * @param $query
     * @return array
     */
    public function scopeWorkLogs ($query)
    {
        return $query->logs()
            ->where('path', 'like', '%bot-work.log%');
    }

    /**
     * @param $query
     * @param $instance_id
     * @return array
     */
    public function scopeScreenshots ($query, $instance_id)
    {
        return $query->where('instance_id', $instance_id)
            ->whereNotNull('parent_id')
            ->where('path', 'like', '%output/screenshots%');
    }

    /**
     * @return BelongsTo
     */
    public function instance()
    {
        return $this->belongsTo(BotInstance::class, 'instance_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(S3Object::class, 'parent_id');
    }

    /**
     * @return HasMany
     */
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

    /**
     *
     */
    public static function calculateStatistic(int $id = 0) {
        $statistic = Cache::remember($id . '_instance_activity', 480, function () use ($id) {
            return S3Object::where('instance_id', $id)
                ->pluck('difference')
                ->chunk(24)
                ->map(function ($chunk) {
                    return $chunk->avg();
                });
        });

        if( !$statistic ) {
            Cache::forget($id . '_instance_activity' );
        }
        return $statistic;
    }
}
