<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriptionPlanRequest;
use App\Http\Requests\SwapSubscriptionPlanPost;
use App\StripeModel;
use App\SubscriptionPlan;
use Illuminate\Support\Facades\Auth;
use Throwable;

class StripeController extends Controller
{
    protected $user;

    public function createSubscription(SubscriptionPlanRequest $request)
    {
        $token = StripeModel::CreateStripeToken($request);
        $this->user = Auth::user();
        if( !isset($request->plan_id) ) {
            //session()->flash('error', 'Subscription Plan Can not Added Successfully');
            return redirect('user/subscription-plans')->with('message', 'Subscription Plan Can not Added Successfully');
        }
        try{
            $this->user->newSubscription(config('services.stripe.product'), $request->plan_id)->create($token->id);
        } catch (Throwable $e) {
            return redirect()->back();
        }
        $plan = SubscriptionPlan::where('stripe_plan',$request->plan_id)->first();
        $this->user->updateCredit($plan->credit);
        session()->flash('success', 'Subscribed Successfully');
        return redirect()->back();
    }

    public function swapSubscriptionPlan(SwapSubscriptionPlanPost $request)
    {
        $this->user = Auth::user();
        $plan_id = $request->plan_id;
        $plan = SubscriptionPlan::where('stripe_plan',$request->plan_id)->first();
        try{
            $this->user->subscription(config('services.stripe.product'))->noProrate()->swap($plan_id);
        } catch (Throwable $e) {
            return redirect()->back();
        }
        $plan = SubscriptionPlan::where('stripe_plan',$request->plan_id)->first();
        $this->user->updateCredit($plan->credit);
        session()->flash('success', 'Changed Subscription Successfully');
        return redirect()->back();
    }
}
