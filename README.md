# P0.0 - Desplegament d'infraestructura

Projecte intermodular ASIX2 · Institut Tecnològic de Barcelona · Grup G03

## Equip
| Membre | Rol |
|---|---|
| Victor Barreda | Xarxa i router |
| Adam Ben Mansor | Base de dades |
| Marc Balastegui | Repositori, plantilla Linux, DNS/DHCP |

## Arquitectura
Router R-N01 amb tres xarxes: NAT, DMZ (F-N01, W-N01) i Intranet (B-N01, D-N01, clients).
Detall a [docs/01-arquitectura.md](docs/01-arquitectura.md).

## Accés
Tots els equips tenen l'usuari `bchecker`.

## Documentació
1. [Arquitectura i pla IP](docs/01-arquitectura.md)
2. [Router R-N01](docs/02-router.md)
3. [DNS i DHCP (D-N01)](docs/03-dns-dhcp.md)
4. [Base de dades (B-N01)](docs/04-bbdd.md)
5. [FTP (F-N01)](docs/05-ftp.md)
6. [Web server (W-N01)](docs/06-web.md)
7. [Clients](docs/07-clients.md)
8. [Control de versions](docs/08-git.md)
9. [Proves](docs/09-proves.md)
