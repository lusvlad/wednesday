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

use Laravel\Lumen\Routing\Router;

/** @var $router Router */
$router->group([
    'prefix' => 'api/v1'
], function () use ($router) {
    $router->post(
        'generate-code',
        [
            'uses' => 'VerificationController@sendCode',
            'middleware' => 'throttle.email:1:5:row|throttle.email:5:60:hour'
        ]
    );
    $router->post(
        'check-code',
        [
            'uses' => 'VerificationController@checkCode',
        ]
    );
});
