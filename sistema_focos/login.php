<?php
session_start();
include 'conexion.php';

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Buscar usuario en la base de datos
    $sql = "SELECT id, username, nombre, rol FROM usuarios 
            WHERE username = ? AND activo = 1";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        
        // POR AHORA - Verificación simple (luego usaremos password_hash)
        if (($username == "admin" && $password == "admin123") || 
            ($username == "vendedor1" && $password == "admin123")) {
            
            // Iniciar sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['username'] = $usuario['username'];
            $_SESSION['rol'] = $usuario['rol'];
            $_SESSION['nombre'] = $usuario['nombre'];
            
            // Redirigir según el rol
            if ($usuario['rol'] == 'administrador') {
                header("Location: reportes.php");
            } else {
                header("Location: ventas.php");
            }
            exit();
        } else {
            echo "Contraseña incorrecta. Grandicimo pendejo";
        }
    } else {
        echo "Usuario no encontrado. Grandicimo pendejo";
    }
}
?>