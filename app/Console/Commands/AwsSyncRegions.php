<?php

namespace App\Console\Commands;

use App\AwsRegion;
use App\Services\Aws;
use Illuminate\Console\Command;

class AwsSyncRegions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aws:sync-regions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $aws        = new Aws;
        $regions    = $aws->getEc2RegionsWithName();

        if (! empty($regions)) {
            foreach ($regions as $region) {
                AwsRegion::updateOrInsert(
                    [ 'name' => $region['name'] ],
                    [ 'code' => $region['code'] ]
                );
            }
        }
    }
}
