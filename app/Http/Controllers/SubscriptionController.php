<?php

namespace App\Http\Controllers;

use App\Http\Resources\User\SubscriptionPlanCollection;
use App\Http\Resources\User\UserResource;
use App\User;
use Illuminate\Http\Request;
use App\SubscriptionPlan;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user   = Auth::user();
        $plans  = (new SubscriptionPlanCollection(SubscriptionPlan::onlyActive()->get()))->response()->getData();

        $subscriptionEnded  = true;
        $activePlan         = null;

        if ($user->subscribed(config('services.stripe.product'))) {
            $subscription       = $user->subscription(config('services.stripe.product'));
            $subscriptionEnded  = $user->subscription(config('services.stripe.product'))->ended();
            $activePlan         = SubscriptionPlan::where('stripe_plan', '=', $subscription->stripe_plan ?? null)->first();
        }

        $user = (new UserResource(User::find(Auth::id())))->response()->getData();

        $response = [
            'user'              => $user->data ?? null,
            'plans'             => $plans->data ?? [],
            'subscriptionEnded' => $subscriptionEnded,
            'activePlan'        => $activePlan
        ];

        return $this->success($response);
    }

    public function subscribe(Request $request) {
        $data = $request->validate([
            'plan_id'   => 'integer|required',
            'token_id' => 'string|required'
        ]);

        $user = $request->user();

        dd($data);
    }
}
