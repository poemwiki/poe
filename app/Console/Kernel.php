<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        if(!(config('app.env') === 'production')) DB::enableQueryLog(); // Enable query log
        $this->load(__DIR__.'/Alias');
        $this->load(__DIR__.'/Bedtime');
        $this->load(__DIR__.'/Commands');
        $this->load(__DIR__.'/Nation');
        $this->load(__DIR__.'/Wiki');
        $this->load(__DIR__.'/Poem');

        require base_path('routes/console.php');
    }
}
