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

$router->get('/', fn () => 'API Product Template');

$router->group(['prefix' => 'auth'], function () use ($router) {
    $router->post('login', [
        'uses' => 'AuthController@login',
        'middleware' => 'throttle:3,1',
    ]);

    $router->post('logout', [
        'uses' => 'AuthController@logout',
        'middleware' => 'auth',
    ]);

    $router->get('google-barcode', [
        'uses' => 'AuthController@googleBarcode',
        'middleware' => 'auth',
    ]);

    $router->post('google-barcode', [
        'uses' => 'AuthController@registerGoogleBarcode',
        'middleware' => 'auth',
    ]);

    $router->post('password/email', 'AuthController@sendResetLinkEmail');
    $router->post('password/reset', 'AuthController@resetPassword');

    $router->get('recaptcha', [
        'uses' => 'AuthController@recaptcha',
    ]);

    $router->get('2fa', [
        'uses' => 'AuthController@twoFA',
    ]);
});

$router->group(['prefix' => 'me', 'middleware' => 'auth'], function () use ($router) {
    $router->get('/', 'MeController@me');
    $router->patch('/change-password', 'MeController@changePassword');
    $router->patch('/change-profile', 'MeController@changeProfile');
    $router->patch('/change-photo', 'MeController@changePhoto');
    $router->post('/deactivate', 'MeController@deactivate');
});

$router->post('/users/registers', 'UserController@register');
$router->post('/users/activations', 'UserController@activation');
$router->get('/users/{id}', 'UserController@show');
$router->group(['prefix' => 'users', 'middleware' => 'auth'], function () use ($router) {
    $router->get('/', 'UserController@index');
    $router->post('/', 'UserController@store');
    $router->post('/{id}/approvals', 'UserController@approval');
    $router->put('/{id}', 'UserController@update');
});

$router->group(['prefix' => 'roles', 'middleware' => 'auth'], function () use ($router) {
    $router->get('/', 'RoleController@index');
    $router->get('/{id}', 'RoleController@show');
});
