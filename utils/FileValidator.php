<?php
/**
 * CLASE PARA VALIDACIÓN Y MANEJO DE ARCHIVOS
 */

class FileValidator {
    
    /**
     * Validar archivo subido
     */
    public static function validar_archivo($archivo, $tipo = 'comprobante') {
        $errores = [];
        
        // Verificar que se haya subido un archivo
        if (!isset($archivo) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
            $errores[] = "No se ha seleccionado ningún archivo";
            return ['success' => false, 'errors' => $errores];
        }
        
        // Verificar errores de subida
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $errores[] = "Error al subir el archivo. Código: " . $archivo['error'];
            return ['success' => false, 'errors' => $errores];
        }
        
        // Verificar tamaño
        if ($archivo['size'] > MAX_UPLOAD_SIZE) {
            $size_mb = MAX_UPLOAD_SIZE / 1048576;
            $errores[] = "El archivo no debe superar " . $size_mb . " MB";
            return ['success' => false, 'errors' => $errores];
        }
        
        // Verificar extensión
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        
        if ($tipo === 'comprobante') {
            $extensiones_permitidas = EXTENSIONES_COMPROBANTE;
        } elseif ($tipo === 'imagen') {
            $extensiones_permitidas = EXTENSIONES_IMAGEN;
        } else {
            $extensiones_permitidas = array_merge(EXTENSIONES_COMPROBANTE, EXTENSIONES_IMAGEN);
        }
        
        if (!in_array($extension, $extensiones_permitidas)) {
            $errores[] = "Extensión no permitida. Permitidas: " . implode(', ', $extensiones_permitidas);
            return ['success' => false, 'errors' => $errores];
        }
        
        // Verificar tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        
        $mimes_permitidos = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf'
        ];
        
        if (!in_array($mime_type, $mimes_permitidos)) {
            $errores[] = "Tipo de archivo no válido";
            return ['success' => false, 'errors' => $errores];
        }
        
        return ['success' => true, 'extension' => $extension, 'mime_type' => $mime_type];
    }
    
    /**
     * Guardar archivo subido
     */
    public static function guardar_archivo($archivo, $carpeta_destino, $nombre_personalizado = null) {
        // Validar archivo primero
        $validacion = self::validar_archivo($archivo);
        
        if (!$validacion['success']) {
            return $validacion;
        }
        
        $extension = $validacion['extension'];
        
        // Generar nombre único
        if ($nombre_personalizado) {
            $nombre_archivo = $nombre_personalizado . '.' . $extension;
        } else {
            $nombre_archivo = uniqid('file_', true) . '.' . $extension;
        }
        
        // Ruta completa
        $ruta_destino = $carpeta_destino . '/' . $nombre_archivo;
        
        // Crear carpeta de destino si no existe
        if (!is_dir($carpeta_destino)) {
            mkdir($carpeta_destino, 0755, true);
        }
        
        // Mover archivo
        if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
            return [
                'success' => true,
                'filename' => $nombre_archivo,
                'path' => $ruta_destino
            ];
        } else {
            return [
                'success' => false,
                'errors' => ['Error al guardar el archivo en el servidor']
            ];
        }
    }
    
    /**
     * Eliminar archivo
     */
    public static function eliminar_archivo($ruta) {
        if (file_exists($ruta)) {
            return unlink($ruta);
        }
        return false;
    }
    
    /**
     * Generar nombre único para archivo
     */
    public static function generar_nombre_unico($prefijo = 'file', $extension = '') {
        $nombre = $prefijo . '_' . date('YmdHis') . '_' . uniqid();
        
        if (!empty($extension)) {
            $nombre .= '.' . $extension;
        }
        
        return $nombre;
    }
    
    /**
     * Obtener tamaño legible de archivo
     */
    public static function formatear_tamano($bytes) {
        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($unidades) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $unidades[$i];
    }
}
?>