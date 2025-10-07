<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['carrito'])) {
    header("Location: ventas.php");
    exit();
}

// Calcular total
$total_venta = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total_venta += $item['precio'] * $item['cantidad'];
}

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

    // 2. Insertar detalles de venta y actualizar inventario
    foreach ($_SESSION['carrito'] as $item) {
        $producto_id = $item['producto_id'];
        $cantidad = $item['cantidad'];
        $precio_unitario = $item['precio'];
        $subtotal = $precio_unitario * $cantidad;

        // Insertar detalle
        $sql_detalle = "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql_detalle);
        $stmt->bind_param("iiidd", $venta_id, $producto_id, $cantidad, $precio_unitario, $subtotal);
        $stmt->execute();
        $stmt->close();

        // Actualizar inventario
        $sql_inventario = "UPDATE inventario SET cantidad = cantidad - ? WHERE producto_id = ?";
        $stmt = $conexion->prepare($sql_inventario);
        $stmt->bind_param("ii", $cantidad, $producto_id);
        $stmt->execute();
        $stmt->close();
    }

    // CONFIRMAR TRANSACCIÓN
    $conexion->commit();

    // Limpiar carrito y redirigir
    $_SESSION['carrito'] = [];
    $_SESSION['mensaje_exito'] = "Venta múltiple procesada exitosamente. Total: $" . number_format($total_venta, 2);
    header("Location: ventas.php");
    exit();

} catch (Exception $e) {
    $conexion->rollback();
    die("Error al procesar la venta múltiple: " . $e->getMessage());
}
?>