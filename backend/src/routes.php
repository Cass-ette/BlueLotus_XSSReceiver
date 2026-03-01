<?php

declare(strict_types=1);

use App\Controller\AuthController;
use App\Controller\ReceiverController;
use App\Controller\RecordController;
use App\Controller\ScriptController;
use App\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    // XSS receiver (no auth) — supports all methods
    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'], '/r', [ReceiverController::class, 'receive']);

    // Auth routes
    $app->post('/api/auth/login', [AuthController::class, 'login']);

    // Protected routes
    $app->group('/api', function (RouteCollectorProxy $group) {
        // Auth management
        $group->post('/auth/logout', [AuthController::class, 'logout']);
        $group->post('/auth/password', [AuthController::class, 'changePassword']);

        // Records
        $group->get('/records', [RecordController::class, 'list']);
        $group->get('/records/{id:[0-9]+}', [RecordController::class, 'get']);
        $group->delete('/records/{id:[0-9]+}', [RecordController::class, 'delete']);
        $group->delete('/records', [RecordController::class, 'clear']);

        // Scripts
        $group->get('/scripts', [ScriptController::class, 'list']);
        $group->get('/scripts/{id:[0-9]+}', [ScriptController::class, 'get']);
        $group->post('/scripts', [ScriptController::class, 'create']);
        $group->put('/scripts/{id:[0-9]+}', [ScriptController::class, 'update']);
        $group->delete('/scripts/{id:[0-9]+}', [ScriptController::class, 'delete']);
        $group->delete('/scripts', [ScriptController::class, 'clear']);
    })->add(new AuthMiddleware($app->getContainer()));

    // Serve static JS files for XSS payloads (no auth)
    $app->get('/js/{name:.+}', [ScriptController::class, 'serveJs']);
};
