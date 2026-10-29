# B-N01 – Servidor de base de dades

B-N01 és el servidor de base de dades de la infraestructura. Es troba a la Intranet (10.3.2.0/24) i hi corre MySQL, amb el llistat d'equipaments d'educació de la ciutat de Barcelona carregat en una taula. Més endavant el servidor web W-N01, situat a la DMZ, hi accedirà pel port 3306 per mostrar-ne les dades. Per sortir a Internet i comunicar-se amb la DMZ depèn del router R-N01, que fa de gateway de la Intranet.

## 1. Creació de la màquina

La màquina s'ha creat a IsardVDI a partir de la plantilla **Ubuntu Server 24.04**. S'ha triat Ubuntu Server perquè no té entorn gràfic, i per tant consumeix menys recursos i té un millor rendiment. A més, Linux incorpora eines natives per administrar serveis com MySQL, cosa que resulta més senzilla i pràctica que en Windows.

| Paràmetre | Valor | Justificació |
|---|---|---|
| vCPUs | 4 | Deixa marge per a la càrrega del CSV i per a les consultes de la futura aplicació web |
| Memòria | 8 GB | MySQL aprofita la memòria per a la memòria cau, i l'anàlisi del CSV amb csvkit també en consumeix |
| Disc | Hard Disk (per defecte) | El de la plantilla |

### Adaptadors de xarxa

A B-N01 se li han afegit dos adaptadors: un de temporal per poder instal·lar paquets i un altre per a la xarxa interna del projecte.

| Adaptador | Xarxa d'Isard | MAC | Interfície | Ús |
|---|---|---|---|---|
| 1 | Default | 52:54:00:66:d1:fe | `enp1s0` | **Temporal.** Sortida a Internet per instal·lar els serveis mentre es configura la resta d'infraestructura. Rep la IP per DHCP |
| 2 | ASIXC2-ITB14 | 52:54:00:2e:09:dd | `enp2s0` | Intranet. IP fixa 10.3.2.10/24, amb gateway el router R-N01 (10.3.2.1) |

S'han apuntat les adreces MAC perquè dins del sistema les interfícies es diuen `enp1s0`, `enp2s0`, etc. Comparant les MAC és la manera segura de saber quina interfície correspon a cada xarxa abans de configurar les IPs.


![Isard Xarxa](img/bbdd/01-hardware-isard.png)

*Configuració de recursos i xarxes de l'escriptori B-N01 a IsardVDI.*

## 2. Configuració base

Aquesta configuració s'aplica igual a totes les màquines del projecte. Així totes queden homogènies i compleixen el requisit de l'usuari bchecker.

### 2.1 Nom de la màquina i usuari bchecker

```bash
sudo hostnamectl set-hostname B-N01
sudo adduser bchecker
sudo usermod -aG sudo bchecker
```

- **`hostnamectl set-hostname`** canvia el nom de la màquina de forma persistent. El nom segueix la nomenclatura del projecte i és el que després resoldrà el DNS. El prompt no canvia fins que es torna a iniciar sessió.
- **`adduser bchecker`** crea l'usuari amb el seu directori personal i li assigna la contrasenya `bchecker121`. L'enunciat exigeix aquest usuari a tots els sistemes, per a la correcció.
- **`usermod -aG sudo bchecker`** afegeix l'usuari al grup `sudo`, perquè pugui administrar la màquina. L'opció `-a` (*append*) és important: sense ella, l'usuari perdria la resta de grups als quals pertany.


![Fig02](img/bbdd/hostname-adduser.png)

*Canvi de hostname i creació de l'usuari bchecker.*

### 2.2 Zona horària i actualització del sistema

```bash
sudo timedatectl set-timezone Europe/Madrid
sudo apt update && sudo apt upgrade -y
```

- **Zona horària Europe/Madrid:** els logs del sistema i de MySQL han de tenir l'hora local. Si no, és difícil relacionar-los amb les proves i les captures, i en un context de seguretat és clau saber exactament quan ha passat cada cosa.
- **`apt update`** actualitza la llista de paquets disponibles, i **`apt upgrade -y`** instal·la les actualitzacions pendents. Es fa abans de configurar cap servei, perquè la plantilla pot tenir paquets antics amb vulnerabilitats conegudes.


![Fig03](img/bbdd/timezone-apt-update.png)

