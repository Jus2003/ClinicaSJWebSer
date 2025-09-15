<?php
use App\Controllers\AuthController;

// Usar $group porque este archivo se incluye dentro del grupo con middleware JWT
$group->group('/auth', function ($subGroup) {
    // ❌ NO incluir login aquí porque ya está en la sección pública del index.php
    // $subGroup->post('/login', [AuthController::class, 'login']);
    
    $subGroup->post('/logout', [AuthController::class, 'logout']);
    $subGroup->get('/perfil', [AuthController::class, 'perfil']);
    $subGroup->get('/verify-session', function ($request, $response) {
        try {
            // Ahora puedes acceder a los datos del usuario del JWT
            $userData = $request->getAttribute('user');
            
            $result = [
                'status' => 200,
                'success' => true,
                'jwt_active' => true,
                'user_data' => $userData,
                'timestamp' => date('Y-m-d H:i:s')
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
});
?>