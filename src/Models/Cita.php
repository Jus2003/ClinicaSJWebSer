<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Cita {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function consultarPorEspecialidadYMedico($idEspecialidad = null, $idMedico = null, $filtros = []) {
        // Validaciones básicas
        if (empty($idMedico) && empty($idEspecialidad)) {
            return ['success' => false, 'message' => 'Debe proporcionar al menos médico o especialidad'];
        }
        
        // Construir consulta base
        $sql = "SELECT c.id_cita, c.fecha_cita, c.hora_cita, c.tipo_cita, c.estado_cita, 
                       c.motivo_consulta, c.fecha_registro,
                       CONCAT(p.nombre, ' ', p.apellido) as nombre_paciente,
                       p.cedula as cedula_paciente,
                       p.telefono as telefono_paciente,
                       CONCAT(m.nombre, ' ', m.apellido) as nombre_medico,
                       e.nombre_especialidad,
                       s.nombre_sucursal
                FROM citas c
                INNER JOIN usuarios p ON c.id_paciente = p.id_usuario
                INNER JOIN usuarios m ON c.id_medico = m.id_usuario
                INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                WHERE 1=1";
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($idEspecialidad)) {
            $sql .= " AND c.id_especialidad = ?";
            $params[] = $idEspecialidad;
        }
        
        if (!empty($idMedico)) {
            $sql .= " AND c.id_medico = ?";
            $params[] = $idMedico;
        }
        
        // Filtros adicionales opcionales
        if (!empty($filtros['estado'])) {
            $sql .= " AND c.estado_cita = ?";
            $params[] = $filtros['estado'];
        }
        
        if (!empty($filtros['tipo'])) {
            $sql .= " AND c.tipo_cita = ?";
            $params[] = $filtros['tipo'];
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND c.fecha_cita >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND c.fecha_cita <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        
        // Ordenar por fecha y hora más recientes
        $sql .= " ORDER BY c.fecha_cita DESC, c.hora_cita DESC";
        
        // Aplicar límite si se especifica
        if (!empty($filtros['limite'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filtros['limite'];
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $citas = $stmt->fetchAll();
            
            // Obtener estadísticas
            $estadisticas = $this->calcularEstadisticasCitas($citas);
            
            return [
                'success' => true,
                'message' => 'Citas consultadas correctamente',
                'data' => [
                    'citas' => $citas,
                    'estadisticas' => $estadisticas,
                    'filtros_aplicados' => [
                        'id_especialidad' => $idEspecialidad,
                        'id_medico' => $idMedico,
                        'filtros_adicionales' => $filtros
                    ]
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error consultando citas: ' . $e->getMessage()
            ];
        }
    }
    
    public function consultarPorRangoFechas($fechaInicio, $fechaFin, $filtros = []) {
        // Validar fechas
        if (empty($fechaInicio) || empty($fechaFin)) {
            return ['success' => false, 'message' => 'Fecha de inicio y fin son requeridas'];
        }
        
        // Validar formato de fechas
        if (!$this->validarFecha($fechaInicio) || !$this->validarFecha($fechaFin)) {
            return ['success' => false, 'message' => 'Formato de fecha inválido. Use YYYY-MM-DD'];
        }
        
        // Validar que fecha inicio no sea mayor que fecha fin
        if ($fechaInicio > $fechaFin) {
            return ['success' => false, 'message' => 'La fecha de inicio no puede ser mayor que la fecha fin'];
        }
        
            $sql = "SELECT c.id_cita, c.fecha_cita, c.hora_cita, c.tipo_cita, c.estado_cita, 
                    c.motivo_consulta, c.fecha_registro,
                    CONCAT(p.nombre, ' ', p.apellido) as nombre_paciente,
                    p.cedula as cedula_paciente,
                    CONCAT(m.nombre, ' ', m.apellido) as nombre_medico,
                    e.nombre_especialidad,
                    s.nombre_sucursal
                FROM citas c
                INNER JOIN usuarios p ON c.id_paciente = p.id_usuario
                INNER JOIN usuarios m ON c.id_medico = m.id_usuario
                INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                WHERE c.fecha_cita BETWEEN ? AND ?";
        
        $params = [$fechaInicio, $fechaFin];
        
        // Filtros adicionales
        if (!empty($filtros['medico'])) {
            $sql .= " AND c.id_medico = ?";
            $params[] = $filtros['medico'];
        }
        
        if (!empty($filtros['especialidad'])) {
            $sql .= " AND c.id_especialidad = ?";
            $params[] = $filtros['especialidad'];
        }
        
        // AGREGAR ESTE FILTRO:
        if (!empty($filtros['paciente'])) {
            $sql .= " AND c.id_paciente = ?";
            $params[] = $filtros['paciente'];
        }
        
        $sql .= " ORDER BY c.fecha_cita ASC, c.hora_cita ASC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $citas = $stmt->fetchAll();
            
            // Agrupar por fechas para mejor visualización
            $citasPorFecha = $this->agruparCitasPorFecha($citas);
            $estadisticas = $this->calcularEstadisticasCitas($citas);
            
            return [
                'success' => true,
                'message' => 'Citas consultadas por rango de fechas',
                'data' => [
                    'rango_fechas' => [
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin' => $fechaFin
                    ],
                    'citas_por_fecha' => $citasPorFecha,
                    'todas_las_citas' => $citas,
                    'estadisticas' => $estadisticas
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error consultando citas por fechas: ' . $e->getMessage()
            ];
        }
    }
    
    private function validarFecha($fecha) {
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
    
    private function calcularEstadisticasCitas($citas) {
        $total = count($citas);
        $estadisticas = [
            'total_citas' => $total,
            'por_estado' => [],
            'por_tipo' => [],
            'por_especialidad' => []
        ];
        
        foreach ($citas as $cita) {
            // Por estado
            $estado = $cita['estado_cita'];
            $estadisticas['por_estado'][$estado] = ($estadisticas['por_estado'][$estado] ?? 0) + 1;
            
            // Por tipo
            $tipo = $cita['tipo_cita'];
            $estadisticas['por_tipo'][$tipo] = ($estadisticas['por_tipo'][$tipo] ?? 0) + 1;
            
            // Por especialidad
            $especialidad = $cita['nombre_especialidad'];
            $estadisticas['por_especialidad'][$especialidad] = ($estadisticas['por_especialidad'][$especialidad] ?? 0) + 1;
        }
        
        return $estadisticas;
    }
    
    private function agruparCitasPorFecha($citas) {
        $grupos = [];
        foreach ($citas as $cita) {
            $fecha = $cita['fecha_cita'];
            if (!isset($grupos[$fecha])) {
                $grupos[$fecha] = [];
            }
            $grupos[$fecha][] = $cita;
        }
        return $grupos;
    }

    public function consultarPorPaciente($idPaciente) {
        $sql = "SELECT c.id_cita, c.fecha_cita, c.hora_cita, c.tipo_cita, c.estado_cita, 
                    c.motivo_consulta, c.observaciones,
                    CONCAT(m.nombre, ' ', m.apellido) as nombre_medico,
                    e.nombre_especialidad,
                    s.nombre_sucursal
                FROM citas c
                INNER JOIN usuarios m ON c.id_medico = m.id_usuario
                INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                WHERE c.id_paciente = ?
                ORDER BY c.fecha_cita DESC, c.hora_cita DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idPaciente]);
            $citas = $stmt->fetchAll();
            
            $estadisticas = $this->calcularEstadisticasCitas($citas);
            
            return [
                'success' => true,
                'message' => 'Citas del paciente obtenidas correctamente',
                'data' => [
                    'citas' => $citas,
                    'estadisticas' => $estadisticas
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error consultando citas del paciente: ' . $e->getMessage()
            ];
        }
    }

    public function listarTodas() {
        $sql = "SELECT c.id_cita, c.fecha_cita, c.hora_cita, c.tipo_cita, c.estado_cita, 
                    c.motivo_consulta,
                    CONCAT(p.nombre, ' ', p.apellido) as nombre_paciente,
                    p.cedula as cedula_paciente,
                    CONCAT(m.nombre, ' ', m.apellido) as nombre_medico,
                    e.nombre_especialidad,
                    s.nombre_sucursal
                FROM citas c
                INNER JOIN usuarios p ON c.id_paciente = p.id_usuario
                INNER JOIN usuarios m ON c.id_medico = m.id_usuario
                INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                ORDER BY c.fecha_cita DESC, c.hora_cita DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $citas = $stmt->fetchAll();
            
            $estadisticas = $this->calcularEstadisticasCitas($citas);
            
            return [
                'success' => true,
                'message' => 'Todas las citas obtenidas correctamente',
                'data' => [
                    'citas' => $citas,
                    'estadisticas' => $estadisticas
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error consultando todas las citas: ' . $e->getMessage()
            ];
        }
    }

}
?>