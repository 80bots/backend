<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Stripe\Error\ApiConnection;
use Stripe\Error\Card;
use Stripe\Stripe;
use Stripe\Token;

use Laravel\Cashier\Cashier;

class StripeModel extends Model
{
    public function __construct()
    {
        parent::__construct();
//        Cashier::useCurrency('eur', '€');
    }

    public static function StripeConnection(){
        try{
            Stripe::setApiKey(env('STRIPE_SECRET'));
            return 'Success';
        } catch (ApiConnection $exception){
            return $exception->getMessage();
        }
    }

    public static function CreateStripeToken($request){
        self::StripeConnection();
        $number = isset($request->number) ? $request->number : '';
        $month = isset($request->month) ? $request->month : '';
        $year = isset($request->year) ? $request->year : '';
        $cvc = isset($request->cvc) ? $request->cvc : '';
        try {
            $token = Token::create([
                'card' => [
                    'number' => $number,
                    'exp_month' => $month,
                    'exp_year' => $year,
                    'cvc' => $cvc
                ]
                ]);
            return $token;
        } catch (Card $exception){
            return $exception->getMessage();
        }
    }
}
