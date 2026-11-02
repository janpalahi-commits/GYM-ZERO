# GYM-ZERO · Disseny de la base de dades (Fase 2, ampliat a la fase 12)

Estat: **dissenyada i provada en local** (MariaDB 10.11). Encara no està a Dinahosting.

Fitxers:
- `gymzero_bd.sql`: crea les 20 taules i la vista.
- `actualitza_fase9.sql`: afegeix la quota i les taules del torn a una base de dades que ja existeix, sense esborrar res.
- `actualitza_fase10.sql`: treu la columna `coef_1rm` (el 1RM es calcula igual a totes les màquines).
- `actualitza_fase11.sql`: afegeix la taula `recuperacions`.
- `actualitza_fase12.sql`: afegeix les tarifes, els pagaments i les taules del pla personalitzat, la llibreta i el seguiment.
- `dades_prova_fase12.sql`: tarifes, rutina, llibreta, revisions i missatges d'exemple (només per a proves, mai al servidor).
- `dades_prova.sql`: usuaris, classes, reserves i entrenaments d'exemple.
- `diagrama-bd.png` / `diagrama-bd.svg`: diagrama entitat-relació.
- `proves/prova_concurrencia.php` i `proves/resultat_prova.txt`: prova del límit de places.

![Diagrama](diagrama-bd.png)

## 1. Taules

| Taula | Què guarda | Servei |
|---|---|---|
| `usuaris` | Clients i administradors. Rol `client` o `admin`. Codi de la polsera i fins quan té pagada la quota (`quota_fins`). | Usuaris i login |
| `activitats` | Tipus d'activitat: Musculació, Cross Training, etc. | Reserves |
| `classes` | Una activitat en un dia i hora concrets, amb un nombre de places. | Reserves |
| `reserves` | Quin usuari ha reservat a quina classe i si està confirmada o cancel·lada. | Reserves |
| `maquines` | Les màquines intel·ligents (codi, tipus, ubicació i estat). | Màquines |
| `entrenaments` | El dia que un usuari entrena. Agrupa les sèries d'aquell dia. | Màquines i historial |
| `series` | Una sèrie en una màquina: càrrega i número de sèrie. | Màquines i historial |
| `repeticions` | Cada repetició que envia la màquina: velocitat, recorregut i durada. | Màquines i anàlisi |
| `notificacions` | Avisos per a l'usuari (reserva confirmada, classe cancel·lada...). | Notificacions |
| `torns` | Els torns d'accés amb lector de polsera (codi, estat i clau de l'API). | Control d'accés |
| `entrades` | Cada vegada que algú acosta la polsera al torn: qui, quan, si ha passat i per què. | Control d'accés |
| `recuperacions` | Enllaços per posar una contrasenya nova quan l'usuari l'ha oblidada. Només se'n guarda el hash, caduquen en 1 hora i només serveixen una vegada. | Usuaris i login |
| `tarifes` | Les 3 tarifes (Gimnàs, + pla, + seguiment) amb el preu d'1 i de 3 mesos. El `nivell` diu quins serveis té. | Tarifes |
| `pagaments` | Cada cobrament de recepció: tarifa, mesos, import, fins quan paga i qui l'ha cobrat. | Tarifes |
| `questionaris` | Les respostes del qüestionari del pla, en JSON, amb el consentiment per a les dades de salut. | Pla personalitzat |
| `plans` | El PDF del pla que puja l'entrenador. | Pla personalitzat |
| `rutina` | Els exercicis de cada dia de la rutina del client, amb la màquina si en té. | Llibreta |
| `registre` | Les sèries que el client apunta a mà (exercicis sense màquina). | Llibreta |
| `revisions` | La revisió de cada 2 setmanes: pes, com li va i la resposta de l'entrenador. | Seguiment |
| `missatges` | La conversa entre el client i l'entrenador. | Seguiment |

