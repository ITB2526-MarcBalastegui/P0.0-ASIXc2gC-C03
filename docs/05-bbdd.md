# Instal.lació i Configuració del servei de BBDD

Servidor de base de dades (MySQL) del projecte P0.0.

**Resum de l'equip**

| Camp | Valor |
|---|---|
| Hostname | B-N01 |
| Sistema operatiu | Ubuntu Server 24.04 LTS |
| Zona | Intranet |
| IP fixa | 10.3.2.10/24 |
| Gateway | 10.3.2.1 |
| DNS | 10.3.2.20 |
| Servei | MySQL 8.0 |
| Usuari | bchecker |

---

## 1. Creació de la màquina

### 1.1. Sistema operatiu

Per a aquest servidor, que oferirà el servei de base de dades, hem creat una màquina virtual a l'IsardVDI del núvol. Hem triat **Ubuntu Server 24.04 LTS** pels motius següents:

- No disposa d'entorn gràfic, de manera que consumeix menys recursos i ofereix un millor rendiment.
- Linux incorpora eines natives per administrar serveis com MySQL, cosa que resulta més senzilla i pràctica que en Windows.

### 1.2. Recursos i adaptadors de xarxa

A la màquina li hem afegit dos adaptadors de xarxa:

| Adaptador | Funció |
|---|---|
| `Default` | **Temporal.** Ens permet instal·lar els serveis mentre es configura el servidor DNS/DHCP. |
| `ASIXC2-ITB14` | Xarxa interna que compartirà amb la resta de màquines del projecte. |

