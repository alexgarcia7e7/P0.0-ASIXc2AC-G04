# Diario de Sesiones y Memoria Técnica del Proyecto de Redes y Servicios

**Asignatura / Módulo:** Administración de Sistemas Operativos en Red y Servicios

**Infraestructura del Proyecto:** Servidores `W-NCC` (Web), `B-NCC` (Base de Datos), `R-NCC` (Router / DHCP / DNS), `F-NCC` (FTP / Git)

**Periodo de Ejecución:** 14 de Octubre – 27 de Octubre

## Resumen de Direccionamiento de Red y Servidores

| Servidor / Elemento | Nombre Host | Interfaz | Dirección IP | Función Principal | 
| ----- | ----- | ----- | ----- | ----- | 
| **Servidor Web** | `W-NCC` | `eth0` | `192.168.10.10` | Apache2, PHP 8.x, Aplicación Web | 
| **Servidor BBDD** | `B-NCC` | `eth0` | `192.168.10.20` | MySQL / MariaDB Server | 
| **Servidor DNS/DHCP** | `R-NCC` | `eth0` / `eth1` | `192.168.10.30` | Servidor DNS BIND9, DHCP Server | 
| **Servidor Archivos/Git** | `F-NCC` | `eth0` | `192.168.10.40` | VSFTPD, Repositorio Git Centralizado | 
| **Subred Local (LAN)** | \- | \- | `192.168.10.0/24` | Red interna de producción del proyecto | 

## Sesión 1: 14/10 – Despliegue Inicial de Prueba (Web de Test y BBDD Errónea)

### 1. Contexto y Objetivos de la Sesión

El objetivo inicial del equipo en esta sesión fue poner en marcha una primera versión funcional ("Proof of Concept") de la plataforma. Se pretendía crear una interfaz web simple en `W-NCC` para la recolección de usuarios y desplegar una base de datos preliminar en `B-NCC`.

### 2. Desarrollo Técnico y Código Implementado

#### A. Página Web de Test (`W-NCC`)

Se creó un script PHP básico mezclado con HTML para validar el motor web Apache y verificar la capacidad de procesamiento PHP.

* **Fichero:** `/var/www/html/index.php`

```php
<?php
// Script de prueba inicial - 14/10
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Página de Prueba del Proyecto NCC</h1>";

$db_host = "192.168.10.20";
$db_user = "root";
$db_pass = "1234";
$db_name = "test_db";

// Intento de conexión con mysqli
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("<p style='color:red;'>Error de conexión a la BBDD: " . mysqli_connect_error() . "</p>");
} else {
    echo "<p style='color:green;'>Conexión establecida con éxito a la Base de Datos.</p>";
}

// Formulario simple de captura de datos
?>
<form action="index.php" method="POST">
    <label>Usuario:</label><br>
    <input type="text" name="nombre"><br><br>
    <label>Contraseña:</label><br>
    <input type="password" name="clave"><br><br>
    <input type="submit" name="enviar" value="Guardar en BBDD">
</form>

<?php
if (isset($_POST['enviar'])) {
    $u = $_POST['nombre'];
    $p = $_POST['clave'];
    
    // Inserción directa sin sanitizar (Vulnerable y con fallos de tipo)
    $sql = "INSERT INTO usuarios VALUES ('$u', '$p')";
    $res = mysqli_query($conn, $sql);
    
    if ($res) {
        echo "Usuario insertado.";
    } else {
        echo "Error SQL: " . mysqli_error($conn);
    }
}
?>
```

#### B. Estructura Inicial Defectuosa de la BBDD (`B-NCC`)

Se intentó crear la base de datos de manera rápida mediante la línea de comandos de MySQL sin la planificación adecuada del modelo entidad-relación.

```sql
-- Script ejecutado el 14/10 (ESTRUCTURA INCORRECTA)
CREATE DATABASE test_db;
USE test_db;

CREATE TABLE usuarios (
    nombre varchar(20),
    clave varchar(20)
);
```

### 3. Diagnóstico Exhaustivo de los Errores Sufridos

La práctica finalizó con fallos graves de arquitectura y conectividad:

1. **Ausencia de Claves Primarias (`PRIMARY KEY`):**

   * La tabla `usuarios` no poseía ningún campo único como `id` o `username`. Esto permitía la duplicación infinita de registros idénticos y la imposibilidad de relacionar esta tabla con futuras entidades del sistema.

