<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

include 'conexion.php';

$mensaje = '';

// 1. CREAR NUEVO USUARIO
if (isset($_POST['crear_usuario'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $rol = $_POST['rol'];
    
    // Verificar si el usuario ya existe
    $sql_verificar = "SELECT id FROM usuarios WHERE username = ?";
    $stmt = $conexion->prepare($sql_verificar);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $mensaje = "❌ El nombre de usuario ya existe";
    } else {
        // Encriptar contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (username, password_hash, nombre, apellido, email, rol) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssssss", $username, $password_hash, $nombre, $apellido, $email, $rol);
        
        if ($stmt->execute()) {
            $mensaje = "✅ Usuario creado correctamente";
        } else {
            $mensaje = "❌ Error al crear el usuario";
        }
    }
}

// 2. CAMBIAR CONTRASEÑA
if (isset($_POST['cambiar_password'])) {
    $usuario_id = $_POST['usuario_id_password'];
    $nueva_password = $_POST['nueva_password'];
    
    $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    
    $sql = "UPDATE usuarios SET password_hash = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $password_hash, $usuario_id);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Contraseña actualizada correctamente";
    } else {
        $mensaje = "❌ Error al actualizar la contraseña";
    }
}

// 3. ACTIVAR/DESACTIVAR USUARIO
if (isset($_POST['toggle_activo'])) {
    $usuario_id = $_POST['usuario_id_toggle'];
    $accion = $_POST['accion']; // 'activar' o 'desactivar'
    
    $nuevo_estado = $accion == 'activar' ? 1 : 0;
    
    $sql = "UPDATE usuarios SET activo = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $nuevo_estado, $usuario_id);
    
    if ($stmt->execute()) {
        $mensaje = $accion == 'activar' ? "✅ Usuario activado correctamente" : "✅ Usuario desactivado correctamente";
    } else {
        $mensaje = "❌ Error al cambiar el estado del usuario";
    }
}

