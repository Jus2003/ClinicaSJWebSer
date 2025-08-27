<?php
use App\Controllers\MedicoController;

$app->group('/medicos', function ($group) {
    $group->get('/listar', [MedicoController::class, 'listarTodos']);
    $group->get('/especialidad/{id_especialidad}', [MedicoController::class, 'listarPorEspecialidad']);
    $group->post('/crear', [MedicoController::class, 'crearMedico']);

    // Nueva ruta para asignar/editar horarios
    $group->post('/{id_medico}/horarios', [MedicoController::class, 'asignarHorarios']);
});