<?php
$conexion = mysqli_connect("localhost", "root", "", "pesbolivia");
mysqli_query($conexion, "ALTER TABLE productos MODIFY id_tipo_pago VARCHAR(255) NULL");
echo "Done";
?>
