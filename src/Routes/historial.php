<?php
use App\Controllers\HistorialController;

$group->group('/historial', function ($subGroup) {
    // Obtener historial completo (todas las citas completadas)
    $subGroup->get('/completo', [HistorialController::class, 'obtenerHistorialCompleto']);
    
    // Obtener historial de una cita específica
    $subGroup->get('/cita/{id_cita}', [HistorialController::class, 'obtenerHistorialPorCita']);
    
    // Obtener historial por cédula del paciente
    $subGroup->get('/cedula/{cedula}', [HistorialController::class, 'obtenerHistorialPorCedula']);
    
    // ✅ NUEVO: Obtener historial por ID del paciente
    $subGroup->get('/paciente/{id_paciente}', [HistorialController::class, 'obtenerHistorialPorIdPaciente']);
});
?>