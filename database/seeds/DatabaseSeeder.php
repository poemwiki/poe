<?php

use App\Models\Content;
use App\Models\Poem;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run() {
        $poems = Poem::all();

        foreach($poems as $poem) {
//            if($poem->id == 1) continue;
            $hash = Poem::contentHash($poem->poem);
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
}
