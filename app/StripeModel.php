<?php

namespace App;

use Illuminate\Support\Facades\Log;
use Stripe\Error\ApiConnection;
use Stripe\Error\Card;
use Stripe\Stripe;
use Stripe\Token;

class StripeModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    public static function StripeConnection()
    {
        try{
            Stripe::setApiKey(config('settings.stripe.secret'));
        } catch (ApiConnection $exception){
            Log::error($exception->getMessage());
        }
    }

    public static function CreateStripeToken($request)
    {
        self::StripeConnection();

        try {
            return Token::create([
                'card' => [
                    'number'    => $request->input('number'),
                    'exp_month' => $request->input('month'),
                    'exp_year'  => $request->input('year'),
                    'cvc'       => $request->input('cvc')
                ]
            ]);
        } catch (Card $exception){
            Log::error($exception->getMessage());
            return null;
        }
    }
}
