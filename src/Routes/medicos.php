<?php
use App\Controllers\MedicoController;

$app->group('/medicos', function ($group) {
    $group->get('/listar', [MedicoController::class, 'listarTodos']);
    $group->get('/especialidad/{id_especialidad}', [MedicoController::class, 'listarPorEspecialidad']);
    $group->post('/crear', [MedicoController::class, 'crearMedico']);

    // ✅ RUTAS PARA HORARIOS
    $group->post('/{id_medico}/horarios', [MedicoController::class, 'asignarHorarios']);      // Asignar/Editar horarios
    $group->put('/{id_medico}/horarios', [MedicoController::class, 'asignarHorarios']);       // También con PUT
    $group->get('/{id_medico}/horarios', [MedicoController::class, 'consultarHorarios']);     // Consultar horarios
});