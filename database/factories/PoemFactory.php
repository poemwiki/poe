<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Poem;
use Faker\Generator as Faker;

$factory->define(Poem::class, function (Faker $faker) {

    return [
        'title' => $faker->word,
        'language' => $faker->word,
        'is_original' => $faker->word,
        'poet' => $faker->word,
        'poet_cn' => $faker->word,
        'bedtime_post_id' => $faker->word,
        'bedtime_post_title' => $faker->word,
        'poem' => $faker->text,
        'length' => $faker->word,
        'translator' => $faker->word,
        'from' => $faker->word,
        'year' => $faker->word,
        'month' => $faker->word,
        'date' => $faker->word,
        'dynasty' => $faker->word,
        'nation' => $faker->word,
        'updated_at' => $faker->date('Y-m-d H:i:s'),
        'created_at' => $faker->date('Y-m-d H:i:s'),
        'need_confirm' => $faker->word,
        'is_lock' => $faker->word
    ];
});
