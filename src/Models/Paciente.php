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

            // Calcular edad
            $edad = null;
            if ($paciente['fecha_nacimiento']) {
                $fechaNac = new \DateTime($paciente['fecha_nacimiento']);
                $hoy = new \DateTime();
                $edad = $fechaNac->diff($hoy)->y;
            }
            
            // Obtener citas con información esencial - SOLO COLUMNAS QUE EXISTEN
            $sqlCitas = "SELECT c.id_cita, c.fecha_cita, c.hora_cita, c.tipo_cita, 
                                c.estado_cita, c.motivo_consulta, c.observaciones,
                                e.nombre_especialidad, e.precio_consulta,
                                s.nombre_sucursal as sucursal_cita,
                                CONCAT(medico.nombre, ' ', medico.apellido) as nombre_medico,
                                -- Datos de la consulta
                                con.id_consulta, con.diagnostico_principal, 
                                con.tratamiento, con.observaciones_medicas
                        FROM citas c
                        LEFT JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                        LEFT JOIN sucursales s ON c.id_sucursal = s.id_sucursal
                        LEFT JOIN usuarios medico ON c.id_medico = medico.id_usuario
                        LEFT JOIN consultas con ON c.id_cita = con.id_cita
                        WHERE c.id_paciente = ?
                        ORDER BY c.fecha_cita DESC, c.hora_cita DESC";
            
            $stmt = $this->db->prepare($sqlCitas);
            $stmt->execute([$idPaciente]);
            $citas = $stmt->fetchAll();
            
            // Procesar cada cita y obtener recetas
            $historialCompleto = [];
            foreach ($citas as $cita) {
                // Obtener recetas de esta consulta (si tiene consulta)
                $recetas = [];
                if ($cita['id_consulta']) {
                    $sqlRecetas = "SELECT r.codigo_receta, r.medicamento, 
                                        r.concentracion, r.dosis, r.frecuencia, 
                                        r.duracion, r.cantidad, r.indicaciones_especiales,
                                        r.fecha_emision, r.estado
                                FROM recetas r
                                WHERE r.id_consulta = ?
                                ORDER BY r.fecha_emision DESC";
                    
                    $stmtRecetas = $this->db->prepare($sqlRecetas);
                    $stmtRecetas->execute([$cita['id_consulta']]);
                    $recetas = $stmtRecetas->fetchAll();
                }

                // Armar el historial simplificado para esta cita
                $citaCompleta = [
                    // Información de la cita
                    'cita' => [
                        'id_cita' => $cita['id_cita'],
                        'fecha_cita' => $cita['fecha_cita'],
                        'hora_cita' => $cita['hora_cita'],
                        'tipo_cita' => $cita['tipo_cita'],
                        'estado_cita' => $cita['estado_cita'],
                        'motivo_consulta' => $cita['motivo_consulta'],
                        'observaciones' => $cita['observaciones'],
                        'especialidad' => $cita['nombre_especialidad'],
                        'precio_consulta' => $cita['precio_consulta'],
                        'medico' => $cita['nombre_medico'],
                        'sucursal' => $cita['sucursal_cita']
                    ],
                    
                    // Información de la consulta (si existe)
                    'consulta' => null,
                    
                    // Recetas médicas
                    'recetas' => $recetas,
                    
                    // Resumen
                    'tiene_consulta' => !empty($cita['id_consulta']),
                    'total_recetas' => count($recetas)
                ];

                // Agregar información de consulta si existe
                if ($cita['id_consulta']) {
                    $citaCompleta['consulta'] = [
                        'id_consulta' => $cita['id_consulta'],
                        'diagnostico_principal' => $cita['diagnostico_principal'],
                        'tratamiento' => $cita['tratamiento'],
                        'observaciones_medicas' => $cita['observaciones_medicas']
                    ];
                }

                $historialCompleto[] = $citaCompleta;
            }

            // Estadísticas básicas
            $totalCitas = count($citas);
            $citasCompletadas = array_filter($citas, fn($c) => $c['estado_cita'] === 'completada');
            $totalConsultas = array_filter($citas, fn($c) => !empty($c['id_consulta']));
            $totalRecetas = 0;
            foreach ($historialCompleto as $item) {
                $totalRecetas += $item['total_recetas'];
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
                        'edad' => $edad,
                        'genero' => $paciente['genero'],
                        'direccion' => $paciente['direccion'],
                        'fecha_registro' => $paciente['fecha_registro']
                    ],
                    'estadisticas' => [
                        'total_citas' => $totalCitas,
                        'citas_completadas' => count($citasCompletadas),
                        'consultas_realizadas' => count($totalConsultas),
                        'recetas_emitidas' => $totalRecetas
                    ],
                    'historial_completo' => $historialCompleto
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false, 
                'message' => 'Error obteniendo historial completo: ' . $e->getMessage()
            ];
        }
    }

    public function listarPacientesParaHistorial($idUsuarioLogueado, $rolUsuario) {
        try {
            // Si es paciente (rol 4), solo ve su propio historial
            if ($rolUsuario == 4) {
                return $this->obtenerHistorialCompleto($idUsuarioLogueado);
            }
            
            // Si es Admin, Recepcionista o Médico (roles 1, 2, 3) → Ve todos los pacientes
            $sql = "SELECT u.id_usuario, u.cedula, u.nombre, u.apellido, 
                        u.email, u.telefono, u.fecha_nacimiento, u.genero,
                        u.fecha_registro, u.ultimo_acceso,
                        s.nombre_sucursal,
                        -- Estadísticas básicas
                        COUNT(c.id_cita) as total_citas,
                        COUNT(CASE WHEN c.estado_cita = 'completada' THEN 1 END) as citas_completadas,
                        MAX(c.fecha_cita) as ultima_cita
                    FROM usuarios u 
                    LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal 
                    LEFT JOIN citas c ON u.id_usuario = c.id_paciente
                    WHERE u.id_rol = 4 AND u.activo = 1
                    GROUP BY u.id_usuario
                    ORDER BY u.nombre ASC, u.apellido ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $pacientes = $stmt->fetchAll();
            
            // Procesar la lista
            $listaPacientes = [];
            foreach ($pacientes as $paciente) {
                // Calcular edad
                $edad = null;
                if ($paciente['fecha_nacimiento']) {
                    $fechaNac = new \DateTime($paciente['fecha_nacimiento']);
                    $hoy = new \DateTime();
                    $edad = $fechaNac->diff($hoy)->y;
                }
                
                $listaPacientes[] = [
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
                    'sucursal' => $paciente['nombre_sucursal'],
                    'fecha_registro' => $paciente['fecha_registro'],
                    'ultimo_acceso' => $paciente['ultimo_acceso'],
                    'estadisticas' => [
                        'total_citas' => (int)$paciente['total_citas'],
                        'citas_completadas' => (int)$paciente['citas_completadas'],
                        'ultima_cita' => $paciente['ultima_cita']
                    ]
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Lista de pacientes para historial médico obtenida correctamente',
                'data' => [
                    'tipo_vista' => 'lista_pacientes', // Indica que es lista para seleccionar
                    'total_pacientes' => count($listaPacientes),
                    'rol_usuario' => $rolUsuario,
                    'pacientes' => $listaPacientes
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false, 
                'message' => 'Error obteniendo lista de pacientes: ' . $e->getMessage()
            ];
        }
    }

    public function listarTodos() {
    $sql = "SELECT u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono,
                   u.fecha_nacimiento, u.genero, s.nombre_sucursal,
                   COUNT(c.id_cita) as total_citas
            FROM usuarios u 
            LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal 
            LEFT JOIN citas c ON u.id_usuario = c.id_paciente
            WHERE u.id_rol = 4 AND u.activo = 1
            GROUP BY u.id_usuario
            ORDER BY u.nombre, u.apellido";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $pacientes = $stmt->fetchAll();
    
    $resultado = [];
    foreach ($pacientes as $paciente) {
        $edad = null;
        if ($paciente['fecha_nacimiento']) {
            $fechaNac = new \DateTime($paciente['fecha_nacimiento']);
            $hoy = new \DateTime();
            $edad = $fechaNac->diff($hoy)->y;
        }
        
        $resultado[] = [
            'id_paciente' => $paciente['id_usuario'],
            'cedula' => $paciente['cedula'],
            'nombre_completo' => $paciente['nombre'] . ' ' . $paciente['apellido'],
            'email' => $paciente['email'],
            'telefono' => $paciente['telefono'],
            'edad' => $edad,
            'genero' => $paciente['genero'],
            'sucursal' => $paciente['nombre_sucursal'],
            'total_citas' => $paciente['total_citas']
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Lista de pacientes obtenida correctamente',
        'data' => $resultado
    ];
}

}
?>