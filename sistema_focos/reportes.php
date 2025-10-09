<?php
session_start();
// Si no está logueado o no es administrador, redirigir
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

include 'conexion.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Sistema de Focos LED</title>
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
            --gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --gradient-danger: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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

        /* Estadísticas */
        .estadisticas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .estadistica-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            text-align: center;
            border-left: 5px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .estadistica-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .numero-grande {
            font-size: 32px;
            font-weight: bold;
            color: var(--primary-dark);
            margin-bottom: 8px;
        }

        /* Tablas */
        h2, h3 {
            color: var(--primary-dark);
            margin: 25px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
        }

        table {
            border-collapse: collapse;
            width: 100%;
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

        /* Estados de stock */
        .stock-bajo {
            background-color: #fef3c7 !important;
            border-left: 4px solid var(--warning-color);
        }

        .stock-critico {
            background-color: #fee2e2 !important;
            border-left: 4px solid var(--danger-color);
        }

        /* Ventas */
        .venta-container {
            margin: 20px 0;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .venta-header {
            background: var(--gradient-primary);
            color: white;
            padding: 15px;
            font-weight: 600;
        }

        .venta-detalle {
            background-color: var(--light-bg);
        }

        /* Botones */
        .btn-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 25px 0;
        }

        .btn {
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--gradient-gold);
            color: white;
        }

        .btn-success {
            background: var(--gradient-success);
            color: white;
        }

        .btn-danger {
            background: var(--gradient-danger);
            color: white;
        }

        .btn-info {
            background: var(--info-color);
            color: white;
        }

        .btn-purple {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Resumen total */
        .resumen-total {
            background: var(--gradient-success);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 25px 0;
            font-size: 18px;
            font-weight: bold;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            color: var(--text-light);
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Reportes del Sistema - Focos LED</h1>
            <p class="welcome-message">Bienvenido Administrador, <strong><?php echo $_SESSION['nombre']; ?></strong></p>
        </div>
        
        <!-- Estadísticas Rápidas -->
        <div class="estadisticas">
            <?php
            // Total de ventas
            $sql_total_ventas = "SELECT COUNT(*) as total_ventas, COALESCE(SUM(total), 0) as ingresos_totales FROM ventas";
            $result_total = $conexion->query($sql_total_ventas);
            $total_data = $result_total->fetch_assoc();
            
            // Productos en stock bajo
            $sql_stock_bajo = "SELECT COUNT(*) as stock_bajo FROM inventario i 
                              INNER JOIN productos p ON i.producto_id = p.id 
                              WHERE i.cantidad <= i.stock_minimo AND p.activo = 1";
            $result_stock = $conexion->query($sql_stock_bajo);
            $stock_bajo = $result_stock->fetch_assoc();
            
            // Productos más vendidos
            $sql_mas_vendido = "SELECT p.nombre, SUM(dv.cantidad) as total_vendido 
                               FROM detalle_ventas dv 
                               INNER JOIN productos p ON dv.producto_id = p.id 
                               GROUP BY p.id 
                               ORDER BY total_vendido DESC 
                               LIMIT 1";
            $result_vendido = $conexion->query($sql_mas_vendido);
            $mas_vendido = $result_vendido->fetch_assoc();
            ?>
            
            <div class="estadistica-card">
                <div class="numero-grande"><?php echo $total_data['total_ventas']; ?></div>
                <div>Ventas Totales</div>
            </div>
            <div class="estadistica-card">
                <div class="numero-grande">$<?php echo number_format($total_data['ingresos_totales'], 2); ?></div>
                <div>Ingresos Totales</div>
            </div>
            <div class="estadistica-card">
                <div class="numero-grande" style="color: <?php echo $stock_bajo['stock_bajo'] > 0 ? '#ef4444' : '#d97706'; ?>">
                    <?php echo $stock_bajo['stock_bajo']; ?>
                </div>
                <div>Productos con Stock Bajo</div>
            </div>
            <div class="estadistica-card">
                <div class="numero-grande" style="font-size: 18px;">
                    <?php echo $mas_vendido ? $mas_vendido['nombre'] : 'N/A'; ?>
                </div>
                <div>Producto Más Vendido</div>
            </div>
        </div>

        <!-- Ventas Recientes Detalladas -->
        <h3>🛒 Ventas Recientes Detalladas</h3>
        <?php
        $sql_ventas = "SELECT id, fecha, hora, total 
                       FROM ventas 
                       ORDER BY fecha DESC, hora DESC 
                       LIMIT 10";
        $result_ventas = $conexion->query($sql_ventas);
        
        if ($result_ventas && $result_ventas->num_rows > 0) {
            $total_periodo = 0;
            
            while($venta = $result_ventas->fetch_assoc()) {
                $total_periodo += $venta['total'];
                
                echo "<div class='venta-container'>";
                echo "<div class='venta-header'>";
                echo "<strong>Venta #" . $venta['id'] . "</strong> - " . $venta['fecha'] . " " . $venta['hora'] . " - Total: <strong>$" . number_format($venta['total'], 2) . "</strong>";
                echo "</div>";
                
                $sql_detalles = "SELECT p.nombre, dv.cantidad, dv.precio_unitario, dv.subtotal 
                                FROM detalle_ventas dv 
                                INNER JOIN productos p ON dv.producto_id = p.id 
                                WHERE dv.venta_id = " . $venta['id'];
                $result_detalles = $conexion->query($sql_detalles);
                
                if ($result_detalles && $result_detalles->num_rows > 0) {
                    echo "<table>";
                    echo "<tr><th>Producto</th><th>Cantidad</th><th>Precio Unit.</th><th>Subtotal</th></tr>";
                    
                    while($detalle = $result_detalles->fetch_assoc()) {
                        echo "<tr class='venta-detalle'>";
                        echo "<td>" . $detalle['nombre'] . "</td>";
                        echo "<td>" . $detalle['cantidad'] . "</td>";
                        echo "<td>$" . number_format($detalle['precio_unitario'], 2) . "</td>";
                        echo "<td>$" . number_format($detalle['subtotal'], 2) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p style='padding: 15px; text-align: center; color: var(--text-light);'>No hay detalles para esta venta.</p>";
                }
                
                echo "</div>";
            }
            
            echo "<div class='resumen-total'>";
            echo "<strong>Total del período: $" . number_format($total_periodo, 2) . "</strong>";
            echo "</div>";
            
        } else {
            echo "<p style='text-align: center; padding: 20px; color: var(--text-light);'>No hay ventas registradas.</p>";
        }
        ?>

        <!-- Inventario Actual -->
        <h3>📦 Inventario Actual</h3>
        <?php
        $sql_inventario = "SELECT p.id, p.nombre, p.precio_venta, i.cantidad, i.stock_minimo 
                          FROM productos p 
                          INNER JOIN inventario i ON p.id = i.producto_id 
                          WHERE p.activo = 1
                          ORDER BY i.cantidad ASC, p.nombre";
        $result_inventario = $conexion->query($sql_inventario);
        
        if ($result_inventario && $result_inventario->num_rows > 0) {
            echo "<table>";
            echo "<tr>
                    <th>Producto</th>
                    <th>Precio Venta</th>
                    <th>Cantidad</th>
                    <th>Stock Mínimo</th>
                    <th>Valor en Inventario</th>
                    <th>Estado</th>
                  </tr>";
            
            $valor_total_inventario = 0;
            while($row = $result_inventario->fetch_assoc()) {
                $valor_producto = $row['precio_venta'] * $row['cantidad'];
                $valor_total_inventario += $valor_producto;
                
                $clase = '';
                $estado = '✅ OK';
                
                if ($row['cantidad'] == 0) {
                    $clase = 'stock-critico';
                    $estado = '❌ AGOTADO';
                } elseif ($row['cantidad'] <= $row['stock_minimo']) {
                    $clase = 'stock-bajo';
                    $estado = '⚠️ Stock Bajo';
                }
                
                echo "<tr class='$clase'>";
                echo "<td>" . $row['nombre'] . "</td>";
                echo "<td>$" . number_format($row['precio_venta'], 2) . "</td>";
                echo "<td>" . $row['cantidad'] . "</td>";
                echo "<td>" . $row['stock_minimo'] . "</td>";
                echo "<td>$" . number_format($valor_producto, 2) . "</td>";
                echo "<td>" . $estado . "</td>";
                echo "</tr>";
            }
            
            echo "<tr style='background: var(--gradient-primary); color: white;'>";
            echo "<td colspan='4' style='text-align: right; font-weight: bold;'>Valor total del inventario:</td>";
            echo "<td colspan='2' style='font-weight: bold; font-size: 16px;'>$" . number_format($valor_total_inventario, 2) . "</td>";
            echo "</tr>";
            
            echo "</table>";
        } else {
            echo "<p style='text-align: center; padding: 20px; color: var(--text-light);'>No hay productos en el inventario.</p>";
        }

        // Cerrar conexión
        $conexion->close();
        ?>
        
        <!-- Botones de acción -->
        <div class="btn-container">
            <a href="ventas.php" class="btn btn-primary">🛒 Ir a Ventas</a>
            <a href="gestion_usuarios.php" class="btn btn-purple">👥 Gestión de Usuarios</a>
            <a href="gestion_inventario.php" class="btn btn-info">📦 Gestión de Inventario</a>
            <a href="reportes_avanzados.php" class="btn btn-success">📅 Reportes por Período</a>
            <a href="logout.php" class="btn btn-danger">🚪 Cerrar Sesión</a>
        </div>
        
        <div class="footer">
            Sistema de Focos LED - Reportes generados el <?php echo date('d/m/Y H:i:s'); ?>
        </div>
    </div>
</body>
</html>