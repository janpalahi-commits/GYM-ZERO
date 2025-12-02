### SMX2-2025/2026


# Projecte Intermodular

## Context i situació inicial

El projecte GYM ZERO neix amb l’objectiu de modernitzar un gimnàs tradicional que actualment no disposa de tecnología.
Aquest gimnàs treballa de manera completament analògica: els registres de socis, els pagaments i les reserves de classes es fan manualment, no hi ha ordinadors ni wifi, i la comunicació amb els clients es realitza de manera presencial o telefònica

Aquesta manca de digitalització genera problemes com:

-Errors en la gestió de cobraments i quotes.

-Dificultats per controlar l’ocupació de les sales i els horaris dels entrenadors.

-Pèrdua d’informació o duplicació de dades en paper.

-Absència de canals digitals per contactar amb els clients o promocionar serveis.

-Inexistència de còpies de seguretat i protocols de seguretat informàtica.

El projecte planteja la transformació del GYM ZERO en un Gimnàs 4.0, capaç d’integrar tecnologies de xarxa, serveis web, aplicacions internes, bases de dades i eines d’anàlisi de dades per millorar la gestió i l’experiència dels clients.

A més, el nou gimnàs incorporarà un Laboratori Científic Simulat on s’analitzaran dades de rendiment i condició física dels usuaris (de forma simulada) per oferir un servei més personalitzat i basat en dades.

### Abast del projecte

L’abast del projecte cobreix tots els àmbits essencials d’una transformació digital dins d’un entorn de formació professional de SMX.
Concretament, el projecte inclou:
Àmbit tècnic

-Infraestructura de xarxa local (LAN) amb connexions Wi-Fi i segments separats per personal, clients i dispositius IoT.

-Instal·lació i configuració de servidors (físics o virtuals) per a serveis interns: DNS, DHCP, web, base de dades i còpies de seguretat.

-Pàgina web pública allotjada a GitHub Pages, amb informació del gimnàs, guies i calculadores interactives (1RM, IMC, TDEE…).

-Portal intern per a entrenadors amb gestió de classes, reserves, incidències i accés al Laboratori.

-Simulació del Laboratori Científic (LAB) per al càlcul i anàlisi de dades esportives mitjançant programes desenvolupats en Python, JavaScript o C++ (principalment seria C++).

#### Àmbit de seguretat i manteniment

-Polítiques de còpies de seguretat 3-2-1, control d’usuaris i permisos, i protecció bàsica de la xarxa amb tallafocs i contrasenyes segures.

-Gestió d’incidències i manteniment del sistema.

-Formació mínima del personal per fer ús dels nous sistemes digitals.

#### Àmbit funcional

-Digitalització del registre de socis i de les quotes.

-Automatització del procés de reserva de classes i control d’accés.

-Millora de la comunicació amb els clients (correu, web i xarxes socials).

-Creació d’un sistema centralitzat de dades per facilitar la gestió i els informes.

#### Limitacions

-No s’instal·larà equipament físic real (és una simulació educativa).

-No s’integraran sistemes de pagament reals ni sensors biomètrics físics.

-L’abast se centra en la planificació, disseny i demostració funcional mitjançant eines de programari i simulació.

Tot aquest abast és una planificació general que pot anar variant segons el transcurs del projecte i com ho vulgui acomodar segons les tasques que tingui plantejades fer, això ara mateix és el que m’agradaria més o menys acabar fent.


#FASES

