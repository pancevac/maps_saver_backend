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

/**
 * Trip
 */
$factory->define(App\Trip::class, function (Faker\Generator $faker) {
    return [
        'user_id' => factory(\App\User::class)->create()->id,
        'name' => $faker->name(),
    ];
});
$factory->afterCreating(App\Trip::class, function (\App\Trip $trip, Faker\Generator $faker) {
    factory(\App\Track::class)->create([
        'trip_id' => $trip->getKey()
    ]);
});

/**
 * Track
 */
$factory->define(\App\Track::class, function (Faker\Generator $faker) {
    return [
        'name' => 'Track ' . $faker->numberBetween(1, 100),
        'description' => $faker->sentence,
    ];
});
$factory->afterCreating(\App\Track::class, function (\App\Track $track, Faker\Generator $faker) {
    $track->points()
        ->createMany(
            factory(\App\Point::class, 5)->make()->toArray()
        );
});

/**
 * Route
 */
$factory->define(\App\Route::class, function (Faker\Generator $faker) {
    return [
        'name' => 'Route ' . $faker->numberBetween(1, 100),
        'description' => $faker->sentence,
    ];
});
$factory->afterCreating(\App\Route::class, function (\App\Route $route, Faker\Generator $faker) {
    $route->points()
        ->createMany(
            factory(\App\Point::class, 5)->make()->toArray()
        );
});

/**
 * Point
 */
$factory->define(\App\Point::class, function (Faker\Generator $faker) {
    return [
        'latitude' => $faker->latitude,
        'longitude' => $faker->longitude,
        'elevation' => $faker->randomFloat('2', '1', '60'),
        'time' => $faker->time('Y-m-d H:m:s'),
    ];
});
