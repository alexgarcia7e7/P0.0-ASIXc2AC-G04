# Diario de Sesiones y Memoria Técnica del Proyecto de Redes y Servicios

**Asignatura / Módulo:** Administración de Sistemas Operativos en Red y Servicios

**Infraestructura del Proyecto:** Servidores `W-NCC` (Web), `B-NCC` (Base de Datos), `R-NCC` (Router / DHCP / DNS), `F-NCC` (FTP / Git)

**Periodo de Ejecución:** 14 de Octubre – 18 de Noviembre

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
| **28/10** | Documentación | Definición estratégica del problema, objetivos y necesidades del proyecto (apartado 1). | **Completado:** Contexto, perfil del cliente y objetivos documentados. | 
| **03/11** | Documentación | Análisis de mercado comparativo: Debian vs Windows Server, Apache vs Nginx vs IIS, MariaDB vs PostgreSQL, BIND9/ISC DHCP vs Microsoft (apartado 2). | **Completado:** Estudio comparativo y oportunidades identificadas. | 
| **04/11** | Documentación | Justificación técnica de la pila tecnológica: Debian, Router dedicado, Apache + PHP, MariaDB, BIND9 + ISC DHCP, VSFTPD y Git sobre SSH (apartado 3). | **Completado:** Decisiones técnicas justificadas. | 
| **10/11** | Documentación | Plan de prevención de riesgos del entorno informático: ergonomía, fatiga visual, riesgos eléctricos y pausas activas (apartado 4). | **Completado:** Plan de prevención documentado. | 
| **11/11** | Documentación | Riesgos técnicos y contingencia: matriz de riesgos, RPO/RTO, protocolos de restauración y matriz RACI (apartado 5). | **Completado:** Plan de contingencia y responsabilidades definidos. | 
| **17/11** | Documentación | Definición de SLA/SLO, KPI y scripts de validación de rendimiento HTTP, DNS y DHCP (apartado 6). | **Completado:** Criterios de rendimiento y acciones correctoras definidos. | 
| **18/11** | Documentación | Plan de infraestructura: dimensionamiento de VMs, mapa IP, topología, DHCP/DNS, puertos y seguridad en `192.168.10.0/24` (apartado 7). | **Completado:** Inventario y direccionamiento cerrados. | 


# Projecte 0.0 - Sprint 2: Infraestructura TI Centralitzada (NCC)

## Sesión 5: 28/10 – 1. Definició Estratègica del Problema, Objectius i Identificació de Necessitats

### 1.1. Context i Anàlisi Exhaustiu del Problema Inicial

En l'escenari operatiu previ de l'organització (o entorn simulat del projecte), la infraestructura de TI presentava una arquitectura fragmentada, descentralitzada i manca d'estàndards d'alta disponibilitat i gestió centralitzada. Aquesta situació genera colls d'ampolla operatius, deficiències de seguretat i greus ineficiències en el desplegament de serveis essencials.

#### Anàlisi de Causes Arrel

Mitjançant un diagnòstic tècnic detallat, s'han identificat les següents causes arrel que justifiquen la reestructuració completa de la infraestructura:

1. **Descentralització de Serveis i Gestió Aïllada (Silos Tecnològics):**
   * Els serveis de base de dades, allotjament web, assignació d'adreçament IP i resolució de noms operaven de forma desacoblada o mitjançant configuracions estàtiques no estandarditzades. Això impedia una monitorització unificada i multiplicava el temps mitjà de resolució d'incidències (MTTR).

2. **Deficiències de Modelatge i Connectivitat en Base de Dades:**
   * Les versions inicials de la base de dades no disposaven d'una normalització adequada (absència de claus primàries estrictes, restriccions d'integritat referencial inexistent o mal definides), la qual cosa provocava inconsistències en la persistència de les dades d'usuaris i impedia consultes SQL eficients.

3. **Absència d'Automatització en l'Encaminament i Adreçament de Xarxa:**
   * La manca d'un servidor DHCP unificat i correctament integrat obligava a configuracions IP estàtiques manuals en els clients de la xarxa local, augmentant el risc de conflictes d'adreces IP (`192.168.10.x`) i dificultant l'escalabilitat del parc d'equips clients.

4. **Resolució de Noms Ineficient o Dependent de Tercers:**
   * La manca d'una zona de resolució DNS privada local (`/etc/bind/`) amb registres `A` i `PTR` coherents alentia la comunicació inter-servidor (`Web <-> BBDD`) i obligava a recórrer a resolucions mitjançant adreces IP directes en el codi de l'aplicació.

