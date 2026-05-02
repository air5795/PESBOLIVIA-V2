<?php
/**
 * CLASE PARA ENVÍO DE EMAILS
 * Utiliza PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    
    private $mail;
    
    public function __construct() {
        require_once BASE_PATH . '/vendor/autoload.php';
        
        $this->mail = new PHPMailer(true);
        $this->configurar_smtp();
    }
    
    /**
     * Configurar servidor SMTP
     */
    private function configurar_smtp() {
        try {
            $this->mail->isSMTP();
            $this->mail->Host = SMTP_HOST;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = SMTP_USER;
            $this->mail->Password = SMTP_PASSWORD;
            $this->mail->SMTPSecure = 'tls';
            $this->mail->Port = SMTP_PORT;
            $this->mail->CharSet = 'UTF-8';
            
            // Remitente por defecto
            $this->mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        } catch (Exception $e) {
            error_log("Error configurando SMTP: " . $e->getMessage());
        }
    }
    
    /**
     * Enviar email genérico
     */
    public function enviar($destinatario, $asunto, $cuerpo_html, $cuerpo_texto = '') {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($destinatario);
            $this->mail->isHTML(true);
            $this->mail->Subject = $asunto;
            $this->mail->Body = $cuerpo_html;
            
            if (!empty($cuerpo_texto)) {
                $this->mail->AltBody = $cuerpo_texto;
            }
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Error enviando email: " . $this->mail->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Email de bienvenida para nuevos usuarios
     */
    public function email_bienvenida($usuario_data) {
        $asunto = "Bienvenido a " . NOMBRE_SISTEMA;
        
        $cuerpo = $this->plantilla_email("
            <h2>¡Bienvenido {$usuario_data['nombre']}!</h2>
            <p>Tu cuenta ha sido creada exitosamente en <strong>" . NOMBRE_SISTEMA . "</strong>.</p>
            <p><strong>Detalles de tu cuenta:</strong></p>
            <ul>
                <li>Usuario: <strong>{$usuario_data['usuario']}</strong></li>
                <li>Email: <strong>{$usuario_data['email']}</strong></li>
                <li>Rol: <strong>" . ucfirst($usuario_data['rol']) . "</strong></li>
            </ul>
            <p>Puedes iniciar sesión haciendo clic en el siguiente botón:</p>
            <p style='text-align: center;'>
                <a href='" . BASE_URL . "/login.php' style='background-color: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                    Iniciar Sesión
                </a>
            </p>
            <p>Si tienes alguna duda, no dudes en contactarnos.</p>
        ");
        
        return $this->enviar($usuario_data['email'], $asunto, $cuerpo);
    }
    
    /**
     * Email de recuperación de contraseña
     */
    public function email_recuperacion_password($email, $token, $nombre) {
        $asunto = "Recuperación de contraseña - " . NOMBRE_SISTEMA;
        
        $link_recuperacion = BASE_URL . "/recuperar_password.php?token=" . $token;
        
        $cuerpo = $this->plantilla_email("
            <h2>Recuperación de Contraseña</h2>
            <p>Hola <strong>$nombre</strong>,</p>
            <p>Hemos recibido una solicitud para restablecer tu contraseña.</p>
            <p>Haz clic en el siguiente botón para crear una nueva contraseña:</p>
            <p style='text-align: center;'>
                <a href='$link_recuperacion' style='background-color: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                    Restablecer Contraseña
                </a>
            </p>
            <p>Este enlace es válido por <strong>1 hora</strong>.</p>
            <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>
            <p style='font-size: 12px; color: #666; margin-top: 20px;'>
                Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:<br>
                <a href='$link_recuperacion'>$link_recuperacion</a>
            </p>
        ");
        
        return $this->enviar($email, $asunto, $cuerpo);
    }
    
    /**
     * Email de confirmación de compra (para comprador)
     */
    public function email_confirmacion_compra($compra_data) {
        $asunto = "Compra registrada - Código: " . $compra_data['codigo_compra'];
        
        $cuerpo = $this->plantilla_email("
            <h2>¡Compra Registrada!</h2>
            <p>Hola <strong>{$compra_data['comprador_nombre']}</strong>,</p>
            <p>Tu compra ha sido registrada exitosamente y está <strong>pendiente de aprobación</strong>.</p>
            
            <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <p><strong>Detalles de la compra:</strong></p>
                <ul>
                    <li>Código: <strong>{$compra_data['codigo_compra']}</strong></li>
                    <li>Producto: <strong>{$compra_data['producto_nombre']}</strong></li>
                    <li>Monto: <strong>" . MONEDA . " {$compra_data['monto_total']}</strong></li>
                    <li>Fecha: <strong>{$compra_data['fecha_compra']}</strong></li>
                    <li>Método de pago: <strong>{$compra_data['tipo_pago']}</strong></li>
                </ul>
            </div>
            
            <p>Nuestro equipo revisará tu comprobante de pago y te notificaremos una vez sea aprobada.</p>
            <p>Puedes ver el estado de tu compra en tu panel de usuario.</p>
        ");
        
        return $this->enviar($compra_data['comprador_email'], $asunto, $cuerpo);
    }
    
    /**
     * Email de nueva compra (para administradores/editores)
     */
    public function email_nueva_compra_admin($compra_data, $email_destino) {
        $asunto = "Nueva compra pendiente - " . $compra_data['codigo_compra'];
        
        $cuerpo = $this->plantilla_email("
            <h2>Nueva Solicitud de Compra</h2>
            <p>Se ha registrado una nueva compra pendiente de aprobación.</p>
            
            <div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                <p><strong>Detalles de la compra:</strong></p>
                <ul>
                    <li>Código: <strong>{$compra_data['codigo_compra']}</strong></li>
                    <li>Comprador: <strong>{$compra_data['comprador_nombre']}</strong></li>
                    <li>Email: <strong>{$compra_data['comprador_email']}</strong></li>
                    <li>Producto: <strong>{$compra_data['producto_nombre']}</strong></li>
                    <li>Monto: <strong>" . MONEDA . " {$compra_data['monto_total']}</strong></li>
                    <li>Fecha: <strong>{$compra_data['fecha_compra']}</strong></li>
                </ul>
            </div>
            
            <p style='text-align: center;'>
                <a href='" . BASE_URL . "/modules/{$compra_data['rol_destino']}/compras.php?id_compra={$compra_data['id_compra']}' style='background-color: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                    Ver Compra
                </a>
            </p>
        ");
        
        return $this->enviar($email_destino, $asunto, $cuerpo);
    }
    
    /**
     * Email de compra aprobada (para comprador)
     */
    public function email_compra_aprobada($compra_data) {
        $asunto = "¡Compra Aprobada! - " . $compra_data['codigo_compra'];
        
        $cuerpo = $this->plantilla_email("
            <h2 style='color: #28a745;'>¡Tu Compra ha sido Aprobada! ✓</h2>
            <p>Hola <strong>{$compra_data['comprador_nombre']}</strong>,</p>
            <p>¡Excelentes noticias! Tu compra ha sido <strong>aprobada</strong>.</p>
            
            <div style='background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745;'>
                <p><strong>Detalles:</strong></p>
                <ul>
                    <li>Código: <strong>{$compra_data['codigo_compra']}</strong></li>
                    <li>Producto: <strong>{$compra_data['producto_nombre']}</strong></li>
                    <li>Monto: <strong>" . MONEDA . " {$compra_data['monto_total']}</strong></li>
                </ul>
            </div>
            
            <p><strong>Accede a tu producto:</strong></p>
            <p style='text-align: center;'>
                <a href='{$compra_data['drive_link']}' style='background-color: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                    Descargar Producto
                </a>
            </p>
            
            <p style='font-size: 12px; color: #666; margin-top: 20px;'>
                También puedes acceder desde tu panel de usuario en la sección \"Mis Compras\".
            </p>
            
            <p>¡Gracias por tu compra!</p>
        ");
        
        return $this->enviar($compra_data['comprador_email'], $asunto, $cuerpo);
    }
    
    /**
     * Email de compra rechazada (para comprador)
     */
    public function email_compra_rechazada($compra_data) {
        $asunto = "Compra no aprobada - " . $compra_data['codigo_compra'];
        
        $observaciones = !empty($compra_data['observaciones']) 
            ? "<p><strong>Motivo:</strong> {$compra_data['observaciones']}</p>" 
            : "";
        
        $cuerpo = $this->plantilla_email("
            <h2 style='color: #dc3545;'>Compra No Aprobada</h2>
            <p>Hola <strong>{$compra_data['comprador_nombre']}</strong>,</p>
            <p>Lamentablemente tu compra <strong>{$compra_data['codigo_compra']}</strong> no pudo ser aprobada.</p>
            
            $observaciones
            
            <p>Si crees que esto es un error o tienes dudas, por favor contáctanos a <strong>" . EMAIL_SISTEMA . "</strong></p>
            <p>Puedes intentar realizar una nueva compra asegurándote de enviar el comprobante de pago correcto.</p>
        ");
        
        return $this->enviar($compra_data['comprador_email'], $asunto, $cuerpo);
    }
    
    /**
     * Plantilla HTML base para emails
     */
    private function plantilla_email($contenido) {
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background-color: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;'>
                <h1 style='margin: 0; font-size: 24px;'>" . NOMBRE_SISTEMA . "</h1>
            </div>
            
            <div style='background-color: #ffffff; padding: 30px; border: 1px solid #ddd; border-top: none;'>
                $contenido
            </div>
            
            <div style='background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 5px 5px;'>
                <p style='margin: 5px 0;'>&copy; " . date('Y') . " " . NOMBRE_SISTEMA . ". Todos los derechos reservados.</p>
                <p style='margin: 5px 0;'>
                    <a href='" . BASE_URL . "' style='color: #007bff; text-decoration: none;'>Visitar sitio web</a> | 
                    <a href='mailto:" . EMAIL_SISTEMA . "' style='color: #007bff; text-decoration: none;'>Contacto</a>
                </p>
            </div>
        </body>
        </html>
        ";
    }
}
?>