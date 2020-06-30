<?php

namespace App\Console\Commands;

use App\AwsRegion;
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
    protected $signature = 'db:add:user {--email=} {--pass=} {--name=}';

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
        $name = $this->option('name');
        $timezone_id = Timezone::all()->pluck('id')->first();
        $region_id = AwsRegion::onlyEc2()->where('code', '=', 'us-east-2')->pluck('id')->first()
            ?? AwsRegion::onlyEc2()->pluck('id')->first();

        $emailParts = explode('@', $email);

        $this->comment('Creating user...');
        $user = new User;
        $user->region_id = $region_id;
        $user->timezone_id = $timezone_id;
        $user->name = $name;
        $user->email = "$emailParts[0]@$emailParts[1]";
        $user->password = bcrypt($pass);
        $user->status = 'active';
        $user->save();
        $this->comment('User has been successfully created');
    }
}
