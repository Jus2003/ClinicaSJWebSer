<?php
use App\Controllers\RecetaController;

$group->group('/recetas', function ($subGroup) {
    // Crear nueva receta médica directamente para una cita
    $subGroup->post('/crear', [RecetaController::class, 'crearReceta']);
    
    // Obtener recetas por cita (en lugar de por consulta)
    $subGroup->get('/cita/{id_cita}', [RecetaController::class, 'obtenerRecetasPorCita']);
});
?>