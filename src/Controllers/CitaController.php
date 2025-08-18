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

}
?>