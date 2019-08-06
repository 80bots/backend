<?php

use App\Role;
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
        $admin = Role::where('name', '=', 'Admin')->pluck('id')->first();
        $user = Role::where('name', '=', 'User')->pluck('id')->first();

        DB::table('users')->insert([
            'role_id' => $admin,
            'name' => 'Darshan',
            'email' => 'darshan.technostacks@gmail.com',
            'password' => bcrypt('123456'),
            'status' => 'active'
        ]);

        DB::table('users')->insert([
            'role_id' => $admin,
            'name' => 'Francis Admin',
            'email' => 'francis+admin@inforca.com',
            'password' => bcrypt('12345678'),
            'status' => 'active'
        ]);

        DB::table('users')->insert([
            'role_id' => $user,
            'name' => 'Rathod',
            'email' => 'darshan@technostacks.com',
            'password' => bcrypt('123456'),
            'status' => 'active'
        ]);

        DB::table('users')->insert([
            'role_id' => $user,
            'name' => 'Francis User',
            'email' => 'francis+user@inforca.com',
            'password' => bcrypt('12345678'),
            'status' => 'active'
        ]);
    }
}
