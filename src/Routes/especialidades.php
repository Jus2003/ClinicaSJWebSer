<?php
use App\Controllers\EspecialidadController;

$group->group('/especialidades', function ($subGroup) {
    $subGroup->get('/listar', [EspecialidadController::class, 'listarTodas']); // Método original
    $subGroup->get('/todas-completas', [EspecialidadController::class, 'listarTodasCompletas']); // NUEVO método global
});
?>