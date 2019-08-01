<?php

namespace App\Http\Controllers;

use App\SubscriptionPlan;
use App\Timezone;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends AppController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('user.dashboard');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::find($id);
        $used_credit = $user->userInstances->sum('used_credit');
        $plan = null;
        if($user->subscribed(config('services.stripe.product'))) {
            $subscription = $user->subscription(config('services.stripe.product'));
            $plan = SubscriptionPlan::where('stripe_plan',$subscription->stripe_plan)->first();
        }

        $timezones = Timezone::all();
        return view('user.profile', compact('user', 'used_credit', 'plan', 'timezones'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function updateTimezone(Request $request)
    {
        $user = auth()->user();
        $user->timezone = $request->get('timezone');
        $user->save();

        return redirect()->back();
    }
}
