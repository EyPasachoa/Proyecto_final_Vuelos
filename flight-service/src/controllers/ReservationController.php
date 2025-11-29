<?php
namespace App\Controllers;
use App\Models\Reservation;
use App\Models\Flight;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReservationController {
    
    public function index(Request $request, Response $response) {
        $user = $request->getAttribute('user');
        $query = Reservation::with('flight.nave');
        
        // Si es gestor, solo ver sus reservas
        if ($user->role === 'gestor') {
            $query->where('user_id', $user->id);
        }
        
        return $this->jsonResponse($response, ['data' => $query->get()]);
    }
    
    public function store(Request $request, Response $response) {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();
        
        // Validar que el vuelo existe
        $flight = Flight::find($data['flight_id']);
        if (!$flight) {
            return $this->jsonResponse($response, ['error' => 'Vuelo no existe'], 400);
        }
        
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'flight_id' => $data['flight_id']
        ]);
        
        return $this->jsonResponse($response, ['message' => 'Reserva creada', 'data' => $reservation], 201);
    }
    
    public function cancel(Request $request, Response $response, $args) {
        $user = $request->getAttribute('user');
        $reservation = Reservation::find($args['id']);
        
        if (!$reservation) {
            return $this->jsonResponse($response, ['error' => 'Reserva no encontrada'], 404);
        }
        
        // Solo el dueño o admin pueden cancelar
        if ($user->role !== 'administrador' && $reservation->user_id !== $user->id) {
            return $this->jsonResponse($response, ['error' => 'No autorizado'], 403);
        }
        
        $reservation->status = 'cancelada';
        $reservation->save();
        
        return $this->jsonResponse($response, ['message' => 'Reserva cancelada']);
    }
    
    private function jsonResponse($response, $data, $status = 200) {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}