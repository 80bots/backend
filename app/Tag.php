<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends BaseModel
{
    const STATUS_ACTIVE     = 'active';
    const STATUS_INACTIVE   = 'inactive';

    protected $table = "tags";

    protected $fillable = [
        'name',
        'status',
    ];

    /**
     * @param $name
     * @return array
     */
    public static function findByName($name)
    {
        return self::where('name' , $name)->first();
    }

    /**
     * @return BelongsToMany
     */
    public function bots()
    {
        return $this->belongsToMany(Bot::class, 'bot_tag');
    }
}
