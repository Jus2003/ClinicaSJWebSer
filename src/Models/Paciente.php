<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use DateTime; // ✅ AGREGAR ESTA LÍNEA

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

    // ✅ MÉTODO CORREGIDO PARA CREAR PACIENTE EN Paciente.php

    public function crearPaciente($datos) {
    try {
        // ✅ Validaciones completas de entrada
        $validacion = $this->validarDatosPaciente($datos);
        if (!$validacion['success']) {
            return $validacion;
        }
        
        $this->db->beginTransaction();
        
        // Generar contraseña temporal
        $passwordTemporal = $this->generarPasswordTemporal();
        
        // ✅ Insertar en tabla usuarios con id_rol = 4 (paciente)
        $sql = "INSERT INTO usuarios (username, email, password, cedula, nombre, apellido, 
                fecha_nacimiento, genero, telefono, direccion, id_rol, 
                activo, requiere_cambio_contrasena, clave_temporal) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 4, 1, 1, ?)";
        
        $stmt = $this->db->prepare($sql);
        $resultado = $stmt->execute([
            $datos['username'],
            $datos['email'],
            base64_encode($passwordTemporal),
            $datos['cedula'],
            $datos['nombre'],
            $datos['apellido'],
            $datos['fecha_nacimiento'] ?? null,
            $datos['genero'] ?? null,
            $datos['telefono'] ?? null,
            $datos['direccion'] ?? null,
            $passwordTemporal
        ]);
        
        if (!$resultado) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Error al crear el paciente'];
        }
        
        $idPaciente = $this->db->lastInsertId();
        $this->db->commit();
        
        // ✅ Intentar enviar email con contraseña temporal
        $nombreCompleto = $datos['nombre'] . ' ' . $datos['apellido'];
        $emailEnviado = false;
        
        try {
            $emailService = new EmailService();
            $resultadoEmail = $emailService->enviarPasswordTemporal(
                $datos['email'], 
                $nombreCompleto, 
                $datos['username'], 
                $passwordTemporal
            );
            $emailEnviado = $resultadoEmail['success'];
        } catch (\Exception $e) {
            // El email falló pero el paciente se creó exitosamente
            error_log('Error enviando email: ' . $e->getMessage());
            $emailEnviado = false;
        }
        
        // ✅ RETORNAR LA INFORMACIÓN COMPLETA COMO EN EL MÉDICO
        return [
            'success' => true,
            'message' => 'Paciente creado exitosamente',
            'data' => [
                'id_paciente' => $idPaciente,
                'nombre_completo' => $nombreCompleto,
                'username' => $datos['username'],
                'email' => $datos['email'],
                'cedula' => $datos['cedula'],
                'telefono' => $datos['telefono'] ?? 'No especificado',
                'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? 'No especificada',
                'genero' => $datos['genero'] ?? 'No especificado',
                'direccion' => $datos['direccion'] ?? 'No especificada',
                'password_temporal' => $passwordTemporal, // ✅ MOSTRAR LA CONTRASEÑA
                'email_enviado' => $emailEnviado,
                'mensaje_email' => $emailEnviado ? 
                    'Se ha enviado un email con las credenciales temporales' : 
                    'No se pudo enviar el email, pero el paciente fue creado exitosamente',
                'rol' => 'Paciente',
                'fecha_creacion' => date('Y-m-d H:i:s')
            ]
        ];
        
    } catch (\Exception $e) {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        return [
            'success' => false,
            'message' => 'Error al crear paciente: ' . $e->getMessage()
        ];
    }
}

public function obtenerPorPaciente($idPaciente) {
    $sql = "SELECT * FROM citas WHERE id_paciente = :id_paciente";
    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':id_paciente', $idPaciente);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ✅ VERSIÓN SIMPLIFICADA SIN DateTime
private function validarDatosPaciente($datos) {
    $errores = [];
    
    // Campos obligatorios
    if (empty($datos['nombre'])) {
        $errores[] = 'El nombre es obligatorio';
    }
    if (empty($datos['apellido'])) {
        $errores[] = 'El apellido es obligatorio';
    }
    if (empty($datos['cedula'])) {
        $errores[] = 'La cédula es obligatoria';
    }
    if (empty($datos['email'])) {
        $errores[] = 'El email es obligatorio';
    }
    if (empty($datos['username'])) {
        $errores[] = 'El username es obligatorio';
    }
    
    // Validar formato de cédula ecuatoriana
    if (!empty($datos['cedula']) && !$this->validarCedulaEcuatoriana($datos['cedula'])) {
        $errores[] = 'La cédula no tiene un formato válido';
    }
    
    // Validar formato de email
    if (!empty($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El email no tiene un formato válido';
    }
    
    // Validar longitud de username
    if (!empty($datos['username']) && (strlen($datos['username']) < 3 || strlen($datos['username']) > 50)) {
        $errores[] = 'El username debe tener entre 3 y 50 caracteres';
    }
    
    // Validar que no existan duplicados
    if (!empty($datos['cedula']) && $this->existeCedula($datos['cedula'])) {
        $errores[] = 'Ya existe un usuario con esa cédula';
    }
    
    if (!empty($datos['email']) && $this->existeEmail($datos['email'])) {
        $errores[] = 'Ya existe un usuario con ese email';
    }
    
    if (!empty($datos['username']) && $this->existeUsername($datos['username'])) {
        $errores[] = 'Ya existe un usuario con ese username';
    }
    
    // ✅ VALIDACIÓN SIMPLE DE FECHA (opcional)
    if (!empty($datos['fecha_nacimiento'])) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha_nacimiento'])) {
            $errores[] = 'La fecha de nacimiento debe tener formato YYYY-MM-DD';
        }
    }
    
    // Validar género si se proporciona
    if (!empty($datos['genero']) && !in_array($datos['genero'], ['M', 'F', 'Masculino', 'Femenino'])) {
        $errores[] = 'El género debe ser M, F, Masculino o Femenino';
    }
    
    // Validar teléfono si se proporciona
    if (!empty($datos['telefono']) && !preg_match('/^[0-9]{10}$/', $datos['telefono'])) {
        $errores[] = 'El teléfono debe tener 10 dígitos';
    }
    
    if (!empty($errores)) {
        return [
            'success' => false,
            'message' => 'Errores de validación',
            'errores' => $errores
        ];
    }
    
    return ['success' => true];
}

    // ✅ MÉTODOS AUXILIARES (si no existen ya)
    private function validarCedulaEcuatoriana($cedula) {
        if (strlen($cedula) != 10 || !is_numeric($cedula)) {
            return false;
        }
        
        $digitos = str_split($cedula);
        $provincia = (int)($digitos[0] . $digitos[1]);
        
        if ($provincia < 1 || $provincia > 24) {
            return false;
        }
        
        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $digito = (int)$digitos[$i];
            if ($i % 2 == 0) {
                $digito *= 2;
                if ($digito > 9) {
                    $digito -= 9;
                }
            }
            $suma += $digito;
        }
        
        $digitoVerificador = (int)$digitos[9];
        $resultado = $suma % 10;
        $resultado = $resultado == 0 ? 0 : 10 - $resultado;
        
        return $resultado == $digitoVerificador;
    }

    private function existeCedula($cedula) {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE cedula = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cedula]);
        return $stmt->fetchColumn() > 0;
    }

    private function existeEmail($email) {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }

    private function existeUsername($username) {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE username = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetchColumn() > 0;
    }

    private function generarPasswordTemporal($longitud = 8) {
        $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < $longitud; $i++) {
            $password .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }
        return $password;
    }

}
?>