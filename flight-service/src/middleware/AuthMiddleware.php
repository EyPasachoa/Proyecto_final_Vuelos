<?php
namespace App\Middleware;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface {
    
    public function process(Request $request, RequestHandlerInterface $handler): Response {
        // 1. Permitir OPTIONS pasar sin token
        if ($request->getMethod() === 'OPTIONS') {
            return $handler->handle($request);
        }
        
        // 2. Validar token en otras peticiones
        $token = $request->getHeaderLine('Authorization');
        
        if (!$token) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token requerido']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        $user = User::where('token', $token)->first();
        
        if (!$user) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token inválido']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        // 3. Pasar usuario al request
        $request = $request->withAttribute('user', $user);
        return $handler->handle($request);
    }
}