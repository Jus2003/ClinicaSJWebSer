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
    
    public function validateToken($token) {
        try {
            error_log("=== JWT VALIDATION DEBUG ===");
            error_log("Token: " . substr($token, 0, 50) . "...");
            error_log("Secret key: " . substr($this->config['secret_key'], 0, 20) . "...");
            error_log("Algorithm: " . $this->config['algorithm']);
            
            $decoded = JWT::decode($token, new Key($this->config['secret_key'], $this->config['algorithm']));
            $result = (array) $decoded;
            
            error_log("Validation SUCCESS");
            error_log("Decoded: " . json_encode($result));
            error_log("============================");
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Validation FAILED: " . $e->getMessage());
            error_log("Exception type: " . get_class($e));
            error_log("============================");
            return false;
        }
    }
    
    public function getBearerToken($request) {
        $header = $request->getHeaderLine('Authorization');
        
        if (!empty($header)) {
            if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    public function getExpirationTime() {
        return $this->config['expiration_time'];
    }
}