# Infraestructura de Red y Servicios — Servidor `F-NCC` (Módulo 0379)

Despliegue, automatización, alta disponibilidad y auditoría de seguridad de los servicios troncales de red en el nodo **F-NCC** (Ubuntu Server).

## Arquitectura y Direccionamiento IP

| Elemento / Nodo | Dirección IP / Rango | Descripción |
| :--- | :--- | :--- |
| **Red Interna** | `192.168.10.0/24` | Subred local bajo el dominio `intranet.local` |
| **Puerta de Enlace** | `192.168.10.1` | Gateway de salida |
| **Servidor `F-NCC`** | `192.168.10.30` | DHCP, DNS, FTP (`chroot`) y SSH Hardening |
| **Rango DHCP** | `192.168.10.50` – `192.168.10.100` | Asignación dinámica de direcciones a clientes |
| **Servidor BBDD** | *Nodo independiente* | Alojamiento del motor MySQL |

## Contenido del Repositorio

* [`dhcp/dhcpd.conf`](dhcp/dhcpd.conf) — Configuración de subred, rango dinámico y parámetros DNS del servidor DHCP.
* [`dns/`](dns/) — Archivos de zona directa (`db.intranet.local`), definición de zonas (`named.conf.local`) y opciones con logging dedicado (`named.conf.options`).
* [`ftp/vsftpd.conf`](ftp/vsftpd.conf) — Configuración del servidor FTP con aislamiento de usuarios locales (`chroot_local_user=YES`).
* [`ssh/sshd_config`](ssh/sshd_config) — Configuración de acceso remoto con autenticación exclusiva por clave pública (`ed25519`).
* [`systemd/`](systemd/) — Políticas de autorrecuperación (`Restart=always`) para los demonios DNS y DHCP.
* [`docs/validacion.md`](docs/validacion.md) — **Matriz completa de validación técnica y capturas de pruebas.**
