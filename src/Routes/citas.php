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
    $group->get('/todas', [CitaController::class, 'listarTodas']);
    $group->get('/todas-completas', [CitaController::class, 'listarTodasCompletas']);
    
    // ✅ NUEVAS RUTAS PARA EL FLUJO DE CITAS
    $group->get('/especialidades-disponibles/{tipo_cita}', [CitaController::class, 'obtenerEspecialidadesDisponibles']);
    $group->get('/medicos-por-especialidad/{id_especialidad}/{tipo_cita}', [CitaController::class, 'obtenerMedicosPorEspecialidad']);
    $group->post('/horarios-disponibles', [CitaController::class, 'obtenerHorariosDisponibles']);
    $group->post('/crear', [CitaController::class, 'crearCita']);
    $group->post('/validar-disponibilidad', [CitaController::class, 'validarDisponibilidad']);
    
    // Rutas POST con JSON existentes
    $group->post('/buscar-por-filtros', [CitaController::class, 'buscarCitasPorEspecialidadMedicoJSON']);
    $group->post('/buscar-por-id', [CitaController::class, 'buscarCitaPorIdJSON']);
    $group->post('/buscar-por-medico', [CitaController::class, 'obtenerCitasPorMedicoJSON']);
    $group->post('/buscar-fechas-usuario', [CitaController::class, 'consultarCitasPorFechasYUsuario']);

    $group->put('/cambiar-estado/{id_cita}', [CitaController::class, 'cambiarEstadoCita']);
    $group->post('/cambiar-estado/{id_cita}', [CitaController::class, 'cambiarEstadoCita']); // También con POST
    
});
?>