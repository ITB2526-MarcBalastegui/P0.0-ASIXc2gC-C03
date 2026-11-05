# Control de versions i desplegament (Git)

Aquest document cobreix la part de control de versions i pujades a producció que demana l'enunciat: el repositori a GitHub, la validació del servidor per clau pública/privada, les ordres de Git (`clone`, `add`, `commit -m`, `push`, `pull`) i el desplegament de l'aplicació a W-N01.

## 1. El repositori

Tota la configuració i la documentació del projecte viuen en un únic repositori a GitHub:

```
P0.0-ASIXc2gC-C03
```

Conté l'arbre de documentació en Markdown (`docs/`), els fitxers de configuració reals de cada màquina (`config/`) i l'aplicació web (`app/`).

### Dos tipus d'accés al repositori

Segons des d'on es treballi, s'accedeix al repositori de dues maneres diferents:

| Des d'on | Mètode | Motiu |
|---|---|---|
| PCs de l'institut | HTTPS amb token | Als PCs del centre l'`ssh` no funciona: l'usuari no existeix al sistema local i qualsevol ordre SSH dona error d'`uid`. Per això s'hi treballa per HTTPS. |
| W-N01 (servidor) | SSH amb deploy key | L'enunciat demana que el servidor es validi per clau pública/privada. A més, el `git pull` de producció s'ha de poder fer sol, sense escriure contrasenyes. |

## 2. Validació del servidor per clau pública/privada (deploy key)

El servidor W-N01 s'identifica davant de GitHub amb una **deploy key**: una clau SSH lligada només a aquest repositori. A diferència d'una clau personal (que obriria tots els repos de l'usuari), la deploy key només dona accés a aquest projecte i, com que no té permís d'escriptura, només permet descarregar. Si W-N01 quedés compromès, l'atacant no podria modificar el repositori ni accedir a la resta del compte.

### Creació de la clau (a W-N01)

```bash
ssh-keygen -t ed25519 -C "W-N01 deploy" -f ~/.ssh/id_ed25519 -N ""
cat ~/.ssh/id_ed25519.pub
```

- **`-t ed25519`:** tipus de clau modern i segur.
- **`-N ""`:** sense contrasenya, perquè el servidor pugui fer `git pull` de forma automàtica, sense intervenció.

La clau **pública** (`id_ed25519.pub`) es dona d'alta a GitHub, al repositori: **Settings → Deploy keys → Add deploy key**, sense marcar *Allow write access*. La clau **privada** no surt mai de W-N01.

### Comprovació

```bash
ssh -T git@github.com
```

Resposta:

```
Hi ITB2526-MarcBalastegui/P0.0-ASIXc2gC-C03! You've successfully authenticated, but GitHub does not provide shell access.
```

Que surti el nom del **repositori** (i no el de l'usuari) confirma que la validació es fa amb la deploy key.

![Validació de W-N01 per deploy key](img/git/deploy_key.png)

*Autenticació de W-N01 a GitHub per intercanvi de clau pública/privada.*

## 3. Ordres de Git

### clone

Descarrega el repositori per primera vegada. A W-N01 es clona al directori de la web:

```bash
git clone git@github.com:ITB2526-MarcBalastegui/P0.0-ASIXc2gC-C03.git /var/www/p00
```

![git clone a W-N01](img/git/clone.png)

### add i commit -m

Al PC de treball, un cop fets canvis, es preparen i es desen a l'historial local:

```bash
git add .
git commit -m "Missatge que descriu el canvi"
```

- **`add`** marca quins fitxers entraran al següent desat (`.` vol dir tots els canvis).
- **`commit -m`** desa aquests canvis a l'historial local amb un missatge que els descriu.

### push

Puja els canvis de l'historial local a GitHub, perquè la resta de l'equip (i el servidor) els tingui:

```bash
git push
```

### pull

Descarrega a la màquina local els canvis que hi ha a GitHub. A W-N01 és l'ordre que publica l'aplicació:

```bash
git pull
```

## 4. Pujades a producció (desplegament)

L'aplicació s'executa a W-N01, però no s'edita directament a la màquina: es treballa al repositori i es publica amb `git pull`. El cicle complet és:

1. Al PC, es fa el canvi a `app/` i es puja: `git add` → `git commit -m` → `git push`.
2. A W-N01, es publica el canvi: `git pull`.
3. Apache serveix la versió nova immediatament.

![git pull a W-N01](img/git/pull.png)

*Desplegament d'un canvi a producció amb `git pull` a W-N01.*

### Apache serveix només l'aplicació

Perquè la configuració i la documentació del repositori no quedin exposades al navegador, Apache publica únicament la carpeta `app/`, no tot el repositori. Al fitxer `/etc/apache2/sites-available/000-default.conf`:

```
DocumentRoot /var/www/p00/app
```

Així, des de fora només es pot accedir a l'aplicació, i mai a `docs/` ni a `config/`.
