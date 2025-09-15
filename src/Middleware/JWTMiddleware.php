<?php
namespace App\Middleware;

use App\Services\JWTService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class JWTMiddleware {
    
    private $jwtService;
    
    public function __construct() {
        $this->jwtService = new JWTService();
    }
    
    public function __invoke(Request $request, RequestHandler $handler): Response {
        
        // ✅ PERMITIR PETICIONES OPTIONS SIN VALIDAR JWT
        if ($request->getMethod() === 'OPTIONS') {
            return $handler->handle($request);
        }
        
        error_log("=== JWT MIDDLEWARE DEBUG ===");
        error_log("Request URI: " . $request->getUri()->getPath());
        error_log("Request Method: " . $request->getMethod());
        
        $token = $this->jwtService->getBearerToken($request);
        
        error_log("Token extracted: " . ($token ? 'YES' : 'NO'));
        if ($token) {
            error_log("Token: " . substr($token, 0, 50) . "...");
        }
        
        if (!$token) {
            error_log("NO TOKEN PROVIDED");
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'status' => 401,
                'success' => false,
                'message' => 'Token no proporcionado',
                'data' => null
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        
        $decoded = $this->jwtService->validateToken($token);
        
        error_log("Token validation result: " . ($decoded ? 'VALID' : 'INVALID'));
        
        if (!$decoded) {
            error_log("TOKEN VALIDATION FAILED");
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'status' => 401,
                'success' => false,
                'message' => 'Token inválido o expirado',
                'data' => null,
                'debug' => [
                    'timestamp' => time(),
                    'token_length' => strlen($token)
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        
        error_log("TOKEN VALIDATION SUCCESS");
        error_log("============================");
        
        // Agregar datos del usuario al request para usar en los controllers
        $request = $request->withAttribute('user', $decoded);
        
        return $handler->handle($request);
    }
}