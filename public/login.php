<?php
/**
 * Punto de entrada para la página de login
 * Utiliza el patrón MVC con LoginController
 * 
 * Autor: Sistema de optimización
 * Fecha: 2024
 */

// Cargar configuración principal (incluye autoloader)
require_once 'config/config.php';

// Crear instancia del controlador
$loginController = new LoginController($conn);

// Procesar el formulario si es POST
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $result = $loginController->processLogin();
    
    if ($result['success']) {
        header("Location: " . $result['redirect']);
        exit();
    } else {
        $error = $result['error'];
    }
}

// Mostrar el formulario de login
$loginController->showLoginForm($error);
?>