2. **Tipos de Datos e Inseguridad:**

   * Las contraseñas se definieron como `varchar(20)` en texto plano. Esto no solo rompía los estándares de seguridad básicos, sino que impedía almacenar cadenas encriptadas con algoritmos tipo `bcrypt` o `SHA-256`, los cuales requieren al menos 60 caracteres.

3. **Fallo de Conexión Remota en MySQL:**

   * El script `index.php` devolvía el siguiente error al intentar acceder a la BBDD:

     ```text
     Warning: mysqli_connect(): (HY000/2003): Can't connect to MySQL server on '192.168.10.20' (111 "Connection refused")
     ```

   * **Causa raíz:** MariaDB/MySQL estaba configurado por defecto para escuchar únicamente en `127.0.0.1` (`bind-address`), bloqueando la conexión remota desde el servidor web `W-NCC` (`192.168.10.10`). Además, el usuario `root` no tenía privilegios de acceso remoto habilitados desde la subred `192.168.10.0/24`.

## Sesión 2: 20/10 – Reestructuración de la BBDD y Configuración del Router

### 1. Contexto y Objetivos de la Sesión

Resolver por completo la deuda técnica generada el 14/10. Esto exigió reconstruir la base de datos bajo estándares profesionales (esquema relacional estricto) y configurar los privilegios de red del usuario gestor (`becker`). En paralelo, se inició la configuración del equipo `R-NCC` como enrutador principal de la red.

### 2. Desarrollo Técnico y Código Implementado

#### A. Reestructuración de la BBDD y Creación del Usuario Gestor (`B-NCC`)

Se eliminó la base de datos previa y se ejecutó la DDL definitiva, aplicando claves primarias auto-incrementales, tipos de datos correctos, campos de auditoría y almacenamiento seguro de claves.

```sql
-- Script de corrección ejecutado en MariaDB (B-NCC) - 20/10
DROP DATABASE IF EXISTS ncc_db;
CREATE DATABASE ncc_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ncc_db;

-- Tabla de Usuarios corregida
CREATE TABLE tbl_usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    rol ENUM('admin', 'user', 'technician') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de Sesiones/Auditoría para relaciones futuras
CREATE TABLE tbl_logins (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    ip_conexion VARCHAR(45) NOT NULL,
    fecha_acceso DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES tbl_usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Creación de usuario administrador de la aplicación 'becker' con privilegios remotos
CREATE USER 'becker'@'%' IDENTIFIED BY 'Becker_Secure_Pass2026!';
GRANT ALL PRIVILEGES ON ncc_db.* TO 'becker'@'%';
FLUSH PRIVILEGES;
```

Para solucionar la escucha en la interfaz de red, se editó el fichero de configuración del servidor MySQL:

* **Fichero:** `/etc/mysql/mariadb.conf.d/50-server.cnf`

```ini
# Modificación de bind-address para escuchar en la red local
bind-address = 0.0.0.0
```

* **Comando de reinicio:** `systemctl restart mariadb`

#### B. Configuración del Router de Red (`R-NCC`)

Se configuraron las dos interfaces de red del servidor `R-NCC`: `eth0` (conectada a Internet / WAN) y `eth1` (conectada a la LAN interna `192.168.10.0/24`).

* **Fichero:** `/etc/network/interfaces`

```ini
# Interfaz WAN
auto eth0
iface eth0 inet dhcp

# Interfaz LAN estática
auto eth1
iface eth1 inet static
    address 192.168.10.30
    netmask 255.255.255.0
    network 192.168.10.0
    broadcast 192.168.10.255
```

* **Habilitación de IP Forwarding y Reglas Iptables:**

```bash
# Activar reenvío de paquetes en el kernel
sysctl -w net.ipv4.ip_forward=1
echo "net.ipv4.ip_forward=1" >> /etc/sysctl.conf

# Limpieza de reglas previas de iptables
iptables -F
iptables -t nat -F

# Regla de Masquerading (NAT) para salir a Internet desde la LAN
iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
iptables -A FORWARD -i eth1 -o eth0 -j ACCEPT
iptables -A FORWARD -i eth0 -o eth1 -m state --state RELATED,ESTABLISHED -j ACCEPT

# Guardar reglas persistentes
apt-get install -y iptables-persistent
iptables-save > /etc/iptables/rules.v4
```

