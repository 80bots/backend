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

                $limit = $this->getLimitByRegion($region['code'] ?? null);

                AwsRegion::updateOrInsert(
                    [
                        'name'  => $region['name'],
                        'limit' => $limit
                    ],
                    [ 'code' => $region['code'] ]
                );
            }
        }
    }

    /**
     * @param string $region
     * @return int
     */
    private function getLimitByRegion(string $region): int
    {
        $limit = 0;

        $aws    = new Aws;
        $result = $aws->getEc2AccountAttributes($region);

        if ($result->hasKey('AccountAttributes')) {

            $account = $result->get('AccountAttributes');

            if (! empty($account) && is_array($account)) {

                $account = collect($account);

                $res = $account->filter(function ($value, $key) {
                    return $value['AttributeName'] === "max-instances";
                })->map(function ($item, $key) {
                    return $item['AttributeValues'][0]['AttributeValue'];
                })->toArray();

                sort($res);

                $limit = intval($res[0]);
                unset($res);
            }

            unset($account);
        }

        unset($aws);
        unset($result);

        return $limit;
    }
}
