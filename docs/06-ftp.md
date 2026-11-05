# F-N01 – Servidor FTP

F-N01 és el servidor FTP de la infraestructura. És a la DMZ perquè és un servei pensat per ser accessible des de fora. Està en una màquina pròpia, separada del servidor web, perquè l'FTP envia usuaris i contrasenyes sense xifrar i és un dels serveis més atacats: si quedés compromès, W-N01 no se'n veuria afectat. Surt a Internet a través de R-N01.

## 1. Creació de la màquina

La màquina s'ha creat a IsardVDI a partir de la plantilla **Ubuntu Server 24.04**. S'ha triat Ubuntu Server perquè no té entorn gràfic, i per tant consumeix pocs recursos i redueix la superfície d'atac. A més, la versió 24.04 és LTS i rep actualitzacions de seguretat fins al 2029.

| Paràmetre | Valor | Justificació |
|---|---|---|
| vCPUs | 2 | Un servidor FTP consumeix molt poca CPU |
| Memòria | 4 GB | Ubuntu Server sense entorn gràfic, amb marge per a les transferències de fitxers |
| Disc | Hard Disk (per defecte) | El de la plantilla |

### Adaptadors de xarxa

| Adaptador | Xarxa d'Isard | MAC | Interfície | Ús |
|---|---|---|---|---|
| 1 | Default | 52:54:00:47:99:1b | `enp1s0` | Només per instal·lar paquets abans que R-N01 funcionés. Ara sense configuració |
| 2 | ASIXC2-ITB13 | 52:54:00:51:00:fe | `enp2s0` | DMZ. IP fixa 10.3.1.10/24, gateway 10.3.1.1 (R-N01) |

S'han apuntat les adreces MAC perquè dins del sistema les interfícies es diuen `enp1s0`, `enp2s0`, etc. Comparant les MAC és l'única manera segura de saber quina interfície correspon a cada xarxa abans de configurar les IPs.

La interfície Default és temporal: la DMZ ha de sortir a Internet a través de R-N01, no directament per Isard. Si es mantingués activa, el trànsit no passaria pel router i el tallafocs no serviria de res.

![Paràmetres de F-N01 a Isard](img/ftp/hardware_f-n01)

*Configuració de recursos i xarxes de l'escriptori F-N01 a IsardVDI.*

## 2. Configuració base

Aquesta configuració s'aplica igual a totes les màquines del projecte. Així totes queden homogènies i compleixen el requisit de l'usuari bchecker.

### 2.1 Nom de la màquina i usuari bchecker

```bash
sudo hostnamectl set-hostname F-N01
sudo useradd -m -s /bin/bash -G sudo bchecker
echo 'bchecker:bchecker121' | sudo chpasswd
```

A `/etc/hosts`, la línia del propi equip:

```
127.0.1.1 F-N01
```

- **`hostnamectl set-hostname`** canvia el nom de la màquina de forma persistent. El nom segueix la nomenclatura del projecte i és el que després resoldrà el DNS. El prompt no canvia fins que es torna a iniciar sessió.
- **`/etc/hosts`** s'actualitza perquè la màquina pugui resoldre el seu propi nom. Si no, ordres com `sudo` mostren un avís perquè no troben el hostname.
- **`useradd -m -s /bin/bash -G sudo bchecker`** crea l'usuari que l'enunciat exigeix a tots els sistemes, per a la correcció:
  - `-m` crea el seu directori personal.
  - `-s /bin/bash` li assigna bash com a intèrpret d'ordres.
  - `-G sudo` l'afegeix al grup `sudo`, perquè pugui administrar la màquina.
- **`chpasswd`** li assigna la contrasenya `bchecker121` sense haver-la d'escriure de forma interactiva.

### 2.2 Zona horària i actualització del sistema

```bash
sudo timedatectl set-timezone Europe/Madrid
sudo apt update && sudo apt upgrade -y
sudo apt install -y nano curl net-tools iputils-ping
```

