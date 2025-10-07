<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.html");
    exit();
}

include 'conexion.php';

$mensaje = '';

// 1. PROCESAR AGREGAR STOCK A PRODUCTOS EXISTENTES
if (isset($_POST['agregar_stock'])) {
    $producto_id = $_POST['producto_id'];
    $cantidad = $_POST['cantidad'];
    
    $sql = "UPDATE inventario SET cantidad = cantidad + ? WHERE producto_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $cantidad, $producto_id);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Stock actualizado correctamente (+$cantidad unidades)";
    } else {
        $mensaje = "❌ Error al actualizar stock";
    }
}

// 2. PROCESAR NUEVO PRODUCTO
if (isset($_POST['nuevo_producto'])) {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $costo_compra = $_POST['costo_compra'];
    $precio_venta = $_POST['precio_venta'];
    $stock_inicial = $_POST['stock_inicial'];
    $stock_minimo = $_POST['stock_minimo'];
    
    $sql_producto = "INSERT INTO productos (nombre, descripcion, costo_compra, precio_venta) VALUES (?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql_producto);
    $stmt->bind_param("ssdd", $nombre, $descripcion, $costo_compra, $precio_venta);
    
    if ($stmt->execute()) {
        $producto_id = $conexion->insert_id;
        
        $sql_inventario = "INSERT INTO inventario (producto_id, cantidad, stock_minimo) VALUES (?, ?, ?)";
        $stmt = $conexion->prepare($sql_inventario);
        $stmt->bind_param("iii", $producto_id, $stock_inicial, $stock_minimo);
        $stmt->execute();
        
        $mensaje = "✅ Producto '$nombre' agregado correctamente con $stock_inicial unidades";
    } else {
        $mensaje = "❌ Error al agregar producto";
    }
}

// 3. PROCESAR AJUSTES DE INVENTARIO (MERMAS/DAÑOS)
if (isset($_POST['ajustar_inventario'])) {
    $producto_id = $_POST['producto_id_ajuste'];
    $cantidad = $_POST['cantidad_ajuste'];
    $motivo = $_POST['motivo'];
    $tipo_ajuste = $_POST['tipo_ajuste'];
    
    $sql_stock = "SELECT cantidad FROM inventario WHERE producto_id = ?";
    $stmt = $conexion->prepare($sql_stock);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stock_actual = $result->fetch_assoc()['cantidad'];
    
    if ($cantidad <= $stock_actual) {
        $sql = "UPDATE inventario SET cantidad = cantidad - ? WHERE producto_id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ii", $cantidad, $producto_id);
        
        if ($stmt->execute()) {
            $sql_historial = "INSERT INTO historial_ajustes (producto_id, cantidad, motivo, tipo_ajuste, fecha, usuario_id) 
                             VALUES (?, ?, ?, ?, NOW(), ?)";
            $stmt = $conexion->prepare($sql_historial);
            $stmt->bind_param("iissi", $producto_id, $cantidad, $motivo, $tipo_ajuste, $_SESSION['usuario_id']);
            $stmt->execute();
            
            $mensaje = "✅ Inventario ajustado correctamente ($tipo_ajuste: -$cantidad unidades)";
        } else {
            $mensaje = "❌ Error al ajustar inventario";
        }
    } else {
        $mensaje = "❌ La cantidad a reducir no puede ser mayor al stock actual ($stock_actual unidades)";
    }
}

// 4. PROCESAR DESACTIVACIÓN DE PRODUCTO
if (isset($_POST['desactivar_producto'])) {
    $producto_id = $_POST['producto_id_desactivar'];
    
    $sql = "UPDATE productos SET activo = 0 WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Producto desactivado correctamente";
    } else {
        $mensaje = "❌ Error al desactivar producto";
    }
}

// 5. PROCESAR ELIMINACIÓN PERMANENTE
if (isset($_POST['eliminar_permanentemente'])) {
    $producto_id = $_POST['producto_id_eliminar'];
    
    // Verificar si el producto tiene ventas
    $sql_verificar_ventas = "SELECT COUNT(*) as total_ventas FROM detalle_ventas WHERE producto_id = ?";
    $stmt = $conexion->prepare($sql_verificar_ventas);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ventas = $result->fetch_assoc()['total_ventas'];
    
    if ($ventas > 0) {
        $mensaje = "❌ No se puede eliminar el producto porque tiene $ventas venta(s) registrada(s). Use desactivación.";
    } else {
        $conexion->begin_transaction();
        
        try {
            // Eliminar de historial_ajustes
            $sql_eliminar_historial = "DELETE FROM historial_ajustes WHERE producto_id = ?";
            $stmt = $conexion->prepare($sql_eliminar_historial);
            $stmt->bind_param("i", $producto_id);
            $stmt->execute();
            
            // Eliminar de inventario
            $sql_eliminar_inventario = "DELETE FROM inventario WHERE producto_id = ?";
            $stmt = $conexion->prepare($sql_eliminar_inventario);
            $stmt->bind_param("i", $producto_id);
            $stmt->execute();
            
            // Eliminar el producto
            $sql_eliminar_producto = "DELETE FROM productos WHERE id = ?";
            $stmt = $conexion->prepare($sql_eliminar_producto);
            $stmt->bind_param("i", $producto_id);
            $stmt->execute();
            
            $conexion->commit();
            $mensaje = "✅ Producto eliminado permanentemente del sistema";
            
        } catch (Exception $e) {
            $conexion->rollback();
            $mensaje = "❌ Error al eliminar producto: " . $e->getMessage();
        }
    }
}

