# Manual d'usuari: aplicació web

Aquest manual és per a qualsevol persona que vulgui consultar l'aplicació web. No cal cap coneixement tècnic.

## Què és

L'aplicació mostra el **llistat d'equipaments d'educació de la ciutat de Barcelona** en una taula. Les dades surten d'un fitxer oficial de l'Ajuntament de Barcelona i es mostren directament des del servidor.

## Com hi accedeixo

1. Has d'estar connectat a la xarxa interna de l'organització (des d'un dels ordinadors clients).
2. Obre un navegador (Firefox, Chrome, etc.).
3. A la barra d'adreces, escriu:

   ```
   http://W-N01
   ```

   Si no carrega pel nom, prova amb l'adreça directa:

   ```
   http://10.3.1.20
   ```

4. Prem Enter. La pàgina amb la taula de dades es carregarà.

## Què hi veuré

Una pàgina amb una taula. Cada fila és un equipament educatiu, i les columnes mostren les seves dades (nom, adreça i la resta d'informació del llistat). Pots desplaçar-te amunt i avall per veure tots els registres.

## Si alguna cosa no funciona

| Què passa | Què pots fer |
|---|---|
| La pàgina no carrega | Comprova que estàs connectat a la xarxa interna. Prova l'adreça `http://10.3.1.20`. |
| Surt un error de connexió | Espera uns minuts i torna-ho a provar. Si continua, avisa l'administrador. |
| La taula surt buida | Avisa l'administrador: pot ser un problema amb les dades del servidor. |

L'aplicació és només de consulta: no pots modificar ni esborrar res, només mirar les dades.
