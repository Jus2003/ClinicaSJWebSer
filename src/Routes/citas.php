<?php
use App\Controllers\CitaController;

$app->group('/citas', function ($group) {
    // Rutas GET existentes
    $group->get('/especialidad/{id_especialidad}/medico/{id_medico}', [CitaController::class, 'consultarPorEspecialidadYMedico']);
    $group->get('/medico/{id_medico}', [CitaController::class, 'consultarPorEspecialidadYMedico']);
    $group->get('/especialidad/{id_especialidad}', [CitaController::class, 'consultarPorEspecialidadYMedico']);
    $group->get('/fechas', [CitaController::class, 'consultarPorRangoFechas']);
    $group->post('/fechas', [CitaController::class, 'consultarPorRangoFechas']);
    $group->get('/paciente/{id_paciente}', [CitaController::class, 'consultarPorPaciente']);
    $group->get('/todas', [CitaController::class, 'listarTodas']); // Método original
    
    // NUEVA RUTA MEJORADA para recepcionista
    $group->get('/todas-completas', [CitaController::class, 'listarTodasCompletas']);
    
    // RUTAS POST con JSON existentes
    $group->post('/buscar-por-filtros', [CitaController::class, 'buscarCitasPorEspecialidadMedicoJSON']);
    $group->post('/buscar-por-id', [CitaController::class, 'buscarCitaPorIdJSON']);
    $group->post('/buscar-por-medico', [CitaController::class, 'obtenerCitasPorMedicoJSON']);
    $group->post('/buscar-fechas-usuario', [CitaController::class, 'consultarCitasPorFechasYUsuario']);
});
?>