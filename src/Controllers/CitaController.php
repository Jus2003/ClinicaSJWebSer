<?php
namespace App\Controllers;

use App\Models\Cita;
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
    
    public function consultarPorRangoFechas($request, $response) {
        // Verificar sesión
        if (!isset($_SESSION['user_id'])) {
            return $response->withJson([
                'success' => false,
                'message' => 'No hay sesión activa'
            ], 401);
        }
        
        $params = $request->getQueryParams();
        $fechaInicio = $params['inicio'] ?? '';
        $fechaFin = $params['fin'] ?? '';
        
        // Obtener filtros adicionales
        $filtros = [];
        
        // LÓGICA AUTOMÁTICA SEGÚN EL ROL:
        $userRole = $_SESSION['rol'] ?? '';
        $userId = $_SESSION['user_id'] ?? '';
        
        if ($userRole === 'Médico') {
            // Si es médico, solo mostrar sus citas
            $filtros['medico'] = $userId;
        } elseif ($userRole === 'Paciente') {
            // Si es paciente, solo mostrar sus citas
            $filtros['paciente'] = $userId;
        }
        // Admin y Recepcionista pueden filtrar opcionalmente
        elseif (isset($params['medico']) && !empty($params['medico'])) {
            $filtros['medico'] = $params['medico'];
        }
        
        if (isset($params['especialidad']) && !empty($params['especialidad'])) {
            $filtros['especialidad'] = $params['especialidad'];
        }
        
        $citaModel = new Cita();
        $resultado = $citaModel->consultarPorRangoFechas($fechaInicio, $fechaFin, $filtros);
        return $response->withJson($resultado);
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

}
?>