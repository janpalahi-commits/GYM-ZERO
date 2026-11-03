# GYM-ZERO

Projecte Intermodular de 2n de SMX (INS Palamós), curs 2026-27. Autor: Jan Palahí.

GYM-ZERO és un gimnàs inventat que funciona sense cap tecnologia: les reserves es fan per telèfon, les quotes s'apunten en paper i cadascú porta els entrenaments a la seva llibreta. El projecte el digitalitza. El gimnàs és fictici; la web, el codi i la base de dades són reals.

La web funciona a **[gymzero.cat](https://gymzero.cat)** (Dinahosting, PHP 8.4 i MariaDB).

## Què hi ha fet

| Part | Estat | On és |
|---|---|---|
| Web pública: portada amb l'horari i les places lliures, activitats i càlculs (1RM, IMC, calories) | Funciona | `www/index.php`, `www/activitats/`, `www/calculs/` |
| Reserves de classes amb control de l'aforament (transacció i `SELECT … FOR UPDATE`) | Funciona | `www/reserves/`, `www/inc/reserves.php` |
| Entrar, sortir i recuperar la contrasenya per correu | Funciona | `www/compte/` |
| Àrea del client: reserves, avisos, el meu entrenament (gràfics i historial de sèries) | Funciona | `www/client/` |
| Panell d'administració: classes, activitats, altes a recepció, polsera, quotes, entrades | Funciona | `www/admin/` |
| Tres tarifes (Gimnàs, + pla, + seguiment) i cobrament de quotes de 1 o 3 mesos | Funciona | `www/inc/pagaments.php` |
| Pla personalitzat: qüestionari en 4 idiomes i PDF del pla fet amb Python | Funciona | `www/client/questionari.php`, `plans/` |
| Llibreta d'entrenament (full com un Excel, 8 setmanes, rècords, CSV) | Funciona | `www/client/llibreta.php` |
| Seguiment amb l'entrenador: revisió cada 2 setmanes i missatges | Funciona | `www/client/seguiment.php` |
| Còpies de seguretat automàtiques al servidor i còpia diària al PC de recepció | Funciona | `www/inc/copies.php`, `copies/` |
| Seguretat: https, contrasenyes amb hash, CSRF, límit d'intents d'entrada | Funciona | `www/.htaccess`, `www/compte/login.php` |
| API de les màquines (rep cada sèrie amb les seves repeticions) | Funciona | `www/api/serie.php` |
| API del torn d'entrada (deixa passar si la quota està pagada) | Funciona | `www/api/entrada.php` |
| Sèries de les màquines i entrades pel torn | Simulat (simulador en C++ i botó «Simular dades») | `simulador/`, `www/inc/simulacio.php` |
| Màquina intel·ligent: polsera RFID, sensor de desplaçament, ESP32 | Dissenyat | `maquina/` |
| Torn d'accés amb lector de polsera | Dissenyat | `acces/` |
| Xarxa del gimnàs: 3 VLAN, 4 punts d'accés, 20 màquines, 2 PCs | Dissenyat | `xarxa/` |
| Maqueta 3D del gimnàs | Model | `gimnas/gimnas-3d.html` |

## Carpetes

```
www/         aplicació web en PHP (la que es puja al servidor, dins de web/)
bd/          script de la base de dades, actualitzacions de cada fase, dades de prova i diagrama
simulador/   simuladors de la màquina i del torn, en C++
plans/       eina en Python que fa el PDF del pla personalitzat
copies/      scripts de còpia: el PC de recepció es baixa la del dia; còpia i restauració en local
proves/      scripts de prova de cada fase, amb el resultat i captures
maquina/     disseny de la màquina intel·ligent
acces/       disseny del control d'accés amb torn
xarxa/       disseny de la xarxa
gimnas/      maqueta 3D
```

## Com provar-ho en local (XAMPP)

1. Copia la carpeta `www` a `/opt/lampp/htdocs/` i canvia-li el nom a `gymzero`.
2. Engega l'XAMPP i obre phpMyAdmin (`http://localhost/phpmyadmin`).
3. Crea una base de dades buida que es digui `gymzero_db` (cotejament `utf8mb4_unicode_ci`), selecciona-la i importa-hi `bd/gymzero_bd.sql`.
4. Per tenir dades de prova, importa també `bd/dades_prova.sql` i després `bd/dades_prova_fase12.sql`.
5. A `www/inc/` copia `config.exemple.php` i anomena la còpia `config.php`. Amb l'XAMPP per defecte no cal canviar res.
6. Obre `http://localhost/gymzero/`.

Usuaris de prova (només amb les dades de prova, mai al servidor real):

| Usuari | Contrasenya | Rol |
|---|---|---|
| admin@gymzero.cat | Admin1234 | admin |
| marc@example.com | Client1234 | client |
| laia@example.com | Client1234 | client |
| pau@example.com | Client1234 | client |

## Base de dades

22 taules InnoDB amb claus foranes i la vista `v_resum_series`, que calcula per a cada sèrie les repeticions, la velocitat mitjana i quanta velocitat s'ha perdut. El disseny i el diagrama són a `bd/disseny-bd.md` i `bd/diagrama-bd.png`.

Les reserves es fan dins d'una transacció que bloqueja la classe, perquè dues persones no puguin agafar l'última plaça alhora (prova a `proves/bd_concurrencia.php`).

## Les màquines, el torn i els simuladors

Cada màquina té un codi (per exemple `PRESS-01`) i una clau. Quan el client acosta la polsera i fa una sèrie, la màquina envia a l'API la càrrega i, per a cada repetició, la velocitat, el recorregut i la durada:

```
POST /api/serie.php
X-Token: <clau de la màquina>

{"maquina": "PRESS-01", "targeta": "04A1B2C3", "carrega_kg": 62.5,
 "repeticions": [{"velocitat": 0.77, "recorregut": 0.41, "durada": 0.52}, ...]}
```

Els simuladors fan el paper de la màquina i del torn. Necessiten `g++` i `curl`:

```
cd simulador
g++ -std=c++17 -Wall -o simulador simulador.cpp
./simulador http://localhost/gymzero/api/serie.php PRESS-01 token-PRESS-01

g++ -std=c++17 -Wall -o torn torn.cpp
./torn http://localhost/gymzero/api/entrada.php TORN-01 token-TORN-01
```

Per ensenyar la web amb dades, a **Administració › Usuaris** cada client té el botó **Simular dades (1 setmana)**: fa com si hagués vingut una setmana sencera (entrades pel torn i sèries a les màquines). Cada vegada que es prem afegeix la setmana d'abans.

## Còpies de seguretat

Al servidor no hi ha cron, així que la mateixa web fa una còpia de la base de dades cada dia, amb la primera visita del dia. La guarda comprimida a `backup_db/`, fora de la carpeta pública, i en manté les 14 últimes. El panell d'administració avisa si falla.

El PC de recepció se'n baixa una cada dia (`copies/baixa_copia.sh`, s'instal·la una vegada amb `copies/installa_pc.sh` i la clau que es genera al panell). Així hi ha la base de dades original, la còpia al servidor i la còpia fora del servidor.

Per a l'ordinador de proves hi ha `copies/copia_bd.sh` (mysqldump) i `copies/restaura_bd.sh`, que restaura una còpia en una base de dades a part per comprovar que funciona.

Les còpies no es pugen mai a GitHub.

## Proves

A `proves/` hi ha un script per a cada part (reserves, administració, API, accés, pla, seguretat, simulació...) i el resultat que dona. Les captures són de l'ordinador de proves, amb les persones inventades de les dades de prova.

## Publicació

La web és a gymzero.cat des del 2 de novembre de 2026. Al servidor es puja el contingut de `www/` a la carpeta `web/`.

`www/inc/config.php` no es puja mai a GitHub perquè conté la contrasenya de la base de dades.
