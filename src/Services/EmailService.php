<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use App\Config\EmailConfig;

class EmailService {

    private function enviarEmail($para, $asunto, $mensajeHTML, $mensajeTextoPlano) {
        try {
            $mail = new PHPMailer(true);

            // Configuración SMTP desde EmailConfig
            $mail->isSMTP();
            $mail->Host       = EmailConfig::SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = EmailConfig::SMTP_USERNAME;
            $mail->Password   = EmailConfig::SMTP_PASSWORD;
            $mail->SMTPSecure = 'tls';
            $mail->Port       = EmailConfig::SMTP_PORT;

            // Remitente
            $mail->setFrom(EmailConfig::SMTP_FROM_EMAIL, EmailConfig::SMTP_FROM_NAME);

            // Destinatario
            $mail->addAddress($para);

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $mensajeHTML;       // plantilla HTML
            $mail->AltBody = $mensajeTextoPlano; // versión texto plano

            $mail->send();

            return ['success' => true, 'message' => 'Correo enviado correctamente'];

        } catch (Exception $e) {
            error_log("Error enviando email: " . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo enviar el correo: ' . $e->getMessage()];
        }
    }

    
    public function enviarPasswordTemporal($email, $nombreCompleto, $usuario, $passwordTemporal) {
        $mail = new PHPMailer(true);
        
        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = EmailConfig::SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = EmailConfig::SMTP_USERNAME;
            $mail->Password = EmailConfig::SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = EmailConfig::SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            
            // Remitente y destinatario
            $mail->setFrom(EmailConfig::SMTP_FROM_EMAIL, EmailConfig::SMTP_FROM_NAME);
            $mail->addAddress($email, $nombreCompleto);
            $mail->addReplyTo(EmailConfig::SUPPORT_EMAIL, 'Soporte Clínica SJ');
            
            // Contenido del email
            $mail->isHTML(true);
            $mail->Subject = '🔐 Recuperación de Contraseña - Clínica SJ';
            $mail->Body = $this->generarPlantillaPasswordTemporal($nombreCompleto, $usuario, $passwordTemporal);
            $mail->AltBody = $this->generarTextoPlano($nombreCompleto, $usuario, $passwordTemporal);
            
            $mail->send();
            return ['success' => true, 'message' => 'Email enviado correctamente'];
            
        } catch (Exception $e) {
            error_log("Error enviando email a {$email}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al enviar email: ' . $e->getMessage()];
        }
    }
    
    private function generarPlantillaPasswordTemporal($nombreCompleto, $usuario, $passwordTemporal) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Recuperación de Contraseña</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 30px 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px 20px; background: #f8f9fa; }
                .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; background: #f8f9fa; border-radius: 0 0 8px 8px; }
                .password-box { background: #e3f2fd; border-left: 4px solid #2196F3; padding: 20px; margin: 20px 0; border-radius: 5px; }
                .password { font-size: 24px; font-weight: bold; color: #1976D2; letter-spacing: 2px; text-align: center; padding: 10px; background: white; border-radius: 5px; margin: 10px 0; }
                .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
                .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 15px 0; }
                h1 { margin: 0; font-size: 28px; }
                h2 { color: #007bff; margin-top: 0; }
                .steps { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .step { margin: 10px 0; padding: 10px; border-left: 3px solid #28a745; background: #f8fff9; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏥 Clínica SJ</h1>
                    <p>Sistema de Gestión Médica</p>
                </div>
                <div class='content'>
                    <h2>🔐 Recuperación de Contraseña</h2>
                    <p>Hola <strong>{$nombreCompleto}</strong>,</p>
                    <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta <strong>{$usuario}</strong>.</p>
                    
                    <div class='password-box'>
                        <h3 style='margin-top: 0; color: #1976D2;'>🎯 Tu contraseña temporal es:</h3>
                        <div class='password'>{$passwordTemporal}</div>
                        <p style='text-align: center; margin: 0; font-size: 14px; color: #666;'>
                            <em>Esta contraseña es temporal y debe ser cambiada en tu primer inicio de sesión</em>
                        </p>
                    </div>
                    
                    <div class='steps'>
                        <h3>📋 Pasos a seguir:</h3>
                        <div class='step'>
                            <strong>1.</strong> Inicia sesión con tu usuario: <strong>{$usuario}</strong>
                        </div>
                        <div class='step'>
                            <strong>2.</strong> Usa la contraseña temporal mostrada arriba
                        </div>
                        <div class='step'>
                            <strong>3.</strong> El sistema te pedirá cambiar la contraseña por una nueva
                        </div>
                        <div class='step'>
                            <strong>4.</strong> Elige una contraseña segura (mínimo 6 caracteres)
                        </div>
                    </div>
                    
                    <div class='warning'>
                        <strong>⚠️ Importante:</strong><br>
                        • Esta contraseña temporal expira en 24 horas<br>
                        • Por seguridad, cámbiala inmediatamente después del login<br>
                        • Si no solicitaste este cambio, contacta con soporte
                    </div>
                    
                    <p style='text-align: center;'>
                        <a href='" . EmailConfig::SYSTEM_URL . "' class='btn'>🔗 Acceder al Sistema</a>
                    </p>
                </div>
                <div class='footer'>
                    <p><strong>📧 Este es un mensaje automático del Sistema de Clínica SJ</strong></p>
                    <p>Si tienes alguna pregunta, contacta con nosotros en " . EmailConfig::SUPPORT_EMAIL . "</p>
                    <p style='font-size: 10px; color: #999;'>Este email fue enviado automáticamente, por favor no responder a este mensaje.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function generarTextoPlano($nombreCompleto, $usuario, $passwordTemporal) {
        return "RECUPERACIÓN DE CONTRASEÑA - CLÍNICA SJ\n\n" .
               "Hola {$nombreCompleto},\n\n" .
               "Tu contraseña temporal es: {$passwordTemporal}\n\n" .
               "Usuario: {$usuario}\n\n" .
               "Pasos a seguir:\n" .
               "1. Inicia sesión con tu usuario y esta contraseña temporal\n" .
               "2. El sistema te pedirá cambiar la contraseña\n" .
               "3. Elige una contraseña segura\n\n" .
               "Esta contraseña temporal expira en 24 horas.\n\n" .
               "Sistema de Clínica SJ\n" .
               "Soporte: " . EmailConfig::SUPPORT_EMAIL;
    }

    // ✅ AGREGAR ESTOS MÉTODOS AL EmailService
public function enviarNotificacionCitaMedico($emailMedico, $nombreMedico, $datosCita) {
    try {
        $asunto = "👨‍⚕️ Nueva Cita Asignada - " . date('d/m/Y', strtotime($datosCita['fecha_cita'])) . " a las " . date('H:i', strtotime($datosCita['hora_cita']));
        $mensaje = $this->generarPlantillaCitaMedico($nombreMedico, $datosCita);
        $textoPlano = $this->generarTextoPlanoNotificacionCita($nombreMedico, $datosCita, 'medico');
        
        return $this->enviarEmail($emailMedico, $asunto, $mensaje, $textoPlano);
        
    } catch (\Exception $e) {
        error_log("Error enviando notificación de cita a médico: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al enviar notificación: ' . $e->getMessage()];
    }
}

public function enviarNotificacionCitaPaciente($emailPaciente, $nombrePaciente, $datosCita) {
    try {
        $asunto = "📅 ¡Cita Médica Confirmada! - " . date('d/m/Y', strtotime($datosCita['fecha_cita']));
        $mensaje = $this->generarPlantillaCitaPaciente($nombrePaciente, $datosCita);
        $textoPlano = $this->generarTextoPlanoNotificacionCita($nombrePaciente, $datosCita, 'paciente');
        
        return $this->enviarEmail($emailPaciente, $asunto, $mensaje, $textoPlano);
        
    } catch (\Exception $e) {
        error_log("Error enviando notificación de cita a paciente: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al enviar notificación: ' . $e->getMessage()];
    }
}

private function generarPlantillaCitaMedico($nombreMedico, $datosCita) {
    $fechaFormateada = date('l, d \d\e F \d\e Y', strtotime($datosCita['fecha_cita']));
    $horaFormateada = date('H:i', strtotime($datosCita['hora_cita']));
    
    $tipoIcono = $datosCita['tipo_cita'] === 'virtual' ? '🎥' : '🏥';
    $tipoTexto = $datosCita['tipo_cita'] === 'virtual' ? 'Virtual' : 'Presencial';

    return "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Nueva Cita Asignada</title>
        <style>
            * { box-sizing: border-box; }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background-color: #f0f4f8;
            }
            .container { 
                max-width: 650px; 
                margin: 20px auto; 
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                overflow: hidden;
            }
            .header { 
                background: linear-gradient(135deg, #2196F3, #1976D2); 
                color: white; 
                padding: 30px 25px; 
                text-align: center; 
            }
            .header h1 { 
                margin: 0; 
                font-size: 28px; 
                font-weight: 300;
            }
            .content { padding: 30px 25px; }
            .greeting {
                font-size: 18px;
                margin-bottom: 25px;
                color: #1565C0;
            }
            .paciente-card {
                background: #e3f2fd;
                border-radius: 10px;
                padding: 25px;
                margin: 25px 0;
                border-left: 5px solid #2196F3;
            }
            .info-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                margin: 20px 0;
            }
            .info-item {
                background: white;
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .info-item strong {
                display: block;
                color: #1976D2;
                font-size: 14px;
                margin-bottom: 5px;
            }
            .motivo-section {
                background: #fff3e0;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                border-left: 4px solid #ff9800;
            }
            .footer {
                background: #f8f9fa;
                padding: 25px;
                text-align: center;
                color: #666;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>👨‍⚕️ Nueva Cita Asignada</h1>
                <div>Se ha programado una nueva cita médica</div>
            </div>
            
            <div class='content'>
                <div class='greeting'>
                    Estimado Dr. <strong>{$nombreMedico}</strong>,
                </div>
                
                <p>Se ha agendado una nueva cita médica a su nombre. Revise los detalles a continuación:</p>
                
                <div class='paciente-card'>
                    <h3 style='margin-top: 0; color: #1565C0;'>👤 Información del Paciente</h3>
                    <div style='background: white; padding: 15px; border-radius: 8px;'>
                        <strong style='color: #1976D2;'>Nombre:</strong> {$datosCita['nombre_paciente']}
                    </div>
                </div>
                
                <div class='info-grid'>
                    <div class='info-item'>
                        <strong>📅 FECHA</strong>
                        <span>{$fechaFormateada}</span>
                    </div>
                    <div class='info-item'>
                        <strong>⏰ HORA</strong>
                        <span>{$horaFormateada}</span>
                    </div>
                    <div class='info-item'>
                        <strong>🏥 ESPECIALIDAD</strong>
                        <span>{$datosCita['especialidad']}</span>
                    </div>
                    <div class='info-item'>
                        <strong>{$tipoIcono} TIPO</strong>
                        <span>{$tipoTexto}</span>
                    </div>
                </div>
                
                <div class='motivo-section'>
                    <h4 style='margin-top: 0; color: #e65100;'>💬 Motivo de la Consulta</h4>
                    <p style='margin: 0; color: #555;'>{$datosCita['motivo_consulta']}</p>
                </div>
                
                <div style='background: #f1f8e9; border-radius: 8px; padding: 20px; margin: 20px 0;'>
                    <h4 style='margin-top: 0; color: #388e3c;'>📋 Recordatorios</h4>
                    <ul style='color: #555; margin: 10px 0;'>
                        <li>Confirme su asistencia con anticipación</li>
                        <li>Revise el historial del paciente si está disponible</li>
                        <li>Prepare los materiales necesarios para la consulta</li>
                    </ul>
                </div>
            </div>
            
            <div class='footer'>
                <strong>Clínica SJ - Sistema de Gestión Médica</strong><br>
                Mensaje automático para profesionales médicos
            </div>
        </div>
    </body>
    </html>";
}


private function generarPlantillaCitaPaciente($nombrePaciente, $datosCita) {
    $fechaFormateada = date('l, d \d\e F \d\e Y', strtotime($datosCita['fecha_cita']));
    $horaFormateada = date('H:i', strtotime($datosCita['hora_cita']));
    
    // Sección especial para citas virtuales
    $seccionVirtual = '';
    if ($datosCita['tipo_cita'] === 'virtual' && isset($datosCita['enlace_virtual'])) {
        $seccionVirtual = "
        <div class='virtual-section'>
            <div class='virtual-header'>
                <h3>🎥 Su Cita es Virtual</h3>
                <p>Podrá atenderse desde la comodidad de su hogar</p>
            </div>
            
            <div class='zoom-info'>
                <div class='zoom-item'>
                    <strong>🔗 Enlace de la videollamada:</strong>
                    <a href='{$datosCita['enlace_virtual']}' class='zoom-link'>Unirse a la videollamada</a>
                </div>
                <div class='zoom-item'>
                    <strong>🔢 ID de reunión:</strong>
                    <code>{$datosCita['zoom_meeting_id']}</code>
                </div>
                <div class='zoom-item'>
                    <strong>🔐 Contraseña:</strong>
                    <code>{$datosCita['zoom_password']}</code>
                </div>
            </div>
            
            <div class='virtual-instructions'>
                <h4>📋 Instrucciones importantes:</h4>
                <ul>
                    <li>Ingrese a la videollamada <strong>5 minutos antes</strong> de la hora programada</li>
                    <li>Asegúrese de tener una conexión estable a internet</li>
                    <li>Use un dispositivo con cámara y micrófono</li>
                    <li>Busque un lugar tranquilo y con buena iluminación</li>
                </ul>
            </div>
        </div>";
    } else {
        $seccionVirtual = "
        <div class='presencial-section'>
            <div class='presencial-header'>
                <h3>🏥 Su Cita es Presencial</h3>
                <p>Lo esperamos en nuestras instalaciones</p>
            </div>
            
            <div class='direccion-info'>
                <div class='direccion-item'>
                    <strong>📍 Dirección:</strong>
                    <p>{$datosCita['direccion_sucursal']}</p>
                </div>
            </div>
            
            <div class='presencial-instructions'>
                <h4>📋 Instrucciones importantes:</h4>
                <ul>
                    <li>Llegue <strong>15 minutos antes</strong> para el proceso de registro</li>
                    <li>Traiga un documento de identificación válido</li>
                    <li>Lleve sus medicamentos actuales y exámenes previos</li>
                    <li>Use mascarilla durante su visita</li>
                </ul>
            </div>
        </div>";
    }

    return "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Confirmación de Cita Médica</title>
        <style>
            * { box-sizing: border-box; }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background-color: #f4f7fa;
            }
            .container { 
                max-width: 650px; 
                margin: 20px auto; 
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                overflow: hidden;
            }
            .header { 
                background: linear-gradient(135deg, #4CAF50, #45a049); 
                color: white; 
                padding: 30px 25px; 
                text-align: center; 
            }
            .header h1 { 
                margin: 0; 
                font-size: 28px; 
                font-weight: 300;
            }
            .header .subtitle {
                font-size: 16px;
                opacity: 0.9;
                margin-top: 8px;
            }
            .content { 
                padding: 30px 25px; 
            }
            .greeting {
                font-size: 18px;
                margin-bottom: 25px;
                color: #2c3e50;
            }
            .cita-card {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 25px;
                margin: 25px 0;
                border-left: 5px solid #4CAF50;
            }
            .cita-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                margin: 20px 0;
            }
            .cita-item {
                background: white;
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .cita-item strong {
                display: block;
                color: #4CAF50;
                font-size: 14px;
                margin-bottom: 5px;
            }
            .cita-item span {
                font-size: 16px;
                color: #2c3e50;
                font-weight: 500;
            }
            .virtual-section, .presencial-section {
                background: #e8f5e9;
                border-radius: 10px;
                padding: 25px;
                margin: 25px 0;
                border: 2px solid #4CAF50;
            }
            .virtual-header, .presencial-header {
                text-align: center;
                margin-bottom: 20px;
            }
            .virtual-header h3, .presencial-header h3 {
                color: #2e7d32;
                margin: 0;
                font-size: 20px;
            }
            .zoom-info {
                background: white;
                border-radius: 8px;
                padding: 20px;
                margin: 15px 0;
            }
            .zoom-item {
                margin: 12px 0;
                padding: 10px 0;
                border-bottom: 1px solid #eee;
            }
            .zoom-item:last-child {
                border-bottom: none;
            }
            .zoom-link {
                display: inline-block;
                background: #4CAF50;
                color: white !important;
                padding: 12px 25px;
                text-decoration: none;
                border-radius: 6px;
                font-weight: 500;
                margin-top: 8px;
            }
            .zoom-link:hover {
                background: #45a049;
            }
            code {
                background: #f1f3f4;
                padding: 6px 10px;
                border-radius: 4px;
                font-family: monospace;
                font-size: 14px;
                color: #d32f2f;
            }
            .virtual-instructions, .presencial-instructions {
                background: white;
                border-radius: 8px;
                padding: 20px;
                margin-top: 15px;
            }
            .virtual-instructions h4, .presencial-instructions h4 {
                color: #2e7d32;
                margin-top: 0;
            }
            .virtual-instructions ul, .presencial-instructions ul {
                padding-left: 20px;
                margin: 10px 0;
            }
            .virtual-instructions li, .presencial-instructions li {
                margin: 8px 0;
                color: #555;
            }
            .footer {
                background: #f8f9fa;
                padding: 25px;
                text-align: center;
                color: #666;
                font-size: 14px;
                border-top: 1px solid #eee;
            }
            .footer strong {
                color: #4CAF50;
            }
            @media (max-width: 600px) {
                .container { margin: 10px; }
                .cita-grid { grid-template-columns: 1fr; }
                .content { padding: 20px; }
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✅ Cita Confirmada</h1>
                <div class='subtitle'>Su cita médica ha sido agendada exitosamente</div>
            </div>
            
            <div class='content'>
                <div class='greeting'>
                    Estimado/a <strong>{$nombrePaciente}</strong>,
                </div>
                
                <p>Nos complace confirmarle que su cita médica ha sido programada. A continuación encontrará todos los detalles:</p>
                
                <div class='cita-card'>
                    <h3 style='margin-top: 0; color: #2e7d32; text-align: center;'>📋 Detalles de su Cita</h3>
                    
                    <div class='cita-grid'>
                        <div class='cita-item'>
                            <strong>📅 FECHA</strong>
                            <span>{$fechaFormateada}</span>
                        </div>
                        <div class='cita-item'>
                            <strong>⏰ HORA</strong>
                            <span>{$horaFormateada}</span>
                        </div>
                        <div class='cita-item'>
                            <strong>👨‍⚕️ MÉDICO</strong>
                            <span>{$datosCita['nombre_medico']}</span>
                        </div>
                        <div class='cita-item'>
                            <strong>🏥 ESPECIALIDAD</strong>
                            <span>{$datosCita['especialidad']}</span>
                        </div>
                    </div>
                    
                    <div style='background: white; padding: 15px; border-radius: 8px; margin-top: 15px;'>
                        <strong style='color: #4CAF50;'>💬 MOTIVO DE LA CONSULTA</strong><br>
                        <span style='color: #555;'>{$datosCita['motivo_consulta']}</span>
                    </div>
                </div>
                
                {$seccionVirtual}
                
                <div style='background: #fff3e0; border-radius: 10px; padding: 20px; margin: 25px 0; border-left: 4px solid #ff9800;'>
                    <h4 style='color: #e65100; margin-top: 0;'>📝 Recomendaciones Importantes</h4>
                    <ul style='color: #555; margin: 10px 0; padding-left: 20px;'>
                        <li>Prepare una lista de sus síntomas y preguntas</li>
                        <li>Traiga todos sus medicamentos actuales</li>
                        <li>Si tiene exámenes previos, no olvide llevarlos</li>
                        <li>En caso de emergencia, acuda inmediatamente a urgencias</li>
                    </ul>
                </div>
            </div>
            
            <div class='footer'>
                <strong>Clínica SJ - Sistema de Gestión Médica</strong><br>
                Este es un mensaje automático, por favor no responder.<br>
                Para cancelar o reprogramar su cita, contacte con nosotros.<br>
                📧 Soporte: " . EmailConfig::SUPPORT_EMAIL . "
            </div>
        </div>
    </body>
    </html>";
}

// Métodos de texto plano
private function generarTextoPlanoMedico($nombreMedico, $datosCita) {
    return "NUEVA CITA AGENDADA\n\n" .
           "Dr. {$nombreMedico},\n\n" .
           "Detalles de la cita:\n" .
           "Fecha: {$datosCita['fecha_cita']}\n" .
           "Hora: {$datosCita['hora_cita']}\n" .
           "Paciente: {$datosCita['nombre_paciente']}\n" .
           "Especialidad: {$datosCita['especialidad']}\n" .
           "Tipo: " . ucfirst($datosCita['tipo_cita']) . "\n" .
           "Motivo: {$datosCita['motivo_consulta']}\n\n" .
           "Sistema de Clínica SJ";
}

private function generarTextoLlanoPaciente($nombrePaciente, $datosCita) {
    return "CONFIRMACIÓN DE CITA MÉDICA\n\n" .
           "Estimado/a {$nombrePaciente},\n\n" .
           "Su cita ha sido confirmada:\n" .
           "Fecha: {$datosCita['fecha_cita']}\n" .
           "Hora: {$datosCita['hora_cita']}\n" .
           "Médico: {$datosCita['nombre_medico']}\n" .
           "Especialidad: {$datosCita['especialidad']}\n" .
           "Sucursal: {$datosCita['sucursal']}\n\n" .
           "Sistema de Clínica SJ";
}

private function generarTextoPlanoNotificacionCita($nombre, $datosCita, $tipo) {
    $base = "NOTIFICACIÓN DE CITA MÉDICA - CLÍNICA SJ\n\n";
    
    if ($tipo === 'paciente') {
        $base .= "Estimado/a {$nombre},\n\n";
        $base .= "Su cita médica ha sido confirmada:\n\n";
    } else {
        $base .= "Dr. {$nombre},\n\n";
        $base .= "Nueva cita asignada:\n\n";
    }
    
    $base .= "📅 Fecha: {$datosCita['fecha_cita']}\n";
    $base .= "⏰ Hora: {$datosCita['hora_cita']}\n";
    
    if ($tipo === 'paciente') {
        $base .= "👨‍⚕️ Médico: {$datosCita['nombre_medico']}\n";
    } else {
        $base .= "👤 Paciente: {$datosCita['nombre_paciente']}\n";
    }
    
    $base .= "🏥 Especialidad: {$datosCita['especialidad']}\n";
    $base .= "📍 Tipo: " . ucfirst($datosCita['tipo_cita']) . "\n";
    $base .= "💬 Motivo: {$datosCita['motivo_consulta']}\n\n";
    
    if ($datosCita['tipo_cita'] === 'virtual' && isset($datosCita['enlace_virtual'])) {
        $base .= "🎥 Enlace: {$datosCita['enlace_virtual']}\n";
        $base .= "🔢 ID: {$datosCita['zoom_meeting_id']}\n";
        $base .= "🔐 Contraseña: {$datosCita['zoom_password']}\n\n";
    }
    
    $base .= "Sistema de Clínica SJ\n";
    $base .= "Soporte: " . EmailConfig::SUPPORT_EMAIL;
    
    return $base;
}

/**
 * Enviar notificación de cambio de estado de cita
 */
public function enviarNotificacionCambioEstado($email, $nombre, $datosCita, $tipo) {
    try {
        // Determinar el asunto según el nuevo estado
        $estadosAsuntos = [
            'confirmada' => '✅ Cita Confirmada',
            'cancelada' => '❌ Cita Cancelada',
            'completada' => '✅ Cita Completada',
            'en_curso' => '🏥 Cita en Curso',
            'no_asistio' => '⚠️ Registro de No Asistencia'
        ];
        
        $nuevoEstado = $datosCita['estado_nuevo'];
        $asuntoBase = $estadosAsuntos[$nuevoEstado] ?? 'Cambio de Estado de Cita';
        $asunto = $asuntoBase . ' - Cita #' . str_pad($datosCita['id_cita'], 6, '0', STR_PAD_LEFT);
        
        // Generar el mensaje HTML
        $mensaje = $this->generarPlantillaCambioEstado($nombre, $datosCita, $tipo);
        
        // Generar versión de texto plano
        $textoPlano = $this->generarTextoPlanoNotificacionCambioEstado($nombre, $datosCita, $tipo);
        
        return $this->enviarEmail($email, $asunto, $mensaje, $textoPlano);
        
    } catch (\Exception $e) {
        error_log("Error enviando notificación de cambio de estado: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al enviar notificación: ' . $e->getMessage()];
    }
}

/**
 * Generar plantilla HTML para cambio de estado
 */
private function generarPlantillaCambioEstado($nombre, $datosCita, $tipo) {
    $fechaFormateada = date('l, d \d\e F \d\e Y', strtotime($datosCita['fecha_cita']));
    $horaFormateada = date('H:i', strtotime($datosCita['hora_cita']));
    
    // Determinar colores y emojis según el estado
    $estadoConfig = [
        'confirmada' => ['color' => '#28a745', 'emoji' => '✅', 'titulo' => 'Cita Confirmada'],
        'cancelada' => ['color' => '#dc3545', 'emoji' => '❌', 'titulo' => 'Cita Cancelada'],
        'completada' => ['color' => '#007bff', 'emoji' => '✅', 'titulo' => 'Cita Completada'],
        'en_curso' => ['color' => '#fd7e14', 'emoji' => '🏥', 'titulo' => 'Cita en Curso'],
        'no_asistio' => ['color' => '#6c757d', 'emoji' => '⚠️', 'titulo' => 'No Asistencia Registrada']
    ];
    
    $config = $estadoConfig[$datosCita['estado_nuevo']] ?? ['color' => '#6c757d', 'emoji' => '📋', 'titulo' => 'Estado Actualizado'];
    
    $saludo = ($tipo === 'paciente') ? "Estimado/a {$nombre}" : "Dr. {$nombre}";
    $mensaje_principal = ($tipo === 'paciente') ? 
        "Le informamos que el estado de su cita médica ha cambiado:" : 
        "Le informamos sobre un cambio en el estado de la cita:";
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Cambio de Estado - Cita Médica</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f4f4f4;'>
        <div style='max-width: 600px; margin: 0 auto; background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 20px rgba(0,0,0,0.1);'>
            
            <!-- Header -->
            <div style='background: linear-gradient(135deg, {$config['color']}, " . $this->darkenColor($config['color'], 20) . "); padding: 30px; text-align: center; color: white;'>
                <h1 style='margin: 0; font-size: 28px; font-weight: bold;'>
                    {$config['emoji']} {$config['titulo']}
                </h1>
                <p style='margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;'>Clínica SJ - Sistema de Citas</p>
            </div>
            
            <!-- Contenido Principal -->
            <div style='padding: 30px;'>
                <p style='font-size: 18px; margin-bottom: 25px; color: #2c3e50;'>
                    {$saludo},
                </p>
                
                <p style='font-size: 16px; margin-bottom: 25px; line-height: 1.8;'>
                    {$mensaje_principal}
                </p>
                
                <!-- Información del Estado -->
                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid {$config['color']}; margin: 20px 0;'>
                    <h3 style='margin: 0 0 10px 0; color: {$config['color']}; font-size: 18px;'>
                        {$config['emoji']} Estado: {$config['titulo']}
                    </h3>
                    <p style='margin: 5px 0; color: #6c757d;'>
                        <strong>Estado anterior:</strong> " . ucfirst($datosCita['estado_anterior']) . "
                    </p>";
    
    if (!empty($datosCita['motivo_cambio'])) {
        $html .= "
                    <p style='margin: 5px 0; color: #6c757d;'>
                        <strong>Motivo:</strong> {$datosCita['motivo_cambio']}
                    </p>";
    }
    
    $html .= "
                </div>
                
                <!-- Detalles de la Cita -->
                <div style='background-color: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin: 20px 0;'>
                    <h3 style='margin: 0 0 15px 0; color: #495057; border-bottom: 2px solid #e9ecef; padding-bottom: 10px;'>
                        📋 Detalles de la Cita
                    </h3>
                    
                    <div style='display: grid; gap: 10px;'>
                        <p style='margin: 5px 0; padding: 8px 0; border-bottom: 1px solid #f8f9fa;'>
                            <strong style='color: #495057;'>🗓️ Fecha:</strong> {$fechaFormateada}
                        </p>
                        <p style='margin: 5px 0; padding: 8px 0; border-bottom: 1px solid #f8f9fa;'>
                            <strong style='color: #495057;'>⏰ Hora:</strong> {$horaFormateada}
                        </p>";
    
    if ($tipo === 'paciente') {
        $html .= "
                        <p style='margin: 5px 0; padding: 8px 0; border-bottom: 1px solid #f8f9fa;'>
                            <strong style='color: #495057;'>👨‍⚕️ Médico:</strong> {$datosCita['nombre_medico']}
                        </p>";
    } else {
        $html .= "
                        <p style='margin: 5px 0; padding: 8px 0; border-bottom: 1px solid #f8f9fa;'>
                            <strong style='color: #495057;'>👤 Paciente:</strong> {$datosCita['nombre_paciente']}
                        </p>";
    }
    
    $html .= "
                        <p style='margin: 5px 0; padding: 8px 0; border-bottom: 1px solid #f8f9fa;'>
                            <strong style='color: #495057;'>🏥 Especialidad:</strong> {$datosCita['especialidad']}
                        </p>
                        <p style='margin: 5px 0; padding: 8px 0;'>
                            <strong style='color: #495057;'>📍 Tipo:</strong> " . ucfirst($datosCita['tipo_cita']) . "
                        </p>
                    </div>
                </div>";
    
    // Información adicional según el tipo de cita
    if ($datosCita['tipo_cita'] === 'virtual' && !empty($datosCita['enlace_virtual'])) {
        $html .= "
                <!-- Información Virtual -->
                <div style='background: linear-gradient(135deg, #17a2b8, #138496); padding: 20px; border-radius: 8px; margin: 20px 0; color: white;'>
                    <h3 style='margin: 0 0 15px 0; font-size: 18px;'>🎥 Información de Conexión Virtual</h3>
                    <p style='margin: 8px 0;'><strong>Enlace:</strong> {$datosCita['enlace_virtual']}</p>
                    <p style='margin: 8px 0;'><strong>ID de reunión:</strong> {$datosCita['zoom_meeting_id']}</p>
                    <p style='margin: 8px 0;'><strong>Contraseña:</strong> {$datosCita['zoom_password']}</p>
                </div>";
    }
    
    $html .= "
            </div>
            
            <!-- Footer -->
            <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #dee2e6;'>
                <p style='margin: 0; color: #6c757d; font-size: 14px;'>
                    Este es un mensaje automático del Sistema de Citas de Clínica SJ
                </p>
                <p style='margin: 5px 0 0 0; color: #6c757d; font-size: 14px;'>
                    📧 Soporte: " . EmailConfig::SUPPORT_EMAIL . "
                </p>
            </div>
        </div>
    </body>
    </html>";
    
    return $html;
}

/**
 * Generar texto plano para cambio de estado
 */
private function generarTextoPlanoNotificacionCambioEstado($nombre, $datosCita, $tipo) {
    $saludo = ($tipo === 'paciente') ? "Estimado/a {$nombre}" : "Dr. {$nombre}";
    $mensaje_principal = ($tipo === 'paciente') ? 
        "Le informamos que el estado de su cita médica ha cambiado" : 
        "Le informamos sobre un cambio en el estado de la cita";
    
    $texto = "CAMBIO DE ESTADO - CITA MÉDICA\n";
    $texto .= "Clínica SJ - Sistema de Citas\n\n";
    $texto .= "{$saludo},\n\n";
    $texto .= "{$mensaje_principal}:\n\n";
    
    $texto .= "NUEVO ESTADO: " . strtoupper($datosCita['estado_nuevo']) . "\n";
    $texto .= "Estado anterior: " . ucfirst($datosCita['estado_anterior']) . "\n";
    
    if (!empty($datosCita['motivo_cambio'])) {
        $texto .= "Motivo: {$datosCita['motivo_cambio']}\n";
    }
    
    $texto .= "\nDETALLES DE LA CITA:\n";
    $texto .= "Fecha: {$datosCita['fecha_cita']}\n";
    $texto .= "Hora: {$datosCita['hora_cita']}\n";
    
    if ($tipo === 'paciente') {
        $texto .= "Médico: {$datosCita['nombre_medico']}\n";
    } else {
        $texto .= "Paciente: {$datosCita['nombre_paciente']}\n";
    }
    
    $texto .= "Especialidad: {$datosCita['especialidad']}\n";
    $texto .= "Tipo: " . ucfirst($datosCita['tipo_cita']) . "\n";
    
    if ($datosCita['tipo_cita'] === 'virtual' && !empty($datosCita['enlace_virtual'])) {
        $texto .= "\nINFORMACIÓN VIRTUAL:\n";
        $texto .= "Enlace: {$datosCita['enlace_virtual']}\n";
        $texto .= "ID: {$datosCita['zoom_meeting_id']}\n";
        $texto .= "Contraseña: {$datosCita['zoom_password']}\n";
    }
    
    $texto .= "\nSistema de Clínica SJ\n";
    $texto .= "Soporte: " . EmailConfig::SUPPORT_EMAIL;
    
    return $texto;
}

/**
 * Función auxiliar para oscurecer colores
 */
private function darkenColor($hex, $percent) {
    // Colores predefinidos más oscuros para evitar cálculos
    $darkColors = [
        '#28a745' => '#1e7e34',  // Verde confirmada
        '#dc3545' => '#bd2130',  // Rojo cancelada  
        '#007bff' => '#0056b3',  // Azul completada
        '#fd7e14' => '#dc6502',  // Naranja en_curso
        '#6c757d' => '#495057'   // Gris no_asistio
    ];
    
    return $darkColors[$hex] ?? $hex; // Si no encuentra el color, devuelve el original
}

}
?>