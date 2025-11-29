<?php
namespace App\Controllers;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController {
    
    public function login(Request $request, Response $response) {
        try {
            $data = $request->getParsedBody();
            
            // Validar campos
            if (empty($data['email']) || empty($data['password'])) {
                return $this->jsonResponse($response, ['error' => 'Email y contraseña requeridos'], 400);
            }
            
            // Buscar usuario
            $user = User::where('email', $data['email'])->first();
            if (!$user) {
                return $this->jsonResponse($response, ['error' => 'Credenciales inválidas'], 401);
            }
            
            // ⚠️ VERIFICAR CONTRASEÑA (modo texto plano)
            if (!$this->verificarPassword($data['password'], $user->password)) {
                return $this->jsonResponse($response, ['error' => 'Credenciales inválidas'], 401);
            }
            
            // Generar token
            $token = bin2hex(random_bytes(32));
            $user->token = $token;
            $user->save();
            
            return $this->jsonResponse($response, [
                'message' => 'Login exitoso',
                'token' => $token,
                'role' => $user->role
            ], 200);
            
        } catch (\Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return $this->jsonResponse($response, [
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function register(Request $request, Response $response) {
        try {
            $data = $request->getParsedBody();
            
            // Validar admin
            $token = $request->getHeaderLine('Authorization');
            $userAuth = User::where('token', $token)->first();
            
            if (!$userAuth || $userAuth->role !== 'administrador') {
                return $this->jsonResponse($response, ['error' => 'No autorizado'], 403);
            }
            
            // ⚠️ Guardar contraseña en TEXTO PLANO (como tu SQL)
            $newUser = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // Texto plano directamente
                'role' => $data['role'] ?? 'gestor'
            ]);
            
            return $this->jsonResponse($response, [
                'message' => 'Usuario creado',
                'user' => $newUser
            ], 201);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'error' => 'Error al crear usuario',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function logout(Request $request, Response $response) {
        try {
            $token = $request->getHeaderLine('Authorization');
            $user = User::where('token', $token)->first();
            
            if ($user) {
                $user->token = null;
                $user->save();
            }
            
            return $this->jsonResponse($response, ['message' => 'Logout exitoso']);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'error' => 'Error al cerrar sesión',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function index(Request $request, Response $response) {
        try {
            $token = $request->getHeaderLine('Authorization');
            $user = User::where('token', $token)->first();
            
            if (!$user || $user->role !== 'administrador') {
                return $this->jsonResponse($response, ['error' => 'No autorizado'], 403);
            }
            
            $users = User::all();
            return $this->jsonResponse($response, ['data' => $users]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'error' => 'Error al listar usuarios',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verificar contraseña (soporte para texto plano y hash)
     */
    private function verificarPassword($passwordInput, $passwordBD) {
        // Si la contraseña en BD está hasheada (empieza con $2y$)
        if (strpos($passwordBD, '$2y$') === 0) {
            return password_verify($passwordInput, $passwordBD);
        }
        
        // Si está en texto plano (TU CASO)
        return $passwordInput === $passwordBD;
    }
    
    private function jsonResponse($response, $data, $status = 200) {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}