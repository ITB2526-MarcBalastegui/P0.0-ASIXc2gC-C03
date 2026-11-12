# P0.0 – Desplegament de la infraestructura

Projecte intermodular ASIXc2 · Institut Tecnològic de Barcelona · **Grup G03**

Aquest repositori conté la documentació del desplegament d'una infraestructura de xarxa a IsardVDI: un router amb tallafocs i NAT, una zona desmilitaritzada (DMZ) amb els serveis exposats, una Intranet amb la base de dades, el DNS, el DHCP i els clients, i una aplicació web que mostra les dades d'un CSV municipal.

---
## La pràctica

L'objectiu és muntar una infraestructura completa, provar-la i documentar-la. Els requisits principals són:

- **Xarxa:** un router que connecta la DMZ i la Intranet entre elles i amb Internet, amb NAT per a les màquines internes i un tallafocs que controla el trànsit entre les dues xarxes.
- **DMZ:** servidors amb IP fixa, accessibles des de l'exterior. Hi ha el servidor FTP i el servidor web amb l'aplicació.
- **Intranet:** la base de dades MySQL amb les dades del CSV, el servidor DNS i DHCP, i els clients Windows i Linux, que reben la configuració per DHCP.
- **Seguretat:** de la DMZ cap a la Intranet només es permet el trànsit imprescindible: el web cap a la base de dades, i la DMZ cap al DNS.
- **Accés:** tots els equips tenen l'usuari `bchecker`, que el professor fa servir per corregir.
- **Control de versions:** el codi de l'aplicació i la documentació es publiquen a GitHub, i el servidor web es desplega amb `git pull`.

---
  
## Índex de documentació

| Document | Contingut |
|---|---|
| [01 – Arquitectura](docs/01-arquitectura.md) | Anàlisi i justificació de serveis, pla d'adreçament, interfícies i diagrama de la xarxa |
| [02 - Planificació d'sprints](docs/02-planificació-sprints.md) | Planificació i anàlisi dels sprints |
| [03 – Router R-N01](docs/03-router.md) | Reenviament, NAT i Tallafocs a R-N01 |
| [04 – DNS i DHCP](docs/04-dns-dhcp.md) | Servidor DNS (bind9) i DHCP a D-N01 |
| [05 – Base de dades](docs/05-bbdd.md) | MySQL a B-N01, esquema i importació del CSV |
| [06 – FTP](docs/06-ftp.md) | Servidor vsftpd a F-N01 |
| [07 – Servidor web](docs/07-web.md) | Apache i aplicació a W-N01 |
| [08 – Clients](docs/08-clients.md) | Clients Windows i Linux a la Intranet |
| [09 – Proves](docs/09-proves.md) | Bateria de proves de tots els serveis |
| [10 – Flux Git](docs/10-git.md) | Control de versions |
