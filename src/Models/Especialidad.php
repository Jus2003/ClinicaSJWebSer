<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Especialidad {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function listarTodas() {
        $sql = "SELECT e.id_especialidad, e.nombre_especialidad, e.descripcion,
                       e.duracion_cita_minutos, e.precio_base, e.activo,
                       COUNT(me.id_medico) as total_medicos
                FROM especialidades e
                LEFT JOIN medico_especialidades me ON e.id_especialidad = me.id_especialidad 
                    AND me.activo = 1
                WHERE e.activo = 1
                GROUP BY e.id_especialidad
                ORDER BY e.nombre_especialidad ASC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $especialidades = $stmt->fetchAll();
            
            $resultado = [];
            foreach ($especialidades as $especialidad) {
                $resultado[] = [
                    'id_especialidad' => $especialidad['id_especialidad'],
                    'nombre_especialidad' => $especialidad['nombre_especialidad'],
                    'descripcion' => $especialidad['descripcion'],
                    'duracion_cita_minutos' => $especialidad['duracion_cita_minutos'],
                    'precio_base' => $especialidad['precio_base'],
                    'total_medicos' => $especialidad['total_medicos']
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Especialidades obtenidas correctamente',
                'data' => $resultado
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error obteniendo especialidades: ' . $e->getMessage()
            ];
        }
    }
}
?>