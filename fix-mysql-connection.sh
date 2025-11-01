#!/bin/bash

echo "=== SOLUCIONADOR DE PROBLEMAS MYSQL ==="
echo "Error detectado: Connection refused a 192.168.23.136:3306"
echo ""

# Función para mostrar opciones
show_menu() {
    echo "Selecciona una opción:"
    echo "1) Usar MySQL local con Docker (RECOMENDADO)"
    echo "2) Probar conexión al servidor remoto"
    echo "3) Verificar configuración actual"
    echo "4) Mostrar comandos de diagnóstico"
    echo "5) Salir"
    echo ""
}

# Función para usar MySQL local
use_local_mysql() {
    echo "🔄 Configurando MySQL local..."
    
    # Detener contenedores actuales
    echo "Deteniendo contenedores actuales..."
    docker-compose down
    
    # Usar docker-compose con MySQL local
    echo "Iniciando con MySQL local..."
    docker-compose -f docker-compose-local-mysql.yml up -d
    
    echo "✅ MySQL local configurado!"
    echo "📝 Configuración aplicada:"
    echo "   - DB_HOST: mysql (contenedor local)"
    echo "   - DB_PORT: 3306"
    echo "   - DB_USER: webapp_user"
    echo "   - DB_NAME: webapp_db"
    echo ""
    echo "🌐 Accede a tu aplicación en: http://localhost:8080"
    echo "🗄️  MySQL disponible en: localhost:3306"
}

# Función para probar conexión remota
test_remote_connection() {
    echo "🔍 Probando conexión al servidor remoto..."
    
    # Verificar si el contenedor está ejecutándose
    if ! docker ps | grep -q php-webapp-container; then
        echo "❌ Contenedor no está ejecutándose. Iniciando..."
        docker-compose up -d
        sleep 5
    fi
    
    echo "Probando ping..."
    if docker exec php-webapp-container ping -c 3 192.168.23.136 > /dev/null 2>&1; then
        echo "✅ Ping exitoso"
    else
        echo "❌ Ping falló"
        return 1
    fi
    
    echo "Probando conexión MySQL..."
    result=$(docker exec php-webapp-container php -r "
    try {
        \$mysqli = new mysqli('192.168.23.136', 'webapp_user', 'your_secure_password_here', 'webapp_db');
        echo \$mysqli->connect_error ? 'ERROR: ' . \$mysqli->connect_error : 'SUCCESS';
    } catch (Exception \$e) {
        echo 'EXCEPTION: ' . \$e->getMessage();
    }
    " 2>&1)
    
    if [[ $result == *"SUCCESS"* ]]; then
        echo "✅ Conexión MySQL exitosa!"
    else
        echo "❌ Conexión MySQL falló:"
        echo "   $result"
        echo ""
        echo "💡 Posibles soluciones:"
        echo "   1. Verificar que MySQL esté ejecutándose en 192.168.23.136"
        echo "   2. Configurar MySQL para aceptar conexiones externas"
        echo "   3. Verificar firewall en el servidor"
        echo "   4. Verificar credenciales de usuario"
    fi
}

# Función para verificar configuración
check_config() {
    echo "🔍 Verificando configuración actual..."
    echo ""
    
    echo "📄 Variables de entorno (.env):"
    if [ -f .env ]; then
        grep -E "^DB_|^APP_" .env | while read line; do
            echo "   $line"
        done
    else
        echo "   ❌ Archivo .env no encontrado"
    fi
    echo ""
    
    echo "🐳 Contenedores Docker:"
    docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
    echo ""
    
    echo "🔗 Configuración de red Docker:"
    docker network ls | grep -E "webapp|bridge"
}

# Función para mostrar comandos de diagnóstico
show_diagnostic_commands() {
    echo "🛠️  Comandos de diagnóstico útiles:"
    echo ""
    echo "# Probar ping desde contenedor:"
    echo "docker exec php-webapp-container ping -c 3 192.168.23.136"
    echo ""
    echo "# Probar conexión MySQL desde contenedor:"
    echo 'docker exec php-webapp-container php -r "
try {
    \$mysqli = new mysqli(\"192.168.23.136\", \"webapp_user\", \"your_secure_password_here\", \"webapp_db\");
    echo \$mysqli->connect_error ? \"ERROR: \" . \$mysqli->connect_error : \"SUCCESS\";
} catch (Exception \$e) {
    echo \"EXCEPTION: \" . \$e->getMessage();
}
"'
    echo ""
    echo "# Ver logs del contenedor:"
    echo "docker logs php-webapp-container"
    echo ""
    echo "# Acceder al contenedor:"
    echo "docker exec -it php-webapp-container bash"
    echo ""
    echo "# En el servidor MySQL (192.168.23.136):"
    echo "sudo systemctl status mysql"
    echo "sudo netstat -tlnp | grep :3306"
    echo "sudo tail -f /var/log/mysql/error.log"
}

# Menú principal
while true; do
    show_menu
    read -p "Opción: " choice
    
    case $choice in
        1)
            use_local_mysql
            break
            ;;
        2)
            test_remote_connection
            echo ""
            ;;
        3)
            check_config
            echo ""
            ;;
        4)
            show_diagnostic_commands
            echo ""
            ;;
        5)
            echo "👋 ¡Hasta luego!"
            exit 0
            ;;
        *)
            echo "❌ Opción inválida. Por favor selecciona 1-5."
            echo ""
            ;;
    esac
done