<?php

use App\Timezone;
use App\AwsRegion;
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
        $timezone   = Timezone::all()->pluck('id')->first();
        $region     = AwsRegion::onlyEc2()->where('code', '=', 'us-east-2')->pluck('id')->first()
            ?? AwsRegion::onlyEc2()->pluck('id')->first();


        $users = [
          ['name' => '80Bots', 'email' => 'user@80bots.com', 'passwords' => 'q<jS\9EwtT9h(U`m'],
        ];

        foreach ($users as $user) {
            $emailParts = explode('@', $user['email']);
            $name = $user['name'];

            DB::table('users')->insert([
                'timezone_id'   => $timezone,
                'region_id'     => $region,
                'name'          => "$name",
                'email'         => "$emailParts[0]@$emailParts[1]",
                'password'      => bcrypt($user['passwords']),
                'status'        => 'active',
            ]);
        }
    }
}
