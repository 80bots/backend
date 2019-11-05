<?php

namespace App\Jobs;

use App\BotInstance;
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
     * @var BotInstance
     */
    protected $instance;

    /**
     * @var string
     */
    protected $key;

    /**
     * Create a new job instance.
     *
     * @param BotInstance $instance
     * @param string $key
     */
    public function __construct(BotInstance $instance, string $key)
    {
        $this->instance = $instance;
        $this->key      = $key;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (strpos($this->key, (string)$this->instance->baseS3Dir) !== false) {
            $base = $this->instance->baseS3Dir . '/';
            //streamer-data/unsightlyunicorn794/2019-10-15/output/json/test.json
            $key = str_replace($base, '', $this->key);
            InstanceHelper::getObjectByPath($this->instance->id, $key);
        }
    }
}
