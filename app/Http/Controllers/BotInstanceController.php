<?php

namespace App\Http\Controllers;

use App\AwsRegion;
use App\Http\Resources\User\BotInstanceCollection;
use App\Http\Resources\User\BotInstanceResource;
use App\Services\Aws;
use App\BotInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class BotInstanceController extends AppController
{
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

            $resource = BotInstance::withTrashed()->findByUserId(Auth::id());

            // TODO: Add Filters

            //
            if (! empty($search)) {
                $resource->where('tag_name', 'like', "%{$search}%")
                    ->orWhere('aws_instance_id', 'like', "%{$search}%");
            }

            //
            if (! empty($sort)) {
                $resource->orderBy($sort, $order);
            }

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
        $regions = AwsRegion::onlyEc2()->pluck('name', 'code')->toArray();

        return $this->success([
            'regions' => $regions
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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
     * @param  \App\BotInstance  $userInstances
     * @return \Illuminate\Http\Response
     */
    public function show(BotInstance $userInstances)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\BotInstance  $userInstances
     * @return \Illuminate\Http\Response
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

            $instance = BotInstance::where([
                ['id', '=', $id],
                ['user_id', '=', Auth::id()]
            ])->first();

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

                            if ($this->changeStatus($value, $id)) {
                                $instance = new BotInstanceResource(BotInstance::withTrashed()
                                    ->where('id', '=', $id)->first());
                                return $this->success($instance->toArray($request));
                            } else {
                                return $this->error(__('user.server_error'), __('user.instances.not_updated'));
                            }

                            break;
                        default:
                            return $this->error(__('user.server_error'), __('user.instances.not_updated'));
                            break;
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
     * @return \Illuminate\Http\Response
     */
    public function destroy(BotInstance $userInstances)
    {
        //
    }
}