- **Zona horària Europe/Madrid:** els logs del sistema i dels serveis han de tenir l'hora local. Si no, és difícil relacionar-los amb les proves i les captures, i en un context de seguretat és clau saber exactament quan ha passat cada cosa.
- **`apt update`** actualitza la llista de paquets disponibles, i **`apt upgrade -y`** instal·la les actualitzacions pendents. Es fa abans de configurar cap servei, perquè la plantilla pot tenir paquets antics amb vulnerabilitats conegudes.
- **Eines bàsiques:** la plantilla d'Isard és una instal·lació mínima. S'hi afegeixen `nano` (editor), `curl` (proves HTTP), `net-tools` i `iputils-ping` (diagnòstic de xarxa; la plantilla no inclou ni l'ordre `ping`).

### 2.3 Accés SSH

```bash
sudo apt install -y openssh-server
sudo systemctl enable --now ssh
```

- El paquet `openssh-server` ja venia instal·lat a la plantilla. Tot i així, s'executa l'ordre per assegurar-ho.
- **`systemctl enable --now ssh`** arrenca el servei immediatament (`--now`) i el deixa activat perquè arrenqui sol en cada reinici (`enable`). Això és el que permet administrar el servidor en remot, i el que permetrà al professor entrar-hi amb l'usuari bchecker.
- Perquè bchecker pugui entrar amb contrasenya, SSH ha de tenir l'autenticació per contrasenya activada. Es comprova a l'apartat 2.5 amb `sshd -T`, que mostra la configuració que s'aplica realment, incloent-hi els fitxers de `/etc/ssh/sshd_config.d/`.

### 2.4 Configuració de xarxa (netplan)

**Què fa.** Assigna a la interfície ITB13 la IP fixa de la DMZ i fa que tot el trànsit cap a fora surti per R-N01 (10.3.1.1). El DNS és provisionalment 8.8.8.8, fins que D-N01 estigui operatiu.

**Configuració.** Fitxer `/etc/netplan/60-itb13.yaml`. Abans, s'ha desactivat la configuració de xarxa de cloud-init perquè no sobreescrigui netplan en reiniciar.

```bash
echo 'network: {config: disabled}' | sudo tee /etc/cloud/cloud.cfg.d/99-disable-network-config.cfg
```

```yaml
network:
  version: 2
  ethernets:
    itb13:
      match:
        macaddress: "52:54:00:51:00:fe"
      dhcp4: false
      mtu: 1446
      addresses: [10.3.1.10/24]
      routes:
        - to: default
          via: 10.3.1.1
      nameservers:
        addresses: [8.8.8.8]
```

```bash
sudo chmod 600 /etc/netplan/*.yaml
sudo netplan try
```

- **`match: macaddress`:** la interfície s'identifica per la MAC i no pel nom. Si es treu la Default a Isard, la ITB13 pot passar a dir-se `enp1s0`, i una configuració per nom deixaria de funcionar. La MAC no canvia.
- **`mtu: 1446`:** és el valor de les xarxes d'Isard. Amb el valor per defecte (1500), els paquets grans es perden i ordres com `apt` es queden penjades.
- **`netplan try`:** aplica la configuració i la desfà sola si no es confirma en 120 segons. Si un error deixa la màquina sense xarxa, es recupera sense haver de fer res.

**Procés.** Mentre R-N01 no funcionava, la màquina tenia un segon fitxer (`50-cloud-init.yaml`) que donava IP per DHCP a la interfície Default, per poder instal·lar paquets. Quan el router va estar operatiu, es va afegir el gateway 10.3.1.1 a la ITB13 i es va eliminar aquest fitxer. Es va configurar així des del principi, amb un fitxer per interfície, per evitar la pèrdua de connexió que va passar a W-N01 (vegeu [W-N01, apartat 2.4](07-web.md#24-configuració-de-xarxa-netplan)).

### 2.5 Comprovació

```bash
hostname
id bchecker
timedatectl | grep "Time zone"
sudo sshd -T | grep -i passwordauthentication
ssh bchecker@localhost
ip a
ip route
curl -I http://archive.ubuntu.com
ping -c 3 10.3.1.20
```

| Comprovació | Resultat obtingut |
|---|---|
| Hostname | `F-N01` |
| Usuari bchecker | `uid=1001(bchecker)`, inclòs al grup `27(sudo)` |
| Zona horària | `Europe/Madrid` |
| Servei SSH | `passwordauthentication yes` i accés per SSH com a bchecker |
| Netplan: IP | `10.3.1.10/24` a `enp2s0` |
| Netplan: rutes | `default via 10.3.1.1 dev enp2s0` i `10.3.1.0/24 dev enp2s0` |
| Sortida a Internet | `curl` rep resposta HTTP de `archive.ubuntu.com` |
| Connectivitat amb W-N01 | `ping` a 10.3.1.20 amb resposta |

A més, aquestes comprovacions ja s'han fet havent iniciat sessió com a **bchecker** (el prompt mostra `bchecker@F-N01`). Això demostra que l'usuari funciona i que pot fer servir `sudo`.

![Comprovació de la configuració base](img/ftp/comprovacions_f-n01)
![Comprovació de la xarxa](img/ftp/xarxa_f-n01)

*Comprovació final de la configuració base.*

## 3. Serveis

### 3.1 Servidor FTP (vsftpd)

**Què fa.** vsftpd és el servidor FTP, pensat perquè els usuaris hi puguin pujar i baixar fitxers. S'ha configurat perquè només hi entrin usuaris del sistema (no anònims), que quedin tancats a la seva pròpia carpeta i amb un rang fix de ports passius, que és el que permetrà obrir l'FTP al tallafocs del Sprint 3 sense obrir tota la màquina.

FTP fa servir dues connexions, la del port 21, per on viatgen les comandes i una de dades per on viatgen els fitxers. En mode passiu la connexió de dades la fa  el client cap  a un port del servidor dins d'un rang. Per això fixem el rang (40000-40100): així, el tallafocs sap quins ports ha d'obrir.

 
**Configuració**
 
Instal·lació:
 
```bash
sudo apt install -y vsftpd
```
Carpeta de l'FTP. vsftpd es nega a arrencar si l'usuari pot escriure a la carpeta on queda tancat (chroot), per seguretat. Per això l'arrel (`ftp/`) no és escrivible i a dins hi ha una subcarpeta (`files/`) on sí es pot pujar:

```bash
sudo mkdir -p /home/bchecker/ftp/files
sudo chown nobody:nogroup /home/bchecker/ftp
sudo chmod a-w /home/bchecker/ftp
sudo chown bchecker:bchecker /home/bchecker/ftp/files
```
Fitxer `/etc/vsftpd.conf` ([`config/F-N01/vsftpd.conf`](../config/F-N01/vsftpd.conf)):

```conf
listen=YES
listen_ipv6=NO
 
anonymous_enable=NO
local_enable=YES
write_enable=YES
 
chroot_local_user=YES
user_sub_token=$USER
local_root=/home/$USER/ftp
 
pasv_enable=YES
pasv_min_port=40000
pasv_max_port=40100
```
- **`anonymous_enable=NO`:** no s'admet accés anònim. Només usuaris del sistema amb contrasenya, ja que només ens interessa que entri `bchecker`
- **`local_enable=YES` i `write_enable=YES`:** permet l'accés amb usuaris locals i que puguin pujar fitxers.
- **`chroot_local_user=YES` amb `local_root`:** tanca cada usuari a `/home/<usuari>/ftp`. No pot sortir d'aquesta carpeta ni veure la resta del sistema.
- **`pasv_min_port` i `pasv_max_port`:** rang fix de ports per a la connexió de dades en mode passiu.

Activació del servei:
 
```bash
sudo systemctl restart vsftpd
sudo systemctl enable vsftpd
```
- **`restart`** aplica la nova configuració del fitxer `/etc/vsftpd.conf`.
- **`enable`** fa que el servei arrenqui sol en cada reinici de la màquina.

### 3.2 Comprovació
 
La prova es fa des del client **PC-Linux**, tal com demana l'enunciat: des d'una màquina de la Intranet, bchecker entra per FTP a F-N01 (DMZ), puja un fitxer i el baixa. Com que Intranet → DMZ està permès al disseny, la connexió funciona. S'usa `lftp`, que fa servir mode passiu per defecte (el que hem configurat).
 
```bash
lftp -u bchecker,bchecker121 10.3.1.10
cd files
put /etc/hostname
ls
get hostname -o /tmp/baixat.txt
exit
cat /tmp/baixat.txt
```
 
| Comprovació | Resultat obtingut |
|---|---|
| Servei vsftpd | `active (running)` i `enabled` |
| Connexió Intranet → DMZ | PC-Linux es connecta a F-N01 (10.3.1.10) |
| Accés de bchecker | Entra amb usuari i contrasenya |
| Chroot | A l'arrel només es veu `files`; l'usuari no surt de la seva carpeta |
| Pujada (`put`) | `9 bytes transferred`, `hostname` apareix al `ls` |
| Baixada (`get`) | `cat /tmp/baixat.txt` mostra `PC-Linux` |
 
El fitxer baixat conté `PC-Linux` perquè s'ha pujat el `/etc/hostname` del propi client: això demostra que el fitxer ha fet el viatge sencer client → F-N01 → client.
 
![Prova de l'FTP des del PC Linux](img/ftp/lftp-comprovacio)
 
*Pujada i baixada per FTP a F-N01 des del client PC-Linux (Intranet).*

