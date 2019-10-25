<?php

namespace App\Console\Commands;

use App\AwsRegion;
use App\Services\Aws;
use Aws\ServiceQuotas\Exception\ServiceQuotasException;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {
        $aws        = new Aws;
        $regions    = $aws->getEc2RegionsWithName();
        $now        = Carbon::now()->toDateTimeString();

        if (! empty($regions)) {
            foreach ($regions as $region) {

                $limit = $this->getLimitByRegion($region['code'] ?? null);

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
        $limit  = 0;
        $aws    = new Aws;

        try {

            // TODO: NEW Quotas Limit
            $result = $aws->getServiceQuotasT3MediumInstance($region);

            if ($result->hasKey('Quota')) {
                $quota = $result->get('Quota');
                $limit = intval($quota['Value']);
            }

        } catch (ServiceQuotasException $exception) {
            Log::error("GetServiceQuota : not fount region : {$region}");
            $limit = $this->getEc2AccountLimitByRegion($aws, $region);
        }

        unset($aws, $result, $quota);

        return $limit;
    }

    /**
     * @param Aws $aws
     * @param string $region
     * @return int
     */
    private function getEc2AccountLimitByRegion(Aws $aws, string $region): int
    {
        try {

            $limit = 0;

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

        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return 0;
        }
    }
}
