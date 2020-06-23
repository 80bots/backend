<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RefreshDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all tables, migrate, seed and install passport';

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
        $this->comment('Refreshing cache...');
        $this->call('cache:refresh');
        $this->comment('Refreshing database...');
        $this->call('migrate:fresh');
        $this->comment('Seeding database...');
        $this->call('db:seed');
        $this->comment('Installing passport...');
        $this->call('passport:install');
        $this->comment('Refreshing cache again...');
        $this->call('cache:refresh');
        $this->comment('chmod storage/logs...');
        $this->info('Completed');
    }
}
