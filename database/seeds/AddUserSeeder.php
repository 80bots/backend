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
          ['name' => 'Francis', 'email' => 'francis@inforca.com', 'passwords' => 'q<jS\9EwtT9h(U`m'],
          ['name' => 'Mishra', 'email' => 'akkimysite@gmail.com', 'passwords' => 'z8Gjhp4!v@#3Wxm'],
          ['name' => 'Kumar', 'email' => 'kumargaf@gmail.com', 'passwords' => '?9d8%?Pm(g3~2qS)'],
          ['name' => 'Mike', 'email' => 'mike.mitrofanov.dev@gmail.com', 'passwords' => 'B8J]+Ridb#YP=F'],
          ['name' => 'Sergey', 'email' => 's.sergeykoval@gmail.com', 'passwords' => 't#cW=$@%^>9XS&j<'],
          ['name' => 'Uxd', 'email' => 'uxd.jun@gmail.com', 'passwords' => '{7>bVswE+53}hGXt'],
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
