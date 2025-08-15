<?php
namespace App\Controllers;

use App\Models\Usuario;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PerfilController {
    
    public function cambiarPassword(Request $request, Response $response) {
        try {
            // Verificar sesión
            if (!isset($_SESSION['user_id'])) {
                $result = [
                    'status' => 401,
                    'success' => false,
                    'message' => 'No hay sesión activa',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }
            
            $data = $request->getParsedBody();
            
            // Validaciones
            if (empty($data['password_actual']) || empty($data['password_nueva']) || empty($data['confirmar_password'])) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'Todos los campos son requeridos',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            if ($data['password_nueva'] !== $data['confirmar_password']) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'Las contraseñas nuevas no coinciden',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            if (strlen($data['password_nueva']) < 6) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'La contraseña debe tener al menos 6 caracteres',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $usuarioModel = new Usuario();
            $resultado = $usuarioModel->cambiarPassword(
                $_SESSION['user_id'], 
                $data['password_actual'], 
                $data['password_nueva']
            );
            
            if ($resultado['success']) {
                $result = [
                    'status' => 200,
                    'success' => true,
                    'message' => $resultado['message'],
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => $resultado['message'],
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
        } catch (\Exception $e) {
            $result = [
                'status' => 500,
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'data' => null
            ];
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    public function olvidoPassword(Request $request, Response $response) {
        try {
            $data = $request->getParsedBody();
            
            if (empty($data['email'])) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'El email es requerido',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'El email no tiene un formato válido',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $usuarioModel = new Usuario();
            $resultado = $usuarioModel->olvidoPassword($data['email']);
            
            if ($resultado['success']) {
                $result = [
                    'status' => 200,
                    'success' => true,
                    'message' => 'Contraseña temporal enviada. Revise su correo porfavor.',
                    'data' => [
                        'password_temporal' => $resultado['data']['password_temporal'],
                        'usuario' => $resultado['data']['usuario'],
                        'nota' => 'Guarde esta contraseña temporal para el login'
                    ]
                ];
            } else {
                $result = [
                    'status' => 404,
                    'success' => false,
                    'message' => $resultado['message'],
                    'data' => null
                ];
            }
            
            $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $result = [
                'status' => 500,
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'data' => null
            ];
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
?>