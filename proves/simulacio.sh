B=${B:-http://localhost/gymzero}; BD=${BD:-gymzero_db}
sql(){ mariadb --default-character-set=utf8mb4 -N $BD -e "$1"; }
csrf(){ curl -s -b $1 -c $1 "$B$2" | grep -o 'name="csrf" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
entra(){ T=$(csrf $1 /compte/login.php); curl -s -b $1 -c $1 -o /dev/null -d "csrf=$T&email=$2&password=$3" $B/compte/login.php; }
simula(){ T=$(csrf $A /admin/usuaris.php); curl -s -b $A -c $A -L -o /tmp/sim.html -d "csrf=$T&accio=simular&usuari_id=$1" $B/admin/usuaris.php
          grep -o '<div class="\(ok\|error\)">[^<]*' /tmp/sim.html | sed 's/<div class="[a-z]*">/   /;s/&#039;/'"'"'/g'; }
resum(){ sql "SELECT CONCAT('   ', e.data, ' ', DATE_FORMAT(e.data,'%a'), ' · entrada ', COALESCE((SELECT DATE_FORMAT(MIN(data_hora),'%H:%i') FROM entrades x WHERE x.usuari_id=e.usuari_id AND DATE(x.data_hora)=e.data),'-'),
                 ' · ', COUNT(DISTINCT s.maquina_id), ' màquines, ', COUNT(DISTINCT s.id), ' sèries')
              FROM entrenaments e JOIN series s ON s.entrenament_id = e.id WHERE e.usuari_id = $1 AND e.data BETWEEN '$2' AND '$3'
             GROUP BY e.id ORDER BY e.data"; }

sql "DELETE FROM intents_login"
A=$(mktemp); entra $A admin@gymzero.cat Admin1234
sql "DELETE FROM usuaris WHERE email='nou@example.com'"
sql "INSERT INTO usuaris (nom, cognoms, email, password_hash, rol, tarifa_id) VALUES ('Nou','Sense Polsera','nou@example.com','x','client',1)"
NOU=$(sql "SELECT id FROM usuaris WHERE email='nou@example.com'")

echo "1. El botó surt a cada client (i no a l'administrador)"
curl -s -b $A $B/admin/usuaris.php | grep -c 'value="simular"' | sed 's/^/   botons: /'
echo "   clients: $(sql "SELECT COUNT(*) FROM usuaris WHERE rol='client'") · text a sota: $(curl -s -b $A $B/admin/usuaris.php | grep -c '(1 setmana)')"

echo "2. Client nou, sense polsera ni dades: primera setmana"
simula $NOU
echo "   polsera: $(sql "SELECT targeta_rfid FROM usuaris WHERE id=$NOU")"
resum $NOU 2000-01-01 2100-01-01
echo "   entrades pel torn: $(sql "SELECT COUNT(*) FROM entrades WHERE usuari_id=$NOU AND permesa=1")"

echo "3. Una sèrie de mostra (press de banca), repetició a repetició"
sql "SELECT CONCAT('   ', s.carrega_kg, ' kg · rep ', r.num_rep, ': ', r.velocitat_mitjana, ' m/s, ', r.recorregut_m, ' m, ', r.durada_s, ' s')
       FROM series s JOIN entrenaments e ON e.id=s.entrenament_id JOIN maquines m ON m.id=s.maquina_id JOIN repeticions r ON r.serie_id=s.id
      WHERE e.usuari_id=$NOU AND m.codi='PRESS-01' AND s.num_serie=1 ORDER BY e.data, r.num_rep" | head -12
sql "SELECT CONCAT('   resum (vista): ', repeticions, ' reps, pèrdua de velocitat ', perdua_velocitat_pct, ' %') FROM v_resum_series v
      WHERE v.usuari_id=$NOU AND maquina='PRESS-01' ORDER BY data, num_serie LIMIT 4"

echo "4. Tres vegades més: cada vegada la setmana d'abans, i la càrrega puja cap al present"
for i in 1 2 3; do simula $NOU; done
sql "SELECT CONCAT('   ', m.codi, ': ', GROUP_CONCAT(CONCAT(DATE_FORMAT(e.data,'%d/%m'), ' ', x.kg, ' kg') ORDER BY e.data SEPARATOR ' → '))
       FROM (SELECT e.id, s.maquina_id, MAX(s.carrega_kg) kg FROM series s JOIN entrenaments e ON e.id=s.entrenament_id
              WHERE e.usuari_id=$NOU GROUP BY e.id, s.maquina_id) x
       JOIN entrenaments e ON e.id=x.id JOIN maquines m ON m.id=x.maquina_id
      WHERE m.codi IN ('PRESS-01','SQUAT-01','CURL-01') GROUP BY m.codi"
echo "   setmanes amb dades: $(sql "SELECT COUNT(DISTINCT YEARWEEK(data,1)) FROM entrenaments WHERE usuari_id=$NOU") · dies: $(sql "SELECT COUNT(*) FROM entrenaments WHERE usuari_id=$NOU")"

echo "5. El Marc té rutina a la llibreta i dades fins ara: la setmana va abans de la primera que té"
echo "   abans: primer entrenament $(sql "SELECT MIN(data) FROM entrenaments WHERE usuari_id=2"), apuntades a mà $(sql "SELECT COUNT(*) FROM registre g JOIN rutina r ON r.id=g.rutina_id WHERE r.usuari_id=2")"
simula 2
echo "   després: primer entrenament $(sql "SELECT MIN(data) FROM entrenaments WHERE usuari_id=2"), apuntades a mà $(sql "SELECT COUNT(*) FROM registre g JOIN rutina r ON r.id=g.rutina_id WHERE r.usuari_id=2")"
P=$(sql "SELECT MIN(data) FROM entrenaments WHERE usuari_id=2")
echo "   dies de la setmana nova = dies de la rutina: $(sql "SELECT GROUP_CONCAT(DISTINCT DAYOFWEEK(data)-1 ORDER BY data) FROM entrenaments WHERE usuari_id=2 AND data < '$P' + INTERVAL 7 DAY") / $(sql "SELECT GROUP_CONCAT(DISTINCT dia ORDER BY dia) FROM rutina WHERE usuari_id=2")"
sql "SELECT CONCAT('   a mà: ', r.exercici, ' ', g.data, ' sèrie ', g.num_serie, ': ', COALESCE(CONCAT(g.carrega_kg,' kg'),'pes del cos'), ' × ', g.reps)
       FROM registre g JOIN rutina r ON r.id=g.rutina_id WHERE r.usuari_id=2 AND g.data < '$P' + INTERVAL 7 DAY ORDER BY g.data, r.ordre, g.num_serie LIMIT 6"

echo "6. Les pàgines del client i de l'administrador es veuen bé amb les dades noves"
M=$(mktemp); entra $M marc@example.com Client1234
for u in /client/entrenament.php /client/llibreta.php; do printf "   %-26s %s\n" $u "$(curl -s -o /tmp/p.html -w '%{http_code}' -b $M $B$u) $(grep -ci 'warning\|fatal\|deprecated' /tmp/p.html) avisos PHP"; done
for u in /admin/usuaris.php /admin/entrades.php "/admin/client.php?id=2" /admin/; do printf "   %-26s %s\n" $u "$(curl -s -o /tmp/p.html -w '%{http_code}' -b $A "$B$u") $(grep -ci 'warning\|fatal\|deprecated' /tmp/p.html) avisos PHP"; done

echo "7. Seguretat: sense ser administrador o sense CSRF no fa res"
T=$(csrf $M /client/); echo "   client: $(curl -s -o /dev/null -w '%{http_code}' -b $M -d "csrf=$T&accio=simular&usuari_id=3" $B/admin/usuaris.php) · entrenaments de la Laia: $(sql "SELECT COUNT(*) FROM entrenaments WHERE usuari_id=3")"
echo "   sense CSRF: $(curl -s -o /dev/null -w '%{http_code}' -b $A -d "accio=simular&usuari_id=3" $B/admin/usuaris.php) · entrenaments de la Laia: $(sql "SELECT COUNT(*) FROM entrenaments WHERE usuari_id=3")"
echo "   a l'administrador:"; simula 1

echo "8. Client amb llibreta (tarifa amb pla) i sense rutina, 8 vegades (2 mesos): la rutina es crea sola i la càrrega puja poc a poc"
sql "DELETE FROM usuaris WHERE email='pla@example.com'"
sql "INSERT INTO usuaris (nom, cognoms, email, password_hash, rol, tarifa_id, quota_fins) VALUES ('Pla','Amb Llibreta','pla@example.com','$(sql "SELECT password_hash FROM usuaris WHERE id=2")','client',2,CURDATE()+INTERVAL 1 MONTH)"
PLA=$(sql "SELECT id FROM usuaris WHERE email='pla@example.com'")
simula $PLA; for i in 2 3 4 5 6 7 8; do simula $PLA > /dev/null; done
echo "   rutina: $(sql "SELECT COUNT(*) FROM rutina WHERE usuari_id=$PLA") exercicis, dies $(sql "SELECT GROUP_CONCAT(DISTINCT dia ORDER BY dia) FROM rutina WHERE usuari_id=$PLA")"
sql "SELECT CONCAT('   ', m.codi, ': de ', MIN(x.kg), ' a ', MAX(x.kg), ' kg (+', ROUND(100*(MAX(x.kg)-MIN(x.kg))/MIN(x.kg)), ' % en ', COUNT(DISTINCT YEARWEEK(e.data,1)), ' setmanes)')
       FROM (SELECT e.id, s.maquina_id, MAX(s.carrega_kg) kg FROM series s JOIN entrenaments e ON e.id=s.entrenament_id WHERE e.usuari_id=$PLA GROUP BY e.id, s.maquina_id) x
       JOIN entrenaments e ON e.id=x.id JOIN maquines m ON m.id=x.maquina_id GROUP BY m.codi ORDER BY m.codi"
P2=$(mktemp); entra $P2 pla@example.com Client1234
curl -s -b $P2 $B/client/llibreta.php > /tmp/ll.html
echo "   llibreta: $(grep -o '[0-9]*,*[0-9]* × [0-9]*' /tmp/ll.html | wc -l) sèries a la setmana visible · avisos PHP: $(grep -ci 'warning\|fatal' /tmp/ll.html)"
curl -s -b $P2 $B/client/entrenament.php > /tmp/en.html
echo "   gràfic del press de banca: $(grep -o '<h3>Press de banca[^<]*</h3>' /tmp/en.html | head -1) punts $(sed -n '/<h3>Press de banca/,/<\/svg>/p' /tmp/en.html | grep -o '<circle' | wc -l), textos de pes $(sed -n '/<h3>Press de banca/,/<\/svg>/p' /tmp/en.html | grep -o 'class="valor"' | wc -l)"
sql "DELETE FROM usuaris WHERE id IN ($NOU, $PLA)"
