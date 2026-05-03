<?php
/**
 * CLASE PARA GESTIONAR PERMISOS EN GOOGLE DRIVE
 */

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\Permission;

class GoogleDriveManager {
    
    private $service;
    
    public function __construct() {
        $credentials_path = BASE_PATH . '/config/google-credentials.json';
        
        if (!file_exists($credentials_path)) {
            error_log("Google Drive API: Archivo de credenciales no encontrado en $credentials_path");
            return;
        }

        try {
            $client = new Client();
            $client->setAuthConfig($credentials_path);
            $client->addScope(Drive::DRIVE);
            $this->service = new Drive($client);
        } catch (Exception $e) {
            error_log("Google Drive API Error (Init): " . $e->getMessage());
        }
    }
    
    /**
     * Dar acceso de lectura a un usuario mediante su email
     * @param string $fileId ID de la carpeta o archivo (se extrae de la URL de Drive)
     * @param string $userEmail Email del comprador
     * @return bool
     */
    public function darAcceso($fileId, $userEmail) {
        if (!$this->service) return false;
        
        try {
            // Extraer el ID de la carpeta si se pasa la URL completa
            $id = $this->extraerId($fileId);
            
            $newPermission = new Permission();
            $newPermission->setType('user');
            $newPermission->setRole('reader');
            $newPermission->setEmailAddress($userEmail);
            
            // Enviamos la solicitud de permiso
            $this->service->permissions->create($id, $newPermission, [
                'sendNotificationEmail' => true // Google le enviará un email avisando del acceso
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Google Drive API Error (darAcceso): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Extrae el ID de una carpeta de Google Drive a partir de su URL
     */
    private function extraerId($input) {
        // Si ya es un ID (no tiene barras), lo devolvemos
        if (strpos($input, '/') === false) {
            return $input;
        }
        
        // Si es una URL, buscamos el patrón /folders/ID o /d/ID
        if (preg_match('/folders\/([a-zA-Z0-9_-]+)/', $input, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/d\/([a-zA-Z0-9_-]+)/', $input, $matches)) {
            return $matches[1];
        }
        
        return $input;
    }
}
