<?php
use App\Controllers\PacienteController;

$app->group('/pacientes', function ($group) {
    $group->get('/buscar-cedula/{cedula}', [PacienteController::class, 'buscarPorCedula']);
    $group->get('/historial-completo/{id_paciente}', [PacienteController::class, 'obtenerHistorialCompleto']);
    $group->get('/historial-cedula/{cedula}', [PacienteController::class, 'obtenerHistorialPorCedula']);
    $group->get('/historial-lista', [PacienteController::class, 'listarPacientesHistorial']);
    $group->get('/historial-lista/{rol}', [PacienteController::class, 'listarPacientesHistorialPorRol']); // ← NUEVA RUTA CON ROL
});
?>