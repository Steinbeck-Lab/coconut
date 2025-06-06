<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('coconut:entries-process')->everyMinute()->withoutOverlapping();
        $schedule->command('coconut:entries-import')->everyMinute()->withoutOverlapping();
        $schedule->command('coconut:cache')->everyFifteenMinutes()->withoutOverlapping();

        // Production auto-processing commands
        if (app()->environment('production')) {
            $schedule->command('coconut:validate-data')->everyFiveMinutes()->withoutOverlapping();
            $schedule->command('coconut:entries-import-references')->everyTenMinutes()->withoutOverlapping();
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
