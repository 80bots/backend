<?php

namespace App\Jobs;

use App\BotInstance;
use App\Helpers\InstanceHelper;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StoreS3Objects implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var User
     */
    protected $user;
    /**
     * @var string
     */
    protected $instance_id;

    /**
     * @var string
     */
    protected $key;

    protected float $difference;

    /**
     * Create a new job instance.
     *
     * @param User $user
     * @param string $instance_id
     * @param string $key
     * @param float $difference
     */
    public function __construct(User $user, string $instance_id, string $key,  float $difference = 0.00)
    {
        $this->user         = $user;
        $this->instance_id  = $instance_id;
        $this->key          = $key;
        $this->difference   = $difference;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $query = BotInstance::where('id', '=', $this->instance_id)
            ->orWhere('aws_instance_id', '=', $this->instance_id);

        $instance = $query->first();

        if (! $instance) return;

        InstanceHelper::getObjectByPath($instance->id, $this->key, $this->difference);
    }
}
