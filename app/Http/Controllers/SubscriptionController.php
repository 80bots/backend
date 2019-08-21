<?php

namespace App\Http\Controllers;

use App\Http\Resources\User\SubscriptionPlanCollection;
use App\Http\Resources\User\SubscriptionPlanResource;
use App\Http\Resources\User\UserResource;
use App\User;
use App\SubscriptionPlan;
use Illuminate\Http\Request;
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
            $activePlan         = (
                new SubscriptionPlanResource(
                    SubscriptionPlan::where('stripe_plan', '=', $subscription->stripe_plan ?? null)->first()
                )
            )->toArray(null);
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

        if(!$user->hasStripeId()) {
            $user->createAsStripeCustomer([
                'description' => $user->name
            ]);
        }

        $user->deletePaymentMethods();
        $paymentMethod = $user->createPaymentMethod($data['token_id']);
        $user->updateDefaultPaymentMethod($paymentMethod);

        $plan = SubscriptionPlan::find($data['plan_id']);

        if (!$plan) return $this->error('Not Found', 'Such subscription plan not found');

        if(!$user->subscription(config('services.stripe.product'))) {
            if(!$user->subscribedToPlan(config('services.stripe.product'), $plan->stripe_plan)) {
                $request->user()->newSubscription(config('services.stripe.product'), $plan->stripe_plan)->create();
                $user->updateCredits($plan->credit);
            }
        } else if(!$user->subscribed(config('services.stripe.product'), $plan->stripe_plan)) {
            $user->subscription(config('services.stripe.product'))->swapAndInvoice($plan->stripe_plan);
            $user->updateCredits($plan->credit);
        } else {
            return $this->error('Bad Request', 'You are already at this plan');
        }
    }
}
