<?php

namespace App\Http\Controllers;

use App\InstanceSessionsHistory;
use Auth;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstanceSessionHistoriesController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @return View
     */
    public function index(Request $request)
    {
        return view('user.bots.running.session-history', [
            'sessions' => InstanceSessionsHistory::with('schedulingInstance.userInstances')->paginate(5),
            'admin' => false
        ]);
    }
}
