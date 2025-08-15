<?php
use App\Controllers\AuthController;

$app->group('/auth', function ($group) {
    $group->post('/login', [AuthController::class, 'login']);
    $group->post('/logout', [AuthController::class, 'logout']);
    $group->get('/perfil', [AuthController::class, 'perfil']);
});
?>