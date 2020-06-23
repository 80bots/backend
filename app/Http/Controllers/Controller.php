<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Helpers\ApiResponse;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param null $data
     * @param null $message
     * @return JsonResponse
     */
    protected function success($data = null, $message = null) {
        return response()->json((new ApiResponse(...func_get_args()))->get(), 200);
    }

    /**
     * @param $reason
     * @param $message
     * @return JsonResponse
     */
    protected function error($reason, $message) {
        return response()->json((new ApiResponse(...func_get_args()))->getError(), 400);
    }

    /**
     * @param $reason
     * @param $message
     * @return JsonResponse
     */
    protected function forbidden($reason, $message) {
        return response()->json((new ApiResponse(...func_get_args()))->getError(), 401);
    }

    /**
     * @param $reason
     * @param $message
     * @return JsonResponse
     */
    protected function notFound($reason, $message) {
        return response()->json((new ApiResponse(...func_get_args()))->getError(), 404);
    }
}
