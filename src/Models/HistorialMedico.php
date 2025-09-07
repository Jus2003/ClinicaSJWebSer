<?php
namespace App\Models;

use App\Config\Database;  // ✅ AGREGAR ESTA LÍNEA
use PDO;
use Exception;

class HistorialMedico {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Obtener todas las citas completadas con sus recetas
     */
    public function obtenerHistorialCompleto($limite = 50, $offset = 0) {
        try {
            $sql = "
                SELECT 
                    c.id_cita,
                    c.fecha_cita,
                    c.hora_cita,
                    c.tipo_cita,
                    c.estado_cita,
                    c.motivo_consulta,
                    c.observaciones,
                    
                    -- Datos del paciente
                    CONCAT(p.nombre, ' ', p.apellido) as nombre_paciente,
                    p.cedula as cedula_paciente,
                    p.email as email_paciente,
                    p.telefono as telefono_paciente,
                    
                    -- Datos del médico
                    CONCAT(m.nombre, ' ', m.apellido) as nombre_medico,
                    m.email as email_medico,
                    
                    -- Especialidad y sucursal
                    e.nombre_especialidad,
                    s.nombre_sucursal,
                    s.direccion as direccion_sucursal,
                    
                    -- Contar recetas
                    (SELECT COUNT(*) FROM recetas_cita rc WHERE rc.id_cita = c.id_cita) as total_recetas
                    
                FROM citas c
                INNER JOIN usuarios p ON c.id_paciente = p.id_usuario
                INNER JOIN usuarios m ON c.id_medico = m.id_usuario
                INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                WHERE c.estado_cita = 'completada'
                ORDER BY c.fecha_cita DESC, c.hora_cita DESC
                LIMIT ? OFFSET ?
                ORDER BY c.id_cita DESC, c.fecha_cita DESC, c.hora_cita DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limite, $offset]);
            $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Para cada cita, obtener sus recetas
            foreach ($citas as &$cita) {
                $cita['recetas'] = $this->obtenerRecetasPorCita($cita['id_cita']);
            }
            
