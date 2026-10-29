# W-N01 – Servidor web

W-N01 és el servidor web de la infraestructura. És a la DMZ perquè és un servei pensat per ser consultat des de fora, i serveix l'aplicació que mostra les dades de la base de dades de B-N01. Surt a Internet a través de R-N01, de manera que tot el seu trànsit passa pel router.

## 1. Creació de la màquina

La màquina s'ha creat a IsardVDI a partir de la plantilla **Ubuntu Server 24.04**. S'ha triat Ubuntu Server perquè no té entorn gràfic, i per tant consumeix pocs recursos i redueix la superfície d'atac. A més, la versió 24.04 és LTS i rep actualitzacions de seguretat fins al 2029.

| Paràmetre | Valor | Justificació |
|---|---|---|
| vCPUs | 2 | Apache i PHP consumeixen poca CPU amb el volum de peticions del projecte |
| Memòria | 4 GB | Ubuntu Server sense entorn gràfic, amb marge per a Apache i PHP quan l'aplicació consulti la base de dades |
| Disc | Hard Disk (per defecte) | El de la plantilla |

### Adaptadors de xarxa

| Adaptador | Xarxa d'Isard | MAC | Interfície | Ús |
|---|---|---|---|---|
| 1 | Default | 52:54:00:39:73:85 | `enp1s0` | Només per instal·lar paquets abans que R-N01 funcionés. Ara sense configuració |
| 2 | ASIXC2-ITB13 | 52:54:00:57:50:ff | `enp2s0` | DMZ. IP fixa 10.3.1.20/24, gateway 10.3.1.1 (R-N01) |

S'han apuntat les adreces MAC perquè dins del sistema les interfícies es diuen `enp1s0`, `enp2s0`, etc. Comparant les MAC és l'única manera segura de saber quina interfície correspon a cada xarxa abans de configurar les IPs.

La interfície Default és temporal: la DMZ ha de sortir a Internet a través de R-N01, no directament per Isard. Si es mantingués activa, el trànsit no passaria pel router i el tallafocs no serviria de res.

![Paràmetres de W-N01 a Isard](/img/web/hardware_w-n01)

*Configuració de recursos i xarxes de l'escriptori W-N01 a IsardVDI.*

## 2. Configuració base

Aquesta configuració s'aplica igual a totes les màquines del projecte. Així totes queden homogènies i compleixen el requisit de l'usuari bchecker.

### 2.1 Nom de la màquina i usuari bchecker

```bash
sudo hostnamectl set-hostname W-N01
sudo useradd -m -s /bin/bash -G sudo bchecker
echo 'bchecker:bchecker121' | sudo chpasswd
```

A `/etc/hosts`, la línia del propi equip:

```
127.0.1.1 W-N01
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
sudo apt update && sudo apt full-upgrade -y
sudo apt install -y vim curl git net-tools iputils-ping
```

- **Zona horària Europe/Madrid:** els logs del sistema i dels serveis han de tenir l'hora local. Si no, és difícil relacionar-los amb les proves i les captures, i en un context de seguretat és clau saber exactament quan ha passat cada cosa.
- **`apt update`** actualitza la llista de paquets disponibles, i **`apt full-upgrade -y`** instal·la les actualitzacions pendents. A diferència d'`apt upgrade`, també pot instal·lar o treure paquets si una actualització ho necessita (per exemple, un nucli nou). Es fa abans de configurar cap servei, perquè la plantilla pot tenir paquets antics amb vulnerabilitats conegudes.
- **Eines bàsiques:** la plantilla d'Isard és una instal·lació mínima. S'hi afegeixen `vim` (editor), `curl` (proves HTTP), `git` (desplegament de l'aplicació), `net-tools` i `iputils-ping` (diagnòstic de xarxa; la plantilla no inclou ni l'ordre `ping`).

### 2.3 Accés SSH

```bash
sudo apt install -y openssh-server
sudo systemctl enable --now ssh
```

- El paquet `openssh-server` ja venia instal·lat a la plantilla, com indica el missatge *"already the newest version"*. Tot i així, s'executa l'ordre per assegurar-ho.
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
        macaddress: "52:54:00:57:50:ff"
      dhcp4: false
      mtu: 1446
      addresses: [10.3.1.20/24]
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

**Procés.** Mentre R-N01 no funcionava, la màquina tenia un segon fitxer (`50-cloud-init.yaml`) que donava IP per DHCP a la interfície Default, per poder instal·lar paquets. Quan el router va estar operatiu, es va afegir el gateway 10.3.1.1 a la ITB13 i es va eliminar aquest fitxer.

