# Xarxa: pla d'adreçament i diagrama

## Xarxes d'IsardVDI

| Interfície d'Isard | Ús | Subxarxa |
|---|---|---|
| Default | NAT: sortida a Internet | DHCP d'Isard |
| ASIXC2-ITB13 | DMZ | 10.3.1.0/24 |
| ASIXC2-ITB14 | Intranet | 10.3.2.0/24 |
| ITB13a / ITB14a | Reserva | — |
| Wireguard VPN | Accés d'administració des de fora | VPN d'Isard |

## Pla d'adreçament

| Equip | Funció | Interfícies | IP | Gateway | DNS |
|---|---|---|---|---|---|
| R-N01 | Router, tallafocs i NAT | Default, ITB13, ITB14, VPN | DHCP / 10.3.1.1 / 10.3.2.1 / VPN | Default | 10.3.2.20 (temporal: 8.8.8.8 fins que D-N01 estigui operatiu) |
| F-N01 | FTP (vsftpd) | ITB13 | 10.3.1.10 | 10.3.1.1 | 10.3.2.20 |
| W-N01 | Web (Apache + app) | ITB13 | 10.3.1.20 | 10.3.1.1 | 10.3.2.20 |
| B-N01 | BBDD (MySQL) | ITB14 | 10.3.2.10 | 10.3.2.1 | 10.3.2.20 |
| D-N01 | DNS + DHCP | ITB14 | 10.3.2.20 | 10.3.2.1 | 127.0.0.1 |
| PC Windows | Client | ITB14 | DHCP | DHCP | DHCP |
| PC Linux | Client | ITB14 | DHCP | DHCP | DHCP |

- **Rang DHCP (Intranet):** 10.3.2.100 – 10.3.2.200
- **Reservades per a futures màquines:** 10.3.1.2–9 (DMZ) i 10.3.2.2–9 (Intranet)
- **Registres DNS previstos:** R-N01 → 10.3.2.1; R → àlies de R-N01; F-N01, W-N01, B-N01 i D-N01 amb la seva IP.

## Diagrama d'arquitectura

**Figura 10** · `docs/img/diagrama-xarxa.png`

*Arquitectura de xarxa del grup 03. Les úniques connexions permeses de la DMZ cap a la Intranet són W-N01 → B-N01 pel port 3306/tcp i la DMZ → D-N01 pel port 53 (DNS).*

## Tallafocs previst (Sprint 3)

| Origen | Destí | Permès |
|---|---|---|
| W-N01 (10.3.1.20) | B-N01 (10.3.2.10) | 3306/tcp (MySQL) |
| DMZ (10.3.1.0/24) | D-N01 (10.3.2.20) | 53 udp/tcp (DNS) |
| Resta de la DMZ | Intranet | Bloquejat |

## Nota sobre la VPN

La VPN d'Isard rep una ruta `10.0.0.0/14`, que inclou els rangs 10.3.x.x. Les rutes directes de les subxarxes internes (`10.3.1.0/24` i `10.3.2.0/24`) són més específiques i tenen preferència, de manera que el trànsit intern mai surt per la VPN.
