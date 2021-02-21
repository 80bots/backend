<?php

namespace App\Http\Controllers\Common\Instances;

use App\AwsRegion;
use App\BotInstance;
use App\Events\InstanceStatusUpdated;
use App\Helpers\InstanceHelper;
use App\Helpers\QueryHelper;
use App\Http\Controllers\AppController;
use App\Http\Resources\BotInstanceCollection;
use App\Http\Resources\BotInstanceResource;
use App\Jobs\UpdateInstanceSecurityGroup;
use App\S3Object;
use App\Services\Aws;
use App\Services\GitHub;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

class InstanceController extends AppController
{
    const PAGINATE = 1;

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return BotInstanceCollection|JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $limit = $request->query('limit') ?? self::PAGINATE;
            $search = $request->input('search');
            $sort = $request->input('sort');
            $order = $request->input('order') ?? 'asc';
            $list = $request->input('list') ?? 'all';
            $resource = BotInstance::withTrashed();
            if ($list === 'my') {
                $resource->findByUserId(Auth::id());
            }
            if (!empty($search)) {
                $resource->where('bot_instances.tag_name', 'like', "%{$search}%")
                    ->orWhere('bot_instances.tag_user_email', 'like', "%{$search}%");
            }

            $resource->when($sort, function ($query, $sort) use ($order) {
                if (!empty(BotInstance::ORDER_FIELDS[$sort])) {
                    return QueryHelper::orderBotInstance($query, BotInstance::ORDER_FIELDS[$sort], $order);
                } else {
                    return $query->orderBy('aws_status', 'asc')->orderBy('start_time', 'desc');
                }
            }, function ($query) {
                return $query->orderBy('aws_status', 'asc')->orderBy('start_time', 'desc');
            });

            $bots = (new BotInstanceCollection($resource->paginate($limit)))->response()->getData();

            foreach ($bots->data as $bot) {
                $bot->last_notification = BotInstance::where('id', $bot->id)->pluck('last_notification')[0];
                $bot->difference = $this->calculateStatistics(S3Object::calculateStatistic($bot->id, $bot->status));
                //$bot->difference = S3Object::calculateStatistic($bot->id, $bot->status);
            }
            $meta = $bots->meta ?? null;

            $response = [
                'data' => $bots->data ?? [],
                'total' => $meta->total ?? 0
            ];

