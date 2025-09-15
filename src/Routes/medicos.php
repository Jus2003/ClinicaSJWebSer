<?php
use App\Controllers\MedicoController;

$group->group('/medicos', function ($subGroup) {
    $subGroup->get('/listar', [MedicoController::class, 'listarTodos']);
    $subGroup->get('/especialidad/{id_especialidad}', [MedicoController::class, 'listarPorEspecialidad']);
    $subGroup->get('/disponibilidad/{id_medico}', [MedicoController::class, 'obtenerDisponibilidad']);
    $subGroup->get('/{id_medico}/horarios', [MedicoController::class, 'consultarHorarios']);
    
    // ✅ AGREGAR ESTA LÍNEA:
    $subGroup->post('/{id_medico}/horarios', [MedicoController::class, 'asignarHorarios']);
    
    $subGroup->post('/buscar', [MedicoController::class, 'buscarMedicos']);
});
?>