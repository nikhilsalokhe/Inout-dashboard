<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Process daily attendance at 11:59 PM every day
        // Marks absent employees, resolves incomplete check-outs,
        // handles weekly offs based on shift configuration
        $schedule->command('attendance:process-daily')
                 ->dailyAt('23:59')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/attendance-processor.log'));
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
