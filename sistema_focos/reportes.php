<?php
session_start();
// Si no está logueado o no es administrador, redirigir
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.html");
    exit();
}

include 'conexion.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reportes - Sistema de Focos</title>
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
        h1, h2, h3 {
            color: #333;
        }
        table { 
            border-collapse: collapse; 
            width: 100%; 
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
        tr:hover {
            background-color: #f1f1f1;
        }
        .stock-bajo { 
            background-color: #ffeaa7 !important; 
        }
        .stock-critico {
            background-color: #ffcccc !important;
        }
        .estadisticas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .estadistica-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #4CAF50;
        }
        .numero-grande {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
        }
        .btn-descargar {
            background: #007bff;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
        }
        .btn-descargar:hover {
            background: #0056b3;
        }
        .venta-header {
            background-color: #e3f2fd !important;
            font-weight: bold;
        }
        .venta-detalle {
            background-color: #fafafa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Reportes del Sistema - Local de Focos</h1>
        <p>Bienvenido Administrador, <strong><?php echo $_SESSION['nombre']; ?></strong></p>
        
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
                <div class="numero-grande" style="color: <?php echo $stock_bajo['stock_bajo'] > 0 ? '#e74c3c' : '#4CAF50'; ?>">
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

        <!-- Ventas Recientes Detalladas - ENFOQUE ALTERNATIVO -->
        <h3>🛒 Ventas Recientes Detalladas</h3>
        <?php
        // ENFOQUE ALTERNATIVO: Primero obtener las ventas, luego los detalles por separado
        $sql_ventas = "SELECT id, fecha, hora, total 
                       FROM ventas 
                       ORDER BY fecha DESC, hora DESC 
                       LIMIT 10";
        $result_ventas = $conexion->query($sql_ventas);
        
        if ($result_ventas && $result_ventas->num_rows > 0) {
            $total_periodo = 0;
            
            while($venta = $result_ventas->fetch_assoc()) {
                $total_periodo += $venta['total'];
                
                // Mostrar cabecera de la venta
                echo "<div style='margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;'>";
                echo "<div class='venta-header' style='padding: 10px; background: #e3f2fd;'>";
                echo "<strong>Venta #" . $venta['id'] . "</strong> - " . $venta['fecha'] . " " . $venta['hora'] . " - Total: <strong>$" . number_format($venta['total'], 2) . "</strong>";
                echo "</div>";
                
                // Obtener detalles de esta venta
                $sql_detalles = "SELECT p.nombre, dv.cantidad, dv.precio_unitario, dv.subtotal 
                                FROM detalle_ventas dv 
                                INNER JOIN productos p ON dv.producto_id = p.id 
                                WHERE dv.venta_id = " . $venta['id'];
                $result_detalles = $conexion->query($sql_detalles);
                
                if ($result_detalles && $result_detalles->num_rows > 0) {
                    echo "<table style='width: 100%; margin: 0;'>";
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
                    echo "<p style='padding: 10px;'>No hay detalles para esta venta.</p>";
                }
                
                echo "</div>";
            }
            
            echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
            echo "<strong>Total del período: $" . number_format($total_periodo, 2) . "</strong>";
            echo "</div>";
            
        } else {
            echo "<p>No hay ventas registradas.</p>";
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
            
            echo "<tr style='background-color: #e3f2fd;'>";
            echo "<td colspan='4' style='text-align: right; font-weight: bold;'>Valor total del inventario:</td>";
            echo "<td colspan='2' style='font-weight: bold; font-size: 16px;'>$" . number_format($valor_total_inventario, 2) . "</td>";
            echo "</tr>";
            
            echo "</table>";
        } else {
            echo "<p>No hay productos en el inventario.</p>";
        }

        // Cerrar conexión
        $conexion->close();
        ?>
        
        <!-- Botones de acción -->
        <div style="margin-top: 20px;">
            <a href="ventas.php" class="btn-descargar">🛒 Ir a Ventas</a>
            <a href="gestion_usuarios.php" class="btn-descargar" style="background: #e83e8c;">👥 Gestión de Usuarios</a>
            <a href="logout.php" style="background: #dc3545; margin-left: 10px;" class="btn-descargar">🚪 Cerrar Sesión</a>
            <a href="gestion_inventario.php" class="btn-descargar" style="background: #17a2b8;">📦 Gestión de Inventario</a>        
            <a href="reportes_avanzados.php" class="btn-descargar" style="background: #6f42c1;">📅 Reportes por Período</a>
        </div>
        
        <p style="margin-top: 20px; color: #666; font-size: 12px;">
            Sistema de Focos - Reportes generados el <?php echo date('d/m/Y H:i:s'); ?>
        </p>
    </div>
</body>
</html>