### 3. Verificación de Resultados

Se ejecutaron consultas SQL de prueba mediante el cliente MySQL remoto desde el servidor web para comprobar el acceso exitoso de `becker`:

```bash
# Prueba de conexión desde W-NCC (192.168.10.10) hacia B-NCC (192.168.10.20)
mysql -h 192.168.10.20 -u becker -p'Becker_Secure_Pass2026!' ncc_db -e "SHOW TABLES;"
```

**Resultado:** La conexión fue aceptada y devolvió `tbl_usuarios` y `tbl_logins` sin retrasos ni bloqueos.

## Sesión 3: 21/10 – Conexión Web-BBDD, Entorno Git/FTP y Despliegue Inicial DHCP/DNS

### 1. Contexto y Objetivos de la Sesión

Integrar el backend PHP con la nueva arquitectura de base de datos MySQL utilizando **PDO** (PHP Data Objects) para mayor seguridad. Configurar el servidor de archivos `F-NCC` con **VSFTPD** e integración **SSH/Git** para el control de versiones. Desplegar los servicios base de **DHCP** y **DNS** en el servidor `R-NCC` (`192.168.10.30`).

### 2. Desarrollo Técnico y Código Implementado

#### A. Conexión Web Estructurada con PDO (`W-NCC`)

Se refactorizó completamente la aplicación PHP utilizando sentencias preparadas contra inyecciones SQL.

* **Fichero:** `/var/www/html/db.php` (Conexión PDO)

```php
<?php
$host = '192.168.10.20';
$db   = 'ncc_db';
$user = 'becker';
$pass = 'Becker_Secure_Pass2026!';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
```

* **Fichero:** `/var/www/html/canvi_contrasenyes.php` (Módulo de Gestión de Usuarios)

```php
<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $new_password = $_POST['new_password'];

    if (!empty($username) && !empty($new_password)) {
        // Encriptación de contraseña usando Bcrypt
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("UPDATE tbl_usuarios SET password_hash = :pass WHERE username = :user");
        $result = $stmt->execute(['pass' => $hashed_password, 'user' => $username]);

        if ($stmt->rowCount() > 0) {
            $msg = "<p style='color:green;'>La contraseña de $username ha sido actualizada correctamente.</p>";
        } else {
            $msg = "<p style='color:orange;'>No se encontró el usuario o la contraseña es idéntica a la anterior.</p>";
        }
    } else {
        $msg = "<p style='color:red;'>Por favor completa todos los campos.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><title>Cambio Contraseñas - NCC</title></head>
<body>
<h2>Administración de Usuarios - Cambio de Contraseña</h2>
<?= $msg ?? '' ?>
<form method="POST" action="">
    <label>Nombre de Usuario:</label><br>
    <input type="text" name="username" required><br><br>
    <label>Nueva Contraseña:</label><br>
    <input type="password" name="new_password" required><br><br>
    <button type="submit">Actualizar Credenciales</button>
</form>
</body>
</html>
```

#### B. Servidor FTP y Repositorio Git (`F-NCC`)

Se configuró el servidor de ficheros (`192.168.10.40`) y el despliegue del repositorio de código mediante SSH claves públicas/privadas.

* **Fichero:** `/etc/vsftpd.conf` (Servidor FTP Seguro)

```ini
listen=NO
listen_ipv6=YES
anonymous_enable=NO
local_enable=YES
write_enable=YES
local_umask=022
dirmessage_enable=YES
use_localtime=YES
xferlog_enable=YES
connect_from_port_20=YES
chroot_local_user=YES
secure_chroot_dir=/var/run/vsftpd/empty
pam_service_name=vsftpd
rsa_cert_file=/etc/ssl/certs/ssl-cert-snakeoil.pem
rsa_private_key_file=/etc/ssl/private/ssl-cert-snakeoil.key
ssl_enable=NO

# Rango de puertos pasivos
pasv_min_port=10000
pasv_max_port=10100
```

* **Generación de Claves SSH y Configuración del Repositorio Git Bare:**

