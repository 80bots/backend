<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Helper\ApiResponse;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function success($data = null, $message = null) {
        return response()->json((new ApiResponse(...func_get_args()))->get(), 200);
    }

    protected function error($reason, $message) {
        return response()->json((new ApiResponse(...func_get_args()))->getError(), 400);
    }

    protected function forbidden($reason, $message) {
        return response()->json((new ApiResponse(...func_get_args()))->getError(), 401);
    }

    protected function notFound($reason, $message) {
        return response()->json((new ApiResponse(...func_get_args()))->getError(), 404);
    }
}
