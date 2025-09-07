<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\HistorialMedico;

class HistorialController {
    
    /**
     * Obtener todas las citas completadas con recetas
     * GET /historial/completo
     */
    public function obtenerHistorialCompleto(Request $request, Response $response) {
        try {
            $params = $request->getQueryParams();
            $limite = isset($params['limite']) ? (int)$params['limite'] : 50;
            $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
            
            $historialModel = new HistorialMedico();
            $resultado = $historialModel->obtenerHistorialCompleto($limite, $offset);
            
            $result = [
                'status' => 200,
                'success' => $resultado['success'],
                'message' => $resultado['message'],
                'data' => $resultado['data'] ?? null
            ];
            
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            
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
    
    /**
     * Obtener historial de una cita específica
     * GET /historial/cita/{id_cita}
     */
    public function obtenerHistorialPorCita(Request $request, Response $response, array $args) {
        try {
            $idCita = $args['id_cita'];
            
            if (!is_numeric($idCita) || $idCita <= 0) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'ID de cita inválido',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $historialModel = new HistorialMedico();
            $resultado = $historialModel->obtenerHistorialPorCita($idCita);
            
            $statusCode = $resultado['success'] ? 200 : 404;
            
            $result = [
                'status' => $statusCode,
                'success' => $resultado['success'],
                'message' => $resultado['message'],
                'data' => $resultado['data'] ?? null
            ];
            
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
            
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
    
    /**
     * Obtener historial por cédula del paciente
     * GET /historial/cedula/{cedula}
     */
    public function obtenerHistorialPorCedula(Request $request, Response $response, array $args) {
        try {
            $cedula = $args['cedula'];
            
            if (empty($cedula) || !is_numeric($cedula) || strlen($cedula) != 10) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'Cédula inválida. Debe tener 10 dígitos',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $historialModel = new HistorialMedico();
            $resultado = $historialModel->obtenerHistorialPorCedula($cedula);
            
            $statusCode = $resultado['success'] ? 200 : 404;
            
            $result = [
                'status' => $statusCode,
                'success' => $resultado['success'],
                'message' => $resultado['message'],
                'data' => $resultado['data'] ?? null
            ];
            
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
            
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

    /**
 * Obtener historial por ID del paciente
 * GET /historial/paciente/{id_paciente}
 */
public function obtenerHistorialPorIdPaciente(Request $request, Response $response, array $args) {
    try {
        $idPaciente = $args['id_paciente'];
        
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
        
        $historialModel = new HistorialMedico();
        $resultado = $historialModel->obtenerHistorialPorIdPaciente($idPaciente);
        
        $statusCode = $resultado['success'] ? 200 : 404;
        
        $result = [
            'status' => $statusCode,
            'success' => $resultado['success'],
            'message' => $resultado['message'],
            'data' => $resultado['data'] ?? null
        ];
        
        $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
        
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
}
?>