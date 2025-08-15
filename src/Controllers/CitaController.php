<?php
namespace App\Controllers;

use App\Models\Cita;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CitaController {
    
    public function consultarPorEspecialidadYMedico(Request $request, Response $response, $args) {
        try {
            $idEspecialidad = $args['id_especialidad'] ?? null;
            $idMedico = $args['id_medico'] ?? null;
            
            // Obtener parámetros de query opcionales
            $queryParams = $request->getQueryParams();
            $filtros = [
                'estado' => $queryParams['estado'] ?? null,
                'tipo' => $queryParams['tipo'] ?? null,
                'fecha_desde' => $queryParams['fecha_desde'] ?? null,
                'fecha_hasta' => $queryParams['fecha_hasta'] ?? null,
                'limite' => $queryParams['limite'] ?? null
            ];
            
            // Filtrar valores vacíos
            $filtros = array_filter($filtros, function($value) {
                return !empty($value);
            });
            
            $citaModel = new Cita();
            $resultado = $citaModel->consultarPorEspecialidadYMedico($idEspecialidad, $idMedico, $filtros);
            
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
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    public function consultarPorRangoFechas(Request $request, Response $response) {
        try {
            $queryParams = $request->getQueryParams();
            $fechaInicio = $queryParams['inicio'] ?? '';
            $fechaFin = $queryParams['fin'] ?? '';
            
            // Filtros opcionales
            $filtros = [
                'medico' => $queryParams['medico'] ?? null,
                'especialidad' => $queryParams['especialidad'] ?? null,
                'estado' => $queryParams['estado'] ?? null
            ];
            
            // Filtrar valores vacíos
            $filtros = array_filter($filtros, function($value) {
                return !empty($value);
            });
            
            $citaModel = new Cita();
            $resultado = $citaModel->consultarPorRangoFechas($fechaInicio, $fechaFin, $filtros);
            
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
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
?>