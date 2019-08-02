<?php

namespace App\Console\Commands;

use App\Bots;
use App\Services\Aws;
use App\User;
use App\UserInstances;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstanceSyncScheduling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instance:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * @var Carbon
     */
    private $now;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        try {

            Log::info('Sync started at ' . date('Y-m-d h:i:s'));

            $aws    = new Aws;
            $limit  = 5;
            $token  = '';

            do
            {
                $instancesByStatus = $aws->sync($limit, $token);
                $token = $instancesByStatus['nextToken'] ?? '';

                $this->syncInstances($instancesByStatus['data']);

            } while(! empty($instancesByStatus['nextToken']));

        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
        }
    }

    private function syncInstances($instancesByStatus)
    {
        try {

            $awsInstancesIn = collect($instancesByStatus)->collapse()->map(function ($item, $key) {
                return $item['aws_instance_id'];
            })->toArray();

            foreach ($instancesByStatus as $status => $instances) {
                foreach ($instances as $key => $instance) {

                    $bot = Bots::where('aws_ami_image_id', $instance['aws_ami_id'])->first();

                    if (! empty($bot)) {
                        $instance['bot_id'] = $bot->id;
                    }

                    if ($status == 'stopped') {
                        $status = 'stop';
                    }

                    $userInstance = UserInstances::where('aws_instance_id' , $instance['aws_instance_id'])->first();

                    if (! empty($userInstance)) {

                        $fill = [
                            'status'            => $status === 'stopped' ? 'stop' : $status,
                            'tag_name'          => $instance['tag_name'] ?? '',
                            'tag_user_email'    => $instance['tag_user_email'] ?? '',
                        ];

                        if($status === 'running') {
                            $fill['is_in_queue'] = 0;
                        }

                        $userInstance->fill($fill);
                        $userInstance->save();

                    } else {

                        Log::info($instance['aws_instance_id'] . ' has not been recorded while launch or manually launched from the aws');

                        $admin = User::whereHas('role', function (Builder $query) {
                            $query->where('name', '=', 'Admin');
                        })->first();

                        if (! empty($admin)) {

                            $instance['user_id']      = $admin->id;
                            $instance['status']       = $status;
                            if($status == 'running') {
                                $instance['is_in_queue']  = 0;
                            }

                            $userInstance = UserInstances::updateOrCreate([
                                'aws_instance_id' => $instance['aws_instance_id']
                            ], $instance);

                        } else {
                            Log::info($instance['aws_instance_id'] . ' cannot be synced');
                        }
                    }
                }
            }

            UserInstances::where(function($query) use($awsInstancesIn) {
                $query->whereNotIn('aws_instance_id', $awsInstancesIn)
                    ->orWhere('aws_instance_id', null)
                    ->orWhere('status', 'terminated');
            })->whereNotIn('status', ['start', 'stop'])
                ->delete();

            UserInstances::where(function($query) {
                $query->where('is_in_queue', 1)
                    ->orWhereIn('status', ['start', 'stop']);
            })->where('updated_at', '<' , Carbon::now()->subMinutes(10)->toDateTimeString())
                ->delete();

            Log::info('Synced completed at ' . date('Y-m-d h:i:s'));
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
        }

//        if(!App::runningInConsole()) {
//            session()->flash('success', 'Instances updated successfully!');
//            return back();
//        }
    }
}
