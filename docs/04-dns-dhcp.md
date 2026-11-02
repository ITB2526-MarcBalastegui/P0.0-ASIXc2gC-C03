# D-N01 – DNS i DHCP

D-N01 és el servidor de noms i de configuració de xarxa de la Intranet. Ofereix el servei DHCP perquè els clients rebin IP, gateway i DNS de manera automàtica, i el servei DNS perquè totes les màquines es resolguin pel nom (R-N01, R, F-N01, W-N01, B-N01 i D-N01). Té una única interfície, a ITB14, perquè és una màquina de la Intranet.

## 1. Creació de la màquina

La màquina s'ha creat a IsardVDI a partir de la plantilla **Ubuntu Server 24.04**, com la resta de servidors del projecte. Té una sola interfície perquè només ha de treballar dins de la Intranet.

| Paràmetre | Valor | Justificació |
|---|---|---|
| vCPUs | 2 | El DNS i el DHCP consumeixen molt poc |
| Memòria | 2 GB | Suficient per a Ubuntu Server sense entorn gràfic |
| Disc | Hard Disk (per defecte) | El de la plantilla |

### Adaptadors de xarxa

| Adaptador | Xarxa d'Isard | MAC | Interfície | Ús |
|---|---|---|---|---|
| 1 | ASIXC2-ITB14 | 52:54:00:59:34:2a | `enp1s0` | Intranet. IP fixa 10.3.2.20/24, gateway 10.3.2.1 |

Només té ITB14 perquè és una màquina de la Intranet. No necessita la interfície Default, perquè la sortida a Internet la fa el router. Així, els paquets de D-N01 surten pel gateway 10.3.2.1, i el NAT de R-N01 els deixa sortir.

**Figura D1** · `docs/img/d-n01-fig01-isard-xarxes.png`

*Configuració de recursos i xarxes de l'escriptori D-N01 a IsardVDI.*

## 2. Configuració base

Aquesta configuració és la mateixa que la de R-N01 i la de la resta de màquines del projecte. La xarxa s'ha configurat abans (vegeu l'apartat 3.1), perquè la màquina necessita sortida pel router per instal·lar paquets.

### 2.1 Nom de la màquina i usuari bchecker

```
sudo hostnamectl set-hostname D-N01
sudo adduser bchecker
sudo usermod -aG sudo bchecker
```

- **`hostnamectl set-hostname`** canvia el nom de la màquina de forma persistent. És el nom que el DNS ha de resoldre, i el que els clients veuran a la xarxa.
- **`adduser bchecker`** crea l'usuari amb el seu directori personal i li assigna la contrasenya `bchecker121`. Els camps d'informació personal s'han deixat en blanc perquè no són necessaris. L'enunciat exigeix aquest usuari a tots els sistemes, per a la correcció.
- **`usermod -aG sudo bchecker`** afegeix l'usuari al grup `sudo`, perquè pugui administrar la màquina. L'opció `-a` (*append*) és important: sense ella, l'usuari perdria la resta de grups als quals pertany.

**Figura D2** · `docs/img/d-n01-fig02-hostname-adduser.png`

*Canvi de hostname i creació de l'usuari bchecker.*

### 2.2 Zona horària i actualització del sistema

```
sudo timedatectl set-timezone Europe/Madrid
sudo apt update && sudo apt upgrade -y
```

- **Zona horària Europe/Madrid:** els logs del DNS i del DHCP han de tenir l'hora local. Així es poden relacionar amb les proves i amb les captures dels clients.
- **`apt update`** actualitza la llista de paquets, i **`apt upgrade -y`** instal·la les actualitzacions pendents. Es fa abans de instal·lar bind9 i el servidor DHCP, perquè la plantilla pot tenir paquets antics. Durant l'actualització, el sistema demana el joc de caràcters de la consola: s'ha triat *Latin1 and Latin5*, que és el que suporta els accents del català i del castellà.

**Figura D3** · `docs/img/d-n01-fig03-timezone-apt-update.png`

*Configuració de la zona horària i actualització del sistema.*

### 2.3 Accés SSH

```
sudo apt install -y openssh-server
sudo systemctl enable --now ssh
```

- El paquet `openssh-server` ja venia instal·lat a la plantilla. S'executa l'ordre per assegurar-ho.
- **`systemctl enable --now ssh`** arrenca el servei immediatament i el deixa activat perquè arrenqui sol en cada reinici. Així es pot administrar D-N01 en remot, i el professor hi pot entrar amb l'usuari bchecker.

**Figura D4** · `docs/img/d-n01-fig04-ssh.png`

*Instal·lació i activació del servei SSH.*

### 2.4 Comprovació

```
hostnamectl | grep hostname
id bchecker
timedatectl | grep "Time zone"
systemctl status ssh
```

| Comprovació | Resultat obtingut |
|---|---|
| Hostname | `Static hostname: D-N01` |
| Usuari bchecker | Inclòs al grup `27(sudo)` |
| Zona horària | `Europe/Madrid (CEST, +0200)` |
| Servei SSH | `active (running)` i `enabled` |

**Figura D5** · `docs/img/d-n01-fig05-comprovacio-base.png`

*Comprovació final de la configuració base.*

## 3. Serveis

*Pendent.* Aquest apartat inclourà:

- 3.1 Configuració de xarxa (netplan)
- 3.2 Servidor DHCP
- 3.3 Servidor DNS (bind9)
