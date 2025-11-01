<?php
// config.php

// Database configuration - usando variables de entorno de Docker
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'your_username');
define('DB_PASS', getenv('DB_PASS') ?: 'your_password');
define('DB_NAME', getenv('DB_NAME') ?: 'your_database_name');

// Establish database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Application settings - usando variables de entorno de Docker
define('APP_NAME', getenv('APP_NAME') ?: 'Simple PHP Web App');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8080');

// Security settings - usando variables de entorno de Docker
define('CSRF_TOKEN_SECRET', getenv('CSRF_SECRET') ?: 'your_csrf_secret_key_change_in_production');
define('PASSWORD_PEPPER', getenv('PASSWORD_PEPPER') ?: 'your_password_pepper_change_in_production');

// Session configurationini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
session_start();

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include essential files
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf_functions.php';
require_once __DIR__ . '/includes/rate_limit.php';
require_once __DIR__ . '/includes/error_handler.php';
require_once __DIR__ . '/includes/dashboard_functions.php';;

// Set up custom error handler
set_error_handler("custom_error_handler");
?>