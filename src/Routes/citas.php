<?php
use App\Controllers\CitaController;

$group->group('/citas', function ($subGroup) {
    // Rutas GET existentes
    $subGroup->get('/especialidad/{id_especialidad}/medico/{id_medico}', [CitaController::class, 'consultarPorEspecialidadYMedico']);
    $subGroup->get('/medico/{id_medico}', [CitaController::class, 'consultarPorEspecialidadYMedico']);
    $subGroup->get('/especialidad/{id_especialidad}', [CitaController::class, 'consultarPorEspecialidadYMedico']);
    $subGroup->get('/fechas', [CitaController::class, 'consultarPorRangoFechas']);
    $subGroup->post('/fechas', [CitaController::class, 'consultarPorRangoFechas']);
    $subGroup->get('/paciente/{id_paciente}', [CitaController::class, 'consultarPorPaciente']);
    $subGroup->get('/todas', [CitaController::class, 'listarTodas']);
    $subGroup->get('/todas-completas', [CitaController::class, 'listarTodasCompletas']);
    
    // ✅ NUEVAS RUTAS PARA EL FLUJO DE CITAS
    $subGroup->get('/especialidades-disponibles/{tipo_cita}', [CitaController::class, 'obtenerEspecialidadesDisponibles']);
    $subGroup->get('/medicos-por-especialidad/{id_especialidad}/{tipo_cita}', [CitaController::class, 'obtenerMedicosPorEspecialidad']);
    $subGroup->post('/horarios-disponibles', [CitaController::class, 'obtenerHorariosDisponibles']);
    $subGroup->post('/crear', [CitaController::class, 'crearCita']);
    $subGroup->post('/validar-disponibilidad', [CitaController::class, 'validarDisponibilidad']);
    
    // Rutas POST con JSON existentes
    $subGroup->post('/buscar-por-filtros', [CitaController::class, 'buscarCitasPorEspecialidadMedicoJSON']);
    $subGroup->post('/buscar-por-id', [CitaController::class, 'buscarCitaPorIdJSON']);
    $subGroup->post('/buscar-por-medico', [CitaController::class, 'obtenerCitasPorMedicoJSON']);
    $subGroup->post('/buscar-fechas-usuario', [CitaController::class, 'consultarCitasPorFechasYUsuario']);

    $subGroup->put('/cambiar-estado/{id_cita}', [CitaController::class, 'cambiarEstadoCita']);
    $subGroup->post('/cambiar-estado/{id_cita}', [CitaController::class, 'cambiarEstadoCita']); // También con POST
    $subGroup->post('/buscar-por-paciente', [CitaController::class, 'obtenerCitasPorPacienteJSON']);
});
?>