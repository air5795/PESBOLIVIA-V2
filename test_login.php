<?php
require_once 'config/config.php';
require_once 'includes/funciones.php';

echo "<h2>TEST DE LOGIN - DEBUG</h2>";

// 1. Verificar conexión
echo "<h3>1. Conexión a BD:</h3>";
if ($conexion) {
    echo "✅ Conexión exitosa<br>";
} else {
    echo "❌ Error de conexión: " . mysqli_connect_error() . "<br>";
    die();
}

// 2. Verificar si existe el usuario admin
echo "<h3>2. Usuario 'admin' en la BD:</h3>";
$query = "SELECT id, usuario, email, password, rol, estado FROM usuarios WHERE usuario = 'admin'";
$result = mysqli_query($conexion, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    echo "✅ Usuario encontrado<br>";
    echo "<strong>ID:</strong> " . $user['id'] . "<br>";
    echo "<strong>Usuario:</strong> " . $user['usuario'] . "<br>";
    echo "<strong>Email:</strong> " . $user['email'] . "<br>";
    echo "<strong>Rol:</strong> " . $user['rol'] . "<br>";
    echo "<strong>Estado:</strong> " . $user['estado'] . "<br>";
    echo "<strong>Hash almacenado:</strong> " . substr($user['password'], 0, 50) . "...<br>";
} else {
    echo "❌ Usuario NO encontrado<br>";
    die();
}

// 3. Generar nuevo hash para comparar
echo "<h3>3. Generar hash de 'admin123':</h3>";
$password = 'admin123';
$nuevo_hash = password_hash($password, PASSWORD_DEFAULT);
echo "<strong>Nuevo hash generado:</strong> " . substr($nuevo_hash, 0, 50) . "...<br>";

// 4. Verificar password
echo "<h3>4. Verificación de contraseña:</h3>";
if (password_verify($password, $user['password'])) {
    echo "✅ La contraseña 'admin123' ES VÁLIDA con el hash actual<br>";
} else {
    echo "❌ La contraseña 'admin123' NO coincide con el hash almacenado<br>";
    echo "<br><strong>SOLUCIÓN:</strong> Ejecuta este SQL:<br>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    echo "UPDATE usuarios SET password = '$nuevo_hash' WHERE usuario = 'admin';";
    echo "</pre>";
}

// 5. Verificar función verificar_password
echo "<h3>5. Función verificar_password():</h3>";
if (function_exists('verificar_password')) {
    echo "✅ Función existe<br>";
    if (verificar_password($password, $user['password'])) {
        echo "✅ verificar_password() retorna TRUE<br>";
    } else {
        echo "❌ verificar_password() retorna FALSE<br>";
    }
} else {
    echo "❌ Función NO existe<br>";
}

// 6. Verificar estado del usuario
echo "<h3>6. Estado del usuario:</h3>";
if ($user['estado'] == 'activo') {
    echo "✅ Usuario está activo<br>";
} else {
    echo "❌ Usuario está: " . $user['estado'] . "<br>";
}
?>