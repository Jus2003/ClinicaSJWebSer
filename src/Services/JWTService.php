<?php
namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTService {
    private $config;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../../config/jwt.php';
    }
    
    /**
     * Genera un JWT token
     */
    public function generateToken($userId, $username, $email = null, $roleId = null) {
        $payload = [
            'iss' => $this->config['issuer'],
            'aud' => $this->config['audience'],
            'iat' => time(),
            'exp' => time() + $this->config['expiration_time'],
            'user_id' => $userId,
            'username' => $username,
            'email' => $email,
            'role_id' => $roleId
        ];
        
        return JWT::encode($payload, $this->config['secret_key'], $this->config['algorithm']);
    }
    
    /**
     * Valida un JWT token
     */
    public function validateToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->config['secret_key'], $this->config['algorithm']));
            return (array) $decoded;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Extrae el token del header Authorization
     */
    public function getBearerToken($request) {
        $header = $request->getHeaderLine('Authorization');
        
        if (!empty($header)) {
            if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Obtiene el tiempo de expiración en segundos
     */
    public function getExpirationTime() {
        return $this->config['expiration_time'];
    }
}