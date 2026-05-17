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
$imagen_principal_index = intval($_POST['imagen_principal_index'] ?? 0);

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
    if ($precio <= 0)
        $errores[] = "El precio debe ser mayor a 0";
    if ($tipo_producto === 'normal' && empty($drive_link)) {
        $errores[] = "El link de Google Drive es requerido";
    }
} else {
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

// Procesar imagen principal (compatibilidad) - se usará para productos.imagen
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

// Procesar múltiples imágenes nuevas
$nuevas_imagenes = [];
if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
    $total_files = count($_FILES['imagenes']['name']);
    
    $fileValidator = new FileValidator();
    for ($i = 0; $i < $total_files; $i++) {
        if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
            $file = [
                'name' => $_FILES['imagenes']['name'][$i],
                'type' => $_FILES['imagenes']['type'][$i],
                'tmp_name' => $_FILES['imagenes']['tmp_name'][$i],
                'error' => $_FILES['imagenes']['error'][$i],
                'size' => $_FILES['imagenes']['size'][$i]
            ];
            
            $resultado = $fileValidator->guardar_archivo(
                $file,
                IMAGENES_PATH,
                'producto_' . time() . '_' . $i
            );
            
            if ($resultado['success']) {
                $nuevas_imagenes[] = [
                    'path' => 'uploads/imagenes/' . $resultado['filename'],
                    'es_principal' => ($i === $imagen_principal_index) ? 1 : 0
                ];
            }
        }
    }
}

// Determinar imagen principal para productos.imagen
if (!empty($nuevas_imagenes)) {
    // Buscar la imagen marcada como principal
    $found_principal = false;
    foreach ($nuevas_imagenes as $img) {
        if ($img['es_principal'] == 1) {
            $imagen_path = $img['path'];
            $found_principal = true;
            break;
        }
    }
    if (!$found_principal) {
        $imagen_path = $nuevas_imagenes[0]['path'];
        $nuevas_imagenes[0]['es_principal'] = 1;
    }
} else {
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
    } else {
        $query = "UPDATE productos SET nombre = ?, id_categoria = ?, descripcion = ?, es_gratuito = ?, precio = ?, 
                  id_tipo_pago = ?, version = ?, requisitos = ?, drive_link = ?, link_descarga_directa = ?, estado = ?,
                  tipo_producto = ?, fecha_actualizacion = NOW() 
                  WHERE id = ?";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "sissdsssssssi", $nombre, $id_categoria, $descripcion, $es_gratuito, $precio, $id_tipo_pago,
            $version, $requisitos, $drive_link, $link_descarga_directa, $estado, $tipo_producto, $id);
    }

    if (mysqli_stmt_execute($stmt)) {
        // Guardar nuevas imágenes en producto_imagenes
        if (!empty($nuevas_imagenes)) {
            // Si hay nuevas imágenes marcadas como principal, quitar principal de existentes
            foreach ($nuevas_imagenes as $img) {
                if ($img['es_principal'] == 1) {
                    $q_reset = "UPDATE producto_imagenes SET es_principal = 0 WHERE id_producto = ?";
                    $s = mysqli_prepare($conexion, $q_reset);
                    mysqli_stmt_bind_param($s, "i", $id);
                    mysqli_stmt_execute($s);
                    mysqli_stmt_close($s);
                    break;
                }
            }
            
            $orden = 0;
            $q_max = "SELECT MAX(orden) as max_orden FROM producto_imagenes WHERE id_producto = ?";
            $s = mysqli_prepare($conexion, $q_max);
            mysqli_stmt_bind_param($s, "i", $id);
            mysqli_stmt_execute($s);
            $r = mysqli_stmt_get_result($s);
            $max = mysqli_fetch_assoc($r);
            $orden = ($max['max_orden'] ?? 0) + 1;
            mysqli_stmt_close($s);
            
            foreach ($nuevas_imagenes as $img) {
                $q_insert = "INSERT INTO producto_imagenes (id_producto, imagen, es_principal, orden) VALUES (?, ?, ?, ?)";
                $s = mysqli_prepare($conexion, $q_insert);
                mysqli_stmt_bind_param($s, "isii", $id, $img['path'], $img['es_principal'], $orden);
                mysqli_stmt_execute($s);
                mysqli_stmt_close($s);
                $orden++;
            }
        }
        
        Session::registrar_actividad(Session::get_user_id(), 'actualizar', 'productos', $id, "Producto actualizado: $nombre");
        $_SESSION['success'] = "Producto actualizado correctamente";
    } else {
        $_SESSION['error'] = "Error al actualizar el producto";
    }

} else {
    // Crear nuevo producto
    $query = "INSERT INTO productos (nombre, id_categoria, descripcion, tipo_producto, es_gratuito, id_tipo_pago, precio, version, requisitos, drive_link, link_descarga_directa, imagen, estado) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "sissssdssssss", $nombre, $id_categoria, $descripcion, $tipo_producto, $es_gratuito, $id_tipo_pago, $precio,
        $version, $requisitos, $drive_link, $link_descarga_directa, $imagen_path, $estado);

    if (mysqli_stmt_execute($stmt)) {
        $nuevo_id = mysqli_insert_id($conexion);
        
        // Guardar imágenes en producto_imagenes
        if (!empty($nuevas_imagenes)) {
            $orden = 0;
            foreach ($nuevas_imagenes as $img) {
                $q_insert = "INSERT INTO producto_imagenes (id_producto, imagen, es_principal, orden) VALUES (?, ?, ?, ?)";
                $s = mysqli_prepare($conexion, $q_insert);
                mysqli_stmt_bind_param($s, "isii", $nuevo_id, $img['path'], $img['es_principal'], $orden);
                mysqli_stmt_execute($s);
                mysqli_stmt_close($s);
                $orden++;
            }
        }
        
        Session::registrar_actividad(Session::get_user_id(), 'crear', 'productos', $nuevo_id, "Producto creado: $nombre");

        if ($tipo_producto === 'tutorial') {
            $_SESSION['success'] = "Tutorial creado correctamente. Ahora agrega los videos.";
            mysqli_stmt_close($stmt);
            redirect("tutorial_videos.php?id=$nuevo_id");
        } else {
            $_SESSION['success'] = "Producto creado correctamente.";
        }
    } else {
        $_SESSION['error'] = "Error al crear el producto";
    }

    mysqli_stmt_close($stmt);
}

redirect('productos.php');
?>