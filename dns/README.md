# Servicio DNS (`bind9` / `named`)

Servidor DNS autoritativo en **`F-NCC`** (`192.168.10.30`) para la resolución de nombres de la zona interna **`intranet.local`**.

* **Archivos de configuración:**
  * [`named.conf.options`](named.conf.options) — Opciones globales y canal de logging dedicado.
  * [`named.conf.local`](named.conf.local) — Declaración de la zona directa `intranet.local`.
  * [`db.intranet.local`](db.intranet.local) — Registros de zona (`SOA`, `NS`, `A`).
* **Contingencia Systemd:** [`../systemd/named-override.conf`](../systemd/named-override.conf) (`Restart=always`)

## Detalles técnicos

* **Control de consultas:** `allow-query { any; };` permite resolver peticiones desde los clientes de la red.
* **Resolución directa:** Mapeo de los nodos de la infraestructura (`F-NCC`, `R-NCC`, etc.) a sus direcciones IPv4 estáticas.
* **Monitorización dedicada:** Canal `default_file` configurado en `named.conf.options` para registrar eventos en `/var/log/named/default.log` (`versions 3 size 5m`).
