<?php
/**
 * Página de inicio de sesión (Login)
 * Permite a los usuarios autenticarse en la aplicación
 * Incluye protección CSRF y limitación de intentos
 * 
 * Autor: Sistema de optimización
 * Fecha: 2024
 */

// Cargar configuración principal (ya incluye todos los archivos necesarios)
require_once 'config/config.php';

// Inicializar variable de error
$error = '';

// === PROCESAMIENTO DEL FORMULARIO DE LOGIN ===
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar token CSRF para prevenir ataques
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("Error de validación CSRF. Por favor, recargue la página e inténtelo de nuevo.");
    }

    // Verificar límite de intentos de login para prevenir ataques de fuerza bruta
    if (!check_rate_limit($_SERVER['REMOTE_ADDR'], 'login', MAX_LOGIN_ATTEMPTS, LOGIN_TIMEOUT)) {
        die("Demasiados intentos de inicio de sesión. Por favor, inténtelo más tarde.");
    }

    // Sanitizar y validar datos de entrada
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    // Validar que los campos no estén vacíos
    if (empty($email) || empty($password)) {
        $error = "Por favor, complete todos los campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato de email inválido.";
    } else {
        // Intentar autenticar al usuario
        $user = attempt_login($conn, $email, $password);
        
        if ($user) {
            // Login exitoso: establecer variables de sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['fullname'];
            $_SESSION['login_time'] = time();
            
            // Registrar actividad de login exitoso
            log_activity($user['id'], "Login exitoso desde IP: " . $_SERVER['REMOTE_ADDR']);
            
            // Resetear contador de intentos fallidos
            reset_login_attempts($_SERVER['REMOTE_ADDR']);
            
            // Redirigir al dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            // Login fallido: mostrar error e incrementar contador
            $error = "Email o contraseña incorrectos.";
            increment_login_attempts($_SERVER['REMOTE_ADDR']);
            
            // Registrar intento de login fallido
            error_log("Intento de login fallido para email: $email desde IP: " . $_SERVER['REMOTE_ADDR']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="Página de inicio de sesión para <?php echo htmlspecialchars(APP_NAME); ?>">
</head>
<body>
    <div class="container">
        <!-- Encabezado de la página -->
        <header>
            <h1>Iniciar Sesión en <?php echo htmlspecialchars(APP_NAME); ?></h1>
        </header>

        <!-- Mostrar mensajes de error si existen -->
        <?php if ($error): ?>
            <div class="error-message">
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <!-- Formulario de inicio de sesión -->
        <form method="POST" action="" class="login-form">
            <!-- Token CSRF para seguridad -->
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <!-- Campo de email -->
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    autocomplete="email"
                    placeholder="ejemplo@correo.com"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                >
            </div>
            
            <!-- Campo de contraseña -->
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                    placeholder="Ingrese su contraseña"
                >
            </div>
            
            <!-- Botón de envío -->
            <button type="submit" class="btn-primary">Iniciar Sesión</button>
        </form>

        <!-- Enlaces adicionales -->
        <div class="additional-links">
            <p>¿No tienes una cuenta? <a href="signup.php">Regístrate aquí</a></p>
            <p><a href="reset-password.php">¿Olvidaste tu contraseña?</a></p>
        </div>
    </div>

    <!-- Script para mejorar la experiencia del usuario -->
    <script>
        // Enfocar automáticamente el campo de email al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('email');
            if (emailField && !emailField.value) {
                emailField.focus();
            }
        });
    </script>
</body>
</html>