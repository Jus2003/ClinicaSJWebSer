<?php
use App\Controllers\TriajeController;

$app->group('/triaje', function ($group) {
    // GET - Obtener todas las preguntas de triaje activas
    $group->get('/preguntas', [TriajeController::class, 'obtenerPreguntas']);
    
    // POST - Enviar respuestas completas del triaje
    $group->post('/responder', [TriajeController::class, 'responderTriaje']);
    
    // GET - Obtener triaje completo de una cita específica
    $group->get('/cita/{id_cita}', [TriajeController::class, 'obtenerTriajePorCita']);
    
    // GET - Verificar si una cita tiene triaje y su estado
    $group->get('/verificar/{id_cita}', [TriajeController::class, 'verificarEstadoTriaje']);
});
?>