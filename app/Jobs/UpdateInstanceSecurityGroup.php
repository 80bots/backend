<?php

namespace App\Jobs;

use App\Services\Aws;
use App\User;
use App\Visitor;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateInstanceSecurityGroup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var string
     */
    protected $ip;
    private $resource;

    /**
     * Create a new job instance.
     *
     * @param User $user
     * @param string|null $ip
     * @param $resource
     */
    public function __construct(User $user, ?string $ip, $resource)
    {
        $this->user = $user;
        $this->ip = $ip;
        $this->resource = $resource;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        Log::info("Starting UpdateInstanceSecurityGroup: $this->ip, $this->user, $this->resource->aws_instance_id");

        try {

            $ports = config('aws.ports.access_user');
            $aws = new Aws;

            if ($this->resource->aws_instance_id && $this->resource->aws_status == 'running') {
                $result = $aws->describeOneInstanceStatus($this->resource->aws_instance_id);
                Log::info($result);
                $aws->updateSecretGroupIngress($ports[0], $this->ip);
            }

            $now = Carbon::now()->toDateTimeString();

            Visitor::insertOrIgnore([
                [
                    'user_id' => $this->user->id,
                    'ip' => $this->ip,
                    'created_at' => $now,
                    'updated_at' => $now
                ],
            ]);
            unset($now);
            Log::info('Completed UpdateInstanceSecurityGroup');
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
        }
    }
}
