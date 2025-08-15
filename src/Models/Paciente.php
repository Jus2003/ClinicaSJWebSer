<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Paciente {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function buscarPorCedula($cedula) {
        // Validar formato de cédula ecuatoriana (10 dígitos)
        if (!preg_match('/^\d{10}$/', $cedula)) {
            return ['success' => false, 'message' => 'Formato de cédula inválido. Debe tener 10 dígitos'];
        }
        
        $sql = "SELECT u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono, 
                       u.fecha_nacimiento, u.genero, u.direccion, u.fecha_registro,
                       s.nombre_sucursal
                FROM usuarios u 
                LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal 
                WHERE u.cedula = ? AND u.id_rol = 4 AND u.activo = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cedula]);
        $paciente = $stmt->fetch();
        
        if ($paciente) {
            // Calcular edad si tiene fecha de nacimiento
            $edad = null;
            if ($paciente['fecha_nacimiento']) {
                $fechaNac = new \DateTime($paciente['fecha_nacimiento']);
                $hoy = new \DateTime();
                $edad = $fechaNac->diff($hoy)->y;
            }
            
            return [
                'success' => true,
                'message' => 'Paciente encontrado',
                'data' => [
                    'id_paciente' => $paciente['id_usuario'],
                    'cedula' => $paciente['cedula'],
                    'nombre_completo' => $paciente['nombre'] . ' ' . $paciente['apellido'],
                    'nombre' => $paciente['nombre'],
                    'apellido' => $paciente['apellido'],
                    'email' => $paciente['email'],
                    'telefono' => $paciente['telefono'],
                    'fecha_nacimiento' => $paciente['fecha_nacimiento'],
                    'edad' => $edad,
                    'genero' => $paciente['genero'],
                    'direccion' => $paciente['direccion'],
                    'sucursal' => $paciente['nombre_sucursal'],
                    'fecha_registro' => $paciente['fecha_registro']
                ]
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Paciente no encontrado con la cédula proporcionada'
            ];
        }
    }
    
    public function obtenerHistorialCompleto($idPaciente) {
        try {
            // Verificar que el paciente existe
            $sqlPaciente = "SELECT u.*, s.nombre_sucursal 
                            FROM usuarios u 
                            LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal 
                            WHERE u.id_usuario = ? AND u.id_rol = 4 AND u.activo = 1";
            
            $stmt = $this->db->prepare($sqlPaciente);
            $stmt->execute([$idPaciente]);
            $paciente = $stmt->fetch();
            
            if (!$paciente) {
                return ['success' => false, 'message' => 'Paciente no encontrado'];
            }
            
            // Obtener citas básicas primero
            $sqlCitas = "SELECT c.id_cita, c.fecha_cita, c.hora_cita, c.tipo_cita, 
                                c.estado_cita, c.motivo_consulta,
                                e.nombre_especialidad,
                                s.nombre_sucursal as sucursal_cita,
                                CONCAT(medico.nombre, ' ', medico.apellido) as nombre_medico
                         FROM citas c
                         LEFT JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                         LEFT JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                         LEFT JOIN usuarios medico ON c.id_medico = medico.id_usuario
                         WHERE c.id_paciente = ?
                         ORDER BY c.fecha_cita DESC, c.hora_cita DESC";
            
            $stmt = $this->db->prepare($sqlCitas);
            $stmt->execute([$idPaciente]);
            $citas = $stmt->fetchAll();
            
            // Organizar los datos de forma simple
            $historial = [];
            foreach ($citas as $cita) {
                $historial[] = [
                    'id_cita' => $cita['id_cita'],
                    'fecha_cita' => $cita['fecha_cita'],
                    'hora_cita' => $cita['hora_cita'],
                    'tipo_cita' => $cita['tipo_cita'],
                    'estado_cita' => $cita['estado_cita'],
                    'motivo_consulta' => $cita['motivo_consulta'],
                    'especialidad' => $cita['nombre_especialidad'],
                    'medico' => $cita['nombre_medico'],
                    'sucursal' => $cita['sucursal_cita']
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Historial clínico obtenido correctamente',
                'data' => [
                    'paciente' => [
                        'id' => $paciente['id_usuario'],
                        'nombre_completo' => $paciente['nombre'] . ' ' . $paciente['apellido'],
                        'cedula' => $paciente['cedula'],
                        'email' => $paciente['email'],
                        'telefono' => $paciente['telefono'],
                        'fecha_nacimiento' => $paciente['fecha_nacimiento'],
                        'genero' => $paciente['genero'],
                        'direccion' => $paciente['direccion']
                    ],
                    'total_citas' => count($citas),
                    'historial_citas' => $historial
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false, 
                'message' => 'Error obteniendo historial: ' . $e->getMessage()
            ];
        }
    }
}
?>