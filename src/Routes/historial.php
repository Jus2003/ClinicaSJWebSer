<?php
use App\Controllers\HistorialController;

$app->group('/historial', function ($group) {
    // Obtener historial completo (todas las citas completadas)
    $group->get('/completo', [HistorialController::class, 'obtenerHistorialCompleto']);
    
    // Obtener historial de una cita específica
    $group->get('/cita/{id_cita}', [HistorialController::class, 'obtenerHistorialPorCita']);
    
    // Obtener historial por cédula del paciente
    $group->get('/cedula/{cedula}', [HistorialController::class, 'obtenerHistorialPorCedula']);
    
    // ✅ NUEVO: Obtener historial por ID del paciente
    $group->get('/paciente/{id_paciente}', [HistorialController::class, 'obtenerHistorialPorIdPaciente']);
});
?>