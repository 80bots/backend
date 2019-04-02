<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddPlatformsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('platforms')->insert([
            'name' => 'Amazon',
        ]);

        DB::table('platforms')->insert([
            'name' => 'Facebook',
        ]);

        DB::table('platforms')->insert([
            'name' => 'Google',
        ]);

        DB::table('platforms')->insert([
            'name' => 'LinkedIn',
        ]);

        DB::table('platforms')->insert([
            'name' => 'Instagram',
        ]);
    }
}
