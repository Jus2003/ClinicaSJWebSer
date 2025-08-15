<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Usuario {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function login($usuario, $password) {
        // Consulta simplificada usando ? en lugar de :parametro
        $sql = "SELECT u.*, r.nombre_rol, s.nombre_sucursal 
                FROM usuarios u 
                LEFT JOIN roles r ON u.id_rol = r.id_rol 
                LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal 
                WHERE u.username = ? AND u.activo = 1 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$usuario]);
        
        $user = $stmt->fetch();
        
        if ($user) {
            // Verificar contraseña base64
            $passwordDecodificada = base64_decode($user['password']);
            
            if ($passwordDecodificada === $password) {
                // Registrar login
                $this->registrarLog($user['id_usuario'], 'LOGIN');
                return $user;
            }
        }
        
        return false;
    }
    
    public function getMenusByRole($roleId) {
        $sql = "SELECT DISTINCT m.id_menu, m.nombre_menu, m.icono, m.orden,
                       sm.id_submenu, sm.nombre_submenu, sm.uri_submenu, sm.icono as icono_submenu
                FROM menus m
                INNER JOIN submenus sm ON m.id_menu = sm.id_menu
                INNER JOIN permisos p ON sm.id_submenu = p.id_submenu
                WHERE p.id_rol = ? 
                AND p.estado = '1' 
                AND p.permiso_leer = 1
                AND m.estado = '1'
                AND sm.estado = '1'
                ORDER BY m.orden, sm.orden";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roleId]);
        $results = $stmt->fetchAll();
        
        // Organizar menús y submenús
        $menus = [];
        foreach ($results as $row) {
            $menuId = $row['id_menu'];
            
            if (!isset($menus[$menuId])) {
                $menus[$menuId] = [
                    'id_menu' => $row['id_menu'],
                    'nombre_menu' => $row['nombre_menu'],
                    'icono' => $row['icono'],
                    'orden' => $row['orden'],
                    'submenus' => []
                ];
            }
            
            $menus[$menuId]['submenus'][] = [
                'id_submenu' => $row['id_submenu'],
                'nombre_submenu' => $row['nombre_submenu'],
                'uri_submenu' => $row['uri_submenu'],
                'icono' => $row['icono_submenu']
            ];
        }
        
        return array_values($menus);
    }
    
    private function registrarLog($userId, $accion) {
        try {
            $sql = "INSERT INTO logs_sistema (id_usuario, accion, tabla_afectada, id_registro) 
                    VALUES (?, ?, 'usuarios', ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $accion, $userId]);
        } catch (\Exception $e) {
            // No afectar el login si falla el log
            error_log("Error registrando log: " . $e->getMessage());
        }
    }

    public function cambiarPassword($userId, $passwordActual, $passwordNueva) {
        // Verificar la contraseña actual
        $sql = "SELECT password FROM usuarios WHERE id_usuario = ? AND activo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }
        
        // Verificar contraseña actual
        $passwordActualDecodificada = base64_decode($user['password']);
        if ($passwordActualDecodificada !== $passwordActual) {
            return ['success' => false, 'message' => 'La contraseña actual es incorrecta'];
        }
        
        // Actualizar con nueva contraseña
        $sql = "UPDATE usuarios SET 
                password = ?, 
                requiere_cambio_contrasena = 0,
                clave_temporal = NULL
                WHERE id_usuario = ?";
        
        $stmt = $this->db->prepare($sql);
        $resultado = $stmt->execute([base64_encode($passwordNueva), $userId]);
        
        if ($resultado) {
            // Registrar cambio en logs
            $this->registrarLog($userId, 'CAMBIO_PASSWORD');
            return ['success' => true, 'message' => 'Contraseña actualizada correctamente'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar la contraseña'];
        }
    }
    
    public function olvidoPassword($email) {
    // Buscar usuario por email
    $sql = "SELECT id_usuario, username, nombre, apellido FROM usuarios WHERE email = ? AND activo = 1";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'message' => 'Email no encontrado'];
    }
    
    // Generar contraseña temporal
    $passwordTemporal = $this->generarPasswordTemporal();
    
    // Actualizar en base de datos
    $sql = "UPDATE usuarios SET 
            password = ?, 
            requiere_cambio_contrasena = 1,
            clave_temporal = ?
            WHERE id_usuario = ?";
    
    $stmt = $this->db->prepare($sql);
    $resultado = $stmt->execute([
        base64_encode($passwordTemporal), 
        $passwordTemporal,
        $user['id_usuario']
    ]);
    
    if ($resultado) {
        // Registrar en logs
        $this->registrarLog($user['id_usuario'], 'RESET_PASSWORD');
        
        // **NUEVO: Enviar por email**
        $emailService = new \App\Services\EmailService();
        $nombreCompleto = $user['nombre'] . ' ' . $user['apellido'];
        $resultadoEmail = $emailService->enviarPasswordTemporal($email, $nombreCompleto, $user['username'], $passwordTemporal);
        
        return [
            'success' => true, 
            'message' => $resultadoEmail['success'] ? 
                        'Contraseña temporal enviada a tu email' : 
                        'Contraseña generada pero no se pudo enviar email',
            'data' => [
                'email_enviado' => $resultadoEmail['success'],
                'password_temporal' => $passwordTemporal, // Solo para debug, remover en producción
                'usuario' => $user['username'],
                'nombre' => $nombreCompleto
            ]
        ];
    } else {
        return ['success' => false, 'message' => 'Error al generar contraseña temporal'];
    }
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