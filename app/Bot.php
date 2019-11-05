<?php

namespace App;

use App\Helpers\QueryHelper;

class Bot extends BaseModel
{
    const STATUS_ACTIVE     = 'active';
    const STATUS_INACTIVE   = 'inactive';

    const TYPE_PUBLIC       = 'public';
    const TYPE_PRIVATE      = 'private';

    const ORDER_FIELDS      = [
        'platform' => [
            'entity'    => QueryHelper::ENTITY_PLATFORM,
            'field'     => 'name'
        ],
        'name' => [
            'entity'    => QueryHelper::ENTITY_BOT,
            'field'     => 'name'
        ],
        'description' => [
            'entity'    => QueryHelper::ENTITY_BOT,
            'field'     => 'description'
        ],
        'status' => [
            'entity'    => QueryHelper::ENTITY_BOT,
            'field'     => 'status'
        ],
        'type' => [
            'entity'    => QueryHelper::ENTITY_BOT,
            'field'     => 'type'
        ],
    ];

    protected $table = "bots";

    protected $fillable = [
        'platform_id',
        'name',
        'description',
        'parameters',
        'path',
        'aws_startup_script',
        'aws_custom_script',
        'status',
        'type'
    ];

    public function platform()
    {
        return $this->belongsTo(Platform::class, 'platform_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'bot_tag');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'bot_user');
    }
}
