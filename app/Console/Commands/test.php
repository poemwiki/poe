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
        $para = "铁板铜琵歌曲子。曲曲新声，删尽凄凉意。飘泊东南羁旅际。灵襟更写湖光翠。海上看羊寻远计。剩水残山，总是伤心地。谁把唾壶今击碎。仰天长啸云飞起。";

        $this->info(textTypo($para));
        return 0;
    }
}
