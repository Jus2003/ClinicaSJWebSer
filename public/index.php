<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

// Configurar zona horaria
date_default_timezone_set('America/Guayaquil');

// Iniciar sesiones
session_start();

// Crear la aplicación Slim
$app = AppFactory::create();

// Configurar base path para WAMP
$app->setBasePath('/citas-medicas-api/public');

// Middleware para parsing del body JSON
$app->addBodyParsingMiddleware();

// Middleware manual para CORS
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Middleware de errores
$app->addErrorMiddleware(true, true, true);

// ============================================
// RUTAS PRINCIPALES
// ============================================

// Ruta de bienvenida - SOLO UNA VEZ
$app->get('/', function ($request, $response) {
    $data = [
        'mensaje' => '🎉 API Citas Médicas - Sistema Completo',
        'version' => '1.0.0',
        'php_version' => phpversion(),
        'servidor' => 'WAMP Server',
        'fecha_hora' => date('Y-m-d H:i:s'),
        'endpoints_disponibles' => [
            'POST /auth/login' => 'Iniciar sesión',
            'POST /auth/logout' => 'Cerrar sesión',
            'GET /auth/perfil' => 'Obtener perfil del usuario',
            'POST /auth/olvido-password' => 'Recuperar contraseña',
            'PUT /perfil/cambiar-password' => 'Cambiar contraseña (requiere sesión)',
            'GET /pacientes/buscar-cedula/{cedula}' => 'Buscar paciente por cédula',
            'GET /pacientes/historial-completo/{id_paciente}' => 'Historial clínico completo',
            'GET /citas/especialidad/{id_especialidad}/medico/{id_medico}' => 'Citas por especialidad y médico',
            'GET /citas/medico/{id_medico}' => 'Citas por médico',
            'GET /citas/fechas?inicio=YYYY-MM-DD&fin=YYYY-MM-DD' => 'Citas por rango de fechas',
            'GET /test' => 'Prueba de conectividad'
        ],
        'ejemplos' => [
            'login' => [
                'url' => '/auth/login',
                'method' => 'POST',
                'body' => ['usuario' => 'admin', 'password' => 'admin123']
            ],
            'cambiar_password' => [
                'url' => '/perfil/cambiar-password',
                'method' => 'PUT',
                'body' => [
                    'password_actual' => 'password_actual',
                    'password_nueva' => 'nueva_password',
                    'confirmar_password' => 'nueva_password'
                ]
            ],
            'olvido_password' => [
                'url' => '/auth/olvido-password',
                'method' => 'POST',
                'body' => ['email' => 'usuario@email.com']
            ]
        ]
    ];
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
});

// Ruta de test
$app->get('/test', function ($request, $response) {
    $data = [
        'status' => 200,
        'success' => true,
        'message' => 'API funcionando correctamente',
        'data' => [
            'timestamp' => time(),
            'session_active' => isset($_SESSION['user_id']) ? 'Sí' : 'No',
            'user_id' => $_SESSION['user_id'] ?? null
        ]
    ];
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json');
});

// Test endpoint para debuggear
$app->get('/debug/paciente-test', function ($request, $response) {
    try {
        // Test básico de conexión a BD
        $database = new App\Config\Database();
        $db = $database->getConnection();
        
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 4";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $total = $stmt->fetch();
        
        $result = [
            'status' => 200,
            'success' => true,
            'message' => 'Test de conexión exitoso',
            'data' => [
                'total_pacientes' => $total['total'],
                'clase_paciente_existe' => class_exists('App\Models\Paciente'),
                'metodos_disponibles' => class_exists('App\Models\Paciente') ? 
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

// ============================================
// INCLUIR RUTAS EXTERNAS
// ============================================

// Incluir rutas de autenticación
require __DIR__ . '/../src/Routes/auth.php';

// Incluir rutas de perfil
require __DIR__ . '/../src/Routes/perfil.php';

require __DIR__ . '/../src/Routes/pacientes.php';

require __DIR__ . '/../src/Routes/citas.php';

// ============================================
// EJECUTAR LA APLICACIÓN
// ============================================
$app->run();
?>