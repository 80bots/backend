<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class EchoServerInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'echo-server:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup a laravel-echo-server.json file from .env ';

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
        try {
            $this->comment('Creating laravel-echo-server-template.json file...');
            $jsonCfg = config('echo-server');
            $jsonCfg = json_encode($jsonCfg, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            file_put_contents(base_path('laravel-echo-server.json'), $jsonCfg);
            $this->info('Completed');
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
        }
    }
}
