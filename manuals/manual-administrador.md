# Manual d'administració

Aquest manual permet operar la infraestructura sense conèixer-ne el disseny intern. Explica com entrar a cada màquina, com comprovar que tot funciona i com fer les tasques més habituals. Per entendre com està muntada la xarxa, consulta [l'arquitectura](01-arquitectura.md); per operar-la, n'hi ha prou amb aquest document.

## 1. Accés a les màquines

Totes les màquines tenen el mateix usuari administrador:

- **Usuari:** `bchecker`
- **Contrasenya:** `bchecker121`

S'hi entra per SSH des d'una màquina de la xarxa interna:

```bash
ssh bchecker@<IP de la màquina>
```

Per fer tasques d'administració, cal posar `sudo` davant de les ordres (et tornarà a demanar la contrasenya).

### Mapa de màquines

| Màquina | IP | Què fa | On és |
|---|---|---|---|
| R-N01 | 10.3.1.1 / 10.3.2.1 | Router, tallafocs i sortida a Internet | — |
| W-N01 | 10.3.1.20 | Servidor web (l'aplicació) | DMZ |
| F-N01 | 10.3.1.10 | Servidor FTP | DMZ |
| B-N01 | 10.3.2.10 | Base de dades (MySQL) | Intranet |
| D-N01 | 10.3.2.20 | DNS i DHCP | Intranet |
| Clients | 10.3.2.100–200 | PCs d'usuari | Intranet |

> Regla pràctica: les adreces `10.3.1.x` són la DMZ (serveis públics) i les `10.3.2.x` són la Intranet (serveis interns i clients).

## 2. Engegar l'entorn

Les màquines viuen a IsardVDI. Cada membre de l'equip engega les seves des del seu compte.

**Ordre important:** engega sempre **R-N01 primer**. La resta de màquines surten a Internet i es comuniquen a través seu; si el router està apagat, semblarà que res funciona.

Després, engega les altres en qualsevol ordre.

## 3. Comprovar que tot funciona

Fes aquestes comprovacions després d'engegar l'entorn. Si totes passen, la infraestructura està operativa.

| Servei | Comprovació | Resultat correcte |
|---|---|---|
| Router | Des d'un client: `ping 10.3.1.1` | Respon |
| Internet | Des d'un client: `ping 8.8.8.8` | Respon |
| DNS | Des d'un client: `nslookup W-N01` | Retorna 10.3.1.20 |
| DHCP | Un client nou rep IP del rang 10.3.2.100–200 | Té IP automàticament |
| Web | Al navegador: `http://W-N01` | Es veu la taula de dades |
| Base de dades | Des de W-N01: `mysql -h 10.3.2.10 -u <usuari> -p` | Entra |
| FTP | Des d'un client: `lftp -u bchecker,bchecker121 10.3.1.10` | Entra |
| SSH | `ssh bchecker@<qualsevol màquina>` | Entra |

## 4. Tasques habituals

### Reiniciar un servei que ha deixat de funcionar

Entra a la màquina del servei i reinicia'l:

```bash
sudo systemctl restart <servei>
sudo systemctl status <servei>
```

Noms dels serveis: `apache2` (web, a W-N01), `vsftpd` (FTP, a F-N01), `mysql` (base de dades, a B-N01), `ssh` (a totes).

### Publicar una versió nova de l'aplicació web

L'aplicació no s'edita directament al servidor: es canvia al repositori de GitHub i es publica amb una ordre. A **W-N01**:

```bash
cd /var/www/p00
git pull
```

La web queda actualitzada immediatament. (Qui fa els canvis els ha d'haver pujat abans amb `git push` des del seu ordinador.)

### Gestionar fitxers de l'FTP

Els fitxers que es pugen per FTP queden a F-N01, dins de `/home/bchecker/ftp/files`. Per veure'ls o fer-ne còpia, entra a F-N01:

```bash
ssh bchecker@10.3.1.10
ls /home/bchecker/ftp/files
```

### Veure quines IP ha repartit el DHCP

A **D-N01**:

```bash
sudo cat /var/lib/dhcp/dhcpd.leases
```

Mostra quins clients tenen quina IP.

### Reiniciar la configuració de xarxa d'una màquina

Si una màquina perd la xarxa després d'un canvi:

```bash
sudo netplan try
```

Aplica la configuració i la desfà sola al cap de 120 segons si no es confirma, així no et quedes sense accés.

## 5. Problemes habituals i solucions

| Símptoma | Causa probable | Solució |
|---|---|---|
| Cap màquina surt a Internet | R-N01 apagat | Engega R-N01 el primer |
| Un client no agafa IP | DHCP (D-N01) apagat, o el client no és a la xarxa Intranet | Comprova que D-N01 està engegat i que el client és a la ITB14 |
| `http://W-N01` no carrega pel nom però sí per IP | El DNS no resol | Comprova que D-N01 està engegat i que el client té el DNS 10.3.2.20 |
| Els clients no arriben al servidor web | La ruta de sortida de W-N01 no passa pel router | A W-N01, `ip route` ha de mostrar `default via 10.3.1.1`. Si no, revisa el netplan |
| L'FTP connecta però es penja en llistar o pujar | Mode passiu / tallafocs | Comprova que el rang de ports passius (40000–40100) està obert al tallafocs |
| Una màquina no arrenca a Isard | Quota o recursos | Engega només les màquines necessàries; cada servei va en una màquina diferent |

## 6. On és tot

- **La documentació tècnica de cada màquina:** carpeta `docs/` del repositori.
- **Els fitxers de configuració reals:** carpeta `config/` del repositori, ordenats per màquina.
- **L'aplicació web:** carpeta `app/` del repositori, desplegada a W-N01.

Amb aquest repositori es pot reconstruir qualsevol màquina si cal.

---

> Nota per a l'equip: els detalls d'administració del router (tallafocs, NAT), del DNS/DHCP i de la base de dades els han de revisar els seus responsables (Victor i Adam), per si alguna ordre concreta difereix del que s'ha escrit aquí.
