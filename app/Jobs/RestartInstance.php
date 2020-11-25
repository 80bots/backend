<?php

namespace App\Jobs;

use App\Bot;
use App\BotInstance;
use App\BotInstancesDetails;
use App\Events\InstanceLaunched;
use App\Helpers\InstanceHelper;
use App\AboutInstance;
use App\Services\Aws;
use App\User;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RestartInstance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var BotInstance
     */
    protected $instance;

    /**
     * @var BotInstancesDetails
     */
    protected $instanceDetail;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $params;

    /**
     * @var string|null
     */
    protected $ip;

    /**
     * Restart running instance.
     * @param BotInstance $instance
     * @param User $user
     * @param array|null $params
     * @param string|null $ip
     */
    public function __construct( BotInstance $instance, User $user, $params, ?string $ip)
    {
        $this->instance         = $instance;
        $this->instanceDetail   = $this->instance->details()->latest()->first();
        $this->user             = $user;
        $this->params           = $params;
        $this->ip               = $ip;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Restart instance for ' . $this->instance->id ?? '');

        try {
            ini_set('memory_limit', '-1');

            Log::debug("params  $$$$". json_encode($this->params));
            Log::debug("instance {$this->instance}");

            $aws = new Aws;
            // Instance Create
            // $newInstanceResponse = $aws->restartInstance(
            //     $this->instance,
            //     $this->user,
            //     $this->params
            // );

        } catch (GuzzleException $exception) {

            $pos = strpos($exception->getMessage(), '<?xml version="1.0" encoding="UTF-8"?>');

            if ($pos === false) {
                Log::error("Error on catch Throwable : {$exception->getMessage()}");
            } else {
                $message = preg_replace('/^(.*)<\?xml version="1\.0" encoding="UTF-8"\?>/s', '', $exception->getMessage());
                Log::error("Error on catch GuzzleException : {$message}");
            }

            $this->removeInstance();

        } catch (Throwable $throwable) {

            $pos = strpos($throwable->getMessage(), '<?xml version="1.0" encoding="UTF-8"?>');

            if ($pos === false) {
                Log::error("Error on catch Throwable : {$throwable->getMessage()}");
            } else {
                $message = preg_replace('/^(.*)<\?xml version="1\.0" encoding="UTF-8"\?>/s', '', $throwable->getMessage());
                Log::error("Error on catch Throwable : {$message}");
            }

            $this->removeInstance();
        }

        broadcast(new InstanceLaunched($this->instance, $this->user));
    }

    /**
     * @return void
     */
    private function removeInstance()
    {
        Log::debug("removeInstance");
        Log::debug(print_r($this->instance, true));

        if (! empty($this->instance)) {
            $this->instance->setAwsStatusTerminated();
        }
    }

    /**
     * @return void
     */
    private function addInstanceInfo()
    {
        try {

            Log::debug("Start addInstanceInfo");

            $details = $this->instanceDetail->only('aws_instance_type', 'aws_storage_gb', 'aws_image_id');
            $parameters = json_encode($this->params);
            $data = array_merge([
                'instance_id'               => $this->instance->id,
                'tag_name'                  => $this->instance->tag_name,
                'tag_user_email'            => $this->instance->tag_user_email,
                'bot_path'                  => $this->bot->path,
                'bot_name'                  => $this->bot->name,
                'params'                    => $parameters,
                'aws_region'                => $this->instance->region->code,
                's3_path'                   => $this->bot->s3_path,
            ], $details);

            Log::debug(print_r($data, true));

            AboutInstance::create($data);

            Log::debug("Completed addInstanceInfo");

        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
        }
    }
}