**Incidència.** En el primer intent, en lloc de crear un fitxer nou per a la ITB13, es va substituir el contingut del fitxer original, que configurava la Default. En aplicar-lo, la Default es va quedar sense IP i la màquina sense sortida a Internet:

- `curl -I http://archive.ubuntu.com` → *Could not resolve host*
- `curl -I http://91.189.91.81` → *Couldn't connect to server*. No era només el DNS: no hi havia cap sortida.
- `ip route` → només hi havia la ruta de la DMZ, cap ruta `default`.

Es va solucionar separant la configuració en un fitxer per interfície. La causa és que netplan només configura les interfícies que té definides: si se n'esborra una dels fitxers, en reaplicar-los es queda sense configuració.

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
ping -c 3 10.3.1.10
```

| Comprovació | Resultat obtingut |
|---|---|
| Hostname | `W-N01` |
| Usuari bchecker | `uid=1001(bchecker)`, inclòs al grup `27(sudo)` |
| Zona horària | `Europe/Madrid` |
| Servei SSH | `passwordauthentication yes` i accés per SSH com a bchecker |
| Netplan: IP | `10.3.1.20/24` a `enp2s0` |
| Netplan: rutes | `default via 10.3.1.1 dev enp2s0` i `10.3.1.0/24 dev enp2s0` |
| Sortida a Internet | `curl` rep resposta HTTP de `archive.ubuntu.com` |
| Connectivitat amb F-N01 | `ping` a 10.3.1.10 amb resposta |

A més, aquestes comprovacions ja s'han fet havent iniciat sessió com a **bchecker** (el prompt mostra `bchecker@W-N01`). Això demostra que l'usuari funciona i que pot fer servir `sudo`.

![Comprovació de la configuració base](img/web/comprovacions_w-n01.png)
![Comprovació de la xarxa](img/web/xarxa_w-n01.png)

*Comprovació final de la configuració base.*

## 3. Serveis

### 3.1 Servidor web (Apache i PHP)

**Què fa.** Apache és el servidor web: rep les peticions dels navegadors i retorna les pàgines. PHP genera pàgines dinàmiques, i és el que farà servir l'aplicació per consultar la base de dades de B-N01 i mostrar-ne les dades.

**Configuració.**

```bash
sudo apt install -y apache2 php libapache2-mod-php php-mysql
```

- **`apache2`:** el servidor web.
- **`php`:** l'intèrpret de PHP (versió 8.3 a Ubuntu 24.04).
- **`libapache2-mod-php`:** connecta Apache amb PHP. Sense aquest mòdul, Apache no executaria els fitxers `.php` i els retornaria com a text.
- **`php-mysql`:** permet a PHP connectar-se a MySQL, necessari perquè l'aplicació llegeixi la base de dades de B-N01.

Per comprovar que Apache i PHP treballen junts, es crea una pàgina de prova mínima:

```bash
echo '<?php echo "PHP funciona a " . gethostname() . "\n"; ?>' | sudo tee /var/www/html/prova.php
```

**Comprovació.**

```bash
systemctl status apache2
php -v
php -m | grep -i mysql
curl localhost/prova.php
```

- `systemctl status apache2` ha de mostrar `active (running)` i `enabled`, és a dir, que el servei funciona i arrencarà sol en cada reinici.
- `php -v` ha de mostrar PHP 8.3.
- `php -m | grep -i mysql` ha de mostrar els mòduls `mysqli`, `mysqlnd` i `pdo_mysql`.
- `curl localhost/prova.php` ha de retornar `PHP funciona a W-N01`. Si retornés el codi PHP tal qual, voldria dir que Apache no passa els fitxers `.php` a PHP.

![Comprovació d'Apache i PHP](img/web/apache_w-n01.png)

*Estat d'Apache, versió de PHP, mòduls de MySQL i pàgina de prova.*

Finalment, es comprova que la web és accessible des d'una altra màquina de la DMZ. Des de F-N01:

```bash
curl http://10.3.1.20/prova.php
```

Retorna `PHP funciona a W-N01`, cosa que demostra que les IPs fixes de la DMZ funcionen i que el servidor web serveix pàgines a altres equips.

![Accés a la web des de F-N01](img/web/acces-desde-f_n01.png)

*Petició a la pàgina de prova de W-N01 des de F-N01.*
