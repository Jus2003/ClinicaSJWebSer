<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use App\Config\EmailConfig;

class EmailService {
    
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
}
?>