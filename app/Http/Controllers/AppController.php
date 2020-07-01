<?php

namespace App\Http\Controllers;

use App\AwsRegion;
use App\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

class AppController extends Controller
{
    public function __construct()
    {
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function ping(Request $request)
    {
        return response()->json([
            'status' => 'Available'
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function apiEmpty()
    {
        return response()->json([]);
    }


    /**
     * Limit check whether we can create instance in the region
     * @param AwsRegion $awsRegion
     * @param int $countInstances
     * @return bool
     */
    protected function checkLimitInRegion(AwsRegion $awsRegion, int $countInstances): bool
    {
        $limit      = $awsRegion->limit ?? 0;
        $created    = $awsRegion->created_instances ?? 0;

        return $created < ($limit*AwsRegion::PERCENT_LIMIT);
    }

    /**
     * @param AwsRegion $region
     * @param string $imageId
     * @return bool
     */
    protected function issetAmiInRegion(AwsRegion $region, string $imageId): bool
    {
        $result = AwsRegion::whereHas('amis', function (Builder $query) use ($imageId) {
            $query->where('image_id', '=', $imageId);
        })->first();

        return !empty($result) ? $result->id === $region->id : false;
    }

    /**
     * @param $id
     * @return Application|RedirectResponse|Redirector
     */
    public function UserActivation($id)
    {
        $checkActivationToken = User::where('verification_token', $id)->first();

        if (isset($checkActivationToken) && !empty($checkActivationToken)) {
            $checkActivationToken->verification_token = '';
            $checkActivationToken->status = 'active';
            if ($checkActivationToken->save()) {
                return redirect(route('login'))->with('success', 'Your Account will be verified successfully!!');
            } else {
                return redirect(route('login'))->with('error', 'Please Try After Some Time');
            }
        } else {
            return redirect(route('login'))->with('error', 'Unauthorized');
        }
    }

    /**
     * Detect a real client's IP address
     * @return string
     */
    public function getIp() {
        $possibleSources = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
            'HTTP_CF_CONNECTING_IP'
        ];
        foreach ($possibleSources as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $ip){
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                        return $ip;
                    }
                }
            }
        }
    }
}

