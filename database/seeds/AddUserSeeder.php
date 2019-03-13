<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'role_id' => '1',
            'name' => 'Darshan',
            'email' => 'darshan.technostacks@gmail.com',
            'verification_token' => str_random(),
            'password' => bcrypt('123456')
        ]);

        DB::table('users')->insert([
            'role_id' => '2',
            'name' => 'Rathod',
            'email' => 'darshan@technostacks.com',
            'verification_token' => str_random(),
            'password' => bcrypt('123456')
        ]);
    }
}
