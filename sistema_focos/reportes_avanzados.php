<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

include 'conexion.php';

// 🆕 MOVER LA LÓGICA DE REINICIO AL INICIO DEL ARCHIVO
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
        
        $_SESSION['mensaje_exito'] = "✅ Cierre mensual realizado. Ventas de $mes_anterior movidas a historial.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
        
    } elseif (isset($_POST['reiniciar_todo'])) {
        // ⚠️ SOLO PARA TESTING - ELIMINAR TODAS LAS VENTAS
        $conexion->begin_transaction();
        try {
            $conexion->query("DELETE FROM detalle_ventas");
            $conexion->query("DELETE FROM ventas");
            $conexion->query("DELETE FROM historial_ajustes");
            $conexion->commit();
            $_SESSION['mensaje_exito'] = "✅ Todas las ventas y ajustes han sido eliminados (sistema reiniciado).";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } catch (Exception $e) {
            $conexion->rollback();
            $_SESSION['mensaje_error'] = "❌ Error al reiniciar: " . $e->getMessage();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes por Período - Sistema de Focos LED</title>
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

        /* Filtros */
        .filtros {
            background: var(--light-bg);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 2px solid var(--border-color);
        }

        .filtros h3 {
            color: var(--primary-dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Períodos rápidos */
        .periodo-rapido {
            display: flex;
            gap: 10px;
            margin: 15px 0;
            flex-wrap: wrap;
        }

        /* Botones */
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

        .btn-primary { background: var(--gradient-gold); color: white; }
        .btn-success { background: var(--gradient-success); color: white; }
        .btn-danger { background: var(--gradient-danger); color: white; }
        .btn-info { background: var(--gradient-info); color: white; }
        .btn-purple { background: var(--gradient-purple); color: white; }
        .btn-warning { background: var(--gradient-warning); color: white; }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Formularios */
        .form-group {
            margin: 15px 0;
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
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        /* Resumen período */
        .resumen-periodo {
            background: var(--gradient-warning);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
            font-weight: 600;
        }

        /* Estadísticas */
        .estadisticas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        /* Sección peligrosa */
        .seccion-peligro {
            background: #fef2f2;
            padding: 25px;
            border-radius: 12px;
            margin: 30px 0;
            border: 2px solid var(--danger-color);
        }

        .seccion-peligro h3 {
            color: var(--danger-color);
        }

        /* Alertas */
        .alerta {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-weight: 500;
        }

        .alerta-peligro {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
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

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            color: var(--text-light);
            font-size: 14px;
        }

        /* Medallas ranking */
        .ranking-1 { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%) !important; color: white; }
        .ranking-2 { background: linear-gradient(135deg, #C0C0C0 0%, #A9A9A9 100%) !important; color: white; }
        .ranking-3 { background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%) !important; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Reportes por Período - Focos LED</h1>
            <p class="welcome-message">Bienvenido Administrador, <strong><?php echo $_SESSION['nombre']; ?></strong></p>
        </div>

        <!-- Mostrar mensajes de éxito/error -->
        <?php if (isset($_SESSION['mensaje_exito'])): ?>
            <div class="mensaje mensaje-success">
                <?php echo $_SESSION['mensaje_exito']; unset($_SESSION['mensaje_exito']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensaje_error'])): ?>
            <div class="mensaje mensaje-error">
                <?php echo $_SESSION['mensaje_error']; unset($_SESSION['mensaje_error']); ?>
            </div>
        <?php endif; ?>

        <!-- Filtros por Período -->
        <div class="filtros">
            <h3>⏰ Seleccionar Período</h3>
            <form method="GET">
                <!-- Períodos Rápidos -->
                <div class="periodo-rapido">
                    <button type="submit" name="periodo" value="hoy" class="btn btn-primary">📅 Hoy</button>
                    <button type="submit" name="periodo" value="ayer" class="btn btn-primary">📅 Ayer</button>
                    <button type="submit" name="periodo" value="esta_semana" class="btn btn-primary">📅 Esta Semana</button>
                    <button type="submit" name="periodo" value="este_mes" class="btn btn-primary">📅 Este Mes</button>
                    <button type="submit" name="periodo" value="mes_anterior" class="btn btn-primary">📅 Mes Anterior</button>
                </div>

                <!-- Período Personalizado -->
                <div class="form-group">
                    <label>📅 Período Personalizado:</label>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>" required>
                        <span style="font-weight: bold;">hasta</span>
                        <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>" required>
                        <button type="submit" name="periodo" value="personalizado" class="btn btn-success">🔍 Generar Reporte</button>
                    </div>
                </div>
            </form>

            <!-- Resumen del Período Seleccionado -->
            <div class="resumen-periodo">
                <h4>📊 Período Seleccionado</h4>
                <p style="margin: 0; font-size: 18px;">
                    <strong>Desde:</strong> <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> 
                    <strong>Hasta:</strong> <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
                </p>
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

        <!-- Ventas del Período -->
        <h3>🛒 Ventas del Período</h3>
        <?php
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
            echo "<tr>
                    <th>ID Venta</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Total</th>
                    <th>Acciones</th>
                  </tr>";
            
            while($venta = $result_ventas->fetch_assoc()) {
                echo "<tr>";
                echo "<td><strong>#" . $venta['id'] . "</strong></td>";
                echo "<td>" . $venta['fecha'] . "</td>";
                echo "<td>" . $venta['hora'] . "</td>";
                echo "<td><strong>$" . number_format($venta['total'], 2) . "</strong></td>";
                echo "<td>
                        <button onclick=\"verDetallesVenta(" . $venta['id'] . ")\" class='btn btn-info' style='padding: 8px 12px; font-size: 14px;'>
                            📋 Ver Detalles
                        </button>
                      </td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div style='text-align: center; padding: 30px; color: var(--text-light); background: var(--light-bg); border-radius: 10px;'>
                    <h4>📭 No hay ventas en el período seleccionado</h4>
                    <p>No se encontraron ventas entre " . date('d/m/Y', strtotime($fecha_inicio)) . " y " . date('d/m/Y', strtotime($fecha_fin)) . "</p>
                  </div>";
        }
        ?>

        <!-- Productos Más Vendidos del Período -->
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
            echo "<tr>
                    <th>Ranking</th>
                    <th>Producto</th>
                    <th>Unidades Vendidas</th>
                    <th>Precio Unit.</th>
                    <th>Ingresos Generados</th>
                  </tr>";
            
            $rank = 1;
            while($row = $result_top->fetch_assoc()) {
                $clase_ranking = $rank <= 3 ? "ranking-$rank" : "";
                $medalla = $rank == 1 ? "🥇" : ($rank == 2 ? "🥈" : ($rank == 3 ? "🥉" : "🔸"));
                
                echo "<tr class='$clase_ranking'>";
                echo "<td><strong>$medalla #$rank</strong></td>";
                echo "<td>" . $row['nombre'] . "</td>";
                echo "<td>" . $row['total_vendido'] . " unidades</td>";
                echo "<td>$" . number_format($row['precio_venta'], 2) . "</td>";
                echo "<td><strong>$" . number_format($row['ingresos_generados'], 2) . "</strong></td>";
                echo "</tr>";
                $rank++;
            }
            echo "</table>";
        } else {
            echo "<div style='text-align: center; padding: 30px; color: var(--text-light); background: var(--light-bg); border-radius: 10px;'>
                    <h4>📭 No hay productos vendidos en el período</h4>
                    <p>No se encontraron ventas de productos en el período seleccionado</p>
                  </div>";
        }
        ?>

        <!-- Reinicio de Contadores -->
        <div class="seccion-peligro">
            <h3>⚠️ Reinicio de Contadores (ADMIN ONLY)</h3>
            <div class="alerta alerta-peligro">
                <strong>🚨 ADVERTENCIA:</strong> Estas acciones son IRREVERSIBLES. Solo para cierre contable.
            </div>
            
            <div style="margin: 20px 0;">
                <form method="POST" onsubmit="return confirm('¿ESTÁS ABSOLUTAMENTE SEGURO? Esto moverá las ventas a historial.')">
                    <button type="submit" name="reiniciar_mes" class="btn btn-danger">
                        📊 Cierre Mensual (Mover a Historial)
                    </button>
                    <small style="margin-left: 10px; color: var(--text-light);">
                        - Archiva ventas del mes y reinicia contadores
                    </small>
                </form>
            </div>
            
            <div style="margin: 20px 0;">
                <form method="POST" onsubmit="return confirm('⚠️ ¿BORRAR TODAS LAS VENTAS? Esto es para TESTING solamente.')">
                    <button type="submit" name="reiniciar_todo" class="btn btn-danger">
                        🗑️ Reiniciar Todo (Solo Testing)
                    </button>
                    <small style="margin-left: 10px; color: var(--text-light);">
                        - ELIMINA todas las ventas (solo para desarrollo)
                    </small>
                </form>
            </div>
        </div>

        <!-- Navegación -->
        <div class="nav-inferior">
            <a href="reportes.php" class="btn btn-info">📊 Reportes Normales</a>
            <a href="gestion_inventario.php" class="btn btn-success">📦 Gestión Inventario</a>
            <a href="gestion_usuarios.php" class="btn btn-purple">👥 Gestión Usuarios</a>
            <a href="ventas.php" class="btn btn-warning">🛒 Ir a Ventas</a>
            <a href="logout.php" class="btn btn-danger">🚪 Cerrar Sesión</a>
        </div>

        <div class="footer">
            Sistema de Focos LED - Reporte generado el <?php echo date('d/m/Y H:i:s'); ?> | 
            Período: <?php echo date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin)); ?>
        </div>
    </div>

    <script>
        function verDetallesVenta(ventaId) {
            window.open('detalles_venta.php?id=' + ventaId, '_blank', 'width=800,height=600');
        }
    </script>

    <?php
    // Cerrar conexión
    $conexion->close();
    ?>
</body>
</html>