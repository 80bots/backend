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

    /**
     * Create a new job instance.
     *
     * @param User $user
     * @param string|null $ip
     */
    public function __construct(User $user, ?string $ip)
    {
        $this->user = $user;
        $this->ip   = $ip;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        Log::info('Starting UpdateInstanceSecurityGroup');

        try {

            $ports = config('aws.ports.access_user');

            $aws = new Aws;

            $this->user->instances()->chunk(10, function ($instances) use ($aws, $ports) {

                foreach ($instances as $instance) {

                    $aws->ec2Connection($instance->region->code);

                    $securityGroup = $instance->oneDetail->aws_security_group_id;

                    $result = $aws->describeSecurityGroups($securityGroup);

                    if ($result->hasKey('SecurityGroups')) {

                        $securityGroups = $result->get('SecurityGroups');
                        $ipPermissions = collect($securityGroups[0]['IpPermissions']);

                        $ipPermissions = $ipPermissions->filter(function ($item, $key) use ($ports) {
                            return in_array($item['FromPort'], $ports);
                        });

                        if ($ipPermissions->isNotEmpty()) {
                            $ipRanges = $ipPermissions->map(function ($item, $key) {
                                return [
                                    'port'  => $item['ToPort'],
                                    'ip'    => collect($item['IpRanges'])->map(function ($item, $key) {
                                                return $item['CidrIp'];
                                            })->toArray()
                                ];
                            })->toArray();

                            sort($ipRanges);

                            foreach ($ipRanges as $ipRange) {
                                if (! in_array("{$this->ip}/32", $ipRange['ip'])) {
                                    $result = $aws->updateSecretGroupIngress($ipRange['port'], $this->ip, 'tcp', $securityGroup);
                                }
                            }
                            unset($ipRanges);
                        }
                        unset($securityGroups, $ipPermissions, $result, $securityGroup);
                    }
                }
            });

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
