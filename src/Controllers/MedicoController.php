<?php
namespace App\Controllers;

use App\Models\Medico;

class MedicoController {
    
    public function listarTodos($request, $response) {
        // Verificar autenticación
        if (!isset($_SESSION['user_id'])) {
            return $response->withJson([
                'success' => false,
                'message' => 'No hay sesión activa'
            ], 401);
        }
        
        $medicoModel = new Medico();
        $resultado = $medicoModel->listarTodos();
        return $response->withJson($resultado);
    }
    
    public function listarPorEspecialidad($request, $response, $args) {
        $idEspecialidad = $args['id_especialidad'];
        
        // Verificar autenticación
        if (!isset($_SESSION['user_id'])) {
            return $response->withJson([
                'success' => false,
                'message' => 'No hay sesión activa'
            ], 401);
        }
        
        $medicoModel = new Medico();
        $resultado = $medicoModel->listarPorEspecialidad($idEspecialidad);
        return $response->withJson($resultado);
    }
}
?>