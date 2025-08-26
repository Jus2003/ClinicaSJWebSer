<?php
namespace App\Models;

use App\Config\Database;

class Especialidad {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * MÉTODO ORIGINAL: Listar especialidades básicas
     */
    public function listarTodas() {
        try {
            $sql = "SELECT id_especialidad, nombre_especialidad, descripcion, activo 
                    FROM especialidades 
                    WHERE activo = 1 
                    ORDER BY nombre_especialidad ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $especialidades = $stmt->fetchAll();
            
            return [
                'success' => true,
                'message' => 'Especialidades activas obtenidas correctamente',
                'data' => $especialidades
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error obteniendo especialidades: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * NUEVO MÉTODO: Listar todas las especialidades con información completa
     */
    public function listarTodasCompletas($incluirInactivas = false, $incluirMedicos = false, $incluirEstadisticas = false) {
        try {
            $sql = "SELECT e.id_especialidad, e.nombre_especialidad, e.descripcion, 
                        e.activo, e.fecha_creacion,
                        e.duracion_cita_minutos,
                        -- Contar médicos por especialidad
                        COUNT(DISTINCT me.id_medico) as total_medicos,
                        COUNT(DISTINCT CASE WHEN u.activo = 1 THEN me.id_medico END) as medicos_activos";
            
            if ($incluirEstadisticas) {
                $sql .= ",
                        -- Estadísticas de citas
                        COUNT(DISTINCT c.id_cita) as total_citas,
                        COUNT(DISTINCT CASE WHEN c.estado_cita = 'completada' THEN c.id_cita END) as citas_completadas,
                        COUNT(DISTINCT CASE WHEN c.fecha_cita >= CURDATE() THEN c.id_cita END) as citas_futuras";
            }
            
            $sql .= " FROM especialidades e
                    LEFT JOIN medico_especialidades me ON e.id_especialidad = me.id_especialidad
                    LEFT JOIN usuarios u ON me.id_medico = u.id_usuario AND u.id_rol = 3";
            
            if ($incluirEstadisticas) {
                $sql .= " LEFT JOIN citas c ON e.id_especialidad = c.id_especialidad";
            }
            
            if (!$incluirInactivas) {
                $sql .= " WHERE e.activo = 1";
            }
            
            $sql .= " GROUP BY e.id_especialidad
                     ORDER BY e.nombre_especialidad ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $especialidades = $stmt->fetchAll();
            
            // Si se solicita, incluir información de médicos por cada especialidad
            if ($incluirMedicos) {
                foreach ($especialidades as &$especialidad) {
                    $especialidad['medicos'] = $this->obtenerMedicosPorEspecialidad($especialidad['id_especialidad']);
                }
            }
            
            // Calcular resumen
            $resumen = [
                'total_especialidades' => count($especialidades),
                'especialidades_activas' => count(array_filter($especialidades, fn($e) => $e['activo'] == 1)),
                'especialidades_inactivas' => count(array_filter($especialidades, fn($e) => $e['activo'] == 0)),
                'total_medicos_sistema' => array_sum(array_column($especialidades, 'total_medicos')),
                'medicos_activos_sistema' => array_sum(array_column($especialidades, 'medicos_activos'))
            ];
            
            if ($incluirEstadisticas) {
                $resumen['total_citas_sistema'] = array_sum(array_column($especialidades, 'total_citas'));
                $resumen['citas_completadas_sistema'] = array_sum(array_column($especialidades, 'citas_completadas'));
                $resumen['citas_futuras_sistema'] = array_sum(array_column($especialidades, 'citas_futuras'));
            }
            
            return [
                'success' => true,
                'message' => 'Especialidades completas obtenidas correctamente',
                'data' => [
                    'especialidades' => $especialidades,
                    'resumen' => $resumen
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error obteniendo especialidades completas: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Obtener médicos por especialidad específica
     */
    private function obtenerMedicosPorEspecialidad($idEspecialidad) {
        try {
            $sql = "SELECT u.id_usuario as id_medico, 
                        CONCAT(u.nombre, ' ', u.apellido) as nombre_completo,
                        u.nombre, u.apellido, u.email, u.telefono, u.cedula,
                        u.activo, u.fecha_registro,
                        s.nombre_sucursal
                    FROM usuarios u
                    INNER JOIN medico_especialidades me ON u.id_usuario = me.id_medico
                    LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal
                    WHERE me.id_especialidad = ? AND u.id_rol = 3
                    ORDER BY u.nombre ASC, u.apellido ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idEspecialidad]);
            
            return $stmt->fetchAll();
            
        } catch (\Exception $e) {
            return [];
        }
    }
}
?>