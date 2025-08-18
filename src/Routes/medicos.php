<?php
use App\Controllers\MedicoController;

$app->group('/medicos', function ($group) {
    $group->get('/listar', [MedicoController::class, 'listarTodos']);
    $group->get('/especialidad/{id_especialidad}', [MedicoController::class, 'listarPorEspecialidad']);
});
?>