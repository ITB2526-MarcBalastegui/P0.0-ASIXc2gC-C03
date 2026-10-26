# R-N01 – Router

R-N01 és el router de la infraestructura. Connecta la DMZ i la Intranet entre elles i amb Internet, fa NAT perquè les màquines internes puguin sortir a l'exterior, i més endavant farà de tallafocs entre les dues xarxes. És la primera màquina que es desplega, perquè la resta depenen d'ella per tenir connectivitat.

## 1. Creació de la màquina

La màquina s'ha creat a IsardVDI a partir de la plantilla **Ubuntu Server 24.04**. S'ha triat Ubuntu Server perquè no té entorn gràfic, i per tant consumeix pocs recursos i redueix la superfície d'atac. A més, la versió 24.04 és LTS i rep actualitzacions de seguretat fins al 2029.

| Paràmetre | Valor | Justificació |
|---|---|---|
| vCPUs | 2 | L'encaminament i el NAT consumeixen molt poca CPU |
| Memòria | 2 GB | Suficient per a Ubuntu Server sense entorn gràfic |
| Disc | Hard Disk (per defecte) | El de la plantilla |

### Adaptadors de xarxa

El router és l'única màquina que té una interfície a cada xarxa, perquè és qui ha de passar el trànsit d'una a l'altra.

| Adaptador | Xarxa d'Isard | MAC | Interfície | Ús |
|---|---|---|---|---|
| 1 | Default | 52:54:00:06:1c:58 | `enp1s0` | Sortida a Internet. Rep la IP per DHCP de la xarxa NAT d'Isard |
| 2 | ASIXC2-ITB13 | 52:54:00:2b:f7:20 | `enp2s0` | DMZ. Gateway dels servidors exposats, IP 10.3.1.1/24 |
| 3 | ASIXC2-ITB14 | 52:54:00:3f:fe:83 | `enp3s0` | Intranet. Gateway de les màquines internes, IP 10.3.2.1/24 |
| 4 | Wireguard VPN | 52:54:00:64:a3:b4 | `enp4s0` | Accés d'administració per SSH des de fora d'Isard |

S'han apuntat les adreces MAC perquè dins del sistema les interfícies es diuen `enp1s0`, `enp2s0`, etc. Comparant les MAC és l'única manera segura de saber quina interfície correspon a cada xarxa abans de configurar les IPs.

**Figura 1** · (/docs/img/fig01-isard-xarxes-r-n01.png)

*Configuració de recursos i xarxes de l'escriptori R-N01 a IsardVDI.*

## 2. Configuració base

Aquesta configuració s'aplica igual a totes les màquines del projecte. Així totes queden homogènies i compleixen el requisit de l'usuari bchecker.

### 2.1 Nom de la màquina i usuari bchecker

```bash
sudo hostnamectl set-hostname R-N01
sudo adduser bchecker
sudo usermod -aG sudo bchecker
```

- **`hostnamectl set-hostname`** canvia el nom de la màquina de forma persistent. El nom segueix la nomenclatura del projecte i és el que després resoldrà el DNS. El prompt no canvia fins que es torna a iniciar sessió, per això a la captura encara es veu `ubuntu2404`.
- **`adduser bchecker`** crea l'usuari amb el seu directori personal i li assigna la contrasenya `bchecker121`. Els camps d'informació personal s'han deixat en blanc perquè no són necessaris. L'enunciat exigeix aquest usuari a tots els sistemes, per a la correcció.
- **`usermod -aG sudo bchecker`** afegeix l'usuari al grup `sudo`, perquè pugui administrar la màquina. L'opció `-a` (*append*) és important: sense ella, l'usuari perdria la resta de grups als quals pertany.

**Figura 2** · `docs/img/fig02-hostname-adduser.png`

*Canvi de hostname i creació de l'usuari bchecker.*

### 2.2 Zona horària i actualització del sistema

```bash
sudo timedatectl set-timezone Europe/Madrid
sudo apt update && sudo apt upgrade -y
```

- **Zona horària Europe/Madrid:** els logs del sistema i dels serveis han de tenir l'hora local. Si no, és difícil relacionar-los amb les proves i les captures, i en un context de seguretat és clau saber exactament quan ha passat cada cosa.
- **`apt update`** actualitza la llista de paquets disponibles, i **`apt upgrade -y`** instal·la les actualitzacions pendents. Es fa abans de configurar cap servei, perquè la plantilla pot tenir paquets antics amb vulnerabilitats conegudes. Durant l'actualització, el sistema ha demanat el joc de caràcters de la consola i s'ha triat *Latin1 and Latin5*, que és el que suporta els accents del català i del castellà.

**Figura 3** · `docs/img/fig03-timezone-apt-update.png`

*Configuració de la zona horària i actualització del sistema.*

### 2.3 Accés SSH

```bash
sudo apt install -y openssh-server
sudo systemctl enable --now ssh
```

- El paquet `openssh-server` ja venia instal·lat a la plantilla, com indica el missatge *"already the newest version"*. Tot i així, s'executa l'ordre per assegurar-ho.
- **`systemctl enable --now ssh`** arrenca el servei immediatament (`--now`) i el deixa activat perquè arrenqui sol en cada reinici (`enable`). Això és el que permet administrar el router en remot, i el que permetrà al professor entrar-hi amb l'usuari bchecker.

