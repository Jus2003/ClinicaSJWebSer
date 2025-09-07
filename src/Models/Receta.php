<?php
namespace App\Models;

use App\Config\Database;
use App\Services\EmailService;
use PDO;

class Receta {
    private $db;

    public function __construct() {
        // ✅ CORREGIR: Usar la forma correcta como en otros modelos
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Crear nueva receta médica directamente para una cita (SIMPLIFICADO)
     */
    public function crearRecetaPorCita($datos) {
        try {
            $this->db->beginTransaction();

            // 1. Verificar que existe la cita y obtener información completa
            $sqlCita = "
                SELECT c.id_cita, c.fecha_cita, c.hora_cita, c.estado_cita,
                       CONCAT(p.nombre, ' ', p.apellido) as nombre_paciente,
                       p.email as email_paciente, p.id_usuario as id_paciente,
                       CONCAT(m.nombre, ' ', m.apellido) as nombre_medico,
                       e.nombre_especialidad as especialidad,
                       s.nombre_sucursal as sucursal
                FROM citas c
                INNER JOIN usuarios p ON c.id_paciente = p.id_usuario
                INNER JOIN usuarios m ON c.id_medico = m.id_usuario
                INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                WHERE c.id_cita = ?
            ";
            
            $stmt = $this->db->prepare($sqlCita);
            $stmt->execute([$datos['id_cita']]);
            $cita = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cita) {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => 'No se encontró la cita especificada'
                ];
            }

            // 2. Insertar la receta en la nueva tabla auxiliar
            $sqlReceta = "
                INSERT INTO recetas_cita (
                    id_cita, medicamento, concentracion, forma_farmaceutica,
                    dosis, frecuencia, duracion, cantidad, indicaciones_especiales
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $this->db->prepare($sqlReceta);
            $stmt->execute([
                $datos['id_cita'],
                $datos['medicamento'],
                $datos['concentracion'] ?? null,
                $datos['forma_farmaceutica'] ?? null,
                $datos['dosis'],
                $datos['frecuencia'],
                $datos['duracion'],
                $datos['cantidad'],
                $datos['indicaciones_especiales'] ?? null
            ]);

            $idReceta = $this->db->lastInsertId();

            // 3. Obtener la receta completa creada (con código generado automáticamente)
            $sqlRecetaCompleta = "
                SELECT rc.*, 
                       CONCAT(p.nombre, ' ', p.apellido) as nombre_paciente,
                       p.email as email_paciente,
                       CONCAT(m.nombre, ' ', m.apellido) as nombre_medico,
                       c.fecha_cita, c.hora_cita
                FROM recetas_cita rc
                INNER JOIN citas c ON rc.id_cita = c.id_cita
                INNER JOIN usuarios p ON c.id_paciente = p.id_usuario
                INNER JOIN usuarios m ON c.id_medico = m.id_usuario
                WHERE rc.id_receta_cita = ?
            ";

            $stmt = $this->db->prepare($sqlRecetaCompleta);
            $stmt->execute([$idReceta]);
            $recetaCompleta = $stmt->fetch(PDO::FETCH_ASSOC);

            // 4. Crear notificación para el paciente
            $sqlNotificacion = "
                INSERT INTO notificaciones (
                    id_usuario_destinatario, tipo_notificacion, titulo, mensaje, id_referencia
                ) VALUES (?, 'receta_disponible', ?, ?, ?)
            ";

            $tituloNotificacion = "📋 Nueva Receta Médica Disponible";
            $mensajeNotificacion = "Su receta médica para {$datos['medicamento']} está disponible. Código: {$recetaCompleta['codigo_receta']}";

            $stmt = $this->db->prepare($sqlNotificacion);
            $stmt->execute([
                $cita['id_paciente'],
                $tituloNotificacion,
                $mensajeNotificacion,
                $idReceta
            ]);

            // 5. Enviar email al paciente con la receta
            $emailEnviado = false;
            try {
                $emailService = new EmailService();
                $datosReceta = [
                    'codigo_receta' => $recetaCompleta['codigo_receta'],
                    'medicamento' => $recetaCompleta['medicamento'],
                    'concentracion' => $recetaCompleta['concentracion'],
                    'dosis' => $recetaCompleta['dosis'],
                    'frecuencia' => $recetaCompleta['frecuencia'],
                    'duracion' => $recetaCompleta['duracion'],
                    'cantidad' => $recetaCompleta['cantidad'],
                    'indicaciones_especiales' => $recetaCompleta['indicaciones_especiales'],
                    'fecha_emision' => $recetaCompleta['fecha_emision'],
                    'fecha_vencimiento' => $recetaCompleta['fecha_vencimiento'],
                    'nombre_paciente' => $recetaCompleta['nombre_paciente'],
                    'nombre_medico' => $recetaCompleta['nombre_medico'],
                    'fecha_cita' => $recetaCompleta['fecha_cita']
                ];

                $resultadoEmail = $emailService->enviarRecetaMedica(
                    $recetaCompleta['email_paciente'],
                    $recetaCompleta['nombre_paciente'],
                    $datosReceta
                );
                
                $emailEnviado = $resultadoEmail['success'] ?? false;

            } catch (\Exception $e) {
                error_log("Error enviando email de receta: " . $e->getMessage());
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Receta médica creada exitosamente',
                'data' => [
                    'id_receta_cita' => $idReceta,
                    'codigo_receta' => $recetaCompleta['codigo_receta'],
                    'medicamento' => $recetaCompleta['medicamento'],
                    'paciente' => $recetaCompleta['nombre_paciente'],
                    'medico' => $recetaCompleta['nombre_medico'],
                    'fecha_emision' => $recetaCompleta['fecha_emision'],
                    'fecha_vencimiento' => $recetaCompleta['fecha_vencimiento'],
                    'email_enviado' => $emailEnviado,
                    'notificacion_creada' => true,
                    'cita_asociada' => [
                        'id_cita' => $datos['id_cita'],
                        'fecha_cita' => $cita['fecha_cita'],
                        'especialidad' => $cita['especialidad'],
                        'sucursal' => $cita['sucursal']
                    ],
                    'detalles_receta' => [
                        'dosis' => $recetaCompleta['dosis'],
                        'frecuencia' => $recetaCompleta['frecuencia'],
                        'duracion' => $recetaCompleta['duracion'],
                        'cantidad' => $recetaCompleta['cantidad'],
                        'indicaciones' => $recetaCompleta['indicaciones_especiales']
                    ]
                ]
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Error al crear la receta: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener recetas de una cita específica (SIMPLIFICADO)
     */
    public function obtenerRecetasPorCita($idCita) {
        try {
            $sql = "
                SELECT rc.*, 
                       CONCAT(p.nombre, ' ', p.apellido) as nombre_paciente,
                       CONCAT(m.nombre, ' ', m.apellido) as nombre_medico,
                       c.fecha_cita, c.estado_cita,
                       e.nombre_especialidad as especialidad
                FROM recetas_cita rc
                INNER JOIN citas c ON rc.id_cita = c.id_cita
                INNER JOIN usuarios p ON c.id_paciente = p.id_usuario
                INNER JOIN usuarios m ON c.id_medico = m.id_usuario
                INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                WHERE rc.id_cita = ?
                ORDER BY rc.fecha_emision DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idCita]);
            $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'message' => count($recetas) > 0 ? 'Recetas encontradas' : 'No hay recetas para esta cita',
                'data' => [
                    'id_cita' => $idCita,
                    'total_recetas' => count($recetas),
                    'recetas' => $recetas
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener las recetas: ' . $e->getMessage()
            ];
        }
    }
}