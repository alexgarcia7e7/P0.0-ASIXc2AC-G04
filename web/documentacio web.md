Desplegament d'un MVP Web connectat a Base de Dades (Entorn DMZ/Intranet)
Aquest repositori conté la documentació tècnica del desplegament d'una infraestructura de xarxa segmentada i la configuració d'un servidor web (Nginx + PHP) que consumeix dades d'un servidor de base de dades (MySQL).

L'objectiu és validar la connectivitat entre una zona desmilitaritzada (DMZ) i una xarxa interna (Intranet) per servir una aplicació web que llegeix dades públiques (OpenData de Barcelona).

Index
Topologia i Arquitectura de Xarxa

Preparació de l'Entorn i Actualització

Configuració de Xarxa i Connectivitat

Gestió d'Usuaris i Seguretat

Desenvolupament de l'Aplicació (Troubleshooting)

Aplicació Final i Estilització

1. Topologia i Arquitectura de Xarxa
El disseny de la xarxa separa els serveis exposats a l'exterior dels serveis crítics interns:

WAN / NAT: Sortida a Internet.

R-NCC (Router): Encaminador amb interfícies eth0 (WAN), eth1 (DMZ) i eth2 (Intranet).

DMZ Zone (172.22.5.0/24): Zona exposada. Alberga el servidor web W-NCC (Nginx + PHP, IP 172.22.5.10) i el servidor FTP F-NCC.

Intranet Zone (172.22.10.0/24): Zona segura. Alberga el servidor de base de dades B-NCC (MySQL, IP 172.22.10.10), el servidor DNS/DHCP i els clients.

Regla de firewall: S'ha de permetre el tràfic des de la DMZ cap a la Intranet específicament pel Port 3306 (SQL).

https://img/Diagrama%20de%20Arquitectura%20de%20Red%20y%20Sistemas.png

Nota de laboratori: Tot i que el diagrama teòric mostra la xarxa 172.22.x.x, les adreces IP utilitzades a les comandes d'execució d'aquest document pertanyen a la xarxa 192.168.10.0/24 degut a la configuració de l'entorn de proves (IsardVDI).

2. Preparació de l'Entorn i Actualització
Ens connectem al servidor W-NCC i actualitzem el llistat de paquets del sistema operatiu (Ubuntu) per assegurar-nos que tenim les últimes versions dels serveis (Nginx, PHP, etc.).

bash
sudo apt update
https://img/actualitzacio%20sistema.png

3. Configuració de Xarxa i Connectivitat
Per poder comunicar el servidor web (W-NCC) amb la base de dades (B-NCC), configurem manualment la targeta de xarxa enp2s0:

bash
# 1. Assignem la IP estàtica
sudo ip addr add 192.168.10.10/24 dev enp2s0

# 2. Aixequem la interfície
sudo ip link set enp2s0 up

# 3. Afegim la ruta per defecte (Gateway)
sudo ip route add default via 192.168.10.1
https://img/conexion%20192.168.10.10.png

Un cop configurada la xarxa, comprovem que hi ha comunicació directa amb el servidor de Base de Dades (192.168.10.20):

bash
ping -c 3 192.168.10.20
https://img/comprovacio%20ping.png

4. Gestió d'Usuaris i Seguretat
Perquè l'aplicació web pugui accedir a la base de dades, creem un usuari específic al sistema i li assignem una contrasenya.

bash
# Creem l'usuari amb directori home i shell de bash
sudo useradd -m -s /bin/bash bchecker
https://img/usuari%20behecker.png

bash
# Assignem la contrasenya
sudo passwd bchecker
Nota: El sistema mostra un avís BAD PASSWORD perquè la contrasenya (bchecker121) conté el nom de l'usuari. En entorns de producció això no estaria permès, però en aquest entorn de proves s'ha forçat l'actualització.
https://img/contrasenya%20behecker.png

5. Desenvolupament de l'Aplicació (Troubleshooting)
Fase 1: Primer contacte i error de xarxa
Inicialment, es va crear un script senzill a /var/www/html/index.php i es va provar amb curl des del propi servidor. El resultat va ser un error crític degut a la manca de rutes (abans de configurar la xarxa):

bash
curl http://localhost/index.php
# Resultat: Error de connexió amb B-NCC: No route to host
https://img/creacio%20index.php.png

Fase 2: Connexió bàsica reeixida
Un cop solucionada la xarxa (Pas 3), actualitzem el codi PHP per connectar-nos mitjançant la llibreria mysqli. Aquest codi fa una consulta a la taula test.

php
<?php
$host = "192.168.10.20";
$user = "bchecker";
$pass = "bchecker121"; 
$db   = "mvp_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo "<h1 style=\"color:red;\">Error de connexió amb B-NCC: " . $conn->connect_error . "</h1>";
} else {
    // Consulta d'extensió per validar la connexió
    $result = $conn->query("SELECT estado FROM test WHERE id=1");
    $row = $result->fetch_assoc();
    echo "<h1 style=\"color:green;\">MVP FUNCIONANT CORRECTAMENT</h1>";
    echo "<h3>Missatge des de B-NCC: " . $row['estado'] . "</h3>";
}
?>
https://img/creacio%20index.php%20bueno.png

6. Aplicació Final i Estilització
Finalment, es va desenvolupar l'aplicació web definitiva. Aquesta versió inclou:

Desactivació d'errors automàtics de mysqli (mysqli_report(MYSQLI_REPORT_OFF)) per controlar-los manualment.

Etiquetes HTML i CSS incrustat per donar format de taula professional a les dades.

Tractament de errors i estats buits (si la taula no té dades).

Codi final (/var/www/html/index.php)
Es va injectar el codi mitjançant una comanda cat << "EOF" per evitar problemes amb els caràcters especials de l'HTML.
https://img/configuracio%20index.php.png
https://img/canvi%20contrasenyes%20php.png

La consulta SQL final ($conn->query()) retorna un llistat d'equipaments educatius (OpenData BCN).

Validació Final
Si accedim a la web via navegador (http://localhost/index.php), veiem que la connexió és correcta (banner verd) i que la taula es renderitza correctament amb les dades de la base de dades B-NCC.

https://img/acces%20a%20la%20web.png
