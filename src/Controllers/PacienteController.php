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
}
?>