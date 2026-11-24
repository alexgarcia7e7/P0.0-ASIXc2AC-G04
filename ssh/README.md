# Servicio SSH (`openssh-server`)

Servicio de administración remota en **`F-NCC`** (`192.168.10.30`) endurecido (*hardening*) para permitir únicamente autenticación criptográfica por clave pública.

* **Archivo de configuración:** [`sshd_config`](sshd_config)

## Medidas de *Hardening* aplicadas

* **`PermitRootLogin no`:** Prohíbe el inicio de sesión directo como usuario `root`.
* **`PasswordAuthentication no`:** Desactiva el acceso mediante contraseña para mitigar ataques de fuerza bruta.
* **`PubkeyAuthentication yes`:** Exige pares de claves asimétricas (`ed25519`) autorizadas en `~/.ssh/authorized_keys`.
* **Persistencia:** Servicio habilitado en `systemd` (`ssh.service`) para arranque automático tras cada reinicio.
