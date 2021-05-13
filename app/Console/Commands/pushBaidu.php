<?php

namespace App\Console\Commands;

use App\Models\Poem;
use Illuminate\Console\Command;

class pushBaidu extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:baidu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'push url to baidu';

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
        $poems = Poem::where('id', '>', 2407)->limit(2000)->get();
        $urls = [];

        foreach ($poems as $poem) {
            $urls[] = 'https://poemwiki.org/p/' . $poem->fakeId;
            echo $poem->id;
        }

        $api = 'http://data.zz.baidu.com/urls?site=poemwiki.org&token=XtvfJ5MucTNTgTxe';
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $api,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => implode("\n", $urls),
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        echo $result;

        return 0;
    }
}
