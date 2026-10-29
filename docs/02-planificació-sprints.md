
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
| **Marc** |
| | Creació del diagrama i anàlisi de serveis |
| | Crear W-N01 i F-N01 des de la plantilla d'Isard amb IPs 10.3.1.20 i 10.3.1.10 |
| | Crear el repositori GitHub amb l'esquema de directoris comoplet |
| | Instal·lar Apache i PHP a W-N01 |
| | Documentar W-N01 i F-N01 |

---

## 4. Sprint 2 – Serveis

| Persona | Tasca | h |
|---|---|---|
| **Victor** | Crear D-N01 des de la plantilla d'Isard, amb configuració base i IP 10.3.2.20 | 2 |
| | Servidor DHCP a D-N01: rang 10.3.2.100–200, gateway 10.3.2.1 | 3 |
| | Servidor DNS (bind9) a D-N01: registres de totes les màquines i reenviament a un DNS públic | 3 |
| | Documentar D-N01, DHCP i DNS | 2 |
| **Adam** | Treure la interfície Default de B-N01 i posar-hi el DNS 10.3.2.20 | 1 |
| | Crear la taula i importar el CSV a MySQL, comprovant el nombre de files i els accents | 4 |
| | Crear l'usuari de l'aplicació, només de lectura i només des de W-N01 | 1 |
| | Primera versió de l'aplicació: connexió a B-N01 i llistat de la taula | 2 |
| | Documentar la importació i els usuaris de MySQL | 2 |
| **Marc** | Treure la interfície Default de W-N01 i F-N01 i posar-hi el DNS 10.3.2.20 | 1 |
| | Servidor FTP vsftpd a F-N01: usuaris locals, engabiats i ports passius fixos | 3 |
| | Clau SSH de W-N01 a GitHub (deploy key) i clonar el repositori | 3 |
| | Pàgina PHP de prova a W-N01 que es connecti a B-N01 | 1 |
| | Documentar l'FTP i la connexió de W-N01 amb GitHub | 2 |

---

## 5. Sprint 3 – Aplicació, seguretat i tancament

| Persona | Tasca | h |
|---|---|---|
| **Victor** | Crear els clients Windows i Linux, només amb ITB14 i per DHCP | 3 |
| | Tallafocs a R-N01: de la DMZ cap a la Intranet només W→B pel 3306 i DMZ→D pel 53 | 3 |
| | Bateria de proves des dels clients: DHCP, DNS, web, FTP i tallafocs | 1 |
| | Documentar els clients, el tallafocs i les proves | 2 |
| | Assaig final conjunt | 1 |
| **Adam** | Acabar l'aplicació: taula HTML, paginació i cercador | 5 |
| | Pujar l'aplicació al repositori i desplegar-la a W-N01 amb `git pull` | 1 |
| | Proves de l'aplicació i accés amb bchecker a MySQL | 1 |
| | Documentar l'aplicació | 2 |
| | Assaig final conjunt | 1 |
| **Marc** | Documentar el flux Git (`clone`, `add`, `commit`, `push`, `pull`) i la pujada a producció | 3 |
| | Passar la documentació a l'arbre Markdown del repositori | 4 |
| | Proves de la web, l'FTP i l'accés amb bchecker a W-N01 i F-N01 | 1 |
| | Revisar que tota la documentació del repositori estigui completa | 1 |
| | Assaig final conjunt | 1 |

**Ordre de l'assaig final:** engegar les màquines en aquest ordre: R-N01, D-N01, B-N01, W-N01, F-N01 i després els clients. Després, passar totes les proves de principi a fi.

---

## 6. Resum d'hores

| Persona | Sprint 1 | Sprint 2 | Sprint 3 | Total |
|---|---|---|---|---|
| Victor | 10 h | 10 h | 10 h | 30 h |
| Adam | 10 h | 10 h | 10 h | 30 h |
| Marc | 10 h | 10 h | 10 h | 30 h |
