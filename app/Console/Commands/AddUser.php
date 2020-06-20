<?php

namespace App\Console\Commands;

use App\AwsRegion;
use App\Role;
use App\Timezone;
use App\User;
use Illuminate\Console\Command;

class AddUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:add:user {--email=} {--pass=} {--role=} {--name=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add user using basic props';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Collecting default data...');
        $email = $this->option('email');
        $pass = $this->option('pass');
        $role = $this->option('role');
        $name = $this->option('name');
        $role_id = Role::where('name', '=', $role)->pluck('id')->first();
        $timezone_id = Timezone::all()->pluck('id')->first();
        $region_id = AwsRegion::onlyEc2()->where('code', '=', 'us-east-2')->pluck('id')->first()
            ?? AwsRegion::onlyEc2()->pluck('id')->first();

        $emailParts = explode('@', $email);
        $suffix = strtolower($role);

        $this->comment('Creating user...');
        $user = new User;
        $user->role_id = $role_id;
        $user->region_id = $region_id;
        $user->timezone_id = $timezone_id;
        $user->name = $name;
        $user->email = "$emailParts[0]+$suffix@$emailParts[1]";
        $user->password = bcrypt($pass);
        $user->status = 'active';
        $user->save();
        $this->comment('User has been successfully created');
    }
}
