<?php

namespace App\Helpers;

use App\Http\Resources\User\UserResource;
use App\User;
use Illuminate\Contracts\Auth\Authenticatable;

class UserHelper
{
    /**
     * @param Authenticatable $authenticatable
     * @return array
     */
    public static function getUserToken(Authenticatable $authenticatable): array
    {
        $tokenResult = $authenticatable->createToken(config('app.name'));
        $token = $tokenResult->token;
        $token->expires_at = now()->addDays(config('app.access_token_lifetime_days'));
        $token->save();

        $user = (new UserResource(User::find($authenticatable->id ?? null)))->response()->getData();

        return [
            'token' => $tokenResult->accessToken,
            'user'  => $user->data ?? null
        ];
    }
}
