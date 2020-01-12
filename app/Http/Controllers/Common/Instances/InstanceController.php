<?php

namespace App\Http\Controllers\Common\Instances;

use App\AwsRegion;
use App\BotInstance;
use App\Events\InstanceStatusUpdated;
use App\Helpers\ApiResponse;
use App\Helpers\InstanceHelper;
use App\Helpers\QueryHelper;
use App\Http\Controllers\AppController;
use App\Http\Resources\BotInstanceCollection;
use App\Http\Resources\BotInstanceResource;
use App\S3Object;
use App\Services\Aws;
use App\Services\GitHub;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstanceController extends AppController {
    const PAGINATE = 1;

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return BotInstanceCollection
     */
    public function index(Request $request)
    {
        try {
            $limit = $request->query('limit') ?? self::PAGINATE;
            $search = $request->input('search');
            $sort   = $request->input('sort');
            $order  = $request->input('order') ?? 'asc';
            $list  = $request->input('list') ?? 'all';
            $resource = BotInstance::withTrashed();
            if(!Auth::user()->isAdmin() || $list === 'my') {
                $resource->findByUserId(Auth::id());
            }
            if (! empty($search)) {
                $resource->where('bot_instances.tag_name', 'like', "%{$search}%")
                    ->orWhere('bot_instances.tag_user_email', 'like', "%{$search}%");
            }

            $resource->when($sort, function ($query, $sort) use ($order) {
                if (! empty(BotInstance::ORDER_FIELDS[$sort])) {
                    return QueryHelper::orderBotInstance($query, BotInstance::ORDER_FIELDS[$sort], $order);
                } else {
                    return $query->orderBy('aws_status', 'asc')->orderBy('start_time', 'desc');
                }
            }, function ($query) {
                return $query->orderBy('aws_status', 'asc')->orderBy('start_time', 'desc');
            });

            $bots   = (new BotInstanceCollection($resource->paginate($limit)))->response()->getData();
            $meta   = $bots->meta ?? null;

            $response = [
                'data'  => $bots->data ?? [],
                'total' => $meta->total ?? 0
            ];

            return $this->success($response);

        } catch (Throwable $throwable) {
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }

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
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        /*$BotObj = Bots::find(1);
        $string = $BotObj->aws_startup_script;
        $StartUpScript = array_filter(explode(';',$string));
        $runScript = $this->RunStartUpScript($StartUpScript);
        dd($runScript);*/
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        /* $user_id = Auth::user()->id;
         $bot_id = isset($request->bot_id) ? $request->bot_id : '';
         try {
             $bots = null;
             $botObj = Bots::find($bot_id);
             if(empty($botObj)){
                 return redirect()->back()->with('error', 'Bot Not Found Please Try Again');
             } else {
                 $bots = $botObj;
             }
             $keyPair = $this->CreateKeyPair();
             $SecurityGroup = $this->CreateSecurityGroupId();

             $keyPairName = $keyPair['keyName'];
             $keyPairPath = $keyPair['path'];

             $groupId = $SecurityGroup['securityGroupId'];
             $groupName = $SecurityGroup['securityGroupName'];
             $instanceIds = [];
             // Instance Create
             $newInstanceResponse = $this->LaunchInstance($keyPairName, $groupName, $bots);
             $instanceId = $newInstanceResponse->getPath('Instances')[0]['InstanceId'];

             array_push($instanceIds, $instanceId);
             $waitUntilResponse = $this->waitUntil($instanceIds);

             /*if(!empty($bots)){
                 $StartUpScriptString = $bots->aws_startup_script;
                 $StartUpScript = explode(PHP_EOL, $StartUpScriptString);
                 $runScript = $this->RunStartUpScript($StartUpScript);
             }*/

        // Instance Describe for Public Dns Name
        /*$describeInstancesResponse = $this->DescribeInstances($instanceIds);
        $instanceArray = $describeInstancesResponse->getPath('Reservations')[0]['Instances'][0];

        $LaunchTime = isset($instanceArray['LaunchTime']) ? $instanceArray['LaunchTime'] : '';
        $publicIp = isset($instanceArray['PublicIpAddress']) ? $instanceArray['PublicIpAddress'] : '';
        $publicDnsName = isset($instanceArray['PublicDnsName']) ? $instanceArray['PublicDnsName'] : '';

        $awsAmiId = env('AWS_IMAGEID','ami-0cd3dfa4e37921605');

        $created_at = date('Y-m-d H:i:s', strtotime($LaunchTime));

        // store instance details in database
        $userInstance = new UserInstances();
        $userInstance->user_id = $user_id;
        $userInstance->bot_id = $bot_id;
        $userInstance->aws_instance_id = $instanceId;
        $userInstance->aws_ami_id = $awsAmiId;
        $userInstance->aws_security_group_id = $groupId;
        $userInstance->aws_security_group_name = $groupName;
        $userInstance->aws_public_ip = $publicIp;
        $userInstance->status = 'running';
        $userInstance->aws_public_dns = $publicDnsName;
        $userInstance->aws_pem_file_path = $keyPairPath;
        $userInstance->created_at = $created_at;
        if($userInstance->save()){
            $userInstanceDetail = new UserInstancesDetails();
            $userInstanceDetail->user_instance_id = $userInstance->id;
            $userInstanceDetail->start_time = $created_at;
            $userInstanceDetail->save();
            session()->flash('success', 'Instance Create successfully');
            return redirect(route('user.instance.index'));
        }
        session()->flash('error', 'Please Try again later');
        return redirect(route('user.instance.index'));
    }
    catch (\Exception $e) {
        session()->flash('error', $e->getMessage());
        return redirect(route('user.instance.index'));
    }*/
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function show(Request $request, $id)
    {
        $resource = BotInstance::withTrashed()->find($id);
        if(!empty($resource)) {
            return $this->success((new BotInstanceResource($resource))->toArray($request));
        } else {
            $this->error('Not found', __('admin.bots.not_found'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param BotInstance $userInstances
     * @return Response
     */
    public function edit(BotInstance $userInstances)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        try {

            $instance = $this->getInstanceWithCheckUser($id);

            if (empty($instance)) {
                return $this->notFound(__('user.not_found'), __('user.instances.not_found'));
            }

            $running    = BotInstance::STATUS_RUNNING;
            $stopped    = BotInstance::STATUS_STOPPED;
            $terminated = BotInstance::STATUS_TERMINATED;

            if (! empty($request->input('update'))) {
                $updateData = $request->validate([
                    'update.status' => "in:{$running},{$stopped},{$terminated}"
                ]);

                foreach ($updateData['update'] as $key => $value) {
                    switch ($key) {
                        case 'status':

                            if (InstanceHelper::changeInstanceStatus($value, $id)) {

                                $instance = new BotInstanceResource(BotInstance::withTrashed()
                                    ->where('id', '=', $id)->first());

                                broadcast(new InstanceStatusUpdated(Auth::id()));

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

        } catch (Throwable $throwable){
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  BotInstance  $userInstances
     * @return Response
     */
    public function destroy(BotInstance $userInstances)
    {
        //
    }

    /**
     * @param Request $request
     * @param $id
     * @return ApiResponse
     */
    public function reportIssue(Request $request, $id)
    {
        $screenshots    = $request->input('screenshots');
        $message        = $request->input('message');
        $instance       = BotInstance::withTrashed()->find($id);

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
                    $pathInfo   = pathinfo($object->path);
                    $sources[]  = [
                        'source'    => $object->getS3Path(),
                        'path'      => "screenshots/{$object->instance->aws_instance_id}/{$pathInfo['basename']}"
                    ];
                }

                $aws    = new Aws();
                $urls   = $aws->copyIssuedObject($sources);

                $body = "User: {$request->user()->email}\nInstance ID: {$instance->aws_instance_id}\nBot Name: {$instance->bot->name}
                \nMessage: {$message}";

                Log::debug($body);

                if (! empty($urls)) {
                    $screenshots = '';
                    foreach ($urls as $url) {
                        $pathInfo   = pathinfo($url);
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

        if (! Auth::user()->isAdmin()) {
            $query->where('user_id', '=', Auth::id());
        }

        return $query->first();
    }
}