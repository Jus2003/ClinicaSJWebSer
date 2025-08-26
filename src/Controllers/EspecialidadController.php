<?php
namespace App\Controllers;

use App\Models\Especialidad;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class EspecialidadController {
    
    /**
     * MÉTODO ORIGINAL: Mantenerlo como está para compatibilidad
     */
    public function listarTodas($request, $response) {
        // ❌ ELIMINAR VALIDACIÓN DE SESIÓN PARA SER "GLOBAL"
        // if (!isset($_SESSION['user_id'])) {
        //     return $response->withJson([
        //         'success' => false,
        //         'message' => 'No hay sesión activa'
        //     ], 401);
        // }
        
        $especialidadModel = new Especialidad();
        $resultado = $especialidadModel->listarTodas();
        return $response->withJson($resultado);
    }
    
    /**
     * NUEVO MÉTODO GLOBAL: Todas las especialidades con información completa
     * GET /especialidades/todas-completas
     */
    public function listarTodasCompletas($request, $response) {
        try {
            // Obtener parámetros opcionales
            $queryParams = $request->getQueryParams();
            $incluirInactivas = isset($queryParams['incluir_inactivas']) && $queryParams['incluir_inactivas'] === 'true';
            $incluirMedicos = isset($queryParams['incluir_medicos']) && $queryParams['incluir_medicos'] === 'true';
            $incluirEstadisticas = isset($queryParams['incluir_estadisticas']) && $queryParams['incluir_estadisticas'] === 'true';
            
            $especialidadModel = new Especialidad();
            $resultado = $especialidadModel->listarTodasCompletas($incluirInactivas, $incluirMedicos, $incluirEstadisticas);
            
            if ($resultado['success']) {
                $result = [
                    'status' => 200,
                    'success' => true,
                    'message' => $resultado['message'],
                    'data' => [
                        'timestamp' => date('Y-m-d H:i:s'),
                        'parametros' => [
                            'incluir_inactivas' => $incluirInactivas,
                            'incluir_medicos' => $incluirMedicos,
                            'incluir_estadisticas' => $incluirEstadisticas
                        ],
                        'especialidades' => $resultado['data']['especialidades'],
                        'resumen' => $resultado['data']['resumen']
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