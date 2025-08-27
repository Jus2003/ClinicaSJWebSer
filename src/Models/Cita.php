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

    // Agregar este método al modelo Cita existente

    /**
 * Consultar una cita específica por ID
 */
    public function consultarPorId($idCita) {
        if (empty($idCita) || !is_numeric($idCita)) {
            return [
                'success' => false, 
                'message' => 'ID de cita inválido'
            ];
        }
        
        $sql = "SELECT c.id_cita, c.fecha_cita, c.hora_cita, c.tipo_cita, c.estado_cita, 
                c.motivo_consulta, c.fecha_registro, c.observaciones,
                CONCAT(p.nombre, ' ', p.apellido) as nombre_paciente,
                p.cedula as cedula_paciente, p.telefono as telefono_paciente,
                p.email as email_paciente,
                CONCAT(m.nombre, ' ', m.apellido) as nombre_medico,
                m.telefono as telefono_medico,
                e.nombre_especialidad,
                s.nombre_sucursal, s.direccion as direccion_sucursal
            FROM citas c
            INNER JOIN usuarios p ON c.id_paciente = p.id_usuario
            INNER JOIN usuarios m ON c.id_medico = m.id_usuario
            INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
            INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
            WHERE c.id_cita = ?";
            
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idCita]);
            $cita = $stmt->fetch();
            
            if (!$cita) {
                return [
                    'success' => false,
                    'message' => 'No se encontró la cita con ID: ' . $idCita
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Cita encontrada exitosamente',
                'data' => $cita
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error consultando la cita: ' . $e->getMessage()
            ];
        }
    }

    public function buscarPorFiltros($filtros) {
        try {
            $sql = "SELECT c.id_cita, c.fecha_cita, c.hora_cita, c.tipo_cita, c.estado_cita, 
                        c.motivo_consulta, c.observaciones, c.fecha_registro,
                        CONCAT(p.nombre, ' ', p.apellido) as nombre_paciente,
                        p.cedula as cedula_paciente,
                        p.telefono as telefono_paciente,
                        CONCAT(m.nombre, ' ', m.apellido) as nombre_medico,
                        m.telefono as telefono_medico,
                        e.nombre_especialidad,
                        s.nombre_sucursal
                    FROM citas c
                    INNER JOIN usuarios p ON c.id_paciente = p.id_usuario
                    INNER JOIN usuarios m ON c.id_medico = m.id_usuario
                    INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                    INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                    WHERE 1=1";
            
            $params = [];
            
            // Aplicar filtros dinámicamente
            if (!empty($filtros['especialidad'])) {
                $sql .= " AND c.id_especialidad = ?";
                $params[] = $filtros['especialidad'];
            }
            
            if (!empty($filtros['medico'])) {
                $sql .= " AND c.id_medico = ?";
                $params[] = $filtros['medico'];
            }
            
            if (!empty($filtros['paciente'])) {
                $sql .= " AND c.id_paciente = ?";
                $params[] = $filtros['paciente'];
            }
            
            if (!empty($filtros['estado'])) {
                $sql .= " AND c.estado_cita = ?";
                $params[] = $filtros['estado'];
            }
            
            if (!empty($filtros['tipo_cita'])) {
                $sql .= " AND c.tipo_cita = ?";
                $params[] = $filtros['tipo_cita'];
            }
            
            $sql .= " ORDER BY c.fecha_cita DESC, c.hora_cita DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $citas = $stmt->fetchAll();
            
            if (empty($citas)) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron citas con los filtros especificados',
                    'data' => null
                ];
            }
            
            // Calcular estadísticas
            $estadisticas = $this->calcularEstadisticasCitas($citas);
            
            return [
                'success' => true,
                'message' => 'Citas encontradas correctamente',
                'data' => [
                    'citas' => $citas,
                    'estadisticas' => $estadisticas,
                    'total_encontradas' => count($citas)
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error buscando citas: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function consultarPorMedico($idMedico) {
        try {
            $sql = "SELECT c.id_cita, c.fecha_cita, c.hora_cita, c.tipo_cita, c.estado_cita, 
                        c.motivo_consulta, c.observaciones, c.fecha_registro,
                        CONCAT(p.nombre, ' ', p.apellido) as nombre_paciente,
                        p.cedula as cedula_paciente,
                        p.telefono as telefono_paciente,
                        p.email as email_paciente,
                        e.nombre_especialidad,
                        s.nombre_sucursal
                    FROM citas c
                    INNER JOIN usuarios p ON c.id_paciente = p.id_usuario
                    INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                    INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                    WHERE c.id_medico = ?
                    ORDER BY c.fecha_cita DESC, c.hora_cita DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idMedico]);
            $citas = $stmt->fetchAll();
            
            if (empty($citas)) {
                return [
                    'success' => true,
                    'message' => 'El médico no tiene citas registradas',
                    'data' => [
                        'citas' => [],
                        'estadisticas' => [
                            'total_citas' => 0,
                            'por_estado' => [],
                            'por_tipo' => [],
                            'por_especialidad' => []
                        ]
                    ]
                ];
            }
            
            // Calcular estadísticas
            $estadisticas = $this->calcularEstadisticasCitas($citas);
            
            return [
                'success' => true,
                'message' => 'Citas del médico obtenidas correctamente',
                'data' => [
                    'citas' => $citas,
                    'estadisticas' => $estadisticas,
                    'total_encontradas' => count($citas)
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error consultando citas del médico: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function consultarPorRangoFechasYUsuario($fechaInicio, $fechaFin, $filtros) {
        try {
            $sql = "SELECT c.id_cita, c.fecha_cita, c.hora_cita, c.tipo_cita, c.estado_cita, 
                        c.motivo_consulta, c.observaciones, c.fecha_registro,
                        CONCAT(p.nombre, ' ', p.apellido) as nombre_paciente,
                        p.cedula as cedula_paciente,
                        p.telefono as telefono_paciente,
                        p.email as email_paciente,
                        CONCAT(m.nombre, ' ', m.apellido) as nombre_medico,
                        m.cedula as cedula_medico,
                        m.telefono as telefono_medico,
                        e.nombre_especialidad,
                        s.nombre_sucursal,
                        s.direccion as direccion_sucursal
                    FROM citas c
                    INNER JOIN usuarios p ON c.id_paciente = p.id_usuario
                    INNER JOIN usuarios m ON c.id_medico = m.id_usuario
                    INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                    INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                    WHERE c.fecha_cita BETWEEN ? AND ?";
            
            $params = [$fechaInicio, $fechaFin];
            
            // Aplicar filtros dinámicamente
            if (!empty($filtros['paciente'])) {
                $sql .= " AND c.id_paciente = ?";
                $params[] = $filtros['paciente'];
            }
            
            if (!empty($filtros['medico'])) {
                $sql .= " AND c.id_medico = ?";
                $params[] = $filtros['medico'];
            }
            
            if (!empty($filtros['especialidad'])) {
                $sql .= " AND c.id_especialidad = ?";
                $params[] = $filtros['especialidad'];
            }
            
            if (!empty($filtros['estado'])) {
                $sql .= " AND c.estado_cita = ?";
                $params[] = $filtros['estado'];
            }
            
            if (!empty($filtros['tipo_cita'])) {
                $sql .= " AND c.tipo_cita = ?";
                $params[] = $filtros['tipo_cita'];
            }
            
            $sql .= " ORDER BY c.fecha_cita ASC, c.hora_cita ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $citas = $stmt->fetchAll();
            
            if (empty($citas)) {
                return [
                    'success' => true,
                    'message' => 'No se encontraron citas en el rango de fechas especificado',
                    'data' => [
                        'rango_fechas' => [
                            'fecha_inicio' => $fechaInicio,
                            'fecha_fin' => $fechaFin
                        ],
                        'citas_por_fecha' => [],
                        'todas_las_citas' => [],
                        'estadisticas' => [
                            'total_citas' => 0,
                            'por_estado' => [],
                            'por_tipo' => [],
                            'por_especialidad' => []
                        ]
                    ]
                ];
            }
            
            // Agrupar por fechas para mejor visualización
            $citasPorFecha = $this->agruparCitasPorFecha($citas);
            $estadisticas = $this->calcularEstadisticasCitas($citas);
            
            return [
                'success' => true,
                'message' => 'Citas encontradas correctamente',
                'data' => [
                    'rango_fechas' => [
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin' => $fechaFin
                    ],
                    'citas_por_fecha' => $citasPorFecha,
                    'todas_las_citas' => $citas,
                    'estadisticas' => $estadisticas,
                    'total_encontradas' => count($citas)
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error consultando citas por fechas y usuario: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

     public function listarTodasCompletas($filtros = [], $limite = 100, $pagina = 1) {
        try {
            $sql = "SELECT c.id_cita, c.fecha_cita, c.hora_cita, c.tipo_cita, c.estado_cita, 
                        c.motivo_consulta, c.observaciones, c.fecha_registro,
                        
                        -- Información del paciente
                        CONCAT(p.nombre, ' ', p.apellido) as nombre_paciente,
                        p.cedula as cedula_paciente,
                        p.telefono as telefono_paciente,
                        p.email as email_paciente,
                        
                        -- Información del médico
                        CONCAT(m.nombre, ' ', m.apellido) as nombre_medico,
                        m.cedula as cedula_medico,
                        m.telefono as telefono_medico,
                        
                        -- Información adicional
                        e.nombre_especialidad,
                        s.nombre_sucursal,
                        s.direccion as direccion_sucursal,
                        s.telefono as telefono_sucursal
                        
                    FROM citas c
                    INNER JOIN usuarios p ON c.id_paciente = p.id_usuario
                    INNER JOIN usuarios m ON c.id_medico = m.id_usuario
                    INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                    INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                    WHERE 1=1";
            
            $params = [];
            
            // Aplicar filtros dinámicamente
            if (!empty($filtros['estado'])) {
                $sql .= " AND c.estado_cita = ?";
                $params[] = $filtros['estado'];
            }
            
            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND c.fecha_cita >= ?";
                $params[] = $filtros['fecha_desde'];
            }
            
            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND c.fecha_cita <= ?";
                $params[] = $filtros['fecha_hasta'];
            }
            
            if (!empty($filtros['especialidad'])) {
                $sql .= " AND c.id_especialidad = ?";
                $params[] = $filtros['especialidad'];
            }
            
            if (!empty($filtros['sucursal'])) {
                $sql .= " AND c.id_sucursal = ?";
                $params[] = $filtros['sucursal'];
            }
            
            // Ordenar por fecha y hora más recientes primero
            $sql .= " ORDER BY c.fecha_cita DESC, c.hora_cita DESC";
            
            // Contar total de registros sin límite
            $sqlCount = "SELECT COUNT(*) as total
                        FROM citas c
                        INNER JOIN usuarios p ON c.id_paciente = p.id_usuario
                        INNER JOIN usuarios m ON c.id_medico = m.id_usuario
                        INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                        INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                        WHERE 1=1";
            
            // Aplicar los mismos filtros al conteo
            if (!empty($filtros['estado'])) {
                $sqlCount .= " AND c.estado_cita = ?";
            }
            if (!empty($filtros['fecha_desde'])) {
                $sqlCount .= " AND c.fecha_cita >= ?";
            }
            if (!empty($filtros['fecha_hasta'])) {
                $sqlCount .= " AND c.fecha_cita <= ?";
            }
            if (!empty($filtros['especialidad'])) {
                $sqlCount .= " AND c.id_especialidad = ?";
            }
            if (!empty($filtros['sucursal'])) {
                $sqlCount .= " AND c.id_sucursal = ?";
            }
            
            $stmtCount = $this->db->prepare($sqlCount);
            $stmtCount->execute($params);
            $totalRegistros = $stmtCount->fetch()['total'];
            
            // Aplicar paginación
            $offset = ($pagina - 1) * $limite;
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limite;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $citas = $stmt->fetchAll();
            
            // Calcular estadísticas
            $estadisticas = $this->calcularEstadisticasCompletas($citas);
            
            // Estados disponibles para cambios
            $estadosDisponibles = [
                'programada' => 'Programada',
                'confirmada' => 'Confirmada',
                'en_proceso' => 'En Proceso',
                'completada' => 'Completada',
                'cancelada' => 'Cancelada',
                'no_asistio' => 'No Asistió',
                'reprogramada' => 'Reprogramada'
            ];
            
            return [
                'success' => true,
                'message' => 'Todas las citas obtenidas correctamente',
                'data' => [
                    'citas' => $citas,
                    'total_registros' => $totalRegistros,
                    'estadisticas' => $estadisticas,
                    'estados_disponibles' => $estadosDisponibles
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error consultando todas las citas: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Calcular estadísticas más completas
     */
    private function calcularEstadisticasCompletas($citas) {
        $total = count($citas);
        $estadisticas = [
            'total_citas' => $total,
            'por_estado' => [],
            'por_tipo' => [],
            'por_especialidad' => [],
            'por_sucursal' => [],
            'por_fecha' => []
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
            
            // Por sucursal
            $sucursal = $cita['nombre_sucursal'];
            $estadisticas['por_sucursal'][$sucursal] = ($estadisticas['por_sucursal'][$sucursal] ?? 0) + 1;
            
            // Por fecha
            $fecha = $cita['fecha_cita'];
            $estadisticas['por_fecha'][$fecha] = ($estadisticas['por_fecha'][$fecha] ?? 0) + 1;
        }
        
        return $estadisticas;
    }

    // ✅ AGREGAR ESTOS MÉTODOS AL src/Models/Cita.php

    /**
     * Obtener especialidades disponibles según tipo de cita
     */
    public function obtenerEspecialidadesDisponibles($tipoCita) {
        try {
            $sql = "
                SELECT DISTINCT 
                    e.id_especialidad, 
                    e.nombre_especialidad, 
                    e.descripcion, 
                    e.activo,
                    e.permite_virtual,
                    e.permite_presencial,
                    e.duracion_cita_minutos,
                    e.precio_consulta
                FROM especialidades e
                INNER JOIN medico_especialidades me ON e.id_especialidad = me.id_especialidad
                INNER JOIN usuarios u ON me.id_medico = u.id_usuario
                WHERE e.activo = 1 
                AND me.activo = 1 
                AND u.activo = 1 
                AND u.id_rol = 3
            ";
            
            // ✅ Filtrar por tipo de cita
            if ($tipoCita === 'virtual') {
                $sql .= " AND e.permite_virtual = 1";
            } else {
                $sql .= " AND e.permite_presencial = 1";
            }
            
            $sql .= " ORDER BY e.nombre_especialidad";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            throw new \Exception('Error obteniendo especialidades: ' . $e->getMessage());
        }
    }


/**
 * Obtener médicos por especialidad y tipo de cita
 */
    public function obtenerMedicosPorEspecialidad($idEspecialidad, $tipoCita) {
        try {
            $sql = "
                SELECT DISTINCT 
                    u.id_usuario as id_medico,
                    u.nombre,
                    u.apellido,
                    CONCAT(u.nombre, ' ', u.apellido) as nombre_completo,
                    u.email,
                    u.telefono,
                    s.nombre_sucursal as sucursal,
                    e.nombre_especialidad as especialidad,
                    me.numero_licencia
                FROM usuarios u
                INNER JOIN medico_especialidades me ON u.id_usuario = me.id_medico
                INNER JOIN especialidades e ON me.id_especialidad = e.id_especialidad
                LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal
                INNER JOIN horarios_medicos hm ON u.id_usuario = hm.id_medico
                WHERE u.id_rol = 3 
                AND u.activo = 1
                AND me.activo = 1
                AND me.id_especialidad = ?
                AND hm.activo = 1
            ";
            
            // ✅ Filtrar por tipo de cita
            if ($tipoCita === 'virtual') {
                $sql .= " AND e.permite_virtual = 1";
            } else {
                $sql .= " AND e.permite_presencial = 1";
            }
            
            $sql .= " ORDER BY u.apellido, u.nombre";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idEspecialidad]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            throw new \Exception('Error obteniendo médicos: ' . $e->getMessage());
        }
    }


/**
 * Obtener horarios disponibles de un médico en una fecha específica
 */
public function obtenerHorariosDisponibles($idMedico, $fecha, $duracionMinutos = 45) {
    try {
        // Obtener el día de la semana (1=Lunes, 7=Domingo)
        $diaSemana = date('N', strtotime($fecha));
        
        // Obtener horarios del médico para ese día
        $sql = "
            SELECT hora_inicio, hora_fin 
            FROM horarios_medicos 
            WHERE id_medico = ? 
            AND dia_semana = ? 
            AND activo = 1
            ORDER BY hora_inicio
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idMedico, $diaSemana]);
        $horariosMedico = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($horariosMedico)) {
            return []; // No tiene horarios ese día
        }
        
        // Obtener citas ya agendadas para esa fecha
        $sqlCitas = "
            SELECT hora_cita 
            FROM citas 
            WHERE id_medico = ? 
            AND fecha_cita = ? 
            AND estado_cita NOT IN ('cancelada', 'no_asistio')
        ";
        
        $stmtCitas = $this->db->prepare($sqlCitas);
        $stmtCitas->execute([$idMedico, $fecha]);
        $citasAgendadas = $stmtCitas->fetchAll(PDO::FETCH_COLUMN);
        
        // Generar slots disponibles
        $slotsDisponibles = [];
        
        foreach ($horariosMedico as $horario) {
            $horaInicio = $horario['hora_inicio'];
            $horaFin = $horario['hora_fin'];
            
            $slots = $this->generarSlots($horaInicio, $horaFin, $duracionMinutos);
            
            // Filtrar slots ya ocupados
            foreach ($slots as $slot) {
                if (!in_array($slot['hora'], $citasAgendadas)) {
                    $slotsDisponibles[] = $slot;
                }
            }
        }
        
        return $slotsDisponibles;
        
    } catch (\Exception $e) {
        throw new \Exception('Error obteniendo horarios disponibles: ' . $e->getMessage());
    }
}

/**
 * Generar slots de tiempo basados en duración
 */
private function generarSlots($horaInicio, $horaFin, $duracionMinutos) {
    $slots = [];
    $inicio = strtotime($horaInicio);
    $fin = strtotime($horaFin);
    $duracionSegundos = $duracionMinutos * 60;
    
    while ($inicio < $fin) {
        $horaSlot = date('H:i:s', $inicio);
        $horaFinSlot = date('H:i:s', $inicio + $duracionSegundos);
        
        // Verificar que el slot completo esté dentro del horario
        if (($inicio + $duracionSegundos) <= $fin) {
            $slots[] = [
                'hora' => $horaSlot,
                'hora_fin' => $horaFinSlot,
                'disponible' => true,
                'duracion_minutos' => $duracionMinutos
            ];
        }
        
        $inicio += $duracionSegundos;
    }
    
    return $slots;
}

/**
 * Validar disponibilidad de un horario específico
 */
public function validarDisponibilidad($idMedico, $fechaCita, $horaCita) {
    try {
        // Verificar que la fecha no sea en el pasado
        if ($fechaCita < date('Y-m-d')) {
            return [
                'disponible' => false,
                'mensaje' => 'No se pueden agendar citas en fechas pasadas',
                'razon' => 'fecha_pasada'
            ];
        }
        
        // Verificar que el médico tenga horario ese día
        $diaSemana = date('N', strtotime($fechaCita));
        
        $sqlHorario = "
            SELECT COUNT(*) 
            FROM horarios_medicos 
            WHERE id_medico = ? 
            AND dia_semana = ? 
            AND ? BETWEEN hora_inicio AND hora_fin 
            AND activo = 1
        ";
        
        $stmt = $this->db->prepare($sqlHorario);
        $stmt->execute([$idMedico, $diaSemana, $horaCita]);
        
        if ($stmt->fetchColumn() == 0) {
            return [
                'disponible' => false,
                'mensaje' => 'El médico no tiene horario disponible en esa fecha/hora',
                'razon' => 'sin_horario'
            ];
        }
        
        // Verificar que no haya conflicto con citas existentes
        $sqlConflicto = "
            SELECT COUNT(*) 
            FROM citas 
            WHERE id_medico = ? 
            AND fecha_cita = ? 
            AND hora_cita = ?
            AND estado_cita NOT IN ('cancelada', 'no_asistio')
        ";
        
        $stmt = $this->db->prepare($sqlConflicto);
        $stmt->execute([$idMedico, $fechaCita, $horaCita]);
        
        if ($stmt->fetchColumn() > 0) {
            return [
                'disponible' => false,
                'mensaje' => 'Ya existe una cita en ese horario',
                'razon' => 'horario_ocupado'
            ];
        }
        
        return [
            'disponible' => true,
            'mensaje' => 'Horario disponible'
        ];
        
    } catch (\Exception $e) {
        return [
            'disponible' => false,
            'mensaje' => 'Error validando disponibilidad: ' . $e->getMessage(),
            'razon' => 'error'
        ];
    }
}

/**
 * Crear nueva cita médica
 */
public function crearCita($datos) {
    try {
        $this->db->beginTransaction();
        
        // 1. Buscar paciente por cédula
        $sqlPaciente = "SELECT id_usuario, nombre, apellido, email FROM usuarios WHERE cedula = ? AND id_rol = 4 AND activo = 1";
        $stmt = $this->db->prepare($sqlPaciente);
        $stmt->execute([$datos['cedula_paciente']]);
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$paciente) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'No se encontró un paciente activo con esa cédula'
            ];
        }
        
        // 2. Validar disponibilidad
        $validacion = $this->validarDisponibilidad($datos['id_medico'], $datos['fecha_cita'], $datos['hora_cita']);
        if (!$validacion['disponible']) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => $validacion['mensaje']
            ];
        }
        
        // 3. Obtener datos del médico y especialidad - ✅ CORREGIDO
        $sqlMedico = "
            SELECT u.nombre, u.apellido, u.email, s.nombre_sucursal as sucursal, s.direccion as direccion_sucursal
            FROM usuarios u 
            LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal
            WHERE u.id_usuario = ?
        ";
        $stmt = $this->db->prepare($sqlMedico);
        $stmt->execute([$datos['id_medico']]);
        $medico = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $sqlEspecialidad = "SELECT nombre_especialidad FROM especialidades WHERE id_especialidad = ?";
        $stmt = $this->db->prepare($sqlEspecialidad);
        $stmt->execute([$datos['id_especialidad']]);
        $especialidad = $stmt->fetchColumn();
        
        // 4. Generar enlace virtual si es necesario
        $enlaceVirtual = null;
        $zoomMeetingId = null;
        $zoomPassword = null;
        $zoomStartUrl = null;
        
        if ($datos['tipo_cita'] === 'virtual') {
            $zoomService = new \App\Services\ZoomService();
            $zoomData = $zoomService->generarEnlaceGenerico('temp-' . time());
            
            $enlaceVirtual = $zoomData['enlace_virtual'];
            $zoomMeetingId = $zoomData['zoom_meeting_id'];
            $zoomPassword = $zoomData['zoom_password'];
            $zoomStartUrl = $zoomData['zoom_start_url'];
        }
        
        // 5. Insertar la cita
        $sql = "
            INSERT INTO citas 
            (id_paciente, id_medico, id_especialidad, id_sucursal, fecha_cita, hora_cita, 
             tipo_cita, motivo_consulta, observaciones, enlace_virtual, zoom_meeting_id, 
             zoom_password, zoom_start_url, id_usuario_registro, estado_cita) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'agendada')
        ";
        
        $stmt = $this->db->prepare($sql);
        $resultado = $stmt->execute([
            $paciente['id_usuario'],
            $datos['id_medico'],
            $datos['id_especialidad'],
            $datos['id_sucursal'] ?? 1, // Sucursal por defecto
            $datos['fecha_cita'],
            $datos['hora_cita'],
            $datos['tipo_cita'],
            $datos['motivo_consulta'],
            $datos['observaciones'] ?? null,
            $enlaceVirtual,
            $zoomMeetingId,
            $zoomPassword,
            $zoomStartUrl
        ]);
        
        if (!$resultado) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Error al crear la cita en la base de datos'
            ];
        }
        
        $idCita = $this->db->lastInsertId();
        $this->db->commit();
        
        // ✅ 6. DEFINIR $datosCita ANTES de usarlo
        $datosCita = [
            'id_cita' => $idCita,
            'fecha_cita' => $datos['fecha_cita'],
            'hora_cita' => $datos['hora_cita'],
            'nombre_paciente' => $paciente['nombre'] . ' ' . $paciente['apellido'],
            'nombre_medico' => $medico['nombre'] . ' ' . $medico['apellido'],
            'especialidad' => $especialidad,
            'sucursal' => $medico['sucursal'] ?? 'Clínica SJ',
            'direccion_sucursal' => $medico['direccion_sucursal'] ?? 'Dirección no disponible',
            'tipo_cita' => $datos['tipo_cita'],
            'motivo_consulta' => $datos['motivo_consulta'],
            'enlace_virtual' => $enlaceVirtual,
            'zoom_meeting_id' => $zoomMeetingId,
            'zoom_password' => $zoomPassword
        ];
        
        // ✅ 7. Enviar emails con los nuevos métodos diseñados
        $emailsEnviados = ['paciente' => false, 'medico' => false];
        
        try {
            $emailService = new \App\Services\EmailService();
            
            // Email al paciente con diseño propio
            $resultadoPaciente = $emailService->enviarNotificacionCitaPaciente(
                $paciente['email'],
                $paciente['nombre'] . ' ' . $paciente['apellido'],
                $datosCita
            );
            $emailsEnviados['paciente'] = $resultadoPaciente['success'] ?? false;
            
            // Email al médico con diseño propio
            $resultadoMedico = $emailService->enviarNotificacionCitaMedico(
                $medico['email'],
                $medico['nombre'] . ' ' . $medico['apellido'],
                $datosCita
            );
            $emailsEnviados['medico'] = $resultadoMedico['success'] ?? false;
            
        } catch (\Exception $e) {
            error_log("Error enviando emails de cita: " . $e->getMessage());
        }
        
        // ✅ 8. Preparar respuesta completa
        return [
            'success' => true,
            'message' => 'Cita creada exitosamente',
            'data' => [
                'cita_creada' => '✅ ÉXITO',
                'id_cita' => $idCita,
                'numero_cita' => str_pad($idCita, 6, '0', STR_PAD_LEFT),
                'paciente' => [
                    'id' => $paciente['id_usuario'],
                    'nombre_completo' => $paciente['nombre'] . ' ' . $paciente['apellido'],
                    'cedula' => $datos['cedula_paciente'],
                    'email' => $paciente['email']
                ],
                'medico' => [
                    'id' => $datos['id_medico'],
                    'nombre_completo' => $medico['nombre'] . ' ' . $medico['apellido'],
                    'especialidad' => $especialidad,
                    'email' => $medico['email']
                ],
                'detalles_cita' => [
                    'fecha' => $datos['fecha_cita'],
                    'hora' => $datos['hora_cita'],
                    'tipo' => $datos['tipo_cita'],
                    'motivo' => $datos['motivo_consulta'],
                    'observaciones' => $datos['observaciones'] ?? null,
                    'estado' => 'agendada',
                    'sucursal' => $medico['sucursal'] ?? 'Clínica SJ'
                ],
                'informacion_virtual' => $datos['tipo_cita'] === 'virtual' ? [
                    '🎥 Enlace_Zoom' => $enlaceVirtual,
                    '🔢 ID_Reunion' => $zoomMeetingId,
                    '🔐 Password' => $zoomPassword,
                    '📝 Instrucciones' => 'Ingrese al enlace 5 minutos antes de la cita'
                ] : null,
                'notificaciones' => [
                    'email_paciente' => $emailsEnviados['paciente'] ? '✅ Enviado' : '❌ Error al enviar',
                    'email_medico' => $emailsEnviados['medico'] ? '✅ Enviado' : '❌ Error al enviar',
                    'mensaje' => ($emailsEnviados['paciente'] && $emailsEnviados['medico']) 
                        ? 'Notificaciones enviadas exitosamente a ambos'
                        : (($emailsEnviados['paciente'] || $emailsEnviados['medico']) 
                            ? 'Algunas notificaciones fueron enviadas' 
                            : 'Las notificaciones no pudieron enviarse')
                ],
                'proximos_pasos' => [
                    'para_paciente' => $datos['tipo_cita'] === 'virtual' 
                        ? 'Use el enlace de Zoom mostrado arriba para su cita virtual'
                        : 'Diríjase a la clínica en la fecha y hora programada',
                    'recordatorio' => 'Recibirá un recordatorio 24 horas antes de su cita'
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
        
    } catch (\Exception $e) {
        if ($this->db->inTransaction()) {
            $this->db->rollback();
        }
        return [
            'success' => false,
            'message' => 'Error al crear la cita: ' . $e->getMessage()
        ];
    }
}

/**
 * Cambiar estado de una cita y enviar notificaciones
 */
public function cambiarEstadoCita($idCita, $nuevoEstado, $motivoCambio = null) {
    try {
        $this->db->beginTransaction();
        
        // 1. Obtener información completa de la cita actual
        $sqlCita = "
            SELECT 
                c.id_cita, c.estado_cita as estado_actual, c.fecha_cita, c.hora_cita, 
                c.tipo_cita, c.motivo_consulta, c.enlace_virtual, c.zoom_meeting_id, c.zoom_password,
                p.id_usuario as id_paciente, p.nombre as nombre_paciente, p.apellido as apellido_paciente, p.email as email_paciente,
                m.id_usuario as id_medico, m.nombre as nombre_medico, m.apellido as apellido_medico, m.email as email_medico,
                e.nombre_especialidad as especialidad,
                s.nombre_sucursal as sucursal, s.direccion as direccion_sucursal
            FROM citas c
            INNER JOIN usuarios p ON c.id_paciente = p.id_usuario
            INNER JOIN usuarios m ON c.id_medico = m.id_usuario
            INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
            INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
            WHERE c.id_cita = ?
        ";
        
        $stmt = $this->db->prepare($sqlCita);
        $stmt->execute([$idCita]);
        $cita = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cita) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'No se encontró la cita especificada'
            ];
        }
        
        // Verificar si ya tiene ese estado
        if ($cita['estado_actual'] === $nuevoEstado) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => "La cita ya tiene el estado: {$nuevoEstado}"
            ];
        }
        
        // 2. Actualizar estado de la cita
        $camposActualizar = ['estado_cita = ?'];
        $valoresActualizar = [$nuevoEstado];
        
        // Agregar campos específicos según el nuevo estado
        if ($nuevoEstado === 'confirmada') {
            $camposActualizar[] = 'fecha_confirmacion = NOW()';
        } elseif ($nuevoEstado === 'cancelada') {
            $camposActualizar[] = 'fecha_cancelacion = NOW()';
            if ($motivoCambio) {
                $camposActualizar[] = 'motivo_cancelacion = ?';
                $valoresActualizar[] = $motivoCambio;
            }
        }
        
        $sqlUpdate = "UPDATE citas SET " . implode(', ', $camposActualizar) . " WHERE id_cita = ?";
        $valoresActualizar[] = $idCita;
        
        $stmt = $this->db->prepare($sqlUpdate);
        $resultado = $stmt->execute($valoresActualizar);
        
        if (!$resultado) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Error al actualizar el estado de la cita'
            ];
        }
        
        $this->db->commit();
        
        // 3. Preparar datos para emails
        $datosCita = [
            'id_cita' => $cita['id_cita'],
            'estado_anterior' => $cita['estado_actual'],
            'estado_nuevo' => $nuevoEstado,
            'fecha_cita' => $cita['fecha_cita'],
            'hora_cita' => $cita['hora_cita'],
            'nombre_paciente' => $cita['nombre_paciente'] . ' ' . $cita['apellido_paciente'],
            'nombre_medico' => $cita['nombre_medico'] . ' ' . $cita['apellido_medico'],
            'especialidad' => $cita['especialidad'],
            'sucursal' => $cita['sucursal'],
            'direccion_sucursal' => $cita['direccion_sucursal'],
            'tipo_cita' => $cita['tipo_cita'],
            'motivo_consulta' => $cita['motivo_consulta'],
            'motivo_cambio' => $motivoCambio,
            'enlace_virtual' => $cita['enlace_virtual'],
            'zoom_meeting_id' => $cita['zoom_meeting_id'],
            'zoom_password' => $cita['zoom_password']
        ];
        
        // 4. Enviar notificaciones por email
        $emailsEnviados = ['paciente' => false, 'medico' => false];
        
        try {
            $emailService = new \App\Services\EmailService();
            
            // Email al paciente
            $resultadoPaciente = $emailService->enviarNotificacionCambioEstado(
                $cita['email_paciente'],
                $cita['nombre_paciente'] . ' ' . $cita['apellido_paciente'],
                $datosCita,
                'paciente'
            );
            $emailsEnviados['paciente'] = $resultadoPaciente['success'] ?? false;
            
            // Email al médico
            $resultadoMedico = $emailService->enviarNotificacionCambioEstado(
                $cita['email_medico'],
                $cita['nombre_medico'] . ' ' . $cita['apellido_medico'],
                $datosCita,
                'medico'
            );
            $emailsEnviados['medico'] = $resultadoMedico['success'] ?? false;
            
        } catch (\Exception $e) {
            error_log("Error enviando emails de cambio de estado: " . $e->getMessage());
        }
        
        // 5. Preparar mensaje de estado
        $mensajesEstado = [
            'agendada' => 'La cita ha sido agendada',
            'confirmada' => 'La cita ha sido confirmada',
            'en_curso' => 'La cita está en curso',
            'completada' => 'La cita ha sido completada',
            'cancelada' => 'La cita ha sido cancelada',
            'no_asistio' => 'Se ha registrado que el paciente no asistió'
        ];
        
        return [
            'success' => true,
            'message' => 'Estado de cita actualizado exitosamente',
            'data' => [
                'cambio_exitoso' => '✅ COMPLETADO',
                'id_cita' => $idCita,
                'numero_cita' => str_pad($idCita, 6, '0', STR_PAD_LEFT),
                'estado_anterior' => $cita['estado_actual'],
                'estado_nuevo' => $nuevoEstado,
                'mensaje_estado' => $mensajesEstado[$nuevoEstado] ?? 'Estado actualizado',
                'motivo_cambio' => $motivoCambio,
                'paciente' => [
                    'nombre' => $cita['nombre_paciente'] . ' ' . $cita['apellido_paciente'],
                    'email' => $cita['email_paciente']
                ],
                'medico' => [
                    'nombre' => $cita['nombre_medico'] . ' ' . $cita['apellido_medico'],
                    'email' => $cita['email_medico']
                ],
                'cita' => [
                    'fecha' => $cita['fecha_cita'],
                    'hora' => $cita['hora_cita'],
                    'tipo' => $cita['tipo_cita'],
                    'especialidad' => $cita['especialidad']
                ],
                'notificaciones' => [
                    'email_paciente' => $emailsEnviados['paciente'] ? '✅ Enviado' : '❌ Error al enviar',
                    'email_medico' => $emailsEnviados['medico'] ? '✅ Enviado' : '❌ Error al enviar',
                    'mensaje' => ($emailsEnviados['paciente'] && $emailsEnviados['medico']) 
                        ? 'Notificaciones enviadas exitosamente a ambos'
                        : (($emailsEnviados['paciente'] || $emailsEnviados['medico']) 
                            ? 'Algunas notificaciones fueron enviadas' 
                            : 'Las notificaciones no pudieron enviarse')
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
        
    } catch (\Exception $e) {
        if ($this->db->inTransaction()) {
            $this->db->rollback();
        }
        return [
            'success' => false,
            'message' => 'Error al cambiar estado de cita: ' . $e->getMessage()
        ];
    }
}

}
?>