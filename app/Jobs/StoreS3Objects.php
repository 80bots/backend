<?php

namespace App\Jobs;

use App\BotInstance;
use App\Events\S3ObjectAdded;
use App\Helpers\InstanceHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StoreS3Objects implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    protected $ip;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * Create a new job instance.
     *
     * @param string $ip
     * @param array $parameters
     */
    public function __construct(string $ip, array $parameters)
    {
        $this->ip           = $ip;
        $this->parameters   = $parameters;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $instance   = BotInstance::find($this->parameters['instance_id'] ?? null);

        if (! empty($instance) /* && $this->ip === $instance->aws_public_ip*/) {
            if (strpos($this->parameters['key'], (string)$instance->baseS3Dir) !== false) {
                //streamer-data/unsightlyunicorn794/2019-10-15/output/json/test.json
                $key = str_replace("{$instance->baseS3Dir}/", '', $this->parameters['key']);
                $result = InstanceHelper::getObjectByPath($instance->id, $key);
                //broadcast(new S3ObjectAdded($instance));
            }
        }
    }
}
