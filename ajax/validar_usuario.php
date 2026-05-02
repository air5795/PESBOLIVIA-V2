<?php
require_once '../config/config.php';
require_once '../includes/funciones.php';

header('Content-Type: application/json');

if (isset($_GET['usuario'])) {
    $usuario = limpiar_texto($_GET['usuario']);
    
    if (empty($usuario)) {
        echo json_encode(['status' => 'empty']);
        exit;
    }

    if (usuario_existe($usuario)) {
        echo json_encode(['status' => 'exists', 'message' => 'El usuario ya está en uso']);
    } else {
        echo json_encode(['status' => 'available', 'message' => 'Usuario disponible']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Parámetro no proporcionado']);
}
