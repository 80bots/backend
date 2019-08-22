<?php

use App\Bot;
use App\Platform;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BotsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $platforms = Platform::get();

        foreach ($platforms as $platform) {
            for ($i = 0; $i <= 5; $i++) {
                Bot::create([
                    'name'                  => Str::random(10),
                    'platform_id'           => $platform->id ?? null,
                    'description'           => Str::random(50),
                    'aws_ami_image_id'      => 'ami-0de51bde84cbc7049',
                    'aws_ami_name'          => 'AMI-' . Str::random(10),
                    'aws_instance_type'     => 't2.micro',
                    'aws_startup_script'    => '',
                    'aws_custom_script'     => '',
                    'aws_storage_gb'        => 8,
                    'type'                  => Bot::TYPE_PUBLIC,
                    'parameters'            => json_encode([
                        ['name' => 'username', 'type' => 'String'],
                        ['name' => 'password', 'type' => 'String'],
                        ['name' => 'speed', 'type' => 'Number', 'range' => [1, 10]],
                        ['name' => 'type', 'type' => 'String', 'enum' => ['type1', 'type2'] ],
                        ['name' => 'public', 'type' => 'Boolean']
                    ]),
                ]);
            }
        }
    }
}
