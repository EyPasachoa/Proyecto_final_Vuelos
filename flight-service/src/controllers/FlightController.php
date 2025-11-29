<?php
namespace App\Controllers;
use App\Models\Flight;
use App\Models\Nave;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FlightController {
    
    public function index(Request $request, Response $response) {
        $user = $request->getAttribute('user');
        $flights = Flight::with('nave')->get();
        return $this->jsonResponse($response, ['data' => $flights]);
    }
    
    public function search(Request $request, Response $response) {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();
        $query = Flight::with('nave');
        
        if (!empty($params['origin'])) {
            $query->where('origin', 'like', '%' . $params['origin'] . '%');
        }
        if (!empty($params['destination'])) {
            $query->where('destination', 'like', '%' . $params['destination'] . '%');
        }
        if (!empty($params['date'])) {
            $query->whereDate('departure', $params['date']);
        }
        
        return $this->jsonResponse($response, ['data' => $query->get()]);
    }
    
    public function store(Request $request, Response $response) {
        $user = $request->getAttribute('user');
        if ($user->role !== 'administrador') {
            return $this->jsonResponse($response, ['error' => 'No autorizado'], 403);
        }
        
        $flight = Flight::create($request->getParsedBody());
        return $this->jsonResponse($response, ['message' => 'Vuelo creado', 'data' => $flight], 201);
    }
    
    public function update(Request $request, Response $response, $args) {
        $user = $request->getAttribute('user');
        if ($user->role !== 'administrador') {
            return $this->jsonResponse($response, ['error' => 'No autorizado'], 403);
        }
        
        $flight = Flight::find($args['id']);
        if (!$flight) {
            return $this->jsonResponse($response, ['error' => 'Vuelo no encontrado'], 404);
        }
        
        $flight->update($request->getParsedBody());
        return $this->jsonResponse($response, ['message' => 'Vuelo actualizado']);
    }
    
    public function delete(Request $request, Response $response, $args) {
        $user = $request->getAttribute('user');
        if ($user->role !== 'administrador') {
            return $this->jsonResponse($response, ['error' => 'No autorizado'], 403);
        }
        
        Flight::destroy($args['id']);
        return $this->jsonResponse($response, ['message' => 'Vuelo eliminado']);
    }
    
    private function jsonResponse($response, $data, $status = 200) {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}