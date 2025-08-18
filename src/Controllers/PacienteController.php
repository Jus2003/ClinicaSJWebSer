<?php
namespace App\Controllers;

use App\Models\Paciente;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PacienteController {
    
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


}
?>