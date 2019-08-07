<?php

namespace App\Http\Controllers;

use App\Bot;
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

            return new BotCollection($resource->paginate(self::PAGINATE));

        } catch (Throwable $throwable) {
            return $this->error(__('auth.forbidden'), $throwable->getMessage());
        }
    }
}
