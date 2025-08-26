<?php
namespace App\Controllers;

use App\Models\Cita;
use App\Models\Usuario;  // ← AGREGAR ESTA LÍNEA
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CitaController {
    
    public function consultarPorEspecialidadYMedico($request, $response, $args) {
    // Verificar sesión
        if (!isset($_SESSION['user_id'])) {
            return $response->withJson([
                'success' => false,
                'message' => 'No hay sesión activa'
            ], 401);
        }
        
        // Sin restricciones adicionales
        $idEspecialidad = $args['id_especialidad'] ?? null;
        $idMedico = $args['id_medico'] ?? null;
        
        $citaModel = new Cita();
        $resultado = $citaModel->consultarPorEspecialidadYMedico($idEspecialidad, $idMedico);
        return $response->withJson($resultado);
    }

        public function buscarCitasPorEspecialidadMedicoJSON(Request $request, Response $response) {
        try {
            $data = $request->getParsedBody();
            
            // Al menos uno de los filtros debe estar presente
            if (empty($data['id_especialidad']) && empty($data['id_medico'])) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'Debe especificar al menos una especialidad o un médico',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $citaModel = new Cita();
            
            // Preparar filtros
            $filtros = [];
            
            if (!empty($data['id_especialidad'])) {
                if (!is_numeric($data['id_especialidad']) || $data['id_especialidad'] <= 0) {
                    $result = [
                        'status' => 400,
                        'success' => false,
                        'message' => 'ID de especialidad inválido',
                        'data' => null
                    ];
                    $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
                $filtros['especialidad'] = $data['id_especialidad'];
            }
            
            if (!empty($data['id_medico'])) {
                if (!is_numeric($data['id_medico']) || $data['id_medico'] <= 0) {
                    $result = [
                        'status' => 400,
                        'success' => false,
                        'message' => 'ID de médico inválido',
                        'data' => null
                    ];
                    $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
                $filtros['medico'] = $data['id_medico'];
            }
            
            // Filtros opcionales adicionales
            if (!empty($data['estado'])) {
                $filtros['estado'] = $data['estado'];
            }
            
            if (!empty($data['tipo_cita'])) {
                $filtros['tipo_cita'] = $data['tipo_cita'];
            }
            
            if (!empty($data['id_paciente']) && is_numeric($data['id_paciente'])) {
                $filtros['paciente'] = $data['id_paciente'];
            }
            
            // Obtener las citas
            $resultado = $citaModel->buscarPorFiltros($filtros);
            
            if ($resultado['success']) {
                $result = [
                    'status' => 200,
                    'success' => true,
                    'message' => 'Citas encontradas correctamente',
                    'data' => [
                        'filtros_aplicados' => $filtros,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'citas' => $resultado['data']['citas'],
                        'estadisticas' => $resultado['data']['estadisticas']
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

    public function buscarCitaPorIdJSON(Request $request, Response $response) {
        try {
            $data = $request->getParsedBody();
            
            // Validar que se proporcione el ID de la cita
            if (empty($data['id_cita'])) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'El ID de la cita es requerido',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $idCita = $data['id_cita'];
            
            // Validar que sea un número válido
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
            
            $citaModel = new Cita();
            $resultado = $citaModel->consultarPorId($idCita);
            
            if ($resultado['success']) {
                $result = [
                    'status' => 200,
                    'success' => true,
                    'message' => 'Cita encontrada correctamente',
                    'data' => [
                        'id_cita_consultada' => $idCita,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'cita' => $resultado['data']
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
    
    public function consultarCitasPorFechasYUsuario(Request $request, Response $response) {
        try {
            $data = $request->getParsedBody();
            
            // Validar fechas requeridas
            if (empty($data['fecha_inicio']) || empty($data['fecha_fin'])) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'Las fechas de inicio y fin son requeridas',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            // Validar que se proporcione al menos un ID (paciente o médico)
            if (empty($data['id_paciente']) && empty($data['id_medico'])) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'Debe especificar un ID de paciente o médico',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            // No permitir ambos IDs al mismo tiempo
            if (!empty($data['id_paciente']) && !empty($data['id_medico'])) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'Solo puede especificar un ID de paciente O un ID de médico, no ambos',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $fechaInicio = $data['fecha_inicio'];
            $fechaFin = $data['fecha_fin'];
            
            // Validar formato de fechas
            if (!$this->validarFecha($fechaInicio) || !$this->validarFecha($fechaFin)) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'Formato de fecha inválido. Use YYYY-MM-DD',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            // Validar que fecha inicio no sea mayor que fecha fin
            if ($fechaInicio > $fechaFin) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'La fecha de inicio no puede ser mayor que la fecha de fin',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            // Preparar filtros
            $filtros = [];
            $tipoConsulta = '';
            
            if (!empty($data['id_paciente'])) {
                if (!is_numeric($data['id_paciente']) || $data['id_paciente'] <= 0) {
                    $result = [
                        'status' => 400,
                        'success' => false,
                        'message' => 'ID de paciente inválido',
                        'data' => null
                    ];
                    $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
                $filtros['paciente'] = $data['id_paciente'];
                $tipoConsulta = 'paciente';
            }
            
            if (!empty($data['id_medico'])) {
                if (!is_numeric($data['id_medico']) || $data['id_medico'] <= 0) {
                    $result = [
                        'status' => 400,
                        'success' => false,
                        'message' => 'ID de médico inválido',
                        'data' => null
                    ];
                    $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
                $filtros['medico'] = $data['id_medico'];
                $tipoConsulta = 'medico';
            }
            
            // Filtros opcionales adicionales
            if (!empty($data['estado'])) {
                $filtros['estado'] = $data['estado'];
            }
            
            if (!empty($data['tipo_cita'])) {
                $filtros['tipo_cita'] = $data['tipo_cita'];
            }
            
            if (!empty($data['id_especialidad']) && is_numeric($data['id_especialidad'])) {
                $filtros['especialidad'] = $data['id_especialidad'];
            }
            
            // Consultar las citas
            $citaModel = new Cita();
            $resultado = $citaModel->consultarPorRangoFechasYUsuario($fechaInicio, $fechaFin, $filtros);
            
            if ($resultado['success']) {
                $result = [
                    'status' => 200,
                    'success' => true,
                    'message' => 'Citas consultadas correctamente',
                    'data' => [
                        'parametros_consulta' => [
                            'fecha_inicio' => $fechaInicio,
                            'fecha_fin' => $fechaFin,
                            'tipo_consulta' => $tipoConsulta,
                            'id_usuario' => $filtros[$tipoConsulta],
                            'filtros_adicionales' => array_diff_key($filtros, [$tipoConsulta => ''])
                        ],
                        'timestamp' => date('Y-m-d H:i:s'),
                        'citas' => $resultado['data']['todas_las_citas'],
                        'citas_por_fecha' => $resultado['data']['citas_por_fecha'],
                        'estadisticas' => $resultado['data']['estadisticas']
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
    
    private function validarFecha($fecha) {
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }

    public function consultarPorRangoFechas($request, $response) {
        // Verificar sesión
        if (!isset($_SESSION['user_id'])) {
            return $response->withJson([
                'success' => false,
                'message' => 'No hay sesión activa'
            ], 401);
        }
        
        $method = $request->getMethod();
        
        // Determinar si es GET (query params) o POST (JSON)
        if ($method === 'GET') {
            // Método original con query parameters
            $params = $request->getQueryParams();
            $fechaInicio = $params['inicio'] ?? '';
            $fechaFin = $params['fin'] ?? '';
            $filtros = [];
            
            // Lógica original para GET
            $userRole = $_SESSION['rol'] ?? '';
            $userId = $_SESSION['user_id'] ?? '';
            
            if ($userRole === 'Médico') {
                $filtros['medico'] = $userId;
            } elseif ($userRole === 'Paciente') {
                $filtros['paciente'] = $userId;
            } elseif (isset($params['medico']) && !empty($params['medico'])) {
                $filtros['medico'] = $params['medico'];
            }
            
            if (isset($params['especialidad']) && !empty($params['especialidad'])) {
                $filtros['especialidad'] = $params['especialidad'];
            }
            
            $citaModel = new Cita();
            $resultado = $citaModel->consultarPorRangoFechas($fechaInicio, $fechaFin, $filtros);
            return $response->withJson($resultado);
            
        } elseif ($method === 'POST') {
            // Nueva funcionalidad con JSON
            $data = $request->getParsedBody();
            
            if (!$data) {
                return $response->withJson([
                    'success' => false,
                    'message' => 'Datos JSON requeridos'
                ], 400);
            }
            
            $citaModel = new Cita();
            
            // OPCIÓN 1: Consultar por ID específico
            if (isset($data['id_cita']) && !empty($data['id_cita'])) {
                if (!is_numeric($data['id_cita'])) {
                    return $response->withJson([
                        'success' => false,
                        'message' => 'El ID de la cita debe ser numérico'
                    ], 400);
                }
                
                $resultado = $citaModel->consultarPorId($data['id_cita']);
                return $response->withJson($resultado);
            }
            
            // OPCIÓN 2: Consultar por rango de fechas
            elseif (isset($data['fecha_inicio']) && isset($data['fecha_fin'])) {
                $fechaInicio = $data['fecha_inicio'];
                $fechaFin = $data['fecha_fin'];
                
                // Preparar filtros del JSON
                $filtros = [];
                
                // Aplicar lógica de roles
                $userRole = $_SESSION['rol'] ?? '';
                $userId = $_SESSION['user_id'] ?? '';
                
                if ($userRole === 'Médico') {
                    $filtros['medico'] = $userId;
                } elseif ($userRole === 'Paciente') {
                    $filtros['paciente'] = $userId;
                } else {
                    // Admin y Recepcionista pueden usar filtros opcionales
                    if (isset($data['id_medico']) && !empty($data['id_medico'])) {
                        $filtros['medico'] = $data['id_medico'];
                    }
                    
                    if (isset($data['id_paciente']) && !empty($data['id_paciente'])) {
                        $filtros['paciente'] = $data['id_paciente'];
                    }
                }
                
                // Filtros adicionales
                if (isset($data['id_especialidad']) && !empty($data['id_especialidad'])) {
                    $filtros['especialidad'] = $data['id_especialidad'];
                }
                
                if (isset($data['estado']) && !empty($data['estado'])) {
                    $filtros['estado'] = $data['estado'];
                }
                
                if (isset($data['tipo_cita']) && !empty($data['tipo_cita'])) {
                    $filtros['tipo_cita'] = $data['tipo_cita'];
                }
                
                $resultado = $citaModel->consultarPorRangoFechas($fechaInicio, $fechaFin, $filtros);
                return $response->withJson($resultado);
            }
            
            // Si no cumple ninguna opción
            else {
                return $response->withJson([
                    'success' => false,
                    'message' => 'Debe enviar "id_cita" para consulta específica O "fecha_inicio" y "fecha_fin" para rango de fechas'
                ], 400);
            }
        }
        
        // Método no soportado
        return $response->withJson([
            'success' => false,
            'message' => 'Método no soportado. Use GET o POST'
        ], 405);
    }

    public function consultarPorPaciente($request, $response, $args) {
        $idPaciente = $args['id_paciente'];
        
        // Solo verificar que hay sesión activa
        if (!isset($_SESSION['user_id'])) {
            return $response->withJson([
                'success' => false,
                'message' => 'No hay sesión activa'
            ], 401);
        }
        
        // Sin restricciones de permisos - cualquier usuario logueado puede consultar
        $citaModel = new Cita();
        $resultado = $citaModel->consultarPorPaciente($idPaciente);
        return $response->withJson($resultado);
    }

    public function listarTodas($request, $response) {
        $userRole = $_SESSION['rol'] ?? '';
        
        if (!in_array($userRole, ['Administrador', 'Recepcionista'])) {
            return $response->withJson([
                'success' => false,
                'message' => 'No tienes permisos para ver todas las citas'
            ], 403);
        }
        
        $citaModel = new Cita();
        $resultado = $citaModel->listarTodas();
        return $response->withJson($resultado);
    }

    // Agregar estos métodos al CitaController existente

    /**
     * Consultar cita específica por ID usando JSON
     * Endpoint: POST /citas/consultar-por-id
     */
    public function consultarPorIdJson($request, $response) {
        // Verificar sesión
        if (!isset($_SESSION['user_id'])) {
            return $response->withJson([
                'success' => false,
                'message' => 'No hay sesión activa'
            ], 401);
        }
        
        // Obtener datos del JSON
        $data = $request->getParsedBody();
        
        if (!isset($data['id_cita']) || empty($data['id_cita'])) {
            return $response->withJson([
                'success' => false,
                'message' => 'El ID de la cita es requerido'
            ], 400);
        }
        
        $idCita = $data['id_cita'];
        
        // Validar que sea numérico
        if (!is_numeric($idCita)) {
            return $response->withJson([
                'success' => false,
                'message' => 'El ID de la cita debe ser numérico'
            ], 400);
        }
        
        $citaModel = new Cita();
        $resultado = $citaModel->consultarPorId($idCita);
        return $response->withJson($resultado);
    }

    /**
     * Consultar citas por rango de fechas usando JSON
     * Endpoint: POST /citas/consultar-por-fechas
     */
    public function consultarPorRangoFechasJson($request, $response) {
        // Verificar sesión
        if (!isset($_SESSION['user_id'])) {
            return $response->withJson([
                'success' => false,
                'message' => 'No hay sesión activa'
            ], 401);
        }
        
        // Obtener datos del JSON
        $data = $request->getParsedBody();
        
        // Validar campos requeridos
        if (!isset($data['fecha_inicio']) || !isset($data['fecha_fin'])) {
            return $response->withJson([
                'success' => false,
                'message' => 'Las fechas de inicio y fin son requeridas'
            ], 400);
        }
        
        $fechaInicio = $data['fecha_inicio'];
        $fechaFin = $data['fecha_fin'];
        
        // Preparar filtros opcionales del JSON
        $filtros = [];
        
        // Aplicar lógica de roles como en el método original
        $userRole = $_SESSION['rol'] ?? '';
        $userId = $_SESSION['user_id'] ?? '';
        
        if ($userRole === 'Médico') {
            // Si es médico, solo sus citas
            $filtros['medico'] = $userId;
        } elseif ($userRole === 'Paciente') {
            // Si es paciente, solo sus citas
            $filtros['paciente'] = $userId;
        }
        // Admin y Recepcionista pueden filtrar opcionalmente
        else {
            if (isset($data['id_medico']) && !empty($data['id_medico'])) {
                $filtros['medico'] = $data['id_medico'];
            }
            
            if (isset($data['id_paciente']) && !empty($data['id_paciente'])) {
                $filtros['paciente'] = $data['id_paciente'];
            }
        }
        
        // Filtros adicionales
        if (isset($data['id_especialidad']) && !empty($data['id_especialidad'])) {
            $filtros['especialidad'] = $data['id_especialidad'];
        }
        
        if (isset($data['estado']) && !empty($data['estado'])) {
            $filtros['estado'] = $data['estado'];
        }
        
        if (isset($data['tipo_cita']) && !empty($data['tipo_cita'])) {
            $filtros['tipo_cita'] = $data['tipo_cita'];
        }
        
        $citaModel = new Cita();
        $resultado = $citaModel->consultarPorRangoFechas($fechaInicio, $fechaFin, $filtros);
        return $response->withJson($resultado);
    }

    public function obtenerCitasPorMedicoJSON(Request $request, Response $response) {
        try {
            $data = $request->getParsedBody();
            
            // Validar que se proporcione el ID del médico
            if (empty($data['id_medico'])) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'El ID del médico es requerido',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $idMedico = $data['id_medico'];
            
            // Validar que sea un número válido
            if (!is_numeric($idMedico) || $idMedico <= 0) {
                $result = [
                    'status' => 400,
                    'success' => false,
                    'message' => 'ID de médico inválido',
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            // VALIDAR QUE EL ID PERTENEZCA A UN MÉDICO (rol 3)
            $usuarioModel = new Usuario();
            $medico = $usuarioModel->verificarEsMedico($idMedico);
            
            if (!$medico['success']) {
                $result = [
                    'status' => 404,
                    'success' => false,
                    'message' => $medico['message'],
                    'data' => null
                ];
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
            // Obtener las citas del médico
            $citaModel = new Cita();
            $resultado = $citaModel->consultarPorMedico($idMedico);
            
            if ($resultado['success']) {
                $result = [
                    'status' => 200,
                    'success' => true,
                    'message' => 'Citas del médico obtenidas correctamente',
                    'data' => [
                        'id_medico_consultado' => $idMedico,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'medico' => $medico['data'], // Información del médico
                        'citas' => $resultado['data']['citas'],
                        'estadisticas' => $resultado['data']['estadisticas']
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

     public function listarTodasCompletas($request, $response) {
        try {
            // Obtener parámetros opcionales de filtro
            $queryParams = $request->getQueryParams();
            $filtros = [];
            
            // Filtros opcionales
            if (!empty($queryParams['estado'])) {
                $filtros['estado'] = $queryParams['estado'];
            }
            
            if (!empty($queryParams['fecha_desde'])) {
                $filtros['fecha_desde'] = $queryParams['fecha_desde'];
            }
            
            if (!empty($queryParams['fecha_hasta'])) {
                $filtros['fecha_hasta'] = $queryParams['fecha_hasta'];
            }
            
            if (!empty($queryParams['especialidad'])) {
                $filtros['especialidad'] = $queryParams['especialidad'];
            }
            
            if (!empty($queryParams['sucursal'])) {
                $filtros['sucursal'] = $queryParams['sucursal'];
            }
            
            // Límite de registros (para paginación)
            $limite = !empty($queryParams['limite']) ? (int)$queryParams['limite'] : 100;
            $pagina = !empty($queryParams['pagina']) ? (int)$queryParams['pagina'] : 1;
            
            $citaModel = new Cita();
            $resultado = $citaModel->listarTodasCompletas($filtros, $limite, $pagina);
            
            if ($resultado['success']) {
                $result = [
                    'status' => 200,
                    'success' => true,
                    'message' => $resultado['message'],
                    'data' => [
                        'timestamp' => date('Y-m-d H:i:s'),
                        'filtros_aplicados' => $filtros,
                        'paginacion' => [
                            'pagina_actual' => $pagina,
                            'limite_por_pagina' => $limite,
                            'total_registros' => $resultado['data']['total_registros']
                        ],
                        'citas' => $resultado['data']['citas'],
                        'estadisticas' => $resultado['data']['estadisticas'],
                        'estados_disponibles' => $resultado['data']['estados_disponibles']
                    ]
                ];
                return $response->withJson($result, 200);
            } else {
                $result = [
                    'status' => 500,
                    'success' => false,
                    'message' => $resultado['message'],
                    'data' => null
                ];
                return $response->withJson($result, 500);
            }
            
        } catch (\Exception $e) {
            $result = [
                'status' => 500,
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
            return $response->withJson($result, 500);
        }
    }
}
?>