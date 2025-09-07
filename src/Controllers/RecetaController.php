<?php
namespace App\Controllers;

use App\Models\Receta;
use App\Services\EmailService;

class RecetaController {
    
    /**
     * Crear nueva receta médica para una cita (SIMPLIFICADO)
     * POST /recetas/crear
     */
    public function crearReceta($request, $response) {
        try {

            $data = $request->getParsedBody();
            
            // Validar campos requeridos
            $camposRequeridos = [
                'id_cita', 'medicamento', 'dosis', 'frecuencia', 
                'duracion', 'cantidad'
            ];
            
            foreach ($camposRequeridos as $campo) {
                if (empty($data[$campo])) {
                    $result = [
                        'status' => 400,
                        'success' => false,
                        'message' => "El campo {$campo} es requerido",
                        'data' => null
                    ];
                    $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            $recetaModel = new Receta();
            $resultado = $recetaModel->crearRecetaPorCita($data);

            if ($resultado['success']) {
                $result = [
                    'status' => 201,
                    'success' => true,
                    'message' => $resultado['message'],
                    'data' => $resultado['data']
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
            } else {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => $resultado['message'],
                    'data' => $resultado['data'] ?? null
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

    /**
     * Obtener recetas de una cita
     * GET /recetas/cita/{id_cita}
     */
    public function obtenerRecetasPorCita($request, $response, $args) {
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

            $recetaModel = new Receta();
            $resultado = $recetaModel->obtenerRecetasPorCita($idCita);

            $result = [
                'status' => 200,
                'success' => $resultado['success'],
                'message' => $resultado['message'],
                'data' => $resultado['data']
            ];
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

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