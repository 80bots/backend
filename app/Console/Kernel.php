<?php

namespace App\Console;

use App\Console\Commands\AddUser;
use App\Console\Commands\AwsSyncAmis;
use App\Console\Commands\CacheRefresh;
use App\Console\Commands\CalculateInstancesUpTime;
use App\Console\Commands\CleanUpUnused;
use App\Console\Commands\EchoServerInit;
use App\Console\Commands\InstanceStartScheduling;
use App\Console\Commands\InstanceStopScheduling;
use App\Console\Commands\InstanceSyncScheduling;
use App\Console\Commands\RefreshDatabase;
use App\Console\Commands\SyncDataFolders;
use App\Console\Commands\SyncLocalBots;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CacheRefresh::class,
        InstanceStartScheduling::class,
        InstanceStopScheduling::class,
        InstanceSyncScheduling::class,
        CalculateInstancesUpTime::class,
        CleanUpUnused::class,
        RefreshDatabase::class,
        AwsSyncAmis::class,
        SyncLocalBots::class,
        AddUser::class,
        SyncDataFolders::class,
        EchoServerInit::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->command('instance:sync')->everyFiveMinutes();
        $schedule->command('instance:start')->everyMinute();
        $schedule->command('instance:stop')->everyMinute();
        $schedule->command('instance:calculate-up-time')->everyTenMinutes();
        $schedule->command('instance:clean')->hourly();
        $schedule->command('aws:sync-amis')->everyThirtyMinutes();
        $schedule->command('bots:sync-local')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
