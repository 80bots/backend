<?php

namespace App\Http\Controllers\Admin;

use App\Bot;
use App\Http\Controllers\AppController;
use App\Http\Resources\Admin\UserInstanceCollection;
use App\Services\Aws;
use App\User;
use App\UserInstance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class BotInstancesController extends AppController
{
    const PAGINATE = 1;

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return UserInstanceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {

            $resource = UserInstance::ajax();

            // TODO: Add Filters

            switch ($request->input('list')) {
                case 'my_bots':
                    $resource->with('user')->findByUserId(Auth::id());
                    break;
                default:
                    $resource->with('user');
                    break;
            }

            return new UserInstanceCollection($resource->paginate(self::PAGINATE));

        } catch (Throwable $throwable) {
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
//        try {
//
//            $bot = Bots::find($request->bot_id);
//
//            if (!$bot) {
//                return redirect()->back()->with('error', 'Bot Not Found Please Try Again');
//            }
//
//            $aws = new Aws;
//
//            $keyPair        = $aws->createKeyPair();
//            $keyPairName    = $keyPair['keyName'];
//            $keyPairPath    = $keyPair['path'];
//
//            $securityGroup  = $aws->createSecretGroup();
//            $groupId        = $securityGroup['securityGroupId'];
//            $groupName      = $securityGroup['securityGroupName'];
//
//            // Instance Create
//            $newInstanceResponse = $aws->launchInstance($keyPairName, $groupName, $bot);
//
//            if (! $newInstanceResponse->hasKey('Instances')) {
//                return redirect()->back()->with('error', 'Not Found Instance');
//            }
//
//            $instanceId = $newInstanceResponse->get('Instances')[0]['InstanceId'];
//
//            $waitUntilResponse = $aws->waitUntil([$instanceId]);
//
//            /*if(!empty($bot)){
//                $StartUpScriptString = $bot->aws_startup_script;
//                $StartUpScript = explode(PHP_EOL, $StartUpScriptString);
//                $runScript = $this->RunStartUpScript($StartUpScript);
//            }*/
//
//            // Instance Describe for Public Dns Name
//            $describeInstancesResponse = $aws->describeInstances([$instanceId]);
//
//            if (! $describeInstancesResponse->hasKey('Reservations')) {
//                return redirect()->back()->with('error', 'Not Found Instance Describe');
//            }
//
//            $instanceArray = $describeInstancesResponse->get('Reservations')[0]['Instances'][0];
//
//            $LaunchTime = $instanceArray['LaunchTime'] ?? '';
//            $publicIp = $instanceArray['PublicIpAddress'] ?? '';
//            $publicDnsName = $instanceArray['PublicDnsName'] ?? '';
//
//            $awsAmiId = env('AWS_IMAGEID', 'ami-0cd3dfa4e37921605');
//
//            $created_at = date('Y-m-d H:i:s', strtotime($LaunchTime));
//
//            // store instance details in database
//            $userInstance = new UserInstances();
//            $userInstance->user_id = $user_id;
//            $userInstance->bot_id = $bot_id;
//            $userInstance->aws_instance_id = $instanceId;
//            $userInstance->aws_ami_id = $awsAmiId;
//            $userInstance->aws_security_group_id = $groupId;
//            $userInstance->aws_security_group_name = $groupName;
//            $userInstance->aws_public_ip = $publicIp;
//            $userInstance->status = 'running';
//            $userInstance->aws_public_dns = $publicDnsName;
//            $userInstance->aws_pem_file_path = $keyPairPath;
//            $userInstance->created_at = $created_at;
//            if ($userInstance->save()) {
//                $userInstanceDetail = new UserInstancesDetails();
//                $userInstanceDetail->user_instance_id = $userInstance->id;
//                $userInstanceDetail->start_time = $created_at;
//                $userInstanceDetail->save();
//                session()->flash('success', 'Instance Created successfully');
//                return redirect(route('admin.my-bots'));
//            }
//            session()->flash('error', 'Please Try again later');
//            return redirect(route('admin.my-bots'));
//        } catch (\Exception $e) {
//            session()->flash('error', $e->getMessage());
//            return redirect(route('admin.my-bots'));
//        }
    }

    public function syncInstances()
    {
        try {

          Log::info('Sync started at ' . date('Y-m-d h:i:s'));

          $aws = new Aws;
          $instancesByStatus = $aws->sync();

          Log::info(print_r($instancesByStatus, true));

          $awsInstancesIn = [];
          foreach ($instancesByStatus as $status => $instances) {
            foreach ($instances as $key => $instance) {
              $bot = Bot::where('aws_ami_image_id', $instance['aws_ami_id'])->first();

              if($bot) {
                $instance['bot_id'] = $bot->id;
              }

              $userInstance = UserInstance::where('aws_instance_id' , $instance['aws_instance_id'])->first();

              if($status == 'stopped') {
                  $status = 'stop';
              }

              if(!$userInstance) {
                Log::info($instance['aws_instance_id'] . ' has not been recorded while launch or manually launched from the aws');
                $admin = User::where('role_id', 1)->first();
                if($admin) {
                  $instance['user_id']      = $admin->id;
                  $instance['status']       = $status;
                  if($status == 'running') {
                    $instance['is_in_queue']  = 0;
                  }
                  $userInstance = UserInstance::updateOrCreate([
                    'aws_instance_id' => $instance['aws_instance_id']
                  ], $instance);
                } else {
                  Log::info($instance['aws_instance_id'] . ' cannot be synced');
                }
              } else {
                $userInstance->status         = $status;
                $userInstance->tag_name       = $instance['tag_name'];
                $userInstance->tag_user_email = $instance['tag_user_email'];
                if($status == 'running') {
                  $userInstance->is_in_queue = 0;
                }
                $userInstance->save();

              }

              $awsInstancesIn[] = $instance['aws_instance_id'];
            }
          }

          UserInstance::where(function($query) use($awsInstancesIn) {
                          $query->whereNotIn('aws_instance_id', $awsInstancesIn)
                          ->orWhere('aws_instance_id', null)
                          ->orWhere('status', 'terminated');
                        })->whereNotIn('status', ['start', 'stop'])
                        ->delete();

          UserInstance::where(function($query) {
                          $query->where('is_in_queue', 1)
                          ->orWhereIn('status', ['start', 'stop']);
                        })->where('updated_at', '<' , Carbon::now()->subMinutes(10)->toDateTimeString())
                        ->delete();

          Log::info('Synced completed at ' . date('Y-m-d h:i:s'));
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }

        if(!App::runningInConsole()) {
          session()->flash('success', 'Instances updated successfully!');
          return back();
        }
    }
}
