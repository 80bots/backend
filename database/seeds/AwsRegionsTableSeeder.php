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
        $aws        = new Aws;
        $regions    = $aws->getEc2RegionsWithName();

        if (! empty($regions)) {
            foreach ($regions as $region) {

                $limit = $this->getLimitByRegion($region['code'] ?? null);

                echo "Region {$region['name']} / limit {$limit}\n";

                AwsRegion::create([
                    'code' => $region['code'],
                    'name'  => $region['name'],
                    'limit' => $limit
                ]);
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
