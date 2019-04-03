<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BotTags extends Model
{
    //
    public static function findWithTagIdAndBotId($tag_id, $bot_id)
    {

    }

    public static function deleteByBotId($id)
    {
        return self::where('bots_id', $id)->delete();
    }

    public function bots(){
        return $this->belongsTo('App\Bots');
    }

    public function tags(){
        return $this->belongsTo('App\Tags');
    }
}
