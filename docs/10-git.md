# Validació del servidor per clau pública/privada (deploy key)

L'enunciat demana que el servidor es validi davant de GitHub per intercanvi de clau pública/privada. El servidor W-N01 ho fa amb una **deploy key**: un parell de claus (una de pública i una de privada) lligat únicament a aquest repositori.

El funcionament és:

- La clau **privada** es queda sempre a W-N01 i no surt mai de la màquina.
- La clau **pública** es dona d'alta a GitHub, al repositori.

Quan W-N01 es connecta, GitHub li proposa un repte que només es pot resoldre amb la clau privada. Si el resol, l'autentica. Així el servidor accedeix al repositori sense escriure cap contrasenya, i sense que la clau privada viatgi mai per la xarxa.

A diferència d'una clau personal (que obriria tots els repositoris de l'usuari), la deploy key només dona accés a aquest projecte. Com que es dona d'alta sense permís d'escriptura, només permet descarregar, no modificar.

## 1. Generació de la clau (a W-N01)

A W-N01 es genera el parell de claus:

```bash
ssh-keygen -t ed25519 -C "W-N01 deploy" -f ~/.ssh/id_ed25519 -N ""
```

- **`-t ed25519`:** tipus de clau modern i segur.
- **`-C "W-N01 deploy"`:** una etiqueta per identificar la clau.
- **`-f ~/.ssh/id_ed25519`:** on es desen els fitxers. Es creen dos: la clau privada (`id_ed25519`) i la pública (`id_ed25519.pub`).
- **`-N ""`:** sense contrasenya, perquè el servidor pugui accedir de forma automàtica, sense que ningú l'hagi d'escriure.

Un cop generada, es mostra la clau **pública**:

```bash
cat ~/.ssh/id_ed25519.pub
```

El contingut d'aquest fitxer és el que s'enganxa a GitHub, al repositori: **Settings → Deploy keys → Add deploy key**, deixant *Allow write access* sense marcar.

![Generació de la clau i clau pública](img/git/01-keygen.png)

## 2. Comprovació de l'accés a GitHub

Amb la clau ja donada d'alta a GitHub, es comprova des de la terminal que W-N01 s'autentica correctament:

```bash
ssh -T git@github.com
```

La primera vegada, SSH pregunta si es confia en el host; s'accepta amb `yes`. La resposta d'èxit és:

```
Hi ITB2526-MarcBalastegui/P0.0-ASIXc2gC-C03! You've successfully authenticated, but GitHub does not provide shell access.
```

Que surti el nom del **repositori** (i no el de l'usuari) confirma que l'autenticació s'ha fet amb la deploy key d'aquest projecte.

![Accés correcte a GitHub amb la deploy key](img/git/02-ssh-test.png)

## 3. Accés al repositori

Finalment, es comprova que la validació no només connecta, sinó que permet treballar amb el repositori. Amb `git clone` es descarrega el projecte per SSH al directori de la web:

```bash
git clone git@github.com:ITB2526-MarcBalastegui/P0.0-ASIXc2gC-C03.git /var/www/p00
```

El fet que el `clone` funcioni sense demanar cap contrasenya és la prova definitiva que el servidor es valida per clau pública/privada.

![Clonat del repositori amb la deploy key](img/git/03-git-clone.png)
