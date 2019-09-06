<?php

namespace App\Http\Controllers;

use App\Helpers\CreditUsageHelper;
use App\Http\Resources\User\SubscriptionPlanCollection;
use App\Http\Resources\User\SubscriptionPlanResource;
use App\Http\Resources\User\UserResource;
use App\SubscriptionPlan;
use App\User;
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

        $user = (new UserResource(User::find($user->id)))->response()->getData();

        $response = [
            'user'              => $user->data ?? null,
            'plans'             => $plans->data ?? [],
            'subscriptionEnded' => $subscriptionEnded,
            'activePlan'        => $activePlan
        ];

        return $this->success($response);
    }

    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'plan_id'   => 'integer|required',
            'token_id' => 'string|required'
        ]);

        $plan = SubscriptionPlan::find($data['plan_id']);

        if (empty($plan)) {
            return $this->error('Not Found', 'Such subscription plan not found');
        }

        $user = Auth::user();

        if (! $user->hasStripeId()) {
            $user->createAsStripeCustomer([
                'description' => $user->name
            ]);
        }

        $credits        = 0;
        $oldPlanCredits = 0;

        $stripePlan = CreditUsageHelper::retrieveStripePlan($user);
        if (! empty($stripePlan)) {
            $oldPlanCredits = intval($stripePlan->metadata->credits ?? 0);
        }

        $user->deletePaymentMethods();
        $paymentMethod = $user->createPaymentMethod($data['token_id']);
        $user->updateDefaultPaymentMethod($paymentMethod);

        // If we have a subscription and the user selected the same plan
        if ($user->subscription(config('services.stripe.product')) && $user->subscribed(config('services.stripe.product'), $plan->stripe_plan)) {
            return $this->error('Bad Request', 'You are already at this plan');
        }

        // If the user doesn't have a subscription in the plan's list
        if (! $user->subscription(config('services.stripe.product'))) {

            if (! $user->subscribedToPlan(config('services.stripe.product'), $plan->stripe_plan)) {
                $user->newSubscription(config('services.stripe.product'), $plan->stripe_plan)->create();
                $credits = $plan->credit ?? 0;
            } else {
                return $this->error('Bad Request', 'Bad Request');
            }

        } else if (! $user->subscribed(config('services.stripe.product'), $plan->stripe_plan)) {
            // If the user has a subscription, but for another plan,
            // we change to the selected one (Upgrading and Downgrading Plans)

            if ($plan->credit > $oldPlanCredits) {
                $user->subscription(config('services.stripe.product'))
                    ->swapAndInvoice($plan->stripe_plan);
                $credits = $plan->credit - $oldPlanCredits;
            } else {

                $difference = $oldPlanCredits - $plan->credit;

                if ($difference > 0 && ($user->credits - $difference) > 0) {
                    $user->subscription(config('services.stripe.product'))
                        ->swapAndInvoice($plan->stripe_plan);

                    $credits = 0 - $difference;

                } else {
                    //
                    return $this->error('Bad Request', 'You cannot change the plan. There are not enough credits in your account');
                }
            }

        } else {
            return $this->error('Bad Request', 'Bad Request');
        }

        CreditUsageHelper::receivedBySubscription($user, $credits);

        $resource = (new UserResource($user))->response()->getData();

        return $this->success([
            'user' => $resource->data ?? null
        ]);
    }
}
