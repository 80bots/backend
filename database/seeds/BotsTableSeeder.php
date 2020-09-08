<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class BotsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Artisan::call('bots:sync-s3');
    }
}
