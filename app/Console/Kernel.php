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
        date_default_timezone_set('Asia/Jakarta');
        $schedule->command('spp:cron')->monthlyOn(1, '08:00')->timezone('Asia/Jakarta');
        $schedule->command('chargePastDueSpp:cron')->monthlyOn(11, '13:10')->timezone('Asia/Jakarta');
        $schedule->command('reminderPastDueMinusOneDays:cron')->monthlyOn(11, '15:00')->timezone('Asia/Jakarta');
        // $schedule->command('reminderPastDueMinusSevenDays:cron')->dailyAt('10:40')->timezone('Asia/Jakarta');
        // $schedule->command('reminderFeeRegister:cron')->dailyAt('16:00')->timezone('Asia/Jakarta');
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