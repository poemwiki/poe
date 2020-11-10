<?php

namespace App\Console\Commands;

use App\Models\Score;
use Illuminate\Console\Command;

class addBedtimeScore extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:addBedtimeScore';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add bedtimepoem.com score';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $this->addBedtimeScore(3054, 28);
        return 0;
    }

    public function addBedtimeScore($maxId = 3054, $userId = 28) {
        $exceptIds = [514, 515, 516, 517, 518];
        $data = [];
        for ($id = 1; $id <= $maxId; $id++) {
            if(in_array($id, $exceptIds)) continue;
            $data[] = ['poem_id' => $id, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now(), 'score' => 4, 'weight' => 1.0];
        }
        Score::insert($data);
    }
}
