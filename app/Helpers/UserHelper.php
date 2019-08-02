<?php

namespace App\Helpers;

use App\User;

class UserHelper
{
    public static function getUserToken(User $user)
    {
        $tokenResult = $user->createToken(config('app.name'));
        $token = $tokenResult->token;
        $token->expires_at = now()->addDays(config('app.access_token_lifetime_days'));
        $token->save();

        $result['token'] = $tokenResult->accessToken;
        $result['user'] = $user;

        return $result;
    }
}
