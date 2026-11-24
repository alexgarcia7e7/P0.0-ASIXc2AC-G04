# Matriz de Validación Técnica — Servidor `F-NCC`

Resultados de las pruebas de automatización, contingencia, monitorización y seguridad ejecutadas sobre la infraestructura.

| ID | Categoría | Prueba / Caso de Uso | Nodo | Procedimiento | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **AUT-01** | Automatización | Persistencia de servicios en el arranque | `F-NCC` | `systemctl is-enabled isc-dhcp-server named vsftpd ssh` | Todos los servicios configurados como `enabled`. | Superada |
| **AUT-02** | Automatización | Arranque automático tras reinicio completo | `F-NCC` | `sudo reboot` + `systemctl is-active ...` | Servicios levantados en estado `active` sin intervención manual. | Superada |
| **CON-01** | Contingencia | Caída brusca de DNS (`named`) y recuperación | `F-NCC` | `sudo kill -9 $(pidof named)` + `Restart=always` | `systemd` regenera el proceso en 3s manteniendo la resolución. | Superada |
| **CON-02** | Contingencia | Caída brusca del motor BBDD (`mysqld`) | Nodo BBDD | `sudo kill -9 $(pidof mysqld)` | Validado en el nodo independiente de base de datos. | Nodo BBDD |
| **CON-03** | Contingencia | Caída brusca de DHCP (`dhcpd`) y recuperación | `F-NCC` | `sudo kill -9 $(pidof dhcpd)` + `Restart=always` | Recuperación automática del demonio tras señal `SIGKILL`. | Superada |
| **MON-01** | Monitorización | Auditoría de sistema, DHCP y SSH | `F-NCC` | `sudo grep -E "dhcpd\|sshd\|named" /var/log/syslog` | Trazabilidad completa de concesiones IP y accesos SSH. | Superada |
| **MON-02** | Monitorización | Registro dedicado de DNS en `/var/log/named/` | `F-NCC` | `sudo tail -n 15 /var/log/named/default.log` | Canal `default_file` operativo registrando eventos de BIND9. | Superada |
| **SEG-01** | Seguridad | Aislamiento `chroot` en VSFTPD | `F-NCC` / Cliente | `ftp 192.168.10.30` -> `pwd` (`/`), `cd /etc` (`550`) | Usuario enjaulado en `/`; acceso denegado al resto del sistema. | Superada |
| **SEG-02** | Seguridad | Mínimo privilegio en MySQL (`becker`) | Nodo BBDD | `SHOW GRANTS FOR 'becker'@'...';` | Validado en el nodo independiente de base de datos. | Nodo BBDD |
| **SEG-03** | Seguridad | Autenticación SSH con claves para Git | `F-NCC` | `git remote -v` + `ssh -T git@github.com` | Autenticación sin contraseña mediante clave SSH `ed25519`. | Superada |

---

## Evidencias Gráficas

### 1. Proves d'Automatització

#### `AUT-01` — Servicios habilitados en el arranque (`enabled`)
<img width="621" height="207" alt="Captura de pantalla de 2026-11-24 15-27-10" src="https://github.com/user-attachments/assets/1d7db613-59d3-47ac-b815-a67105b04cd6" />


#### `AUT-02` — Servicios activos tras reinicio del servidor (`reboot`)
<img width="622" height="150" alt="Captura de pantalla de 2026-11-24 15-27-24" src="https://github.com/user-attachments/assets/83e3926f-c389-456d-b805-8e344c04b2c8" />

---

### 2. Proves de Contingència

#### `CON-01` — Recuperación automática de DNS (`named`) tras `kill -9`
<img width="613" height="367" alt="Captura de pantalla de 2026-11-24 15-27-57" src="https://github.com/user-attachments/assets/fe153c3f-c9bc-40ab-8043-d7cab9a51b45" />


#### `CON-03` — Recuperación automática de DHCP (`isc-dhcp-server`) tras `kill -9`
<img width="613" height="367" alt="Captura de pantalla de 2026-11-24 15-28-19" src="https://github.com/user-attachments/assets/311168c5-e2d8-4269-8a9c-e9fa23743c62" />


---

### 3. Proves de Monitorització

#### `MON-01` — Auditoría de eventos en `/var/log/syslog`
<img width="615" height="601" alt="Captura de pantalla de 2026-11-24 15-28-53" src="https://github.com/user-attachments/assets/0f5946ce-c176-41c1-9359-ffc04cc7541f" />


#### `MON-02` — Registro dedicado de BIND9 en `/var/log/named/default.log`
<img width="616" height="563" alt="Captura de pantalla de 2026-11-24 15-29-08" src="https://github.com/user-attachments/assets/b11ad591-0751-45a6-920c-980b5739e413" />


---

### 4. Proves de Seguretat

#### `SEG-01` — Enjaulamiento `chroot` en VSFTPD (denegación en `/etc`)
<img width="453" height="317" alt="Captura de pantalla de 2026-11-24 15-29-59" src="https://github.com/user-attachments/assets/e78ce330-a3e1-4bac-a7f2-4673d3170496" />


#### `SEG-03` — Autenticación SSH por clave pública en GitHub
<img width="611" height="125" alt="Captura de pantalla de 2026-11-24 15-30-14" src="https://github.com/user-attachments/assets/963d50c2-ec5b-4b80-a046-5a726e2bb6b7" />

