<?php
use App\Controllers\MedicoController;

$group->group('/medicos', function ($subGroup) {
    $subGroup->get('/listar', [MedicoController::class, 'listarTodos']);
    $subGroup->get('/especialidad/{id_especialidad}', [MedicoController::class, 'listarPorEspecialidad']);
    $subGroup->get('/disponibilidad/{id_medico}', [MedicoController::class, 'obtenerDisponibilidad']);
    $subGroup->get('/horarios/{id_medico}', [MedicoController::class, 'obtenerHorarios']);
    $subGroup->post('/buscar', [MedicoController::class, 'buscarMedicos']);
});
?>