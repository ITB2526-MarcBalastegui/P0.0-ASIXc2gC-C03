# Validació del servidor per clau pública/privada (deploy key)

El servidor W-N01 s'identifica davant de GitHub amb una **deploy key**: un parell de claus (pública i privada) lligat només a aquest repositori. La clau privada es queda a W-N01 i la pública es dona d'alta a GitHub. Així el servidor pot accedir al repositori sense escriure cap contrasenya.

## 1. Generació de la clau (a W-N01)

```bash
ssh-keygen -t ed25519 -C "W-N01 deploy" -f ~/.ssh/id_ed25519 -N ""
```

- **`-t ed25519`:** tipus de clau modern i segur.
- **`-f ~/.ssh/id_ed25519`:** on es desa (clau privada `id_ed25519`, clau pública `id_ed25519.pub`).
- **`-N ""`:** sense contrasenya, perquè el servidor pugui accedir de forma automàtica.

Es mostra la clau **pública**, que és la que s'enganxa a GitHub (repositori → Settings → Deploy keys → Add deploy key):

```bash
cat ~/.ssh/id_ed25519.pub
```

![Generació de la deploy key](img/git/01-keygen.png)

## 2. Comprovació de l'accés a GitHub

Amb la clau ja donada d'alta, es comprova des de la terminal que W-N01 s'autentica correctament:

```bash
ssh -T git@github.com
```

Resposta d'èxit:

```
Hi ITB2526-MarcBalastegui/P0.0-ASIXc2gC-C03! You've successfully authenticated, but GitHub does not provide shell access.
```

Que surti el nom del **repositori** confirma que la validació es fa amb la deploy key.

![Accés correcte a GitHub](img/git/02-ssh-test.png)

Finalment, es comprova que l'accés serveix per operar amb el repositori. `git pull` connecta amb GitHub i confirma que el repositori està al dia:

```bash
cd /var/www/p00
git pull
```

![Accés al repositori amb git pull](img/git/03-git-pull.png)
