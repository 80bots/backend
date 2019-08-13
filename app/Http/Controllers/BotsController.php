<?php

namespace App\Http\Controllers;

use App\Bot;
use App\Helpers\CommonHelper;
use App\Http\Resources\User\BotCollection;
use Illuminate\Http\Request;
use Throwable;

class BotsController extends Controller
{
    const PAGINATE = 1;

    public function index(Request $request)
    {
        try {

            $resource = Bot::ajax();

            // TODO: Add Filters

            $bots   = (new BotCollection($resource->paginate(self::PAGINATE)))->response()->getData();
            $meta   = $bots->meta ?? null;

            $response = [
                'bots'  => $bots->data ?? [],
                'total' => $meta->total ?? 0
            ];

            return $this->success($response);

        } catch (Throwable $throwable) {
            return $this->error(__('auth.forbidden'), $throwable->getMessage());
        }
    }
}