**Figura 4** · `docs/img/fig04-ssh-install.png`

*Instal·lació i activació del servei SSH.*

### 2.4 Comprovació

```bash
hostnamectl | grep hostname
id bchecker
timedatectl | grep "Time zone"
systemctl status ssh
```

| Comprovació | Resultat obtingut |
|---|---|
| Hostname | `Static hostname: R-N01` |
| Usuari bchecker | uid 1001, inclòs al grup `27(sudo)` |
| Zona horària | `Europe/Madrid (CEST, +0200)` |
| Servei SSH | `active (running)` i `enabled` |

A més, aquestes comprovacions ja s'han fet havent iniciat sessió com a **bchecker** (el prompt mostra `bchecker@R-N01`). Això demostra que l'usuari funciona i que pot fer servir `sudo`.

**Figura 5** · `docs/img/fig05-comprovacio-base.png`

*Comprovació final de la configuració base.*

## 3. Serveis

### 3.1 Configuració de xarxa (netplan)

**Què fa.** Assigna a cada interfície la seva xarxa: la Default obté IP per DHCP d'Isard i és la sortida a Internet, mentre que ITB13 (DMZ) i ITB14 (Intranet) reben una IP fixa, que fa de *gateway* de cada xarxa. La VPN també obté IP per DHCP, però amb una mètrica més alta perquè la ruta per defecte continuï sortint per la Default.

**Configuració.** Fitxer `/etc/netplan/50-cloud-init.yaml`. Abans, s'ha desactivat la configuració de xarxa de cloud-init perquè no el sobreescrigui en reiniciar.

```yaml
network:
  version: 2
  ethernets:
    enp1s0:
      dhcp4: true
    enp2s0:
      addresses: [10.3.1.1/24]
    enp3s0:
      addresses: [10.3.2.1/24]
    enp4s0:
      dhcp4: true
      dhcp4-overrides:
        route-metric: 200
        use-dns: false
```

**Figura 6** · `docs/img/fig06-netplan-nano.png`

*Fitxer de configuració de netplan editat amb nano.*

**Comprovació.** Les IPs de cada interfície s'han de correspondre amb la taula d'adreçament, i `ip route` ha de tenir una sola ruta `default`, per `enp1s0`.

**Figura 7** · `docs/img/fig07-netplan-aplicat.png`

*Aplicació del netplan i sortida de `ip -br a` i `ip route`.*

### 3.2 Reenviament de paquets (IP forwarding)

**Què fa.** Permet al router passar paquets d'una xarxa a l'altra. Sense aquest reenviament, R-N01 només respondria per a ell mateix, i les màquines de la DMZ i la Intranet no es veurien.

**Configuració.** Fitxer `/etc/sysctl.d/99-router.conf` amb la línia `net.ipv4.ip_forward=1`. El fitxer ha de tenir l'extensió `.conf`, perquè `sysctl --system` només llegeix aquest tipus de fitxer.

```bash
echo "net.ipv4.ip_forward=1" | sudo tee /etc/sysctl.d/99-router.conf
sudo sysctl --system
```

**Comprovació.** Després d'aplicar-ho amb `sudo sysctl --system`, `sysctl net.ipv4.ip_forward` ha de mostrar `= 1`.

```bash
sysctl net.ipv4.ip_forward
```

**Figura 8** · `docs/img/fig08-ip-forwarding.png`

*Aplicació del reenviament de paquets i valor de `net.ipv4.ip_forward`.*

### 3.3 NAT (MASQUERADE)

**Què fa.** Permet que les màquines de la DMZ i la Intranet surtin a Internet. Les seves IPs són privades (10.3.x.x) i Internet no sap tornar-hi, així que el router canvia l'origen dels paquets per la seva pròpia IP de la Default. Les respostes tornen al router i ell les reenvia a la màquina que les ha demanat.

**Configuració.** Una regla a la taula `nat`, cadena `POSTROUTING`, que aplica a les màquines internes amb sortida per la Default:

```bash
sudo iptables -t nat -A POSTROUTING -s 10.3.0.0/16 -o enp1s0 -j MASQUERADE
```

La regla es fa persistent amb `iptables-persistent`, perquè sobrevisqui a un reinici:

```bash
sudo apt install -y iptables iptables-persistent
sudo netfilter-persistent save
```

**Comprovació.** Des d'una màquina interna amb la ruta per defecte via el router (en aquest cas W-N01), es fa `ping -c 3 8.8.8.8`. Després, al router, el comptador de la regla ha de ser superior a 0.

```bash
sudo iptables -t nat -L POSTROUTING -n -v
```

Durant la configuració, el comptador es va mantenir a 0 mentre W-N01 tenia encara la ruta per defecte per la interfície Default. Això va passar perquè el trànsit no arribava al router. Quan la ruta va passar a ser via `10.3.1.1`, el NAT va començar a funcionar. Es van fer diversos pings per comprovar com el comptador pujava; en aquest cas el número és 6, perquè s'han realitzat 6 pings.

**Figura 9** · `docs/img/fig09-nat-comptador.png`

*Comptador de la regla MASQUERADE després dels pings a 8.8.8.8 des de W-N01.*
