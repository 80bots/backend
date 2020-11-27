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

class UpdateScriptRestartBot implements ShouldQueue
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
        Log::info('Update  Script and restart bot for ' . $this->instance->id ?? '');

        try {
            ini_set('memory_limit', '-1');

            Log::debug("params  $$$$". json_encode($this->params));
            Log::debug("instance {$this->instance}");

            $aws = new Aws;
            //Instance Restart
            $newInstanceResponse = $aws->updateScriptRestartBot(
                $this->instance,
                $this->user,
                $this->params
            );

        }catch (Throwable $throwable) {

            $pos = strpos($throwable->getMessage(), '<?xml version="1.0" encoding="UTF-8"?>');

            if ($pos === false) {
                Log::error("Error on catch Throwable : {$throwable->getMessage()}");
            } else {
                $message = preg_replace('/^(.*)<\?xml version="1\.0" encoding="UTF-8"\?>/s', '', $throwable->getMessage());
                Log::error("Error on catch Throwable : {$message}");
            }
        }
    }
}