##Fase 1 — Disseny i implementació de la infraestructura bàsica
En aquesta primera fase es dissenya i es posa en marxa tota la infraestructura tècnica mínima perquè el GYM ZERO passi de ser un gimnàs 0.0 a tenir un sistema informàtic funcional i estable. En primer lloc, es defineix la xarxa local (LAN) del centre: s’instal·la un router amb sortida a internet, un switch principal i, si cal, punts d’accés Wi-Fi per donar cobertura a recepció, sales d’entrenament i zona de personal. Es planifica un esquema d’adreçament intern (per exemple, una subxarxa 192.168.X.0/24) i, com a millora, es poden separar tràfics per tipus d’ús (personal, convidats, dispositius LAB) mitjançant VLANs o SSIDs diferents.
En paral·lel, s’instal·la un servidor principal (o una màquina virtual/contenidor en un host) amb un sistema operatiu tipus Ubuntu Server. Aquest servidor actua com a nucli dels serveis interns del gimnàs: servidor web intern (per al panell d’entrenadors), servidor de base de dades (MySQL/MariaDB) per guardar la informació de socis, reserves, incidències i dades del LAB, i servei de compartició d’arxius (per exemple, Samba o NFS) per emmagatzemar documents, informes i còpies de configuració. També es prepara un sistema de còpies de seguretat bàsic, que pot consistir en un disc extern o un repositori addicional on es desa regularment la base de dades i els fitxers crítics seguint una política senzilla (per exemple, còpia diària + còpia setmanal completa).
A nivell de postos de treball, es configuren com a mínim un ordinador de recepció i un equip per al personal tècnic/entrenadors, amb sistemes operatius tipus Windows o Ubuntu Desktop, connectats a la LAN i amb accés al servidor central. Aquests equips s’utilitzen per gestionar l’alta de socis, consultar reserves, registrar incidències i accedir al panell intern. Paral·lelament, es desplega la pàgina web pública del gimnàs mitjançant GitHub Pages (carpeta /docs del repositori), que inclou informació bàsica del centre, tarifes i les primeres eines interactives com les calculadores d’entrenament (1RM, IMC, TDEE) implementades amb HTML i JavaScript.
Finalment, en aquesta fase també s’implementa una primera versió del panell intern per a entrenadors i personal, encara que sigui amb funcionalitats bàsiques: inici de sessió, visualització i gestió de classes i reserves, registre d’incidències i, si és possible, un primer prototip del mòdul de LAB per poder importar dades simulades (per exemple, fitxers CSV) i guardar-les a la base de dades. Tot plegat es complementa amb una configuració mínima de seguretat: tallafocs al servidor (UFW), comptes d’usuari separades, contrasenyes fortes i desactivació de serveis innecessaris. Amb la finalització de la Fase 1, el gimnàs ja disposa d’una infraestructura informàtica bàsica operativa, amb xarxa, servidors i programes funcionant i preparats per ser millorats i ampliats en fases posteriors.

