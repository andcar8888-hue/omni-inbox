<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1'], static function (RouteCollection $routes) {
    // Catch-all OPTIONS handler so CORS preflight requests have a route to
    // match. CI4 resolves routes before running filters, so without this the
    // router 404s on OPTIONS before the `cors` filter (Config\Filters,
    // applied to api/v1/*) ever gets a chance to short-circuit the preflight
    // with the proper Access-Control-* headers. This route's own body never
    // actually runs for a real preflight request — the filter intercepts it.
    $routes->options('(:any)', static fn () => service('response')->setStatusCode(204));

    // Public
    $routes->post('auth/login', 'AuthController::login');

    // Protected (JWT required)
    $routes->group('', ['filter' => 'jwtAuth'], static function (RouteCollection $routes) {
        $routes->get('conversations', 'ConversationsController::index');
        $routes->get('conversations/(:num)/messages', 'ConversationsController::messages/$1');
        $routes->post('conversations/(:num)/messages', 'ConversationsController::sendMessage/$1');
    });
});
