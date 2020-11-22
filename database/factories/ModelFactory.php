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
/** @var  \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Brackets\AdminAuth\Models\AdminUser::class, function (Faker\Generator $faker) {
    return [
        'activated' => true,
        'created_at' => $faker->dateTime,
        'deleted_at' => null,
        'email' => $faker->email,
        'first_name' => $faker->firstName,
        'forbidden' => $faker->boolean(),
        'language' => 'en',
        'last_name' => $faker->lastName,
        'password' => bcrypt($faker->password),
        'remember_token' => null,
        'updated_at' => $faker->dateTime,
        
    ];
});/** @var  \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Genre::class, static function (Faker\Generator $faker) {
    return [
        'created_at' => $faker->dateTime,
        'deleted_at' => null,
        'f_id' => $faker->sentence,
        'name' => $faker->firstName,
        'updated_at' => $faker->dateTime,
        'wikidata_id' => $faker->text(),
        
        'describe_lang' => ['en' => $faker->sentence],
        'name_lang' => ['en' => $faker->sentence],
        
    ];
});
/** @var  \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Dynasty::class, static function (Faker\Generator $faker) {
    return [
        'created_at' => $faker->dateTime,
        'deleted_at' => null,
        'f_id' => $faker->sentence,
        'name' => $faker->firstName,
        'updated_at' => $faker->dateTime,
        'wikidata_id' => $faker->text(),
        
        'describe_lang' => ['en' => $faker->sentence],
        'name_lang' => ['en' => $faker->sentence],
        
    ];
});
/** @var  \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Nation::class, static function (Faker\Generator $faker) {
    return [
        'created_at' => $faker->dateTime,
        'deleted_at' => null,
        'f_id' => $faker->sentence,
        'name' => $faker->firstName,
        'updated_at' => $faker->dateTime,
        'wikidata_id' => $faker->text(),
        
        'describe_lang' => ['en' => $faker->sentence],
        'name_lang' => ['en' => $faker->sentence],
        
    ];
});
/** @var  \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Tag::class, static function (Faker\Generator $faker) {
    return [
        'category_id' => $faker->sentence,
        'created_at' => $faker->dateTime,
        'deleted_at' => null,
        'name' => $faker->firstName,
        'updated_at' => $faker->dateTime,
        'wikidata_id' => $faker->text(),
        
        'describe_lang' => ['en' => $faker->sentence],
        'name_lang' => ['en' => $faker->sentence],
        
    ];
});
/** @var  \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Category::class, static function (Faker\Generator $faker) {
    return [
        'created_at' => $faker->dateTime,
        'deleted_at' => null,
        'name' => $faker->firstName,
        'updated_at' => $faker->dateTime,
        'wikidata_id' => $faker->text(),
        
        'describe_lang' => ['en' => $faker->sentence],
        'name_lang' => ['en' => $faker->sentence],
        
    ];
});
/** @var  \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Author::class, static function (Faker\Generator $faker) {
    return [
        'created_at' => $faker->dateTime,
        'deleted_at' => null,
        'pic_url' => $faker->sentence,
        'updated_at' => $faker->dateTime,
        'user_id' => $faker->sentence,
        'wikidata_id' => $faker->text(),
        
        'describe_lang' => ['en' => $faker->sentence],
        'name_lang' => ['en' => $faker->sentence],
        'wikipedia_url' => ['en' => $faker->sentence],
        
    ];
});
/** @var  \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Score::class, static function (Faker\Generator $faker) {
    return [
        'created_at' => $faker->dateTime,
        'deleted_at' => null,
        'poem_id' => $faker->sentence,
        'score' => $faker->boolean(),
        'updated_at' => $faker->dateTime,
        'user_id' => $faker->sentence,
        'weight' => $faker->randomFloat,
        
        
    ];
});
/** @var  \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\User::class, static function (Faker\Generator $faker) {
    return [
        'avatar' => $faker->sentence,
        'created_at' => $faker->dateTime,
        'email' => $faker->email,
        'email_verified_at' => $faker->dateTime,
        'invite_code' => $faker->sentence,
        'invite_max' => $faker->boolean(),
        'invited_by' => $faker->sentence,
        'is_active' => $faker->boolean(),
        'is_admin' => $faker->boolean(),
        'name' => $faker->firstName,
        'password' => bcrypt($faker->password),
        'remember_token' => null,
        'updated_at' => $faker->dateTime,
        
        
    ];
});
