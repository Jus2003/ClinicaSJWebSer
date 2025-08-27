<?php
namespace App\Controllers;

use App\Models\Medico;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MedicoController {
    private $medico;
    
    // ✅ CONSTRUCTOR CORREGIDO
    public function __construct() {
        $this->medico = new Medico();
    }
    
    // ✅ MÉTODO EXISTENTE - CREAR MÉDICO
    public function crearMedico(Request $request, Response $response) {
        try {
            $data = $request->getParsedBody();
            
            // Validación básica de datos
            if (empty($data)) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'No se recibieron datos',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            // Asegurar que especialidades sea un array
            if (isset($data['especialidades']) && !is_array($data['especialidades'])) {
                $data['especialidades'] = [$data['especialidades']];
            }
            
            $resultado = $this->medico->crearMedico($data);
            
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
                    'data' => isset($resultado['errores']) ? ['errores' => $resultado['errores']] : null
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

    // ✅ MÉTODO PARA LISTAR TODOS LOS MÉDICOS
    public function listarTodos($request, $response) {
        try {
            $resultado = $this->medico->listarTodos();
            
            $result = [
                'status' => 200,
                'success' => true,
                'message' => 'Médicos consultados exitosamente',
                'data' => $resultado
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

    // ✅ MÉTODO PARA LISTAR MÉDICOS POR ESPECIALIDAD
    public function listarPorEspecialidad($request, $response, $args) {
        try {
            $id_especialidad = $args['id_especialidad'];
            $resultado = $this->medico->listarPorEspecialidad($id_especialidad);
            
            $result = [
                'status' => 200,
                'success' => true,
                'message' => 'Médicos por especialidad consultados exitosamente',
                'data' => $resultado
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

    // ✅ MÉTODO PRINCIPAL PARA ASIGNAR/EDITAR HORARIOS
    public function asignarHorarios($request, $response, $args)
    {
        try {
            $id_medico = $args['id_medico'];
            $data = $request->getParsedBody();

            // ✅ Validaciones completas
            if (!isset($data['horarios']) || !is_array($data['horarios'])) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'El formato de horarios es inválido. Se requiere un array de horarios.',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Verificar que el médico existe
            if (!$this->medico->existeMedico($id_medico)) {
                $result = [
                    'status' => 404,
                    'success' => false,
                    'message' => 'El médico especificado no existe.',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // ✅ Validar cada horario
            foreach ($data['horarios'] as $index => $horario) {
                $errores = [];

                // Campos requeridos
                if (!isset($horario['dia_semana']) || empty($horario['dia_semana'])) {
                    $errores[] = "Horario #{$index}: día_semana es requerido";
                }
                if (!isset($horario['hora_inicio']) || empty($horario['hora_inicio'])) {
                    $errores[] = "Horario #{$index}: hora_inicio es requerida";
                }
                if (!isset($horario['hora_fin']) || empty($horario['hora_fin'])) {
                    $errores[] = "Horario #{$index}: hora_fin es requerida";
                }
                if (!isset($horario['id_sucursal']) || empty($horario['id_sucursal'])) {
                    $errores[] = "Horario #{$index}: id_sucursal es requerido";
                }

                // Validar día de la semana (1-7)
                if (isset($horario['dia_semana']) && ($horario['dia_semana'] < 1 || $horario['dia_semana'] > 7)) {
                    $errores[] = "Horario #{$index}: día_semana debe estar entre 1 (Lunes) y 7 (Domingo)";
                }

                // Validar formato de horas
                if (isset($horario['hora_inicio']) && !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $horario['hora_inicio'])) {
                    $errores[] = "Horario #{$index}: hora_inicio debe tener formato HH:MM o HH:MM:SS";
                }
                if (isset($horario['hora_fin']) && !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $horario['hora_fin'])) {
                    $errores[] = "Horario #{$index}: hora_fin debe tener formato HH:MM o HH:MM:SS";
                }

                // Validar que hora_inicio < hora_fin
                if (isset($horario['hora_inicio']) && isset($horario['hora_fin'])) {
                    if (strtotime($horario['hora_inicio']) >= strtotime($horario['hora_fin'])) {
                        $errores[] = "Horario #{$index}: hora_inicio debe ser menor que hora_fin";
                    }
                }

                // Si hay errores, retornar
                if (!empty($errores)) {
                    $result = [
                        'status' => 400,
                        'success' => false,
                        'message' => 'Errores de validación en los horarios',
                        'data' => ['errores' => $errores]
                    ];
                    $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            // ✅ Procesar los horarios
            $resultado = $this->medico->asignarHorarios($id_medico, $data['horarios']);

            if ($resultado['success']) {
                $result = [
                    'status' => 200,
                    'success' => true,
                    'message' => $resultado['message'],
                    'data' => [
                        'id_medico' => $id_medico,
                        'horarios_asignados' => count($data['horarios'])
                    ]
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
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
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'data' => null
            ];
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // ✅ MÉTODO PARA CONSULTAR HORARIOS
    public function consultarHorarios($request, $response, $args)
    {
        try {
            $id_medico = $args['id_medico'];

            // Verificar que el médico existe
            if (!$this->medico->existeMedico($id_medico)) {
                $result = [
                    'status' => 404,
                    'success' => false,
                    'message' => 'El médico especificado no existe.',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $horarios = $this->medico->obtenerHorarios($id_medico);

            $result = [
                'status' => 200,
                'success' => true,
                'message' => 'Horarios consultados exitosamente',
                'data' => [
                    'id_medico' => $id_medico,
                    'horarios' => $horarios
                ]
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