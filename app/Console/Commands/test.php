<?php

namespace App\Console\Commands;

use Illuminate\Console\Command; 
class test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return int
     */
    public function handle()
    {
        $para = "杨柳无情，丝丝化作愁千缕。惺忪如许，萦起心头绪。谁道销魂，心意无凭据。离亭外，一帆风雨，只有人归去。";
        $this->info(splitPairedLines($para));
        return 0;
    }
}
