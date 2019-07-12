<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\SubscriptionPlan;
use Illuminate\Support\Facades\Auth;

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $plans = SubscriptionPlan::all();
        $subscription_ended = true;
        $activeplan = null;
        if($user->subscribed(config('services.stripe.product'))) {
            $subscription = $user->subscription(config('services.stripe.product'));
            $subscription_ended = $user->subscription(config('services.stripe.product'))->ended();
            $activeplan = SubscriptionPlan::where('stripe_plan',$subscription->stripe_plan)->first();
        }
        return view('user.plans.index', [
            'plans' => $plans,
            'user' => $user,
            'subscription_ended' => $subscription_ended,
            'activeplan' => $activeplan 
        ]);
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
        //
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
}
