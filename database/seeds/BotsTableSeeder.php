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
        Artisan::call('bots:sync-local');
    }
}
