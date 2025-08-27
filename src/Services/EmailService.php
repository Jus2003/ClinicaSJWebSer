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
 * Cambiar estado de una cita y enviar notificaciones
 */
public function cambiarEstadoCita($idCita, $nuevoEstado, $motivoCambio = null) {
    try {
        $this->db->beginTransaction();
        
        // 1. Obtener información completa de la cita actual
        $sqlCita = "
            SELECT 
                c.id_cita, c.estado_cita as estado_actual, c.fecha_cita, c.hora_cita, 
                c.tipo_cita, c.motivo_consulta, c.enlace_virtual, c.zoom_meeting_id, c.zoom_password,
                p.id_usuario as id_paciente, p.nombre as nombre_paciente, p.apellido as apellido_paciente, p.email as email_paciente,
                m.id_usuario as id_medico, m.nombre as nombre_medico, m.apellido as apellido_medico, m.email as email_medico,
                e.nombre_especialidad as especialidad,
                s.nombre_sucursal as sucursal, s.direccion as direccion_sucursal
            FROM citas c
            INNER JOIN usuarios p ON c.id_paciente = p.id_usuario
            INNER JOIN usuarios m ON c.id_medico = m.id_usuario
            INNER JOIN especialidades e ON c.id_especialidad = e.id_especialidad
            INNER JOIN sucursales s ON c.id_sucursal = s.id_sucursal
            WHERE c.id_cita = ?
        ";
        
        $stmt = $this->db->prepare($sqlCita);
        $stmt->execute([$idCita]);
        $cita = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cita) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'No se encontró la cita especificada'
            ];
        }
        
        // Verificar si ya tiene ese estado
        if ($cita['estado_actual'] === $nuevoEstado) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => "La cita ya tiene el estado: {$nuevoEstado}"
            ];
        }
        
        // 2. Actualizar estado de la cita
        $camposActualizar = ['estado_cita = ?'];
        $valoresActualizar = [$nuevoEstado];
        
        // Agregar campos específicos según el nuevo estado
        if ($nuevoEstado === 'confirmada') {
            $camposActualizar[] = 'fecha_confirmacion = NOW()';
        } elseif ($nuevoEstado === 'cancelada') {
            $camposActualizar[] = 'fecha_cancelacion = NOW()';
            if ($motivoCambio) {
                $camposActualizar[] = 'motivo_cancelacion = ?';
                $valoresActualizar[] = $motivoCambio;
            }
        }
        
        $sqlUpdate = "UPDATE citas SET " . implode(', ', $camposActualizar) . " WHERE id_cita = ?";
        $valoresActualizar[] = $idCita;
        
        $stmt = $this->db->prepare($sqlUpdate);
        $resultado = $stmt->execute($valoresActualizar);
        
        if (!$resultado) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Error al actualizar el estado de la cita'
            ];
        }
        
        $this->db->commit();
        
        // 3. Preparar datos para emails
        $datosCita = [
            'id_cita' => $cita['id_cita'],
            'estado_anterior' => $cita['estado_actual'],
            'estado_nuevo' => $nuevoEstado,
            'fecha_cita' => $cita['fecha_cita'],
            'hora_cita' => $cita['hora_cita'],
            'nombre_paciente' => $cita['nombre_paciente'] . ' ' . $cita['apellido_paciente'],
            'nombre_medico' => $cita['nombre_medico'] . ' ' . $cita['apellido_medico'],
            'especialidad' => $cita['especialidad'],
            'sucursal' => $cita['sucursal'],
            'direccion_sucursal' => $cita['direccion_sucursal'],
            'tipo_cita' => $cita['tipo_cita'],
            'motivo_consulta' => $cita['motivo_consulta'],
            'motivo_cambio' => $motivoCambio,
            'enlace_virtual' => $cita['enlace_virtual'],
            'zoom_meeting_id' => $cita['zoom_meeting_id'],
            'zoom_password' => $cita['zoom_password']
        ];
        
        // 4. Enviar notificaciones por email
        $emailsEnviados = ['paciente' => false, 'medico' => false];
        
        try {
            $emailService = new \App\Services\EmailService();
            
            // Email al paciente
            $resultadoPaciente = $emailService->enviarNotificacionCambioEstado(
                $cita['email_paciente'],
                $cita['nombre_paciente'] . ' ' . $cita['apellido_paciente'],
                $datosCita,
                'paciente'
            );
            $emailsEnviados['paciente'] = $resultadoPaciente['success'] ?? false;
            
            // Email al médico
            $resultadoMedico = $emailService->enviarNotificacionCambioEstado(
                $cita['email_medico'],
                $cita['nombre_medico'] . ' ' . $cita['apellido_medico'],
                $datosCita,
                'medico'
            );
            $emailsEnviados['medico'] = $resultadoMedico['success'] ?? false;
            
        } catch (\Exception $e) {
            error_log("Error enviando emails de cambio de estado: " . $e->getMessage());
        }
        
        // 5. Preparar mensaje de estado
        $mensajesEstado = [
            'agendada' => 'La cita ha sido agendada',
            'confirmada' => 'La cita ha sido confirmada',
            'en_curso' => 'La cita está en curso',
            'completada' => 'La cita ha sido completada',
            'cancelada' => 'La cita ha sido cancelada',
            'no_asistio' => 'Se ha registrado que el paciente no asistió'
        ];
        
        return [
            'success' => true,
            'message' => 'Estado de cita actualizado exitosamente',
            'data' => [
                'cambio_exitoso' => '✅ COMPLETADO',
                'id_cita' => $idCita,
                'numero_cita' => str_pad($idCita, 6, '0', STR_PAD_LEFT),
                'estado_anterior' => $cita['estado_actual'],
                'estado_nuevo' => $nuevoEstado,
                'mensaje_estado' => $mensajesEstado[$nuevoEstado] ?? 'Estado actualizado',
                'motivo_cambio' => $motivoCambio,
                'paciente' => [
                    'nombre' => $cita['nombre_paciente'] . ' ' . $cita['apellido_paciente'],
                    'email' => $cita['email_paciente']
                ],
                'medico' => [
                    'nombre' => $cita['nombre_medico'] . ' ' . $cita['apellido_medico'],
                    'email' => $cita['email_medico']
                ],
                'cita' => [
                    'fecha' => $cita['fecha_cita'],
                    'hora' => $cita['hora_cita'],
                    'tipo' => $cita['tipo_cita'],
                    'especialidad' => $cita['especialidad']
                ],
                'notificaciones' => [
                    'email_paciente' => $emailsEnviados['paciente'] ? '✅ Enviado' : '❌ Error al enviar',
                    'email_medico' => $emailsEnviados['medico'] ? '✅ Enviado' : '❌ Error al enviar',
                    'mensaje' => ($emailsEnviados['paciente'] && $emailsEnviados['medico']) 
                        ? 'Notificaciones enviadas exitosamente a ambos'
                        : (($emailsEnviados['paciente'] || $emailsEnviados['medico']) 
                            ? 'Algunas notificaciones fueron enviadas' 
                            : 'Las notificaciones no pudieron enviarse')
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
        
    } catch (\Exception $e) {
        if ($this->db->inTransaction()) {
            $this->db->rollback();
        }
        return [
            'success' => false,
            'message' => 'Error al cambiar estado de cita: ' . $e->getMessage()
        ];
    }
}
}
?>