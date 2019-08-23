<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
         $this->call([
             RolesTableseeder::class,
             SubscriptionPlansTableSeeder::class,
             TimezonesTableSeeder::class,
             AwsRegionsTableSeeder::class,
             AddUserSeeder::class,
             AddPlatformsSeeder::class,
             BotsTableSeeder::class,
             AwsAmisTableSeeder::class
         ]);
    }
}
