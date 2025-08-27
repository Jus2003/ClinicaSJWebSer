<?php
namespace App\Services;

class ZoomService {
    
    public function generarEnlaceZoom($idCita, $fechaHora, $nombreMedico, $nombrePaciente) {
        // Generar ID único para la reunión
        $meetingId = 'cita-' . $idCita . '-' . time();
        
        // Generar contraseña simple
        $password = rand(100000, 999999);
        
        // En producción, aquí usarías la API de Zoom
        // Por ahora generamos enlaces simulados
        
        $enlaceVirtual = "https://zoom.us/j/{$meetingId}?pwd={$password}";
        
        return [
            'enlace_virtual' => $enlaceVirtual,
            'zoom_meeting_id' => $meetingId,
            'zoom_password' => $password,
            'zoom_start_url' => $enlaceVirtual . "&role=1", // Para el médico
            'instrucciones' => "Ingrese al enlace 5 minutos antes de la cita. ID de reunión: {$meetingId}, Contraseña: {$password}"
        ];
    }
    
    public function generarEnlaceGenerico($idCita) {
        $meetingId = 'cita-' . $idCita . '-' . time();
        $password = rand(100000, 999999);
        
        return [
            'enlace_virtual' => "https://meet.google.com/lookup/{$meetingId}",
            'zoom_meeting_id' => $meetingId,
            'zoom_password' => $password,
            'zoom_start_url' => "https://meet.google.com/lookup/{$meetingId}",
            'instrucciones' => "Clic en el enlace para unirse a la videollamada"
        ];
    }
}
?>