<?php
/**
 * Autoloader básico para cargar clases automáticamente
 * Implementa PSR-4 básico para la estructura de la aplicación
 * 
 * Autor: Sistema de optimización
 * Fecha: 2024
 */

spl_autoload_register(function ($className) {
    // Directorio base de la aplicación
    $baseDir = __DIR__ . '/';
    
    // Mapeo de namespaces a directorios
    $namespaceMap = [
        'Controllers\\' => 'controllers/',
        'Models\\' => 'models/',
        'Middleware\\' => 'middleware/',
    ];
    
    // Buscar en el mapeo de namespaces
    foreach ($namespaceMap as $namespace => $directory) {
        if (strpos($className, $namespace) === 0) {
            $relativeClass = substr($className, strlen($namespace));
            $file = $baseDir . $directory . str_replace('\\', '/', $relativeClass) . '.php';
            
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
    
    // Si no se encuentra en namespaces, buscar directamente en controllers
    $controllerFile = $baseDir . 'controllers/' . $className . '.php';
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        return;
    }
    
    // Buscar en models
    $modelFile = $baseDir . 'models/' . $className . '.php';
    if (file_exists($modelFile)) {
        require_once $modelFile;
        return;
    }
    
    // Buscar en middleware
    $middlewareFile = $baseDir . 'middleware/' . $className . '.php';
    if (file_exists($middlewareFile)) {
        require_once $middlewareFile;
        return;
    }
});

/**
 * Función para cargar todos los archivos de middleware
 */
function load_middleware() {
    $middlewareDir = __DIR__ . '/middleware/';
    $files = [
        'csrf_functions.php',
        'rate_limit.php',
        'error_handler.php'
    ];
    
    foreach ($files as $file) {
        $filePath = $middlewareDir . $file;
        if (file_exists($filePath)) {
            require_once $filePath;
        }
    }
}

/**
 * Función para cargar todos los modelos
 */
function load_models() {
    $modelsDir = __DIR__ . '/models/';
    $files = [
        'functions.php'
    ];
    
    foreach ($files as $file) {
        $filePath = $modelsDir . $file;
        if (file_exists($filePath)) {
            require_once $filePath;
        }
    }
}

// Cargar middleware y modelos automáticamente
load_middleware();
load_models();
?>