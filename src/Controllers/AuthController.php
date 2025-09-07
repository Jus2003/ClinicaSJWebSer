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
    $debugInfo = [];
    $debugInfo['inicio'] = date('Y-m-d H:i:s');
    
    try {
        // 🔍 PASO 1: Verificar estado inicial de la sesión
        $debugInfo['session_status_inicial'] = session_status();
        $debugInfo['session_id_inicial'] = session_id();
        $debugInfo['session_data_inicial'] = $_SESSION ?? [];
        
        // 🔍 PASO 2: Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            $debugInfo['session_iniciada'] = true;
        } else {
            $debugInfo['session_ya_activa'] = true;
        }
        
        $debugInfo['session_id_despues_start'] = session_id();
        $debugInfo['session_data_antes_limpiar'] = $_SESSION ?? [];
        
        // 🔍 PASO 3: Limpiar variables de sesión
        $sessionDataBefore = $_SESSION ?? [];
        $_SESSION = [];
        $debugInfo['session_limpiada'] = true;
        $debugInfo['datos_eliminados'] = $sessionDataBefore;
        
        // 🔍 PASO 4: Destruir cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            $cookieDestroyed = setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
            $debugInfo['cookie_destruida'] = $cookieDestroyed;
            $debugInfo['cookie_params'] = $params;
        }
        
        // 🔍 PASO 5: Destruir sesión
        $sessionDestroyResult = session_destroy();
        $debugInfo['session_destroy_result'] = $sessionDestroyResult;
        
        // 🔍 PASO 6: Verificar que todo se limpió
        $debugInfo['session_status_final'] = session_status();
        
        // 🔍 PASO 7: Iniciar nueva sesión para verificar limpieza
        session_start();
        $debugInfo['nueva_session_id'] = session_id();
        $debugInfo['nueva_session_data'] = $_SESSION ?? [];
        $debugInfo['session_completamente_limpia'] = empty($_SESSION);
        
        $result = [
            'status' => 200,
            'success' => true,
            'message' => '✅ SESIÓN CERRADA EXITOSAMENTE ✅',
            'data' => [
                'logout_exitoso' => true,
                'timestamp' => date('Y-m-d H:i:s'),
                'debug_info' => $debugInfo
            ]
        ];
        
        // 📝 GUARDAR DEBUG EN LOG DEL SERVIDOR
        error_log("=== LOGOUT DEBUG ===");
        error_log("Timestamp: " . date('Y-m-d H:i:s'));
        error_log("Debug info: " . json_encode($debugInfo, JSON_PRETTY_PRINT));
        error_log("===================");
        
        $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        
    } catch (\Exception $e) {
        $debugInfo['error'] = $e->getMessage();
        $debugInfo['error_line'] = $e->getLine();
        $debugInfo['error_file'] = $e->getFile();
        
        // 📝 GUARDAR ERROR EN LOG
        error_log("=== LOGOUT ERROR ===");
        error_log("Error: " . $e->getMessage());
        error_log("Debug info: " . json_encode($debugInfo, JSON_PRETTY_PRINT));
        error_log("===================");
        
        $result = [
            'status' => 500,
            'success' => false,
            'message' => '❌ Error al cerrar sesión: ' . $e->getMessage(),
            'data' => [
                'debug_info' => $debugInfo
            ]
        ];
        $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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