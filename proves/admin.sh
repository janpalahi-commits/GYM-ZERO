B=http://127.0.0.1:8089
JA=$(mktemp); JC=$(mktemp); JL=$(mktemp)
csrf(){ curl -s -b $1 -c $1 "$B$2" | grep -o 'name="csrf" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
avis(){ curl -s -b $1 -c $1 "$B$2" | grep -o '<div class="\(ok\|error\)">[^<]*' | sed 's/<div class="//;s/">/: /;s/^/   /'; }
login(){ T=$(csrf $1 /compte/login.php); curl -s -b $1 -c $1 -o /dev/null -w "   %{http_code} -> %{redirect_url}\n" -d "csrf=$T&email=$2&password=$3" $B/compte/login.php; }
post(){ J=$1; P=$2; shift 2; T=$(csrf $J $P); curl -s -b $J -c $J -o /dev/null -d "csrf=$T&$*" $B$P; avis $J $P; }
echo "1. Anònim a /admin/:"; curl -s -o /dev/null -w "   %{http_code} -> %{redirect_url}\n" $B/admin/
echo "2. Client (Marc) entra i prova /admin/:"; login $JC marc@example.com Client1234; curl -s -b $JC -w "   %{http_code} " $B/admin/classes.php; echo
echo "   Client no veu l'enllaç Administració: $(curl -s -b $JC $B/client/ | grep -c 'Administració') coincidències"
echo "3. Admin entra (ha d'anar a /admin/):"; login $JA admin@gymzero.cat Admin1234
echo "   Xifres: $(curl -s -b $JA $B/admin/ | grep -o '<b>[0-9]*</b><span>[^<]*' | sed 's/<[^>]*>/ /g' | tr -s ' ' | paste -sd',')"
echo "4. Crear classe Cross Training demà 20:00 amb places buides:"; D=$(date -d tomorrow +%F); post $JA /admin/classes.php "accio=crear&activitat_id=2&data=$D&hora=20:00&places="
echo "5. Crear-la repetida:"; post $JA /admin/classes.php "accio=crear&activitat_id=2&data=$D&hora=20:00&places=5"
echo "6. Crear-ne una en el passat:"; post $JA /admin/classes.php "accio=crear&activitat_id=2&data=2026-01-01&hora=10:00&places=5"
echo "7. Data inventada (31/02):"; post $JA /admin/classes.php "accio=crear&activitat_id=2&data=2026-02-31&hora=10:00&places=5"
NOVA=$(mariadb -N gymzero_db -e "select id from classes where activitat_id=2 and time(inici)='20:00:00'"); echo "   classe nova id=$NOVA places=$(mariadb -N gymzero_db -e "select places from classes where id=$NOVA")"
echo "8. Marc reserva la classe nova:"; post $JC /reserves/ ; T=$(csrf $JC /reserves/); curl -s -b $JC -c $JC -o /dev/null -d "csrf=$T&accio=reservar&classe_id=$NOVA" $B/reserves/accio.php; avis $JC /reserves/
echo "9. Admin veu qui ha reservat la classe nova:"; curl -s -b $JA "$B/admin/classe.php?id=$NOVA" | grep -o '<td>[^<]*</td>' | sed 's/<\/*td>//g' | paste -sd'|' | sed 's/^/   /'
echo "10. Admin cancel·la la classe nova:"; post $JA /admin/classes.php "accio=cancelar&classe_id=$NOVA"
echo "11. Avís a l'àrea de Marc:"; curl -s -b $JC $B/client/ | grep -o 'Classe cancel·lada pel gimnàs[^<]*' | sed 's/^/   /'
echo "    La classe ja no surt a Classes: $(curl -s -b $JC $B/reserves/ | grep -c "value=\"$NOVA\"") botons"
echo "12. Tornar a cancel·lar-la:"; post $JA /admin/classes.php "accio=cancelar&classe_id=$NOVA"
echo "13. Crear activitat Ioga:"; post $JA /admin/activitats.php "accio=crear&nom=Ioga&durada_min=60&places_per_defecte=10&descripcio=Classe+de+ioga"
echo "14. Crear-la repetida:"; post $JA /admin/activitats.php "accio=crear&nom=Ioga&durada_min=60&places_per_defecte=10"
echo "15. Desactivar Mobilitat (id 5):"; post $JA /admin/activitats.php "accio=canviar_estat&activitat_id=5"; echo "    Mobilitat surt a Classes: $(curl -s $B/reserves/ | grep -c Mobilitat)"
post $JA /admin/activitats.php "accio=canviar_estat&activitat_id=5" >/dev/null
echo "16. Admin intenta desactivar-se a si mateix:"; post $JA /admin/usuaris.php "accio=canviar_actiu&usuari_id=1"
echo "17. Admin desactiva la Laia (id 3) i ella intenta entrar:"; post $JA /admin/usuaris.php "accio=canviar_actiu&usuari_id=3"; T=$(csrf $JL /compte/login.php); curl -s -b $JL -c $JL -d "csrf=$T&email=laia@example.com&password=Client1234" $B/compte/login.php | grep -o 'class="error">[^<]*' | sed 's/^/   /'
post $JA /admin/usuaris.php "accio=canviar_actiu&usuari_id=3" >/dev/null
echo "18. Admin fa admin el Pau (id 4) i després el torna client; Marc continua sense accés:"; post $JA /admin/usuaris.php "accio=canviar_rol&usuari_id=4"; post $JA /admin/usuaris.php "accio=canviar_rol&usuari_id=4"; curl -s -b $JC -o /dev/null -w "   Marc a /admin/: %{http_code}\n" $B/admin/
echo "19. Admin treu el rol d'admin mentre té la sessió oberta (simulat a la BD):"; mariadb gymzero_db -e "update usuaris set rol='client' where id=1"; curl -s -b $JA -o /dev/null -w "   %{http_code}\n" $B/admin/; mariadb gymzero_db -e "update usuaris set rol='admin' where id=1"
echo "20. POST d'admin sense CSRF:"; curl -s -b $JA -o /dev/null -w "   %{http_code}\n" -d "accio=cancelar&classe_id=1" $B/admin/classes.php
