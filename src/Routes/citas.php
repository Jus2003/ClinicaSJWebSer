<?php
use App\Controllers\CitaController;

$app->group('/citas', function ($group) {
    // Consultar por especialidad y médico
    $group->get('/especialidad/{id_especialidad}/medico/{id_medico}', [CitaController::class, 'consultarPorEspecialidadYMedico']);
    // Solo por médico
    $group->get('/medico/{id_medico}', [CitaController::class, 'consultarPorEspecialidadYMedico']);
    // Solo por especialidad
    $group->get('/especialidad/{id_especialidad}', [CitaController::class, 'consultarPorEspecialidadYMedico']);
    // Por rango de fechas
    $group->get('/fechas', [CitaController::class, 'consultarPorRangoFechas']);
});
?>