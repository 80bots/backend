<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Charge;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use App\StripeModel;
use App\SubscriptionPlan;

use App\Http\Requests\SubscriptionPlanRequest;
use App\Http\Requests\SwapSubscriptionPlanPost;

class StripeController extends Controller
{
    protected $user;

    public function SendPayment(){
//        StripeModel::CreateStripeToken($request);
    }

    public function createSubscription(SubscriptionPlanRequest $request)
    {
        $token = StripeModel::CreateStripeToken($request);
        $this->user = Auth::user();
        if( !isset($request->plan_id) ) {
            //session()->flash('error', 'Subscription Plan Can not Added Successfully');
            return redirect('user/subscription-plans')->with('message', 'Subscription Plan Can not Added Successfully');
        }
        try{
            $this->user->newSubscription('80bots', $request->plan_id)->create($token->id);           
        } catch (Exception $e) {
            return redirect()->back();
        }
        $plan = SubscriptionPlan::where('stripe_plan',$request->plan_id)->first();
        $this->user->updateCredit($plan->credit);
        session()->flash('success', 'Subscribed Successfully');
        return redirect()->back();
        //return redirect('user/subscription-plans');
    }

    public function swapSubscriptionPlan(SwapSubscriptionPlanPost $request)
    {
        $this->user = Auth::user();
        $plan_id = $request->plan_id;
        $upgrade = false;
        $current = $this->user->subscription('80bots')->stripe_plan;
        $currentPlan = SubscriptionPlan::where('stripe_plan',$current)->first();
        $requestedPlan = SubscriptionPlan::where('stripe_plan',$request->plan_id)->first();
        if($currentPlan->price < $requestedPlan->price) $upgrade = true;

        try{
            if($upgrade) {
                $this->user->subscription('80bots')->swap($plan_id);
            }
/*             dump($this->user->invoices()->first()->invoice->lines);
            dd($this->user->asStripeCustomer()->subscriptions->data);
 */        
        } catch (Exception $e) {
            return redirect()->back();
        }
        
        $this->user->updateCredit($plan->credit);
        session()->flash('success', 'Changed Subscription Successfully');
        return redirect()->back();
    }

}