```bash
# En F-NCC (192.168.10.40): Creación del repositorio Git central
mkdir -p /srv/git/proyecto-ncc.git
cd /srv/git/proyecto-ncc.git
git init --bare

# En W-NCC (192.168.10.10): Generación de pares de claves SSH
ssh-keygen -t rsa -b 4096 -C "w-ncc@ncc.cat" -f ~/.ssh/id_rsa -N ""
ssh-copy-id -i ~/.ssh/id_rsa.pub gituser@192.168.10.40

# Clonado inicial de prueba
git clone gituser@192.168.10.40:/srv/git/proyecto-ncc.git /var/www/html/repo
```

#### C. Configuración Inicial DHCP y DNS (`R-NCC` - `192.168.10.30`)

* **Servidor DHCP (`isc-dhcp-server`):**

  * **Fichero:** `/etc/dhcp/dhcpd.conf`

```text
option domain-name "ncc.cat";
option domain-name-servers 192.168.10.30;

default-lease-time 600;
max-lease-time 7200;

authoritative;

subnet 192.168.10.0 netmask 255.255.255.0 {
    range 192.168.10.50 192.168.10.150;
    option routers 192.168.10.30;
    option broadcast-address 192.168.10.255;
}
```

* **Servidor DNS BIND9:**

  * **Fichero:** `/etc/bind/named.conf.local`

```text
zone "ncc.cat" {
    type master;
    file "/etc/bind/db.ncc.cat";
};

zone "10.168.192.in-addr.arpa" {
    type master;
    file "/etc/bind/db.192.168.10";
};
```

## Sesión 4: 27/10 – Configuración Avanzada de Clientes y Resolución Compleja de Incidencias DHCP/DNS

### 1. Contexto y Objetivos de la Sesión

Integrar los clientes de la LAN para recibir su configuración IP vía DHCP de forma automática y resolver el dominio `www.ncc.cat` mediante el servidor DNS en `192.168.10.30`. Durante esta sesión surgieron graves inconvenientes de red que bloquearon la comunicación del cliente con los servidores.

### 2. Desglose Detallado de los Errores Sufridos el 27/10

#### Error 1: Fallo de Concesión de Dirección IP en Clientes DHCP

* **Síntoma:** Las máquinas clientes no obtenían dirección del rango `192.168.10.50 - 192.168.10.150`. Los clientes tomaban direcciones APIPA (`169.254.x.x`) o fallaban tras caducar el timeout de solicitud.

* **Logs del Servidor (`/var/log/syslog` en `R-NCC`):**

  ```text
  dhcpd[2041]: DHCPDISCOVER from 52:54:00:12:34:56 via eth1: network 192.168.10.0/24: no free leases
  dhcpd[2041]: DHCPDISCOVER from 52:54:00:88:99:ab via eth0: wrong network.
  ```

* **Causas Raíz Identificadas:**

  1. El demonio `isc-dhcp-server` estaba escuchando en la interfaz incorrecta (`eth0` WAN en vez de `eth1` LAN) debido al fichero `/etc/default/isc-dhcp-server`.

  2. Existían tablas de arrendamiento corruptas (`/var/lib/dhcp/dhcpd.leases`) con concesiones bloqueadas de pruebas anteriores.

#### Error 2: Fallos en la Resolución Directa e Inversa en BIND9

* **Síntoma:** Al realizar `nslookup www.ncc.cat` o `ping www.ncc.cat` desde los clientes, el servidor devolvía `NXDOMAIN` o `Server Failure (SERVFAIL)`.

* **Comandos de prueba y salida de error en cliente:**

```text
$ nslookup www.ncc.cat 192.168.10.30
Server:		192.168.10.30
Address:	192.168.10.30#53

** server can't find www.ncc.cat: SERVFAIL
```

* **Causas Raíz Identificadas:**

  1. **Errores de Sintaxis en Zonas BIND9:** Falta de puntos finales `.` en los nombres de dominio cualificados (FQDN) dentro del fichero de zona.

  2. **Errores de Registro PTR:** En la zona inversa `db.192.168.10`, las entradas apuntaban a IPs inconsistentes que no coincidían con la zona directa.

  3. **Incompatibilidad de Permisos:** El usuario `bind` no tenía permisos de lectura sobre `/etc/bind/db.ncc.cat`.

### 3. Pasos de Depuración y Solución de Problemas (Troubleshooting)

#### Paso 1: Corrección de la Interfaz DHCP y Limpieza

