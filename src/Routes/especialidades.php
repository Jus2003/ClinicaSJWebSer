<?php
use App\Controllers\EspecialidadController;

$app->group('/especialidades', function ($group) {
    $group->get('/listar', [EspecialidadController::class, 'listarTodas']); // Método original
    $group->get('/todas-completas', [EspecialidadController::class, 'listarTodasCompletas']); // NUEVO método global
});
?>