<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.html");
    exit();
}

include 'conexion.php';

// Fechas por defecto (mes actual)
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-t');
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'personalizado';

// Aplicar períodos predefinidos
switch ($periodo) {
    case 'hoy':
        $fecha_inicio = date('Y-m-d');
        $fecha_fin = date('Y-m-d');
        break;
    case 'ayer':
        $fecha_inicio = date('Y-m-d', strtotime('-1 day'));
        $fecha_fin = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'esta_semana':
        $fecha_inicio = date('Y-m-d', strtotime('monday this week'));
        $fecha_fin = date('Y-m-d');
        break;
    case 'este_mes':
        $fecha_inicio = date('Y-m-01');
        $fecha_fin = date('Y-m-t');
        break;
    case 'mes_anterior':
        $fecha_inicio = date('Y-m-01', strtotime('-1 month'));
        $fecha_fin = date('Y-m-t', strtotime('-1 month'));
        break;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reportes por Período - Sistema de Focos</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .filtros { background: #e3f2fd; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .estadisticas { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .estadistica-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .numero-grande { font-size: 24px; font-weight: bold; color: #4CAF50; }
        .resumen-periodo { background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .btn { background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
        .btn-warning { background: #ffc107; color: black; }
        .periodo-rapido { display: flex; gap: 10px; margin: 15px 0; flex-wrap: wrap; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📅 Reportes por Período - Sistema de Focos</h1>
        <p>Bienvenido Administrador, <strong><?php echo $_SESSION['nombre']; ?></strong></p>

        <!-- Filtros por Período -->
        <div class="filtros">
            <h3>⏰ Seleccionar Período</h3>
            <form method="GET">
                <!-- Períodos Rápidos -->
                <div class="periodo-rapido">
                    <button type="submit" name="periodo" value="hoy" class="btn">📅 Hoy</button>
                    <button type="submit" name="periodo" value="ayer" class="btn">📅 Ayer</button>
                    <button type="submit" name="periodo" value="esta_semana" class="btn">📅 Esta Semana</button>
                    <button type="submit" name="periodo" value="este_mes" class="btn">📅 Este Mes</button>
                    <button type="submit" name="periodo" value="mes_anterior" class="btn">📅 Mes Anterior</button>
                </div>

                <!-- Período Personalizado -->
                <div style="margin: 15px 0;">
                    <label><strong>Período Personalizado:</strong></label><br>
                    <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>" required>
                    <span> hasta </span>
                    <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>" required>
                    <button type="submit" name="periodo" value="personalizado" class="btn-success">🔍 Generar Reporte</button>
                </div>
            </form>

            <!-- Resumen del Período Seleccionado -->
            <div class="resumen-periodo">
                <h4>📊 Período Seleccionado:</h4>
                <p><strong>Desde:</strong> <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> 
                   <strong>Hasta:</strong> <?php echo date('d/m/Y', strtotime($fecha_fin)); ?></p>
            </div>
        </div>

        <?php
        // ESTADÍSTICAS DEL PERÍODO
        $sql_estadisticas = "SELECT 
                            COUNT(*) as total_ventas,
                            COALESCE(SUM(total), 0) as ingresos_totales,
                            COALESCE(AVG(total), 0) as promedio_venta,
                            MAX(total) as venta_maxima,
                            (SELECT COUNT(DISTINCT DATE(fecha)) FROM ventas WHERE fecha BETWEEN ? AND ?) as dias_con_ventas
                         FROM ventas 
                         WHERE fecha BETWEEN ? AND ?";
        
        $stmt = $conexion->prepare($sql_estadisticas);
        $stmt->bind_param("ssss", $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result_estadisticas = $stmt->get_result();
        $estadisticas = $result_estadisticas->fetch_assoc();
        ?>

        <!-- Tarjetas de Estadísticas -->
        <div class="estadisticas">
            <div class="estadistica-card">
                <div class="numero-grande"><?php echo $estadisticas['total_ventas']; ?></div>
                <div>Ventas Totales</div>
            </div>
            <div class="estadistica-card">
                <div class="numero-grande">$<?php echo number_format($estadisticas['ingresos_totales'], 2); ?></div>
                <div>Ingresos Totales</div>
            </div>
            <div class="estadistica-card">
                <div class="numero-grande">$<?php echo number_format($estadisticas['promedio_venta'], 2); ?></div>
                <div>Ticket Promedio</div>
            </div>
            <div class="estadistica-card">
                <div class="numero-grande">$<?php echo number_format($estadisticas['venta_maxima'], 2); ?></div>
                <div>Venta Más Alta</div>
            </div>
            <div class="estadistica-card">
                <div class="numero-grande"><?php echo $estadisticas['dias_con_ventas']; ?></div>
                <div>Días con Ventas</div>
            </div>
        </div>

        <!-- Ventas del Período - CONSULTA CORREGIDA -->
        <h3>🛒 Ventas del Período</h3>
        <?php
        // CONSULTA CORREGIDA - Sin GROUP_CONCAT
        $sql_ventas = "SELECT v.id, v.fecha, v.hora, v.total
                       FROM ventas v 
                       WHERE v.fecha BETWEEN ? AND ?
                       ORDER BY v.fecha DESC, v.hora DESC";
        
        $stmt = $conexion->prepare($sql_ventas);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result_ventas = $stmt->get_result();
        
        if ($result_ventas->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>ID Venta</th><th>Fecha</th><th>Hora</th><th>Total</th><th>Acciones</th></tr>";
            
            while($venta = $result_ventas->fetch_assoc()) {
                echo "<tr>";
                echo "<td>#" . $venta['id'] . "</td>";
                echo "<td>" . $venta['fecha'] . "</td>";
                echo "<td>" . $venta['hora'] . "</td>";
                echo "<td><strong>$" . number_format($venta['total'], 2) . "</strong></td>";
                echo "<td><button onclick=\"verDetallesVenta(" . $venta['id'] . ")\" class='btn'>📋 Ver Detalles</button></td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Script para ver detalles (modal simple)
            echo "
            <script>
            function verDetallesVenta(ventaId) {
                window.open('detalles_venta.php?id=' + ventaId, '_blank', 'width=800,height=600');
            }
            </script>
            ";
        } else {
            echo "<p>No hay ventas en el período seleccionado.</p>";
        }
        ?>

        <!-- Productos Más Vendidos del Período - CONSULTA CORREGIDA -->
        <h3>🏆 Productos Más Vendidos del Período</h3>
        <?php
        $sql_top_productos = "SELECT p.nombre, 
                                     SUM(dv.cantidad) as total_vendido,
                                     SUM(dv.subtotal) as ingresos_generados,
                                     p.precio_venta
                              FROM detalle_ventas dv
                              INNER JOIN productos p ON dv.producto_id = p.id
                              INNER JOIN ventas v ON dv.venta_id = v.id
                              WHERE v.fecha BETWEEN ? AND ?
                              GROUP BY p.id, p.nombre, p.precio_venta
                              ORDER BY total_vendido DESC
                              LIMIT 10";
        
        $stmt = $conexion->prepare($sql_top_productos);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result_top = $stmt->get_result();
        
        if ($result_top->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>Producto</th><th>Unidades Vendidas</th><th>Precio Unit.</th><th>Ingresos Generados</th><th>Ranking</th></tr>";
            
            $rank = 1;
            while($row = $result_top->fetch_assoc()) {
                $medalla = $rank == 1 ? "🥇" : ($rank == 2 ? "🥈" : ($rank == 3 ? "🥉" : ""));
                echo "<tr>";
                echo "<td>" . $row['nombre'] . "</td>";
                echo "<td>" . $row['total_vendido'] . " unidades</td>";
                echo "<td>$" . number_format($row['precio_venta'], 2) . "</td>";
                echo "<td><strong>$" . number_format($row['ingresos_generados'], 2) . "</strong></td>";
                echo "<td>" . $medalla . " #" . $rank . "</td>";
                echo "</tr>";
                $rank++;
            }
            echo "</table>";
        } else {
            echo "<p>No hay ventas de productos en el período seleccionado.</p>";
        }
        ?>

        <!-- 🆕 Reinicio de Contadores (OPCIONAL/PELIGROSO) -->
        <div style="background: #f8d7da; padding: 20px; border-radius: 10px; margin: 30px 0; border: 2px solid #dc3545;">
            <h3 style="color: #dc3545;">⚠️ Reinicio de Contadores (ADMIN ONLY)</h3>
            <p><strong>ADVERTENCIA:</strong> Estas acciones son IRREVERSIBLES. Solo para cierre contable.</p>
            
            <div style="margin: 15px 0;">
                <form method="POST" onsubmit="return confirm('¿ESTÁS ABSOLUTAMENTE SEGURO? Esto moverá las ventas a historial.')">
                    <button type="submit" name="reiniciar_mes" class="btn-danger">
                        📊 Cierre Mensual (Mover a Historial)
                    </button>
                    <small> - Archiva ventas del mes y reinicia contadores</small>
                </form>
            </div>
            
            <div style="margin: 15px 0;">
                <form method="POST" onsubmit="return confirm('⚠️ ¿BORRAR TODAS LAS VENTAS? Esto es para TESTING solamente.')">
                    <button type="submit" name="reiniciar_todo" class="btn-danger">
                        🗑️ Reiniciar Todo (Solo Testing)
                    </button>
                    <small> - ELIMINA todas las ventas (solo para desarrollo)</small>
                </form>
            </div>
        </div>

        <!-- Navegación -->
        <div style="margin-top: 30px;">
            <a href="reportes.php" class="btn">📊 Reportes Normales</a>
            <a href="gestion_inventario.php" class="btn">📦 Gestión Inventario</a>
            <a href="gestion_usuarios.php" class="btn">👥 Gestión Usuarios</a>
            <a href="ventas.php" class="btn">🛒 Ir a Ventas</a>
            <a href="logout.php" class="btn-danger">🚪 Cerrar Sesión</a>
        </div>

        <p style="margin-top: 20px; color: #666; font-size: 12px;">
            Reporte generado el <?php echo date('d/m/Y H:i:s'); ?> | 
            Período: <?php echo date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin)); ?>
        </p>
    </div>

    <?php
    // 🆕 LÓGICA PARA REINICIAR CONTADORES
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['reiniciar_mes'])) {
            // Crear tabla de historial_ventas si no existe
            $sql_crear_historial = "CREATE TABLE IF NOT EXISTS historial_ventas LIKE ventas";
            $conexion->query($sql_crear_historial);
            
            // Mover ventas del mes anterior al historial
            $mes_anterior = date('Y-m', strtotime('-1 month'));
            $sql_mover = "INSERT INTO historial_ventas SELECT * FROM ventas WHERE DATE_FORMAT(fecha, '%Y-%m') = ?";
            $stmt = $conexion->prepare($sql_mover);
            $stmt->bind_param("s", $mes_anterior);
            $stmt->execute();
            
            // Eliminar ventas del mes anterior
            $sql_eliminar = "DELETE FROM ventas WHERE DATE_FORMAT(fecha, '%Y-%m') = ?";
            $stmt = $conexion->prepare($sql_eliminar);
            $stmt->bind_param("s", $mes_anterior);
            $stmt->execute();
            
            echo "<script>alert('✅ Cierre mensual realizado. Ventas de $mes_anterior movidas a historial.');</script>";
            
        } elseif (isset($_POST['reiniciar_todo'])) {
            // ⚠️ SOLO PARA TESTING - ELIMINAR TODAS LAS VENTAS
            $conexion->begin_transaction();
            try {
                $conexion->query("DELETE FROM detalle_ventas");
                $conexion->query("DELETE FROM ventas");
                $conexion->query("DELETE FROM historial_ajustes");
                $conexion->commit();
                echo "<script>alert('✅ Todas las ventas y ajustes han sido eliminados (sistema reiniciado).');</script>";
            } catch (Exception $e) {
                $conexion->rollback();
                echo "<script>alert('❌ Error al reiniciar: " . $e->getMessage() . "');</script>";
            }
        }
    }
    
    $conexion->close();
    ?>
</body>
</html>