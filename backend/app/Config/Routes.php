<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1'], static function (RouteCollection $routes) {
    // Public
    $routes->post('auth/login', 'AuthController::login');

    // Protected (JWT required)
    $routes->group('', ['filter' => 'jwtAuth'], static function (RouteCollection $routes) {
        $routes->get('conversations', 'ConversationsController::index');
        $routes->get('conversations/(:num)/messages', 'ConversationsController::messages/$1');
        $routes->post('conversations/(:num)/messages', 'ConversationsController::sendMessage/$1');
    });
});
