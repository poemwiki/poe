<?php

/** @var  \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Poem::class, static function (Faker\Generator $faker) {
    return [
        'title' => $faker->sentence,
        'language' => $faker->boolean(),
        'is_original' => $faker->boolean(),
        'poet' => $faker->sentence,
        'poet_cn' => $faker->sentence,
        'bedtime_post_id' => $faker->sentence,
        'bedtime_post_title' => $faker->sentence,
        'poem' => $faker->text(),
        'length' => $faker->sentence,
        'translator' => $faker->sentence,
        'from' => $faker->sentence,
        'year' => $faker->sentence,
        'month' => $faker->sentence,
        'date' => $faker->sentence,
        'dynasty' => $faker->sentence,
        'nation' => $faker->sentence,
        'updated_at' => $faker->dateTime,
        'created_at' => $faker->dateTime,
        'need_confirm' => $faker->boolean(),
        'is_lock' => $faker->boolean(),
        'deleted_at' => null,
        'content_id' => $faker->randomNumber(5),
        
        
    ];
});
/** @var  \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Score::class, static function (Faker\Generator $faker) {
    return [
        'content_id' => $faker->sentence,
        'created_at' => $faker->dateTime,
        'factor' => $faker->randomFloat,
        'poem_id' => $faker->sentence,
        'score' => $faker->boolean(),
        'updated_at' => $faker->dateTime,
        'user_id' => $faker->sentence,
        
        
    ];
});
/** @var  \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Review::class, static function (Faker\Generator $faker) {
    return [
        'content' => $faker->text(),
        'content_id' => $faker->sentence,
        'created_at' => $faker->dateTime,
        'deleted_at' => null,
        'like' => $faker->randomNumber(5),
        'poem_id' => $faker->sentence,
        'title' => $faker->sentence,
        'updated_at' => $faker->dateTime,
        'user_id' => $faker->sentence,
        
        
    ];
});
