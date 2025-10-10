<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
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
        
        $_SESSION['mensaje'] = "✅ Producto agregado al carrito correctamente";
    } else {
        $_SESSION['error'] = "❌ Stock insuficiente. Stock disponible: " . ($stock ? $stock['cantidad'] : 0);
    }
    
    header("Location: ventas.php");
    exit();
}

// Vaciar carrito
if (isset($_GET['vaciar_carrito'])) {
    $_SESSION['carrito'] = [];
    $_SESSION['mensaje'] = "🗑️ Carrito vaciado correctamente";
    header("Location: ventas.php");
    exit();
}

// Eliminar item del carrito
if (isset($_GET['eliminar_item'])) {
    $index = $_GET['eliminar_item'];
    if (isset($_SESSION['carrito'][$index])) {
        unset($_SESSION['carrito'][$index]);
        $_SESSION['carrito'] = array_values($_SESSION['carrito']); // Reindexar
        $_SESSION['mensaje'] = "✅ Producto eliminado del carrito";
    }
    header("Location: ventas.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venta Múltiple - Sistema de Focos LED</title>
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

        /* Carrito */
        .carrito {
            background: var(--light-bg);
            padding: 25px;
            border-radius: 12px;
            margin: 25px 0;
            border: 2px solid var(--border-color);
            box-shadow: var(--shadow-md);
        }

        .carrito h3 {
            color: var(--primary-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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

        /* Total */
        .total-container {
            background: var(--gradient-success);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 25px 0;
            font-size: 20px;
            font-weight: bold;
        }

        /* Botones */
        .btn-container {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin: 20px 0;
        }

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
        }

        .btn-primary { background: var(--gradient-gold); color: white; }
        .btn-success { background: var(--gradient-success); color: white; }
        .btn-danger { background: var(--gradient-danger); color: white; }
        .btn-info { background: var(--gradient-info); color: white; }
        .btn-warning { background: var(--gradient-warning); color: white; }
         .btn-purple {background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; }
            
         
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Productos */
        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .producto-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            border-left: 5px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .producto-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .producto-header h4 {
            color: var(--primary-dark);
            margin-bottom: 10px;
            font-size: 18px;
        }

        .producto-info {
            margin: 15px 0;
        }

        .precio {
            font-size: 20px;
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

        /* Formularios */
        .form-group {
            margin: 15px 0;
        }

        input, select {
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: var(--light-bg);
            width: 100%;
            max-width: 120px;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
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

        /* Carrito vacío */
        .carrito-vacio {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
        }

        .carrito-vacio .icono {
            font-size: 60px;
            margin-bottom: 15px;
        }

        /* Acciones rápidas */
        .acciones-rapidas {
            display: flex;
            gap: 10px;
            margin: 15px 0;
            flex-wrap: wrap;
        }

        .btn-rapido {
            background: var(--gradient-secondary);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-rapido:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(245, 158, 11, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 Venta Múltiple - Focos LED</h1>
            <p class="welcome-message">
                Bienvenido <strong><?php echo $_SESSION['nombre']; ?></strong> 
                (<?php echo $_SESSION['rol']; ?>)
            </p>
        </div>
        
        <!-- Mostrar mensajes -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje mensaje-success">
                <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mensaje mensaje-error">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

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
                        <th>Acciones</th>
                    </tr>
                    <?php 
                    $total_venta = 0;
                    foreach ($_SESSION['carrito'] as $index => $item): 
                        $subtotal = $item['precio'] * $item['cantidad'];
                        $total_venta += $subtotal;
                    ?>
                    <tr>
                        <td><strong><?php echo $item['nombre']; ?></strong></td>
                        <td>$<?php echo number_format($item['precio'], 2); ?></td>
                        <td><?php echo $item['cantidad']; ?></td>
                        <td><strong>$<?php echo number_format($subtotal, 2); ?></strong></td>
                        <td>
                            <a href="ventas.php?eliminar_item=<?php echo $index; ?>" class="btn btn-danger" style="padding: 8px 12px; font-size: 12px;">
                                🗑️ Eliminar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>

                <div class="total-container">
                    💰 Total de la Venta: $<?php echo number_format($total_venta, 2); ?>
                </div>

                <div class="btn-container">
                    <form action="procesar_venta_multiple.php" method="POST" style="display: inline;">
                        <button type="submit" class="btn btn-success">
                            ✅ Finalizar Venta
                        </button>
                    </form>
                    <a href="ventas.php?vaciar_carrito=true" class="btn btn-danger">
                        🗑️ Vaciar Carrito
                    </a>
                </div>
                
            <?php else: ?>
                <div class="carrito-vacio">
                    <div class="icono">🛒</div>
                    <h4>El carrito está vacío</h4>
                    <p>Agrega productos desde la sección de abajo</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- PRODUCTOS DISPONIBLES -->
        <h3 style="color: var(--primary-dark); margin: 30px 0 20px 0; display: flex; align-items: center; gap: 10px;">
            📦 Productos Disponibles
        </h3>

        <div class="productos-grid">
            <?php
            $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio_venta, i.cantidad, i.stock_minimo 
                    FROM productos p 
                    INNER JOIN inventario i ON p.id = i.producto_id 
                    WHERE p.activo = 1
                    ORDER BY p.nombre";
            $result = $conexion->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $stock_class = $row['cantidad'] <= $row['stock_minimo'] ? 'stock-bajo' : '';
                    echo '<div class="producto-card">';
                    echo '<div class="producto-header">';
                    echo '<h4>' . $row['nombre'] . '</h4>';
                    echo '</div>';
                    echo '<div class="producto-info">';
                    echo '<p style="color: var(--text-light); margin-bottom: 10px;">' . $row['descripcion'] . '</p>';
                    echo '<div class="precio">$' . number_format($row['precio_venta'], 2) . '</div>';
                    echo '<div class="stock ' . $stock_class . '">Stock disponible: ' . $row['cantidad'] . ' unidades</div>';
                    echo '</div>';
                    
                    echo '<form action="ventas.php" method="POST">';
                    echo '<input type="hidden" name="producto_id" value="' . $row['id'] . '">';
                    
                    echo '<div class="form-group">';
                    echo '<label style="display: block; margin-bottom: 8px; font-weight: 500;">Cantidad:</label>';
                    echo '<input type="number" name="cantidad" min="1" max="' . $row['cantidad'] . '" value="1" required>';
                    echo '</div>';

                    echo '<div class="acciones-rapidas">';
                    echo '<button type="button" class="btn-rapido" onclick="setCantidad(this, 1)">1</button>';
                    echo '<button type="button" class="btn-rapido" onclick="setCantidad(this, 2)">2</button>';
                    echo '<button type="button" class="btn-rapido" onclick="setCantidad(this, 5)">5</button>';
                    echo '</div>';

                    echo '<button type="submit" name="agregar_carrito" class="btn btn-primary" style="width: 100%; margin-top: 10px;">';
                    echo '➕ Agregar al Carrito';
                    echo '</button>';
                    echo '</form>';
                    echo '</div>';
                }
            } else {
                echo '<div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-light); background: var(--light-bg); border-radius: 10px;">';
                echo '<h4>📭 No hay productos disponibles</h4>';
                echo '<p>Todos los productos están actualmente agotados o inactivos</p>';
                echo '</div>';
            }
            ?>
        </div>

        <!-- Navegación -->
        <div class="nav-inferior">
            <?php if ($_SESSION['rol'] == 'administrador'): ?>
                <a href="reportes.php" class="btn btn-info">📊 Reportes</a>
                <a href="gestion_inventario.php" class="btn btn-success">📦 Gestión de Inventario</a>
                <a href="gestion_usuarios.php" class="btn btn-purple">👥 Gestión Usuarios</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-danger">🚪 Cerrar Sesión</a>
        </div>
    </div>

    <script>
        function setCantidad(button, cantidad) {
            const form = button.closest('form');
            const input = form.querySelector('input[name="cantidad"]');
            const max = parseInt(input.getAttribute('max'));
            
            if (cantidad <= max) {
                input.value = cantidad;
            } else {
                input.value = max;
                alert('La cantidad no puede ser mayor al stock disponible: ' + max);
            }
        }

        // Animación para los productos al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const productos = document.querySelectorAll('.producto-card');
            productos.forEach((producto, index) => {
                producto.style.opacity = '0';
                producto.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    producto.style.transition = 'all 0.5s ease';
                    producto.style.opacity = '1';
                    producto.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>

    <?php $conexion->close(); ?>
</body>
</html>