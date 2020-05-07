<?php

use App\AwsRegion;
use App\Services\Aws;
use Illuminate\Database\Seeder;

class AwsRegionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $aws    = new Aws;
        $regions = $aws->getEc2Regions();

        /* Hardcoded list of all regions with name
         * source here: https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/using-regions-availability-zones.html
         */
        $all_regions = [
            ['code' => 'us-east-2', 'name' => 'US East (Ohio)'],
            ['code' => 'us-east-1', 'name' => 'US East (N. Virginia)'],
            ['code' => 'us-west-1', 'name' => 'US West (N. California)'],
            ['code' => 'us-west-2', 'name' => 'US West (Oregon)'],
            ['code' => 'af-south-1', 'name' => 'Africa (Cape Town)'],
            ['code' => 'ap-east-1', 'name' => 'Asia Pacific (Hong Kong)'],
            ['code' => 'ap-south-1', 'name' => 'Asia Pacific (Mumbai)'],
            ['code' => 'ap-northeast-3', 'name' => 'Asia Pacific (Osaka-Local)'],
            ['code' => 'ap-northeast-2', 'name' => 'Asia Pacific (Seoul)'],
            ['code' => 'ap-southeast-1', 'name' => 'Asia Pacific (Singapore)'],
            ['code' => 'ap-southeast-2', 'name' => 'Asia Pacific (Sydney)'],
            ['code' => 'ap-northeast-1', 'name' => 'Asia Pacific (Tokyo)'],
            ['code' => 'ca-central-1', 'name' => 'Canada (Central)'],
            ['code' => 'eu-central-1', 'name' => 'Europe (Frankfurt)'],
            ['code' => 'eu-west-1', 'name' => 'Europe (Ireland)'],
            ['code' => 'eu-west-2', 'name' => 'Europe (London)'],
            ['code' => 'eu-south-1', 'name' => 'Europe (Milan)'],
            ['code' => 'eu-west-3', 'name' => 'Europe (Paris)'],
            ['code' => 'eu-north-1', 'name' => 'Europe (Stockholm)'],
            ['code' => 'me-south-1', 'name' => 'Middle East (Bahrain'],
            ['code' => 'sa-east-1', 'name' => 'South America (São Paulo)'],
        ];

        if (! empty($regions)) {
            foreach ($all_regions as $region) {
                if (in_array($region['code'], $regions)) {

                    $limit = $this->getLimitByRegion($region['code'] ?? null);

                    echo "Region {$region['name']} / limit {$limit}\n";

                    AwsRegion::create([
                        'code' => $region['code'],
                        'name' => $region['name'],
                        'limit' => $limit
                    ]);
                }
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