            return [
                'success' => true,
                'message' => 'Historial médico obtenido exitosamente',
                'data' => [
                    'citas' => $citas,
                    'total_citas' => count($citas),
                    'limite' => $limite,
                    'offset' => $offset
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error obteniendo historial: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener historial de una cita específica por ID
     */
    public function obtenerHistorialPorCita($idCita) {
        try {
            $sql = "
                SELECT 
                    c.id_cita,
                    c.fecha_cita,
                    c.hora_cita,
                    c.tipo_cita,
                    c.estado_cita,
                    c.motivo_consulta,
                    c.observaciones,
                    c.fecha_registro,
                    
                    -- Datos del paciente
                    p.id_usuario as id_paciente,
                    CONCAT(p.nombre, ' ', p.apellido) as nombre_paciente,
                    p.cedula as cedula_paciente,
                    p.email as email_paciente,
                    p.telefono as telefono_paciente,
                    p.fecha_nacimiento,
                    p.genero,
                    
                    -- Datos del médico
                    m.id_usuario as id_medico,
                    CONCAT(m.nombre, ' ', m.apellido) as nombre_medico,
                    m.email as email_medico,
                    
                    -- Especialidad y sucursal
                    e.id_especialidad,
                    e.nombre_especialidad,
                    e.precio_consulta,
                    s.id_sucursal,
                    s.nombre_sucursal,
                    s.direccion as direccion_sucursal,
                    s.telefono as telefono_sucursal
                    
                FROM citas c
                INNER JOIN usuarios p ON c.id_paciente = p.id_usuario
                INNER JOIN usuarios m ON c.id_medico = m.id_usuario
                INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                WHERE c.id_cita = ? AND c.estado_cita = 'completada'
                ORDER BY rc.id_receta_cita DESC, rc.fecha_emision DESC
            ";
                        
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idCita]);
            $cita = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cita) {
                return [
                    'success' => false,
                    'message' => 'Cita no encontrada o no está completada'
                ];
            }
            
            // Obtener recetas de la cita
            $cita['recetas'] = $this->obtenerRecetasPorCita($idCita);
            $cita['total_recetas'] = count($cita['recetas']);
            
            return [
                'success' => true,
                'message' => 'Historial de cita obtenido exitosamente',
                'data' => $cita
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error obteniendo historial de cita: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener historial por cédula del paciente
     */
    public function obtenerHistorialPorCedula($cedula) {
        try {
            // Primero buscar el paciente
            $sqlPaciente = "SELECT id_usuario, nombre, apellido, email FROM usuarios WHERE cedula = ? AND id_rol = 4 AND activo = 1";
            $stmt = $this->db->prepare($sqlPaciente);
            $stmt->execute([$cedula]);
            $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$paciente) {
                return [
                    'success' => false,
                    'message' => 'Paciente no encontrado con esa cédula'
                ];
            }
            
            $sql = "
                SELECT 
                    c.id_cita,
                    c.fecha_cita,
                    c.hora_cita,
                    c.tipo_cita,
                    c.estado_cita,
                    c.motivo_consulta,
                    c.observaciones,
                    
                    -- Datos del médico
                    CONCAT(m.nombre, ' ', m.apellido) as nombre_medico,
                    
                    -- Especialidad y sucursal
                    e.nombre_especialidad,
                    e.precio_consulta,
                    s.nombre_sucursal,
                    
                    -- Contar recetas
                    (SELECT COUNT(*) FROM recetas_cita rc WHERE rc.id_cita = c.id_cita) as total_recetas
                    
                FROM citas c
                INNER JOIN usuarios m ON c.id_medico = m.id_usuario
                INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                WHERE c.id_paciente = ? AND c.estado_cita = 'completada'
                ORDER BY c.fecha_cita DESC, c.hora_cita DESC
                ORDER BY c.id_cita DESC, c.fecha_cita DESC, c.hora_cita DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$paciente['id_usuario']]);
            $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Para cada cita, obtener sus recetas
            foreach ($citas as &$cita) {
                $cita['recetas'] = $this->obtenerRecetasPorCita($cita['id_cita']);
            }
            
            return [
                'success' => true,
                'message' => 'Historial del paciente obtenido exitosamente',
                'data' => [
                    'paciente' => [
                        'id' => $paciente['id_usuario'],
                        'nombre_completo' => $paciente['nombre'] . ' ' . $paciente['apellido'],
                        'cedula' => $cedula,
                        'email' => $paciente['email']
                    ],
                    'citas' => $citas,
                    'total_citas_completadas' => count($citas),
                    'total_recetas' => array_sum(array_column($citas, 'total_recetas'))
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error obteniendo historial por cédula: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener recetas de una cita específica
     */
private function obtenerRecetasPorCita($idCita) {
    try {
        $sql = "
            SELECT 
                rc.id_receta_cita,
                rc.codigo_receta,
                rc.medicamento,
                rc.concentracion,
                rc.forma_farmaceutica,
                rc.dosis,
                rc.frecuencia,
                rc.duracion,
                rc.cantidad,
                rc.indicaciones_especiales,
                rc.fecha_emision,
                rc.fecha_vencimiento,
                rc.estado,
                // ... más campos
                
            FROM recetas_cita rc  // ✅ Aquí está usando recetas_cita
            WHERE rc.id_cita = ?
            ORDER BY rc.fecha_emision DESC
        ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idCita]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }

    /**
 * Obtener historial por ID del paciente
 */
public function obtenerHistorialPorIdPaciente($idPaciente) {
    try {
        // Primero verificar que el paciente existe
        $sqlPaciente = "SELECT id_usuario, nombre, apellido, email, cedula FROM usuarios WHERE id_usuario = ? AND id_rol = 4 AND activo = 1";
        $stmt = $this->db->prepare($sqlPaciente);
        $stmt->execute([$idPaciente]);
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$paciente) {
            return [
                'success' => false,
                'message' => 'Paciente no encontrado'
            ];
        }
        
        $sql = "
                SELECT 
                    c.id_cita,
                    c.fecha_cita,
                    c.hora_cita,
                    c.tipo_cita,
                    c.estado_cita,
                    c.motivo_consulta,
                    c.observaciones,
                    
                    -- Datos del médico
                    CONCAT(m.nombre, ' ', m.apellido) as nombre_medico,
                    
                    -- Especialidad y sucursal
                    e.nombre_especialidad,
                    e.precio_consulta,
                    s.nombre_sucursal,
                    
                    -- Contar recetas
                    (SELECT COUNT(*) FROM recetas_cita rc WHERE rc.id_cita = c.id_cita) as total_recetas
                    
                FROM citas c
                INNER JOIN usuarios m ON c.id_medico = m.id_usuario
                INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                WHERE c.id_paciente = ? AND c.estado_cita = 'completada'
                ORDER BY c.id_cita DESC, c.fecha_cita DESC, c.hora_cita DESC
            ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idPaciente]);
        $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Para cada cita, obtener sus recetas
        foreach ($citas as &$cita) {
            $cita['recetas'] = $this->obtenerRecetasPorCita($cita['id_cita']);
        }
        
        return [
            'success' => true,
            'message' => 'Historial del paciente obtenido exitosamente',
            'data' => [
                'paciente' => [
                    'id' => $paciente['id_usuario'],
                    'nombre_completo' => $paciente['nombre'] . ' ' . $paciente['apellido'],
                    'cedula' => $paciente['cedula'],
                    'email' => $paciente['email']
                ],
                'citas' => $citas,
                'total_citas_completadas' => count($citas),
                'total_recetas' => array_sum(array_column($citas, 'total_recetas'))
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error obteniendo historial por ID de paciente: ' . $e->getMessage()
        ];
    }
}
}
?>