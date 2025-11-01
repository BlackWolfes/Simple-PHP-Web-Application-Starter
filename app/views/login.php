<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
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
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
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
                    value="<?php echo $email; ?>"
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
    <script src="assets/js/main.js"></script>
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