5. **Vulnerabilitats de Seguretat i Gestió de Credencials:**
   * Absència d'aïllament per entorns (`chroot` en FTP no consolidat), polítiques febles d'accés a la base de dades MySQL (ús d'usuaris globals sense limitació de privilegis per host) i manca de control de versions centralitzat mitjançant Git sobre canal segur SSH.

### 1.2. Perfil Detallat del Client i Destinatari Final

La solució dissenyada s'orienta a dos perfils d'usuaris ben diferenciats, garantint l'adaptabilitat de la interfície i la infraestructura a les seves necessitats específiques:

#### A. Usuari Final / Client Corporatiu

* **Perfil:** Empleats, col·laboradors o clients externs de la plataforma que interactuen amb el portal web i el repositori de fitxers.
* **Necessitats Tècniques:**
  * Accés web intuïtiu i ràpid mitjançant domini local (`http://www.ncc.cat` o IP `192.168.10.10`).
  * Gestió autònoma de credencials (registre de compte, autenticació d'inici de sessió i canvi segur de contrasenya).
  * Transferència de fitxers àgil i transparent mitjançant client FTP (connexió segura a `192.168.10.30`).
  * Connectivitat Plug-and-Play a la xarxa local mitjançant assignació automàtica de paràmetres de xarxa via DHCP.

#### B. Equip d'Administració de Sistemes i TI (SysAdmins)

* **Perfil:** Personal tècnic responsable de l'operació, manteniment, seguretat i continuïtat de la infraestructura.
* **Necessitats Tècniques:**
  * Control unificat de la topologia de xarxa (`192.168.10.0/24`).
  * Separació clara de rols en servidors independents: Servidor Web (`W-NCC`: `.10`), Servidor de BBDD (`B-NCC`: `.20`) i Servidor de Xarxa/DNS/DHCP (`R-NCC`: `.30`).
  * Traçabilitat i auditabilitat mitjançant registres d'esdeveniments (*logs*) centralitzats.
  * Desplegament de codi i canvis de configuració auditats a través de Git mitjançant clau pública/privada SSH.

### 1.3. Objectius Quantitatius i Qualitatius del Projecte

Per mesurar amb precisió l'èxit de la implantació, s'estableixen els següents objectius quantificables:

#### Objectius Quantitatius (Mètriques Clau de Rendiment - KPI)

* **Disponibilitat del Servei (Uptime):** Assolir un temps d'activitat de la infraestructura del `99.9%` en entorn de producció simulat.
* **Temps de Resposta Web (HTTP/PHP):** Mantenir un temps mitjà de càrrega de la pàgina `index.php` inferior a `200 ms` per a peticions dins de la LAN.
* **Temps d'Assignació DHCP:** Garantir la concessió d'adreçament IP i paràmetres de xarxa (IP, Màscara, Gateway, DNS) a clients en menys de `2 segons` després de l'inici de la interfície de xarxa.
* **Temps de Resolució DNS:** Assegurar un temps de resposta en la resolució de noms locals (registres directes i inversos) inferior a `50 ms`.
* **Integritat i Seguretat en BBDD:** `0%` de pèrdua de transaccions i temps d'execució de consultes d'autenticació SQL inferior a `50 ms`.

#### Objectius Qualitatius

* **Modularitat i Escalabilitat:** Garantir que la separació física/virtual de serveis permeti escalar o reemplaçar qualsevol dels nodes (`W-NCC`, `B-NCC`, `R-NCC`) sense afectar la resta de la infraestructura.
* **Mantenibilitat:** Estandarditzar la gestió de fitxers de configuració (`vsftpd.conf`, `/etc/bind/`, `dhcpd.conf`) amb documentació exhaustiva i control de versions.

### 1.4. Justificació del Valor Aportat per la Nova Arquitectura Centralitzada

La transició des del model inicial defectuós cap a la nova arquitectura en xarxa aporta un valor estratègic i operatiu substancial:

```text
      +-----------------------------------+
      |            CLIENTS LAN            |
      |      (Assignació DHCP .30)        |
      +-----------------+-----------------+
                        |
                        v
      +-----------------+-----------------+
      |     Router / DNS / DHCP / FTP     |
      |             [ R-NCC ]             |
      |           192.168.10.30           |
      +--------+-----------------+--------+
               |                 |
Resolució DNS  |                 | Consultes SQL /
/ Tràfic Web   |                 | Dades
               v                 v
+-----------------+           +---------------------+
|  Servidor Web   |           | Servidor Base Dades |
|    [ W-NCC ]    |           |      [ B-NCC ]      |
|  192.168.10.10  |<--------->|    192.168.10.20    |
+-----------------+           +---------------------+
```

1. **Aïllament de Responsabilitats i Seguretat (Least Privilege):**
   * En separar la lògica web (`W-NCC`) de la base de dades (`B-NCC`), un compromís de seguretat en la capa de l'aplicació web no compromet directament la integritat de les dades al servidor de base de dades.

2. **Eficiència en la Gestió de Xarxa:**
   * La integració centralitzada de DHCP i DNS al servidor de xarxa (`R-NCC`) elimina l'assignació manual d'IPs, garanteix la coherència de l'espai d'adreçament `192.168.10.0/24` i permet la resolució ràpida per nom de domini de tots els recursos locals.

3. **Robustesa i Persistència de Dades:**
   * La reestructuració completa de l'esquema MySQL, juntament amb la creació d'usuaris específics amb permisos acotats (`bchecker`), prevé fugues d'informació i errors d'integritat referencial.

4. **Control de Canvis i Desplegament Segur:**
   * L'ús de Git acoblat a autenticació SSH simplifica la integració contínua del codi de l'aplicació web, garantint la reversibilitat immediata davant d'errors de codi o desplegament.

## Sesión 6: 03/11 – 2. Anàlisi de Mercat i Estudi Comparatiu de Solucions Existents (RA1)

### 2.1. Introducció i Metodologia d'Anàlisi

Per tal de dissenyar una infraestructura de xarxa i serveis que sigui robusta, escalable, segura i d'alt rendiment, és essencial realitzar un estudi comparatiu del mercat tecnològic actual. L'anàlisi avalua les principals solucions del mercat dividides en quatre capes clau de la infraestructura: Sistemes Operatius de Servidor, Servidors Web, Sistemes de Gestió de Bases de Dades (SGBD) i Serveis de Xarxa Core (DHCP i DNS).

L'avaluació es realitza seguint criteris objectius: rendiment, seguretat, cost de llicenciament, consum de recursos, comunitat/suport i facilitat d'automatització.

### 2.2. Comparativa de Sistemes Operatius de Servidor: Linux (Debian) vs. Windows Server

| Criteri d'Avaluació | Linux (Debian GNU/Linux) | Microsoft Windows Server |
| --- | --- | --- |
| **Model de Llicència i Cost** | Open Source (GPL), sense cost de llicència ni de CALs (*Client Access Licenses*). | Propietari. Cost elevat per nucli (*Core-based*) i requereix llicències CAL per usuari/dispositiu. |
| **Consum de Recursos** | Molt baix. Opció d'execució sense interfície gràfica (CLI), optimitzant RAM i CPU. | Elevat. La interfície gràfica (GUI) i serveis base requereixen més memòria i emmagatzematge. |
| **Seguretat i Aïllament** | Gran modularitat, gestió estricta de permisos POSIX, SELinux/AppArmor i menor superfície d'atac. | Bon sistema de seguretat basat en Active Directory, però amb major superfície d'atac global. |
| **Gestió i Automatització** | Excel·lent via SSH, Bash, Ansible i scripts de xarxa natius. | Molt bona via PowerShell i Windows Admin Center, tot i que dependència de la GUI. |

### 2.3. Comparativa de Servidors Web: Apache HTTP Server vs. Nginx vs. Microsoft IIS

| Criteri d'Avaluació | Apache HTTP Server | Nginx | Microsoft IIS |
| --- | --- | --- | --- |
| **Arquitectura** | Basada en processos/threads (*Multiprocessing Modules* - MPM). | Basada en esdeveniments asíncrons (*Event-driven*). | Basada en fils integrats al nucli de Windows. |
| **Processament de Codi Dinàmic (PHP)** | Excel·lent mitjançant mètodes integrats (`mod_php`) o `PHP-FPM`. | Execució mitjançant `PHP-FPM` (FastCGI). | Execució mitjançant FastCGI sobre entorn Windows. |
| **Fitxers de Configuració Distribuïts** | Suporta `.htaccess` per a configuracions dinàmiques per directori. | No suporta `.htaccess` (tota la configuració resideix al fitxer principal). | Suporta `web.config` per directori. |
| **Facilitat de Configuració** | Altament documentat, modular i molt flexible per a entorns acadèmics/pime. | Alt rendiment en contingut estàtic i proxy invers; configuració més rígida. | Integració directa amb l'ecosistema de Microsoft (.NET). |

### 2.4. Comparativa de Bases de Dades Relacionals: MySQL / MariaDB vs. PostgreSQL

| Criteri d'Avaluació | MySQL / MariaDB | PostgreSQL |
| --- | --- | --- |
| **Tipus i Filosofia** | Relacional (RDBMS) d'alt rendiment i velocitat en operacions de lectura. | Objecte-Relacional (ORDBMS) enfocat a la rigidesa i estàndards SQL complexos. |
| **Velocitat d'Autenticació i Consultes** | Molt ràpida per a consultes `SELECT`, `INSERT` i `UPDATE` senzilles (ideal per a entorns web). | Més lenta en consultes simples, millor en transaccions complexes i grans volums. |
| **Facilitat d'Administració** | Gran senzillesa en la gestió d'usuaris, taules, permisos (`GRANT`) i manteniment. | Major complexitat d'administració, configuració de fitxers `pg_hba.conf` més rígida. |
| **Ecosistema i Suport PHP** | Integració nativa i madura amb PHP via l'extensió `mysqli` / `PDO_MySQL`. | Bones extensions PHP, però menys estesa en solucions web estàndard. |

### 2.5. Comparativa de Serveis de Xarxa Core: ISC DHCP / BIND9 vs. Microsoft DHCP / DNS

| Criteri d'Avaluació | Solució Open Source (ISC DHCP + BIND9) | Solució Microsoft (Windows Server DHCP/DNS) |
| --- | --- | --- |
| **Estàndard de la Indústria** | BIND9 i ISC DHCP són els estàndards històrics i de referència a la xarxa global de l'Internet. | Estàndard integrat en entorns corporatius basats en Active Directory. |
| **Control de Fitxers de Configuració** | Configuració basada en fitxers de text pla (`dhcpd.conf`, `/etc/bind/named.conf`). | Configuració principalment mitjançant la consola MMC de Windows o PowerShell. |
| **Aïllament i Granularitat** | Permet una configuració exacta de registres directes (`A`), inversos (`PTR`) i zones personalitzades. | Alta integració automàtica amb Active Directory, però menys flexible en entorns híbrids/Linux. |
| **Consum de Recursos** | Requeriments de memòria i CPU extremadament baixos (manteniment en dimonis/*daemons*). | Requeriments superiors en estar vinculat als serveis de Windows Server. |

### 2.6. Conclusions de l'Anàlisi de Mercat i Oportunitats Identificades

De l'estudi realitzat se n'extreuen les següents conclusions estratègiques:

1. **Eficiència de Costos i Absència de Vendor Lock-in:** Optar per una infraestructura basada en Debian Linux permet eliminar completament els costos de llicenciament (cap cost de llicència de SO ni de CALs), destinant els recursos a l'optimització tècnica.
2. **Equilibri Entre Rendiment i Simplicitat Operativa:** La combinació d'Apache HTTP Server i MySQL/MariaDB sobre Linux ofereix l'entorn més estabilitzat i documentat del mercat per a aplicacions PHP, garantint un temps de resposta inferior als límits fixats en els KPIs del projecte.
3. **Control Total de la Infraestructura de Xarxa:** L'adopció de BIND9 i ISC DHCP Server permet gestionar el rang `192.168.10.0/24` de manera transparent i directa mitjançant fitxers de configuració versionables, la qual cosa facilita l'auditoria tècnica i l'automatització.

## Sesión 7: 04/11 – 3. Justificació Tècnica i Estratègica de la Pila Tecnològica (RA1)

### 3.1. Arquitectura General del Sistema i Filosofia de Disseny

Després d'avaluar les alternatives del mercat a l'anàlisi comparativa (`[RA1-02]`), s'ha dissenyat una arquitectura multicapa desacoblada basant-nos en el principi de disseny modular, aïllament de responsabilitats (*Separation of Concerns*) i mínim privilegi (*Least Privilege*).

La infraestructura es desplega sobre el segment de xarxa privada `192.168.10.0/24` distribuïda en cinc nodes/equips independents sobre Debian GNU/Linux:

* **Router / Gateway Dedicat:** Enrutador de xarxa encarregat de la sortida a l'exterior i encaminament de paquets mitjançant reenviament d'IP (*IP Forwarding*).
* **Servidor Web (`W-NCC`):** `192.168.10.10` (Apache + PHP 8.x)
* **Servidor de Base de Dades (`B-NCC`):** `192.168.10.20` (MariaDB / MySQL)
* **Servidor de Serveis Core de Xarxa (`R-NCC`):** `192.168.10.30` (DNS BIND9 i ISC DHCP Server)
* **Servidor d'Emmagatzematge i Fitxers (`F-NCC`):** `192.168.10.40` (VSFTPD en entorn aïllat)

Aquesta distribució garanteix que cap servidor exposi serveis innecessaris, reduint dràsticament la superfície d'atac (*blast radius*) i garantint que un possible dany de seguretat o sobrecàrrega al servei FTP no comprometi els serveis crítics de la xarxa (DNS/DHCP), la base de dades ni l'encaminament general.

### 3.2. Justificació del Sistema Operatiu: Debian GNU/Linux

La tria de Debian com a sistema operatiu base per a tots els nodes servidors i l'enrutador es fonamenta en tres pilars tècnics:

1. **Estabilitat i Cicle de Vida (LTS):** Debian és conegut per la seva rigorosa política de proves de programari. Les seves versions *Stable* garanteixen una absència pràcticament total de regressions o fallades de nucli, aspecte crític per a entorns de producció.
2. **Consum Mínim de Recursos (Headless Deployment):** En executar-se en mode només consola (sense interfície gràfica GUI), el consum de memòria RAM en repòs per node és inferior a `256 MB`, permetent destinar la major part del hardware de la màquina host (RAM i vCPU) al processament de peticions web, consultes SQL, resolució DNS i transferència de fitxers.
3. **Seguretat i Gestió de Paquets (`apt`):** El sistema de gestió de paquets garanteix actualitzacions de seguretat immediates i una gestió de dependències neta i audible.

### 3.3. Justificació de l'Enrutador i Encaminament (Router Dedicat)

La inclusió d'un node dedicat exclusivament a les funcions d'enrutament (*gateway*) es justifica pels següents motius:

1. **Aïllament de la Topologia Interna:** Actua com a punt d'entrada i sortida entre la subxarxa privada de treball (`192.168.10.0/24`) i la xarxa externa o d'accés general.
2. **Reenviament de Paquets (IP Forwarding):** Mitjançant l'activació de la directiva del nucli de Linux `net.ipv4.ip_forward = 1` (a `/etc/sysctl.conf`), la màquina Router gestiona el tràfic inter-xarxa de manera lleugera i sense sobrecarregar la resta de servidors d'aplicació.
3. **Control Centralitzat de Tràfic i Seguretat:** Disposar d'un enrutador independent permet aplicar regles de filtratge de paquets (via `iptables` / `nftables`) o traducció d'adreces de xarxa (NAT) en un únic punt d'inspecció de la xarxa.

### 3.4. Justificació de la Capa Web i Desenvolupament: Apache HTTP Server + PHP 8.x

Per a la capa d'aplicació i servidors web al node `W-NCC` (`192.168.10.10`) s'ha seleccionat la combinació Apache HTTP Server i el llenguatge de programació PHP 8.x:

* **Mètode de Processament i Mòduls d'Apache:** La capacitat d'Apache per gestionar mòduls d'autenticació, reescriptura URL (`mod_rewrite`) i gestió de directoris mitjançant fitxers `.htaccess` aporta una gran flexibilitat per al desplegament de l'aplicació web.
* **PHP 8.x com a Motor d'Aplicació:** L'elecció de PHP es basa en la seva nativa i madura integració amb servidors Apache i sistemes de bases de dades relacionals. Les opcions de compilació JIT (*Just-In-Time*) i l'extensió `mysqli` / `PDO` de PHP 8 garanteixen una execució de consultes i temps de generació de pàgina per sota dels `200 ms` exigits en els KPIs del projecte.

### 3.5. Justificació de la Capa de Dades: MariaDB / MySQL

La selecció del motor MariaDB/MySQL al node `B-NCC` (`192.168.10.20`) es justifica pels següents motius:

1. **Rendiment en Lectura/Escriptura Web:** El motor d'emmagatzematge `InnoDB` ofereix suport complet per a transaccions ACID, bloqueig a nivell de fila (*row-level locking*) i claus foranes, assegurant la integritat referencial de les taules d'usuaris sense penalitzar la velocitat de resposta.
2. **Seguretat d'Accés per Xarxa:** MariaDB es configura per escoltar la interfície de xarxa interna (`192.168.10.20`). Es configuren usuaris d'accés estricte (com `bchecker`) amb permisos restringits exclusivament a la IP del servidor web (`192.168.10.10`), denegant qualsevol connexió directa no autoritzada des de la resta de la xarxa.

### 3.6. Justificació dels Serveis Core de Xarxa: BIND9 + ISC DHCP Server

El node `R-NCC` (`192.168.10.30`) es dedica exclusivament a la gestió dels serveis d'infraestructura crítica:

1. **BIND9 (Domain Name System):**
   * Permet la resolució de noms interna (`.ncc.cat`) sense dependre de servidors DNS externs ni de la modificació manual de fitxers `/etc/hosts` a cada equip client.
   * La configuració de la zona directa i la zona inversa (`PTR`) permet que tots els servidors (`W-NCC`, `B-NCC`, `F-NCC` i Router) es resolguin per nom i que les auditories de logs identifiquin clarament els equips per FQDN.

2. **ISC DHCP Server:**
   * Garanteix l'assignació automàtica i dinàmica d'adreçament IP (`192.168.10.0/24`), màscara de xarxa, porta d'enllaç (apuntant a la IP del Router) i adreça DNS (`192.168.10.30`) a qualsevol equip de la LAN (clients Windows i clients Linux).
   * Proporciona un control centralitzat dels temps de concessió (*lease time*) i evita qualsevol risc de conflicte d'IPs duplicades a la xarxa.

### 3.7. Justificació del Servidor d'Emmagatzematge Dedicat: VSFTPD

El node `F-NCC` (`192.168.10.40`) aïlla el servei de transferència de fitxers per motius de seguretat i rendiment:

1. **Aïllament de Serveis Crítics:** En separar el servei FTP dels serveis Core de xarxa (`R-NCC`), s'evita que operacions d'alta intensitat d'E/S de disc o possibles atacs d'accés per força bruta afectin la resolució DNS o l'assignació d'IPs DHCP.
2. **VSFTPD (Very Secure FTP Daemon):** Es tria per ser el dimoni FTP més segur, ràpid i estable en entorns UNIX/Linux.
3. **Entorn d'Aïllament Estricte (`chroot`):** S'estableixen directives de seguretat rigoroses (`chroot_local_user=YES`) per tal que els usuaris que es connectin no puguin recórrer l'arbre de directoris del sistema operatiu, quedant confinats exclusivament a la seva àrea d'emmagatzematge assignada.

### 3.8. Justificació del Control de Versions i Desplegament: Git sobre SSH

Per al manteniment i desplegament del codi font de la web i dels scripts d'infraestructura s'utilitza Git utilitzant el protocol SSH:

* **Autenticació Basada en Claus Asimètriques (RSA/Ed25519):** Elimina l'ús de contrasenyes en text pla per al desplegament de codi, utilitzant un parell de claus pública/privada.
* **Traçabilitat i Reversibilitat:** Qualsevol canvi en el codi o en la configuració queda registrat amb autor, data i descripció, permetent un retorn immediat a un estat funcional anterior (*rollback*) en cas d'error en el desplegament.

## Sesión 8: 10/11 – 4. Pla de Prevenció de Riscos adaptat a l'Entorn Informàtic del Projecte

Aquest pla de prevenció està dissenyat especificament per a l'arquitectura i els perfils d'usuaris descrits. Les mesures s'orienten especialment a l'Equip d'Administració de Sistemes i TI, responsables del manteniment de la infraestructura, i a l'Usuari Final o Client Corporatiu. L'entorn de treball inclou l'operació d'una infraestructura multicapa amb cinc nodes independents sobre el segment de xarxa `192.168.10.0/24`.

### 4.1. Avaluació i Prevenció Ergonòmica

El disseny de la infraestructura centralitzada requereix que els SysAdmins dediquin llargues jornades a la configuració d'equips, d'auditories de registres d'esdeveniments i desplegaments de codi mitjançant SSH i Git.

* **Cadires regulables:** És obligatori l'ús de cadires amb cinc punts de suport, alçada ajustable i respatller amb suport lumbar. Això és crític per al personal tècnic que gestiona tasques de llarga durada, com la normalització de bases de dades i l'escriptura de consultes SQL eficients.
* **Alçada de la pantalla:** La vora superior del monitor ha d'estar a l'alçada dels ulls per evitar la flexió del coll. El monitor s'ha de situar a una distància mínima de 50-70 cm de l'usuari.
* **Postura de treball:** Els usuaris corporatius que interactuen amb el portal web o transfereixen fitxers via FTP han de mantenir els peus totalment recolzats a terra o utilitzar un reposapeus. Els colzes i els genolls han de formar un angle de 90 graus.
* **Perifèrics:** S'han d'ubicar de manera que els canells es mantinguin rectes, recomanant l'ús de reposacanells per evitar la síndrome de túnel carpià durant tasques de documentació o programació.

### 4.2. Gestió de la Fatiga Visual

El consum mínim de recursos d'aquesta arquitectura es basa en l'ús de servidors Debian Linux executats exclusivament en mode consola (sense interfície gràfica o CLI). Aquest treball intensiu amb text sobre fons fosc augmenta significativament l'esforç visual.

* **Optimització del contrast en terminals:** S'han de configurar els emuladors de terminal i els entorns d'escriptori per oferir un contrast suau però llegible reduint la fatiga en l'edició de fitxers de text pla com `dhcpd.conf` o `/etc/bind/named.conf`.
* **Regla del 20-20-20:** Cada 20 minuts d'anàlisi de codi PHP o configuracions en el servidor web Apache, el tècnic ha de desviar la mirada cap a un objecte situat a uns 20 peus durant almenys 20 segons.
* **Il·luminació ambiental:** S'han d'evitar els reflexos directes sobre les pantalles. La il·luminació del lloc de treball ha de ser homogènia, preferiblement perpendicular a les finestres, per evitar enlluernaments mentre es monitoritzen els temps de resposta de l'índex web o l'estat del servidor DHCP.

### 4.3. Prevenció de Riscos Elèctrics i Físics al CPD / Rack

La separació de rols en servidors físics o virtuals independents implica la manipulació de maquinari de xarxa i equips d'encaminament.

* **Cablejat estructurat:** Tot el cablejat de xarxa associat al rang `192.168.10.0/24` i els cables d'alimentació han d'estar canalitzats, guiats i fixes dins del rack o sota el terra tècnic. Això prevé ensopegades i desconnexions accidentals de serveis crítics.
* **Connexió a terra i prevenció de sobrecàrregues:** Tots els nodes servidors i el router dedicat a l'encaminament s'han de connectar a preses amb presa de terra adequada i sistemes d'alimentació ininterrompuda per protegir la integritat de les dades davant de talls de subministrament.
* **Aïllament elèctric:** Les tasques de manteniment en el maquinari on s'executa el motor MariaDB/MySQL o l'Apache HTTP Server només les pot realitzar personal tècnic qualificat amb els equips completament desconnectats de la font d'alimentació.
* **Condicions ambientals del CPD:** S'ha de mantenir una temperatura i ventilació constants en la sala d'equips, especialment necessari perquè màquines com el Servidor de BBDD assoleixin el `99.9%` de disponibilitat exigit sense patir estrès tèrmic.

### 4.4. Recomanacions de Pauses Actives

La naturalesa estàtica i d'alta concentració en la gestió de la xarxa, el control de versions i el desplegament segur de canvis exigeix trencar el sedentarisme.

* **Pautes de descans:** S'estableix l'obligatorietat de fer una pausa de 5 a 10 minuts cada dues hores de feina contínua en pantalla.
* **Estiraments de mans, canells i dits:** Recomanats abans i després de sessions llargues introduint registres directes o inversos al servei DNS o administrant configuracions SQL complexes. Els estiraments han d'incloure moviments circulars dels canells i extensió dels dits.
* **Mobilitat cervical i espatlles:** Fer rotacions suaus del coll i rotacions d'espatlles cap endavant i cap enrere per alliberar la tensió acumulada en la part superior del tronc derivat de l'ús prolongat del teclat i la visualització de logs centralitzats.
* **Descans mental:** Durant les pauses, s'aconsella aixecar-se de la cadira i caminar breument per afavorir la circulació sanguínia cap a les extremitats inferiors.

## Sesión 9: 11/11 – 5. Pla de Prevenció de Riscos Tècnics i Contingència d'Infraestructura

### 5.1. Matriu d'Identificació de Riscos Tècnics, Impacte i Mitigació

Per tal de garantir la continuïtat del negoci i l'alta disponibilitat dels serveis crítics de la xarxa corporativa, s'ha realitzat una anàlisi exhaustiva de les amenaces potencials que poden afectar la infraestructura.

| ID Risc | Categoria | Descripció del Risc / Amenaça | Node Afectat | Probabilitat | Impacte | Nivell de Risc | Mesura de Prevenció / Hardening |
| --- | --- | --- | --- | --- | --- | --- | --- |
| **R-01** | Seguretat | Accés directe no autoritzat al port de BBDD (`3306/tcp`) des de la LAN o l'exterior. | `B-NCC` (`192.168.10.20`) | Alta | Crític | **ALT** | Restricció d'usuaris a MariaDB (`bchecker@192.168.10.10`) i regla de firewall `ufw` que denega el port 3306 a qualsevol IP excepte el servidor web. |
| **R-02** | Disponibilitat | Caiguda o fallida del servei DNS BIND9, impedint la resolució dels dominis `.ncc.cat`. | `DD-NCC` (`192.168.10.50`) | Mitjana | Crític | **ALT** | Validació periòdica de sintaxi (`named-checkconf`), dimoni de supervisió amb reinici automàtic i fallback a DNS externs al router. |
| **R-03** | Disponibilitat | Aturada del servidor DHCP, provocant la impossibilitat d'assignar IP als nous clients de la LAN. | `DH-NCC` (`192.168.10.30`) | Mitjana | Alt | **MITJÀ** | Configuració de leases persistents a `/var/lib/dhcp/dhcpd.leases` i reserva d'IPs estàtiques per a servidors crítics. |
| **R-04** | Integritat | Corrupció de taules a MariaDB per fallida de disc, tancament no abrupte o error de codi PHP. | `B-NCC` (`192.168.10.20`) | Baixa | Crític | **ALT** | Automatització de còpies de seguretat diàries amb `mysqldump` i ús exclusiu del motor transaccional InnoDB. |
| **R-05** | Seguretat | Escapada de directori (*directory traversal*) d'usuaris FTP cap a l'arrel del SO (`/`). | `F-NCC` (`192.168.10.40`) | Mitjana | Alt | **MITJÀ** | Configuració d'entorn aïllat chroot a VSFTPD (`chroot_local_user=YES`) i deshabilitació de shell interactiva (`/usr/sbin/nologin`). |
| **R-06** | Xarxa | Caiguda del node Gateway o pèrdua de la regla de reenviament de paquets (*IP Forwarding*). | `gw-ncc` (`192.168.10.1`) | Baixa | Alt | **MITJÀ** | Persistència de la directiva `net.ipv4.ip_forward = 1` a `/etc/sysctl.conf` i script de verificació de rutes activat via `cron`. |

### 5.2. Mètriques de Continuïtat de Negoci: RPO i RTO

S'estableixen els objectius de recuperació per a cada servei segons la severitat del seu impacte operacional:

* **RPO (Recovery Point Objective):** Quantitat màxima de dades o informació que l'organització es pot permetre perdre en cas de fallida greu.
* **RTO (Recovery Time Objective):** Temps màxim transcorregut des de la detecció del tall del servei fins a la restauració completa de la seva operativitat.

| Servei / Node | RPO Objectiu | RTO Objectiu | Estratègia de Recuperació Assolida |
| --- | --- | --- | --- |
| **Servidor BBDD (`B-NCC`)** | `< 24 hores` | `< 30 minuts` | Restauració de l'últim abocament SQL diari (`mysqldump`) i aplicació de registres de transaccions. |
| **Servidor Web (`W-NCC`)** | `< 1 hora` | `< 15 minuts` | Desplegament immediat del darrer commit de codi mitjançant Git sobre SSH des del repositori central. |
| **Servidors Core (`DD-NCC` / `DH-NCC`)** | `0 hores` (Canvis rars) | `< 10 minuts` | Reposició de fitxers de configuració versionats (`named.conf`, `dhcpd.conf`) i reinici del servei. |
| **Servidor FTP (`F-NCC`)** | `< 24 hores` | `< 20 minuts` | Restauració de l'arbre de directoris d'usuari des del backup d'emmagatzematge secundari. |
| **Router / Gateway (`gw-ncc`)** | `0 hores` | `< 10 minuts` | Re-execució del script de regles de firewall i encaminament `iptables-restore`. |

### 5.3. Protocol Operatiu i Procediments de Restauració (Disaster Recovery)

En cas de fallida crítica o desastre d'infraestructura, l'equip d'administració de sistemes aplicarà els següents procediments estandarditzats de recuperació:

#### Procediment A: Restauració de la Base de Dades (`B-NCC`)

En cas de corrupció de dades o fallida total del node MariaDB:

1. Desplegar o reiniciar la màquina virtual `B-NCC` (`192.168.10.20`).

2. Comprovar que el servei MariaDB s'executa correctament:

   ```bash
   sudo systemctl restart mariadb
   ```

3. Importar el darrer backup SQL funcional realitzat per l'script automatitzat:

   ```bash
   mysql -u root -p mvp_db < /var/backups/mysql/mvp_db_last_backup.sql
   ```

4. Re-aplicar immediatament els permisos de mínima exposició de l'usuari `bchecker`:

   ```sql
   GRANT SELECT, INSERT, UPDATE, DELETE ON mvp_db.* TO 'bchecker'@'192.168.10.10' IDENTIFIED BY 'pirineus';
   FLUSH PRIVILEGES;
   ```

5. Comprovar les regles de firewall `ufw`:

   ```bash
   sudo ufw status | grep 3306
   # Confirmar que només permet accés des de 192.168.10.10
   ```

#### Procediment B: Restauració del Servidor DNS (`DD-NCC`)

En cas de fallida en la resolució de noms del domini `.ncc.cat`:

1. Validar els fitxers de configuració de BIND9 a la màquina `192.168.10.50`:

   ```bash
   sudo named-checkconf /etc/bind/named.conf
   sudo named-checkzone ncc.cat /etc/bind/db.ncc.cat
   ```

2. Si els fitxers estan corruptes, restaurar les darreres còpies de zona des del repositori de backups:

   ```bash
   sudo cp /var/backups/bind/db.ncc.cat /etc/bind/db.ncc.cat
   sudo cp /var/backups/bind/db.10.168.192 /etc/bind/db.10.168.192
   ```

3. Reiniciar i verificar l'estat del dimoni de BIND9:

   ```bash
   sudo systemctl restart bind9
   sudo systemctl status bind9
   ```

4. Executar un `nslookup web.ncc.cat 192.168.10.50` des d'un client per validar la resolució.

#### Procediment C: Restauració del Servidor Web (`W-NCC`)

En cas de caiguda del servidor Apache o alteració del codi font:

1. Comprovar l'estat del servei Apache2 a `192.168.10.10`:

   ```bash
   sudo systemctl restart apache2
   ```

2. Si el codi PHP s'ha malmès, forçar el rollback a l'últim estat estable utilitzant Git:

   ```bash
   cd /var/www/html
   git fetch origin
   git reset --hard origin/main
   ```

3. Verificació de la connexió cap a MariaDB des de la consola de `W-NCC`:

   ```bash
   mysql -h 192.168.10.20 -u bchecker -p
   ```

### 5.4. Assignació de Responsabilitats (Matriu RACI)

Per evitar duplicitats o omissions durant una incidència greu, es defineix la següent matriu de responsabilitats (**RACI:** *Responsible, Accountable, Consulted, Informed*):

| Rol / Tasca d'Infraestructura | SysAdmin 1 (Tu - Desplegament/RA3) | SysAdmin 2 (Companys - Seguretat/RA2) | Usuaris / Clients LAN |
| --- | --- | --- | --- |
| **Manteniment de les regles UFW i Hardening BBDD** | C | R/A | - |
| **Execució i Verificació de Backups diaris** | R | A | - |
| **Desplegament i Manteniment de Codi Web (Git)** | R/A | C | - |
| **Resolució d'incidències a la xarxa Core (DNS/DHCP)** | R | A | - |
| **Notificació de tall del servei (Incidències)** | - | - | R |

* **R (Responsible):** Persona encarregada de realitzar la tasca directament.
* **A (Accountable):** Responsable últim de la tasca que aprova el resultat.
* **C (Consulted):** Persona a qui es demana assessorament o opinió tècnica.
* **I (Informed):** Persona que rep notificació sobre el procés o l'estat.

## Sesión 10: 17/11 – 6. Definició i Criteris de Rendiment, Disponibilitat i SLO/SLA (RA2)

### 6.1. Marc d'Acords de Nivell de Servei (SLA / SLO) i Pressupost d'Error

Per tal de garantir la qualitat del servei (*Quality of Service - QoS*) en la topologia consolidada de 4 nodes (`gw-ncc`, `W-NCC`, `B-NCC` i `F-NCC`), s'estableix un marc formal basat en mètriques quantificables.

* **SLA (Service Level Agreement):** S'estableix un compromís global de disponibilitat de la infraestructura del `99.9%` (Uptime) en règim d'operació continuada (24/7/365).
* **SLO (Service Level Objective):** Llindars mètrics interns que cada servei ha de complir per sota dels límits tolerats.

#### Pressupost d'Error (Error Budget) per a un SLA del 99.9%

El pressupost d'error defineix el temps màxim d'inactivitat no planificada (*downtime*) admès per la infraestructura:

* **Inactivitat màxima anual:** 8 hores, 45 minuts i 57 segons.
* **Inactivitat màxima mensual:** 43 minuts i 49 segons.
* **Inactivitat màxima setmanal:** 10 minuts i 4 segons.

### 6.2. Matriu de Mètriques Clau de Rendiment (KPI) i SLOs Quantitatius

Els criteris de rendiment s'ajusten a la concentració de serveis de xarxa al node `gw-ncc` (Router + BIND9 + ISC DHCP) i a la separació de capes Web (`W-NCC`) i Dades (`B-NCC`):

| Servei / Funció | Node Responsable | IP del Node | Indicador Mètric / SLO Objectiu | Mètode i Eina de Mesurament |
| --- | --- | --- | --- | --- |
| **Càrrega i Procés Web** | `W-NCC` (Apache2 + PHP) | `192.168.10.10` | Temps de resposta HTTP `< 200 ms` | Mesurament del temps total de petició HTTP (TTFB) mitjançant comanda `curl` des d'un client LAN. |
| **Resolució de Noms DNS** | `gw-ncc` (BIND9) | `192.168.10.1` | Temps de resolució `< 50 ms` | Consulta de registres `A`/`PTR` amb l'eina `dig` o `nslookup` mesurant el camp `Query time`. |
| **Assignació de Leasing DHCP** | `gw-ncc` (ISC DHCP) | `192.168.10.1` | Temps de negociació DORA `< 2 s` | Cronometratge de la sol·licitud i concessió IP (Lease) durant la negociació `dhclient` / `ipconfig`. |
| **Consultes a la BBDD** | `B-NCC` (MariaDB) | `192.168.10.20` | Temps d'execució SQL `< 50 ms` | Auditoria de consultes d'autenticació des del servidor web mitjançant el *Slow Query Log* de MariaDB. |
| **Transferència de Fitxers** | `F-NCC` (VSFTPD) | `192.168.10.40` | Autenticació i chroot `< 500 ms` | Test de negociació de control FTP i latència de resposta inicial del daemon VSFTPD. |

### 6.3. Mètodes de Mesurament Objectiu i Scripts de Validació

La verificació quantitativa d'aquests SLOs es realitza de manera objectiva mitjançant les següents comandes d'auditoria:

#### 1. Mesurament del temps de resposta HTTP (`< 200 ms`)

Es valida la latència del servidor Apache2 executant des del client:

```bash
curl -o /dev/null -s -w "Temps total: %{time_total}s | Connect: %{time_connect}s | TTFB: %{time_starttransfer}s\n" http://web.ncc.cat/index.php
```

* **Criteri d'acceptació:** El camp `time_total` ha de retornar un valor estrictament inferior a `0.200` segons.

#### 2. Mesurament de la latència DNS a la màquina integrada (`gw-ncc`) (`< 50 ms`)

Es valida el temps de resposta del daemon BIND9 integrat al router (`192.168.10.1`):

```bash
dig @192.168.10.1 web.ncc.cat | grep "Query time"
```

* **Criteri d'acceptació:** El resultat de `Query time:` ha de mostrar un valor comprès entre `0 ms` i `49 ms`.

#### 3. Mesurament de la concessió de Leasing DHCP (`< 2 s`)

Es valida la velocitat del servei ISC DHCP executat a `gw-ncc` forçant la negociació DORA des del client Linux/Windows:

```bash
# Execució al Client Linux
time (sudo dhclient -r eth0 && sudo dhclient eth0)
```

* **Criteri d'acceptació:** El temps cronometrat per la línia d'ordres ha de ser inferior a `2.0` segons des de la petició *Discover* fins a la recepció del *ACK*.

### 6.4. Pla d'Accions Correctores davant Desviacions de Rendiment

Si els sistemes d'auditoria detecten un incompliment dels SLOs establerts, s'aplicarà el següent protocol de resolució:

1. **Desviació HTTP (`> 200 ms`):** Inspecció de processos actius a `W-NCC`, optimització del script PHP de connexió a MariaDB i revisió de `/var/log/apache2/error.log`.
2. **Desviació DNS (`> 50 ms`):** Execució de `rndc flush` a `gw-ncc` per purgar la memòria cau de BIND9, comprovació de l'ús de CPU al router i reinici del servei amb `systemctl restart bind9`.
3. **Desviació DHCP (`> 2 s`):** Purga de concessions obsoletes al fitxer `/var/lib/dhcp/dhcpd.leases` del node `gw-ncc` i verificació que el pool (`192.168.10.100 - 192.168.10.200`) no presenta col·lisions d'IPs.

## Sesión 11: 18/11 – 7. Pla d'Infraestructura TI Centralitzada NCC: Recursos Virtualitzats, Adreçament IP i Arquitectura de Xarxa `192.168.10.0/24`

### 7.1. Marc de disseny i coherència amb la documentació

Es manté l'arquitectura de 5 nodes independents sobre Debian GNU/Linux *headless* descrita al PDF:

* **Router/Gateway dedicat**
* **W-NCC:** Servidor Web Apache + PHP 8.x `-> 192.168.10.10`
* **B-NCC:** Servidor BBDD MariaDB/MySQL `-> 192.168.10.20`
* **R-NCC:** DNS BIND9 + DHCP ISC `-> 192.168.10.30`
* **F-NCC:** FTP VSFTPD amb chroot `-> 192.168.10.40`

### 7.2. Dimensionament del maquinari virtual dels nodes

Reserves sense *overcommit*, amb aprovisionament gruixut de disc. S'inclou disc de SO, disc de dades, swap i total reservat.

* **Router/GW:** Rol Gateway, NAT, IP Forwarding i filtratge. SO Debian headless. 1 vCPU. 512 MB de RAM. Disc de SO 10 GB. Sense disc de dades. Swap 1 GB. Total disc reservat 11 GB. 2 NIC: WAN i LAN. IP estàtica `192.168.10.1`.
* **W-NCC:** Rol Apache HTTP + PHP 8.x i aplicació web. SO Debian headless. 2 vCPU. 2 GB de RAM. Disc de SO 20 GB. Disc de dades 10 GB per logs i aplicació. Swap 2 GB. Total disc reservat 32 GB. 1 NIC LAN. IP estàtica `192.168.10.10`.
* **B-NCC:** Rol MariaDB/MySQL i dades d'usuaris. SO Debian headless. 2 vCPU. 4 GB de RAM. Disc de SO 20 GB. Disc de dades 40 GB. Swap 4 GB. Total disc reservat 64 GB. 1 NIC LAN. IP estàtica `192.168.10.20`.
* **R-NCC:** Rol BIND9 DNS + ISC DHCP. SO Debian headless. 1 vCPU. 1 GB de RAM. Disc de SO 15 GB. Disc de dades 5 GB per logs i zones. Swap 1 GB. Total disc reservat 21 GB. 1 NIC LAN. IP estàtica `192.168.10.30`.
* **F-NCC:** Rol VSFTPD amb chroot i emmagatzematge. SO Debian headless. 1 vCPU. 1 GB de RAM. Disc de SO 15 GB. Disc de dades 45 GB per dades FTP. Swap 2 GB. Total disc reservat 62 GB. 1 NIC LAN. IP estàtica `192.168.10.40`.

**Total de la infraestructura:** 7 vCPU, 8.704 MB de RAM (aproximadament 8,5 GB), 80 GB de disc de SO, 100 GB de disc de dades, 10 GB de swap, 190 GB de disc total reservat i 6 NIC.

#### Dimensionament recomanat de l'amfitrió virtualitzador

* **vCPU físiques o lògiques:** 8 vCPU mínim.
* **RAM amfitrió:** 16 GB mínim.
* **Emmagatzematge:** 256 GB SSD/NVMe mínim.
* **Xarxa:** 2 interfícies, WAN i LAN interna virtual.
* **Reserva addicional:** 20-30% per a snapshots, backups i creixement.

### 7.3. Mapa d'adreces IP i franges reservades

* **Xarxa:** `192.168.10.0/24`
* **Màscara:** `255.255.255.0`
* **Gateway:** `192.168.10.1`
* **DNS:** `192.168.10.30`
* **Domini local:** `ncc.cat`
* **Broadcast:** `192.168.10.255`

#### Distribució d'adreces

* `192.168.10.0`: adreça de xarxa, reservada.
* `192.168.10.1`: Router/Gateway, estàtica.
* `192.168.10.2 – 192.168.10.9`: reserva d'infraestructura, estàtica o futur.
* `192.168.10.10`: `W-NCC` Servidor Web, estàtica.
* `192.168.10.11 – 192.168.10.19`: reserva de serveis, estàtica o futur.
* `192.168.10.20`: `B-NCC` Servidor BBDD, estàtica.
* `192.168.10.21 – 192.168.10.29`: reserva de serveis, estàtica o futur.
* `192.168.10.30`: `R-NCC` DNS/DHCP, estàtica.
* `192.168.10.31 – 192.168.10.39`: reserva de serveis, estàtica o futur.
* `192.168.10.40`: `F-NCC` FTP/emmagatzematge, estàtica.
* `192.168.10.41 – 192.168.10.99`: reserva estàtica futura, fora de DHCP.
* `192.168.10.100 – 192.168.10.200`: pool DHCP dinàmic per a clients LAN.
* `192.168.10.201 – 192.168.10.254`: reserva futura, fora de DHCP.
* `192.168.10.255`: broadcast, reservada.

**Pool DHCP:** `192.168.10.100 - 192.168.10.200` (101 adreces dinàmiques).  
**Exclusions DHCP:** `192.168.10.1 - 192.168.10.99` i `192.168.10.201 – 192.168.10.254`.

### 7.4. Descripció textual de la topologia i fluxos de trànsit

La topologia es pot representar sense diagrama ASCII mitjançant les connexions següents:

* **Router/GW** connecta amb WAN i switch LAN. Funció: NAT, `ip_forward = 1`, filtratge i sortida a Internet.
* **Switch virtual LAN** connecta amb Router/GW, `W-NCC`, `B-NCC`, `R-NCC`, `F-NCC` i clients LAN. Funció: segmentació física/virtual de `192.168.10.0/24`.
* **W-NCC** connecta amb switch LAN i `B-NCC`. Funció: Apache + PHP 8.x; consultes SQL a `B-NCC`.
* **B-NCC** connecta amb switch LAN i `W-NCC`. Funció: MariaDB/MySQL; només accepta SQL des de `192.168.10.10`.
* **R-NCC** connecta amb switch LAN i clients LAN. Funció: BIND9 DNS i ISC DHCP.
* **F-NCC** connecta amb switch LAN i clients LAN. Funció: VSFTPD amb `chroot_local_user=YES`.
* **Clients LAN** connecten amb switch LAN i `R-NCC`. Funció: obtenen IP del pool DHCP `.100-.200`.

#### Flux de xarxa esperat

1. Els clients LAN arrenquen i sol·liciten IP per DHCP a `192.168.10.30`.
2. El servidor DHCP entrega IP del pool `192.168.10.100 - 192.168.10.200`, màscara `255.255.255.0`, gateway `192.168.10.1`, DNS `192.168.10.30` i domini `ncc.cat`.
3. El client accedeix a `http://www.ncc.cat` o `http://192.168.10.10`.
4. `W-NCC` consulta `B-NCC` per SQL a `192.168.10.20:3306`, restringit a la IP `192.168.10.10`.
5. `R-NCC` resol noms interns i registres inversos `PTR`.
6. `F-NCC` ofereix FTP a `192.168.10.40` amb aïllament `chroot`.
7. El Router/GW aplica NAT i reenviament IP cap a WAN.

### 7.5. Paràmetres DHCP del segment

```conf
subnet 192.168.10.0 netmask 255.255.255.0 {
  range 192.168.10.100 192.168.10.200;
  option routers 192.168.10.1;
  option domain-name-servers 192.168.10.30;
  option domain-name "ncc.cat";
  option broadcast-address 192.168.10.255;
  default-lease-time 43200; # 12 h
  max-lease-time 86400;     # 24 h
}
```

No s'inclouen al rang DHCP: `.1`, `.10`, `.20`, `.30`, `.40` ni les reserves `.2-.99` i `.201-.254`.

### 7.6. Zona DNS i resolució interna

#### Registres recomanats

* `gw-ncc.ncc.cat` tipus `A` valor `192.168.10.1`
* `w-ncc.ncc.cat` tipus `A` valor `192.168.10.10`
* `www.ncc.cat` tipus `A` o `CNAME` valor `192.168.10.10`
* `b-ncc.ncc.cat` tipus `A` valor `192.168.10.20`
* `r-ncc.ncc.cat` tipus `A` valor `192.168.10.30`
* `f-ncc.ncc.cat` tipus `A` valor `192.168.10.40`

També hi han d'existir registres `PTR` inversos coherents per a `.1`, `.10`, `.20`, `.30` i `.40`.

### 7.7. Matriu de serveis, ports i restriccions

* **Router/GW:** Serveis NAT, forwarding i filtratge. Sense ports propis. Restricció: `net.ipv4.ip_forward = 1`.
* **W-NCC:** Serveis Apache i PHP. Ports `80/tcp`, `443/tcp` i `22/tcp` admin. Restricció: accés web LAN.
* **B-NCC:** Serveis MariaDB/MySQL. Port `3306/tcp`. Restricció: només des de `192.168.10.10`.
* **R-NCC:** Serveis BIND9 i ISC DHCP. Ports `53/tcp-udp`, `67/udp`, `68/udp` i `22/tcp`. Restricció: DNS/DHCP LAN.
* **F-NCC:** Servei VSFTPD. Ports `21/tcp`, rang passiu FTP i `22/tcp` admin. Restricció: `chroot_local_user=YES`.

### 7.8. Criteris de seguretat i bones pràctiques

* `B-NCC` ha d'escoltar a `192.168.10.20` i limitar l'usuari d'aplicació, per exemple `bchecker`, a connexions des de `192.168.10.10`.
* `F-NCC` ha d'usar `chroot_local_user=YES` per confinar els usuaris FTP.
* `R-NCC` centralitza DNS i DHCP per evitar configuració manual d'IPs als clients.
* Router/GW aplica NAT i `ip_forward = 1` mitjançant `/etc/sysctl.conf`.
* Git sobre SSH amb claus pública/privada per al desplegament i la traçabilitat.
* Els servidors han de tenir IP estàtica fora del pool DHCP per evitar conflictes.

Amb això queda definit l'inventari de recursos virtualitzats, l'adreçament estàtic, el pool dinàmic i la topologia completa sota `192.168.10.0/24`.
