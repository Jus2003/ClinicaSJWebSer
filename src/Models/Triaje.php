<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use Exception;

class Triaje {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Obtener todas las preguntas de triaje activas
    public function obtenerPreguntasTriaje() {
        $sql = "SELECT * FROM preguntas_triaje 
                WHERE activo = 1 
                ORDER BY orden ASC, id_pregunta ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Guardar respuesta de triaje
    public function guardarRespuestaTriaje($datos) {
        // Primero verificar si ya existe la respuesta
        $sqlCheck = "SELECT id_respuesta FROM triaje_respuestas 
                     WHERE id_cita = ? AND id_pregunta = ?";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute([$datos['id_cita'], $datos['id_pregunta']]);
        
        if ($stmtCheck->fetch()) {
            // Actualizar respuesta existente
            $sql = "UPDATE triaje_respuestas SET 
                    respuesta = ?, valor_numerico = ?, fecha_respuesta = NOW()
                    WHERE id_cita = ? AND id_pregunta = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $datos['respuesta'],
                $datos['valor_numerico'] ?? null,
                $datos['id_cita'],
                $datos['id_pregunta']
            ]);
        } else {
            // Insertar nueva respuesta
            $sql = "INSERT INTO triaje_respuestas 
                    (id_cita, id_pregunta, respuesta, valor_numerico, 
                     tipo_triaje, id_usuario_registro) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $datos['id_cita'],
                $datos['id_pregunta'],
                $datos['respuesta'],
                $datos['valor_numerico'] ?? null,
                $datos['tipo_triaje'] ?? 'digital',
                $datos['id_usuario_registro']
            ]);
        }
    }
    
    // Guardar múltiples respuestas de triaje (transacción)
    public function guardarRespuestasTriaje($id_cita, $respuestas, $id_usuario_registro, $tipo_triaje = 'digital') {
        try {
            $this->db->beginTransaction();
            
            foreach ($respuestas as $respuesta) {
                $datos = [
                    'id_cita' => $id_cita,
                    'id_pregunta' => $respuesta['id_pregunta'],
                    'respuesta' => $respuesta['respuesta'],
                    'valor_numerico' => $respuesta['valor_numerico'] ?? null,
                    'tipo_triaje' => $tipo_triaje,
                    'id_usuario_registro' => $id_usuario_registro
                ];
                
                if (!$this->guardarRespuestaTriaje($datos)) {
                    throw new Exception("Error al guardar respuesta pregunta " . $respuesta['id_pregunta']);
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    // Obtener respuestas de triaje por cita
    public function obtenerRespuestasTriajePorCita($id_cita) {
        $sql = "SELECT tr.*, pt.pregunta, pt.tipo_pregunta, pt.opciones_json,
                       CONCAT(u.nombre, ' ', u.apellido) as usuario_registro
                FROM triaje_respuestas tr
                JOIN preguntas_triaje pt ON tr.id_pregunta = pt.id_pregunta  
                LEFT JOIN usuarios u ON tr.id_usuario_registro = u.id_usuario
                WHERE tr.id_cita = ?
                ORDER BY pt.orden ASC, pt.id_pregunta ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_cita]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Verificar si una cita tiene triaje completo
    public function tieneTriajeCompleto($id_cita) {
        // Contar preguntas obligatorias
        $sqlObligatorias = "SELECT COUNT(*) FROM preguntas_triaje 
                           WHERE activo = 1 AND obligatoria = 1";
        $stmtOb = $this->db->prepare($sqlObligatorias);
        $stmtOb->execute();
        $totalObligatorias = $stmtOb->fetchColumn();
        
        // Contar respuestas de preguntas obligatorias para esta cita
        $sqlRespuestas = "SELECT COUNT(*) FROM triaje_respuestas tr
                          JOIN preguntas_triaje pt ON tr.id_pregunta = pt.id_pregunta
                          WHERE tr.id_cita = ? AND pt.obligatoria = 1 AND pt.activo = 1";
        $stmtResp = $this->db->prepare($sqlRespuestas);
        $stmtResp->execute([$id_cita]);
        $respuestasCompletas = $stmtResp->fetchColumn();
        
        return $respuestasCompletas >= $totalObligatorias;
    }
    
    // Obtener estadísticas de triaje
    public function obtenerEstadisticasTriaje($id_cita) {
        $sql = "SELECT 
                    COUNT(*) as total_preguntas_respondidas,
                    COUNT(CASE WHEN pt.obligatoria = 1 THEN 1 END) as obligatorias_respondidas,
                    tr.tipo_triaje,
                    MIN(tr.fecha_respuesta) as fecha_inicio,
                    MAX(tr.fecha_respuesta) as fecha_fin
                FROM triaje_respuestas tr
                JOIN preguntas_triaje pt ON tr.id_pregunta = pt.id_pregunta
                WHERE tr.id_cita = ?
                GROUP BY tr.tipo_triaje";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_cita]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Eliminar respuestas de triaje (para rehacer)
    public function eliminarRespuestasTriaje($id_cita, $id_usuario_registro = null) {
        $sql = "DELETE FROM triaje_respuestas WHERE id_cita = ?";
        $params = [$id_cita];
        
        if ($id_usuario_registro) {
            $sql .= " AND id_usuario_registro = ?";
            $params[] = $id_usuario_registro;
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
?>