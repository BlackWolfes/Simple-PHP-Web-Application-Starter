<?php
/**
 * Funciones de Protección CSRF (Cross-Site Request Forgery)
 * Proporciona funcionalidades para generar y verificar tokens CSRF
 * para proteger contra ataques de falsificación de solicitudes entre sitios
 * 
 * Características:
 * - Generación segura de tokens CSRF
 * - Verificación temporal de tokens
 * - Rotación automática de tokens
 * - Protección contra ataques de timing
 * 
 * Autor: Sistema de optimización
 * Fecha: 2024
 */

// === CONFIGURACIÓN DE CSRF ===
// Tiempo de vida del token CSRF en segundos (30 minutos por defecto)
define('CSRF_TOKEN_LIFETIME', 1800);

// === FUNCIONES PRINCIPALES DE CSRF ===

/**
 * Genera un token CSRF seguro y lo almacena en la sesión
 * Incluye timestamp para verificación de expiración
 * 
 * @param bool $force_regenerate Forzar regeneración del token
 * @return string Token CSRF generado
 */
function generate_csrf_token($force_regenerate = false) {
    // Verificar si ya existe un token válido
    if (!$force_regenerate && isset($_SESSION['csrf_token']) && isset($_SESSION['csrf_token_time'])) {
        // Verificar si el token no ha expirado
        if ((time() - $_SESSION['csrf_token_time']) < CSRF_TOKEN_LIFETIME) {
            return $_SESSION['csrf_token'];
        }
    }
    
    try {
        // Generar nuevo token seguro
        $token = bin2hex(random_bytes(32));
        
        // Almacenar token y timestamp en la sesión
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        // Log de generación de token (solo en modo debug)
        if (defined('DEBUG_MODE') && constant('DEBUG_MODE')) {
            error_log("Nuevo token CSRF generado para sesión: " . session_id());
        }
        
        return $token;
        
    } catch (Exception $e) {
        // Fallback en caso de error con random_bytes
        error_log("Error generando token CSRF: " . $e->getMessage());
        
        // Usar método alternativo menos seguro pero funcional
        $token = hash('sha256', uniqid(mt_rand(), true) . microtime());
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
}

/**
 * Verifica si un token CSRF es válido
 * Incluye verificación de expiración y protección contra timing attacks
 * 
 * @param string $token Token a verificar
 * @param bool $auto_regenerate Regenerar token automáticamente después de verificación exitosa
 * @return bool True si el token es válido, false en caso contrario
 */
function verify_csrf_token($token, $auto_regenerate = false) {
    // Verificar que existan los datos necesarios en la sesión
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        error_log("Intento de verificación CSRF sin token en sesión");
        return false;
    }
    
    // Verificar que el token no esté vacío
    if (empty($token)) {
        error_log("Intento de verificación CSRF con token vacío");
        return false;
    }
    
    // Verificar expiración del token
    if ((time() - $_SESSION['csrf_token_time']) >= CSRF_TOKEN_LIFETIME) {
        error_log("Token CSRF expirado para sesión: " . session_id());
        // Limpiar token expirado
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    // Verificar token usando hash_equals para prevenir timing attacks
    $is_valid = hash_equals($_SESSION['csrf_token'], $token);
    
    if ($is_valid) {
        // Log de verificación exitosa (solo en modo debug)
        if (defined('DEBUG_MODE') && constant('DEBUG_MODE')) {
            error_log("Token CSRF verificado exitosamente para sesión: " . session_id());
        }
        
        // Regenerar token automáticamente si se solicita (one-time use)
        if ($auto_regenerate) {
            generate_csrf_token(true);
        }
    } else {
        // Log de intento de verificación fallido
        error_log("Intento de verificación CSRF fallido para sesión: " . session_id() . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
    
    return $is_valid;
}

/**
 * Genera un campo de entrada oculto HTML con el token CSRF
 * Útil para incluir directamente en formularios
 * 
 * @param string $field_name Nombre del campo (por defecto 'csrf_token')
 * @return string HTML del campo oculto
 */
function csrf_token_field($field_name = 'csrf_token') {
    $token = generate_csrf_token();
    $field_name = htmlspecialchars($field_name, ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="' . $field_name . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Obtiene el token CSRF actual sin generar uno nuevo
 * Útil para AJAX requests
 * 
 * @return string|null Token actual o null si no existe
 */
function get_current_csrf_token() {
    if (isset($_SESSION['csrf_token']) && isset($_SESSION['csrf_token_time'])) {
        // Verificar que no haya expirado
        if ((time() - $_SESSION['csrf_token_time']) < CSRF_TOKEN_LIFETIME) {
            return $_SESSION['csrf_token'];
        } else {
            // Token expirado, limpiar
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        }
    }
    return null;
}

/**
 * Regenera el token CSRF (útil después de cambios importantes como login)
 * 
 * @return string Nuevo token generado
 */
function regenerate_csrf_token() {
    return generate_csrf_token(true);
}

/**
 * Limpia el token CSRF de la sesión
 * Útil durante logout o limpieza de sesión
 * 
 * @return void
 */
function clear_csrf_token() {
    unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
    
    if (defined('DEBUG_MODE') && constant('DEBUG_MODE')) {
        error_log("Token CSRF limpiado para sesión: " . session_id());
    }
}

/**
 * Verifica si el token CSRF actual está próximo a expirar
 * Útil para mostrar advertencias al usuario
 * 
 * @param int $warning_threshold Segundos antes de expiración para mostrar advertencia
 * @return bool True si está próximo a expirar
 */
function csrf_token_expires_soon($warning_threshold = 300) {
    if (!isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    $time_remaining = CSRF_TOKEN_LIFETIME - (time() - $_SESSION['csrf_token_time']);
    return $time_remaining <= $warning_threshold && $time_remaining > 0;
}

/**
 * Obtiene el tiempo restante del token CSRF en segundos
 * 
 * @return int Segundos restantes o 0 si ha expirado
 */
function csrf_token_time_remaining() {
    if (!isset($_SESSION['csrf_token_time'])) {
        return 0;
    }
    
    $time_remaining = CSRF_TOKEN_LIFETIME - (time() - $_SESSION['csrf_token_time']);
    return max(0, $time_remaining);
}

// === FUNCIONES DE UTILIDAD PARA DESARROLLADORES ===

/**
 * Función de middleware para verificar CSRF en requests POST/PUT/DELETE
 * Termina la ejecución si la verificación falla
 * 
 * @param array $exempt_actions Acciones exentas de verificación CSRF
 * @return void
 */
function csrf_protect($exempt_actions = []) {
    // Solo verificar en métodos que modifican datos
    $protected_methods = ['POST', 'PUT', 'DELETE', 'PATCH'];
    $request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if (!in_array($request_method, $protected_methods)) {
        return;
    }
    
    // Verificar si la acción actual está exenta
    $current_action = $_REQUEST['action'] ?? $_SERVER['PHP_SELF'] ?? '';
    if (in_array($current_action, $exempt_actions)) {
        return;
    }
    
    // Obtener token del request
    $token = $_POST['csrf_token'] ?? $_REQUEST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($token)) {
        // Log del intento de acceso sin CSRF válido
        error_log("Acceso bloqueado por CSRF inválido - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " - URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
        
        // Responder según el tipo de request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            // Request AJAX
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'error' => 'Token CSRF inválido o expirado',
                'code' => 'CSRF_INVALID'
            ]);
        } else {
            // Request normal
            http_response_code(403);
            echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Error de Seguridad</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
        .error { color: #e74c3c; }
    </style>
</head>
<body>
    <h1 class="error">Error de Seguridad</h1>
    <p>Token CSRF inválido o expirado. Por favor, recargue la página e inténtelo de nuevo.</p>
    <p><a href="javascript:history.back()">← Volver</a></p>
</body>
</html>';
        }
        exit();
    }
}
?>