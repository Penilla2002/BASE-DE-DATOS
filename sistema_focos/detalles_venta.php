<?php
session_start();
include "conexion.php";

$venta_id = $_GET['id'] ?? 0;

$sql = "SELECT p.nombre, dv.cantidad, dv.precio_unitario, dv.subtotal 
        FROM detalle_ventas dv 
        INNER JOIN productos p ON dv.producto_id = p.id 
        WHERE dv.venta_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles Venta #<?php echo $venta_id; ?> - Focos LED</title>
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
            --gradient-danger: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 900px;
            width: 100%;
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

        .header h2 {
            color: var(--primary-dark);
            font-size: 28px;
            margin-bottom: 10px;
        }

        .venta-id {
            color: var(--text-light);
            font-size: 16px;
            background: var(--light-bg);
            padding: 8px 15px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 5px;
        }

        /* Tabla */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
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

        .total-row {
            background: var(--gradient-success) !important;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }

        .total-row td {
            border-bottom: none;
        }

        /* Mensaje de error */
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
            border: 1px solid #fecaca;
        }

        .error-message h3 {
            color: #dc2626;
            margin-bottom: 10px;
        }

        /* Botón */
        .btn-container {
            text-align: center;
            margin-top: 25px;
        }

        .btn {
            padding: 12px 25px;
            background: var(--gradient-danger);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 38, 38, 0.3);
        }

        /* Información adicional */
        .info-adicional {
            background: var(--light-bg);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
            color: var(--text-light);
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>📋 Detalles de Venta</h2>
            <div class="venta-id">Venta #<?php echo $venta_id; ?></div>
        </div>
        
        <?php if ($result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                </tr>
                <?php 
                $total_venta = 0;
                while($row = $result->fetch_assoc()): 
                    $total_venta += $row['subtotal'];
                ?>
                <tr>
                    <td><?php echo $row['nombre']; ?></td>
                    <td><?php echo $row['cantidad']; ?></td>
                    <td>$<?php echo number_format($row['precio_unitario'], 2); ?></td>
                    <td>$<?php echo number_format($row['subtotal'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;"><strong>Total de la Venta:</strong></td>
                    <td><strong>$<?php echo number_format($total_venta, 2); ?></strong></td>
                </tr>
            </table>

            <div class="info-adicional">
                <strong>💡 Información:</strong> Esta venta contiene <?php echo $result->num_rows; ?> producto(s) diferentes.
            </div>
        <?php else: ?>
            <div class="error-message">
                <h3>❌ No se encontraron detalles</h3>
                <p>No hay productos registrados para la venta #<?php echo $venta_id; ?></p>
                <p style="margin-top: 10px; font-size: 14px;">Es posible que la venta haya sido eliminada o no exista.</p>
            </div>
        <?php endif; ?>
        
        <div class="btn-container">
            <button class="btn" onclick="window.close()">
                <span>✖️</span>
                Cerrar Ventana
            </button>
        </div>

        <div class="info-adicional">
            Sistema de Focos LED - Detalles generados el <?php echo date('d/m/Y H:i:s'); ?>
        </div>
    </div>
</body>
</html>
<?php 
$stmt->close();
$conexion->close(); 
?>