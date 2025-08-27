<?php
namespace App\Models;

use App\Config\Database;
use App\Services\EmailService;
use PDO;

class Medico {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function crearMedico($datos) {
        try {
            // Validaciones de entrada
            $validacion = $this->validarDatosMedico($datos);
            if (!$validacion['success']) {
                return $validacion;
            }
            
            $this->db->beginTransaction();
            
            // Generar contraseña temporal
            $passwordTemporal = $this->generarPasswordTemporal();
            
            // Insertar en tabla usuarios
            $sql = "INSERT INTO usuarios (username, email, password, cedula, nombre, apellido, 
                    fecha_nacimiento, genero, telefono, direccion, id_rol, id_sucursal, 
                    activo, requiere_cambio_contrasena, clave_temporal) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 3, ?, 1, 1, ?)";
            
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
                $datos['id_sucursal'],
                $passwordTemporal
            ]);
            
            if (!$resultado) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Error al crear el médico'];
            }
            
            $idMedico = $this->db->lastInsertId();
            
            // Insertar especialidades
            foreach ($datos['especialidades'] as $idEspecialidad) {
                $sqlEsp = "INSERT INTO medico_especialidades (id_medico, id_especialidad, activo) VALUES (?, ?, 1)";
                $stmtEsp = $this->db->prepare($sqlEsp);
                $stmtEsp->execute([$idMedico, $idEspecialidad]);
            }
            
            $this->db->commit();
            
            // Enviar email con contraseña temporal
            $emailService = new EmailService();
            $nombreCompleto = $datos['nombre'] . ' ' . $datos['apellido'];
            $resultadoEmail = $emailService->enviarPasswordTemporal(
                $datos['email'], 
                $nombreCompleto, 
                $datos['username'], 
                $passwordTemporal
            );
            
            return [
                'success' => true,
                'message' => 'Médico creado exitosamente',
                'data' => [
                    'id_medico' => $idMedico,
                    'nombre_completo' => $nombreCompleto,
                    'username' => $datos['username'],
                    'email' => $datos['email'],
                    'password_temporal' => $passwordTemporal, // Solo para testing
                    'email_enviado' => $resultadoEmail['success'],
                    'especialidades_asignadas' => count($datos['especialidades'])
                ]
            ];
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    private function validarDatosMedico($datos) {
        $errores = [];
        
        // Campos requeridos
        if (empty($datos['nombre'])) $errores[] = 'El nombre es requerido';
        if (empty($datos['apellido'])) $errores[] = 'El apellido es requerido';
        if (empty($datos['cedula'])) $errores[] = 'La cédula es requerida';
        if (empty($datos['email'])) $errores[] = 'El email es requerido';
        if (empty($datos['username'])) $errores[] = 'El username es requerido';
        if (empty($datos['id_sucursal'])) $errores[] = 'La sucursal es requerida';
        if (empty($datos['especialidades']) || !is_array($datos['especialidades'])) {
            $errores[] = 'Debe seleccionar al menos una especialidad';
        }
        
        // Validación de cédula ecuatoriana
        if (!empty($datos['cedula'])) {
            if (!$this->validarCedulaEcuatoriana($datos['cedula'])) {
                $errores[] = 'La cédula no es válida (debe ser ecuatoriana de 10 dígitos)';
            }
        }
        
        // Validación de email
        if (!empty($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El formato del email no es válido';
        }
        
        // Verificar que no existan duplicados
        if (!empty($datos['cedula'])) {
            if ($this->existeCedula($datos['cedula'])) {
                $errores[] = 'Ya existe un usuario con esta cédula';
            }
        }
        
        if (!empty($datos['email'])) {
            if ($this->existeEmail($datos['email'])) {
                $errores[] = 'Ya existe un usuario con este email';
            }
        }
        
        if (!empty($datos['username'])) {
            if ($this->existeUsername($datos['username'])) {
                $errores[] = 'Ya existe un usuario con este username';
            }
        }
        
        // Validar que la sucursal existe y está activa
        if (!empty($datos['id_sucursal'])) {
            if (!$this->existeSucursal($datos['id_sucursal'])) {
                $errores[] = 'La sucursal seleccionada no existe o no está activa';
            }
        }
        
        // Validar especialidades
        if (!empty($datos['especialidades'])) {
            foreach ($datos['especialidades'] as $idEsp) {
                if (!$this->existeEspecialidad($idEsp)) {
                    $errores[] = "La especialidad con ID {$idEsp} no existe o no está activa";
                }
            }
        }
        
        if (!empty($errores)) {
            return ['success' => false, 'message' => implode(', ', $errores), 'errores' => $errores];
        }
        
        return ['success' => true];
    }
    
    private function validarCedulaEcuatoriana($cedula) {
        // Verificar que tenga 10 dígitos
        if (!preg_match('/^\d{10}$/', $cedula)) {
            return false;
        }
        
        // Algoritmo de validación de cédula ecuatoriana
        $digitos = str_split($cedula);
        $provincia = intval(substr($cedula, 0, 2));
        
        // Las provincias van del 01 al 24
        if ($provincia < 1 || $provincia > 24) {
            return false;
        }
        
        // Algoritmo de módulo 10
        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $digito = intval($digitos[$i]);
            if ($i % 2 == 0) { // Posiciones impares (0, 2, 4, 6, 8)
                $digito *= 2;
                if ($digito > 9) {
                    $digito -= 9;
                }
            }
            $suma += $digito;
        }
        
        $digitoVerificador = intval($digitos[9]);
        $residuo = $suma % 10;
        $resultado = $residuo == 0 ? 0 : 10 - $residuo;
        
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
    
    private function existeSucursal($idSucursal) {
        $sql = "SELECT COUNT(*) FROM sucursales WHERE id_sucursal = ? AND activo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idSucursal]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function existeEspecialidad($idEspecialidad) {
        $sql = "SELECT COUNT(*) FROM especialidades WHERE id_especialidad = ? AND activo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idEspecialidad]);
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

    public function asignarHorarios($id_medico, $horarios)
    {
        try {
            // Elimina los horarios anteriores del médico
            $stmt = $this->db->prepare("DELETE FROM horarios_medicos WHERE id_medico = ?");
            $stmt->execute([$id_medico]);

            // Inserta los nuevos horarios
            $stmt = $this->db->prepare("INSERT INTO horarios_medicos (id_medico, id_sucursal, dia_semana, hora_inicio, hora_fin, activo) VALUES (?, ?, ?, ?, ?, 1)");
            foreach ($horarios as $horario) {
                $stmt->execute([
                    $id_medico,
                    $horario['id_sucursal'],
                    $horario['dia_semana'],
                    $horario['hora_inicio'],
                    $horario['hora_fin']
                ]);
            }

            return [
                'success' => true,
                'message' => 'Horarios asignados correctamente'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al asignar horarios: ' . $e->getMessage()
            ];
        }
    }
}
?>