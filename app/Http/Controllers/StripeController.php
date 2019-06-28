<?php

namespace App\Http\Controllers;

use App\StripeModel;
use Illuminate\Http\Request;
use Stripe\Charge;
use Stripe\Stripe;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\SubscriptionPlanRequest;

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
        if( !isset($request->plan_id) ) return redirect('user/subscription-plans');
        $this->user->newSubscription('80bots', $request->plan_id)->create($token->id);
        return redirect('user/subscription-plans');
    }

}
