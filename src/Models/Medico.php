<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Medico {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function listarTodos() {
        $sql = "SELECT DISTINCT u.id_usuario, u.nombre, u.apellido, u.email, u.telefono,
                       u.cedula, s.nombre_sucursal,
                       GROUP_CONCAT(e.nombre_especialidad SEPARATOR ', ') as especialidades,
                       COUNT(DISTINCT me.id_especialidad) as total_especialidades
                FROM usuarios u
                INNER JOIN medico_especialidades me ON u.id_usuario = me.id_medico
                INNER JOIN especialidades e ON me.id_especialidad = e.id_especialidad
                LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal
                WHERE u.id_rol = 3 AND u.activo = 1 AND me.activo = 1
                GROUP BY u.id_usuario
                ORDER BY u.nombre, u.apellido";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $medicos = $stmt->fetchAll();
            
            $resultado = [];
            foreach ($medicos as $medico) {
                $resultado[] = [
                    'id_medico' => $medico['id_usuario'],
                    'nombre_completo' => $medico['nombre'] . ' ' . $medico['apellido'],
                    'nombre' => $medico['nombre'],
                    'apellido' => $medico['apellido'],
                    'email' => $medico['email'],
                    'telefono' => $medico['telefono'],
                    'cedula' => $medico['cedula'],
                    'sucursal' => $medico['nombre_sucursal'],
                    'especialidades' => $medico['especialidades'],
                    'total_especialidades' => $medico['total_especialidades']
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Médicos obtenidos correctamente',
                'data' => $resultado
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error obteniendo médicos: ' . $e->getMessage()
            ];
        }
    }
    
    public function listarPorEspecialidad($idEspecialidad) {
        $sql = "SELECT DISTINCT u.id_usuario, u.nombre, u.apellido, u.email, u.telefono,
                       u.cedula, s.nombre_sucursal, e.nombre_especialidad
                FROM usuarios u
                INNER JOIN medico_especialidades me ON u.id_usuario = me.id_medico
                INNER JOIN especialidades e ON me.id_especialidad = e.id_especialidad
                LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal
                WHERE u.id_rol = 3 AND u.activo = 1 AND me.activo = 1 
                      AND me.id_especialidad = ?
                ORDER BY u.nombre, u.apellido";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idEspecialidad]);
            $medicos = $stmt->fetchAll();
            
            $resultado = [];
            foreach ($medicos as $medico) {
                $resultado[] = [
                    'id_medico' => $medico['id_usuario'],
                    'nombre_completo' => $medico['nombre'] . ' ' . $medico['apellido'],
                    'nombre' => $medico['nombre'],
                    'apellido' => $medico['apellido'],
                    'email' => $medico['email'],
                    'telefono' => $medico['telefono'],
                    'cedula' => $medico['cedula'],
                    'sucursal' => $medico['nombre_sucursal'],
                    'especialidad' => $medico['nombre_especialidad']
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Médicos por especialidad obtenidos correctamente',
                'data' => $resultado
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error obteniendo médicos por especialidad: ' . $e->getMessage()
            ];
        }
    }
}
?>