Se modificó el archivo de interfaz por defecto del servidor DHCP:

* **Fichero:** `/etc/default/isc-dhcp-server`

```ini
INTERFACESv4="eth1"
INTERFACESv6=""
```

Se limpió el historial de concesiones bloqueadas y se reinició el servicio:

```bash
systemctl stop isc-dhcp-server
echo "" > /var/lib/dhcp/dhcpd.leases
systemctl start isc-dhcp-server
```

#### Paso 2: Reescritura y Corrección Sintáctica de Zonas DNS BIND9

* **Fichero Directo Corregido:** `/etc/bind/db.ncc.cat`

```text
$TTL    604800
@       IN      SOA     ns1.ncc.cat. admin.ncc.cat. (
                              3         ; Serial
                         604800         ; Refresh
                          86400         ; Retry
                        2419200         ; Expire
                         604800 )       ; Negative Cache TTL
;
@       IN      NS      ns1.ncc.cat.
ns1     IN      A       192.168.10.30
W-NCC   IN      A       192.168.10.10
B-NCC   IN      A       192.168.10.20
F-NCC   IN      A       192.168.10.40
www     IN      CNAME   W-NCC.ncc.cat.
```

* **Fichero Inverso Corregido:** `/etc/bind/db.192.168.10`

```text
$TTL    604800
@       IN      SOA     ns1.ncc.cat. admin.ncc.cat. (
                              3         ; Serial
                         604800         ; Refresh
                          86400         ; Retry
                        2419200         ; Expire
                         604800 )       ; Negative Cache TTL
;
@       IN      NS      ns1.ncc.cat.
30      IN      PTR     ns1.ncc.cat.
10      IN      PTR     w-ncc.ncc.cat.
20      IN      PTR     b-ncc.ncc.cat.
40      IN      PTR     f-ncc.ncc.cat.
```

* **Validación de Sintaxis mediante herramientas de BIND9:**

```bash
# Validar la sintaxis global de named.conf
named-checkconf

# Validar la zona directa
named-checkzone ncc.cat /etc/bind/db.ncc.cat

# Validar la zona inversa
named-checkzone 10.168.192.in-addr.arpa /etc/bind/db.192.168.10

# Reinicio de BIND9
systemctl restart bind9
```

### 4. Pruebas Finales de Verificación (Estado del Sistema al Cierre del 27/10)

#### A. Verificación DHCP en Cliente Linux

```bash
# Renovación de IP en cliente
dhclient -r eth0
dhclient eth0

# Comprobación de la IP obtenida
ip addr show eth0
```

**Resultado:** `inet 192.168.10.50/24 brd 192.168.10.255 scope global eth0` (Concesión correcta).

#### B. Verificación de Resolución DNS con `dig` y `nslookup`

```bash
# Consulta Directa
dig @192.168.10.30 www.ncc.cat +short
# Salida esperada: 
# W-NCC.ncc.cat.
# 192.168.10.10

# Consulta Inversa
dig @192.168.10.30 -x 192.168.10.10 +short
# Salida esperada:
# w-ncc.ncc.cat.
```

## Matriz de Resumen y Control de Cambios

| Fecha | Componente | Cambio / Tarea Ejecutada | Estado Final | 
| ----- | ----- | ----- | ----- | 
| **14/10** | `W-NCC` / `B-NCC` | Script PHP test y BBDD preliminar en `192.168.10.20`. | **Error:** Sin claves primarias y conexión rechazada en MySQL. | 
| **20/10** | `B-NCC` / `R-NCC` | Reconstrucción DDL BBDD, usuario `becker`, `bind-address 0.0.0.0` e IP Forwarding/NAT en Router. | **Exitoso:** BBDD accesible y enrutamiento operativo. | 
| **21/10** | `W-NCC` / `F-NCC` | Conexión PDO, script `canvi_contrasenyes.php`, servidor VSFTPD, SSH keys, repo Git, y despliegue inicial DHCP/DNS (`.30`). | **Exitoso:** Código seguro y servicios de ficheros/versiones listos. | 
| **27/10** | `R-NCC` / Clientes | Depuración de interfaz DHCP en `/etc/default/isc-dhcp-server`, corrección de FQDN y registros PTR en BIND9. | **Resuelto:** Clientes reciben IP `192.168.10.50+` y resuelven `www.ncc.cat`. | 