*Configuració de la zona horària i actualització del sistema.*

### 2.3 Accés SSH

```bash
sudo apt install -y openssh-server
sudo systemctl enable --now ssh
```

- **`apt install -y openssh-server`** instal·la el servidor SSH. Si la plantilla ja el portava, l'ordre simplement ho indica i no canvia res.
- **`systemctl enable --now ssh`** arrenca el servei immediatament (`--now`) i el deixa activat perquè arrenqui sol en cada reinici (`enable`). Això és el que permet administrar el servidor en remot, i el que permetrà al professor entrar-hi amb l'usuari bchecker.


![Fig04](img/bbdd/ssh-install.png)

*Instal·lació i activació del servei SSH.*

### 2.4 Configuració de xarxa (netplan)

**Què fa.** Assigna a cada interfície la seva xarxa: la Default obté IP per DHCP d'Isard i serveix de sortida temporal a Internet, mentre que la interfície de la Intranet (ITB14) rep una IP fixa. El gateway és la IP del router R-N01 a la Intranet (`10.3.2.1`) i el DNS és `10.3.2.20`.

**Configuració.** Fitxer `/etc/netplan/50-cloud-init.yaml`.

```yaml
network:
  version: 2
  ethernets:
    enp1s0:
      dhcp4: true
    enp2s0:
      dhcp4: false
      addresses: [10.3.2.10/24]
      routes:
        - to: default
          via: 10.3.2.1
      nameservers:
        addresses: [10.3.2.20]
```

Per aplicar els canvis:

```bash
sudo chmod 600 /etc/netplan/*.yaml
sudo netplan apply
```


![Fig05](img/bbdd/02-netplan.png)

*Fitxer de configuració de netplan editat amb nano.*

**Comprovació.** La interfície `enp2s0` ha de mostrar la IP `10.3.2.10/24`, i la `enp1s0` la IP rebuda per DHCP de la xarxa Default.

```bash
ip -br a
```


![Fig06](img/bbdd/03-ip-br-a.png)

*Sortida d'`ip -br a`: `enp1s0` amb IP per DHCP i `enp2s0` amb 10.3.2.10/24.*

### 2.5 Comprovació

```bash
hostnamectl
id bchecker
timedatectl | grep "Time zone"
systemctl status ssh
```

| Comprovació | Resultat esperat |
|---|---|
| Hostname | `Static hostname: B-N01` |
| Sistema operatiu | `Ubuntu 24.04.4 LTS` |
| Usuari bchecker | uid 1001, inclòs al grup `27(sudo)` |
| Zona horària | `Europe/Madrid (CEST, +0200)` |
| Servei SSH | `active (running)` i `enabled` |

A més, aquestes comprovacions es fan havent iniciat sessió com a **bchecker** (el prompt mostra `bchecker@B-N01`). Això demostra que l'usuari funciona i que pot fer servir `sudo`.


![Fig07](img/bbdd/04-hostnamectl.png)

*Comprovació de la configuració base: hostname, sistema operatiu i usuari bchecker.*

## 3. Serveis

### 3.1 MySQL

**Què fa.** És el servei de base de dades del projecte. Guarda el llistat d'equipaments i hi dona accés als altres equips mitjançant el port 3306.

**Configuració.** S'instal·la el paquet des dels repositoris oficials d'Ubuntu i s'activa amb `systemctl`, perquè arrenqui automàticament amb el sistema.

```bash
sudo apt install -y mysql-server
sudo systemctl enable --now mysql
sudo systemctl status mysql
```

**Comprovació.** El servei ha de figurar com a `active (running)` i `enabled`, amb l'estat *"Server is operational"*. La versió instal·lada és MySQL 8.0.


![Fig08](img/bbdd/05-systemctl-mysql.png)

*Estat del servei MySQL amb `systemctl status mysql`.*

### 3.2 Base de dades i usuari

**Què fa.** Crea la base de dades `equipaments` i l'usuari `bchecker` amb tots els privilegis sobre ella. La base de dades utilitza la codificació **utf8mb4**: el CSV és en català i conté molts caràcters especials (accents, ç, punt volat), i utf8mb4 és la implementació completa d'UTF-8 a MySQL. La col·lació `utf8mb4_unicode_ci` permet comparar i ordenar el text seguint les regles d'Unicode i sense distingir majúscules de minúscules.

