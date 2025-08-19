<?php
use App\Controllers\CitaController;

$app->group('/citas', function ($group) {
    // Rutas existentes...
    $group->get('/especialidad/{id_especialidad}/medico/{id_medico}', [CitaController::class, 'consultarPorEspecialidadYMedico']);
    $group->get('/medico/{id_medico}', [CitaController::class, 'consultarPorEspecialidadYMedico']);
    $group->get('/especialidad/{id_especialidad}', [CitaController::class, 'consultarPorEspecialidadYMedico']);
    $group->get('/fechas', [CitaController::class, 'consultarPorRangoFechas']);
    $group->get('/paciente/{id_paciente}', [CitaController::class, 'consultarPorPaciente']);
    $group->get('/todas', [CitaController::class, 'listarTodas']);
    
    // NUEVAS RUTAS CON JSON
    $group->post('/consultar-por-id', [CitaController::class, 'consultarPorIdJson']);
    $group->post('/consultar-por-fechas', [CitaController::class, 'consultarPorRangoFechasJson']);
});