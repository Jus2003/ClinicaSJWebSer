<?php
namespace App\Controllers;

use App\Models\Especialidad;

class EspecialidadController {
    
    public function listarTodas($request, $response) {
        // Verificar autenticación
        if (!isset($_SESSION['user_id'])) {
            return $response->withJson([
                'success' => false,
                'message' => 'No hay sesión activa'
            ], 401);
        }
        
        $especialidadModel = new Especialidad();
        $resultado = $especialidadModel->listarTodas();
        return $response->withJson($resultado);
    }
}
?>