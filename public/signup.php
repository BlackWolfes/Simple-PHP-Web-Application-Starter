<?php
/**
 * Página de registro de usuarios (Sign Up)
 * Permite a nuevos usuarios crear una cuenta en la aplicación
 * Incluye validación de datos, protección CSRF y verificación por email
 * 
 * Autor: Sistema de optimización
 * Fecha: 2024
 */

// Cargar configuración principal (ya incluye todos los archivos necesarios)
require_once 'config/config.php';

// Inicializar variables de estado
$error = '';
$success = '';

// === PROCESAMIENTO DEL FORMULARIO DE REGISTRO ===
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar token CSRF para prevenir ataques
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("Error de validación CSRF. Por favor, recargue la página e inténtelo de nuevo.");
    }

    // Sanitizar y obtener datos del formulario
    $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // === VALIDACIONES DE ENTRADA ===
    if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Por favor, complete todos los campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato de email inválido.";
    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $error = "La contraseña debe contener al menos una letra minúscula, una mayúscula y un número.";
    } elseif (strlen($fullname) < 2) {
        $error = "El nombre completo debe tener al menos 2 caracteres.";
    } else {
        // === VERIFICAR SI EL EMAIL YA EXISTE ===
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Ya existe una cuenta con este email.";
            $stmt->close();
        } else {
            $stmt->close();
            
            // === CREAR NUEVA CUENTA DE USUARIO ===
            try {
                // Generar hash seguro de la contraseña con pepper
                $hashed_password = password_hash($password . PASSWORD_PEPPER, PASSWORD_DEFAULT);
                
                // Generar código de verificación único
                $verification_code = bin2hex(random_bytes(32));
                
                // Insertar nuevo usuario en la base de datos
                $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, verification_code, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssss", $fullname, $email, $hashed_password, $verification_code);

                if ($stmt->execute()) {
                    $user_id = $stmt->insert_id;
                    
                    // === ENVIAR EMAIL DE VERIFICACIÓN ===
                    $to = $email;
                    $subject = "Verificar Cuenta - " . APP_NAME;
                    $message = "Hola $fullname,\n\n";
                    $message .= "Gracias por registrarte en " . APP_NAME . ".\n\n";
                    $message .= "Por favor, haz clic en el siguiente enlace para verificar tu cuenta:\n\n";
                    $message .= get_base_url() . "/verify.php?code=$verification_code&email=" . urlencode($email) . "\n\n";
                    $message .= "Si no creaste esta cuenta, puedes ignorar este email.\n\n";
                    $message .= "Saludos,\nEquipo de " . APP_NAME;
                    
                    $headers = "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n";
                    $headers .= "Reply-To: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n";
                    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                    // Intentar enviar el email
                    if (mail($to, $subject, $message, $headers)) {
                        $success = "Registro exitoso. Por favor, revisa tu email para verificar tu cuenta.";
                        
                        // Registrar actividad de registro exitoso
                        log_activity($user_id, "Registro de usuario exitoso - Email: $email");
                    } else {
                        $success = "Registro exitoso, pero no se pudo enviar el email de verificación. Por favor, contacta al soporte.";
                        
                        // Registrar problema con el email
                        error_log("Error enviando email de verificación para: $email");
                    }
                } else {
                    $error = "Error al crear la cuenta. Por favor, inténtelo de nuevo.";
                    error_log("Error en registro de usuario: " . $stmt->error);
                }
                
                $stmt->close();
                
            } catch (Exception $e) {
                $error = "Error interno del servidor. Por favor, inténtelo más tarde.";
                error_log("Excepción en registro: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="Página de registro para crear una cuenta en <?php echo htmlspecialchars(APP_NAME); ?>">
</head>
<body>
    <div class="container">
        <!-- Encabezado de la página -->
        <header>
            <h1>Registrarse en <?php echo htmlspecialchars(APP_NAME); ?></h1>
        </header>

        <!-- Mostrar mensajes de estado -->
        <?php if ($error): ?>
            <div class="error-message">
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <p class="success"><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <!-- Formulario de registro -->
        <?php if (!$success): ?>
        <form method="POST" action="" class="signup-form">
            <!-- Token CSRF para seguridad -->
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <!-- Campo de nombre completo -->
            <div class="form-group">
                <label for="fullname">Nombre Completo:</label>
                <input 
                    type="text" 
                    id="fullname" 
                    name="fullname" 
                    required 
                    autocomplete="name"
                    placeholder="Ingrese su nombre completo"
                    value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>"
                    minlength="2"
                >
            </div>
            
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
                    autocomplete="new-password"
                    placeholder="Mínimo 8 caracteres"
                    minlength="8"
                >
                <small class="form-help">
                    La contraseña debe tener al menos 8 caracteres, incluyendo una letra minúscula, una mayúscula y un número.
                </small>
            </div>
            
            <!-- Campo de confirmación de contraseña -->
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña:</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    required 
                    autocomplete="new-password"
                    placeholder="Repita su contraseña"
                    minlength="8"
                >
            </div>
            
            <!-- Botón de envío -->
            <button type="submit" class="btn-primary">Crear Cuenta</button>
        </form>
        <?php endif; ?>

        <!-- Enlaces adicionales -->
        <div class="additional-links">
            <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
        </div>
    </div>

    <!-- Script para validación del lado del cliente -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.signup-form');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            // Validar que las contraseñas coincidan
            function validatePasswords() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Las contraseñas no coinciden');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            // Agregar eventos de validación
            if (password && confirmPassword) {
                password.addEventListener('input', validatePasswords);
                confirmPassword.addEventListener('input', validatePasswords);
            }
            
            // Enfocar el primer campo al cargar
            const firstField = document.getElementById('fullname');
            if (firstField && !firstField.value) {
                firstField.focus();
            }
        });
    </script>
</body>
</html>