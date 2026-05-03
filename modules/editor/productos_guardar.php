<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

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
$id_editor = Session::get_user_id();

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
    // Producto de pago - Validar que tenga métodos de pago activos
    $query_metodos = "SELECT COUNT(*) as total FROM tipos_pago WHERE id_editor = ? AND estado = 'activo'";
    $stmt_m = mysqli_prepare($conexion, $query_metodos);
    mysqli_stmt_bind_param($stmt_m, "i", $id_editor);
    mysqli_stmt_execute($stmt_m);
    $res_m = mysqli_stmt_get_result($stmt_m);
    $data_m = mysqli_fetch_assoc($res_m);
    mysqli_stmt_close($stmt_m);

    if ($data_m['total'] == 0) {
        $errores[] = "Debes configurar al menos un método de pago activo para crear productos de pago.";
    }

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
    $query = "SELECT imagen FROM productos WHERE id = ? AND id IN (SELECT id_producto FROM producto_editores WHERE id_editor = ?)";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id, $id_editor);
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
        if (!empty($imagen_anterior) && file_exists(BASE_PATH . '/' . $imagen_anterior)) {
            unlink(BASE_PATH . '/' . $imagen_anterior);
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
    // Editar (solo si el producto pertenece al editor)
    $query_check = "SELECT id FROM productos WHERE id = ? AND id IN (SELECT id_producto FROM producto_editores WHERE id_editor = ?)";
    $stmt = mysqli_prepare($conexion, $query_check);
    mysqli_stmt_bind_param($stmt, "ii", $id, $id_editor);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        $_SESSION['error'] = "No tienes permiso para editar este producto";
        redirect('productos.php');
    }
    mysqli_stmt_close($stmt);

    // Actualizar producto
    if (!empty($imagen_path)) {
        $query = "UPDATE productos SET nombre = ?, id_categoria = ?, descripcion = ?, es_gratuito = ?, precio = ?, 
                  id_tipo_pago = ?, version = ?, requisitos = ?, drive_link = ?, link_descarga_directa = ?, imagen = ?, estado = ? 
                  WHERE id = ?";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "sissdsssssssi", $nombre, $id_categoria, $descripcion, $es_gratuito, $precio, $id_tipo_pago,
            $version, $requisitos, $drive_link, $link_descarga_directa, $imagen_path, $estado, $id);
    }
    else {
        $query = "UPDATE productos SET nombre = ?, id_categoria = ?, descripcion = ?, es_gratuito = ?, precio = ?, 
                  id_tipo_pago = ?, version = ?, requisitos = ?, drive_link = ?, link_descarga_directa = ?, estado = ? 
                  WHERE id = ?";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "sissdssssssi", $nombre, $id_categoria, $descripcion, $es_gratuito, $precio, $id_tipo_pago,
            $version, $requisitos, $drive_link, $link_descarga_directa, $estado, $id);
    }

    if (mysqli_stmt_execute($stmt)) {
        Session::registrar_actividad($id_editor, 'actualizar', 'productos', $id, "Producto actualizado: $nombre");
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

        // Asignar automáticamente al editor con 100% (para mantener la propiedad, sea de pago o gratuito)
        $porcentaje = 100.00;
        $query_asignar = "INSERT INTO producto_editores (id_producto, id_editor, porcentaje) VALUES (?, ?, ?)";
        $stmt_asignar = mysqli_prepare($conexion, $query_asignar);
        mysqli_stmt_bind_param($stmt_asignar, "iid", $nuevo_id, $id_editor, $porcentaje);
        mysqli_stmt_execute($stmt_asignar);
        mysqli_stmt_close($stmt_asignar);

        if ($es_gratuito === 'no') {
            Session::registrar_actividad($id_editor, 'crear', 'productos', $nuevo_id, "Producto creado: $nombre (100% comisión)");

            // Redirigir a agregar videos si es tutorial
            if ($tipo_producto === 'tutorial') {
                $_SESSION['success'] = "Tutorial creado correctamente. Ahora agrega los videos.";
                mysqli_stmt_close($stmt);
                redirect("tutorial_videos.php?id=$nuevo_id");
            }
            else {
                $_SESSION['success'] = "Producto creado correctamente. Tienes asignado el 100% de comisión.";
            }
        }
        else {
            Session::registrar_actividad($id_editor, 'crear', 'productos', $nuevo_id, "Producto gratuito creado: $nombre");

            if ($tipo_producto === 'tutorial') {
                $_SESSION['success'] = "Tutorial gratuito creado. Ahora agrega los videos.";
                mysqli_stmt_close($stmt);
                redirect("tutorial_videos.php?id=$nuevo_id");
            }
            else {
                $_SESSION['success'] = "Producto gratuito creado correctamente.";
            }
        }
    }
    else {
        $_SESSION['error'] = "Error al crear el producto";
    }

    mysqli_stmt_close($stmt);
}

redirect('productos.php');
?>