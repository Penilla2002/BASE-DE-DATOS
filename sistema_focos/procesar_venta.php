<?php
session_start();
include 'conexion.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit();
}

if ($_POST) {
    $producto_id = $_POST['producto_id'];
    $cantidad = $_POST['cantidad'];
    $usuario_id = $_SESSION['usuario_id'];

    // Obtener información del producto (CÓDIGO CORREGIDO)
    $sql_producto = "SELECT p.precio_venta, i.cantidad as stock 
                     FROM productos p 
                     INNER JOIN inventario i ON p.id = i.producto_id 
                     WHERE p.id = ?";
    
    $stmt = $conexion->prepare($sql_producto);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();  // ← ESTA ES LA LÍNEA CLAVE CORREGIDA
    $producto = $result->fetch_assoc();
    
    // Cerrar el statement antes de continuar
    $stmt->close();
    
    if (!$producto) {
        die("Producto no encontrado");
    }

    // Verificar stock suficiente
    if ($cantidad > $producto['stock']) {
        die("No hay suficiente stock. Stock disponible: " . $producto['stock']);
    }

    // Calcular total
    $precio_unitario = $producto['precio_venta'];
    $total_venta = $precio_unitario * $cantidad;

    // INICIAR TRANSACCIÓN
    $conexion->begin_transaction();

    try {
        // 1. Insertar la venta
        $sql_venta = "INSERT INTO ventas (fecha, hora, total) VALUES (CURDATE(), CURTIME(), ?)";
        $stmt = $conexion->prepare($sql_venta);
        $stmt->bind_param("d", $total_venta);
        $stmt->execute();
        $venta_id = $conexion->insert_id;
        $stmt->close();

        // 2. Insertar detalle de venta
        $sql_detalle = "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql_detalle);
        $stmt->bind_param("iiidd", $venta_id, $producto_id, $cantidad, $precio_unitario, $total_venta);
        $stmt->execute();
        $stmt->close();

        // 3. Actualizar inventario (restar lo vendido)
        $sql_inventario = "UPDATE inventario SET cantidad = cantidad - ? WHERE producto_id = ?";
        $stmt = $conexion->prepare($sql_inventario);
        $stmt->bind_param("ii", $cantidad, $producto_id);
        $stmt->execute();
        $stmt->close();

        // CONFIRMAR TRANSACCIÓN
        $conexion->commit();

        // Redirigir con mensaje de éxito
        header("Location: ventas.php?exito=Venta registrada correctamente. Total: $" . $total_venta);
        exit();

    } catch (Exception $e) {
        // Si algo falla, revertir todo
        $conexion->rollback();
        die("Error al procesar la venta: " . $e->getMessage());
    }
    
    // Cerrar conexión
    $conexion->close();
}
?>