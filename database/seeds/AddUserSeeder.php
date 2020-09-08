<?php

use App\Timezone;
use App\AwsRegion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AddUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $random = str_shuffle('abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$%^&!$%^&');
        $email          = 'hello@80bots.com';
        $randomPassword = substr($random, 0, 10);
        $timezone       = Timezone::all()->pluck('id')->first();
        $region         = AwsRegion::onlyEc2()->where('code', '=', 'us-east-2')->pluck('id')->first()
            ?? AwsRegion::onlyEc2()->pluck('id')->first();

        DB::table('users')->insert([
            'timezone_id'   => $timezone,
            'region_id'     => $region,
            'name'          => '80Bots',
            'email'         => $email,
            'password'      => Hash::make($randomPassword),
            'status'        => 'active',
        ]);
        echo "\033[01;32m  Access to your account. \033[0m" . "\n";
        echo "\033[01;32m  Email: \033[0m" . $email . "\n";
        echo "\033[01;32m  Password: \033[0m" . $randomPassword . "\n";
    }
}
