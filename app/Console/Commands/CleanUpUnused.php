<?php

namespace App\Console\Commands;

use App\DeleteSecurityGroup;
use App\Services\Aws;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CleanUpUnused extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instance:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up unused security groups';

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
        /**
         * Get all the removed instances for the last hour and remove connected groups,
         * as it is impossible to remove a group once the instance isn't fully removed on AWS
         */

        $now = Carbon::now()->subHour();

        $aws = new Aws;

        DeleteSecurityGroup::where('created_at', '<', $now->toDateTimeString())
            ->chunk(200, function ($groups) use ($aws) {
                foreach ($groups as $group) {

                    try {
                        $result = $aws->deleteSecurityGroup($group->group_id ?? '', $group->group_name ?? '');

                        if ($result) {
                            $group->delete();
                        }

                    } catch (Throwable $throwable) {
                        Log::error($throwable->getMessage());
                    }
                }
            });
    }
}
