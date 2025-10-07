<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.html");
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
<html>
<head>
    <title>Gestión de Usuarios - Sistema de Focos</title>
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
        .seccion { 
            background: #f8f9fa; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 10px;
            border-left: 4px solid #4CAF50;
        }
        .seccion-info {
            border-left: 4px solid #17a2b8;
        }
        h1, h2, h3 {
            color: #333;
        }
        input, select { 
            padding: 10px; 
            margin: 5px; 
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 200px;
        }
        button { 
            background: #28a745; 
            color: white; 
            padding: 12px 20px; 
            border: none; 
            cursor: pointer; 
            border-radius: 5px; 
            margin: 5px;
            font-size: 14px;
        }
        button:hover {
            opacity: 0.9;
        }
        .btn-danger { background: #dc3545; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-info { background: #17a2b8; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
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
        .inactivo { 
            background-color: #f8d7da; 
            color: #721c24;
        }
        .nav-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .nav-tab {
            padding: 10px 20px;
            background: #e9ecef;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
            cursor: pointer;
        }
        .nav-tab.active {
            background: #4CAF50;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .usuario-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>👥 Gestión de Usuarios - Sistema de Focos</h1>
        <p>Bienvenido Administrador, <strong><?php echo $_SESSION['nombre']; ?></strong></p>
        
        <?php if (!empty($mensaje)): ?>
            <div style='padding: 15px; background: #d4edda; color: #155724; border-radius: 5px; margin: 15px 0; border: 1px solid #c3e6cb;'>
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
                <div style="font-size: 24px; font-weight: bold; color: #4CAF50;"><?php echo $stats['total_usuarios']; ?></div>
                <div>Total Usuarios</div>
            </div>
            <div class="stat-card">
                <div style="font-size: 24px; font-weight: bold; color: #28a745;"><?php echo $stats['usuarios_activos']; ?></div>
                <div>Usuarios Activos</div>
            </div>
            <div class="stat-card">
                <div style="font-size: 24px; font-weight: bold; color: #dc3545;"><?php echo $stats['administradores']; ?></div>
                <div>Administradores</div>
            </div>
            <div class="stat-card">
                <div style="font-size: 24px; font-weight: bold; color: #ffc107;"><?php echo $stats['vendedores']; ?></div>
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
                    echo "<p>No hay usuarios registrados.</p>";
                }
                ?>
            </div>
        </div>

        <!-- Pestaña 2: Crear Usuario -->
        <div id="tab-crear" class="tab-content">
            <div class="seccion">
                <h2>🆕 Crear Nuevo Usuario</h2>
                <form method="POST">
                    <input type="text" name="username" placeholder="Nombre de usuario" required style="width: 300px;"><br>
                    <input type="password" name="password" placeholder="Contraseña" required><br>
                    <input type="text" name="nombre" placeholder="Nombre" required>
                    <input type="text" name="apellido" placeholder="Apellido" required><br>
                    <input type="email" name="email" placeholder="Email" style="width: 300px;"><br>
                    <select name="rol" required>
                        <option value="">Seleccionar Rol</option>
                        <option value="administrador">Administrador</option>
                        <option value="vendedor">Vendedor</option>
                    </select><br>
                    <button type="submit" name="crear_usuario">👤 Crear Usuario</button>
                </form>
            </div>
        </div>

        <!-- Pestaña 3: Cambiar Contraseña -->
        <div id="tab-password" class="tab-content">
            <div class="seccion">
                <h2>🔐 Cambiar Contraseña de Usuario</h2>
                <form method="POST">
                    <select name="usuario_id_password" required style="width: 300px;">
                        <option value="">Seleccionar Usuario</option>
                        <?php
                        $sql = "SELECT id, username, nombre, apellido FROM usuarios ORDER BY username";
                        $result = $conexion->query($sql);
                        while($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['username']} - {$row['nombre']} {$row['apellido']}</option>";
                        }
                        ?>
                    </select><br>
                    <input type="password" name="nueva_password" placeholder="Nueva contraseña" required>
                    <button type="submit" name="cambiar_password" class="btn-info">🔐 Cambiar Contraseña</button>
                </form>
            </div>
        </div>

        <!-- Pestaña 4: Editar Usuario -->
        <div id="tab-editar" class="tab-content">
            <div class="seccion seccion-info">
                <h2>✏️ Editar Información de Usuario</h2>
                <form method="POST">
                    <select name="usuario_id_editar" id="usuario_editar" required style="width: 300px;" onchange="cargarDatosUsuario()">
                        <option value="">Seleccionar Usuario a Editar</option>
                        <?php
                        $sql = "SELECT id, username, nombre, apellido, email, rol FROM usuarios ORDER BY username";
                        $result = $conexion->query($sql);
                        while($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id']}' data-nombre='{$row['nombre']}' data-apellido='{$row['apellido']}' data-email='{$row['email']}' data-rol='{$row['rol']}'>{$row['username']} - {$row['nombre']} {$row['apellido']}</option>";
                        }
                        ?>
                    </select><br>
                    
                    <div id="datos_usuario" style="display: none;">
                        <input type="text" name="nombre_editar" id="nombre_editar" placeholder="Nombre" required>
                        <input type="text" name="apellido_editar" id="apellido_editar" placeholder="Apellido" required><br>
                        <input type="email" name="email_editar" id="email_editar" placeholder="Email" style="width: 300px;"><br>
                        <select name="rol_editar" id="rol_editar" required>
                            <option value="administrador">Administrador</option>
                            <option value="vendedor">Vendedor</option>
                        </select><br>
                        <button type="submit" name="editar_usuario" class="btn-warning">✏️ Actualizar Usuario</button>
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
                    <select name="usuario_id_toggle" required style="width: 300px;">
                        <option value="">Seleccionar Usuario</option>
                        <?php
                        $sql = "SELECT id, username, nombre, apellido, activo FROM usuarios ORDER BY username";
                        $result = $conexion->query($sql);
                        while($row = $result->fetch_assoc()) {
                            $estado = $row['activo'] == 1 ? ' (ACTIVO)' : ' (INACTIVO)';
                            echo "<option value='{$row['id']}'>{$row['username']} - {$row['nombre']} {$row['apellido']}{$estado}</option>";
                        }
                        ?>
                    </select><br>
                    
                    <div style="margin: 15px 0;">
                        <label><strong>Acción:</strong></label><br>
                        <input type="radio" name="accion" value="activar" required> ✅ Activar Usuario<br>
                        <input type="radio" name="accion" value="desactivar" required> ❌ Desactivar Usuario
                    </div>
                    
                    <button type="submit" name="toggle_activo" class="btn-warning">⚙️ Aplicar Cambio</button>
                </form>
            </div>
        </div>

        <!-- Navegación inferior -->
        <div style="margin-top: 30px; text-align: center;">
            <a href="reportes.php" style="background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;">📊 Reportes</a>
            <a href="gestion_inventario.php" style="background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;">📦 Inventario</a>
            <a href="reportes_avanzados.php" style="background: #6f42c1; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;">📅 Reportes Avanzados</a>
            <a href="ventas.php" style="background: #ffc107; color: black; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;">🛒 Ventas</a>
            <a href="logout.php" style="background: #dc3545; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;">🚪 Cerrar Sesión</a>
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