# Proves de l'alta de clients a recepció i del canvi de contrasenya (Fase 10)
B=${B:-http://127.0.0.1:8088}
JA=$(mktemp); JC=$(mktemp)
csrf(){ curl -s -b $1 -c $1 "$B$2" | grep -o 'name="csrf" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
avis(){ curl -s -b $1 -c $1 "$B$2" | grep -o '<div class="\(ok\|error\)">[^<]*' | sed 's/<div class="//;s/">/: /'; }
entra(){ T=$(csrf $1 /compte/login.php); curl -s -b $1 -c $1 -o /dev/null -w "   login: %{http_code} -> %{redirect_url}\n" -d "csrf=$T&email=$2&password=$3" $B/compte/login.php; }
alta(){ T=$(csrf $JA /admin/alta.php); curl -s -b $JA -c $JA -d "csrf=$T&$1" $B/admin/alta.php | grep -o 'class="error">[^<]*' | sed 's/^/   /'; avis $JA /admin/usuaris.php | sed 's/^/   /'; }
bd(){ mariadb -t gymzero_db -e "SELECT nom, email, rol, targeta_rfid, quota_fins FROM usuaris WHERE email LIKE '%@prova.cat' ORDER BY id"; }

echo "1. Capçalera per a anònims (enllaços):"; curl -s $B/ | grep -o 'href="[^"]*compte/[^"]*"' | sed 's/^/   /'
echo "2. Login, text de sota el formulari:"; curl -s $B/compte/login.php | grep -o 'No tens compte?[^<]*' | sed 's/^/   /'
echo "3. Un client (Marc) obre /admin/alta.php:"; entra $JC marc@example.com Client1234; curl -s -b $JC -o /dev/null -w "   %{http_code}\n" $B/admin/alta.php
echo "4. Un client envia el formulari d'alta:"; T=$(csrf $JC /client/); curl -s -b $JC -o /dev/null -w "   %{http_code}\n" -d "csrf=$T&nom=A&cognoms=B&email=a@prova.cat&password=provaprova" $B/admin/alta.php
echo "5. Admin entra:"; entra $JA admin@gymzero.cat Admin1234
echo "6. Alta de l'Anna amb polsera i 1 mes pagat:"; alta "nom=Anna&cognoms=Puig&email=anna@prova.cat&polsera=0a1b2c3d&password=provaprova&mesos=1"
echo "7. Alta del Biel sense polsera i sense pagar:"; alta "nom=Biel&cognoms=Roig&email=biel@prova.cat&password=provaprova&mesos=0"
echo "7b. Alta de l'Eva amb 3 mesos pagats:"; alta "nom=Eva&cognoms=Font&email=eva@prova.cat&password=provaprova&mesos=3"
echo "7c. Alta amb una quota de 5 mesos (no existeix):"; alta "nom=Pol&cognoms=Mas&email=pol@prova.cat&password=provaprova&mesos=5"
echo "8. Alta amb la polsera de l'Anna:"; alta "nom=Carla&cognoms=Vila&email=carla@prova.cat&polsera=0A1B2C3D&password=provaprova&mesos=1"
echo "9. Alta amb errors (correu dolent, polsera curta, contrasenya curta):"; alta "nom=Dani&cognoms=Sala&email=dani&polsera=12AB&password=curta"
echo "10. Estat a la BD:"; bd
echo "11. L'Anna entra i veu la quota:"; JN=$(mktemp); entra $JN anna@prova.cat provaprova; curl -s -b $JN $B/client/ | grep -o 'Pagada fins al [0-9/]*\|No tens la quota pagada' | sed 's/^/   /'
echo "12. L'Anna canvia la contrasenya amb l'actual malament:"; T=$(csrf $JN /client/contrasenya.php); curl -s -b $JN -c $JN -d "csrf=$T&actual=dolenta&password=novanova1&password2=novanova1" $B/client/contrasenya.php | grep -o 'class="error">[^<]*' | sed 's/^/   /'
echo "13. L'Anna canvia la contrasenya bé:"; T=$(csrf $JN /client/contrasenya.php); curl -s -b $JN -c $JN -o /dev/null -w "   %{http_code} -> %{redirect_url}\n" -d "csrf=$T&actual=provaprova&password=novanova1&password2=novanova1" $B/client/contrasenya.php; avis $JN /client/ | sed 's/^/   /'
echo "14. Login amb l'antiga i amb la nova:"; JO=$(mktemp); entra $JO anna@prova.cat provaprova; entra $JO anna@prova.cat novanova1
id(){ mariadb -N gymzero_db -e "SELECT id FROM usuaris WHERE email = '$1'"; }
paga(){ T=$(csrf $JA /admin/usuaris.php); curl -s -b $JA -c $JA -o /dev/null -d "csrf=$T&usuari_id=$(id $1)&accio=$2" $B/admin/usuaris.php; avis $JA /admin/usuaris.php | sed 's/^/   /'; }
echo "15. Recepció cobra 3 mesos al Biel (no tenia quota):"; paga biel@prova.cat pagar_3mesos
echo "16. Recepció cobra 3 mesos més a l'Eva (ja en tenia 3):"; paga eva@prova.cat pagar_3mesos
echo "17. Recepció cobra 1 mes a l'Anna:"; paga anna@prova.cat pagar_mes
echo "18. Estat final a la BD:"; bd
rm -f $JA $JC $JN $JO
