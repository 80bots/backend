<?php

namespace App\Http\Controllers;

use App\InstanceSessionsHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstanceSessionsHistoryController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if(Auth::user()->hasRole('User')){
            return view('user.instance.sessionhistory', [
                'sessions' => InstanceSessionsHistory::where('user_id', Auth::id())->with('schedulingInstance.userInstances')->paginate(5),
                'admin' => false
            ]);
        }

        return view('user.instance.sessionhistory', [
            'sessions' => InstanceSessionsHistory::with('schedulingInstance.userInstances')->paginate(5),
            'admin' => true
        ]);
    }
}
