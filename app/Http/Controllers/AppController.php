<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

class AppController extends Controller
{
    public function UserActivation($id){
        $checkActivationToken = User::where('verification_token', $id)->first();

        if(isset($checkActivationToken) && !empty($checkActivationToken)){
            $checkActivationToken->verification_token = '';
            $checkActivationToken->status = 'active';
            if($checkActivationToken->save()){
                return redirect(route('login'))->with('success','Your Account will be verified successfully!!');
            } else {
                return redirect(route('login'))->with('error','Please Try After Some Time');
            }
        } else {
            return redirect(route('login'))->with('error','Unauthorized');
        }
    }
}
