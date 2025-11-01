# Solución de Problemas MySQL - Connection Refused

## 🔍 Diagnóstico Realizado

**Error detectado:** `mysqli_sql_exception: Connection refused`
**Servidor objetivo:** 192.168.23.136:3306
**Estado de conectividad:**
- ✅ Ping al host: EXITOSO
- ❌ Conexión al puerto 3306: RECHAZADA
- ✅ Configuración PHP: CORRECTA
- ✅ Variables de entorno: CONFIGURADAS

## 🚨 Problema Identificado

El servidor MySQL en `192.168.23.136:3306` está **rechazando conexiones**. Esto puede deberse a:

1. **MySQL no está ejecutándose** en el servidor
2. **MySQL no acepta conexiones externas** (bind-address configurado solo para localhost)
3. **Firewall bloqueando** el puerto 3306
4. **Credenciales incorrectas** o usuario sin permisos remotos

## 🛠️ Soluciones Paso a Paso

### Solución 1: Verificar Estado del Servidor MySQL

```bash
# En el servidor 192.168.23.136, verificar si MySQL está ejecutándose
sudo systemctl status mysql
# o
sudo systemctl status mariadb

# Si no está ejecutándose, iniciarlo
sudo systemctl start mysql
sudo systemctl enable mysql
```

### Solución 2: Configurar MySQL para Conexiones Externas

```bash
# En el servidor MySQL (192.168.23.136)
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf

# Buscar y modificar la línea bind-address:
# Cambiar de:
bind-address = 127.0.0.1
# A:
bind-address = 0.0.0.0

# Reiniciar MySQL
sudo systemctl restart mysql
```

### Solución 3: Configurar Usuario MySQL para Acceso Remoto

```sql
-- Conectar a MySQL como root en el servidor
mysql -u root -p

-- Crear usuario con acceso remoto
CREATE USER 'webapp_user'@'%' IDENTIFIED BY 'your_secure_password_here';
GRANT ALL PRIVILEGES ON webapp_db.* TO 'webapp_user'@'%';
FLUSH PRIVILEGES;

-- Verificar usuarios
SELECT user, host FROM mysql.user WHERE user = 'webapp_user';
```

### Solución 4: Configurar Firewall

```bash
# En el servidor MySQL (192.168.23.136)
# Para UFW:
sudo ufw allow 3306

# Para iptables:
sudo iptables -A INPUT -p tcp --dport 3306 -j ACCEPT
sudo iptables-save > /etc/iptables/rules.v4

# Para firewalld:
sudo firewall-cmd --permanent --add-port=3306/tcp
sudo firewall-cmd --reload
```

### Solución 5: Usar MySQL Local con Docker Compose

Si no puedes acceder al servidor remoto, puedes usar MySQL local:

```yaml
# Agregar a docker-compose.yml
version: '3.8'
services:
  web:
    build: .
    ports:
      - "8080:80"
    depends_on:
      - mysql
    environment:
      - DB_HOST=mysql
      - DB_USER=webapp_user
      - DB_PASS=your_secure_password_here
      - DB_NAME=webapp_db

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: webapp_db
      MYSQL_USER: webapp_user
      MYSQL_PASSWORD: your_secure_password_here
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./db/schema.sql:/docker-entrypoint-initdb.d/schema.sql

volumes:
  mysql_data:
```

## 🔧 Comandos de Diagnóstico Rápido

### Desde el Host Docker:
```bash
# Probar conectividad básica
docker exec php-webapp-container ping -c 3 192.168.23.136

# Probar conexión MySQL
docker exec php-webapp-container php -r "
try {
    \$mysqli = new mysqli('192.168.23.136', 'webapp_user', 'your_secure_password_here', 'webapp_db');
    echo \$mysqli->connect_error ? 'ERROR: ' . \$mysqli->connect_error : 'ÉXITO: Conectado';
} catch (Exception \$e) {
    echo 'EXCEPCIÓN: ' . \$e->getMessage();
}
"
```

### Desde el Servidor MySQL:
```bash
# Verificar puertos abiertos
sudo netstat -tlnp | grep :3306

# Verificar logs de MySQL
sudo tail -f /var/log/mysql/error.log

# Probar conexión local
mysql -u webapp_user -p webapp_db
```

## 📋 Lista de Verificación

- [ ] MySQL está ejecutándose en 192.168.23.136
- [ ] bind-address configurado para 0.0.0.0
- [ ] Usuario webapp_user existe con permisos remotos
- [ ] Firewall permite puerto 3306
- [ ] Credenciales correctas en .env
- [ ] Red Docker puede acceder al host

## 🚀 Pasos Inmediatos Recomendados

1. **Contactar al administrador** del servidor 192.168.23.136 para verificar:
   - Estado del servicio MySQL
   - Configuración de firewall
   - Permisos de usuario

2. **Alternativa temporal**: Usar MySQL local con Docker Compose

3. **Verificar credenciales**: Confirmar que la contraseña en .env es correcta

## 📞 Comandos de Emergencia

Si necesitas una solución rápida, ejecuta:

```bash
# Opción 1: Usar MySQL local
docker-compose down
# Editar docker-compose.yml para incluir MySQL
docker-compose up -d

# Opción 2: Cambiar a localhost si MySQL está en el mismo host
# Editar .env y cambiar DB_HOST=localhost
```

---
**Nota:** Este documento fue generado automáticamente basado en el diagnóstico del error "Connection refused" en la aplicación PHP.