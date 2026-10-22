# F-N01: Servidor FTP

Responsable: Marc Balastegui

| Paràmetre | Valor |
|---|---|
| Hostname | F-N01 |
| Sistema | Ubuntu Server 24.04 |
| Xarxa | DMZ (ITB13) |
| IP | 10.3.1.10/24 |
| Gateway | 10.3.1.1 (R-N01) |
| Serveis | SSH (FTP al Sprint 2) |

---

## 1. Configuració de la màquina a IsardVDI

![Paràmetres fe F-N01 a Isard](img/f-n01-isard.png)

| Paràmetre | Valor |
|---|---|
| Plantilla | Ubuntu Server 24.04 (catàleg d'Isard) |
| vCPU | [omplir] |
| RAM | [omplir] |
| Disc | [omplir] |

### Interfícies de xarxa

| Interfície Isard | Nom a Linux | MAC | Ús |
|---|---|---|---|
| Default | enp1s0 | [omplir] | Temporal: sortida a Internet per instal·lar paquets |
| ITB13 | enp2s0 | [omplir] | DMZ, IP fixa 10.3.1.10 |
 
La interfície Default és temporal pel mateix motiu que a [W-N01](06-web.md#1-configuració-de-la-màquina-a-isardvdi).
 
---
 
## 2. Configuració base

Mateix procediment que a [W-N01](06-web.md#2-configuració-base), canviant el hostname i la IP.

### 2.1 Sistema, hostname i usuari bchecker

```bash
sudo apt update
sudo apt install -y openssh-server nano curl net-tools iputils-ping
sudo systemctl enable --now ssh
sudo hostnamectl set-hostname F-N01
sudo timedatectl set-timezone Europe/Madrid
sudo useradd -m -s /bin/bash -G sudo bchecker
echo 'bchecker:bchecker121' | sudo chpasswd
```

A `/etc/hosts`:

```
127.0.1.1 F-N01
```

### 2.2 Xarxa (netplan)

**Interfíficie Default** ([`config/F-N01/50-cloud-init.yaml`](../config/F-N01/50-cloud-init.yaml)):

```yaml
network:
  version: 2
  ethernets:
    default:
      match:
        macaddress: "52:54:00:47:99:1b"
      dhcp4: true
```

**Interfície ITB13, DMZ** ([`config/F-N01/60-itb13.yaml`](../config/F-N01/60-itb13.yaml)):

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
```

Les decisions (fitxer per interfície, identificació per MAC, MTU 1446) i l'incidència que les va motivar estan explicades a [W-N01](06-web.md#24-xarxa-netplan).
 
### 2.3 Comprovacions

![Configuració base](img/f-n01-base.png)

| Prova | Ordre | Esperat | Resultat |
|---|---|---|---|
| Hostname | `hostname` | `F-N01` | |
| Zona horària | `timedatectl` | `Europe/Madrid` | |
| bchecker amb sudo | `id bchecker` | grup `sudo` | |
| SSH accepta contrasenya | `sudo sshd -T \| grep -i passwordauthentication` | `yes` | |
| Accés SSH amb bchecker | `ssh bchecker@localhost` | Entra | |

![Xarxa](img/f-n01-xarxa.png)
 
| Prova | Ordre | Esperat | Resultat |
|---|---|---|---|
| IP de la DMZ | `ip a` | `10.3.1.10/24` a la ITB13 | |
| Rutes | `ip route` | `default` per la Default i `10.3.1.0/24` per la ITB13 | |
| Internet | `curl -I http://archive.ubuntu.com` | Respon | |
| Arriba a W-N01 | `ping -c 3 10.3.1.20` | Resposta | |

---