A més, la vista `v_resum_series` calcula el resum de cada sèrie: repeticions, velocitat mitjana, velocitat màxima i **pèrdua de velocitat** (de la repetició més ràpida a l'última). És el que farà servir l'historial del client.

## 2. Relacions

- Un usuari té moltes reserves, molts entrenaments i moltes notificacions (1:N).
- Una activitat té moltes classes; una classe té moltes reserves (1:N).
- Usuaris i classes queden relacionats N:M a través de `reserves`.
- Un entrenament té moltes sèries; una sèrie té moltes repeticions (1:N).
- Una màquina té moltes sèries (1:N).
- Un torn i un usuari tenen moltes entrades (1:N). Si la polsera no és de ningú, l'entrada es guarda sense usuari.
- Una tarifa té molts usuaris i molts pagaments; un usuari té molts pagaments (1:N).
- Un usuari té molts qüestionaris, plans, revisions i missatges (1:N). Es guarden tots: l'últim és el que val.
- Un usuari té moltes files de rutina; cada fila té moltes sèries apuntades a `registre` (1:N). Si la fila és d'una màquina, les sèries surten de `series` (polsera) i no de `registre`.

## 3. Decisions i per què

**Activitat ≠ classe.** Abans la reserva guardava el nom de l'activitat com a text i una data i hora qualsevol. Ara el client reserva una `classe` que existeix, amb unes places concretes. Sense això no es pot controlar l'aforament.

**Sense reserves duplicades.** La clau única `(usuari_id, classe_id)` fa que la base de dades rebutgi que un usuari reservi dues vegades la mateixa classe. Si cancel·la, la reserva no s'esborra: passa a `cancelada`, i si torna a reservar es torna a confirmar la mateixa fila.

**Límit de places amb concurrència.** Si dues persones reserven l'última plaça al mateix moment, totes dues poden veure "queda 1 plaça" i totes dues entren. Per evitar-ho, la reserva es fa dins d'una transacció i es bloqueja la fila de la classe:

```sql
START TRANSACTION;
SELECT places FROM classes WHERE id = ? AND cancelada = 0 FOR UPDATE;  -- bloqueja la classe
SELECT COUNT(*) FROM reserves WHERE classe_id = ? AND estat = 'confirmada';
-- si queden places:
INSERT INTO reserves (usuari_id, classe_id) VALUES (?, ?)
  ON DUPLICATE KEY UPDATE estat = 'confirmada', data_cancelacio = NULL;
COMMIT;   -- (o ROLLBACK si no queden places)
```

Mentre una reserva no fa `COMMIT`, les altres de la mateixa classe s'esperen al `FOR UPDATE`. Resultat de la prova (`proves/resultat_prova.txt`), amb 3 usuaris reservant alhora una classe de 2 places on ja n'hi havia 1 d'ocupada:

| | Reserves finals | Places |
|---|---|---|
| Sense `FOR UPDATE` | 4 | 2 |
| Amb `FOR UPDATE` | 2 | 2 |

**Contrasenyes.** Es guarda `password_hash`, mai la contrasenya. A PHP es fa amb `password_hash()` i es comprova amb `password_verify()`.

**Polsera RFID.** Cada usuari pot tenir el codi (UID) d'una polsera, a la columna `targeta_rfid`. La màquina i el torn l'envien per saber qui és. Al projecte la polsera és **simulada**: els simuladors envien aquest codi.

**Repeticions per separat.** Guardant cada repetició es pot calcular la pèrdua de velocitat dins d'una sèrie i comparar sessions. Són càlculs simples sobre dades guardades; no es fa cap diagnòstic.

**Un entrenament per usuari i dia.** Quan arriba la primera sèrie del dia, l'API crea l'entrenament; les següents s'hi afegeixen.

**Clau de cada màquina.** Cada màquina (o el simulador) s'identifica a l'API amb un token. A la base de dades només es guarda el hash, igual que les contrasenyes.

**1RM amb la mateixa fórmula a totes les màquines.** El pes màxim estimat es calcula amb la fórmula d'Epley, igual per a tots els exercicis:

`1RM = pes × (1 + repeticions / 30)`

En realitat, amb el mateix percentatge del màxim no es fan les mateixes repeticions a tots els exercicis. Es va provar de guardar un número diferent per a cada màquina (`coef_1rm`), però els valors quedaven tan a prop que el resultat era pràcticament igual, així que es va treure (`actualitza_fase10.sql`). Queda com a limitació: el 1RM és una estimació orientativa.

**Quota i entrades.** La quota és una data (`quota_fins`), no una taula de pagaments: la web només ha de saber fins quan la té pagada. Recepció la allarga 1 o 3 mesos. Per reservar una classe, la quota ha d'arribar fins al dia de la classe. Els comptes de client només els crea recepció (`admin/alta.php`), quan la persona s'apunta al gimnàs. Les entrades es guarden també quan el torn no deixa passar, per saber qui ho ha intentat i per què. Si s'esborra un usuari, les seves entrades es queden sense usuari (`ON DELETE SET NULL`) en lloc d'esborrar-se.

**Còpies de seguretat.** No són una taula: es faran amb `mysqldump` des del servidor (ho veurem a la fase de backups).

**Tarifes en una taula, no al codi.** Els preus i el que inclou cada tarifa són a `tarifes`. Si el gimnàs canvia un preu, es canvia a la base de dades i la portada i recepció ja surten bé. El `nivell` (1, 2, 3) és el que fa servir la web per decidir si un client pot entrar a la llibreta o al seguiment.

**Cobrar en una transacció.** En cobrar, es bloqueja la fila del client (`FOR UPDATE`), es calcula la nova data i s'apunta el pagament. Si continua amb la mateixa tarifa, els mesos s'afegeixen al final del que ja tenia; si canvia de tarifa o li havia caducat, compta des del dia que paga.

**Qüestionari en JSON.** Les preguntes poden canviar i l'eina de Python (carpeta `plans/`) llegeix el JSON tal qual. Una restricció `CHECK (JSON_VALID(respostes))` fa que la base de dades no accepti un text que no sigui JSON, i `CHECK (consentiment = 1)` fa que no es pugui guardar sense consentiment.

**El PDF dins la base de dades.** Els plans es guarden com a `MEDIUMBLOB` (fins a 16 MB; la web en deixa pujar 10). Així no hi ha fitxers amb dades de salut en una carpeta pública del servidor i la còpia de seguretat de la base de dades ja els inclou.

**Llibreta: màquina o a mà.** Una fila de la rutina amb `maquina_id` agafa les sèries de la polsera; sense màquina, el client les apunta a `registre`. La clau única `(rutina_id, data, num_serie)` fa que tornar a apuntar la mateixa sèrie la corregeixi en lloc de duplicar-la.

## 4. Com provar-ho a XAMPP

1. Arrenca XAMPP: `sudo /opt/lampp/lampp start`.
2. Obre `http://localhost/phpmyadmin` i crea una base de dades `gymzero_db` (cotejament `utf8mb4_unicode_ci`).
3. Selecciona-la > **Importar** > `gymzero_bd.sql`.
4. Torna a **Importar** > `dades_prova.sql` i després `dades_prova_fase12.sql`.
5. Comprova-ho a la pestanya SQL: `SELECT * FROM v_resum_series;`

Usuaris de prova: `admin@gymzero.cat` / `Admin1234` i `marc@example.com` / `Client1234`.

**No ho importis a Dinahosting encara.** El fitxer esborra l'antiga taula `reserves` i el formulari que funciona ara a la web antiga deixaria de funcionar. Es pujarà quan el PHP nou (Fase 3) estigui fet.
