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
        $script = <<<HERESHELL
file="puppeteer/params/params.json"
username="kabas"
cd /home/\$username/

su - \$username -c 'git clone -b master https://14b12de18e2199b2d584d3f6cf9492f3353f9b3e@github.com/80bots/puppeteer.git ./puppeteer'
su - \$username -c 'cd ./puppeteer && npm i'

su - \$username -c 'mkdir params'
HERESHELL;

        AwsSetting::create([
            'image_id'  => config('aws.image_id', 'ami-0a15d2bfc04351315'),
            'type'      => config('aws.instance_type', 't3.medium'),
            'storage'   => config('aws.volume_size', 32),
            'script'    => $script,
            'default'   => true
        ]);
    }
}
