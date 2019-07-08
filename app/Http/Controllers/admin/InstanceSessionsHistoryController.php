<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\AppController;
use App\InstanceSessionsHistory;
use Auth;
use Illuminate\Http\Request;

class InstanceSessionsHistoryController extends AppController
{
    public function __construct()
    {
        //
    }
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if($user->role->name === 'User'){
            return view('user.instance.sessionhistory', [
                'sessions' => InstanceSessionsHistory::where('user_id', $user->id)->with('schedulingInstance.userInstances')->paginate(5),
                'admin' => false
            ]);
        }

        return view('user.instance.sessionhistory', [
            'sessions' => InstanceSessionsHistory::with('schedulingInstance.userInstances')->paginate(5),
            'admin' => true
        ]);
    }
}
