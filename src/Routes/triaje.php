<?php
use App\Controllers\TriajeController;

$group->group('/triaje', function ($subGroup) {
    // GET - Obtener todas las preguntas de triaje activas
    $subGroup->get('/preguntas', [TriajeController::class, 'obtenerPreguntas']);
    
    // POST - Enviar respuestas completas del triaje
    $subGroup->post('/responder', [TriajeController::class, 'responderTriaje']);
    
    // GET - Obtener triaje completo de una cita específica
    $subGroup->get('/cita/{id_cita}', [TriajeController::class, 'obtenerTriajePorCita']);
    
    // GET - Verificar si una cita tiene triaje y su estado
    $subGroup->get('/verificar/{id_cita}', [TriajeController::class, 'verificarEstadoTriaje']);
});
?>