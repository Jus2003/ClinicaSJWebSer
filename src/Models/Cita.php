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

}
?>