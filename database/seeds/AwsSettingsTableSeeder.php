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
            'image_id' => config('aws.image_id'),
            'type' => config('aws.instance_type'),
            'storage' => config('aws.volume_size'),
            'default' => true
        ]);
    }
}
