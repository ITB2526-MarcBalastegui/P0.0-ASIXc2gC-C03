
# Equip i planificació dels sprints

Aquest document explica com està organitzat el treball del grup, qui fa cada part, quines tasques té cadascú i com es reparteixen les hores en cada sprint.

---

## 1. Equip i rols

| Membre | Rol | Màquines | Responsabilitat |
|---|---|---|---|
| Victor Barreda | Xarxa | R-N01, D-N01, PC Windows, PC Linux | Router, tallafocs, DNS, DHCP i clients |
| Adam Ben Mansor | Dades | B-N01 | MySQL, importació del CSV i aplicació web |
| Marc Balastegui | Web i Git | W-N01, F-N01 | Servidor web, FTP, repositori GitHub i desplegament |

**Criteri del repartiment.** Cada persona és responsable de la seva part de principi a fi, crea les seves màquines, les configura, les prova i fa la documentació. Així no depèn de la feina dels altres per avançar.
**Regles comunes a tot l'equip:**

- **Configuració base:** Igual a totes les màquines: hostname, IP fixa, usuari `bchecker` amb sudo, SSH actiu i zona horària Europe/Madrid.
- **Estructura de documentació:** Igual per a tothom: creació de la màquina (adaptadors i recursos), configuració base i serveis, cadascú amb la seva comprovació i captures.
- **Tasques a ProofHub:** Amb responsable, hores estimades i dependències marcades.

---

## 2. Planificació general

El projecte es divideix en tres sprints:

| Sprint | Objectiu | Resultat esperat al final |
|---|---|---|
| **1. Xarxa base i màquines** | Creació i configuració bàsica de tots els serveis, excepte DHCP i DNS | El router funciona amb NAT, i totes les màquines estan creades i configurades |
| **2. Serveis** | Cada servei funciona corectament | DHCP i DNS, MySQL amb les dades del CSV, FTP, Apache i la clau SSH per al desplegament |
| **3. Aplicació, seguretat i tancament** | Tot funciona de principi a fi i està provat | Aplicació web, tallafocs, clients, bateria de proves i documentació final |

### Dependències entre tasques

- **El router bloqueja gairebé tot.** Les altres màquines no tenen sortida a Internet fins que R-N01 funciona. Per això és la primera tasca del Sprint 1.
- **La importació del CSV bloqueja l'aplicació.** Sense les dades a MySQL, l'aplicació no té res a mostrar.
- **El tallafocs va al final.** Es configura quan l'aplicació ja funciona. Si es fa abans i alguna cosa falla, no es pot saber si és per la regla o per l'aplicació.
- **Les màquines sense Default depenen del router.** Quan R-N01 està operatiu, es treu la interfície Default a les màquines de la DMZ i la Intranet, i només queden amb ITB13 o ITB14.

---

## 3. Sprint 1 – Xarxa base i màquines

| Persona | Tasca |
|---|---|
| **Victor** | Crear el projecte i el backlog a ProofHub |
| | Creació del diagrama i anàlisi de serveis |
| | Pla IP i diagrama d'arquitectura |
| | Crear R-N01 des de la plantilla d'Isard: configuració base, interfícies, reenviament i NAT |
| | Documentar el router i el pla IP |
| **Adam** | Crear B-N01 des de la plantilla d'Isard amb IP 10.3.2.10 |
| | Instal·lar MySQL i crear l'usuari bchecker amb accés remot |
| | Descarregar el CSV, analitzar-lo i preparar l'esquema de la taula | 
| | Documentar B-N01 i MySQL |
| **Marc** | Creació del diagrama i anàlisi de serveis |
| | Crear el repositori GitHub amb l'esquema de directoris comoplet |
| | Crear W-N01 i F-N01 des de la plantilla d'Isard amb les IPs 10.3.1.20 i 10.3.1.10 |
| | Instal·lar Apache i PHP a W-N01 |
| | Documentar W-N01 i F-N01 |

---

## 4. Sprint 2 – Serveis

| Persona | Tasca |
|---|---|
| **Victor** | Crear D-N01 des de la plantilla d'Isard, amb configuració base i IP 10.3.2.20 |
| | Servidor DHCP a D-N01: rang 10.3.2.100–200, gateway 10.3.2.1 |
| | Servidor DNS (bind9) a D-N01: registres de totes les màquines i reenviament a un DNS públic |
| | Documentar D-N01, DHCP i DNS |
| | Tallafocs a R-N0 |
| | Crear els clients Windows i Linux, només amb ITB14 i per DHCP, i amb l’usuari bchecker |
| | Bateria de proves des dels clients, DHCP, DNS (R i R-N01), web, FTP i el tallafocs |
| | Documentar els clients, el tallafocs i les proves |
| **Adam** | Treure la interfície Default de B-N01 i posar-hi el DNS 10.3.2.20 |
| | Crear la taula i importar el CSV a MySQL, comprovant el nombre de files i els accents |
| | Crear l'usuari de l'aplicació, només de lectura i només des de W-N01 |
| | Pàgina PHP de prova a W-N01 que es connecti a B-N01 |
| | Documentar la importació i els usuaris de MySQL |
| | Primera versió de l’aplicació: connexió a B-N01 i llistat de la taula |
| **Marc** | Treure la interfície Default de W-N01 i F-N01 i posar-hi el DNS 10.3.2.20 |
| | Servidor FTP vsftpd a F-N01: usuaris locals, engabiats i ports passius fixos |
| | Clau SSH de W-N01 a GitHub (deploy key) i clonar el repositori |
| | Pàgina PHP de prova a W-N01 que es connecti a B-N01 |
| | Servidor FTP vsftpd a F-N01 |
| | Documentar l'FTP i la connexió de W-N01 amb GitHub |
| | Crear els clients Windows i Linux, només amb ITB14 i per DHCP, i amb l’usuari bchecker |
| | Ícona de tornar a l'índex, per millorar l'accessibilitat, fent més fàcil moure'ns entre pàgines |


---

## 5. Sprint 3 – Revisió i Tancament

| Persona | Tasca |
|---|---|
| **Victor** | Revisar que tota la documentació del repo estigui completa |
| | Assaig final conjunt |
| **Adam** | Pujar l'aplicació al repositori i desplegar-la a W-N01 amb `git pull` |
| | Proves de l'aplicació i accés amb bchecker a MySQL |
| | Acabar l’aplicació |
| | Documentar l'aplicació |
| | Assaig final conjunt |
| **Marc** | Documentar el flux Git (`clone`, `add`, `commit`, `push`, `pull`) i la pujada a producció |
| | Assaig final conjunt |

---

