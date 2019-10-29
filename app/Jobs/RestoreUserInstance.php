<?php

namespace App\Jobs;

use App\BotInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RestoreUserInstance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var BotInstance
     */
    protected $instance;

    /**
     * Create a new job instance.
     *
     * @param BotInstance $instance
     */
    public function __construct(BotInstance $instance)
    {
        //$this->instance = $instance;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //$mongoData = $this->instance->mongodb;
    }
}
