<?php

use Carbon\Carbon;
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
        $now = Carbon::now()->toDateTimeString();

        DB::table('platforms')->insert([
            'name' => 'Amazon',
            'created_at' => $now,
            'updated_at' => $now
        ]);

        DB::table('platforms')->insert([
            'name' => 'Facebook',
            'created_at' => $now,
            'updated_at' => $now
        ]);

        DB::table('platforms')->insert([
            'name' => 'Google',
            'created_at' => $now,
            'updated_at' => $now
        ]);

        DB::table('platforms')->insert([
            'name' => 'LinkedIn',
            'created_at' => $now,
            'updated_at' => $now
        ]);

        DB::table('platforms')->insert([
            'name' => 'Instagram',
            'created_at' => $now,
            'updated_at' => $now
        ]);
    }
}
