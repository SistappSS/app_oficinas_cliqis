<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('normalize:customer')->everyTenMinutes();
        $schedule->command('register:monthly-overdue')->monthlyOn(1, 0);
        $schedule->command('register:yearly-overdue')->yearlyOn(1, 0);
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
