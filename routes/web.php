<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->post('login', 'Auth\LoginController@login');
$router->post('register', 'Auth\RegisterController@register');

$router->get('user', ['middleware' => 'auth', function (\Illuminate\Http\Request $request) {
    return $request->user();
}]);

$router->get('trips', [
    'as' => 'trips.index',
    'uses' => 'TripsController@index'
]);
$router->post('trips', [
    'as' => 'trips.store',
    'uses' => 'TripsController@store'
]);
$router->get('trips/{id}', [
    'as' => 'trips.show',
    'uses' => 'TripsController@show'
]);
$router->put('trips/{id}', [
    'as' => 'trips.update',
    'uses' => 'TripsController@update'
]);
$router->delete('trips/{id}', [
    'as' => 'trips.destroy',
    'uses' => 'TripsController@destroy'
]);
$router->get('trips/gpx/{id}', [
    'as' => 'trips.gpx',
    'uses' => 'TripsController@getGpx'
]);