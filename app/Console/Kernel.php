<?php

namespace App\Console;

use App\Console\Commands\CalculateInstancesUpTime;
use App\Console\Commands\CalculateUserCreditScore;
use App\Console\Commands\InstanceStartScheduling;
use App\Console\Commands\InstanceStopScheduling;
use App\Console\Commands\InstanceSyncScheduling;
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
        InstanceStartScheduling::class,
        InstanceStopScheduling::class,
        InstanceSyncScheduling::class,
        CalculateInstancesUpTime::class,
        CalculateUserCreditScore::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->command('instance:sync')->everyFiveMinutes();
        $schedule->command('instance:start')->everyMinute();
        $schedule->command('instance:stop')->everyMinute();
        $schedule->command('instance:calculate-up-time')->everyTenMinutes();
        $schedule->command('instance:calculate-user-credit-score')->everyTenMinutes();
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
