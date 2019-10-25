<?php

namespace App\Http\Controllers;

use App\Bot;
use App\Helpers\CommonHelper;
use App\Http\Resources\User\BotCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Throwable;

class BotController extends Controller
{
    const PAGINATE = 1;

    public function index(Request $request)
    {
        try {
            $limit      = $request->query('limit') ?? self::PAGINATE;
            $platform   = $request->input('platform');
            $search     = $request->input('search');
            $sort       = $request->input('sort');
            $order      = $request->input('order') ?? 'asc';

            $resource = Bot::query();

            // TODO: Add Filters

            //
            if (! empty($platform)) {
                $resource->whereHas('platform', function (Builder $query) use ($platform) {
                    $query->where('name', '=', $platform);
                });
            }

            //
            if (! empty($search)) {
                $resource->where('name', 'like', "%{$search}%")
                    ->orWhere('aws_ami_image_id', 'like', "%{$search}%")
                    ->orWhere('aws_ami_name', 'like', "%{$search}%");
            }

            //
            if (! empty($sort)) {
                $resource->orderBy($sort, $order);
            }

            $bots   = (new BotCollection($resource->paginate($limit)))->response()->getData();
            $meta   = $bots->meta ?? null;

            $response = [
                'data'  => $bots->data ?? [],
                'total' => $meta->total ?? 0
            ];

            return $this->success($response);

        } catch (Throwable $throwable) {
            return $this->error(__('auth.forbidden'), $throwable->getMessage());
        }
    }
}