![Maquinari de B-N01 a IsardVDI: 4 vCPU, 8 GB de memòria i els adaptadors Default i ASIXC2-ITB14](img//bbdd/01-hardware-isard.png)

---

## 2. Configuració base

### 2.1. Xarxa (netplan)

Un cop creada la màquina, hem configurat el fitxer de netplan (`/etc/netplan/50-cloud-init.yaml`) per establir la **IP fixa del segon adaptador**:

- Adreça IP: `10.3.2.10/24`
- Gateway: `10.3.2.1`
- Servidor DNS: `10.3.2.20`

El primer adaptador (`Default`) es manté amb DHCP, ja que només l'utilitzem de forma temporal per accedir a internet.

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

![Fitxer netplan de B-N01](img/bbdd/02-netplan.png)

Comprovem que les interfícies han rebut l'adreça esperada:

```bash
ip -br a
```

![Sortida d'ip -br a: enp1s0 amb IP per DHCP i enp2s0 amb 10.3.2.10/24](img/bbdd/03-ip-br-a.png)

### 2.2. Hostname i usuari

Després de configurar la xarxa, hem canviat el hostname a `B-N01` i hem creat l'usuari que demana la tasca, `bchecker`, amb la contrasenya `bchecker121` i pertinença al grup `sudo`.

```bash
sudo hostnamectl set-hostname B-N01
sudo adduser bchecker
sudo usermod -aG sudo bchecker
```

Comprovació:

```bash
hostnamectl
id bchecker
```

![Sortida de hostnamectl i id bchecker](img/bbdd/04-hostnamectl.png)

### 2.3. Actualitzacions i SSH

> **Nota:** aquest apartat no apareixia al document original. Cal afegir-hi la captura de les comprovacions.

Actualitzem el sistema i instal·lem i activem el servei SSH:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y openssh-server
sudo systemctl enable --now ssh
```

Comprovació:

```bash
sudo systemctl status ssh
```

<!-- TODO: afegir captura de systemctl status ssh -->

---

## 3. Serveis

### 3.1. Instal·lació de MySQL

Un cop acabada la configuració inicial, instal·lem el servei MySQL i l'activem amb `systemctl` perquè s'iniciï automàticament amb el sistema.

```bash
sudo apt install -y mysql-server
sudo systemctl enable --now mysql
sudo systemctl status mysql
```

La captura mostra el servei en estat `active (running)`:

![Estat del servei MySQL amb systemctl status](img/bbdd/05-systemctl-mysql.png)

### 3.2. Creació de la base de dades i de l'usuari

Accedim a MySQL com a `root` i hi creem la base de dades `equipaments` amb codificació **utf8mb4**. Ho fem així perquè el CSV és en català i conté una gran diversitat de caràcters (accents, ç, punt volat...) que MySQL ha de poder interpretar correctament. També hi creem l'usuari `bchecker` amb tots els privilegis sobre aquesta base de dades.

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

Finalment, comprovem que la base de dades s'ha creat correctament utilitzant el nou usuari:

```bash
mysql -u bchecker -pbchecker121 -e "SHOW DATABASES;"
```

![Creació de la base de dades i comprovació amb l'usuari bchecker](img/bbdd/06-mysql-creacio-bd.png)

---

## 4. Dades: el fitxer CSV

### 4.1. Descàrrega

Creem un directori dedicat al CSV a la home, anomenat `data`, i hi descarreguem el fitxer amb `wget` a partir de la URL proporcionada, guardant-lo com a `equipaments_educacio.csv`.

```bash
mkdir -p ~/data && cd ~/data
wget -O equipaments_educacio.csv "https://opendata-ajuntament.barcelona.cat/data/dataset/f36b60f2-9541-4d08-b0f9-b0a9313fab3d/resource/29d9ff10-6892-4f16-9012-d5c4997857e7/download"
```

### 4.2. Conversió de la codificació

Quan vam tenir el fitxer, vam intentar extreure'n les dades amb l'eina `csvcut`, però ens vam trobar que el fitxer està codificat en **UTF-16**, mentre que nosaltres treballem en **UTF-8**.

Per això hem convertit la codificació a UTF-8 amb `iconv`, generant una còpia anomenada `equipaments_educacio_utf8.csv` (l'original queda intacte). Després de la conversió:

- Verifiquem la codificació amb `file -i`.
- Mirem la capçalera amb els noms de les columnes i dues files d'exemple amb `head -n 3`.
- Comptem les línies amb `wc -l`: un total de **2711 línies**.

```bash
iconv -f UTF-16LE -t UTF-8 equipaments_educacio.csv > equipaments_educacio_utf8.csv
file -i equipaments_educacio_utf8.csv
head -n 3 equipaments_educacio_utf8.csv
wc -l equipaments_educacio_utf8.csv
```

![Comprovació de la codificació, capçalera i nombre de línies](img/bbdd/07-file-head-wc.png)

> Les 2711 línies no equivalen a 2710 registres: alguns camps contenen salts de línia. El nombre real de registres el dona `csvstat --count`.

### 4.3. Detecció del separador

Tornem a utilitzar `csvcut` amb el fitxer UTF-8 per comprovar quin separador fa servir el CSV. Provem si està separat per punt i coma o per tabuladors; en tots dos casos la sortida és una única columna llarga, cosa que demostra que el separador és la **coma**.

```bash
csvcut -d ';' -n equipaments_educacio_utf8.csv
csvcut -t -n equipaments_educacio_utf8.csv
```

![Proves de separador amb csvcut: en tots dos casos una sola columna](img/bbdd/08-csvcut-separador.png)

### 4.4. Anàlisi de columnes i tipus

Un cop adaptat el CSV, n'analitzem les columnes i els tipus:

- Amb `csvcut -n` obtenim la llista numerada de columnes (**40 columnes**).
- Amb `csvstat --count` comptem el nombre de registres: **2710**.
- Amb `csvstat` generem l'anàlisi detallat de cada columna (tipus, nuls, valors únics i longitud màxima) i el desem en un fitxer de text. Així el podem consultar còmodament sense haver d'executar l'ordre cada vegada, ja que la sortida és molt llarga i al servidor és complicat desplaçar-se per veure el contingut anterior.

```bash
csvcut -n equipaments_educacio_utf8.csv
csvstat --count equipaments_educacio_utf8.csv
csvstat equipaments_educacio_utf8.csv > ~/data/csvstat_salida.txt
```

Llista de columnes:

![Llista numerada de les 40 columnes del CSV](img/bbdd/09-csvcut-columnes.png)

Fitxer `csvstat_salida.txt` obert amb `nano`:

![Contingut de csvstat_salida.txt](img/bbdd/10-csvstat-txt.png)

---

## 5. Càrrega de les dades a MySQL

### 5.1. Generació i correcció de l'script de la taula

Amb `csvsql` generem un fitxer SQL amb la definició de la taula a partir del contingut del CSV:

```bash
csvsql -S -i mysql --tables equipaments_educacio equipaments_educacio_utf8.csv > create_table.sql
cat create_table.sql
```

El fitxer generat automàticament conté diverses incongruències, per això l'hem corregit manualment:

| Problema | Correcció |
|---|---|
| Dates com a `TIMESTAMP` (límit a l'any 2038) | `DATETIME` |
| `timetable` com a `VARCHAR(1648)` | `TEXT` |
| `addresses_zip_code` com a `DECIMAL` (perd el zero inicial) | `VARCHAR(5)` |
| Columnes buides detectades com a `BOOL` | `VARCHAR` |
| Identificadors com a `DECIMAL(38,0)` | `BIGINT` / `INT` |
| Coordenades com a `DECIMAL(38,x)` | `DOUBLE` |
| Longituds de `VARCHAR` sense marge | Longituds amb marge |
| Sense clau primària | `id INT AUTO_INCREMENT PRIMARY KEY` |

Un cop fets aquests canvis, el fitxer SQL queda a punt per executar-lo a la base de dades:

![Fitxer create_table.sql corregit](img/bbdd/11-create-table.png)

### 5.2. Creació de la taula

Creem la taula amb l'usuari `bchecker` dins de la base de dades `equipaments`, utilitzant el fitxer SQL anterior:

```bash
mysql -u bchecker -pbchecker121 equipaments < create_table.sql
```

### 5.3. Càrrega de les dades

A continuació carreguem les dades amb `csvsql`. L'opció `--no-create` fa que utilitzi la taula que hem definit nosaltres, i `--insert` hi insereix les files del CSV.

```bash
csvsql -y 0 --db "mysql+pymysql://bchecker:bchecker121@localhost/equipaments" \
       --tables equipaments_educacio --no-create --insert equipaments_educacio_utf8.csv
```

![Execució de la càrrega amb csvsql](img/bbdd/12-carrega-csvsql.png)

### 5.4. Correcció dels codis postals

> **Nota:** aquest pas no apareixia al document original, però és necessari perquè la captura final mostri els codis postals correctes.

En fer la càrrega, `csvsql` va interpretar els codis postals com a nombres i va perdre el zero inicial (`8024` en lloc de `08024`). Ho hem corregit emplenant amb zeros per l'esquerra fins a 5 caràcters:

```sql
UPDATE equipaments_educacio
SET addresses_zip_code = LPAD(addresses_zip_code, 5, '0')
WHERE addresses_zip_code IS NOT NULL;
```

---

## 6. Comprovació final

Per acabar, confirmem que la taula s'ha creat correctament comptant quants registres té i seleccionant una quantitat limitada d'informació per verificar que les dades són correctes (accents, codis postals amb zero inicial i dates).

```bash
mysql -u bchecker -pbchecker121 equipaments -e "SELECT COUNT(*) FROM equipaments_educacio;"
mysql -u bchecker -pbchecker121 equipaments -e "SELECT register_id, name, addresses_zip_code, created FROM equipaments_educacio LIMIT 5;"
```

Resultat: **2710 registres** carregats.

![Comprovació final: recompte de registres i mostra de 5 files](img/bbdd/13-verificacio-final.png)
