<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

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

// 🔧 ENDPOINT DE LOGIN ESPECIAL PARA NAVEGADOR
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

// MANEJAR PETICIONES OPTIONS (PREFLIGHT)
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
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
        'ngrok_ready' => true, // 👈 Indicador para frontend
        'endpoints_disponibles' => [
            'POST /auth/login' => 'Iniciar sesión',
            'POST /auth/logout' => 'Cerrar sesión',
            'GET /auth/perfil' => 'Obtener perfil del usuario',
            'POST /auth/olvido-password' => 'Recuperar contraseña',
            'PUT /perfil/cambiar-password' => 'Cambiar contraseña (requiere sesión)',
            'GET /pacientes/buscar-cedula/{cedula}' => 'Buscar paciente por cédula',
            'GET /pacientes/historial-completo/{id_paciente}' => 'Historial clínico completo',
            'GET /pacientes/historial-cedula/{cedula}' => 'Historial clínico completo por cédula',
            'GET /pacientes/historial-lista' => 'Lista de pacientes para historial médico (según rol)',
            'GET /citas/especialidad/{id_especialidad}/medico/{id_medico}' => 'Citas por especialidad y médico',
            'GET /citas/medico/{id_medico}' => 'Citas por médico',
            'GET /citas/fechas?inicio=YYYY-MM-DD&fin=YYYY-MM-DD' => 'Citas por rango de fechas',
            'GET /especialidades/listar' => 'Lista de especialidades activas',
            'GET /medicos/listar' => 'Lista de médicos activos',
            'GET /medicos/especialidad/{id_especialidad}' => 'Médicos por especialidad',
            'GET /citas/paciente/{id_paciente}' => 'Citas de un paciente específico',
            'GET /citas/todas' => 'Todas las citas (admin/recepcionista)',
            'POST /citas/consultar-por-id' => 'Consultar cita específica por ID (JSON)',
            'POST /citas/consultar-por-fechas' => 'Consultar citas por rango de fechas (JSON)',
            'POST /pacientes/buscar-historial-id' => 'Buscar historial clínico por ID (JSON)',
            'POST /pacientes/buscar-historial-cedula' => 'Buscar historial clínico por cédula (JSON)',
            'POST /citas/buscar-por-filtros' => 'Buscar citas por especialidad/médico (JSON)',
            'POST /citas/buscar-por-id' => 'Buscar cita específica por ID (JSON)',
            'POST /citas/buscar-por-medico' => 'Buscar todas las citas de un médico por ID (JSON)',
            'POST /citas/buscar-fechas-usuario' => 'Citas por rango de fechas + paciente/médico (JSON)',

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

// 🔧 RUTA DE TEST MEJORADA
$app->get('/test', function ($request, $response) {
    $data = [
        'status' => 200,
        'success' => true,
        'message' => 'API funcionando correctamente ✅',
        'data' => [
            'timestamp' => time(),
            'fecha_hora' => date('Y-m-d H:i:s'),
            'session_active' => isset($_SESSION['user_id']) ? 'Sí' : 'No',
            'user_id' => $_SESSION['user_id'] ?? null,
            'cors_enabled' => true,
            'ngrok_compatible' => true
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

// Incluir rutas de especialidades
require __DIR__ . '/../src/Routes/especialidades.php';

// Incluir rutas de médicos
require __DIR__ . '/../src/Routes/medicos.php';

require __DIR__ . '/../src/Routes/triaje.php';

require __DIR__ . '/../src/Routes/recetas.php';

require_once __DIR__ . '/../src/Routes/historial.php';

// ============================================
// EJECUTAR LA APLICACIÓN
// ============================================
$app->run();
?>