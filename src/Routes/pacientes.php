<?php
use App\Controllers\PacienteController;

$app->group('/pacientes', function ($group) {
    $group->get('/buscar-cedula/{cedula}', [PacienteController::class, 'buscarPorCedula']);
    $group->get('/historial-completo/{id_paciente}', [PacienteController::class, 'obtenerHistorialCompleto']);
});
?>