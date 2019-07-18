<?php

namespace App\Http\Controllers;

use App\Bots;
use App\Http\Controllers\AwsConnectionController;
use App\Job;
use App\Jobs\StoreUserInstance;
use App\Platforms;
use App\UserInstances;
use App\UserInstancesDetails;
use function GuzzleHttp\Promise\all;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Session;


class BotsController extends Controller
{
    public $limit;

    public function index($platformId = null)
    {
        if(!$platformId) {
          $this->limit = 5;
        }

        $platforms = new Platforms;

        $platforms = $platforms->hasBots($this->limit, $platformId)->paginate(5);

        return view('user.bots.index',compact('platforms'));
    }
}
