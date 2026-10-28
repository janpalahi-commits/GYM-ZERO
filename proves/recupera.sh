# Proves de "Has oblidat la contrasenya?" i de les pàgines d'error (Fase 11)
# A XAMPP i amb el servidor de proves de PHP els correus es desen a /tmp/gymzero-correus.txt
B=${B:-http://127.0.0.1:8088}
CORREUS=/tmp/gymzero-correus.txt
J=$(mktemp)
csrf(){ curl -s -b $1 -c $1 "$B$2" | grep -o 'name="csrf" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
demana(){ T=$(csrf $J /compte/oblit.php); curl -s -b $J -c $J -d "csrf=$T&email=$1" $B/compte/oblit.php | grep -o 'class="ok">[^<]*' | sed 's/^/   /'; }
entra(){ T=$(csrf $J /compte/login.php); curl -s -b $J -c $J -o /dev/null -w "   login amb $2: %{http_code} -> %{redirect_url}\n" -d "csrf=$T&email=$1&password=$2" $B/compte/login.php; curl -s -b $J -c $J -o /dev/null $B/compte/logout.php; }
nova(){ T=$(csrf $J "/compte/nova.php?codi=$1"); curl -s -b $J -c $J -d "csrf=$T&codi=$1&password=$2&password2=$3" -w "\n%{http_code} -> %{redirect_url}\n" $B/compte/nova.php | grep -o 'class="error">[^<]*\|^[0-9]* -> .*' | sed 's/^/   /'; }
codi(){ grep -o 'codi=[0-9a-f]*' $CORREUS | tail -1 | cut -d= -f2; }
rm -f $CORREUS

echo "1. Enllaç al login:"; curl -s $B/compte/login.php | grep -o 'Has oblidat[^<]*<a href="[^"]*">[^<]*' | sed 's/^/   /'
echo "2. Demana una contrasenya nova per a marc@example.com:"; demana marc@example.com
echo "   Correus desats: $(grep -c '^Per a:' $CORREUS)"; sed -n '2,3p' $CORREUS | sed 's/^/   /'
echo "3. Demana-la per a un correu que no existeix (mateix missatge, cap correu):"; demana ningu@example.com; echo "   Correus desats: $(grep -c '^Per a:' $CORREUS)"
echo "4. A la BD només hi ha el hash del codi:"; C=$(codi); echo "   codi del correu: ${C:0:12}... ($(echo -n $C | wc -c) caràcters)"; mariadb -N gymzero_db -e "SELECT CONCAT('   hash guardat:   ', LEFT(token_hash, 12), '... coincideix amb SHA-256 del codi: ', token_hash = SHA2('$C', 256)) FROM recuperacions ORDER BY id DESC LIMIT 1"
echo "5. Codi inventat:"; nova $(printf 'a%.0s' {1..64}) novanova1 novanova1
echo "6. Codi bo però contrasenyes diferents:"; nova $C novanova1 altra1234
echo "7. Codi bo:"; nova $C novanova1 novanova1
echo "8. El mateix enllaç una altra vegada:"; nova $C altra1234 altra1234
echo "9. Login amb l'antiga i amb la nova:"; entra marc@example.com Client1234; entra marc@example.com novanova1
echo "10. Enllaç caducat (fa més d'1 hora):"; demana marc@example.com >/dev/null; C2=$(codi); mariadb gymzero_db -e "UPDATE recuperacions SET caduca = NOW() - INTERVAL 1 MINUTE WHERE token_hash = SHA2('$C2', 256)"; nova $C2 altra1234 altra1234
echo "11. Límit de 3 correus per hora (en porta 2; en demana 3 més):"; for i in 1 2 3; do demana marc@example.com >/dev/null; done; echo "   Correus a marc@example.com: $(grep -c '^Per a: marc@example.com' $CORREUS)"
echo "12. Pàgines d'error amb el disseny:"
JC=$(mktemp); T=$(csrf $JC /compte/login.php); curl -s -b $JC -c $JC -o /dev/null -d "csrf=$T&email=pau@example.com&password=Client1234" $B/compte/login.php
err(){ R=$(curl -s -b $2 -w '\n%{http_code}' $3 "$B$4"); echo "   $1: $(echo "$R" | tail -1) $(echo "$R" | grep -o '<h1>[^<]*</h1>') $(echo "$R" | grep -o 'class="btn"[^>]*>[^<]*' | sed 's/.*>/botó: /')"; }
err "client a /admin/" $JC "" /admin/
err "POST sense CSRF" $JC "-d accio=reservar&classe_id=1" /reserves/accio.php
JA=$(mktemp); T=$(csrf $JA /compte/login.php); curl -s -b $JA -c $JA -o /dev/null -d "csrf=$T&email=admin@gymzero.cat&password=Admin1234" $B/compte/login.php
err "classe que no existeix" $JA "" "/admin/classe.php?id=999"
echo "13. Pàgina d'avís legal: $(curl -s -o /dev/null -w '%{http_code}' $B/legal/), enllaç al peu: $(curl -s $B/ | grep -c 'legal/">Avís legal')"
rm -f $J $JC $JA