##Fase 2 — Optimització, seguretat i consolidació del sistema
Un cop la infraestructura bàsica i els programes principals ja estan en funcionament, la Fase 2 se centra en fer que el sistema del GYM ZERO sigui més segur, més estable i més còmode de gestionar en el dia a dia. L’objectiu no és afegir grans coses noves, sinó polir, reforçar i professionalitzar allò que ja s’ha muntat a la Fase 1.
En primer lloc, es treballa la seguretat del sistema. Al servidor principal (Ubuntu Server) es revisen i es tanquen tots els ports que no siguin estrictament necessaris, configurant un tallafocs senzill amb UFW (per exemple, permetent només HTTP/HTTPS, SSH restringit i els ports interns de base de dades si cal). També s’instal·la i configura una eina com fail2ban per detectar intents repetits de connexió fallida i bloquejar automàticament possibles atacs de força bruta. A més, s’ordenen els usuaris i permisos: es creen grups diferenciats per administrador, personal del gimnàs i eventuals comptes tècniques, i s’assignen permisos mínims segons el rol (principi de “mínim privilegi”).
En paral·lel, es dissenya i documenta una política de còpies de seguretat més clara i sistemàtica. Partint de la base implementada a la Fase 1, s’aplica una estratègia inspirada en la regla 3-2-1: tres còpies de les dades, en dos suports diferents, amb almenys una còpia fora de la màquina principal. Per exemple, es pot planificar una còpia diària incremental i una còpia setmanal completa de la base de dades i dels fitxers importants del servidor, guardades en un disc extern i, si és possible, en un servei de núvol privat o un segon equip. Aquesta fase també inclou la prova real de restauració d’una còpia, per assegurar que la documentació és correcta i que, en cas de problema, el sistema es podria recuperar.
També es fan millores d’optimització i manteniment. S’automatitzen certes tasques amb scripts i cron (per exemple, neteja de fitxers temporals, rotació de logs o llançament de còpies de seguretat), es revisa el rendiment de la base de dades (indexació bàsica de les taules més consultades), i es comprova que el panell intern respongui de manera fluida amb les dades i funcionalitats actuals. En aquesta fase es poden incorporar eines senzilles de monitoratge, com scripts que comproven periòdicament si els serveis principals (web, DB) estan encesos i enregistres en un log, o eines gràfiques si el temps ho permet.
Des del punt de vista funcional, la Fase 2 també serveix per refinar el panell intern i la web pública segons el feedback inicial. Això pot incloure millorar la usabilitat del panell d’entrenadors (per exemple, filtres de classes, millor visualització de reserves o estat de les incidències), afegir validacions a formularis, traduccions i petites millores visuals. A la web pública es poden acabar de polir les seccions d’informació, afegir enllaços clars a les calculadores i estructurar millor el contingut perquè sigui entenedor tant per als clients com per als professors que avaluen el projecte.
Finalment, aquesta fase s’aprofita per consolidar la documentació tècnica: es deixa per escrit la configuració dels serveis, els passos de desplegament, els procediments de còpia i restauració i les normes bàsiques de seguretat i ús per al personal del gimnàs. D’aquesta manera, en tancar la Fase 2, el sistema del GYM ZERO no només funciona, sinó que és més segur, més mantenible i millor documentat, i queda preparat per poder créixer cap a funcionalitats més avançades a la Fase 3.
##Fase 3 — Expansió, millores avançades i evolució cap al Gimnàs 4.0 complet
La Fase 3 representa la part més innovadora i evolucionada del projecte, on el GYM ZERO deixa de ser només un gimnàs digitalitzat per convertir-se en un centre intel·ligent capaç d’oferir serveis avançats basats en dades, automatització i experiència de l’usuari. En aquesta fase ja no s’està “activant” la infraestructura bàsica ni reforçant la seguretat, sinó que s’estan incorporant funcionalitats que aporten valor afegit, milloren l’eficiència i introdueixen elements propis d’un Gimnàs 4.0.
Un dels punts centrals d’aquesta fase és l’ampliació i potenciació del Laboratori Científic (LAB). S’afegeixen noves funcionalitats que permeten analitzar dades de rendiment esportiu de manera més completa i visual. Tot i que la recollida de dades continua sent simulada (d’acord amb les limitacions del projecte educatiu), es poden integrar nous tipus de dades com velocitat de barra, índex de fatiga, salts, pulsacions estimades, o progressió de càrregues al llarg de les setmanes. Aquestes dades s’emmagatzemen a la base de dades i es presenten mitjançant gràfics interactius, informes automàtics i estadístiques bàsiques. Opcionalment, s’hi pot incorporar algun mòdul experimental en C++ o Python que ofereixi càlculs avançats (per exemple, prediccions de 1RM, estimacions de volum d’entrenament o recomanacions automàtiques simples).
Paral·lelament, es milloren diferents aspectes de la pàgina web pública i del panell intern. La web pública pot incorporar noves seccions com guies d’entrenament més completes, eines interactives addicionals o fins i tot una simulació d’assistent virtual senzill per ajudar els clients a trobar la informació que busquen. El panell intern pot rebre millores com una millor integració del calendari, avisos inteligents quan una classe està plena, estadístiques bàsques de participació o fins i tot un sistema intern de notificacions entre entrenadors.
A nivell d’automatització i sistematització, la Fase 3 explora la possibilitat d’incloure dispositius IoT simulats dins la infraestructura del gimnàs. Aquests dispositius poden representar sensors d’ocupació, temperatura o ús de màquines, que envien dades simulades al servidor per tal de practicar la integració de dispositius externs dins la xarxa. També es pot incorporar un petit motor d’alertes o recomanacions (per exemple, quan un valor supera un llindar), demostrant el funcionament bàsic de sistemes proactius.
Finalment, aquesta fase inclou la documentació de millores futures, deixant clar com podria evolucionar el sistema real si fos un projecte professional: integració amb passarel·les de pagament reals, control d’accés físic amb RFID, aplicació mòbil pròpia, recomanacions basades en IA o implementació real de monitors de ritme cardíac, velocitat i potència. Tot i no desenvolupar-se plenament, aquestes millores queden descrites per demostrar visió de futur i capacitat d’escalabilitat del sistema.
En acabar la Fase 3, el GYM ZERO presenta una estructura digital sòlida, segura i evolucionada, complementada amb funcionalitats avançades que el situen com un projecte complet, modern i perfectament alineat amb el concepte de Gimnàs 4.0, demostrant una integració efectiva de tots els coneixements adquirits al llarg del cicle de SMX.

