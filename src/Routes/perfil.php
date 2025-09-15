<?php
use App\Controllers\PerfilController;

// Usar $group porque este archivo se incluye dentro del grupo con middleware JWT
$group->group('/perfil', function ($subGroup) {
    $subGroup->put('/cambiar-password', [PerfilController::class, 'cambiarPassword']);
});


?>