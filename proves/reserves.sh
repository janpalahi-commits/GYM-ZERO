B=${B:-http://127.0.0.1:8088}
J1=$(mktemp); J2=$(mktemp)
csrf(){ curl -s -b $1 -c $1 "$B$2" | grep -o 'name="csrf" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
avis(){ curl -s -b $1 -c $1 "$B$2" | grep -o '<div class="\(ok\|error\)">[^<]*' | sed 's/<div class="//;s/">/: /'; }
echo "1. Anònim a /reserves/:"; curl -s $B/reserves/ | grep -c "<tr>" | sed 's/^/   files taula: /'; curl -s $B/reserves/ | grep -c 'name="accio"' | sed 's/^/   botons: /'
echo "2. Anònim a /client/:"; curl -s -o /dev/null -w "   %{http_code} -> %{redirect_url}\n" $B/client/
JA=$(mktemp); T=$(csrf $JA /compte/login.php); curl -s -b $JA -c $JA -o /dev/null -d "csrf=$T&email=admin@gymzero.cat&password=Admin1234" $B/compte/login.php
alta(){ T=$(csrf $JA /admin/alta.php); curl -s -b $JA -c $JA -d "csrf=$T&$1" $B/admin/alta.php | grep -o 'class="error">[^<]*' | sed 's/^/   /'; avis $JA /admin/usuaris.php | sed 's/^/   /'; }
echo "3. Recepció dona d'alta el Jan i cobra el primer mes:"; alta "nom=Jan&cognoms=Prova&email=jan@example.com&password=provaprova&mesos=1"
T=$(csrf $J1 /compte/login.php); curl -s -b $J1 -c $J1 -o /dev/null -w "   login del Jan: %{http_code} -> %{redirect_url}\n" -d "csrf=$T&email=jan@example.com&password=provaprova" $B/compte/login.php
echo "4. Alta amb email repetit:"; alta "nom=X&cognoms=Y&email=marc@example.com&password=provaprova&mesos=1"
echo "5. Anònim a l'antic /compte/registre.php:"; J3=$(mktemp); curl -s -b $J3 -c $J3 -o /dev/null -w "   %{http_code} -> %{redirect_url}\n" $B/compte/registre.php; avis $J3 /compte/login.php | sed 's/^/   /'
echo "6. Login contrasenya incorrecta:"; T=$(csrf $J2 /compte/login.php); curl -s -b $J2 -c $J2 -d "csrf=$T&email=marc@example.com&password=dolenta" $B/compte/login.php | grep -o 'class="error">[^<]*' | sed 's/^/   /'
echo "7. Login correcte (Marc):"; T=$(csrf $J2 /compte/login.php); curl -s -b $J2 -c $J2 -o /dev/null -w "   %{http_code} -> %{redirect_url}\n" -d "csrf=$T&email=marc@example.com&password=Client1234" $B/compte/login.php
acc(){ T=$(csrf $1 /reserves/); curl -s -b $1 -c $1 -o /dev/null -d "csrf=$T&accio=$2&classe_id=$3" $B/reserves/accio.php; avis $1 /reserves/ | sed 's/^/   /'; }
echo "8. Marc reserva Cross Training (classe 2):"; acc $J2 reservar 2
echo "9. Marc la torna a reservar:"; acc $J2 reservar 2
echo "10. Marc reserva Mobilitat (classe 5, 2 places, 1 ocupada):"; acc $J2 reservar 5
echo "11. Jan reserva Mobilitat (ja plena):"; acc $J1 reservar 5
echo "12. Marc cancel·la Mobilitat:"; acc $J2 cancelar 5
echo "13. Jan reserva Mobilitat ara:"; acc $J1 reservar 5
echo "14. Jan cancel·la una classe que no té (classe 3):"; acc $J1 cancelar 3
echo "15. POST sense token CSRF:"; curl -s -b $J2 -o /dev/null -w "   %{http_code}\n" -d "accio=reservar&classe_id=4" $B/reserves/accio.php
echo "16. Àrea de Marc:"; curl -s -b $J2 $B/client/ | grep -o '<td>[^<]*</td>' | sed 's/<\/*td>//g' | paste -sd'|' | sed 's/^/   /'
echo "17. Logout i tornar a /client/:"; curl -s -b $J2 -c $J2 -o /dev/null $B/compte/logout.php; curl -s -b $J2 -o /dev/null -w "   %{http_code} -> %{redirect_url}\n" $B/client/
entra(){ T=$(csrf $1 /compte/login.php); curl -s -b $1 -c $1 -o /dev/null -d "csrf=$T&email=$2&password=Client1234" $B/compte/login.php; }
echo "18. Client donat d'alta sense pagar (Nil) reserva Hipertrofia (classe 4):"; alta "nom=Nil&cognoms=Prova&email=nil@example.com&password=provaprova&mesos=0" >/dev/null; J4=$(mktemp); T=$(csrf $J4 /compte/login.php); curl -s -b $J4 -c $J4 -o /dev/null -d "csrf=$T&email=nil@example.com&password=provaprova" $B/compte/login.php; acc $J4 reservar 4
echo "19. Pàgina de classes del Nil:"; curl -s -b $J4 $B/reserves/ | grep -o 'class="error">[^<]*\|>Sense quota<' | sort | uniq -c | sed 's/^ */   /'
echo "20. Laia (quota caducada fa 3 dies) reserva Hipertrofia (classe 4):"; J5=$(mktemp); entra $J5 laia@example.com; acc $J5 reservar 4
echo "21. Pau amb la quota fins demà reserva Powerlifting (classe 3, d'aquí a 2 dies):"; mariadb gymzero_db -e "UPDATE usuaris SET quota_fins = CURDATE() + INTERVAL 1 DAY WHERE email = 'pau@example.com'"; J6=$(mktemp); entra $J6 pau@example.com; acc $J6 reservar 3
echo "22. Pau reserva Musculació (classe 1, demà):"; acc $J6 reservar 1
echo "23. Estat a la BD:"; mariadb -t gymzero_db -e "SELECT u.email, r.classe_id, r.estat FROM reserves r JOIN usuaris u ON u.id=r.usuari_id ORDER BY r.classe_id, u.email"
