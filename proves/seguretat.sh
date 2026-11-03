# Proves de la fase 13: còpies automàtiques, còpia al PC de recepció, límit d'intents i .htaccess.
# Cal Apache (perquè llegeixi els .htaccess) amb la web a $B i la BD $BD amb les dades de prova.
# Ús: B=http://127.0.0.1:8099 BD=gz12 DIR=/srv/gz13/backup_db bash seguretat.sh
B=${B:-http://localhost/gymzero}; BD=${BD:-gymzero_db}; DIR=${DIR:-/opt/lampp/htdocs/backup_db}
sql(){ mariadb --default-character-set=utf8mb4 -N $BD -e "$1"; }
csrf(){ curl -s -b $1 -c $1 "$B$2" | grep -o 'name="csrf" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
entra(){ T=$(csrf $1 /compte/login.php); curl -s -b $1 -c $1 -o /tmp/entra.html -w "%{http_code}" -H "X-Forwarded-For: ${4:-}" -d "csrf=$T&email=$2&password=$3" $B/compte/login.php; echo " $(grep -o '<div class="error">[^<]*' /tmp/entra.html | sed 's/<div class="error">//;s/&#039;/'"'"'/g')"; }

echo "1. Còpia automàtica: no n'hi ha cap; s'obre la portada"
rm -f $DIR/gymzero_* $DIR/.ultim_intent; curl -s -o /dev/null $B/; sleep 1
ls -l $DIR | grep -o 'gymzero_.*\|^-[rwx-]*' | paste -sd' ' | sed 's/^/   /'
F=$(ls -t $DIR/gymzero_*.sql.gz | head -1)
echo "   gzip: $(gzip -t $F && echo bé) · taules: $(zcat $F | grep -c '^CREATE TABLE') · última línia: $(zcat $F | tail -1)"
echo "2. Una altra pàgina el mateix dia: no en fa cap més"
curl -s -o /dev/null $B/activitats/; sleep 1; echo "   còpies: $(ls $DIR/gymzero_*.sql.gz | wc -l)"
echo "3. Restaurar la còpia en una BD nova i comparar totes les taules"
mariadb -e "DROP DATABASE IF EXISTS gz_restaurada; CREATE DATABASE gz_restaurada CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
zcat $F | mariadb gz_restaurada
for t in $(sql "SHOW FULL TABLES WHERE Table_type='BASE TABLE'" | cut -f1 | grep -v notificacions); do
  [ "$(sql "CHECKSUM TABLE $t" | cut -f2)" = "$(mariadb -N gz_restaurada -e "CHECKSUM TABLE $t" | cut -f2)" ] && echo -n "$t=igual " || echo -n "$t=DIFERENT "
done | fold -w 100 -s | sed 's/^/   /'; echo
echo "   vista v_resum_series: $(mariadb -N gz_restaurada -e 'SELECT COUNT(*) FROM v_resum_series') files (original: $(sql 'SELECT COUNT(*) FROM v_resum_series'))"
mariadb -e "DROP DATABASE gz_restaurada"
echo "4. Avís als administradors: $(sql "SELECT missatge FROM notificacions WHERE missatge LIKE 'Còpia%' ORDER BY id DESC LIMIT 1")"
echo "5. Des del navegador no es pot arribar a res de dins (codi HTTP)"
for u in /inc/ /inc/config.php /inc/copies.php /inc/questionari.json /css/ /bd.sql /copies/ /css/estil.css /; do printf "   %-24s %s\n" $u $(curl -s -o /dev/null -w '%{http_code}' $B$u); done
echo "6. Capçaleres de seguretat:"; curl -sI $B/ | grep -iE 'x-frame|content-security|nosniff|referrer|permissions' | sed 's/^/   /'
echo "7. A gymzero.cat per http va a https:"; curl -s -o /dev/null -w "   %{http_code} -> %{redirect_url}\n" -H 'Host: gymzero.cat' "$B/reserves/?a=1"
echo "8. Límit d'intents: 5 contrasenyes dolentes del Marc i després la bona"
sql "DELETE FROM intents_login"; J=$(mktemp)
for i in 1 2 3 4 5; do echo "   $(entra $J marc@example.com dolenta)"; done
echo "   bona: $(entra $J marc@example.com Client1234)"
echo "   la Laia, des de la mateixa IP, sí que entra: $(entra $(mktemp) laia@example.com Client1234)"
sql "UPDATE intents_login SET data = data - INTERVAL 16 MINUTE"
echo "   passats 15 minuts, el Marc: $(entra $(mktemp) marc@example.com Client1234)"
echo "9. 20 errors des d'una IP (proxy del hosting: X-Forwarded-For), amb correus diferents:"
sql "DELETE FROM intents_login"
for i in $(seq 1 20); do entra $(mktemp) p$i@example.com x 83.40.1.2 > /dev/null; done
echo "   des d'aquesta IP: $(entra $(mktemp) laia@example.com Client1234 83.40.1.2)"
echo "   des d'una altra:  $(entra $(mktemp) laia@example.com Client1234 83.40.9.9)"
sql "DELETE FROM intents_login"
echo "10. API de les còpies (PC de recepció)"
echo "   sense clau: $(curl -s -w ' [%{http_code}]' $B/api/copia.php | tr -d '\n')"
echo "   clau dolenta: $(curl -s -w ' [%{http_code}]' -H 'X-Token: abc' $B/api/copia.php | tr -d '\n')"
JA=$(mktemp); entra $JA admin@gymzero.cat Admin1234 > /dev/null
T=$(csrf $JA /admin/); CLAU=$(curl -s -b $JA -c $JA -L -d "csrf=$T&clau_copies=1" $B/admin/ | grep -o '<code>[0-9a-f]\{48\}' | sed 's/<code>//')
echo "   clau generada al panell: ${#CLAU} caràcters · a la BD: $(sql "SELECT LEFT(valor, 7) FROM configuracio WHERE clau='copies_token_hash'")..."
curl -s -D /tmp/caps -o /tmp/baixada.gz -H "X-Token: $CLAU" $B/api/copia.php
echo "   amb la clau: $(grep -i '^X-Copia' /tmp/caps | tr -d '\r') · igual que la del servidor: $(cmp -s /tmp/baixada.gz $F && echo sí || echo no)"
echo "   panell: $(curl -s -b $JA $B/admin/ | grep -o "L'última descàrrega[^<]*")"
