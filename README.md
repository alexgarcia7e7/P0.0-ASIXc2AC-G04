# P0.0-ASIXc2AC-G04 - Desplegament d'infraestructura multicapa

## Esquema de Xarxa

| Màquina | Nom | IP LAN | Funció / Serveis |
| :--- | :--- | :--- | :--- |
| **Router** | `R-NCC` | `192.168.10.1/24` | Gateway / NAT (`iptables`) |
| **Web** | `W-NCC` | `192.168.10.10/24` | Apache2 + PHP |
| **BBDD** | `B-NCC` | `192.168.10.20/24` | MariaDB |

## Estat del MVP
- [x] Connectivitat de xarxa i enrutament validat.
- [x] Servidor MariaDB configurat per a connexions remotes amb l'usuari `bchecker`.
- [x] Servidor Web connectat a la BBDD i verificat mitjançant PHP (`MVP FUNCIONANDO CORRECTAMENTE`).
