# P0.0 - ASIXc2AC-G04: MVP conexión web y base de datos

En este documento se explica cómo se ha montado la parte de base de datos del MVP en la máquina B-NCC, cómo se ha comprobado que la web se conecta y cómo se ha dejado la estructura del proyecto en GitHub.

## 1. Instalación de MariaDB

Primero se actualizan los repositorios y se instala `mariadb-server` con `apt`. En este caso ya estaba instalado en su última versión, así que no se cambió nada.

```bash
sudo apt update && sudo apt install -y mariadb-server
```

![Actualización del sistema e instalación de MariaDB](img/ACTUALITZACIO%20SISTEMA.png)

## 2. Configuración del servidor

Se edita el fichero `/etc/mysql/mariadb.conf.d/50-server.cnf` y se cambia `bind-address` a `0.0.0.0`. Así MariaDB acepta conexiones desde otras máquinas de la red y no solo desde localhost.

```bash
sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf
```

![Configuración de bind-address en 50-server.cnf](img/CONF%20SERVER.CONF.png)

## 3. Comprobación del puerto 3306

Después de guardar el cambio se reinicia el servicio y se mira si el puerto 3306 está escuchando. Sale en `0.0.0.0:3306`, así que el cambio se ha aplicado bien.

```bash
sudo systemctl restart mariadb
sudo ss -tulpn | grep 3306
```

![Comprobación del puerto 3306 de MariaDB](img/comprovacio%20port%20maria%20db.png)

## 4. Creación del usuario del sistema

Se crea el usuario `bchecker` con shell bash y se le pone contraseña con `chpasswd`. Se añade al grupo `sudo`.

La primera vez dio error: el sistema rechazó la contraseña porque contenía el nombre del usuario, y además el usuario ya se había creado. Se repitió el proceso con una contraseña válida y con `id bchecker` se comprobó que quedaba dentro del grupo `sudo`.

```bash
sudo useradd -m -s /bin/bash bchecker
echo "bchecker:<contraseña>" | sudo chpasswd
sudo usermod -aG sudo bchecker
id bchecker
```

![Creación del usuario bchecker](img/CONF%20USUARI%20BECKER.png)

## 5. Creación de la base de datos

Desde la terminal se crea la base de datos `mvp_db` con una tabla `test` y una fila de prueba. Esa fila es la que luego muestra la web.

También se crea el usuario `bchecker` de MariaDB, con acceso desde cualquier host (`%`), y se le dan permisos sobre `mvp_db`.

```bash
sudo mysql -e "CREATE DATABASE IF NOT EXISTS mvp_db;"
sudo mysql -e "CREATE TABLE IF NOT EXISTS mvp_db.test (id INT PRIMARY KEY AUTO_INCREMENT, estado VARCHAR(100));"
sudo mysql -e "INSERT INTO mvp_db.test (estado) VALUES ('Conexion BBDD a Web EXITOSA');"
sudo mysql -e "CREATE USER IF NOT EXISTS 'bchecker'@'%' IDENTIFIED BY '<contraseña>';"
sudo mysql -e "GRANT ALL PRIVILEGES ON mvp_db.* TO 'bchecker'@'%';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

![Creación de la base de datos, la tabla y el usuario](img/creacio%20bbdd.png)

## 6. Verificación del usuario

Para confirmar que el usuario existe se consulta la tabla `mysql.user`. Aparece `bchecker` con host `%`, que es lo esperado.

```bash
sudo mysql -e "SELECT User, Host FROM mysql.user WHERE User='bchecker';"
```

![Consulta SQL del usuario bchecker](img/consulta%20sql.png)

## 7. Acceso a la web

Desde el navegador se entra en `http://192.168.10.10/index.php`. La página muestra el mensaje "MVP FUNCIONANT CORRECTAMENT" y "Conexion BBDD a Web EXITOSA", que viene de la tabla creada antes. Con esto queda comprobado que la web llega a la base de datos.

![Acceso web al MVP](img/acces%20web.png)

## 8. Repositorio en GitHub

El repositorio `P0.0-ASIXc2AC-G04` es público y de momento solo tiene el `README.md` del primer commit.

![Repositorio en GitHub](img/conf%20git.png)

## 9. Estructura de carpetas

En el equipo R-NCC se clona el repositorio y se crean las carpetas `router`, `web` y `bbdd`. Git no sube carpetas vacías, así que se añade un fichero `.gitkeep` en cada una. También se edita el `README.md`.

```bash
mkdir -p router web bbdd
touch router/.gitkeep web/.gitkeep bbdd/.gitkeep
```

![Creación de la estructura de carpetas](img/creacio%20repo%20git.png)

## 10. Commit y push

Se hace el commit con los cambios y se sube a la rama `main`. Git pide usuario y contraseña de GitHub. El push termina bien (`71c0085..ee59709`).

```bash
git add .
git commit -m "docs/structure: crea la estructura de carpetas y el README inicial"
git push origin main
```

![Push al repositorio de GitHub](img/push%20git.png)
