<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Score::class, static function (Faker\Generator $faker) {
    return [
        'content_id' => $faker->sentence,
        'created_at' => $faker->dateTime,
        'factor'     => $faker->randomFloat,
        'poem_id'    => $faker->sentence,
        'score'      => $faker->boolean(),
        'updated_at' => $faker->dateTime,
        'user_id'    => $faker->sentence,


    ];
});
/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Review::class, static function (Faker\Generator $faker) {
    return [
        'content'    => $faker->text(),
        'content_id' => $faker->sentence,
        'created_at' => $faker->dateTime,
        'deleted_at' => null,
        'like'       => $faker->randomNumber(5),
        'poem_id'    => $faker->sentence,
        'title'      => $faker->sentence,
        'updated_at' => $faker->dateTime,
        'user_id'    => $faker->sentence,


    ];
});
/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Brackets\AdminAuth\Models\AdminUser::class, function (Faker\Generator $faker) {
    return [
        'activated'      => true,
        'created_at'     => $faker->dateTime,
        'deleted_at'     => null,
        'email'          => $faker->email,
        'first_name'     => $faker->firstName,
        'forbidden'      => $faker->boolean(),
        'language'       => 'en',
        'last_name'      => $faker->lastName,
        'password'       => bcrypt($faker->password),
        'remember_token' => null,
        'updated_at'     => $faker->dateTime,

    ];
});/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Genre::class, static function (Faker\Generator $faker) {
    return [
        'created_at'  => $faker->dateTime,
        'deleted_at'  => null,
        'f_id'        => $faker->sentence,
        'name'        => $faker->firstName,
        'updated_at'  => $faker->dateTime,
        'wikidata_id' => $faker->text(),

        'describe_lang' => ['en' => $faker->sentence],
        'name_lang'     => ['en' => $faker->sentence],

    ];
});
/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Dynasty::class, static function (Faker\Generator $faker) {
    return [
        'created_at'  => $faker->dateTime,
        'deleted_at'  => null,
        'f_id'        => $faker->sentence,
        'name'        => $faker->firstName,
        'updated_at'  => $faker->dateTime,
        'wikidata_id' => $faker->text(),

        'describe_lang' => ['en' => $faker->sentence],
        'name_lang'     => ['en' => $faker->sentence],

    ];
});
/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Nation::class, static function (Faker\Generator $faker) {
    return [
        'created_at'  => $faker->dateTime,
        'deleted_at'  => null,
        'f_id'        => $faker->sentence,
        'name'        => $faker->firstName,
        'updated_at'  => $faker->dateTime,
        'wikidata_id' => $faker->text(),

        'describe_lang' => ['en' => $faker->sentence],
        'name_lang'     => ['en' => $faker->sentence],

    ];
});
/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Tag::class, static function (Faker\Generator $faker) {
    return [
        'category_id' => $faker->sentence,
        'created_at'  => $faker->dateTime,
        'deleted_at'  => null,
        'name'        => $faker->firstName,
        'updated_at'  => $faker->dateTime,
        'wikidata_id' => $faker->text(),

        'describe_lang' => ['en' => $faker->sentence],
        'name_lang'     => ['en' => $faker->sentence],

    ];
});
/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Category::class, static function (Faker\Generator $faker) {
    return [
        'created_at'  => $faker->dateTime,
        'deleted_at'  => null,
        'name'        => $faker->firstName,
        'updated_at'  => $faker->dateTime,
        'wikidata_id' => $faker->text(),

        'describe_lang' => ['en' => $faker->sentence],
        'name_lang'     => ['en' => $faker->sentence],

    ];
});
/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Author::class, static function (Faker\Generator $faker) {
    return [
        'created_at'  => $faker->dateTime,
        'deleted_at'  => null,
        'pic_url'     => $faker->sentence,
        'updated_at'  => $faker->dateTime,
        'user_id'     => $faker->sentence,
        'wikidata_id' => $faker->text(),

        'describe_lang' => ['en' => $faker->sentence],
        'name_lang'     => ['en' => $faker->sentence],
        'wikipedia_url' => ['en' => $faker->sentence],

    ];
});
/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Score::class, static function (Faker\Generator $faker) {
    return [
        'created_at' => $faker->dateTime,
        'deleted_at' => null,
        'poem_id'    => $faker->sentence,
        'score'      => $faker->boolean(),
        'updated_at' => $faker->dateTime,
        'user_id'    => $faker->sentence,
        'weight'     => $faker->randomFloat,


    ];
});