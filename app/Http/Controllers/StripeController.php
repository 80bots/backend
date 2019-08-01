<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriptionPlanRequest;
use App\Http\Requests\SwapSubscriptionPlanPost;
use App\StripeModel;
use App\SubscriptionPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class StripeController extends Controller
{
    /**
     * @param SubscriptionPlanRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function createSubscription(SubscriptionPlanRequest $request)
    {
        $planId = $request->input('plan_id');

        if (empty($planId)) {
            return redirect('user/subscription-plans')->with('message', 'Subscription plan can not added successfully');
        }

        $plan = SubscriptionPlan::where('stripe_plan', $planId)->first();

        if (empty($plan)) {
            return redirect('user/subscription-plans')->with('message', 'Plan not found');
        }

        try{

            $user   = Auth::user();
            $token  = StripeModel::CreateStripeToken($request);

            if (! empty($token)) {
                $user->newSubscription(config('services.stripe.product'), $planId)->create($token->id);
                $user->updateCredit($plan->credit);
                session()->flash('success', 'Subscribed Successfully');
            } else {
                session()->flash('error', 'Subscribed Error');
            }

            return redirect()->back();

        } catch (Throwable $e) {
            Log::error($e->getMessage());
            session()->flash('error', $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * @param SwapSubscriptionPlanPost $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function swapSubscriptionPlan(SwapSubscriptionPlanPost $request)
    {
        $planId = $request->input('plan_id');

        if (empty($planId)) {
            return redirect('user/subscription-plans')->with('message', 'Subscription plan can not added successfully');
        }

        $plan = SubscriptionPlan::where('stripe_plan', $planId)->first();

        if (empty($plan)) {
            return redirect('user/subscription-plans')->with('message', 'Plan not found');
        }

        try{
            $user   = Auth::user();
            $subscription = $user->subscription(config('services.stripe.product'));

            if (! empty($subscription)) {
                $subscription->noProrate()->swap($planId);
                $user->updateCredit($plan->credit);
                session()->flash('success', 'Changed Subscription Successfully');
            } else {
                session()->flash('error', 'Subscription not found');
            }

            return redirect()->back();

        } catch (Throwable $e) {
            Log::error($e->getMessage());
            session()->flash('error', $e->getMessage());
            return redirect()->back();
        }
    }
}
