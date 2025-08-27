<?php
namespace App\Controllers;

use App\Models\Medico;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MedicoController {
    
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
            
            $medicoModel = new Medico();
            $resultado = $medicoModel->crearMedico($data);
            
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

    public function asignarHorarios($request, $response, $args)
    {
        $id_medico = $args['id_medico'];
        $data = $request->getParsedBody();

        // Validar formato JSON y estructura
        if (!isset($data['horarios']) || !is_array($data['horarios'])) {
            return $response->withStatus(400)->withJson(['error' => 'Formato de horarios inválido']);
        }

        foreach ($data['horarios'] as $horario) {
            if (
                !isset($horario['dia_semana']) ||
                !isset($horario['hora_inicio']) ||
                !isset($horario['hora_fin']) ||
                !isset($horario['id_sucursal'])
            ) {
                return $response->withStatus(400)->withJson(['error' => 'Datos de horario incompletos']);
            }
            // Validaciones adicionales de hora y día aquí...
        }

        // Guardar o actualizar los horarios en la base de datos
        // ...implementación de guardado/actualización...

        return $response->withJson(['success' => true, 'message' => 'Horarios asignados correctamente']);
    }
}
?>