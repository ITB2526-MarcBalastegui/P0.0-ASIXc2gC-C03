# P0.0 – Desplegament de la infraestructura

Projecte intermodular ASIX2 · Institut Tecnològic de Barcelona · **Grup G03**

Aquest repositori conté la documentació del desplegament d'una infraestructura de xarxa a IsardVDI: un router amb tallafocs i NAT, una zona desmilitaritzada (DMZ) amb els serveis exposats, una Intranet amb la base de dades, el DNS, el DHCP i els clients, i una aplicació web que mostra les dades d'un CSV municipal.

---

## Índex de documentació

| Document | Contingut | Responsable |
|---|---|---|
| [01 – Arquitectura](docs/01-arquitectura.md) | Pla d'adreçament, interfícies i diagrama de la xarxa |
| [02 – Router R-N01](docs/02-router.md) | Creació, configuració base, netplan, reenviament i NAT |
| [03 – DNS i DHCP](docs/03-dns-dhcp.md) | Servidor DNS (bind9) i DHCP a D-N01 |
| [04 – Base de dades](docs/04-bbdd.md) | MySQL a B-N01, esquema i importació del CSV |
| [05 – FTP](docs/05-ftp.md) | Servidor vsftpd a F-N01 |
| [06 – Servidor web](docs/06-web.md) | Apache i aplicació a W-N01 |
| [07 – Clients](docs/07-clients.md) | Clients Windows i Linux a la Intranet |
| [08 – Control de versions](docs/08-git.md) | Flux Git, claus SSH i desplegament amb `git pull` |
| [09 – Proves](docs/09-proves.md) | Bateria de proves de tots els serveis |

Cada document segueix la mateixa estructura: **creació de la màquina** (adaptadors i recursos), **configuració base** (hostname, usuari, actualitzacions i SSH) i **serveis**, amb la seva comprovació i captures.

---

## 1. La pràctica

L'objectiu és muntar una infraestructura completa, provar-la i documentar-la. Els requisits principals són:

- **Xarxa:** un router que connecta la DMZ i la Intranet entre elles i amb Internet, amb NAT per a les màquines internes i un tallafocs que controla el trànsit entre les dues xarxes.
- **DMZ:** servidors amb IP fixa, accessibles des de l'exterior. Hi ha el servidor FTP i el servidor web amb l'aplicació.
- **Intranet:** la base de dades MySQL amb les dades del CSV, el servidor DNS i DHCP, i els clients Windows i Linux, que reben la configuració per DHCP.
- **Seguretat:** de la DMZ cap a la Intranet només es permet el trànsit imprescindible: el web cap a la base de dades, i la DMZ cap al DNS.
- **Accés:** tots els equips tenen l'usuari `bchecker`, que el professor fa servir per corregir.
- **Control de versions:** el codi de l'aplicació i la documentació es publiquen a GitHub, i el servidor web es desplega amb `git pull`.

## 2. Equip i rols

| Membre | Rol | Màquines | Responsabilitat |
|---|---|---|---|
| Victor Barreda | Xarxa | R-N01, D-N01, PC Windows, PC Linux | Router, tallafocs, DNS, DHCP i clients |
| Adam Ben Mansor | Dades | B-N01 | MySQL, importació del CSV i aplicació web |
| Marc Balastegui | Web i Git | W-N01, F-N01 | Servidor web, FTP, repositori GitHub i desplegament |

Cada membre crea, configura, prova i documenta les seves màquines. Les regles comunes són:

- **Configuració base** igual a totes les màquines: hostname, IP fixa, usuari `bchecker` amb sudo, SSH actiu i zona horària Europe/Madrid.
- **Estructura de documentació** igual per a tothom, tal com indica l'índex d'alt.

## 3. Planificació en sprints

El projecte es divideix en tres sprints de deu hores per persona. Cada sprint té un objectiu clar, i les tasques es gestionen a ProofHub.

| Sprint | Objectiu | Què es fa |
|---|---|---|
| **1. Xarxa base i màquines** | Totes les màquines existeixen, tenen IP fixa i surten a Internet pel router | Router amb NAT, creació de B-N01, W-N01, F-N01 i D-N01, MySQL instal·lat, repositori GitHub i pla IP |
| **2. Serveis** | Els serveis funcionen de manera independent | DHCP i DNS a D-N01, importació del CSV a MySQL, FTP a F-N01, Apache a W-N01 i clau SSH per al desplegament |
| **3. Aplicació, seguretat i tancament** | Tot funciona de principi a fi i està provat | Aplicació web, tallafocs del router, clients, bateria de proves i revisió final de la documentació |

**Criteri del plantejament.** Cada persona és responsable de la seva part de principi a fi, de manera que no depèn de la feina dels altres per avançar. Les dependències entre tasques s'han marcat a ProofHub:

- **El router bloqueja gairebé tot.** Les altres màquines no tenen sortida a Internet fins que el router funciona. Per això és la primera tasca del Sprint 1.
- **La importació del CSV bloqueja l'aplicació.** Sense les dades a MySQL, l'aplicació no té res a mostrar.
- **El tallafocs va al final.** Es configura quan l'aplicació ja funciona, perquè si alguna cosa falla, es pugui saber si és per la regla o per l'aplicació.

## 4. Accés

Tots els equips tenen l'usuari `bchecker`, amb permisos de sudo. 

## 5. Estructura del repositori

```
P0.0-ASIXc2gC-G03/
├── README.md              Aquesta pàgina: índex, pràctica, rols i sprints
├── app/                   Codi de l'aplicació web (W-N01)
├── config/                Fitxers de configuració de cada màquina
├── docs/                  Documentació, un fitxer per apartat
│   ├── 01-arquitectura.md
│   ├── 02-router.md
│   ├── ...
│   └── img/               Captures i diagrames
└── sql/                   Esquema de la taula i script de càrrega del CSV
```
