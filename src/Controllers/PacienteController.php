<?php
namespace App\Controllers;

use App\Models\Paciente;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PacienteController {
    private $paciente;

    public function __construct() {
        $this->paciente = new Paciente();
    }
    
    public function buscarPorCedula(Request $request, Response $response, $args) {
        try {
            $cedula = $args['cedula'] ?? '';
            
            if (empty($cedula)) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'Cédula es requerida',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $pacienteModel = new Paciente();
            $resultado = $pacienteModel->buscarPorCedula($cedula);
            
            if ($resultado['success']) {
                $result = [
                    'status' => 200,
                    'success' => true,
                    'message' => $resultado['message'],
                    'data' => $resultado['data']
                ];
                $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $result = [
                    'status' => 404,
                    'success' => false,
                    'message' => $resultado['message'],
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
        } catch (\Exception $e) {
            $result = [
                'status' => 500,
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    public function obtenerHistorialCompleto(Request $request, Response $response, $args) {
        try {
            $idPaciente = $args['id_paciente'] ?? '';
            
            if (empty($idPaciente) || !is_numeric($idPaciente)) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'ID de paciente inválido',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $pacienteModel = new Paciente();
            $resultado = $pacienteModel->obtenerHistorialCompleto($idPaciente);
            
            if ($resultado['success']) {
                $result = [
                    'status' => 200,
                    'success' => true,
                    'message' => $resultado['message'],
                    'data' => $resultado['data']
                ];
                $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $result = [
                    'status' => 404,
                    'success' => false,
                    'message' => $resultado['message'],
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
        } catch (\Exception $e) {
            $result = [
                'status' => 500,
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function obtenerHistorialPorCedula(Request $request, Response $response, $args) {
        try {
            $cedula = $args['cedula'] ?? '';
            
            if (empty($cedula)) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'Cédula es requerida',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $pacienteModel = new Paciente();
            
            // Primero buscar el paciente por cédula
            $busqueda = $pacienteModel->buscarPorCedula($cedula);
            
            if (!$busqueda['success']) {
                $result = [
                    'status' => 404,
                    'success' => false,
                    'message' => $busqueda['message'],
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
            // Obtener el historial completo
            $idPaciente = $busqueda['data']['id_paciente'];
            $historial = $pacienteModel->obtenerHistorialCompleto($idPaciente);
            
            if ($historial['success']) {
                $result = [
                    'status' => 200,
                    'success' => true,
                    'message' => $historial['message'],
                    'data' => $historial['data']
                ];
                $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $result = [
                    'status' => 500,
                    'success' => false,
                    'message' => $historial['message'],
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }
            
        } catch (\Exception $e) {
            $result = [
                'status' => 500,
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

     public function buscarHistorialPorCedulaJSON(Request $request, Response $response) {
        try {
            $data = $request->getParsedBody();
            
            // Validar que se proporcione la cédula
            if (empty($data['cedula'])) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'La cédula es requerida',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $cedula = trim($data['cedula']);
            
            // Validar formato de cédula (opcional)
            if (!is_numeric($cedula) || strlen($cedula) < 10) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'Formato de cédula inválido',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $pacienteModel = new Paciente();
            
            // Primero buscar el paciente por cédula
            $busqueda = $pacienteModel->buscarPorCedula($cedula);
            
            if (!$busqueda['success']) {
                $result = [
                    'status' => 404,
                    'success' => false,
                    'message' => $busqueda['message'],
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
            // Obtener el historial completo
            $idPaciente = $busqueda['data']['id_paciente'];
            $historial = $pacienteModel->obtenerHistorialCompleto($idPaciente);
            
            if ($historial['success']) {
                $result = [
                    'status' => 200,
                    'success' => true,
                    'message' => 'Historial clínico obtenido correctamente',
                    'data' => [
                        'cedula_consultada' => $cedula,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'paciente' => $historial['data']['paciente'],
                        'estadisticas' => $historial['data']['estadisticas'],
                        'historial_completo' => $historial['data']['historial_completo']
                    ]
                ];
                $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $result = [
                    'status' => 500,
                    'success' => false,
                    'message' => $historial['message'],
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }
            
        } catch (\Exception $e) {
            $result = [
                'status' => 500,
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function obtenerHistorialPorIdJSON(Request $request, Response $response) {
        try {
            $data = $request->getParsedBody();
            
            // Validar que se proporcione el ID del paciente
            if (empty($data['id_paciente'])) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'El ID del paciente es requerido',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $idPaciente = $data['id_paciente'];
            
            // Validar que sea un número válido
            if (!is_numeric($idPaciente) || $idPaciente <= 0) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'ID de paciente inválido',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $pacienteModel = new Paciente();
            $resultado = $pacienteModel->obtenerHistorialCompleto($idPaciente);
            
            if ($resultado['success']) {
                $result = [
                    'status' => 200,
                    'success' => true,
                    'message' => 'Historial clínico obtenido correctamente',
                    'data' => [
                        'id_paciente_consultado' => $idPaciente,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'paciente' => $resultado['data']['paciente'],
                        'estadisticas' => $resultado['data']['estadisticas'],
                        'historial_completo' => $resultado['data']['historial_completo']
                    ]
                ];
                $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $result = [
                    'status' => 404,
                    'success' => false,
                    'message' => $resultado['message'],
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
        } catch (\Exception $e) {
            $result = [
                'status' => 500,
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

   public function listarPacientesHistorialPorRol(Request $request, Response $response, $args) {
        try {
            $rolUsuario = (int)($args['rol'] ?? 1); // Por defecto Admin
            $idUsuarioLogueado = 1; // Por ahora fijo, después de sesión
            
            // Si pasas un ID específico para paciente, úsalo
            if ($rolUsuario == 4) {
                // Para pruebas con paciente, usar ID específico
                $idUsuarioLogueado = 13; // ID de Valeria para pruebas
            }
            
            $pacienteModel = new Paciente();
            $resultado = $pacienteModel->listarPacientesParaHistorial($idUsuarioLogueado, $rolUsuario);
            
            if ($resultado['success']) {
                $result = [
                    'status' => 200,
                    'success' => true,
                    'message' => $resultado['message'],
                    'data' => $resultado['data']
                ];
                $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $result = [
                    'status' => 500,
                    'success' => false,
                    'message' => $resultado['message'],
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }
            
        } catch (\Exception $e) {
            $result = [
                'status' => 500,
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function listarTodos($request, $response) {
        $pacienteModel = new Paciente();

        // ❌ Eliminamos validación de rol para pruebas
        // $userRole = $_SESSION['rol'] ?? '';
        // if (!in_array($userRole, ['Administrador', 'Médico', 'Recepcionista'])) {
        //     return $response->withJson([
        //         'success' => false,
        //         'message' => 'No tienes permisos para ver la lista de pacientes'
        //     ], 403);
        // }

        // ✅ Ahora siempre devuelve la lista
        $resultado = $pacienteModel->listarTodos();
        return $response->withJson($resultado);
    }


    public function crearPaciente(Request $request, Response $response) {
    try {
        $data = $request->getParsedBody();

        if (empty($data)) {
            $response->getBody()->write(json_encode([
                'status' => 400,
                'success' => false,
                'message' => 'No se recibieron datos'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Campos requeridos
        $campos_requeridos = ['username', 'email', 'cedula', 'nombre', 'apellido'];
        foreach ($campos_requeridos as $campo) {
            if (empty($data[$campo])) {
                $response->getBody()->write(json_encode([
                    'status' => 400,
                    'success' => false,
                    'message' => "El campo {$campo} es requerido"
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        }

        // Generar contraseña simple
        $password = 'temp' . rand(1000, 9999);
        
        // Crear conexión directa
        $database = new \App\Config\Database();
        $db = $database->getConnection();
        
        $sql = "INSERT INTO usuarios (username, email, password, cedula, nombre, apellido, id_rol, activo, requiere_cambio_contrasena, clave_temporal) VALUES (?, ?, ?, ?, ?, ?, 4, 1, 1, ?)";
        $stmt = $db->prepare($sql);
        $resultado = $stmt->execute([
            $data['username'],
            $data['email'],
            base64_encode($password),
            $data['cedula'],
            $data['nombre'],
            $data['apellido'],
            $password
        ]);

        if ($resultado) {
    $emailService = new \App\Services\EmailService();
    $emailEnviado = false;

    try {
        $resEmail = $emailService->enviarPasswordTemporal(
            $data['email'],
            $data['nombre'] . ' ' . $data['apellido'],
            $data['username'],
            $password
        );
        $emailEnviado = $resEmail['success'];
    } catch (\Exception $e) {
        error_log("Error enviando email a {$data['email']}: " . $e->getMessage());
    }

    $response->getBody()->write(json_encode([
        'status' => 201,
        'success' => true,
        'message' => 'Paciente creado exitosamente',
        'data' => [
            'id_paciente' => $db->lastInsertId(),
            'nombre_completo' => $data['nombre'] . ' ' . $data['apellido'],
            'username' => $data['username'],
            'email' => $data['email'],
            'cedula' => $data['cedula'],
            'password_temporal' => $password,
            'email_enviado' => $emailEnviado,
            'rol' => 'Paciente'
        ]
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
}

    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'status' => 500,
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
}



private function generarPasswordSimple($longitud = 8) {
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $longitud; $i++) {
        $password .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    return $password;
}


}

?>