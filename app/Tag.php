<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $table = "tags";

    protected $fillable = [
        'name',
        'status',
    ];

    //
    public static function findByName($name)
    {
        return self::where('name' , $name)->first();
    }

    public function bots()
    {
        return $this->belongsToMany(Bot::class, 'bot_tag');
    }
}
