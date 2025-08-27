<?php
use App\Controllers\PacienteController;

$app->group('/pacientes', function ($group) {
    $group->get('/buscar-cedula/{cedula}', [PacienteController::class, 'buscarPorCedula']);
    $group->get('/historial-completo/{id_paciente}', [PacienteController::class, 'obtenerHistorialCompleto']);
    $group->get('/historial-cedula/{cedula}', [PacienteController::class, 'obtenerHistorialPorCedula']);
    
    // NUEVAS RUTAS POST para buscar historial con JSON
    $group->post('/buscar-historial-cedula', [PacienteController::class, 'buscarHistorialPorCedulaJSON']);
    $group->post('/buscar-historial-id', [PacienteController::class, 'obtenerHistorialPorIdJSON']);
    
    $group->get('/historial-lista', [PacienteController::class, 'listarPacientesHistorial']);
    $group->get('/historial-lista/{rol}', [PacienteController::class, 'listarPacientesHistorialPorRol']);
    $group->get('/listar', [PacienteController::class, 'listarTodos']);
    $group->post('/crear', [PacienteController::class, 'crearPaciente']);
});
?>