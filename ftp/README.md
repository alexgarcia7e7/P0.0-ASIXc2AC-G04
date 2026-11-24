# Servicio FTP (`vsftpd`)

Servidor de transferencia de archivos en **`F-NCC`** (`192.168.10.30`) con aislamiento de usuarios locales mediante enjaulamiento `chroot`.

* **Archivo de configuración:** [`vsftpd.conf`](vsftpd.conf)

## Parámetros de acceso y seguridad

| Directiva | Valor | Función |
| :--- | :--- | :--- |
| `anonymous_enable` | `NO` | Bloquea el acceso anónimo sin credenciales. |
| `local_enable` | `YES` | Permite el acceso mediante usuarios locales del sistema (ej. `bchecker`). |
| `write_enable` | `YES` | Habilita la subida, modificación y borrado de archivos. |
| `chroot_local_user` | `YES` | Enjaula a cada usuario en su directorio raíz (`/`), bloqueando el acceso a `/etc` y al resto del sistema. |
| `allow_writeable_chroot` | `YES` | Permite permisos de escritura dentro del directorio raíz enjaulado. |
