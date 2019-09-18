<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->email,
        'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret,
        'activated' => $faker->boolean,
        'blocked' => $faker->boolean
    ];
});

$factory->define(App\Trip::class, function (Faker\Generator $faker) {
    return [
        'user_id' => factory(\App\User::class)->create()->id,
        'name' => $faker->name(),
    ];
});