// 4. EDITAR USUARIO
if (isset($_POST['editar_usuario'])) {
    $usuario_id = $_POST['usuario_id_editar'];
    $nombre = $_POST['nombre_editar'];
    $apellido = $_POST['apellido_editar'];
    $email = $_POST['email_editar'];
    $rol = $_POST['rol_editar'];
    
    $sql = "UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, rol = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssssi", $nombre, $apellido, $email, $rol, $usuario_id);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Usuario actualizado correctamente";
    } else {
        $mensaje = "❌ Error al actualizar el usuario";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Sistema de Focos LED</title>
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

        /* Estadísticas */
        .usuario-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: var(--shadow-md);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .stat-numero {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
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

        .seccion-info {
            border-left: 5px solid var(--info-color);
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

        /* Radio buttons y labels */
        .radio-group {
            margin: 15px 0;
        }

        .radio-option {
            margin: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .radio-option input[type="radio"] {
            width: auto;
            max-width: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 Gestión de Usuarios - Sistema de Focos LED</h1>
            <p class="welcome-message">Bienvenido Administrador, <strong><?php echo $_SESSION['nombre']; ?></strong></p>
        </div>
        
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo strpos($mensaje, '✅') !== false ? 'mensaje-success' : 'mensaje-error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas rápidas -->
        <div class="usuario-stats">
            <?php
            $sql_stats = "SELECT 
                         COUNT(*) as total_usuarios,
                         SUM(activo = 1) as usuarios_activos,
                         SUM(rol = 'administrador') as administradores,
                         SUM(rol = 'vendedor') as vendedores
                         FROM usuarios";
            $result_stats = $conexion->query($sql_stats);
            $stats = $result_stats->fetch_assoc();
            ?>
            
            <div class="stat-card">
                <div class="stat-numero" style="color: var(--primary-color);"><?php echo $stats['total_usuarios']; ?></div>
                <div>Total Usuarios</div>
            </div>
            <div class="stat-card">
                <div class="stat-numero" style="color: var(--warning-color);"><?php echo $stats['usuarios_activos']; ?></div>
                <div>Usuarios Activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-numero" style="color: var(--danger-color);"><?php echo $stats['administradores']; ?></div>
                <div>Administradores</div>
            </div>
            <div class="stat-card">
                <div class="stat-numero" style="color: var(--warning-color);"><?php echo $stats['vendedores']; ?></div>
                <div>Vendedores</div>
            </div>
        </div>

        <!-- Navegación por pestañas -->
        <div class="nav-tabs">
            <div class="nav-tab active" onclick="mostrarTab('tab-lista')">📋 Lista de Usuarios</div>
            <div class="nav-tab" onclick="mostrarTab('tab-crear')">🆕 Crear Usuario</div>
            <div class="nav-tab" onclick="mostrarTab('tab-password')">🔐 Cambiar Contraseña</div>
            <div class="nav-tab" onclick="mostrarTab('tab-editar')">✏️ Editar Usuario</div>
            <div class="nav-tab" onclick="mostrarTab('tab-estado')">⚙️ Activar/Desactivar</div>
        </div>

        <!-- Pestaña 1: Lista de Usuarios -->
        <div id="tab-lista" class="tab-content active">
            <div class="seccion">
                <h2>📋 Lista de Usuarios del Sistema</h2>
                <?php
                $sql = "SELECT id, username, nombre, apellido, email, rol, activo, fecha_creacion, ultimo_login 
                        FROM usuarios 
                        ORDER BY activo DESC, fecha_creacion DESC";
                $result = $conexion->query($sql);
                
                if ($result->num_rows > 0) {
                    echo "<table>";
                    echo "<tr>
                            <th>Usuario</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Fecha Creación</th>
                            <th>Último Login</th>
                          </tr>";
                    while($row = $result->fetch_assoc()) {
                        $clase = $row['activo'] == 0 ? 'inactivo' : '';
                        $estado = $row['activo'] == 0 ? '❌ INACTIVO' : '✅ ACTIVO';
                        $ultimo_login = $row['ultimo_login'] ? $row['ultimo_login'] : 'Nunca';
                        
                        echo "<tr class='$clase'>";
                        echo "<td><strong>{$row['username']}</strong></td>";
                        echo "<td>{$row['nombre']} {$row['apellido']}</td>";
                        echo "<td>{$row['email']}</td>";
                        echo "<td>{$row['rol']}</td>";
                        echo "<td>{$estado}</td>";
                        echo "<td>{$row['fecha_creacion']}</td>";
                        echo "<td>{$ultimo_login}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p style='text-align: center; padding: 20px; color: var(--text-light);'>No hay usuarios registrados.</p>";
                }
                ?>
            </div>
        </div>

        <!-- Pestaña 2: Crear Usuario -->
        <div id="tab-crear" class="tab-content">
            <div class="seccion">
                <h2>🆕 Crear Nuevo Usuario</h2>
                <form method="POST">
                    <div class="form-group">
                        <input type="text" name="username" placeholder="Nombre de usuario" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Contraseña" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="nombre" placeholder="Nombre" required>
                        <input type="text" name="apellido" placeholder="Apellido" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <select name="rol" required>
                            <option value="">Seleccionar Rol</option>
                            <option value="administrador">Administrador</option>
                            <option value="vendedor">Vendedor</option>
                        </select>
                    </div>
                    <button type="submit" name="crear_usuario" class="btn btn-success">👤 Crear Usuario</button>
                </form>
            </div>
        </div>

        <!-- Pestaña 3: Cambiar Contraseña -->
        <div id="tab-password" class="tab-content">
            <div class="seccion">
                <h2>🔐 Cambiar Contraseña de Usuario</h2>
                <form method="POST">
                    <div class="form-group">
                        <select name="usuario_id_password" required>
                            <option value="">Seleccionar Usuario</option>
                            <?php
                            $sql = "SELECT id, username, nombre, apellido FROM usuarios ORDER BY username";
                            $result = $conexion->query($sql);
                            while($row = $result->fetch_assoc()) {
                                echo "<option value='{$row['id']}'>{$row['username']} - {$row['nombre']} {$row['apellido']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="password" name="nueva_password" placeholder="Nueva contraseña" required>
                    </div>
                    <button type="submit" name="cambiar_password" class="btn btn-info">🔐 Cambiar Contraseña</button>
                </form>
            </div>
        </div>

        <!-- Pestaña 4: Editar Usuario -->
        <div id="tab-editar" class="tab-content">
            <div class="seccion">
                <h2>✏️ Editar Información de Usuario</h2>
                <form method="POST">
                    <div class="form-group">
                        <select name="usuario_id_editar" id="usuario_editar" required onchange="cargarDatosUsuario()">
                            <option value="">Seleccionar Usuario a Editar</option>
                            <?php
                            $sql = "SELECT id, username, nombre, apellido, email, rol FROM usuarios ORDER BY username";
                            $result = $conexion->query($sql);
                            while($row = $result->fetch_assoc()) {
                                echo "<option value='{$row['id']}' data-nombre='{$row['nombre']}' data-apellido='{$row['apellido']}' data-email='{$row['email']}' data-rol='{$row['rol']}'>{$row['username']} - {$row['nombre']} {$row['apellido']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div id="datos_usuario" style="display: none;">
                        <div class="form-group">
                            <input type="text" name="nombre_editar" id="nombre_editar" placeholder="Nombre" required>
                            <input type="text" name="apellido_editar" id="apellido_editar" placeholder="Apellido" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email_editar" id="email_editar" placeholder="Email" required>
                        </div>
                        <div class="form-group">
                            <select name="rol_editar" id="rol_editar" required>
                                <option value="administrador">Administrador</option>
                                <option value="vendedor">Vendedor</option>
                            </select>
                        </div>
                        <button type="submit" name="editar_usuario" class="btn btn-warning">✏️ Actualizar Usuario</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Pestaña 5: Activar/Desactivar Usuario -->
        <div id="tab-estado" class="tab-content">
            <div class="seccion">
                <h2>⚙️ Activar/Desactivar Usuario</h2>
                <p><em>Los usuarios desactivados no pueden iniciar sesión en el sistema.</em></p>
                
                <form method="POST">
                    <div class="form-group">
                        <select name="usuario_id_toggle" required>
                            <option value="">Seleccionar Usuario</option>
                            <?php
                            $sql = "SELECT id, username, nombre, apellido, activo FROM usuarios ORDER BY username";
                            $result = $conexion->query($sql);
                            while($row = $result->fetch_assoc()) {
                                $estado = $row['activo'] == 1 ? ' (ACTIVO)' : ' (INACTIVO)';
                                echo "<option value='{$row['id']}'>{$row['username']} - {$row['nombre']} {$row['apellido']}{$estado}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="radio-group">
                        <label><strong>Acción:</strong></label>
                        <div class="radio-option">
                            <input type="radio" name="accion" value="activar" required> 
                            <span>✅ Activar Usuario</span>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="accion" value="desactivar" required> 
                            <span>❌ Desactivar Usuario</span>
                        </div>
                    </div>
                    
                    <button type="submit" name="toggle_activo" class="btn btn-warning">⚙️ Aplicar Cambio</button>
                </form>
            </div>
        </div>

        <!-- Navegación inferior -->
        <div class="nav-inferior">
            <a href="reportes.php" class="btn btn-info">📊 Reportes</a>
            <a href="gestion_inventario.php" class="btn btn-success">📦 Inventario</a>
            <a href="reportes_avanzados.php" class="btn btn-purple">📅 Reportes Avanzados</a>
            <a href="ventas.php" class="btn btn-primary">🛒 Ventas</a>
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

        function cargarDatosUsuario() {
            const select = document.getElementById('usuario_editar');
            const selectedOption = select.options[select.selectedIndex];
            const datosDiv = document.getElementById('datos_usuario');
            
            if (select.value) {
                // Cargar datos en los campos
                document.getElementById('nombre_editar').value = selectedOption.getAttribute('data-nombre');
                document.getElementById('apellido_editar').value = selectedOption.getAttribute('data-apellido');
                document.getElementById('email_editar').value = selectedOption.getAttribute('data-email');
                document.getElementById('rol_editar').value = selectedOption.getAttribute('data-rol');
                
                // Mostrar el formulario
                datosDiv.style.display = 'block';
            } else {
                datosDiv.style.display = 'none';
            }
        }
    </script>

    <?php $conexion->close(); ?>
</body>
</html>