**Configuració.** S'accedeix a MySQL com a `root` i s'hi executen les ordres següents:

```bash
sudo mysql
```

```sql
CREATE DATABASE equipaments CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'bchecker'@'localhost' IDENTIFIED BY 'bchecker121';

GRANT ALL PRIVILEGES ON equipaments.* TO 'bchecker'@'localhost';

FLUSH PRIVILEGES;

EXIT;
```

**Comprovació.** Es consulten les bases de dades amb el nou usuari; `equipaments` ha d'aparèixer a la llista.

```bash
mysql -u bchecker -pbchecker121 -e "SHOW DATABASES;"
```


![Fig09](img/bbdd/06-mysql-creacio-bd.png)

*Creació de la base de dades i de l'usuari, i comprovació amb bchecker.*

### 3.3 Descàrrega i conversió del CSV

**Què fa.** Obté el llistat d'equipaments d'educació de Barcelona (Open Data BCN) i el deixa en un format que MySQL i les eines de Linux puguin llegir. El fitxer original està codificat en **UTF-16**, mentre que el sistema treballa en **UTF-8**, així que cal convertir-lo.

**Configuració.** Es crea un directori `data` a la home i s'hi descarrega el fitxer amb `wget`:

```bash
mkdir -p ~/data && cd ~/data
wget -O equipaments_educacio.csv "https://opendata-ajuntament.barcelona.cat/data/dataset/f36b60f2-9541-4d08-b0f9-b0a9313fab3d/resource/29d9ff10-6892-4f16-9012-d5c4997857e7/download"
```

En intentar llegir-lo amb `csvcut`, l'eina va rebutjar el fitxer per la codificació. Amb `file -i` es va confirmar que era UTF-16LE, i es va convertir amb `iconv` a una còpia nova, `equipaments_educacio_utf8.csv`, que deixa l'original intacte:

```bash
iconv -f UTF-16LE -t UTF-8 equipaments_educacio.csv > equipaments_educacio_utf8.csv
```

**Comprovació.** Es verifica la codificació amb `file -i`, es mira la capçalera i dues files d'exemple amb `head -n 3`, i es compten les línies amb `wc -l`.

```bash
file -i equipaments_educacio_utf8.csv
head -n 3 equipaments_educacio_utf8.csv
wc -l equipaments_educacio_utf8.csv
```

El resultat és `charset=utf-8` i un total de **2711 línies**. Aquesta xifra no coincideix amb el nombre de registres perquè alguns camps contenen salts de línia; el recompte real es fa a l'apartat següent.


![Fig10](img/bbdd/07-file-head-wc.png)

*Verificació de la codificació, capçalera del CSV i nombre de línies.*

### 3.4 Anàlisi del CSV

**Què fa.** Determina com està format el fitxer (separador, columnes, tipus i longituds), que és la base per dissenyar la taula de MySQL.

**Separador.** Es prova `csvcut` amb punt i coma (`-d ';'`) i amb tabuladors (`-t`). En tots dos casos la sortida és una única columna llarga, cosa que demostra que el separador és la **coma**.

```bash
csvcut -d ';' -n equipaments_educacio_utf8.csv
csvcut -t -n equipaments_educacio_utf8.csv
```


![Fig11](img/bbdd/08-csvcut-separador.png)

*Proves de separador amb `csvcut`: en tots dos casos una sola columna.*

**Columnes i registres.** Amb `csvcut -n` s'obté la llista numerada de columnes (**40**), i amb `csvstat --count` el nombre real de registres (**2710**). A més, `csvstat` genera l'anàlisi detallat de cada columna (tipus, nuls, valors únics i longitud màxima), que es desa en un fitxer de text. Així es pot consultar còmodament sense haver d'executar l'ordre cada vegada, ja que la sortida és molt llarga i al servidor és complicat desplaçar-se per veure el contingut anterior.

```bash
csvcut -n equipaments_educacio_utf8.csv
csvstat --count equipaments_educacio_utf8.csv
csvstat equipaments_educacio_utf8.csv > ~/data/csvstat_salida.txt
```


![Fig12](img/bbdd/09-csvcut-columnes.png)

*Llista numerada de les 40 columnes del CSV.*


![Fig13](img/bbdd/10-csvstat-txt.png)

