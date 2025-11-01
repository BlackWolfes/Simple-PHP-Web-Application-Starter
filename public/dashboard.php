<?php
/**
 * Panel de Control (Dashboard)
 * Página principal del usuario autenticado que muestra estadísticas y actividad reciente
 * Incluye verificación de sesión y datos del usuario
 * 
 * Autor: Sistema de optimización
 * Fecha: 2024
 */

// Cargar configuración principal (ya incluye todos los archivos necesarios)
require_once 'config/config.php';

// === VERIFICACIÓN DE AUTENTICACIÓN ===
// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    // Redirigir a login si no está autenticado
    header("Location: login.php");
    exit();
}

// Verificar timeout de sesión
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > SESSION_TIMEOUT)) {
    // Sesión expirada, destruir y redirigir
    session_destroy();
    header("Location: login.php?expired=1");
    exit();
}

// Obtener ID del usuario de la sesión
$user_id = $_SESSION['user_id'];

try {
    // === OBTENER DATOS DEL USUARIO ===
    $stmt = $conn->prepare("SELECT fullname, email, created_at FROM users WHERE id = ?");
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

    // === OBTENER ESTADÍSTICAS DEL USUARIO ===
    $total_projects = get_user_project_count($conn, $user_id);
    $completed_tasks = get_user_completed_task_count($conn, $user_id);
    $pending_tasks = get_user_pending_task_count($conn, $user_id);
    $total_tasks = $completed_tasks + $pending_tasks;

    // === OBTENER ACTIVIDAD RECIENTE ===
    $recent_activity = get_user_recent_activity($conn, $user_id, 10);

    // Registrar acceso al dashboard
    log_activity($user_id, "Acceso al dashboard");

} catch (Exception $e) {
    // Error al obtener datos, registrar y mostrar mensaje genérico
    error_log("Error en dashboard para usuario $user_id: " . $e->getMessage());
    $error_message = "Error al cargar los datos del dashboard. Por favor, inténtelo más tarde.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="Panel de control personal en <?php echo htmlspecialchars(APP_NAME); ?>">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    <div class="dashboard-container">
        <!-- Encabezado con navegación -->
        <header class="dashboard-header">
            <div class="header-content">
                <h1>Bienvenido, <?php echo htmlspecialchars($user['fullname']); ?></h1>
                <p class="user-info">
                    Miembro desde: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                </p>
            </div>
            
            <!-- Navegación principal -->
            <nav class="main-nav">
                <ul>
                    <li><a href="dashboard.php" class="active">Panel de Control</a></li>
                    <li><a href="profile.php">Mi Perfil</a></li>
                    <li><a href="logout.php" class="logout-link">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </header>

        <!-- Contenido principal -->
        <main class="dashboard-main">
            <!-- Mostrar error si existe -->
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php else: ?>
                
                <!-- Sección de estadísticas -->
                <section class="stats-section">
                    <h2>Tus Estadísticas</h2>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-icon">📊</div>
                            <h3>Proyectos Totales</h3>
                            <p class="stat-number"><?php echo number_format($total_projects); ?></p>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">✅</div>
                            <h3>Tareas Completadas</h3>
                            <p class="stat-number"><?php echo number_format($completed_tasks); ?></p>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">⏳</div>
                            <h3>Tareas Pendientes</h3>
                            <p class="stat-number"><?php echo number_format($pending_tasks); ?></p>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">📋</div>
                            <h3>Total de Tareas</h3>
                            <p class="stat-number"><?php echo number_format($total_tasks); ?></p>
                        </div>
                    </div>
                </section>

                <!-- Sección de actividad reciente -->
                <section class="activity-section">
                    <h2>Actividad Reciente</h2>
                    
                    <?php if (empty($recent_activity)): ?>
                        <div class="no-activity">
                            <p>No hay actividad reciente para mostrar.</p>
                            <p><em>Comienza creando tu primer proyecto o tarea.</em></p>
                        </div>
                    <?php else: ?>
                        <div class="activity-list">
                            <?php foreach ($recent_activity as $activity): ?>
                                <div class="activity-item">
                                    <span class="activity-date">
                                        <?php echo htmlspecialchars($activity['date']); ?>
                                    </span>
                                    <span class="activity-description">
                                        <?php echo htmlspecialchars($activity['description']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Sección de acciones rápidas -->
                <section class="quick-actions">
                    <h2>Acciones Rápidas</h2>
                    <div class="actions-grid">
                        <a href="profile.php" class="action-button">
                            <span class="action-icon">👤</span>
                            <span class="action-text">Editar Perfil</span>
                        </a>
                        
                        <a href="#" class="action-button" onclick="alert('Funcionalidad en desarrollo')">
                            <span class="action-icon">➕</span>
                            <span class="action-text">Nuevo Proyecto</span>
                        </a>
                        
                        <a href="#" class="action-button" onclick="alert('Funcionalidad en desarrollo')">
                            <span class="action-icon">📝</span>
                            <span class="action-text">Nueva Tarea</span>
                        </a>
                        
                        <a href="#" class="action-button" onclick="alert('Funcionalidad en desarrollo')">
                            <span class="action-icon">📊</span>
                            <span class="action-text">Ver Reportes</span>
                        </a>
                    </div>
                </section>

            <?php endif; ?>
        </main>

        <!-- Pie de página -->
        <footer class="dashboard-footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(APP_NAME); ?>. Todos los derechos reservados.</p>
            <p>Última actualización de sesión: <?php echo date('d/m/Y H:i:s'); ?></p>
        </footer>
    </div>

    <!-- Script para funcionalidades del dashboard -->
    <script>
        // Actualizar automáticamente la hora cada minuto
        setInterval(function() {
            const now = new Date();
            const timeString = now.toLocaleString('es-ES');
            document.querySelector('.dashboard-footer p:last-child').textContent = 
                'Última actualización de sesión: ' + timeString;
        }, 60000);

        // Confirmar antes de cerrar sesión
        document.querySelector('.logout-link').addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>