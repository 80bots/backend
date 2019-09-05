<?php

namespace App\Helpers;

use App\Bot;
use App\CreditUsage;
use App\User;

class CreditUsageHelper
{
    public static function adminAddCredit(User $user, int $credits)
    {
        CreditUsage::create([
            'user_id'   => $user->id,
            'credit'    => $credits - $user->remaining_credits ?? 0,
            'action'    => CreditUsage::ACTION_ADDED,
            'subject'   => "Credits have been added by the site's admin"
        ]);
    }

    public static function receivedBySubscription(User $user, int $credits, string $order)
    {
        CreditUsage::create([
            'user_id'   => $user->id,
            'credit'    => $credits,
            'action'    => CreditUsage::ACTION_ADDED,
            'subject'   => "Credits have been received by subscription (Order #123)"
        ]);
    }

    public static function usingTheBot(User $user, Bot $bot, int $credits)
    {
        CreditUsage::create([
            'user_id'   => $user->id,
            'credit'    => $credits,
            'action'    => CreditUsage::ACTION_USED,
            'subject'   => "Credits charging for using the bot (bot's name)"
        ]);
    }
}
