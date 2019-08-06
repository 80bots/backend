<?php

namespace App\Helpers;

use Illuminate\Contracts\Auth\Authenticatable;

class UserHelper
{
    /**
     * @param Authenticatable $user
     * @return array
     */
    public static function getUserToken(Authenticatable $user): array
    {
        $tokenResult = $user->createToken(config('app.name'));
        $token = $tokenResult->token;
        $token->expires_at = now()->addDays(config('app.access_token_lifetime_days'));
        $token->save();

        return [
            'token' => $tokenResult->accessToken,
            'user'  => $user
        ];
    }
}
