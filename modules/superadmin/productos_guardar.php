<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('productos.php');
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nombre = limpiar_texto($_POST['nombre']);
$id_categoria = intval($_POST['id_categoria']);
$id_tipo_pago = isset($_POST['id_tipo_pago']) && is_array($_POST['id_tipo_pago']) ? implode(',', array_map('intval', $_POST['id_tipo_pago'])) : null;
$descripcion = limpiar_texto($_POST['descripcion']);
$tipo_producto = limpiar_texto($_POST['tipo_producto']); // normal o tutorial
$es_gratuito = limpiar_texto($_POST['es_gratuito']);
$precio = ($es_gratuito === 'si') ? 0 : floatval($_POST['precio']);
$version = limpiar_texto($_POST['version'] ?? '');
$requisitos = limpiar_texto($_POST['requisitos'] ?? '');
$drive_link = limpiar_texto($_POST['drive_link'] ?? '');
$link_descarga_directa = limpiar_texto($_POST['link_descarga_directa'] ?? '');
$estado = limpiar_texto($_POST['estado']);

$errores = [];

// Validaciones
if (empty($nombre))
    $errores[] = "El nombre es requerido";
if ($id_categoria <= 0)
    $errores[] = "Selecciona una categoría";
if (empty($descripcion))
    $errores[] = "La descripción es requerida";
if (!in_array($tipo_producto, ['normal', 'tutorial']))
    $errores[] = "Tipo de producto no válido";
if (!in_array($es_gratuito, ['si', 'no']))
    $errores[] = "Tipo de producto no válido";

if ($es_gratuito === 'no') {
    // Producto de pago
    if ($precio <= 0)
        $errores[] = "El precio debe ser mayor a 0";

    // Solo validar drive_link si NO es tutorial
    if ($tipo_producto === 'normal' && empty($drive_link)) {
        $errores[] = "El link de Google Drive es requerido";
    }
}
else {
    // Producto gratuito
    if ($tipo_producto === 'normal' && empty($link_descarga_directa)) {
        $errores[] = "El link de descarga directa es requerido";
    }
}

if (!in_array($estado, ['activo', 'inactivo']))
    $errores[] = "Estado no válido";

// Validar que la categoría exista
if ($id_categoria > 0) {
    $query_cat = "SELECT id FROM categorias WHERE id = ? AND estado = 'activo'";
    $stmt = mysqli_prepare($conexion, $query_cat);
    mysqli_stmt_bind_param($stmt, "i", $id_categoria);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) === 0) {
        $errores[] = "La categoría seleccionada no existe";
    }
    mysqli_stmt_close($stmt);
}

if (!empty($errores)) {
    $_SESSION['error'] = implode('<br>', $errores);
    redirect($id > 0 ? "productos_editar.php?id=$id" : 'productos_crear.php');
}

// Procesar imagen
$imagen_path = '';
$imagen_anterior = '';

if ($id > 0) {
    // Obtener imagen anterior si existe
    $query = "SELECT imagen FROM productos WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $imagen_anterior = $row['imagen'];
    }
    mysqli_stmt_close($stmt);
}

// Si hay nueva imagen
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $fileValidator = new FileValidator();
    $resultado = $fileValidator->guardar_archivo(
        $_FILES['imagen'],
        IMAGENES_PATH,
        'producto_' . time()
    );

    if ($resultado['success']) {
        $imagen_path = 'uploads/imagenes/' . $resultado['filename'];

        // Eliminar imagen anterior si existe
        if (!empty($imagen_anterior) && file_exists('../../' . $imagen_anterior)) {
            unlink('../../' . $imagen_anterior);
        }
    }
    else {
        $_SESSION['error'] = implode('<br>', $resultado['errors']);
        redirect($id > 0 ? "productos_editar.php?id=$id" : 'productos_crear.php');
    }
}
else {
    // Mantener imagen anterior si está editando
    if ($id > 0) {
        $imagen_path = $imagen_anterior;
    }
}

if ($id > 0) {
    // Actualizar producto
    if (!empty($imagen_path)) {
        $query = "UPDATE productos SET nombre = ?, id_categoria = ?, descripcion = ?, es_gratuito = ?, precio = ?, 
                  id_tipo_pago = ?, version = ?, requisitos = ?, drive_link = ?, link_descarga_directa = ?, imagen = ?, estado = ?, 
                  tipo_producto = ?, fecha_actualizacion = NOW() 
                  WHERE id = ?";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "sissdssssssssi", $nombre, $id_categoria, $descripcion, $es_gratuito, $precio, $id_tipo_pago,
            $version, $requisitos, $drive_link, $link_descarga_directa, $imagen_path, $estado, $tipo_producto, $id);
    }
    else {
        $query = "UPDATE productos SET nombre = ?, id_categoria = ?, descripcion = ?, es_gratuito = ?, precio = ?, 
                  id_tipo_pago = ?, version = ?, requisitos = ?, drive_link = ?, link_descarga_directa = ?, estado = ?,
                  tipo_producto = ?, fecha_actualizacion = NOW() 
                  WHERE id = ?";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "sissdsssssssi", $nombre, $id_categoria, $descripcion, $es_gratuito, $precio, $id_tipo_pago,
            $version, $requisitos, $drive_link, $link_descarga_directa, $estado, $tipo_producto, $id);
    }

    if (mysqli_stmt_execute($stmt)) {
        Session::registrar_actividad(Session::get_user_id(), 'actualizar', 'productos', $id, "Producto actualizado: $nombre");
        $_SESSION['success'] = "Producto actualizado correctamente";
    }
    else {
        $_SESSION['error'] = "Error al actualizar el producto";
    }

}
else {
    // Crear nuevo producto
    $query = "INSERT INTO productos (nombre, id_categoria, descripcion, tipo_producto, es_gratuito, id_tipo_pago, precio, version, requisitos, drive_link, link_descarga_directa, imagen, estado) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "sissidsssssss", $nombre, $id_categoria, $descripcion, $tipo_producto, $es_gratuito, $id_tipo_pago, $precio,
        $version, $requisitos, $drive_link, $link_descarga_directa, $imagen_path, $estado);

    if (mysqli_stmt_execute($stmt)) {
        $nuevo_id = mysqli_insert_id($conexion);
        Session::registrar_actividad(Session::get_user_id(), 'crear', 'productos', $nuevo_id, "Producto creado: $nombre");

        if ($tipo_producto === 'tutorial') {
            $_SESSION['success'] = "Tutorial creado correctamente. Ahora agrega los videos.";
            mysqli_stmt_close($stmt);
            redirect("tutorial_videos.php?id=$nuevo_id");
        }
        else {
            $_SESSION['success'] = "Producto creado correctamente.";
        }
    }
    else {
        $_SESSION['error'] = "Error al crear el producto";
    }

    mysqli_stmt_close($stmt);
}

redirect('productos.php');
?>