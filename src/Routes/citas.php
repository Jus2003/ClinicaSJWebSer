<?php
use App\Controllers\CitaController;

$app->group('/citas', function ($group) {
    // Rutas existentes
    $group->get('/especialidad/{id_especialidad}/medico/{id_medico}', [CitaController::class, 'consultarPorEspecialidadYMedico']);
    $group->get('/medico/{id_medico}', [CitaController::class, 'consultarPorEspecialidadYMedico']);
    $group->get('/especialidad/{id_especialidad}', [CitaController::class, 'consultarPorEspecialidadYMedico']);
    
    // ENDPOINT UNIFICADO - Acepta GET y POST
    $group->get('/fechas', [CitaController::class, 'consultarPorRangoFechas']);
    $group->post('/fechas', [CitaController::class, 'consultarPorRangoFechas']); // ← Agregar esta línea
    
    $group->get('/paciente/{id_paciente}', [CitaController::class, 'consultarPorPaciente']);
    $group->get('/todas', [CitaController::class, 'listarTodas']);
});
?>