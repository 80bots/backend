<?php

namespace App\Http\Controllers;

use App\StripeModel;
use Illuminate\Http\Request;
use Stripe\Charge;
use Stripe\Stripe;

class StripeController extends Controller
{

    public function SendPayment(){
//        StripeModel::CreateStripeToken($request);
    }

}
