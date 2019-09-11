<?php

namespace App\Helpers;

use App\Bot;
use App\CreditUsage;
use App\User;
use Illuminate\Support\Facades\Log;
use Stripe\Plan as StripePlan;
use Stripe\Stripe;
use Throwable;

class CreditUsageHelper
{
    public static function adminAddCredit(User $user, int $credits)
    {
        try {
            CreditUsage::create([
                'user_id'   => $user->id,
                'credits'   => $credits,
                'total'     => $user->credits ?? 0,
                'action'    => CreditUsage::ACTION_ADDED,
                'subject'   => "Credits have been added by the site's admin"
            ]);
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
        }
    }

    public static function receivedBySubscription(User $user, int $credits)
    {
        if($credits > 0) {
            $user->increment('credits', $credits);
        } else {
            $user->decrement('credits', abs($credits));
        }

        $subject = $credits > 0
            ? "Credits have been received by subscription"
            : "Credits have been removed due to changing the subscription";

        $action = $credits > 0 ? CreditUsage::ACTION_ADDED : CreditUsage::ACTION_USED;

        // TODO:
        CreditUsage::create([
            'user_id'   => $user->id,
            'credits'   => $credits,
            'total'     => $user->credits ?? 0,
            'action'    => $action,
            'subject'   => $subject
        ]);
    }

    public static function usingTheBot(User $user, Bot $bot, int $credits)
    {
        $user->decrement('credits', $credits);

        CreditUsage::create([
            'user_id'   => $user->id,
            'credits'   => $credits,
            'total'     => $user->credits ?? 0,
            'action'    => CreditUsage::ACTION_USED,
            'subject'   => "Credits charging for using the bot ({$bot->name})"
        ]);
    }

    public static function startInstance(User $user, int $credits, string $instanceId, string $name)
    {
        $user->decrement('credits', $credits);

        CreditUsage::create([
            'user_id'   => $user->id,
            'credits'   => $credits,
            'total'     => $user->credits ?? 0,
            'action'    => CreditUsage::ACTION_USED,
            'subject'   => "Funds charging for the first hour of instance work (Instance name: {$name} / Instance ID: {$instanceId})"
        ]);
    }

    /**
     * @param User $user
     * @return StripePlan|null
     */
    public static function retrieveStripePlan(User $user): ?StripePlan
    {
        $subscription = $user->subscriptions()->latest()->first();
        if (! empty($subscription)) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                return StripePlan::retrieve($subscription->stripe_plan);
            } catch (Throwable $throwable) {
                Log::error($throwable->getMessage());
                return null;
            }
        }
        return null;
    }
}
