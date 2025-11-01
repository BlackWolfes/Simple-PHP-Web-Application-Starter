<?php
/**
 * Script de diagnóstico para conexión MySQL
 * Ayuda a identificar problemas de conectividad con el servidor MySQL
 */

echo "=== DIAGNÓSTICO DE CONEXIÓN MYSQL ===\n\n";

// Cargar variables de entorno desde .env si existe
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue; // Skip comments
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Configuración de base de datos
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'webapp_user';
$db_pass = getenv('DB_PASS') ?: 'your_secure_password_here';
$db_name = getenv('DB_NAME') ?: 'webapp_db';
$db_port = getenv('DB_PORT') ?: 3306;

echo "1. CONFIGURACIÓN DETECTADA:\n";
echo "   Host: $db_host\n";
echo "   Puerto: $db_port\n";
echo "   Usuario: $db_user\n";
echo "   Base de datos: $db_name\n";
echo "   Contraseña: " . (strlen($db_pass) > 0 ? str_repeat('*', strlen($db_pass)) : 'NO CONFIGURADA') . "\n\n";

// Test 1: Verificar si el host es alcanzable
echo "2. PRUEBA DE CONECTIVIDAD DE RED:\n";
$connection = @fsockopen($db_host, $db_port, $errno, $errstr, 5);
if ($connection) {
    echo "   ✓ El host $db_host:$db_port es alcanzable\n";
    fclose($connection);
} else {
    echo "   ✗ ERROR: No se puede conectar a $db_host:$db_port\n";
    echo "   Código de error: $errno\n";
    echo "   Mensaje: $errstr\n";
}
echo "\n";

// Test 2: Verificar extensión mysqli
echo "3. VERIFICACIÓN DE EXTENSIONES PHP:\n";
if (extension_loaded('mysqli')) {
    echo "   ✓ Extensión mysqli está disponible\n";
} else {
    echo "   ✗ ERROR: Extensión mysqli no está disponible\n";
}

if (extension_loaded('pdo_mysql')) {
    echo "   ✓ Extensión PDO MySQL está disponible\n";
} else {
    echo "   ✗ WARNING: Extensión PDO MySQL no está disponible\n";
}
echo "\n";

// Test 3: Intentar conexión MySQL
echo "4. PRUEBA DE CONEXIÓN MYSQL:\n";
try {
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    
    if ($mysqli->connect_error) {
        echo "   ✗ ERROR DE CONEXIÓN: " . $mysqli->connect_error . "\n";
        echo "   Código de error: " . $mysqli->connect_errno . "\n";
    } else {
        echo "   ✓ Conexión MySQL exitosa\n";
        echo "   Versión del servidor: " . $mysqli->server_info . "\n";
        echo "   Versión del cliente: " . $mysqli->client_info . "\n";
        
        // Test básico de consulta
        $result = $mysqli->query("SELECT 1 as test");
        if ($result) {
            echo "   ✓ Consulta de prueba exitosa\n";
            $result->free();
        } else {
            echo "   ✗ ERROR en consulta de prueba: " . $mysqli->error . "\n";
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "   ✗ EXCEPCIÓN: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Información del sistema
echo "5. INFORMACIÓN DEL SISTEMA:\n";
echo "   PHP Version: " . phpversion() . "\n";
echo "   Sistema operativo: " . php_uname() . "\n";
echo "   Directorio actual: " . getcwd() . "\n";

// Verificar si estamos en Docker
if (file_exists('/.dockerenv')) {
    echo "   ✓ Ejecutándose dentro de un contenedor Docker\n";
} else {
    echo "   ℹ No se detectó entorno Docker\n";
}
echo "\n";

// Test 5: Comandos de red (si están disponibles)
echo "6. DIAGNÓSTICO DE RED AVANZADO:\n";

// Ping test
echo "   Probando ping a $db_host...\n";
$ping_output = shell_exec("ping -c 3 $db_host 2>&1");
if ($ping_output) {
    if (strpos($ping_output, '3 received') !== false || strpos($ping_output, 'bytes from') !== false) {
        echo "   ✓ Ping exitoso\n";
    } else {
        echo "   ✗ Ping falló\n";
        echo "   Salida: " . substr($ping_output, 0, 200) . "...\n";
    }
} else {
    echo "   ℹ Comando ping no disponible\n";
}

// Telnet test
echo "   Probando conexión telnet a $db_host:$db_port...\n";
$telnet_output = shell_exec("timeout 5 telnet $db_host $db_port 2>&1");
if ($telnet_output && strpos($telnet_output, 'Connected') !== false) {
    echo "   ✓ Conexión telnet exitosa\n";
} else {
    echo "   ✗ Conexión telnet falló\n";
    if ($telnet_output) {
        echo "   Salida: " . substr($telnet_output, 0, 200) . "...\n";
    }
}

// Netcat test
echo "   Probando con netcat...\n";
$nc_output = shell_exec("timeout 5 nc -zv $db_host $db_port 2>&1");
if ($nc_output && (strpos($nc_output, 'succeeded') !== false || strpos($nc_output, 'open') !== false)) {
    echo "   ✓ Netcat conexión exitosa\n";
} else {
    echo "   ✗ Netcat conexión falló\n";
    if ($nc_output) {
        echo "   Salida: " . substr($nc_output, 0, 200) . "...\n";
    }
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";

// Recomendaciones
echo "\nRECOMENDACIONES:\n";
echo "1. Si la conectividad de red falla, verificar:\n";
echo "   - Que el servidor MySQL esté ejecutándose en $db_host:$db_port\n";
echo "   - Configuración del firewall\n";
echo "   - Configuración de red de Docker\n";
echo "   - Que MySQL esté configurado para aceptar conexiones externas\n\n";

echo "2. Si la conexión MySQL falla pero la red funciona:\n";
echo "   - Verificar credenciales de usuario\n";
echo "   - Verificar permisos del usuario en MySQL\n";
echo "   - Verificar configuración bind-address en MySQL\n\n";

echo "3. Para ejecutar este diagnóstico desde Docker:\n";
echo "   docker exec -it <container_name> php mysql-diagnostic.php\n\n";
?>