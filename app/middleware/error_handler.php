<?php
/**
 * Manejador de Errores y Sistema de Logging
 * Proporciona funcionalidades avanzadas para el manejo de errores y registro de actividades
 * 
 * Características:
 * - Manejo personalizado de errores PHP
 * - Sistema de logging estructurado
 * - Rotación automática de logs
 * - Diferentes niveles de logging
 * - Notificaciones de errores críticos
 * 
 * Autor: Sistema de optimización
 * Fecha: 2024
 */

// === CONFIGURACIÓN DE LOGGING ===
// Directorio base para logs
define('LOG_DIR', __DIR__ . '/../logs');

// Tamaño máximo de archivo de log en bytes (10MB)
define('MAX_LOG_SIZE', 10 * 1024 * 1024);

// Número máximo de archivos de log rotados a mantener
define('MAX_LOG_FILES', 5);

// Niveles de log
define('LOG_LEVEL_DEBUG', 1);
define('LOG_LEVEL_INFO', 2);
define('LOG_LEVEL_WARNING', 3);
define('LOG_LEVEL_ERROR', 4);
define('LOG_LEVEL_CRITICAL', 5);

// === FUNCIONES DE MANEJO DE ERRORES ===

/**
 * Manejador personalizado de errores PHP
 * Registra errores en archivos de log y maneja la visualización según el entorno
 * 
 * @param int $errno Nivel del error
 * @param string $errstr Mensaje del error
 * @param string $errfile Archivo donde ocurrió el error
 * @param int $errline Línea donde ocurrió el error
 * @return bool True para prevenir el manejador de errores por defecto
 */
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    // Mapear niveles de error PHP a nuestros niveles
    $error_levels = [
        E_ERROR => 'CRITICAL',
        E_WARNING => 'WARNING', 
        E_PARSE => 'CRITICAL',
        E_NOTICE => 'INFO',
        E_CORE_ERROR => 'CRITICAL',
        E_CORE_WARNING => 'WARNING',
        E_COMPILE_ERROR => 'CRITICAL',
        E_COMPILE_WARNING => 'WARNING',
        E_USER_ERROR => 'ERROR',
        E_USER_WARNING => 'WARNING',
        E_USER_NOTICE => 'INFO',
        E_STRICT => 'INFO',
        E_RECOVERABLE_ERROR => 'ERROR',
        E_DEPRECATED => 'INFO',
        E_USER_DEPRECATED => 'INFO'
    ];
    
    $level = $error_levels[$errno] ?? 'ERROR';
    
    // Crear mensaje de error estructurado
    $error_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => $level,
        'errno' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline,
        'user_id' => $_SESSION['user_id'] ?? 'anonymous',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ];
    
    // Registrar error en log
    log_error($error_data);
    
    // Manejar visualización del error
    if (is_development_mode()) {
        // En desarrollo, mostrar error detallado
        display_detailed_error($error_data);
    } else {
        // En producción, mostrar mensaje genérico
        display_generic_error($level);
    }
    
    // Para errores críticos, enviar notificación
    if (in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        notify_critical_error($error_data);
    }
    
    return true; // Prevenir el manejador por defecto
}

/**
 * Manejador de excepciones no capturadas
 * 
 * @param Throwable $exception Excepción no capturada
 * @return void
 */
function custom_exception_handler($exception) {
    $error_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => 'CRITICAL',
        'type' => 'EXCEPTION',
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
        'user_id' => $_SESSION['user_id'] ?? 'anonymous',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ];
    
    log_error($error_data);
    
    if (is_development_mode()) {
        display_detailed_error($error_data);
    } else {
        display_generic_error('CRITICAL');
    }
    
    notify_critical_error($error_data);
}

/**
 * Manejador de errores fatales
 * 
 * @return void
 */
function custom_fatal_error_handler() {
    $error = error_get_last();
    
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $error_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'CRITICAL',
            'type' => 'FATAL',
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
            'user_id' => $_SESSION['user_id'] ?? 'anonymous',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        log_error($error_data);
        notify_critical_error($error_data);
    }
}

// === FUNCIONES DE LOGGING ===

/**
 * Registra un error en el archivo de log correspondiente
 * 
 * @param array $error_data Datos del error
 * @return void
 */
function log_error($error_data) {
    $log_file = LOG_DIR . '/error.log';
    
    // Crear directorio de logs si no existe
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    // Verificar rotación de logs
    rotate_log_if_needed($log_file);
    
    // Formatear mensaje de log
    $log_message = sprintf(
        "[%s] %s: %s in %s:%d (User: %s, IP: %s)\n",
        $error_data['timestamp'],
        $error_data['level'],
        $error_data['message'],
        $error_data['file'],
        $error_data['line'],
        $error_data['user_id'],
        $error_data['ip']
    );
    
    // Escribir al log
    error_log($log_message, 3, $log_file);
}

/**
 * Registra actividad de usuario
 * 
 * @param int|string $user_id ID del usuario
 * @param string $action Acción realizada
 * @param array $additional_data Datos adicionales opcionales
 * @return void
 */
