<?php
$conexion = mysqli_connect("localhost", "root", "", "pesbolivia");
$res = mysqli_query($conexion, "SHOW COLUMNS FROM usuarios");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . "\n";
}
?>
