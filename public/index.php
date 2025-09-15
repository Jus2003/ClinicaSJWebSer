<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\Middleware\JWTMiddleware;

// Configurar zona horaria
date_default_timezone_set('America/Guayaquil');

// Iniciar sesiones
session_start();

// Crear la aplicación Slim
$app = AppFactory::create();

// 🔧 RESTAURAR ESTA LÍNEA:
$app->setBasePath('/citas-medicas-api/public');

// Middleware para parsing del body JSON
$app->addBodyParsingMiddleware();

// CORS MEJORADO PARA NGROK
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, ngrok-skip-browser-warning, User-Agent')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Max-Age', '3600')
        ->withHeader('Vary', 'Origin');
});

// ============================================
// RUTAS PÚBLICAS (SIN JWT TOKEN)
// ============================================

// 🔧 ENDPOINT ESPECIAL PARA BYPASS DE NGROK
$app->get('/bypass-test', function ($request, $response) {
    // Headers especiales para navegadores
    $response = $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Accept')
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Cache-Control', 'no-cache');
    
    $data = [
        'status' => 200,
        'success' => true,
        'message' => 'Bypass test funcionando ✅',
        'timestamp' => time(),
        'ngrok_ready' => true
    ];
    
    $response->getBody()->write(json_encode($data));
    return $response;
});

// 🔧 ENDPOINT DE LOGIN ESPECIAL PARA NAVEGADOR (MANTENER SI LO NECESITAS)
$app->post('/browser-login', function ($request, $response) {
    // Headers especiales
    $response = $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Accept')
        ->withHeader('Content-Type', 'application/json');
    
    $data = $request->getParsedBody();
    
    // Simulación de login para prueba
    if (empty($data['usuario']) || empty($data['password'])) {
        $result = [
            'status' => 400,
            'success' => false,
            'message' => 'Usuario y contraseña requeridos'
        ];
    } else {
        $result = [
            'status' => 200,
            'success' => true,
            'message' => 'Login de prueba exitoso',
            'data' => [
                'usuario' => [
                    'nombre' => 'Usuario',
                    'apellido' => 'Prueba',
                    'email' => 'test@test.com',
                    'rol' => 'Administrador'
                ]
            ]
        ];
    }
    
    $response->getBody()->write(json_encode($result));
    return $response;
});

// RUTA DE BIENVENIDA
$app->get('/', function ($request, $response) {
    $data = [
        'mensaje' => '🎉 API Citas Médicas - Sistema Completo',
        'version' => '1.0.0',
        'php_version' => phpversion(),
        'servidor' => 'WAMP Server',
        'fecha_hora' => date('Y-m-d H:i:s'),
        'ngrok_ready' => true, // 👈 Indicador para frontend
        'endpoints_disponibles' => [
            'POST /auth/login' => 'Iniciar sesión (PÚBLICO)',
            'POST /auth/olvido-password' => 'Recuperar contraseña (PÚBLICO)',
            '🔒 TODOS LOS DEMÁS' => 'Requieren Bearer Token JWT',
            'GET /bypass-test' => 'Test de conexión (PÚBLICO)',
        ],
        'jwt_info' => [
            'auth_required' => 'Bearer Token en Authorization header',
            'token_expiration' => '1 hora',
            'public_endpoints' => [
                'POST /auth/login',
                'POST /auth/olvido-password',
                'GET /',
                'GET /bypass-test'
            ]
        ]
    ];
    
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json');
});

// 📝 ENDPOINT DE TEST PARA CLASES Y MÉTODOS
$app->get('/test-models', function ($request, $response) {
    try {
        $result = [
            'status' => 200,
            'success' => true,
            'message' => 'Test de modelos completado',
            'data' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'autoload_funcionando' => class_exists('App\Models\Usuario'),
                'paciente_existe' => class_exists('App\Models\Paciente'),
                'metodos_paciente' => class_exists('App\Models\Paciente') ? 
                    get_class_methods('App\Models\Paciente') : 'Clase no existe'
            ]
        ];
        
        $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
        
    } catch (\Exception $e) {
        $result = [
            'status' => 500,
            'success' => false,
            'message' => 'Error en test: ' . $e->getMessage(),
            'data' => [
                'trace' => $e->getTraceAsString()
            ]
        ];
        $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// Test temporal - agregar antes del grupo protegido
$app->get('/test-jwt-config', function ($request, $response) {
    $config = require __DIR__ . '/../config/jwt.php';
    
    $result = [
        'secret_key_preview' => substr($config['secret_key'], 0, 20) . '...',
        'secret_key_length' => strlen($config['secret_key']),
        'algorithm' => $config['algorithm'],
        'expiration_time' => $config['expiration_time']
    ];
    
    $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

// Debug JWT - reemplazar el anterior
$app->post('/debug-jwt', function ($request, $response) {
    try {
        $data = $request->getParsedBody();
        $token = $data['token'] ?? '';
        
        if (empty($token)) {
            throw new Exception('Token requerido en el body');
        }
        
        $jwtService = new \App\Services\JWTService();
        
        $result = [
            'step1_token_received' => 'OK',
            'step2_token_length' => strlen($token),
            'step3_token_preview' => substr($token, 0, 50) . '...',
        ];
        
        // Validación directa
        $decoded = $jwtService->validateToken($token);
        
        if ($decoded !== false) {
            $result['step4_validation'] = 'SUCCESS';
            $result['step5_decoded_data'] = $decoded;
            $result['step6_data_type'] = gettype($decoded);
        } else {
            $result['step4_validation'] = 'FAILED';
            $result['step5_error'] = 'Token validation returned false';
        }
        
        $result['step7_current_time'] = time();
        $result['step8_current_date'] = date('Y-m-d H:i:s');
        
        $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
        
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// 🔑 RUTAS PÚBLICAS SIN TOKEN - SOLO LOGIN Y RECUPERAR CONTRASEÑA
$app->post('/auth/login', [App\Controllers\AuthController::class, 'login']);
$app->post('/auth/olvido-password', [App\Controllers\PerfilController::class, 'olvidoPassword']);

// ============================================
// TODAS LAS DEMÁS RUTAS REQUIEREN JWT TOKEN
// ============================================

$app->group('', function ($group) {
    
    // Incluir rutas de autenticación (logout, perfil, etc. - TODO EXCEPTO login)
    require __DIR__ . '/../src/Routes/auth.php';
    
    // Incluir rutas de perfil
    require __DIR__ . '/../src/Routes/perfil.php';
    
    // Incluir rutas de pacientes
    require __DIR__ . '/../src/Routes/pacientes.php';
    
    // Incluir rutas de citas
    require __DIR__ . '/../src/Routes/citas.php';
    
    // Incluir rutas de especialidades
    require __DIR__ . '/../src/Routes/especialidades.php';
    
    // Incluir rutas de médicos
    require __DIR__ . '/../src/Routes/medicos.php';
    
    // Incluir rutas de triaje
    require __DIR__ . '/../src/Routes/triaje.php';
    
    // Incluir rutas de recetas
    require __DIR__ . '/../src/Routes/recetas.php';
    
    // Incluir rutas de historial
    require_once __DIR__ . '/../src/Routes/historial.php';
    
})->add(new JWTMiddleware()); // 🔒 AQUÍ SE APLICA EL MIDDLEWARE JWT

// MANEJAR PETICIONES OPTIONS (PREFLIGHT)
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

// Middleware de errores
$app->addErrorMiddleware(true, true, true);

// ============================================
// EJECUTAR LA APLICACIÓN
// ============================================
$app->run();
?>