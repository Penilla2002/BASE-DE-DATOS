<?php
// conexion.php

$servidor = "localhost";
$usuario = "root";
$password = "P3N1ll4#1";
$base_datos = "sistema_focos";
$puerto = 3306;

// Crear conexión
$conexion = new mysqli($servidor, $usuario, $password, $base_datos, $puerto);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
// Quitamos el mensaje de conexión exitosa
?>