function log_activity($user_id, $action, $additional_data = []) {
    $log_file = LOG_DIR . '/activity.log';
    
    // Crear directorio de logs si no existe
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    // Verificar rotación de logs
    rotate_log_if_needed($log_file);
    
    // Preparar datos de actividad
    $activity_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $user_id,
        'action' => $action,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ];
    
    // Agregar datos adicionales si se proporcionan
    if (!empty($additional_data)) {
        $activity_data = array_merge($activity_data, $additional_data);
    }
    
    // Formatear mensaje de log
    $log_message = sprintf(
        "[%s] Usuario %s: %s (IP: %s)\n",
        $activity_data['timestamp'],
        $activity_data['user_id'],
        $activity_data['action'],
        $activity_data['ip']
    );
    
    // Agregar datos adicionales al mensaje si existen
    if (!empty($additional_data)) {
        $log_message .= "    Datos adicionales: " . json_encode($additional_data) . "\n";
    }
    
    // Escribir al log
    error_log($log_message, 3, $log_file);
}

/**
 * Registra eventos de seguridad
 * 
 * @param string $event Tipo de evento de seguridad
 * @param array $details Detalles del evento
 * @return void
 */
function log_security_event($event, $details = []) {
    $log_file = LOG_DIR . '/security.log';
    
    // Crear directorio de logs si no existe
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    // Verificar rotación de logs
    rotate_log_if_needed($log_file);
    
    $security_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'user_id' => $_SESSION['user_id'] ?? 'anonymous'
    ];
    
    $security_data = array_merge($security_data, $details);
    
    $log_message = sprintf(
        "[%s] SEGURIDAD - %s: %s (Usuario: %s, IP: %s)\n",
        $security_data['timestamp'],
        $security_data['event'],
        json_encode($details),
        $security_data['user_id'],
        $security_data['ip']
    );
    
    error_log($log_message, 3, $log_file);
}

// === FUNCIONES DE UTILIDAD ===

/**
 * Rota un archivo de log si excede el tamaño máximo
 * 
 * @param string $log_file Ruta del archivo de log
 * @return void
 */
function rotate_log_if_needed($log_file) {
    if (!file_exists($log_file) || filesize($log_file) < MAX_LOG_SIZE) {
        return;
    }
    
    // Rotar archivos existentes
    for ($i = MAX_LOG_FILES - 1; $i >= 1; $i--) {
        $old_file = $log_file . '.' . $i;
        $new_file = $log_file . '.' . ($i + 1);
        
        if (file_exists($old_file)) {
            if ($i == MAX_LOG_FILES - 1) {
                unlink($old_file); // Eliminar el más antiguo
            } else {
                rename($old_file, $new_file);
            }
        }
    }
    
    // Mover el archivo actual
    rename($log_file, $log_file . '.1');
}

// Función is_development_mode() ya está definida en config.php

/**
 * Muestra error detallado para desarrollo
 * 
 * @param array $error_data Datos del error
 * @return void
 */
function display_detailed_error($error_data) {
    echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;">';
    echo '<h3>Error de Desarrollo</h3>';
    echo '<p><strong>Nivel:</strong> ' . htmlspecialchars($error_data['level']) . '</p>';
    echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($error_data['message']) . '</p>';
    echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($error_data['file']) . '</p>';
    echo '<p><strong>Línea:</strong> ' . htmlspecialchars($error_data['line']) . '</p>';
    echo '<p><strong>Timestamp:</strong> ' . htmlspecialchars($error_data['timestamp']) . '</p>';
    
    if (isset($error_data['trace'])) {
        echo '<details><summary>Stack Trace</summary><pre>' . htmlspecialchars($error_data['trace']) . '</pre></details>';
    }
    
    echo '</div>';
}

/**
 * Muestra mensaje de error genérico para producción
 * 
 * @param string $level Nivel del error
 * @return void
 */
function display_generic_error($level) {
    $messages = [
        'INFO' => 'Se ha registrado una notificación del sistema.',
        'WARNING' => 'Se ha detectado una advertencia. El sistema continúa funcionando.',
        'ERROR' => 'Ha ocurrido un error. Por favor, inténtelo de nuevo.',
        'CRITICAL' => 'Error crítico del sistema. Por favor, contacte al soporte técnico.'
    ];
    
    $message = $messages[$level] ?? $messages['ERROR'];
    
    echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;">';
    echo '<p>' . htmlspecialchars($message) . '</p>';
    echo '</div>';
}

/**
 * Envía notificación para errores críticos
 * 
 * @param array $error_data Datos del error
 * @return void
 */
function notify_critical_error($error_data) {
    // Aquí se podría implementar envío de emails, webhooks, etc.
    // Por ahora solo registramos en un log especial
    $critical_log = LOG_DIR . '/critical.log';
    
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    $notification_message = sprintf(
        "[%s] ERROR CRÍTICO - %s en %s:%d (Usuario: %s, IP: %s)\n",
        $error_data['timestamp'],
        $error_data['message'],
        $error_data['file'],
        $error_data['line'],
        $error_data['user_id'],
        $error_data['ip']
    );
    
    error_log($notification_message, 3, $critical_log);
}

// === CONFIGURACIÓN DE MANEJADORES ===
// Establecer manejadores personalizados
set_error_handler('custom_error_handler');
set_exception_handler('custom_exception_handler');
register_shutdown_function('custom_fatal_error_handler');

// Configurar reporte de errores según el entorno
if (is_development_mode()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
}
?>