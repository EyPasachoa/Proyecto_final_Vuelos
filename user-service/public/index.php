<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/database.php';

use Slim\Factory\AppFactory;
use App\Controllers\AuthController;

$app = AppFactory::create();

// ================= CRÍTICO: Middleware BODY PARSING =================
$app->addBodyParsingMiddleware(); // <-- ESTO PARSEA EL JSON

// ================= Middleware CORS =================
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

// ================= Rutas =================
$app->get('/', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'status' => 'Microservicio de Usuarios activo',
        'endpoints' => ['POST /login', 'POST /register', 'POST /logout', 'GET /users']
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/login', [new AuthController(), 'login']);
$app->post('/register', [new AuthController(), 'register']);
$app->post('/logout', [new AuthController(), 'logout']);
$app->get('/users', [new AuthController(), 'index']);

$app->run();