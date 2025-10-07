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
<html>
<head>
    <title>Detalles Venta #<?php echo $venta_id; ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0;
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
        .total-row {
            background-color: #e8f5e8 !important;
            font-weight: bold;
            font-size: 16px;
        }
        button { 
            padding: 12px 20px; 
            background: #dc3545; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📋 Detalles de Venta #<?php echo $venta_id; ?></h2>
        
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
        <?php else: ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h3>❌ No se encontraron detalles</h3>
                <p>No hay productos registrados para la venta #<?php echo $venta_id; ?></p>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 20px;">
            <button onclick="window.close()">✖️ Cerrar Ventana</button>
        </div>
    </div>
</body>
</html>
<?php 
$stmt->close();
$conexion->close(); 
?>