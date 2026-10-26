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

**Figura 10** · 

![Fig10](img/diagrama-xarxa.png)

## Justificació del disseny

### Tres xarxes separades

La infraestructura es divideix segons qui ha de poder accedir a cada servei:

| Xarxa | Funció | Equips |
|---|---|---|
| NAT | Connexió amb l'exterior | Només R-N01 |
| DMZ | Serveis que s'ofereixen cap a fora | F-N01, W-N01 |
| Intranet | Serveis i equips interns, no exposats | B-N01, D-N01, clients |

Si un servei de la DMZ queda compromès, l'atacant no té accés directe a la Intranet: per arribar-hi ha de passar pel router, que només deixa passar el trànsit imprescindible.

### Per què el web i l'FTP són a la DMZ

Són els serveis pensats per ser consultats des de fora de l'organització. Tenir-los en una xarxa pròpia permet exposar-los sense exposar també la xarxa interna.

### Per què la base de dades és a la Intranet

Les dades són l'actiu més valuós i no s'han de poder consultar des de fora. L'aplicació de W-N01 les necessita, però no cal que siguin a la mateixa xarxa: s'hi connecta a través del router, només pel port 3306/tcp. Si algú comprometés el servidor web, la base de dades no seria al seu abast.

### Per què el DNS i el DHCP són a la Intranet

- **DHCP:** funciona per difusió (*broadcast*). El client, que encara no té IP, envia la petició a tota la seva xarxa, i aquests missatges no travessen el router. Per això el servidor ha de ser al mateix segment que els clients.
- **DNS:** és un servei intern, que tradueix els noms dels nostres equips. Els servidors de la DMZ també el fan servir, i per això el tallafocs permet el port 53 de la DMZ cap a D-N01. És l'única excepció, a més de la base de dades.

### Per què un sol router

Tot el trànsit entre xarxes, i cap a Internet, passa per R-N01. Això el converteix en l'únic punt on cal controlar què pot anar d'una xarxa a l'altra: fa el NAT perquè la DMZ i la Intranet surtin a Internet i hi aplica el tallafocs. Cap altre equip té interfície a la xarxa NAT.

### Per què la VPN arriba al router

La VPN permet administrar la infraestructura per SSH des de fora del centre. Entrar pel router, en lloc de connectar cada màquina a la VPN, manté un únic punt d'entrada i fa que aquest accés també quedi sota el control del tallafocs.

### IP fixa als servidors i DHCP als clients

Els servidors han de tenir sempre la mateixa adreça perquè els altres equips els trobin i perquè les regles del tallafocs hi facin referència. Els clients poden canviar d'adreça sense problema, i el DHCP evita configurar-los un a un.

### Criteri d'adreçament

Les adreces segueixen el format `10.3.X.H`:

- **3:** número de grup, perquè les adreces no coincideixin amb les d'altres grups.
- **X:** la xarxa (1 DMZ, 2 Intranet). Només amb veure una IP se sap on és l'equip.
- **H:** `.1` per al router, `.2` a `.9` reservades, `.10` i `.20` per als servidors i `.100` a `.200` per als clients.
- 
*Arquitectura de xarxa del grup 03. Les úniques connexions permeses de la DMZ cap a la Intranet són W-N01 → B-N01 pel port 3306/tcp i la DMZ → D-N01 pel port 53 (DNS).*

## Tallafocs previst (Sprint 3)

| Origen | Destí | Permès |
|---|---|---|
| W-N01 (10.3.1.20) | B-N01 (10.3.2.10) | 3306/tcp (MySQL) |
| DMZ (10.3.1.0/24) | D-N01 (10.3.2.20) | 53 udp/tcp (DNS) |
| Resta de la DMZ | Intranet | Bloquejat |

## Nota sobre la VPN

La VPN d'Isard rep una ruta `10.0.0.0/14`, que inclou els rangs 10.3.x.x. Les rutes directes de les subxarxes internes (`10.3.1.0/24` i `10.3.2.0/24`) són més específiques i tenen preferència, de manera que el trànsit intern mai surt per la VPN.
