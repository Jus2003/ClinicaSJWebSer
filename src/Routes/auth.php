<?php
use App\Controllers\AuthController;

$app->group('/auth', function ($group) {
    $group->post('/login', [AuthController::class, 'login']);
    $group->post('/logout', [AuthController::class, 'logout']);
    $group->get('/perfil', [AuthController::class, 'perfil']);
});
$app->get('/auth/verify-session', function ($request, $response) {
    try {
        $result = [
            'status' => 200,
            'success' => true,
            'session_active' => isset($_SESSION['user_id']),
            'user_id' => $_SESSION['user_id'] ?? null,
            'session_id' => session_id(),
            'data' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'session_data' => $_SESSION ?? []
            ]
        ];
        
        $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
        
    } catch (\Exception $e) {
        $result = [
            'status' => 500,
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
        $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});
?>