// Crear tabla de historial de ajustes si no existe
$sql_create_table = "CREATE TABLE IF NOT EXISTS historial_ajustes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    tipo_ajuste ENUM('merma', 'daño') NOT NULL,
    fecha DATETIME NOT NULL,
    usuario_id INT NOT NULL,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
)";
$conexion->query($sql_create_table);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestión de Inventario - Sistema de Focos</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .seccion { 
            background: #f8f9fa; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 10px;
            border-left: 4px solid #4CAF50;
        }
        .seccion-peligro {
            border-left: 4px solid #dc3545;
            background: #f8d7da;
        }
        h1, h2, h3 {
            color: #333;
        }
        input, select, textarea { 
            padding: 10px; 
            margin: 5px; 
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 200px;
        }
        textarea {
            width: 400px;
        }
        button { 
            background: #28a745; 
            color: white; 
            padding: 12px 20px; 
            border: none; 
            cursor: pointer; 
            border-radius: 5px; 
            margin: 5px;
            font-size: 14px;
        }
        button:hover {
            opacity: 0.9;
        }
        .btn-danger { background: #dc3545; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-dark { background: #343a40; }
        .btn-info { background: #17a2b8; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0;
            background: white;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 12px; 
            text-align: left; 
        }
        th { 
            background-color: #4CAF50; 
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .inactivo { 
            background-color: #f8d7da; 
            color: #721c24;
        }
        .stock-bajo {
            background-color: #fff3cd;
        }
        .nav-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .nav-tab {
            padding: 10px 20px;
            background: #e9ecef;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
            cursor: pointer;
        }
        .nav-tab.active {
            background: #4CAF50;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 Gestión Completa de Inventario</h1>
        <p>Bienvenido Administrador, <strong><?php echo $_SESSION['nombre']; ?></strong></p>
        
        <?php if (!empty($mensaje)): ?>
            <div style='padding: 15px; background: #d4edda; color: #155724; border-radius: 5px; margin: 15px 0; border: 1px solid #c3e6cb;'>
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Navegación por pestañas -->
        <div class="nav-tabs">
            <div class="nav-tab active" onclick="mostrarTab('tab-stock')">📥 Stock</div>
            <div class="nav-tab" onclick="mostrarTab('tab-productos')">🆕 Productos</div>
            <div class="nav-tab" onclick="mostrarTab('tab-ajustes')">📉 Ajustes</div>
            <div class="nav-tab" onclick="mostrarTab('tab-gestion')">⚙️ Gestión</div>
            <div class="nav-tab" onclick="mostrarTab('tab-inventario')">📊 Inventario</div>
        </div>

        <!-- Pestaña 1: Gestión de Stock -->
        <div id="tab-stock" class="tab-content active">
            <div class="seccion">
                <h2>📥 Agregar Stock a Productos Existentes</h2>
                <form method="POST">
                    <select name="producto_id" required style="width: 300px;">
                        <option value="">Seleccionar Producto</option>
                        <?php
                        $sql = "SELECT p.id, p.nombre, i.cantidad FROM productos p 
                                INNER JOIN inventario i ON p.id = i.producto_id 
                                WHERE p.activo = 1
                                ORDER BY p.nombre";
                        $result = $conexion->query($sql);
                        while($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['nombre']} (Stock actual: {$row['cantidad']})</option>";
                        }
                        ?>
                    </select><br>
                    <input type="number" name="cantidad" min="1" placeholder="Cantidad a agregar" required>
                    <button type="submit" name="agregar_stock">➕ Agregar Stock</button>
                </form>
            </div>
        </div>

        <!-- Pestaña 2: Gestión de Productos -->
        <div id="tab-productos" class="tab-content">
            <div class="seccion">
                <h2>🆕 Agregar Nuevo Producto</h2>
                <form method="POST">
                    <input type="text" name="nombre" placeholder="Nombre del producto" required style="width: 300px;"><br>
                    <textarea name="descripcion" placeholder="Descripción del producto" rows="3" required></textarea><br>
                    <input type="number" name="costo_compra" step="0.01" placeholder="Costo de compra" required>
                    <input type="number" name="precio_venta" step="0.01" placeholder="Precio de venta" required><br>
                    <input type="number" name="stock_inicial" min="0" placeholder="Stock inicial" required>
                    <input type="number" name="stock_minimo" min="1" placeholder="Stock mínimo" value="5" required>
                    <button type="submit" name="nuevo_producto">📦 Agregar Producto</button>
                </form>
            </div>
        </div>

        <!-- Pestaña 3: Ajustes de Inventario -->
        <div id="tab-ajustes" class="tab-content">
            <div class="seccion">
                <h2>📉 Ajustes de Inventario (Mermas/Daños)</h2>
                <form method="POST">
                    <select name="producto_id_ajuste" required style="width: 300px;">
                        <option value="">Seleccionar Producto</option>
                        <?php
                        $sql = "SELECT p.id, p.nombre, i.cantidad FROM productos p 
                                INNER JOIN inventario i ON p.id = i.producto_id 
                                WHERE p.activo = 1
                                ORDER BY p.nombre";
                        $result = $conexion->query($sql);
                        while($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['nombre']} (Stock: {$row['cantidad']})</option>";
                        }
                        ?>
                    </select><br>
                    <input type="number" name="cantidad_ajuste" min="1" placeholder="Cantidad a reducir" required>
                    <select name="tipo_ajuste" required>
                        <option value="merma">Merma/Pérdida</option>
                        <option value="daño">Daño/Defectuoso</option>
                    </select>
                    <input type="text" name="motivo" placeholder="Motivo específico" required style="width: 250px;">
                    <button type="submit" name="ajustar_inventario" class="btn-danger">📉 Reducir Inventario</button>
                </form>
            </div>

            <div class="seccion">
                <h2>📋 Historial de Ajustes Recientes</h2>
                <?php
                $sql_historial = "SELECT ha.*, p.nombre as producto_nombre, u.nombre as usuario_nombre 
                                 FROM historial_ajustes ha
                                 INNER JOIN productos p ON ha.producto_id = p.id
                                 INNER JOIN usuarios u ON ha.usuario_id = u.id
                                 ORDER BY ha.fecha DESC 
                                 LIMIT 10";
                $result_historial = $conexion->query($sql_historial);
                
                if ($result_historial->num_rows > 0) {
                    echo "<table>";
                    echo "<tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Motivo</th><th>Usuario</th></tr>";
                    while($row = $result_historial->fetch_assoc()) {
                        $color_tipo = $row['tipo_ajuste'] == 'merma' ? '#ffc107' : '#dc3545';
                        echo "<tr>";
                        echo "<td>" . $row['fecha'] . "</td>";
                        echo "<td>" . $row['producto_nombre'] . "</td>";
                        echo "<td style='background-color: $color_tipo; color: white;'>" . ucfirst($row['tipo_ajuste']) . "</td>";
                        echo "<td>" . $row['cantidad'] . "</td>";
                        echo "<td>" . $row['motivo'] . "</td>";
                        echo "<td>" . $row['usuario_nombre'] . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>No hay ajustes registrados.</p>";
                }
                ?>
            </div>
        </div>

        <!-- Pestaña 4: Gestión Avanzada -->
        <div id="tab-gestion" class="tab-content">
            <div class="seccion">
                <h2>🚫 Desactivar Producto</h2>
                <p><em>El producto dejará de aparecer en ventas pero mantendrá su historial.</em></p>
                <form method="POST" onsubmit="return confirm('¿Estás seguro de que quieres desactivar este producto? No aparecerá en ventas.')">
                    <select name="producto_id_desactivar" required style="width: 300px;">
                        <option value="">Seleccionar Producto a Desactivar</option>
                        <?php
                        $sql = "SELECT p.id, p.nombre, i.cantidad FROM productos p 
                                INNER JOIN inventario i ON p.id = i.producto_id 
                                WHERE p.activo = 1
                                ORDER BY p.nombre";
                        $result = $conexion->query($sql);
                        while($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['nombre']} (Stock: {$row['cantidad']})</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" name="desactivar_producto" class="btn-warning">🚫 Desactivar Producto</button>
                </form>
            </div>

            <div class="seccion seccion-peligro">
                <h2 style="color: #dc3545;">☠️ Eliminación Permanente</h2>
                <p style="color: #dc3545; font-weight: bold;">
                    ⚠️ ADVERTENCIA: Esta acción no se puede deshacer. Se eliminarán TODOS los registros del producto.
                </p>
                <form method="POST" onsubmit="return confirm('⚠️ ¿ESTÁS ABSOLUTAMENTE SEGURO?\n\nEsta acción ELIMINARÁ PERMANENTEMENTE el producto de:\n- Inventario\n- Historial de ajustes\n\n¿Continuar?');">
                    <select name="producto_id_eliminar" required style="width: 300px;">
                        <option value="">Seleccionar Producto a ELIMINAR</option>
                        <?php
                        $sql = "SELECT p.id, p.nombre, i.cantidad, p.activo 
                                FROM productos p 
                                INNER JOIN inventario i ON p.id = i.producto_id 
                                WHERE p.activo = 1
                                ORDER BY p.nombre";
                        $result = $conexion->query($sql);
                        while($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['nombre']} - Stock: {$row['cantidad']}</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" name="eliminar_permanentemente" class="btn-dark">☠️ Eliminar Permanentemente</button>
                </form>
                
                <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px;">
                    <h4>📋 Nota importante:</h4>
                    <p>Los productos con ventas registradas no se pueden eliminar para conservar el historial.</p>
                </div>
            </div>
        </div>

        <!-- Pestaña 5: Inventario Actual -->
        <div id="tab-inventario" class="tab-content">
            <div class="seccion">
                <h2>📊 Inventario Actual Completo</h2>
                <?php
                $sql = "SELECT p.id, p.nombre, p.descripcion, p.costo_compra, p.precio_venta, 
                               i.cantidad, i.stock_minimo, p.activo,
                               (p.precio_venta * i.cantidad) as valor_total
                        FROM productos p 
                        INNER JOIN inventario i ON p.id = i.producto_id 
                        ORDER BY p.activo DESC, p.nombre";
                $result = $conexion->query($sql);
                
                if ($result->num_rows > 0) {
                    $valor_total_inventario = 0;
                    echo "<table>";
                    echo "<tr>
                            <th>Producto</th>
                            <th>Descripción</th>
                            <th>Costo</th>
                            <th>Precio Venta</th>
                            <th>Stock</th>
                            <th>Stock Mín.</th>
                            <th>Valor Total</th>
                            <th>Estado</th>
                          </tr>";
                    while($row = $result->fetch_assoc()) {
                        $clase = '';
                        if ($row['activo'] == 0) {
                            $clase = 'inactivo';
                        } elseif ($row['cantidad'] <= $row['stock_minimo']) {
                            $clase = 'stock-bajo';
                        }
                        
                        $estado = $row['activo'] == 0 ? '❌ INACTIVO' : 
                                 ($row['cantidad'] > $row['stock_minimo'] ? '✅ ACTIVO' : '⚠️ STOCK BAJO');
                        
                        $valor_total_inventario += $row['valor_total'];
                        
                        echo "<tr class='$clase'>";
                        echo "<td>{$row['nombre']}</td>";
                        echo "<td>{$row['descripcion']}</td>";
                        echo "<td>$" . number_format($row['costo_compra'], 2) . "</td>";
                        echo "<td>$" . number_format($row['precio_venta'], 2) . "</td>";
                        echo "<td>{$row['cantidad']}</td>";
                        echo "<td>{$row['stock_minimo']}</td>";
                        echo "<td>$" . number_format($row['valor_total'], 2) . "</td>";
                        echo "<td>{$estado}</td>";
                        echo "</tr>";
                    }
                    
                    echo "<tr style='background-color: #e3f2fd; font-weight: bold;'>";
                    echo "<td colspan='6' style='text-align: right;'>Valor total del inventario:</td>";
                    echo "<td colspan='2'>$" . number_format($valor_total_inventario, 2) . "</td>";
                    echo "</tr>";
                    
                    echo "</table>";
                } else {
                    echo "<p>No hay productos en el inventario.</p>";
                }
                ?>
            </div>
        </div>

        <!-- Navegación inferior -->
        <div style="margin-top: 30px; text-align: center;">
            <a href="reportes.php" style="background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">📊 Volver a Reportes</a>
            <a href="ventas.php" style="background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">🛒 Ir a Ventas</a>
            <a href="logout.php" style="background: #dc3545; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">🚪 Cerrar Sesión</a>
        </div>
    </div>

    <script>
        function mostrarTab(tabId) {
            // Ocultar todas las pestañas
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remover activo de todos los botones
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Mostrar pestaña seleccionada
            document.getElementById(tabId).classList.add('active');
            
            // Marcar botón como activo
            event.target.classList.add('active');
        }
    </script>

    <?php $conexion->close(); ?>
</body>
</html>