<?php
session_start();
include 'conexion.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Mensaje de éxito
$mensaje_exito = '';
if (isset($_GET['exito'])) {
    $mensaje_exito = $_GET['exito'];
}

if ($_POST) {
    $producto_id = $_POST['producto_id'];
    $cantidad = $_POST['cantidad'];
    $usuario_id = $_SESSION['usuario_id'];

    // Obtener información del producto (CÓDIGO CORREGIDO)
    $sql_producto = "SELECT p.precio_venta, i.cantidad as stock, p.nombre 
                     FROM productos p 
                     INNER JOIN inventario i ON p.id = i.producto_id 
                     WHERE p.id = ? AND p.activo = 1";
    
    $stmt = $conexion->prepare($sql_producto);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();  // ← ESTA ES LA LÍNEA CLAVE CORREGIDA
    $producto = $result->fetch_assoc();
    
    // Cerrar el statement antes de continuar
    $stmt->close();
    
    if (!$producto) {
        die("Producto no encontrado o inactivo");
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
        $sql_venta = "INSERT INTO ventas (fecha, hora, total, usuario_id) VALUES (CURDATE(), CURTIME(), ?, ?)";
        $stmt = $conexion->prepare($sql_venta);
        $stmt->bind_param("di", $total_venta, $usuario_id);
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
        header("Location: ventas.php?exito=Venta registrada correctamente. Producto: " . $producto['nombre'] . " - Cantidad: " . $cantidad . " - Total: $" . number_format($total_venta, 2));
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

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto de Venta - Sistema de Focos LED</title>
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
            --purple-color: #8b5cf6;
            
            /* Degradados Amarillos/Dorados */
            --gradient-primary: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --gradient-secondary: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            --gradient-gold: linear-gradient(135deg, #fcd34d 0%, #f59e0b 50%, #d97706 100%);
            --gradient-sunshine: linear-gradient(135deg, #fef3c7 0%, #fcd34d 50%, #f59e0b 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --gradient-danger: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --gradient-info: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --gradient-purple: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            
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
            max-width: 1200px;
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
            text-align: center;
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

        /* Layout principal */
        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 30px 0;
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }

        /* Secciones */
        .seccion {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            border-left: 5px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .seccion:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        h2, h3 {
            color: var(--primary-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Formularios */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 500;
        }

        input, select {
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: var(--light-bg);
            width: 100%;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        /* Botones */
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary { background: var(--gradient-gold); color: white; }
        .btn-success { background: var(--gradient-success); color: white; }
        .btn-danger { background: var(--gradient-danger); color: white; }
        .btn-info { background: var(--gradient-info); color: white; }
        .btn-purple { background: var(--gradient-purple); color: white; }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-full {
            width: 100%;
            justify-content: center;
            padding: 15px;
            font-size: 18px;
        }

        /* Información del producto */
        .producto-info {
            background: var(--light-bg);
            padding: 20px;
            border-radius: 10px;
            margin-top: 15px;
            border: 2px solid var(--border-color);
        }

        .precio {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-dark);
            margin: 10px 0;
        }

        .stock {
            color: var(--text-light);
            font-size: 14px;
        }

        .stock-bajo {
            color: var(--danger-color);
            font-weight: bold;
        }

        /* Ventas rápidas */
        .ventas-rapidas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }

        .btn-rapido {
            background: var(--gradient-secondary);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-rapido:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(245, 158, 11, 0.3);
        }

        /* Navegación */
        .nav-inferior {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        /* Calculadora */
        .calculadora {
            background: var(--light-bg);
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }

        .total-venta {
            font-size: 20px;
            font-weight: bold;
            color: var(--success-color);
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 Punto de Venta - Focos LED</h1>
            <p class="welcome-message">
                Bienvenido <strong><?php echo $_SESSION['nombre']; ?></strong> 
                (<?php echo $_SESSION['rol']; ?>)
            </p>
        </div>
        
        <?php if (!empty($mensaje_exito)): ?>
            <div class="mensaje mensaje-success">
                ✅ <?php echo htmlspecialchars($mensaje_exito); ?>
            </div>
        <?php endif; ?>

        <div class="main-content">
            <!-- Sección de Venta -->
            <div class="seccion">
                <h2>💰 Realizar Venta</h2>
                <form method="POST" id="formVenta">
                    <div class="form-group">
                        <label for="producto_id">📦 Seleccionar Producto:</label>
                        <select name="producto_id" id="producto_id" required onchange="actualizarInfoProducto()">
                            <option value="">-- Selecciona un producto --</option>
                            <?php
                            $sql_productos = "SELECT p.id, p.nombre, p.precio_venta, i.cantidad as stock 
                                             FROM productos p 
                                             INNER JOIN inventario i ON p.id = i.producto_id 
                                             WHERE p.activo = 1 
                                             ORDER BY p.nombre";
                            $result_productos = $conexion->query($sql_productos);
                            while($producto = $result_productos->fetch_assoc()) {
                                $stock_class = $producto['stock'] <= 5 ? 'stock-bajo' : '';
                                echo "<option value='{$producto['id']}' 
                                      data-precio='{$producto['precio_venta']}' 
                                      data-stock='{$producto['stock']}'
                                      data-nombre='{$producto['nombre']}'>
                                      {$producto['nombre']} (Stock: <span class='{$stock_class}'>{$producto['stock']}</span>)
                                      </option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div id="infoProducto" class="producto-info" style="display: none;">
                        <h4>📋 Información del Producto</h4>
                        <div><strong>Producto:</strong> <span id="nombreProducto"></span></div>
                        <div class="precio">Precio: $<span id="precioProducto">0.00</span></div>
                        <div class="stock">Stock disponible: <span id="stockProducto">0</span> unidades</div>
                    </div>

                    <div class="form-group">
                        <label for="cantidad">🔢 Cantidad:</label>
                        <input type="number" name="cantidad" id="cantidad" min="1" value="1" required oninput="calcularTotal()">
                    </div>

                    <div class="calculadora">
                        <h4>🧮 Cálculo de Venta</h4>
                        <div><strong>Precio unitario:</strong> $<span id="displayPrecio">0.00</span></div>
                        <div><strong>Cantidad:</strong> <span id="displayCantidad">0</span></div>
                        <div class="total-venta">Total: $<span id="totalVenta">0.00</span></div>
                    </div>

                    <button type="submit" class="btn btn-success btn-full" id="btnVender">
                        💰 Realizar Venta
                    </button>
                </form>

                <!-- Ventas rápidas -->
                <div style="margin-top: 20px;">
                    <h4>⚡ Cantidades Rápidas</h4>
                    <div class="ventas-rapidas">
                        <button type="button" class="btn-rapido" onclick="setCantidad(1)">1</button>
                        <button type="button" class="btn-rapido" onclick="setCantidad(2)">2</button>
                        <button type="button" class="btn-rapido" onclick="setCantidad(5)">5</button>
                        <button type="button" class="btn-rapido" onclick="setCantidad(10)">10</button>
                    </div>
                </div>
            </div>

            <!-- Sección de Información -->
            <div class="seccion">
                <h2>📊 Información de Ventas</h2>
                
                <!-- Estadísticas rápidas -->
                <div style="margin-bottom: 20px;">
                    <h4>📈 Hoy</h4>
                    <?php
                    $sql_hoy = "SELECT COUNT(*) as ventas_hoy, COALESCE(SUM(total), 0) as total_hoy 
                               FROM ventas 
                               WHERE fecha = CURDATE()";
                    $result_hoy = $conexion->query($sql_hoy);
                    $hoy = $result_hoy->fetch_assoc();
                    ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div style="background: var(--light-bg); padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: var(--primary-color);">
                                <?php echo $hoy['ventas_hoy']; ?>
                            </div>
                            <div>Ventas Hoy</div>
                        </div>
                        <div style="background: var(--light-bg); padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: var(--success-color);">
                                $<?php echo number_format($hoy['total_hoy'], 2); ?>
                            </div>
                            <div>Total Hoy</div>
                        </div>
                    </div>
                </div>

                <!-- Productos más vendidos -->
                <div>
                    <h4>🏆 Productos Populares</h4>
                    <?php
                    $sql_populares = "SELECT p.nombre, SUM(dv.cantidad) as total_vendido 
                                     FROM detalle_ventas dv 
                                     INNER JOIN productos p ON dv.producto_id = p.id 
                                     GROUP BY p.id 
                                     ORDER BY total_vendido DESC 
                                     LIMIT 5";
                    $result_populares = $conexion->query($sql_populares);
                    
                    if ($result_populares->num_rows > 0) {
                        echo "<ul style='list-style: none; padding: 0;'>";
                        while($popular = $result_populares->fetch_assoc()) {
                            echo "<li style='padding: 8px; background: var(--light-bg); margin: 5px 0; border-radius: 5px;'>
                                    {$popular['nombre']} <span style='float: right; font-weight: bold; color: var(--primary-color);'>{$popular['total_vendido']} vendidos</span>
                                  </li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "<p style='text-align: center; color: var(--text-light);'>No hay ventas registradas.</p>";
                    }
                    ?>
                </div>

                <!-- Últimas ventas -->
                <div style="margin-top: 20px;">
                    <h4>🕒 Últimas Ventas</h4>
                    <?php
                    $sql_ultimas = "SELECT v.id, v.fecha, v.hora, v.total, p.nombre as producto_nombre, dv.cantidad
                                   FROM ventas v
                                   INNER JOIN detalle_ventas dv ON v.id = dv.venta_id
                                   INNER JOIN productos p ON dv.producto_id = p.id
                                   ORDER BY v.fecha DESC, v.hora DESC 
                                   LIMIT 5";
                    $result_ultimas = $conexion->query($sql_ultimas);
                    
                    if ($result_ultimas->num_rows > 0) {
                        echo "<div style='max-height: 200px; overflow-y: auto;'>";
                        while($venta = $result_ultimas->fetch_assoc()) {
                            echo "<div style='padding: 10px; background: var(--light-bg); margin: 5px 0; border-radius: 5px; border-left: 3px solid var(--success-color);'>
                                    <div><strong>{$venta['producto_nombre']}</strong> x{$venta['cantidad']}</div>
                                    <div style='font-size: 12px; color: var(--text-light);'>
                                        {$venta['fecha']} {$venta['hora']} - <strong>${$venta['total']}</strong>
                                    </div>
                                  </div>";
                        }
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Navegación inferior -->
        <div class="nav-inferior">
            <?php if ($_SESSION['rol'] == 'administrador'): ?>
                <a href="reportes.php" class="btn btn-info">📊 Reportes</a>
                <a href="gestion_inventario.php" class="btn btn-success">📦 Inventario</a>
                <a href="gestion_usuarios.php" class="btn btn-purple">👥 Usuarios</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-danger">🚪 Cerrar Sesión</a>
        </div>
    </div>

    <script>
        function actualizarInfoProducto() {
            const select = document.getElementById('producto_id');
            const selectedOption = select.options[select.selectedIndex];
            const infoDiv = document.getElementById('infoProducto');
            
            if (select.value) {
                const precio = selectedOption.getAttribute('data-precio');
                const stock = selectedOption.getAttribute('data-stock');
                const nombre = selectedOption.getAttribute('data-nombre');
                
                document.getElementById('nombreProducto').textContent = nombre;
                document.getElementById('precioProducto').textContent = parseFloat(precio).toFixed(2);
                document.getElementById('stockProducto').textContent = stock;
                document.getElementById('displayPrecio').textContent = parseFloat(precio).toFixed(2);
                
                // Mostrar información del producto
                infoDiv.style.display = 'block';
                
                // Actualizar cantidad máxima
                document.getElementById('cantidad').max = stock;
                
                // Recalcular total
                calcularTotal();
            } else {
                infoDiv.style.display = 'none';
                calcularTotal();
            }
        }

        function calcularTotal() {
            const precio = parseFloat(document.getElementById('precioProducto').textContent) || 0;
            const cantidad = parseInt(document.getElementById('cantidad').value) || 0;
            const total = precio * cantidad;
            
            document.getElementById('displayCantidad').textContent = cantidad;
            document.getElementById('totalVenta').textContent = total.toFixed(2);
            
            // Validar stock
            const stock = parseInt(document.getElementById('stockProducto').textContent) || 0;
            const btnVender = document.getElementById('btnVender');
            
            if (cantidad > stock) {
                btnVender.disabled = true;
                btnVender.style.background = '#9ca3af';
                document.getElementById('totalVenta').style.color = 'var(--danger-color)';
            } else {
                btnVender.disabled = false;
                btnVender.style.background = '';
                document.getElementById('totalVenta').style.color = 'var(--success-color)';
            }
        }

        function setCantidad(cantidad) {
            document.getElementById('cantidad').value = cantidad;
            calcularTotal();
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            actualizarInfoProducto();
        });
    </script>

    <?php $conexion->close(); ?>
</body>
</html>