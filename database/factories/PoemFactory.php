<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Poem;
use Faker\Generator as Faker;

$factory->define(Poem::class, function (Faker $faker) {
    return [
        'title'        => $faker->sentence(3),
        'is_original'  => $faker->numberBetween(0, 1),
        'is_owner_uploaded' => $faker->numberBetween(0, 1),
        'original_id'  => 0,
        'poet'         => $faker->name,
        'poet_cn'      => $faker->name,
        'poem'         => $faker->paragraph,
        'translator'   => $faker->name,
        'from'         => $faker->word,
        'year'         => (string)$faker->numberBetween(1900, 2024),
        'month'        => (string)$faker->numberBetween(1, 12),
        'date'         => (string)$faker->numberBetween(1, 28),
        'dynasty'      => $faker->word,
        'nation'       => $faker->countryCode,
        'need_confirm' => 0,
        'flag'         => 0,
    ];
});
