<?php

namespace App\Console\Commands;

use App\AwsRegion;
use App\Services\Aws;
use Carbon\Carbon;
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
        $now        = Carbon::now()->toDateTimeString();

        if (! empty($regions)) {
            foreach ($regions as $region) {

                $limit = $this->getLimitByRegion($region['code'] ?? null);

                echo "Region {$region['name']} / limit {$limit}\n";

                AwsRegion::updateOrInsert(
                    [ 'code' => $region['code'] ],
                    [
                        'name'          => $region['name'],
                        'limit'         => $limit,
                        'updated_at'    => $now
                    ]
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

        // TODO: NEW Quotas Limit
        //$result = $aws->getServiceQuotas($region);

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
            }
        }

        unset($aws, $result, $res, $account);

        return $limit;
    }
}
