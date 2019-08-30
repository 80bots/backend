<?php

use App\AwsSetting;
use Illuminate\Database\Seeder;

class AwsSettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AwsSetting::create([
            'image_id'  => config('aws.image_id', 'ami-0ebc3e5e32781b350'),
            'type'      => config('aws.instance_type', 't3.small'),
            'storage'   => config('aws.volume_size', 32),
            'default'   => true
        ]);
    }
}
