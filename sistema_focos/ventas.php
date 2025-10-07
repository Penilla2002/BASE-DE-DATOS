<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit();
}

include 'conexion.php';

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Agregar producto al carrito
if (isset($_POST['agregar_carrito'])) {
    $producto_id = $_POST['producto_id'];
    $cantidad = $_POST['cantidad'];
    
    // Verificar stock
    $sql_stock = "SELECT cantidad FROM inventario WHERE producto_id = ?";
    $stmt = $conexion->prepare($sql_stock);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stock = $result->fetch_assoc();
    
    if ($stock && $cantidad <= $stock['cantidad']) {
        // Buscar producto en carrito
        $encontrado = false;
        foreach ($_SESSION['carrito'] as &$item) {
            if ($item['producto_id'] == $producto_id) {
                $item['cantidad'] += $cantidad;
                $encontrado = true;
                break;
            }
        }
        
        if (!$encontrado) {
            // Obtener info del producto
            $sql_producto = "SELECT nombre, precio_venta FROM productos WHERE id = ?";
            $stmt = $conexion->prepare($sql_producto);
            $stmt->bind_param("i", $producto_id);
            $stmt->execute();
            $producto = $stmt->get_result()->fetch_assoc();
            
            $_SESSION['carrito'][] = [
                'producto_id' => $producto_id,
                'nombre' => $producto['nombre'],
                'precio' => $producto['precio_venta'],
                'cantidad' => $cantidad
            ];
        }
        
        $_SESSION['mensaje'] = "Producto agregado al carrito";
    } else {
        $_SESSION['error'] = "Stock insuficiente";
    }
    
    header("Location: ventas.php");
    exit();
}

// Vaciar carrito
if (isset($_GET['vaciar_carrito'])) {
    $_SESSION['carrito'] = [];
    header("Location: ventas.php");
    exit();
}

// Mostrar mensajes
if (isset($_SESSION['mensaje'])) {
    echo '<div style="background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 5px;">✅ ' . $_SESSION['mensaje'] . '</div>';
    unset($_SESSION['mensaje']);
}

if (isset($_SESSION['error'])) {
    echo '<div style="background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 5px;">❌ ' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ventas - Sistema de Focos</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .producto { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f9f9f9; }
        .carrito { background: #e9f7ef; padding: 15px; margin: 20px 0; border-radius: 5px; }
        button { background: #28a745; color: white; padding: 10px 15px; border: none; cursor: pointer; border-radius: 3px; margin: 5px; }
        button:hover { background: #218838; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        input[type="number"] { padding: 8px; width: 80px; margin-right: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>🛒 Venta Múltiple - Sistema de Focos</h1>
    <p>Bienvenido, <?php echo $_SESSION['nombre']; ?> (<?php echo $_SESSION['rol']; ?>)</p>
    
    <!-- CARRITO DE COMPRAS -->
    <div class="carrito">
        <h3>🛒 Carrito de Compras</h3>
        <?php if (!empty($_SESSION['carrito'])): ?>
            <table>
                <tr>
                    <th>Producto</th>
                    <th>Precio Unit.</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                </tr>
                <?php 
                $total_venta = 0;
                foreach ($_SESSION['carrito'] as $item): 
                    $subtotal = $item['precio'] * $item['cantidad'];
                    $total_venta += $subtotal;
                ?>
                <tr>
                    <td><?php echo $item['nombre']; ?></td>
                    <td>$<?php echo $item['precio']; ?></td>
                    <td><?php echo $item['cantidad']; ?></td>
                    <td>$<?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                    <td><strong>$<?php echo number_format($total_venta, 2); ?></strong></td>
                </tr>
            </table>
            
            <form action="procesar_venta_multiple.php" method="POST" style="display: inline;">
                <button type="submit" style="background: #007bff;">✅ Finalizar Venta</button>
            </form>
            <a href="ventas.php?vaciar_carrito=true" class="btn-danger" style="background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;">🗑️ Vaciar Carrito</a>
            
        <?php else: ?>
            <p>El carrito está vacío. Agrega productos desde abajo.</p>
        <?php endif; ?>
    </div>

    <!-- PRODUCTOS DISPONIBLES -->
    <h3>📦 Productos Disponibles</h3>
    <?php
    $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio_venta, i.cantidad, i.stock_minimo 
            FROM productos p 
            INNER JOIN inventario i ON p.id = i.producto_id 
            WHERE p.activo = 1
            ORDER BY p.nombre";
    $result = $conexion->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo '<div class="producto">';
            echo '<h4>' . $row['nombre'] . '</h4>';
            echo '<p>' . $row['descripcion'] . '</p>';
            echo '<p><strong>Precio: $' . $row['precio_venta'] . '</strong> | ';
            echo 'Stock: ' . $row['cantidad'] . '</p>';
            
            echo '<form action="ventas.php" method="POST">';
            echo '<input type="hidden" name="producto_id" value="' . $row['id'] . '">';
            echo '<input type="number" name="cantidad" min="1" max="' . $row['cantidad'] . '" value="1" required>';
            echo '<button type="submit" name="agregar_carrito">➕ Agregar al Carrito</button>';
            echo '</form>';
            echo '</div>';
        }
    } else {
        echo "<p>No hay productos disponibles.</p>";
    }
    ?>
    
    <br>
    <a href="logout.php">Cerrar Sesión</a> | 
    <a href="<?php echo $_SESSION['rol'] == 'administrador' ? 'reportes.php' : 'ventas.php'; ?>">
        Volver a <?php echo $_SESSION['rol'] == 'administrador' ? 'Reportes' : 'Ventas'; ?>
    </a>
</body>
</html>