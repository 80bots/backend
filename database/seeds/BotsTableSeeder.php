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
                    'platform_id'   => $platform->id ?? null,
                    'name'          => Str::random(10),
                    'description'   => Str::random(50),
                    'type'          => Bot::TYPE_PUBLIC,
                    'parameters'    => json_encode([
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
