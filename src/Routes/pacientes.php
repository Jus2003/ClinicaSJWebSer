<?php
use App\Controllers\PacienteController;

$group->group('/pacientes', function ($subGroup) {
    $subGroup->get('/buscar-cedula/{cedula}', [PacienteController::class, 'buscarPorCedula']);
    $subGroup->get('/historial-completo/{id_paciente}', [PacienteController::class, 'obtenerHistorialCompleto']);
    $subGroup->get('/historial-cedula/{cedula}', [PacienteController::class, 'obtenerHistorialPorCedula']);
    
    // NUEVAS RUTAS POST para buscar historial con JSON
    $subGroup->post('/buscar-historial-cedula', [PacienteController::class, 'buscarHistorialPorCedulaJSON']);
    $subGroup->post('/buscar-historial-id', [PacienteController::class, 'obtenerHistorialPorIdJSON']);
    
    $subGroup->get('/historial-lista', [PacienteController::class, 'listarPacientesHistorial']);
    $subGroup->get('/historial-lista/{rol}', [PacienteController::class, 'listarPacientesHistorialPorRol']);
    $subGroup->get('/listar', [PacienteController::class, 'listarTodos']);
    $subGroup->post('/crear', [PacienteController::class, 'crearPaciente']);
});
?>