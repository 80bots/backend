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
                AwsRegion::updateOrInsert(
                    [ 'name' => $region['name'] ],
                    [ 'code' => $region['code'] ]
                );
            }
        }
    }
}
