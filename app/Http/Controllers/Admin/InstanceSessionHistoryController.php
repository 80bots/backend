<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppController;
use App\InstanceSessionsHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstanceSessionHistoryController extends AppController
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @return View $response
     */
    public function index(Request $request)
    {
        return view('user.bots.running.session-history', [
            'sessions' => InstanceSessionsHistory::with('schedulingInstance.userInstances')->paginate(5),
            'admin' => true
        ]);
    }
}
