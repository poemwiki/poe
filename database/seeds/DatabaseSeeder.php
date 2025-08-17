<?php

use App\Models\Content;
use App\Models\Poem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function runHash() {
        $poems = Poem::all();

        foreach($poems as $poem) {
//            if($poem->id == 1) continue;
            $hash = Str::contentHash($poem->poem);
            $content = Content::create([
                'entry_id' => $poem->id,
                'type' => 0,
                'content' => $poem->poem,
                'hash' => $hash,
                'new_hash' => $hash
            ]);

            $poem->content_id = $content->id;
            $poem->save();
//            break;
        }
    }
    public function run() {
        $contents = Content::all();

        foreach($contents as $p) {
            $hash = Str::contentFullHash($p->content);
            $p->fullHash = $hash;
            $p->save();
        }
    }
}
