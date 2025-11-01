<?php
/**
 * Punto de entrada principal de la aplicación
 * Redirige a login si no está autenticado, o al dashboard si lo está
 * 
 * Autor: Sistema de optimización
 * Fecha: 2024
 */

// Iniciar sesión
session_start();

// Verificar si el usuario está autenticado
if (isset($_SESSION['user_id'])) {
    // Usuario autenticado, redirigir al dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // Usuario no autenticado, redirigir al login
    header("Location: login.php");
    exit();
}
?>