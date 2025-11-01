#!/bin/bash
# Script de comandos Docker para Simple PHP Web Application

echo "=== Comandos Docker para Simple PHP Web Application ==="
echo ""

# Función para mostrar ayuda
show_help() {
    echo "Uso: ./docker-commands.sh [COMANDO]"
    echo ""
    echo "Comandos disponibles:"
    echo "  build       - Construir la imagen Docker"
    echo "  run         - Ejecutar el contenedor"
    echo "  compose     - Usar docker-compose"
    echo "  stop        - Detener el contenedor"
    echo "  logs        - Ver logs del contenedor"
    echo "  shell       - Acceder al shell del contenedor"
    echo "  test        - Probar conectividad con MySQL"
    echo "  status      - Ver estado del contenedor"
    echo "  clean       - Limpiar contenedores e imágenes"
    echo "  help        - Mostrar esta ayuda"
}

# Función para construir la imagen
build_image() {
    echo "🔨 Construyendo imagen Docker..."
    docker build -t php-webapp:latest .
    echo "✅ Imagen construida exitosamente"
}

# Función para ejecutar el contenedor
run_container() {
    echo "🚀 Ejecutando contenedor..."
    docker run -d \
      --name php-webapp-container \
      -p 8080:80 \
      -e DB_HOST=192.168.23.136 \
      -e DB_PORT=3306 \
      -e DB_NAME=webapp_db \
      -e DB_USER=webapp_user \
      -e DB_PASS=your_secure_password \
      -e APP_NAME="Simple PHP Web App" \
      -e APP_URL=http://192.168.23.136:8080 \
      -e CSRF_SECRET=change_this_in_production_min_32_chars \
      -e PASSWORD_PEPPER=change_this_in_production_min_32_chars \
      -v $(pwd)/logs:/var/www/html/logs \
      --add-host=database:192.168.23.136 \
      php-webapp:latest
    echo "✅ Contenedor ejecutándose en http://192.168.23.136:8080"
}

# Función para usar docker-compose
use_compose() {
    echo "🐳 Usando docker-compose..."
    docker-compose up -d
    echo "✅ Aplicación ejecutándose con docker-compose"
}

# Función para detener el contenedor
stop_container() {
    echo "🛑 Deteniendo contenedor..."
    docker stop php-webapp-container
    docker rm php-webapp-container
    echo "✅ Contenedor detenido y eliminado"
}

# Función para ver logs
show_logs() {
    echo "📋 Mostrando logs del contenedor..."
    docker logs -f php-webapp-container
}

# Función para acceder al shell
access_shell() {
    echo "🐚 Accediendo al shell del contenedor..."
    docker exec -it php-webapp-container /bin/bash
}

# Función para probar conectividad
test_connectivity() {
    echo "🔍 Probando conectividad con MySQL en 192.168.23.136..."
    echo "1. Probando ping..."
    docker exec php-webapp-container ping -c 3 192.168.23.136
    
    echo ""
    echo "2. Probando conexión MySQL (requiere credenciales válidas)..."
    echo "   Comando: docker exec -it php-webapp-container mysql -h 192.168.23.136 -u webapp_user -p"
    
    echo ""
    echo "3. Verificando variables de entorno..."
    docker exec php-webapp-container env | grep -E "(DB_|APP_)"
}

# Función para ver estado
show_status() {
    echo "📊 Estado del contenedor..."
    docker ps -a | grep php-webapp
    echo ""
    echo "📈 Estadísticas de recursos..."
    docker stats php-webapp-container --no-stream
}

# Función para limpiar
clean_docker() {
    echo "🧹 Limpiando contenedores e imágenes..."
    docker stop php-webapp-container 2>/dev/null
    docker rm php-webapp-container 2>/dev/null
    docker rmi php-webapp:latest 2>/dev/null
    echo "✅ Limpieza completada"
}

# Procesar argumentos
case "$1" in
    build)
        build_image
        ;;
    run)
        run_container
        ;;
    compose)
        use_compose
        ;;
    stop)
        stop_container
        ;;
    logs)
        show_logs
        ;;
    shell)
        access_shell
        ;;
    test)
        test_connectivity
        ;;
    status)
        show_status
        ;;
    clean)
        clean_docker
        ;;
    help|*)
        show_help
        ;;
esac