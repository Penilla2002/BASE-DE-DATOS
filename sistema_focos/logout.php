<?php
session_start();

// Verificar si hay sesión activa para mostrar mensaje personalizado
$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';

// Destruir la sesión
session_destroy();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrando Sesión - Sistema de Focos LED</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .logout-container {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 15px;
            box-shadow: var(--shadow-lg);
            text-align: center;
            max-width: 500px;
            width: 100%;
            border: 1px solid var(--border-color);
            animation: fadeInUp 0.8s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logout-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .logout-title {
            color: var(--primary-dark);
            font-size: 32px;
            margin-bottom: 15px;
        }

        .logout-message {
            color: var(--text-light);
            font-size: 18px;
            margin-bottom: 25px;
        }

        .user-name {
            color: var(--primary-color);
            font-weight: bold;
        }

        .countdown {
            font-size: 48px;
            font-weight: bold;
            color: var(--primary-dark);
            margin: 20px 0;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--light-bg);
            border-radius: 4px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress {
            width: 100%;
            height: 100%;
            background: var(--gradient-gold);
            border-radius: 4px;
            animation: progressAnimation 3s linear forwards;
        }

        @keyframes progressAnimation {
            0% { width: 100%; }
            100% { width: 0%; }
        }

        .btn-login {
            display: inline-block;
            padding: 12px 25px;
            background: var(--gradient-gold);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
        }

        .footer {
            margin-top: 25px;
            color: var(--text-light);
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">👋</div>
        <h1 class="logout-title">¡Hasta Pronto!</h1>
        
        <p class="logout-message">
            Sesión cerrada correctamente para <span class="user-name"><?php echo htmlspecialchars($nombre_usuario); ?></span>
        </p>

        <div class="countdown" id="countdown">3</div>
        
        <div class="progress-bar">
            <div class="progress"></div>
        </div>

        <p style="color: var(--text-light); margin-bottom: 15px;">
            Redirigiendo al login...
        </p>

        <a href="login.php" class="btn-login">↻ Volver al Login</a>

        <div class="footer">
            Sistema de Focos LED • <?php echo date('Y'); ?>
        </div>
    </div>

    <script>
        // Contador regresivo
        let countdown = 3;
        const countdownElement = document.getElementById('countdown');
        
        const interval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(interval);
                window.location.href = 'login.php';
            }
        }, 1000);

        // Permitir saltar la espera haciendo clic en cualquier parte
        document.body.addEventListener('click', function() {
            window.location.href = 'login.php';
        });
    </script>
</body>
</html>