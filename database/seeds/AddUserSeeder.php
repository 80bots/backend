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
        $region     = AwsRegion::onlyEc2()->pluck('id')->first();

        $users = [
          ['name' => 'Francis', 'passwords' => ['q<jS\9EwtT9h(U`m', 'u&z!#d-RB]w]nX6@']],
          ['name' => 'Mishra', 'passwords' => ['z8Gjhp4!v@#3Wxm', 'q6.USnjSSt,::9UG']],
          ['name' => 'Kumar', 'passwords' => ['?9d8%?Pm(g3~2qS)', 'ht3!Q#u7w_?Pr9D3']],
          ['name' => 'Mike', 'passwords' => ['B8J]+Ridb#YP=F', 'xJY+d/c[ks3M']],
          ['name' => 'Sergey', 'passwords' => ['t#cW=$@%^>9XS&j<', ';Gr-{UA3XzC<WgWc']],
        ];

        foreach ($users as $user) {
            $emailName = strtolower($user['name']);
            $name = $user['name'];

            DB::table('users')->insert([
                'role_id'       => $adminRole,
                'timezone_id'   => $timezone,
                'region_id'     => $region,
                'name'          => "$name Admin",
                'email'         => "$emailName+admin@inforca.com",
                'password'      => bcrypt($user['passwords'][0]),
                'status'        => 'active',
                'credits'       => 100
            ]);

            DB::table('users')->insert([
                'role_id'       => $userRole,
                'region_id'     => $region,
                'timezone_id'   => $timezone,
                'name'          => "$name User",
                'email'         => "$emailName+user@inforca.com",
                'password'      => bcrypt($user['passwords'][1]),
                'status'        => 'active',
                'credits'       => 100
            ]);
        }
    }
}
