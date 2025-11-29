<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/database.php';

use Slim\Factory\AppFactory;
use App\Controllers\FlightController;
use App\Controllers\ReservationController;
use App\Middleware\AuthMiddleware;

$app = AppFactory::create();

// ================= PASO 0: HANDLER PRINCIPAL PARA OPTIONS =================
// Esto intercepta OPTIONS ANTES de que Slim procese la ruta
$app->options('/{routes:.+}', function ($request, $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withStatus(200);
});

// ================= PASO 1: MIDDLEWARE CORS (para todas las respuestas) =================
// Este middleware se ejecuta DESPUÉS de la ruta pero ANTES de enviar la respuesta
// Agrega headers CORS a cualquier respuesta (incluyendo errores)
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// ================= PASO 2: ERROR HANDLER (siempre devuelve JSON) =================
$app->addErrorMiddleware(true, true, true);

// ================= PASO 3: BODY PARSING =================
$app->addBodyParsingMiddleware(); 

// ================= PASO 4: AUTH MIDDLEWARE =================
$app->add(new AuthMiddleware());

// ================= PASO 5: RUTAS =================
$app->get('/flights', [new FlightController(), 'index']);
$app->get('/flights/search', [new FlightController(), 'search']);
$app->post('/flights', [new FlightController(), 'store']);
$app->put('/flights/{id}', [new FlightController(), 'update']);
$app->delete('/flights/{id}', [new FlightController(), 'delete']);

$app->get('/reservations', [new ReservationController(), 'index']);
$app->post('/reservations', [new ReservationController(), 'store']);
$app->put('/reservations/{id}/cancel', [new ReservationController(), 'cancel']);

$app->run();