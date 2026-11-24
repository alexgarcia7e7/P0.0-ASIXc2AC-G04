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

## Servicios Desplegados

| Servicio | Demonio | Carpeta y Documentación | Archivo Principal |
| :--- | :--- | :--- | :--- |
| **DHCP** | `isc-dhcp-server` | [`dhcp/`](dhcp/) | [`dhcp/dhcpd.conf`](dhcp/dhcpd.conf) |
| **DNS** | `named` (`bind9`) | [`dns/`](dns/) | [`dns/named.conf.options`](dns/named.conf.options) |
| **FTP** | `vsftpd` | [`ftp/`](ftp/) | [`ftp/vsftpd.conf`](ftp/vsftpd.conf) |
| **SSH** | `ssh` (`openssh-server`) | [`ssh/`](ssh/) | [`ssh/sshd_config`](ssh/sshd_config) |
| **Contingencia** | `systemd` overrides | [`systemd/`](systemd/) | `Restart=always` (DNS y DHCP) |

## Validación y Evidencias

* **[Matriz de Validación Técnica (`docs/validacion.md`)](docs/validacion.md)** — Pruebas de automatización, contingencia, monitorización, seguridad y capturas de pantalla.
