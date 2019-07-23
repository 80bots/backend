<?php

namespace App\Http\Controllers;

use App\Platforms;
use Illuminate\Http\Request;

class BotsController extends Controller
{
    public $limit;

    public function index($platformId = null)
    {
        if(!$platformId) {
          $this->limit = 5;
        }

        $platforms = new Platforms;

        $platforms = $platforms->hasBots($this->limit, $platformId, $status = 'active')->paginate(5);

        return view('user.bots.index',compact('platforms'));
    }
}
