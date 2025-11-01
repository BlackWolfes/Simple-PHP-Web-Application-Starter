<?php
/**
 * Página de Perfil de Usuario
 * Permite a los usuarios ver y editar su información personal
 * Incluye validación de datos y protección CSRF
 * 
 * Autor: Sistema de optimización
 * Fecha: 2024
 */

// Cargar configuración principal (ya incluye todos los archivos necesarios)
require_once 'config/config.php';

// === VERIFICACIÓN DE AUTENTICACIÓN ===
// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar timeout de sesión
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > SESSION_TIMEOUT)) {
    session_destroy();
    header("Location: login.php?expired=1");
    exit();
}

// Obtener ID del usuario de la sesión
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

try {
    // === OBTENER DATOS ACTUALES DEL USUARIO ===
    $stmt = $conn->prepare("SELECT fullname, email, phone, bio, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Usuario no encontrado, destruir sesión
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();

} catch (Exception $e) {
    error_log("Error obteniendo datos de usuario $user_id: " . $e->getMessage());
    $error = "Error al cargar los datos del perfil. Por favor, inténtelo más tarde.";
}

// === PROCESAMIENTO DEL FORMULARIO DE ACTUALIZACIÓN ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($error)) {
    // Verificar token CSRF
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("Error de validación CSRF. Por favor, recargue la página e inténtelo de nuevo.");
    }

    // Sanitizar y validar datos de entrada
    $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_STRING);

    // === VALIDACIONES ===
    if (empty($fullname)) {
        $error = "El nombre completo no puede estar vacío.";
    } elseif (strlen($fullname) < 2) {
        $error = "El nombre completo debe tener al menos 2 caracteres.";
    } elseif (strlen($fullname) > 100) {
        $error = "El nombre completo no puede exceder 100 caracteres.";
    } elseif (!empty($phone) && !preg_match('/^[\d\s\-\+\(\)]{7,20}$/', $phone)) {
        $error = "Formato de teléfono inválido.";
    } elseif (strlen($bio) > 500) {
        $error = "La biografía no puede exceder 500 caracteres.";
    } else {
        try {
            // === ACTUALIZAR DATOS DEL USUARIO ===
            $update_stmt = $conn->prepare("UPDATE users SET fullname = ?, phone = ?, bio = ?, updated_at = NOW() WHERE id = ?");
            $update_stmt->bind_param("sssi", $fullname, $phone, $bio, $user_id);
            
            if ($update_stmt->execute()) {
                $success = "Perfil actualizado exitosamente.";
                
                // Actualizar datos locales para mostrar los cambios
                $user['fullname'] = $fullname;
                $user['phone'] = $phone;
                $user['bio'] = $bio;
                
                // Actualizar nombre en la sesión
                $_SESSION['user_name'] = $fullname;
                
                // Registrar actividad
                log_activity($user_id, "Perfil actualizado");
                
            } else {
                $error = "Error al actualizar el perfil. Por favor, inténtelo de nuevo.";
                error_log("Error actualizando perfil usuario $user_id: " . $update_stmt->error);
            }
            
            $update_stmt->close();
            
        } catch (Exception $e) {
            $error = "Error interno del servidor. Por favor, inténtelo más tarde.";
            error_log("Excepción actualizando perfil usuario $user_id: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="Página de perfil personal en <?php echo htmlspecialchars(APP_NAME); ?>">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    <div class="container">
        <!-- Encabezado de la página -->
        <header class="profile-header">
            <h1>Mi Perfil</h1>
            <p class="profile-subtitle">Gestiona tu información personal</p>
        </header>

        <!-- Navegación de regreso -->
        <nav class="breadcrumb">
            <a href="dashboard.php">← Volver al Panel de Control</a>
        </nav>

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

        <!-- Información de la cuenta -->
        <section class="account-info">
            <h2>Información de la Cuenta</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>Correo Electrónico:</label>
                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-item">
                    <label>Miembro desde:</label>
                    <span><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>
        </section>

        <!-- Formulario de edición de perfil -->
        <section class="profile-form-section">
            <h2>Editar Información Personal</h2>
            
            <form method="POST" action="" class="profile-form">
                <!-- Token CSRF para seguridad -->
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <!-- Campo de nombre completo -->
                <div class="form-group">
                    <label for="fullname">Nombre Completo: <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="fullname" 
                        name="fullname" 
                        value="<?php echo htmlspecialchars($user['fullname']); ?>" 
                        required 
                        autocomplete="name"
                        placeholder="Ingrese su nombre completo"
                        maxlength="100"
                        minlength="2"
                    >
                    <small class="form-help">Mínimo 2 caracteres, máximo 100</small>
                </div>
                
                <!-- Campo de email (solo lectura) -->
                <div class="form-group">
                    <label for="email">Correo Electrónico:</label>
                    <input 
                        type="email" 
                        id="email" 
                        value="<?php echo htmlspecialchars($user['email']); ?>" 
                        disabled
                        class="disabled-field"
                    >
                    <small class="form-help">El email no se puede modificar</small>
                </div>
                
                <!-- Campo de teléfono -->
                <div class="form-group">
                    <label for="phone">Teléfono:</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                        autocomplete="tel"
                        placeholder="Ej: +52 55 1234 5678"
                        pattern="[\d\s\-\+\(\)]{7,20}"
                    >
                    <small class="form-help">Formato: números, espacios, guiones, paréntesis y signo +</small>
                </div>
                
                <!-- Campo de biografía -->
                <div class="form-group">
                    <label for="bio">Biografía:</label>
                    <textarea 
                        id="bio" 
                        name="bio" 
                        rows="4" 
                        placeholder="Cuéntanos un poco sobre ti..."
                        maxlength="500"
                    ><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    <small class="form-help">
                        <span id="bio-counter">0</span>/500 caracteres
                    </small>
                </div>
                
                <!-- Botón de envío -->
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Actualizar Perfil</button>
                    <button type="reset" class="btn-secondary">Restablecer</button>
                </div>
            </form>
        </section>

        <!-- Enlaces adicionales -->
        <section class="additional-actions">
            <h2>Acciones Adicionales</h2>
            <div class="actions-list">
                <a href="reset-password.php" class="action-link">
                    🔒 Cambiar Contraseña
                </a>
                <a href="dashboard.php" class="action-link">
                    📊 Ir al Panel de Control
                </a>
                <a href="#" class="action-link" onclick="alert('Funcionalidad en desarrollo')">
                    🗑️ Eliminar Cuenta
                </a>
            </div>
        </section>
    </div>

    <!-- Script para funcionalidades del perfil -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Contador de caracteres para la biografía
            const bioTextarea = document.getElementById('bio');
            const bioCounter = document.getElementById('bio-counter');
            
            function updateBioCounter() {
                const currentLength = bioTextarea.value.length;
                bioCounter.textContent = currentLength;
                
                // Cambiar color si se acerca al límite
                if (currentLength > 450) {
                    bioCounter.style.color = '#e74c3c';
                } else if (currentLength > 400) {
                    bioCounter.style.color = '#f39c12';
                } else {
                    bioCounter.style.color = '#7f8c8d';
                }
            }
            
            // Inicializar contador
            updateBioCounter();
            
            // Actualizar contador en tiempo real
            bioTextarea.addEventListener('input', updateBioCounter);
            
            // Validación del formulario
            const form = document.querySelector('.profile-form');
            form.addEventListener('submit', function(e) {
                const fullname = document.getElementById('fullname').value.trim();
                
                if (fullname.length < 2) {
                    alert('El nombre completo debe tener al menos 2 caracteres.');
                    e.preventDefault();
                    return;
                }
                
                if (fullname.length > 100) {
                    alert('El nombre completo no puede exceder 100 caracteres.');
                    e.preventDefault();
                    return;
                }
            });
            
            // Enfocar el primer campo editable
            document.getElementById('fullname').focus();
        });
    </script>
</body>
</html>