# Servicio DHCP (`isc-dhcp-server`)

Configuración del servidor DHCP en **`F-NCC`** (`192.168.10.30`) para la asignación dinámica de direcciones IP y parámetros de red en la subred interna.

* **Archivo de configuración:** [`dhcpd.conf`](dhcpd.conf)
* **Contingencia Systemd:** [`../systemd/dhcp-override.conf`](../systemd/dhcp-override.conf) (`Restart=always`)

## Parámetros configurados

| Directiva | Valor | Función |
| :--- | :--- | :--- |
| `subnet` / `netmask` | `192.168.10.0` / `255.255.255.0` | Define el ámbito de red local administrado. |
| `range` | `192.168.10.50` – `192.168.10.100` | Rango de direcciones IP dinámicas asignadas a los clientes. |
| `option routers` | `192.168.10.1` | Puerta de enlace predeterminada entregada por DHCP. |
| `option domain-name-servers` | `192.168.10.30` | Asigna `F-NCC` como servidor DNS primario. |
| `option domain-name` | `"intranet.local"` | Dominio de búsqueda DNS interno. |
