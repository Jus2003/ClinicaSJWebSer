<?php
use App\Controllers\PerfilController;

$app->group('/perfil', function ($group) {
    $group->put('/cambiar-password', [PerfilController::class, 'cambiarPassword']);
});

$app->group('/auth', function ($group) {
    $group->post('/olvido-password', [PerfilController::class, 'olvidoPassword']);
});
?>