            return $this->success($response);

        } catch (Throwable $throwable) {
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param array
     * @return array
     */

    public function calculateStatistics($data)
    {
        $length = count($data);
        if ($length >= 2) {
            Log::debug("caculate statisistics {$length}");
            $difference = array();
            $prevTime = null;
            foreach ($data as $diff) {
                if ($prevTime != null) {
                    $prev = \Carbon\Carbon::parse($prevTime);
                    //Log::debug("prev**** {$prev}");
                    $current = \Carbon\Carbon::parse($diff->created_at);
                    //Log::debug("current*** {$current}");
                    $diffSeconds = $current->diffInSeconds($prev);
                    Log::debug("diffsec**** {$diffSeconds}");
                    if ($diffSeconds > 6) {
                        //Log::debug("diffsec is greater than 300");
                        $startTime = \Carbon\Carbon::parse($prevTime);
                        $endTime = \Carbon\Carbon::parse($diff->created_at);
                        while ($startTime < $endTime) {
                            array_push($difference, 0);
                            $startTime = $startTime->addSeconds(6);
                        }
                    } else {
                        //Log::debug("diffsec is less than 300");
                    }
                    array_push($difference, $diff->difference);
                    //Log::debug("difference ".json_encode($difference));
                }
                $prevTime = $diff->created_at;
            }
            //Log::debug("difference at end ".json_encode($difference));
            return $difference;
        } else {
            //Log::debug("return difference {$length}");
            return $data;
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function regions(Request $request)
    {
        $regions = AwsRegion::onlyEc2()->pluck('id', 'name')->toArray();
        $result = [];

        foreach ($regions as $name => $id) {
            array_push($result, ['name' => $name, 'id' => $id]);
        }

        return $this->success([
            'data' => $result
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function show(Request $request, $id)
    {
        /** @var User $user */
        $user = Auth::user();
        $resource = BotInstance::withTrashed()->find($id);
        if (!$resource) {
            $this->error('Not found', __('user.bots.not_found'));
        }

        $ip = $this->getIp();
        dispatch(new UpdateInstanceSecurityGroup($user, $ip, $resource));
        return $this->success((new BotInstanceResource($resource))->toArray($request));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        Log::debug("+++++++++++++update instance++++++++++++");
        try {

            $instance = $this->getInstanceWithCheckUser($id);

            if (empty($instance)) {
                return $this->notFound(__('user.not_found'), __('user.instances.not_found'));
            }

            $running = BotInstance::STATUS_RUNNING;
            $stopped = BotInstance::STATUS_STOPPED;
            $terminated = BotInstance::STATUS_TERMINATED;
            $restart = BotInstance::STATUS_RESTART;
            Log::debug("updateData " . json_encode($request->input('update')));
            if (!empty($request->input('update'))) {
                $updateData = $request->validate([
                    'update.status' => "in:{$running},{$stopped},{$terminated},{$restart}"
                ]);

                Log::debug('updateData after filter : ' . json_encode($updateData));

                foreach ($updateData['update'] as $key => $value) {
                    switch ($key) {
                        case 'status':
                            $user_id = Auth::id();

                            if (InstanceHelper::changeInstanceStatus($value, $id, $user_id)) {

                                $instance = new BotInstanceResource(BotInstance::withTrashed()
                                    ->where('id', '=', $id)->first());

                                broadcast(new InstanceStatusUpdated($user_id));

                                return $this->success($instance->toArray($request));
                            } else {
                                return $this->error(__('user.server_error'), __('user.instances.not_updated'));
                            }
                        default:
                            return $this->error(__('user.server_error'), __('user.instances.not_updated'));
                    }
                }

            }

            return $this->error(__('user.server_error'), __('user.instances.not_updated'));

        } catch (Throwable $throwable) {
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function reportIssue(Request $request, $id)
    {
        $screenshots = $request->input('screenshots');
        $message = $request->input('message');
        $instance = BotInstance::withTrashed()->find($id);

        if (empty($instance)) {
            return $this->error(__('keywords.not_found'), __('keywords.bots.not_found'));
        }

        if (empty($screenshots)) {
            return $this->error(__('keywords.error'), __('keywords.bots.error_screenshots'));
        }

        try {

            Log::info("Report Issue");

            $objects = S3Object::whereIn('id', $screenshots)->get();

            if ($objects->isNotEmpty()) {

                $sources = [];

                foreach ($objects as $object) {
                    $pathInfo = pathinfo($object->path);
                    $sources[] = [
                        'source' => $object->getS3Path(),
                        'path' => "screenshots/{$object->instance->aws_instance_id}/{$pathInfo['basename']}"
                    ];
                }

                $aws = new Aws();
                $urls = $aws->copyIssuedObject($sources);

                $body = "User: {$request->user()->email}\nInstance ID: {$instance->aws_instance_id}\nBot Name: {$instance->bot->name}
                \nMessage: {$message}";

                Log::debug($body);

                if (!empty($urls)) {
                    $screenshots = '';
                    foreach ($urls as $url) {
                        $pathInfo = pathinfo($url);
                        $screenshots .= " ![{$pathInfo['basename']}]({$url})\n";
                    }
                    $body = $body . "\n{$screenshots}";
                }

                Log::debug($body);

                GitHub::createIssue('Issue Report', $body);

                return $this->success([]);
            }

            return $this->error(__('keywords.error'), __('keywords.bots.not_found_screenshots'));

        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param string|null $id
     * @param bool $withTrashed
     * @return BotInstance|null
     */
    public function getInstanceWithCheckUser(?string $id, $withTrashed = false): ?BotInstance
    {
        /** @var BotInstance $query */
        $query = BotInstance::where('id', '=', $id)->orWhere('aws_instance_id', '=', $id);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->first();
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function updateLastNotification(Request $request)
    {
        try {
            $aws_instance_id = htmlspecialchars($request->input('aws_instance_id') ?? '');
            $notification = htmlspecialchars($request->input('notification') ?? '');
            $notification = date('Y-m-d H:i:s') . '(/break/)' . $notification;
            BotInstane::where('aws_instance_id', $aws_instance_id)->update(['last_notification' => $notification]);
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
        return response('The instance last notification has been updated.', 200);
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function updateLastNotificationTunnel(Request $request, string $instance_id)
    {
        Log::debug("updateLastNotification +++++++++++++++");
        try {
            $aws_instance_id = htmlspecialchars($request->input('aws_instance_id') ?? '');
            $notification = htmlspecialchars($request->input('notification') ?? '');
            $notification = date('Y-m-d H:i:s') . '(/break/)' . $notification;
            BotInstance::where('aws_instance_id', $aws_instance_id)->update(['last_notification' => $notification]);
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
        //return response('The instance last notification has been updated.', 200);
        return response()->json([], 201);
    }
}
