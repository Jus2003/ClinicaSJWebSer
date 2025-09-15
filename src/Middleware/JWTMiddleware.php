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
        
        $token = $this->jwtService->getBearerToken($request);
        
        if (!$token) {
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
        
        if (!$decoded) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'status' => 401,
                'success' => false,
                'message' => 'Token inválido o expirado',
                'data' => null
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        
        // Agregar datos del usuario al request para usar en los controllers
        $request = $request->withAttribute('user', $decoded);
        
        return $handler->handle($request);
    }
}