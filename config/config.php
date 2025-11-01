<?php
/**
 * Archivo de configuración principal de la aplicación PHP
 * Contiene configuración de base de datos, aplicación y seguridad
 * Autor: Sistema de optimización
 * Fecha: 2024
 */

// === CONFIGURACIÓN DE BASE DE DATOS ===
// Utiliza variables de entorno de Docker para mayor flexibilidad
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'your_username');
define('DB_PASS', getenv('DB_PASS') ?: 'your_password');
define('DB_NAME', getenv('DB_NAME') ?: 'your_database_name');

// Establecer conexión a la base de datos con manejo de errores mejorado
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Verificar conexión
    if ($conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos: " . $conn->connect_error);
    }
    
    // Establecer charset UTF-8 para soporte completo de caracteres
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Registrar error y mostrar mensaje genérico al usuario
    error_log("Error de conexión DB: " . $e->getMessage());
    die("Error de conexión a la base de datos. Por favor, inténtelo más tarde.");
}

// === CONFIGURACIÓN DE LA APLICACIÓN ===
// Configuración básica de la aplicación usando variables de entorno
define('APP_NAME', getenv('APP_NAME') ?: 'Simple PHP Web App');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8080');

// === CONFIGURACIÓN DE SEGURIDAD ===
// Claves secretas para CSRF y encriptación de contraseñas
define('CSRF_TOKEN_SECRET', getenv('CSRF_SECRET') ?: 'your_csrf_secret_key_change_in_production');
define('PASSWORD_PEPPER', getenv('PASSWORD_PEPPER') ?: 'your_password_pepper_change_in_production');

// === CONFIGURACIÓN DE SESIONES ===
// Configurar sesiones seguras antes de iniciarlas
ini_set('session.cookie_httponly', 1);  // Prevenir acceso via JavaScript
ini_set('session.use_only_cookies', 1); // Solo usar cookies para sesiones
ini_set('session.cookie_secure', 1);    // Solo HTTPS (desactivar en desarrollo local)
ini_set('session.cookie_samesite', 'Strict'); // Protección CSRF adicional

// Iniciar sesión con configuración segura
session_start();

// === CONFIGURACIÓN DE ERRORES ===
// Configurar reporte de errores (cambiar a 0 en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/error.log');

// === INCLUIR ARCHIVOS DE FUNCIONES ===
// Cargar autoloader y todas las funciones necesarias para la aplicación

// Cargar autoloader que maneja la carga automática de clases
require_once __DIR__ . '/../app/autoload.php';

// === CONFIGURACIÓN DEL MANEJADOR DE ERRORES ===
// Establecer manejador personalizado de errores
set_error_handler("custom_error_handler");

// === CONFIGURACIÓN DE ZONA HORARIA ===
// Establecer zona horaria por defecto
date_default_timezone_set('America/Mexico_City');

// === CONSTANTES ADICIONALES ===
// Definir constantes útiles para la aplicación
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 300); // 5 minutos
define('SESSION_TIMEOUT', 3600); // 1 hora

/**
 * Función para verificar si la aplicación está en modo de desarrollo
 * @return bool True si está en desarrollo, False en producción
 */
function is_development_mode() {
    return getenv('APP_ENV') === 'development' || ini_get('display_errors') == 1;
}

/**
 * Función para obtener la URL base de la aplicación
 * @return string URL base de la aplicación
 */
function get_base_url() {
    return rtrim(APP_URL, '/');
}

?>