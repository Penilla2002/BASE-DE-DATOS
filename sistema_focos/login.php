<?php
session_start();
include 'conexion.php';

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Buscar usuario en la base de datos
    $sql = "SELECT id, username, nombre, rol FROM usuarios 
            WHERE username = ? AND activo = 1";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        
        // POR AHORA - Verificación simple (luego usaremos password_hash)
        if (($username == "admin" && $password == "admin123") || 
            ($username == "vendedor1" && $password == "admin123")) {
            
            // Iniciar sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['username'] = $usuario['username'];
            $_SESSION['rol'] = $usuario['rol'];
            $_SESSION['nombre'] = $usuario['nombre'];
            
            // Redirigir según el rol
            if ($usuario['rol'] == 'administrador') {
                header("Location: reportes.php");
            } else {
                header("Location: ventas.php");
            }
            exit();
        } else {
            $error = "Contraseña incorrecta";
        }
    } else {
        $error = "Usuario no encontrado";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Punto de Venta</title>
    <style>
        /* ===== VARIABLES DE COLOR AMARILLO/DORADO ===== */
        :root {
            --primary-color: #f59e0b;
            --primary-dark: #d97706;
            --secondary-color: #fbbf24;
            --gradient-gold: linear-gradient(135deg, #fcd34d 0%, #f59e0b 50%, #d97706 100%);
            --gradient-sunshine: linear-gradient(135deg, #fef3c7 0%, #fcd34d 50%, #f59e0b 100%);
            --light-bg: #fffbeb;
            --card-bg: #ffffff;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --border-color: #fde68a;
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 15px;
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--border-color);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: var(--primary-dark);
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: var(--text-light);
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: var(--light-bg);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--gradient-gold);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #fecaca;
        }

        .demo-accounts {
            margin-top: 25px;
            padding: 15px;
            background: var(--light-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .demo-accounts h3 {
            color: var(--primary-dark);
            font-size: 14px;
            margin-bottom: 10px;
            text-align: center;
        }

        .demo-account {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .demo-account:last-child {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Iniciar Sesión</h1>
            <p>Sistema Punto de Venta - Focos LED</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required 
                       placeholder="Ingresa tu usuario">
            </div>

            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Ingresa tu contraseña">
            </div>

            <button type="submit" class="btn-login">Ingresar al Sistema</button>
        </form>
    </div>
</body>
</html>