<?php
use App\Controllers\EspecialidadController;

$app->group('/especialidades', function ($group) {
    $group->get('/listar', [EspecialidadController::class, 'listarTodas']);
});
?>