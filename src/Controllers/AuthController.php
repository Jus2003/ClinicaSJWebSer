<?php
namespace App\Controllers;

use App\Models\Usuario;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController {
    
    public function login(Request $request, Response $response) {
        try {
            $data = $request->getParsedBody();
            
            // Validaciones básicas
            if (empty($data['usuario']) || empty($data['password'])) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'Usuario y contraseña son requeridos',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $usuarioModel = new Usuario();
            $user = $usuarioModel->login($data['usuario'], $data['password']);
            
            if ($user) {
                // Crear sesión
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['id_rol'];
                $_SESSION['user_name'] = $user['nombre'] . ' ' . $user['apellido'];
                
                // Obtener menús del usuario
                $menus = $usuarioModel->getMenusByRole($user['id_rol']);
                
                $result = [
                    'status' => 200,
                    'success' => true,
                    'message' => 'Login exitoso',
                    'data' => [
                        'usuario' => [
                            'id' => $user['id_usuario'],
                            'nombre' => $user['nombre'],
                            'apellido' => $user['apellido'],
                            'email' => $user['email'],
                            'cedula' => $user['cedula'],
                            'telefono' => $user['telefono'],
                            'rol' => $user['nombre_rol'],
                            'sucursal' => $user['nombre_sucursal']
                        ],
                        'menus' => $menus,
                        'session_id' => session_id()
                    ]
                ];
                
                $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
                
            } else {
                $result = [
                    'status' => 401,
                    'success' => false,
                    'message' => 'Credenciales inválidas',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
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
    
    public function logout(Request $request, Response $response) {
        try {
            session_destroy();
            
            $result = [
                'status' => 200,
                'success' => true,
                'message' => 'Logout exitoso',
                'data' => null
            ];
            
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            
        } catch (\Exception $e) {
            $result = [
                'status' => 500,
                'success' => false,
                'message' => 'Error al cerrar sesión: ' . $e->getMessage(),
                'data' => null
            ];
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    public function perfil(Request $request, Response $response) {
        try {
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
            
            $result = [
                'status' => 200,
                'success' => true,
                'message' => 'Perfil obtenido correctamente',
                'data' => [
                    'user_id' => $_SESSION['user_id'],
                    'user_name' => $_SESSION['user_name'],
                    'user_email' => $_SESSION['user_email'],
                    'user_role' => $_SESSION['user_role'],
                    'session_id' => session_id()
                ]
            ];
            
            $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            
        } catch (\Exception $e) {
            $result = [
                'status' => 500,
                'success' => false,
                'message' => 'Error al obtener perfil: ' . $e->getMessage(),
                'data' => null
            ];
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
?>