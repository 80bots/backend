<?php

namespace App\Http\Controllers;

use App\Http\Resources\User\TimezoneCollection;
use App\Services\Aws;
use App\SubscriptionPlan;
use App\Timezone;
use App\User;
use DOMDocument;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class UserController extends AppController
{
    /**
     * Display the specified resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        try {

            $user = User::find(Auth::id());
            $plan = null;

            if ($user->subscribed(config('services.stripe.product'))) {
                $subscription = $user->subscription(config('services.stripe.product'));
                $plan = SubscriptionPlan::where('stripe_plan', $subscription->stripe_plan)->first();
            }

            return $this->success([
                'user'          => $user,
                'used_credit'   => $user->instances->sum('used_credit'),
                'plan'          => $plan,
                'timezones'     => (new TimezoneCollection(Timezone::get()))->response()->getData()
            ]);

        } catch (Throwable $throwable) {
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {

            $updateData = $request->validate([
                'update.timezone_id' => 'integer',
                'update.region_id'   => 'integer'
            ]);

            foreach ($updateData['update'] as $key => $value) {
                switch ($key) {
                    case 'timezone_id':
                        $request->user()->timezone_id = $value;
                        break;
                    case 'region_id':
                        $request->user()->region_id = $value;
                        break;
                }
            }

            if ($request->user()->save()) {
                return $this->success();
            }

            return $this->error('System Error', 'Cannot update profile at this moment');
        } catch (\Exception $exception){
            return $this->error('System Error', $exception->getMessage());
        }
    }

    /**
     * Get list timezones
     * @return JsonResponse
     */
    public function getTimezones(): JsonResponse
    {
        try {
            return $this->success((new TimezoneCollection(Timezone::all()))->response()->getData());
        } catch (Throwable $throwable) {
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }
}
