<?php
use App\Controllers\RecetaController;

$app->group('/recetas', function ($group) {
    // Crear nueva receta médica directamente para una cita
    $group->post('/crear', [RecetaController::class, 'crearReceta']);
    
    // Obtener recetas por cita (en lugar de por consulta)
    $group->get('/cita/{id_cita}', [RecetaController::class, 'obtenerRecetasPorCita']);
});
?>