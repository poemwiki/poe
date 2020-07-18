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
        $poems = Poem::with('content');
        var_dump(count($poems));
        die;

        foreach($poems as $poem) {
//            break;
//            if($poem->id == 1) continue;
            $hash = Poem::contentHash($poem->poem);
            Content::create([
                'entry_id' => $poem->id,
                'type' => 0,
                'content' => $poem->poem,
                'hash' => $hash,
                'new_hash' => $hash
            ]);
        }
    }
}
