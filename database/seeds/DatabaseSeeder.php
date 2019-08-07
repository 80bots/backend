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
             AddUserSeeder::class,
             AddPlatformsSeeder::class,
             SubscriptionPlansTableSeeder::class,
             TimezonesTableSeeder::class
         ]);
    }
}
