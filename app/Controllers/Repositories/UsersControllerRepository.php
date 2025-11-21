<?php
namespace App\Controllers\Repositories;

use App\Controllers\UsersController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UsersControllerRepository
{
    private const HTTP_STATES = [
        'default' => 500,
        404 => 404,
        400 => 400,
        'VALIDATION_ERROR' => 422,
    ];

    private UsersController $controller;

    public function __construct(UsersController $controller)
    {
        $this->controller = $controller;
    }

    public function index(Request $request, Response $response): ResponseInterface
    {
        $data = $this->controller->index();
        $status = empty($data) ? 204 : 200;
        
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public function detail(Request $request, Response $response, array $args): ResponseInterface
    {
        try {
            $user = $this->controller->detail((int)$args['id']);
            $response->getBody()->write(json_encode($user));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Exception $ex) {
            $status = self::HTTP_STATES[$ex->getCode()] ?? self::HTTP_STATES['default'];
            return $this->errorResponse($response, $status, $ex->getMessage());
        }
    }

    public function create(Request $request, Response $response): ResponseInterface
    {
        $data = $this->parseJsonBody($request);
        
        try {
            $created = $this->controller->create($data);
            $response->getBody()->write(json_encode($created));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201); // Created
        } catch (\Exception $ex) {
            $status = self::HTTP_STATES[$ex->getCode()] ?? self::HTTP_STATES['default'];
            return $this->errorResponse($response, $status, $ex->getMessage());
        }
    }

    public function update(Request $request, Response $response, array $args): ResponseInterface
    {
        $data = $this->parseJsonBody($request);
        $id = (int)$args['id'];
        
        try {
            $updated = $this->controller->update($id, $data);
            $response->getBody()->write(json_encode($updated));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Exception $ex) {
            $status = self::HTTP_STATES[$ex->getCode()] ?? self::HTTP_STATES['default'];
            return $this->errorResponse($response, $status, $ex->getMessage());
        }
    }

    public function delete(Request $request, Response $response, array $args): ResponseInterface
    {
        try {
            $this->controller->delete((int)$args['id']);
            return $response->withStatus(204); // No Content
        } catch (\Exception $ex) {
            $status = self::HTTP_STATES[$ex->getCode()] ?? self::HTTP_STATES['default'];
            return $this->errorResponse($response, $status, $ex->getMessage());
        }
    }

    private function parseJsonBody(Request $request): array
    {
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON payload', 400);
        }
        
        return $data;
    }

    private function errorResponse(Response $response, int $status, string $message): ResponseInterface
    {
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}