*Contingut del fitxer `csvstat_salida.txt` obert amb nano.*

**Comprovació.** El fitxer `csvstat_salida.txt` ha de contenir un bloc per columna amb el tipus de dades, els valors nuls, els valors únics i la longitud màxima.

### 3.5 Creació de la taula

**Què fa.** Defineix l'estructura on es guardaran les dades. Una sola taula, `equipaments_educacio`, amb les 40 columnes del CSV i una clau primària pròpia (`id`), perquè cap columna del CSV és única: cada equipament apareix repetit en diverses files, una per cada atribut o filtre.

**Configuració.** Amb `csvsql` es genera un esborrany de l'script SQL a partir del CSV:

```bash
csvsql -S -i mysql --tables equipaments_educacio equipaments_educacio_utf8.csv > create_table.sql
cat create_table.sql
```

L'script generat automàticament conté diverses incongruències, per això s'ha corregit manualment:

| Problema | Correcció |
|---|---|
| Dates com a `TIMESTAMP` (límit a l'any 2038) | `DATETIME` |
| `timetable` com a `VARCHAR(1648)` | `TEXT` |
| `addresses_zip_code` com a `DECIMAL` (perd el zero inicial) | `VARCHAR(5)` |
| Columnes buides detectades com a `BOOL` | `VARCHAR` |
| Identificadors com a `DECIMAL(38,0)` | `BIGINT` / `INT` |
| Coordenades com a `DECIMAL(38,x)` | `DOUBLE` |
| Longituds de `VARCHAR` ajustades al màxim actual | Longituds amb marge |
| Sense clau primària | `id INT AUTO_INCREMENT PRIMARY KEY` |


![Fig14](img/bbdd/11-create-table.png)

*Fitxer `create_table.sql` ja corregit.*

Un cop corregit, es crea la taula amb l'usuari `bchecker` dins de la base de dades `equipaments`:

```bash
mysql -u bchecker -pbchecker121 equipaments < create_table.sql
```

**Comprovació.** L'ordre no ha de mostrar cap error. Es pot confirmar amb `SHOW TABLES;` dins de la base de dades.

### 3.6 Càrrega de les dades

**Què fa.** Insereix les files del CSV a la taula. L'opció `--no-create` fa que `csvsql` faci servir la taula definida a l'apartat anterior en lloc de crear-ne una de pròpia, i `--insert` hi carrega les dades. El paràmetre `-y 0` desactiva l'intent de detecció automàtica del format, que no funciona amb aquest fitxer i només genera un avís.

**Configuració.**

```bash
csvsql -y 0 --db "mysql+pymysql://bchecker:bchecker121@localhost/equipaments" \
       --tables equipaments_educacio --no-create --insert equipaments_educacio_utf8.csv
```


![Fig15](img/bbdd/12-carrega-csvsql.png)

*Execució de la càrrega de dades amb `csvsql`.*

**Correcció dels codis postals.** En fer la càrrega, `csvsql` va interpretar els codis postals com a nombres i va perdre el zero inicial (`8024` en lloc de `08024`). Com que tots els codis postals de Barcelona tenen 5 dígits, es va corregir emplenant amb zeros per l'esquerra:

```sql
UPDATE equipaments_educacio
SET addresses_zip_code = LPAD(addresses_zip_code, 5, '0')
WHERE addresses_zip_code IS NOT NULL;
```

**Comprovació.** Es compta quants registres té la taula (ha de coincidir amb els 2710 del CSV) i es seleccionen unes quantes files per verificar que les dades són correctes: accents (`Til·ler`), codis postals amb el zero inicial i dates.

```bash
mysql -u bchecker -pbchecker121 equipaments -e "SELECT COUNT(*) FROM equipaments_educacio;"
mysql -u bchecker -pbchecker121 equipaments -e "SELECT register_id, name, addresses_zip_code, created FROM equipaments_educacio LIMIT 5;"
```

| Comprovació | Resultat obtingut |
|---|---|
| Nombre de registres | `2710` |
| Codis postals | `08024`, `08030`, `08038` (amb zero inicial) |
| Caràcters especials | `Institut Escola El Til·ler` correcte |


![Fig16](img/bbdd/13-verificacio-final.png)

*Recompte de registres i mostra de 5 files de la taula `equipaments_educacio`.*
