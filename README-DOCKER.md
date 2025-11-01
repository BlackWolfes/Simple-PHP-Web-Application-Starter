# 🐳 Dockerización - Simple PHP Web Application

## 📋 Resumen

Esta aplicación PHP ha sido completamente dockerizada para ejecutarse con conectividad al servidor MySQL en **192.168.23.136**. La aplicación será accesible desde cualquier dispositivo en la red a través de **http://192.168.23.136:8080**.

## 🚀 Inicio Rápido

### 1. Configurar Variables de Entorno

```bash
# Copiar el archivo de ejemplo
cp .env.example .env

# Editar las variables según tu configuración
nano .env
```

**Variables importantes a configurar:**
- `DB_PASS`: Contraseña de tu base de datos MySQL
- `CSRF_SECRET`: Clave secreta para tokens CSRF (mínimo 32 caracteres)
- `PASSWORD_PEPPER`: Clave para hash de contraseñas (mínimo 32 caracteres)

### 2. Opción A: Usar Docker Compose (Recomendado)

```bash
# Construir y ejecutar con docker-compose
docker-compose up -d

# Ver logs
docker-compose logs -f

# Detener
docker-compose down
```

### 3. Opción B: Usar Comandos Docker Directos

```bash
# Usar el script de comandos incluido
./docker-commands.sh build    # Construir imagen
./docker-commands.sh run      # Ejecutar contenedor
./docker-commands.sh status   # Ver estado
./docker-commands.sh logs     # Ver logs
./docker-commands.sh stop     # Detener
```

### 4. Opción C: Comandos Manuales

```bash
# Construir imagen
docker build -t php-webapp:latest .

# Ejecutar contenedor
docker run -d \
  --name php-webapp-container \
  -p 8080:80 \
  -e DB_HOST=192.168.23.136 \
  -e DB_PORT=3306 \
  -e DB_NAME=webapp_db \
  -e DB_USER=webapp_user \
  -e DB_PASS=tu_contraseña_segura \
  -e APP_URL=http://192.168.23.136:8080 \
  -v $(pwd)/logs:/var/www/html/logs \
  --add-host=database:192.168.23.136 \
  php-webapp:latest
```

## 🔧 Configuración de Base de Datos

### Preparar MySQL en 192.168.23.136

1. **Crear base de datos y usuario:**

```sql
-- Conectar a MySQL en 192.168.23.136
mysql -h 192.168.23.136 -u root -p

-- Crear base de datos
CREATE DATABASE webapp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario para la aplicación
CREATE USER 'webapp_user'@'%' IDENTIFIED BY 'tu_contraseña_segura';
GRANT ALL PRIVILEGES ON webapp_db.* TO 'webapp_user'@'%';
FLUSH PRIVILEGES;
```

2. **Importar esquema:**

```bash
# Desde el servidor MySQL
mysql -h 192.168.23.136 -u webapp_user -p webapp_db < db/schema.sql
```

## 🌐 Acceso a la Aplicación

Una vez ejecutándose, la aplicación estará disponible en:

- **URL Principal**: http://192.168.23.136:8080
- **Login**: http://192.168.23.136:8080/login.php
- **Registro**: http://192.168.23.136:8080/signup.php

### Usuario de Prueba (incluido en schema.sql)
- **Email**: test@example.com
- **Contraseña**: password123

## 🔍 Verificación y Monitoreo

### Verificar Conectividad

```bash
# Probar conectividad con MySQL
./docker-commands.sh test

# O manualmente:
docker exec php-webapp-container ping -c 3 192.168.23.136
docker exec -it php-webapp-container mariadb -h 192.168.23.136 -u webapp_user -p
```

### Monitorear Logs

```bash
# Logs del contenedor
docker logs -f php-webapp-container

# Logs de la aplicación
tail -f logs/error.log
tail -f logs/activity.log

# Acceder al contenedor
docker exec -it php-webapp-container /bin/bash
```

### Ver Estado

```bash
# Estado del contenedor
docker ps | grep php-webapp

# Estadísticas de recursos
docker stats php-webapp-container
```

## 📁 Estructura de Archivos Docker

```
Simple-PHP-Web-Application-Starter/
├── Dockerfile                 # Imagen Docker optimizada
├── docker-compose.yml         # Configuración de Docker Compose
├── .dockerignore             # Archivos excluidos de la imagen
├── .env.example              # Variables de entorno de ejemplo
├── docker-commands.sh        # Script de comandos útiles
├── README-DOCKER.md          # Esta documentación
└── logs/                     # Logs persistentes (volumen)
```

## 🛠️ Comandos Útiles

### Script de Comandos Incluido

```bash
./docker-commands.sh help     # Ver todos los comandos disponibles
./docker-commands.sh build    # Construir imagen
./docker-commands.sh run      # Ejecutar contenedor
./docker-commands.sh compose  # Usar docker-compose
./docker-commands.sh stop     # Detener contenedor
./docker-commands.sh logs     # Ver logs
./docker-commands.sh shell    # Acceder al shell
./docker-commands.sh test     # Probar conectividad
./docker-commands.sh status   # Ver estado
./docker-commands.sh clean    # Limpiar contenedores
```

## 🔒 Configuración de Seguridad

### Variables de Entorno Importantes

```bash
# En producción, usar valores seguros:
CSRF_SECRET=tu_clave_csrf_de_minimo_32_caracteres_aqui
PASSWORD_PEPPER=tu_clave_pepper_de_minimo_32_caracteres_aqui
DB_PASS=tu_contraseña_mysql_muy_segura
```

### Configuración de Red

- **Puerto de aplicación**: 8080 (host) → 80 (contenedor)
- **Conectividad MySQL**: 192.168.23.136:3306
- **Red Docker**: Configurada para acceso externo
- **Host mapping**: database → 192.168.23.136

## 🐛 Solución de Problemas

### Error de Conexión a MySQL

```bash
# Verificar conectividad de red
docker exec php-webapp-container ping 192.168.23.136

# Verificar variables de entorno
docker exec php-webapp-container env | grep DB_

# Probar conexión MySQL
docker exec -it php-webapp-container mariadb -h 192.168.23.136 -u webapp_user -p
```

### Error de Permisos

```bash
# Verificar permisos de logs
ls -la logs/

# Corregir permisos si es necesario
sudo chown -R $USER:$USER logs/
chmod -R 755 logs/
```

### Reconstruir Imagen

```bash
# Limpiar y reconstruir
./docker-commands.sh clean
./docker-commands.sh build
./docker-commands.sh run
```

## 📞 Soporte

Si encuentras problemas:

1. Revisa los logs: `./docker-commands.sh logs`
2. Verifica la conectividad: `./docker-commands.sh test`
3. Comprueba el estado: `./docker-commands.sh status`
4. Accede al contenedor: `./docker-commands.sh shell`

---

**¡Tu aplicación PHP está lista para ejecutarse en Docker con conectividad a 192.168.23.136! 🎉**