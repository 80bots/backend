<?php

use App\Role;
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
        $adminRole  = Role::where('name', '=', 'Admin')->pluck('id')->first();
        $userRole   = Role::where('name', '=', 'User')->pluck('id')->first();
        $timezone   = Timezone::all()->pluck('id')->first();
        $region     = AwsRegion::onlyEc2()->where('code', '=', 'us-east-2')->pluck('id')->first()
            ?? AwsRegion::onlyEc2()->pluck('id')->first();


        $users = [
          ['name' => 'Francis', 'email' => 'francis@inforca.com', 'passwords' => ['q<jS\9EwtT9h(U`m', 'u&z!#d-RB]w]nX6@']],
          ['name' => 'Mishra', 'email' => 'akkimysite@gmail.com', 'passwords' => ['z8Gjhp4!v@#3Wxm', 'q6.USnjSSt,::9UG']],
          ['name' => 'Kumar', 'email' => 'kumargaf@gmail.com', 'passwords' => ['?9d8%?Pm(g3~2qS)', 'ht3!Q#u7w_?Pr9D3']],
          ['name' => 'Mike', 'email' => 'mike.mitrofanov.dev@gmail.com', 'passwords' => ['B8J]+Ridb#YP=F', 'xJY+d/c[ks3M']],
          ['name' => 'Sergey', 'email' => 's.sergeykoval@gmail.com', 'passwords' => ['t#cW=$@%^>9XS&j<', ';Gr-{UA3XzC<WgWc']],
          ['name' => 'Uxd', 'email' => 'uxd.jun@gmail.com', 'passwords' => ['{7>bVswE+53}hGXt', '&3L=DJWp"&#eVd7Q']],
        ];

        foreach ($users as $user) {
            $emailParts = explode('@', $user['email']);
            $name = $user['name'];

            DB::table('users')->insert([
                'role_id'       => $adminRole,
                'timezone_id'   => $timezone,
                'region_id'     => $region,
                'name'          => "$name Admin",
                'email'         => "$emailParts[0]+admin@$emailParts[1]",
                'password'      => bcrypt($user['passwords'][0]),
                'status'        => 'active',
                'credits'       => 100
            ]);

            DB::table('users')->insert([
                'role_id'       => $userRole,
                'region_id'     => $region,
                'timezone_id'   => $timezone,
                'name'          => "$name User",
                'email'         => "$emailParts[0]+user@$emailParts[1]",
                'password'      => bcrypt($user['passwords'][1]),
                'status'        => 'active',
                'credits'       => 100
            ]);
        }
    }
}
