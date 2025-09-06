<?php
namespace App\Controllers;

use App\Models\Triaje;
use App\Models\Cita;
use Exception;

class TriajeController {
    private $triaje;
    private $cita;
    
    public function __construct() {
        $this->triaje = new Triaje();
        $this->cita = new Cita();
    }
    
    // GET /triaje/preguntas - Obtener todas las preguntas del triaje
    public function obtenerPreguntas($request, $response) {
        try {
            $preguntas = $this->triaje->obtenerPreguntasTriaje();
            
            // Decodificar opciones JSON para mejor manejo en frontend
            foreach ($preguntas as &$pregunta) {
                if ($pregunta['opciones_json']) {
                    $pregunta['opciones'] = json_decode($pregunta['opciones_json'], true);
                }
                unset($pregunta['opciones_json']); // Limpieza del campo JSON crudo
            }
            
            $data = [
                'success' => true,
                'message' => 'Preguntas de triaje obtenidas exitosamente',
                'data' => $preguntas,
                'total' => count($preguntas)
            ];
            
            $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $data = [
                'success' => false, 
                'message' => 'Error interno: ' . $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    // POST /triaje/responder - Responder triaje completo
    public function responderTriaje($request, $response) {
        try {
            $datos = json_decode($request->getBody()->getContents(), true);
            
            // Validaciones básicas
            if (!isset($datos['id_cita']) || !isset($datos['respuestas'])) {
                $data = [
                    'success' => false, 
                    'message' => 'ID de cita y respuestas son requeridos'
                ];
                $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $id_cita = $datos['id_cita'];
            $respuestas = $datos['respuestas'];
            $tipo_triaje = $datos['tipo_triaje'] ?? 'digital';
            $id_usuario_registro = $datos['id_usuario_registro'] ?? 1; // Usuario por defecto
            
            // Verificar que la cita existe usando el método correcto
            $resultadoCita = $this->cita->consultarPorId($id_cita);
            if (!$resultadoCita['success']) {
                $data = [
                    'success' => false, 
                    'message' => 'Cita no encontrada: ' . $resultadoCita['message']
                ];
                $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
            $citaInfo = $resultadoCita['data']; // Extraer los datos de la cita
            
            // Validar estructura de respuestas
            if (!is_array($respuestas) || empty($respuestas)) {
                $data = [
                    'success' => false, 
                    'message' => 'Respuestas inválidas'
                ];
                $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            // Guardar respuestas
            $this->triaje->guardarRespuestasTriaje(
                $id_cita, 
                $respuestas, 
                $id_usuario_registro, 
                $tipo_triaje
            );
            
            // Verificar si el triaje está completo
            $triageCompleto = $this->triaje->tieneTriajeCompleto($id_cita);
            $estadisticas = $this->triaje->obtenerEstadisticasTriaje($id_cita);
            
            $data = [
                'success' => true,
                'message' => 'Triaje guardado exitosamente',
                'data' => [
                    'id_cita' => $id_cita,
                    'triaje_completo' => $triageCompleto,
                    'respuestas_guardadas' => count($respuestas),
                    'estadisticas' => $estadisticas,
                    'info_cita' => [
                        'fecha_cita' => $citaInfo['fecha_cita'],
                        'hora_cita' => $citaInfo['hora_cita'],
                        'estado_cita' => $citaInfo['estado_cita'],
                        'paciente' => $citaInfo['nombre_paciente']
                    ]
                ]
            ];
            
            $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
            
        } catch (Exception $e) {
            $data = [
                'success' => false, 
                'message' => 'Error interno: ' . $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    // GET /triaje/cita/{id_cita} - Obtener triaje de una cita específica  
    public function obtenerTriajePorCita($request, $response, $args) {
        try {
            $id_cita = $args['id_cita'];
            
            // Verificar que la cita existe usando el método correcto
            $resultadoCita = $this->cita->consultarPorId($id_cita);
            if (!$resultadoCita['success']) {
                $data = [
                    'success' => false, 
                    'message' => 'Cita no encontrada: ' . $resultadoCita['message']
                ];
                $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
            $citaInfo = $resultadoCita['data']; // Extraer los datos de la cita
            
            // Obtener respuestas del triaje
            $respuestas = $this->triaje->obtenerRespuestasTriajePorCita($id_cita);
            $triageCompleto = $this->triaje->tieneTriajeCompleto($id_cita);
            $estadisticas = $this->triaje->obtenerEstadisticasTriaje($id_cita);
            
            // Procesar respuestas para mejor presentación
            foreach ($respuestas as &$respuesta) {
                if ($respuesta['opciones_json']) {
                    $respuesta['opciones'] = json_decode($respuesta['opciones_json'], true);
                }
                unset($respuesta['opciones_json']);
            }
            
            if (empty($respuestas)) {
                $data = [
                    'success' => false,
                    'message' => 'No se ha realizado triaje para esta cita',
                    'data' => [
                        'id_cita' => $id_cita,
                        'triaje_realizado' => false,
                        'estado_cita' => $citaInfo['estado_cita'],
                        'puede_realizar_triaje' => true, // Siempre puede realizar triaje
                        'info_cita' => [
                            'fecha_cita' => $citaInfo['fecha_cita'],
                            'hora_cita' => $citaInfo['hora_cita'],
                            'paciente' => $citaInfo['nombre_paciente'],
                            'medico' => $citaInfo['nombre_medico'],
                            'especialidad' => $citaInfo['nombre_especialidad']
                        ]
                    ]
                ];
                $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
            $data = [
                'success' => true,
                'message' => 'Triaje obtenido exitosamente',
                'data' => [
                    'id_cita' => $id_cita,
                    'triaje_realizado' => true,
                    'triaje_completo' => $triageCompleto,
                    'respuestas' => $respuestas,
                    'estadisticas' => $estadisticas,
                    'info_cita' => [
                        'fecha_cita' => $citaInfo['fecha_cita'],
                        'hora_cita' => $citaInfo['hora_cita'],
                        'estado_cita' => $citaInfo['estado_cita'],
                        'paciente' => $citaInfo['nombre_paciente'],
                        'medico' => $citaInfo['nombre_medico'],
                        'especialidad' => $citaInfo['nombre_especialidad']
                    ]
                ]
            ];
            
            $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $data = [
                'success' => false, 
                'message' => 'Error interno: ' . $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    // GET /triaje/verificar/{id_cita} - Verificar estado del triaje
    public function verificarEstadoTriaje($request, $response, $args) {
        try {
            $id_cita = $args['id_cita'];
            
            // Verificar que la cita existe usando el método correcto
            $resultadoCita = $this->cita->consultarPorId($id_cita);
            if (!$resultadoCita['success']) {
                $data = [
                    'success' => false, 
                    'message' => 'Cita no encontrada: ' . $resultadoCita['message']
                ];
                $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
            $citaInfo = $resultadoCita['data']; // Extraer los datos de la cita
            
            $triageCompleto = $this->triaje->tieneTriajeCompleto($id_cita);
            $estadisticas = $this->triaje->obtenerEstadisticasTriaje($id_cita);
            
            $data = [
                'success' => true,
                'message' => 'Estado del triaje verificado',
                'data' => [
                    'id_cita' => $id_cita,
                    'estado_cita' => $citaInfo['estado_cita'],
                    'triaje_realizado' => !empty($estadisticas),
                    'triaje_completo' => $triageCompleto,
                    'puede_realizar_triaje' => true, // Siempre puede realizar triaje
                    'estadisticas' => $estadisticas ?: null,
                    'info_cita' => [
                        'fecha_cita' => $citaInfo['fecha_cita'],
                        'hora_cita' => $citaInfo['hora_cita'],
                        'paciente' => $citaInfo['nombre_paciente'],
                        'medico' => $citaInfo['nombre_medico'],
                        'especialidad' => $citaInfo['nombre_especialidad']
                    ]
                ]
            ];
            
            $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $data = [
                'success' => false, 
                'message' => 'Error interno: ' . $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
?>