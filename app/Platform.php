<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Platform extends BaseModel
{
    const STATUS_ACTIVE     = 'active';
    const STATUS_INACTIVE   = 'inactive';

    protected $table = 'platforms';

    protected $fillable = [
        'name',
        'status',
    ];

    /**
     * @param $query
     * @param null $limit
     * @param null $platformId
     * @param bool $status
     * @return Platform|Builder
     */
    public function scopeHasBots($query, $limit = null, $platformId = null, $status = false)
    {
        $query = $this->with(['bots' => function($query) use ($limit) {
            if($limit) {
                $query->take($limit);
            }
        }]);

        if ($status && $status == 'active') {
            $query = $query->whereHas('activeBotsWithPrivate');
        } else {
            $query = $query->whereHas('botsWithPrivate');
        }

        if ($platformId) {
            $query = $query->where('id', $platformId);
        }

        return $query;
    }

    public function scopeFindByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    public function bots()
    {
        return $this->hasMany(Bot::class,'platform_id', 'id');
    }

    public function activeBots()
    {
        return $this->bots()->where('status', '=', Bot::STATUS_ACTIVE);
    }

    public function activeBotsWithPrivate()
    {
        return $this->bots()
            ->where('status', '=', Bot::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->where('type', '=', Bot::TYPE_PUBLIC)
                    ->orWhereHas('users', function (Builder $query) {
                        $query->whereIn('id', [Auth::id()]);
                    });
            });
    }

    public function botsWithPrivate()
    {
        return $this->bots()
            ->where(function ($query) {
                $query->where('type', '=', Bot::TYPE_PUBLIC)
                    ->orWhereHas('users', function (Builder $query) {
                        $query->whereIn('id', [Auth::id()]);
                    });
            });
    }
}
