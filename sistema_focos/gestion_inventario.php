<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario - Sistema de Focos LED</title>
    <style>
        /* ===== VARIABLES DE COLOR AMARILLO/DORADO ===== */
        :root {
            --primary-color: #f59e0b;
            --primary-dark: #d97706;
            --secondary-color: #fbbf24;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            
            /* Degradados Amarillos/Dorados */
            --gradient-primary: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --gradient-secondary: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            --gradient-gold: linear-gradient(135deg, #fcd34d 0%, #f59e0b 50%, #d97706 100%);
            --gradient-sunshine: linear-gradient(135deg, #fef3c7 0%, #fcd34d 50%, #f59e0b 100%);
            --gradient-success: linear-gradient(135deg, #b9b610ff 0%, #968005ff 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --gradient-danger: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --gradient-info: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --gradient-purple: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            --gradient-dark: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            
            /* Colores neutros */
            --light-bg: #fffbeb;
            --card-bg: #ffffff;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --border-color: #fde68a;
            
            /* Sombras */
            --shadow-sm: 0 1px 2px 0 rgba(245, 158, 11, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(245, 158, 11, 0.1), 0 2px 4px -1px rgba(245, 158, 11, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(245, 158, 11, 0.1), 0 4px 6px -2px rgba(245, 158, 11, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gradient-sunshine);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--card-bg);
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .header h1 {
            color: var(--primary-dark);
            font-size: 32px;
            margin-bottom: 10px;
        }

        .welcome-message {
            color: var(--text-light);
            font-size: 16px;
        }

        .welcome-message strong {
            color: var(--primary-color);
        }

        /* Mensajes */
        .mensaje {
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            border: 1px solid;
            font-weight: 500;
        }

        .mensaje-success {
            background: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }

        .mensaje-error {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fecaca;
        }

        /* Pestañas */
        .nav-tabs {
            display: flex;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--border-color);
            flex-wrap: wrap;
            gap: 5px;
        }

        .nav-tab {
            padding: 12px 20px;
            background: var(--light-bg);
            border: 2px solid var(--border-color);
            border-bottom: none;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            color: var(--text-dark);
        }

        .nav-tab.active {
            background: var(--gradient-gold);
            color: white;
            border-color: var(--primary-dark);
        }

        .nav-tab:hover:not(.active) {
            background: #fef3c7;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Secciones */
        .seccion {
            background: var(--card-bg);
            padding: 25px;
            margin: 20px 0;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            border-left: 5px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .seccion:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .seccion-peligro {
            border-left: 5px solid var(--danger-color);
            background: #fef2f2;
        }

        h2, h3 {
            color: var(--primary-dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Formularios */
        .form-group {
            margin-bottom: 15px;
        }

        input, select, textarea {
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: var(--light-bg);
            width: 100%;
            max-width: 300px;
        }

        textarea {
            max-width: 500px;
            min-height: 80px;
            resize: vertical;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        /* Botones */
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            margin: 5px;
        }

        .btn-primary { background: var(--gradient-gold); color: white; }
        .btn-success { background: var(--gradient-success); color: white; }
        .btn-danger { background: var(--gradient-danger); color: white; }
        .btn-warning { background: var(--gradient-warning); color: white; }
        .btn-info { background: var(--gradient-info); color: white; }
        .btn-purple { background: var(--gradient-purple); color: white; }
        .btn-dark { background: var(--gradient-dark); color: white; }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Tablas */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: var(--card-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background: var(--gradient-gold);
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:nth-child(even) {
            background-color: var(--light-bg);
        }

        tr:hover {
            background-color: #fef3c7;
        }

        /* Estados */
        .inactivo {
            background-color: #fef2f2 !important;
            color: #991b1b;
        }

        .stock-bajo {
            background-color: #fffbeb !important;
            border-left: 4px solid var(--warning-color);
        }

        /* Navegación inferior */
        .nav-inferior {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        /* Alertas importantes */
        .alerta-importante {
            background: #fef3c7;
            border: 2px solid var(--warning-color);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .alerta-peligro {
            background: #fef2f2;
            border: 2px solid var(--danger-color);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Gestión Completa de Inventario</h1>
            <p class="welcome-message">Bienvenido Administrador, <strong><?php echo $_SESSION['nombre']; ?></strong></p>
        </div>
        
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo strpos($mensaje, '✅') !== false ? 'mensaje-success' : 'mensaje-error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Navegación por pestañas -->
        <div class="nav-tabs">
            <div class="nav-tab active" onclick="mostrarTab('tab-stock')">📥 Agregar Stock</div>
            <div class="nav-tab" onclick="mostrarTab('tab-productos')">🆕 Nuevos Productos</div>
            <div class="nav-tab" onclick="mostrarTab('tab-ajustes')">📉 Ajustes/Mermas</div>
            <div class="nav-tab" onclick="mostrarTab('tab-gestion')">⚙️ Gestión Avanzada</div>
            <div class="nav-tab" onclick="mostrarTab('tab-inventario')">📊 Inventario Actual</div>
        </div>

        <!-- Pestaña 1: Gestión de Stock -->
        <div id="tab-stock" class="tab-content active">
            <div class="seccion">
                <h2>📥 Agregar Stock a Productos Existentes</h2>
                <form method="POST">
                    <div class="form-group">
                        <select name="producto_id" required>
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
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="number" name="cantidad" min="1" placeholder="Cantidad a agregar" required>
                    </div>
                    <button type="submit" name="agregar_stock" class="btn btn-success">➕ Agregar Stock</button>
                </form>
            </div>
        </div>

        <!-- Pestaña 2: Gestión de Productos -->
        <div id="tab-productos" class="tab-content">
            <div class="seccion">
                <h2>🆕 Agregar Nuevo Producto</h2>
                <form method="POST">
                    <div class="form-group">
                        <input type="text" name="nombre" placeholder="Nombre del producto" required>
                    </div>
                    <div class="form-group">
                        <textarea name="descripcion" placeholder="Descripción del producto" required></textarea>
                    </div>
                    <div class="form-group">
                        <input type="number" name="costo_compra" step="0.01" placeholder="Costo de compra" required>
                        <input type="number" name="precio_venta" step="0.01" placeholder="Precio de venta" required>
                    </div>
                    <div class="form-group">
                        <input type="number" name="stock_inicial" min="0" placeholder="Stock inicial" required>
                        <input type="number" name="stock_minimo" min="1" placeholder="Stock mínimo" value="5" required>
                    </div>
                    <button type="submit" name="nuevo_producto" class="btn btn-primary">📦 Agregar Producto</button>
                </form>
            </div>
        </div>

        <!-- Pestaña 3: Ajustes de Inventario -->
        <div id="tab-ajustes" class="tab-content">
            <div class="seccion">
                <h2>📉 Ajustes de Inventario (Mermas/Daños)</h2>
                <form method="POST">
                    <div class="form-group">
                        <select name="producto_id_ajuste" required>
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
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="number" name="cantidad_ajuste" min="1" placeholder="Cantidad a reducir" required>
                        <select name="tipo_ajuste" required>
                            <option value="merma">Merma/Pérdida</option>
                            <option value="daño">Daño/Defectuoso</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="text" name="motivo" placeholder="Motivo específico" required>
                    </div>
                    <button type="submit" name="ajustar_inventario" class="btn btn-danger">📉 Reducir Inventario</button>
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
                        $color_tipo = $row['tipo_ajuste'] == 'merma' ? 'var(--warning-color)' : 'var(--danger-color)';
                        echo "<tr>";
                        echo "<td>" . $row['fecha'] . "</td>";
                        echo "<td>" . $row['producto_nombre'] . "</td>";
                        echo "<td style='background-color: $color_tipo; color: white; font-weight: bold;'>" . ucfirst($row['tipo_ajuste']) . "</td>";
                        echo "<td>" . $row['cantidad'] . "</td>";
                        echo "<td>" . $row['motivo'] . "</td>";
                        echo "<td>" . $row['usuario_nombre'] . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p style='text-align: center; padding: 20px; color: var(--text-light);'>No hay ajustes registrados.</p>";
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
                    <div class="form-group">
                        <select name="producto_id_desactivar" required>
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
                    </div>
                    <button type="submit" name="desactivar_producto" class="btn btn-warning">🚫 Desactivar Producto</button>
                </form>
            </div>

            <div class="seccion seccion-peligro">
                <h2 style="color: var(--danger-color);">☠️ Eliminación Permanente</h2>
                <div class="alerta-peligro">
                    <strong>⚠️ ADVERTENCIA:</strong> Esta acción no se puede deshacer. Se eliminarán TODOS los registros del producto.
                </div>
                <form method="POST" onsubmit="return confirm('⚠️ ¿ESTÁS ABSOLUTAMENTE SEGURO?\n\nEsta acción ELIMINARÁ PERMANENTEMENTE el producto de:\n- Inventario\n- Historial de ajustes\n\n¿Continuar?');">
                    <div class="form-group">
                        <select name="producto_id_eliminar" required>
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
                    </div>
                    <button type="submit" name="eliminar_permanentemente" class="btn btn-dark">☠️ Eliminar Permanentemente</button>
                </form>
                
                <div class="alerta-importante" style="margin-top: 15px;">
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
                    
                    echo "<tr style='background: var(--gradient-success); color: white; font-weight: bold;'>";
                    echo "<td colspan='6' style='text-align: right;'>Valor total del inventario:</td>";
                    echo "<td colspan='2'>$" . number_format($valor_total_inventario, 2) . "</td>";
                    echo "</tr>";
                    
                    echo "</table>";
                } else {
                    echo "<p style='text-align: center; padding: 20px; color: var(--text-light);'>No hay productos en el inventario.</p>";
                }
                ?>
            </div>
        </div>

        <!-- Navegación inferior -->
        <div class="nav-inferior">
            <a href="reportes.php" class="btn btn-info">📊 Volver a Reportes</a>
            <a href="ventas.php" class="btn btn-success">🛒 Ir a Ventas</a>
            <a href="logout.php" class="btn btn-danger">🚪 Cerrar Sesión</a>
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