# Proves de la fase 12: tarifes, qüestionari, pla en PDF, llibreta i seguiment.
# Abans: bd/gymzero_bd.sql (o actualitza_fase12.sql), dades_prova.sql i dades_prova_fase12.sql.
# Marc = tarifa amb seguiment, Pau = tarifa amb pla, Laia = tarifa Gimnàs (i quota caducada).
B=${B:-http://127.0.0.1:8088}
BD=${BD:-gymzero_db}
JA=$(mktemp); JM=$(mktemp); JP=$(mktemp); JL=$(mktemp); TMP=$(mktemp -d)
csrf(){ curl -s -b $1 -c $1 "$B$2" | grep -o 'name="csrf" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
avis(){ curl -s -b $1 -c $1 "$B$2" | grep -o '<div class="\(ok\|error\)">[^<]*' | sed 's/<div class="//;s/">/: /;s/^/   /'; }
entra(){ T=$(csrf $1 /compte/login.php); curl -s -b $1 -c $1 -o /dev/null -d "csrf=$T&email=$2&password=$3" $B/compte/login.php; }
codi(){ curl -s -b $1 -o /dev/null -w "%{http_code}" "$B$2"; }
id(){ mariadb -N $BD -e "SELECT id FROM usuaris WHERE email = '$1'"; }
sql(){ mariadb --default-character-set=utf8mb4 -t $BD -e "$1"; }
MARC=$(id marc@example.com); PAU=$(id pau@example.com); LAIA=$(id laia@example.com)
entra $JA admin@gymzero.cat Admin1234; entra $JM marc@example.com Client1234; entra $JP pau@example.com Client1234; entra $JL laia@example.com Client1234

echo "1. Portada: preus de les tarifes"
curl -s $B/ | grep -o '<h3>[^<]*</h3>\|<b>[0-9]* €</b> al mes' | sed 's/<[^>]*>//g;s/^/   /' | paste -sd' ' | sed 's/ al mes/ al mes ·/g'

echo "2. Qui pot entrar a cada servei (codi HTTP)"
printf "   %-26s %6s %6s %6s %6s\n" pàgina Laia Pau Marc Anònim
for p in /client/questionari.php /client/llibreta.php /client/apunta.php /client/pla.php /client/seguiment.php /admin/plans.php "/admin/client.php?id=$MARC"; do
  printf "   %-26s %6s %6s %6s %6s\n" "$p" $(codi $JL $p) $(codi $JP $p) $(codi $JM $p) $(codi /dev/null $p)
done
echo "   Missatge que veu la Laia al qüestionari:"; curl -s -b $JL $B/client/questionari.php | grep -o '<p>Aquest servei[^<]*\|<p>Tens la quota[^<]*' | sed 's/<p>/   /'
echo "   El Pau a seguiment:"; curl -s -b $JP $B/client/seguiment.php | grep -o '<p>Aquest servei[^<]*' | sed 's/<p>/   /'

echo "3. La meva àrea: secció El meu pla"
for J in $JL $JP $JM; do curl -s -b $J $B/client/ | tr -d '\n' | grep -o '<h2>El meu pla</h2>.*<h2>El meu entrenament' | grep -o '<h3>[^<]*</h3>\|class="tancat"\|Tens la tarifa <b>[^<]*' | sed 's/<[^>]*>//g' | paste -sd'|' | sed 's/^/   /'; done

echo "4. El Pau envia el qüestionari amb una alçada impossible i la resta per omplir"
camps(){ php $(dirname $0)/camps.php "$@"; }
T=$(csrf $JP /client/questionari.php)
R4=$(curl -s -b $JP -c $JP -d "csrf=$T&lang=ca&$(camps idioma_pla=Català naixement=1995-06-02 alcada=300 pes=88 sexe=Home)" $B/client/questionari.php | tr -d '\n')
echo "$R4" | grep -o '<div class="error" role="alert">[^<]*' | sed 's/.*>/   Avís: /'
echo "   Preguntes en vermell: $(echo "$R4" | grep -o 'class="pregunta err"' | wc -l)"
for t in 'Alçada' 'Confirmo'; do
  echo "$R4" | sed 's/<div class="pregunta/\n&/g' | grep "class=\"enunciat\"[^>]*>$t" | grep -o '<p class="msg">[^<]*' | sed "s/<p class=\"msg\">/   $t -> /"
done
echo "5. El Pau l'envia bé, en anglès (amb una opció inventada i una resposta d'una pàgina que no li toca)"
ARGS=(idioma_pla=Català naixement=1995-06-02 alcada=172 pes=87,5 canvi_pes=Estable bascula=Sí "com_et_veus=Amb bastant greix" sexe=Home
  cor=No dolor_pit=No malalties=Cap medicacio=Cap "al_lergies=Lactosa|Inventada" dolor_ara=No lesio_on=Genoll
  "objectiu=Perdre greix" "que_ha_fallat=Falta de temps" compromis=8 "ritme=Normal: l'equilibri (recomanat)" "experiencia=No he entrenat mai"
  dies=3 "quins_dies=Dilluns|Dimecres|Divendres" "temps_sessio=45-60 min" "hora_entreno=07:00 (matí)" cardio=No esport=No
  "feina=Dret o caminant" "dies_feina=Dilluns|Dimarts" "hora_feina_inici=09:00 (matí)" "hora_feina_fi=18:00 (tarda)" torns=No
  "hora_llevar=06:00 (matí)" "hora_dormir=23:00 (nit)" son=Regular estres=6 "fumes_alcohol=Res d'això" "gana=Sempre tinc gana"
  "hores_apats=5-7 h|No la faig|13-15 h|No la faig|19-21 h|No la faig" apats=3 "on_menges=A casa" qui_cuina=Jo "temps_cuinar=Una mica"
  bascula_cuina=Sí dieta=Cap "no_menges=Menjo de tot" "caps_setmana=Em descontrolo bastant" suplements=Cap "aigua=1-2 litres"
  pressupost=Normal relacio_menjar=No permis_anonim=No "menor=Sóc major d'edat")
CONFIRMO=$(php -r 'require "'$(dirname $0)'/../www/inc/questionari.php"; foreach (questionari()["q"] as $i => $q) if (($q["k"] ?? "") === "confirmo") echo "p[$i][]=0&p[$i][]=1&p[$i][]=2&p[$i][]=3";')
curl -s -b $JP -c $JP -o /dev/null -d "csrf=$T&lang=en&$(camps "${ARGS[@]}")&$CONFIRMO" $B/client/questionari.php
avis $JP /client/
sql "SELECT JSON_VALUE(respostes,'$.\"Idioma del formulario\"') AS idioma, JSON_VALUE(respostes,'$.\"Peso actual (kg)\"') AS pes,
     JSON_EXTRACT(respostes,'$.\"¿Tienes alguna alergia o intolerancia alimentaria?\"') AS alergies,
     JSON_EXTRACT(respostes,'$.\"¿Dónde?\"') AS lesio, JSON_VALUE(respostes,'$.\"¿A qué hora sueles empezar a entrenar?\"') AS hora, consentiment
     FROM questionaris WHERE usuari_id = $PAU ORDER BY id DESC LIMIT 1"

echo "6. L'entrenador: llista de plans pendents"
curl -s -b $JA $B/admin/plans.php | tr -d '\n' | sed 's/<\/table>.*//' | sed 's/<tr>/\n/g' | grep 'client.php' | sed 's/<span class="etiqueta [a-z]*">/[/g;s/<\/span>/]/g;s/<[^>]*>/ /g;s/Obrir la fitxa//;s/  */ /g;s/^/  /'
echo "   Al panell: $(curl -s -b $JA $B/admin/ | grep -o 'Plans i seguiment[^<]*')"

echo "7. Descarregar el JSON del Pau i pujar-li el pla"
curl -s -b $JA -D $TMP/cap -o $TMP/pau.json "$B/admin/client.php?id=$PAU&json=1"; grep -i '^content-type\|^content-disposition' $TMP/cap | tr -d '\r' | sed 's/^/   /'
python3 -c "import json,sys; d=json.load(open('$TMP/pau.json')); r=d['respostes']; print('   client:', r['Nombre y apellido'], '· objectiu:', r['¿Cuál es tu objetivo principal?'], '· al·lèrgies:', r['¿Tienes alguna alergia o intolerancia alimentaria?'], '· columnes:', len(r))"
printf 'no soc un pdf' > $TMP/fals.pdf
printf '%%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%%%EOF\n' > $TMP/pla_pau.pdf
puja(){ T=$(csrf $JA "/admin/client.php?id=$PAU"); curl -s -b $JA -c $JA -o /dev/null -F csrf=$T -F id=$PAU -F accio=pujar_pla -F "pdf=@$1;type=application/pdf" $B/admin/client.php; avis $JA "/admin/client.php?id=$PAU"; }
echo "   Un fitxer que no és PDF:"; puja $TMP/fals.pdf
echo "   El PDF:"; puja $TMP/pla_pau.pdf
echo "   El Pau el descarrega:"; curl -s -b $JP -D $TMP/cap2 -o $TMP/baixat.pdf $B/client/pla.php; grep -i '^content-type\|^content-disposition' $TMP/cap2 | tr -d '\r' | sed 's/^/   /'; cmp -s $TMP/baixat.pdf $TMP/pla_pau.pdf && echo "   És el mateix fitxer que s'ha pujat"
echo "   La Laia no el pot descarregar ni posant-hi un altre usuari:"; echo "   $(codi $JL "/client/pla.php?usuari=$PAU")"
echo "   El Marc posa ?usuari= del Pau i rep el seu, no el del Pau:"; curl -s -b $JM -o $TMP/m.pdf -w "   %{http_code}\n" "$B/client/pla.php?usuari=$PAU"; cmp -s $TMP/m.pdf $TMP/pla_pau.pdf && echo "   ERROR: ha rebut el del Pau" || echo "   Correcte: no és el del Pau"

echo "8. Llibreta del Marc"
curl -s -b $JM "$B/client/llibreta.php?dia=1" > $TMP/ll.html
echo "   Pestanyes: $(tr -d '\n' < $TMP/ll.html | grep -o '<nav class="dies".*</nav>' | sed 's/<\/nav>.*//' | grep -o '>[A-Z][a-z]*</a>' | sed 's/[<>]//g;s/\/a//' | paste -sd' ')"
echo "   Exercicis del dilluns: $(grep -o '<th class="ex" scope="row">' $TMP/ll.html | wc -l) · caselles amb rècord: $(grep -c 'te-record' $TMP/ll.html) · setmanes: $(grep -o 'Setm. [0-9]' $TMP/ll.html | wc -l)"
echo "   Rècord del press de banca: $(tr -d '\n' < $TMP/ll.html | grep -o 'Press de banca</b>.*' | grep -o 'class="set rec">[^<]*' | head -1 | sed 's/.*>//')"
curl -s -b $JM -D $TMP/cap3 -o $TMP/ll.csv "$B/client/llibreta.php?csv=1"
echo "   CSV: $(grep -i '^content-type' $TMP/cap3 | tr -d '\r') · $(wc -l < $TMP/ll.csv) línies"; head -3 $TMP/ll.csv | sed 's/^/   /'

echo "9. El Marc apunta el press inclinat d'avui"
R=$(mariadb -N $BD -e "SELECT id FROM rutina WHERE usuari_id = $MARC AND exercici LIKE 'Press inclinat%'")
RM=$(mariadb -N $BD -e "SELECT id FROM rutina WHERE usuari_id = $MARC AND maquina_id IS NOT NULL LIMIT 1")
T=$(csrf $JM "/client/apunta.php?r=$R")
echo "   Amb una sèrie dolenta:"; curl -s -b $JM -c $JM -d "csrf=$T&r=$R&data=$(date +%F)&kg[1]=22,5&reps[1]=10&kg[2]=abc&reps[2]=8" $B/client/apunta.php | grep -o 'class="error">[^<]*' | sed 's/class="error">/   /'
echo "   Bé (la 3a buida):"; curl -s -b $JM -c $JM -o /dev/null -d "csrf=$T&r=$R&data=$(date +%F)&kg[1]=22,5&reps[1]=10&kg[2]=22.5&reps[2]=9&kg[3]=&reps[3]=" $B/client/apunta.php; avis $JM /client/llibreta.php
echo "   Corregeix: la 2a amb 8 reps i n'afegeix una 3a:"; curl -s -b $JM -c $JM -o /dev/null -d "csrf=$T&r=$R&data=$(date +%F)&kg[1]=22,5&reps[1]=10&kg[2]=22,5&reps[2]=8&kg[3]=20&reps[3]=10" $B/client/apunta.php; avis $JM /client/llibreta.php
sql "SELECT data, num_serie, carrega_kg, reps FROM registre WHERE rutina_id = $R AND data = CURDATE() ORDER BY num_serie"
echo "   Amb una data de demà:"; curl -s -b $JM -c $JM -d "csrf=$T&r=$R&data=$(date -d tomorrow +%F)&kg[1]=20&reps[1]=10" $B/client/apunta.php | grep -o 'class="error">[^<]*' | sed 's/class="error">/   /'
echo "   Un exercici de màquina (no es pot apuntar a mà) i un del Marc des del compte del Pau:"
curl -s -b $JM "$B/client/apunta.php?r=$RM" | grep -o '<p>Tria l.exercici' | sed 's/<p>/   Marc, màquina: surt la llista: /'
curl -s -b $JP "$B/client/apunta.php?r=$R" | grep -o '<p>La teva rutina no té[^<]*' | sed 's/<p>/   Pau: /'
T2=$(csrf $JP /client/apunta.php); curl -s -b $JP -c $JP -o /dev/null -d "csrf=$T2&r=$R&data=$(date +%F)&kg[1]=99&reps[1]=1" $B/client/apunta.php
sql "SELECT COUNT(*) AS series_de_99kg FROM registre WHERE rutina_id = $R AND carrega_kg = 99"

echo "10. Seguiment del Marc"
curl -s -b $JM $B/client/seguiment.php | grep -o 'Ja toca la revisió\|La propera revisió toca el <b>[^<]*' | sed 's/<b>//;s/^/   /'
T=$(csrf $JM /client/seguiment.php)
curl -s -b $JM -c $JM -o /dev/null -d "csrf=$T&accio=revisio&pes_kg=77,8&com_va=Molt bé, he pujat el press inclinat." $B/client/seguiment.php; avis $JM /client/seguiment.php
echo "   Una altra revisió el mateix dia:"; curl -s -b $JM -c $JM -d "csrf=$T&accio=revisio&com_va=Una altra" $B/client/seguiment.php | grep -o 'class="error">[^<]*' | sed 's/class="error">/   /'
echo "   Missatge buit i missatge bo:"; curl -s -b $JM -c $JM -d "csrf=$T&accio=missatge&text=   " $B/client/seguiment.php | grep -o 'class="error">[^<]*' | sed 's/class="error">/   /'
curl -s -b $JM -c $JM -o /dev/null -d "csrf=$T&accio=missatge&text=<b>Puc</b> canviar la sentadilla per premsa?" $B/client/seguiment.php
curl -s -b $JM $B/client/seguiment.php | grep -o '<li class="meu"><b>Tu</b>.*' | tail -1 | sed 's/<small>.*<p>/ · /;s/<\/p>.*//;s/<[^>]*>//g;s/^/   /'
echo "   Sense CSRF:"; curl -s -b $JM -o /dev/null -w "   %{http_code}\n" -d "accio=missatge&text=hola" $B/client/seguiment.php

echo "11. L'entrenador respon"
REV=$(mariadb -N $BD -e "SELECT id FROM revisions WHERE usuari_id = $MARC AND resposta IS NULL")
T=$(csrf $JA "/admin/client.php?id=$MARC")
curl -s -b $JA -c $JA -o /dev/null -d "csrf=$T&id=$MARC&accio=respondre_revisio&revisio_id=$REV&resposta=Perfecte. Puja a 24 kg." $B/admin/client.php
curl -s -b $JA -c $JA -o /dev/null -d "csrf=$T&id=$MARC&accio=missatge&text=Sí, fes premsa aquesta setmana." $B/admin/client.php
curl -s -b $JA -c $JA -o /dev/null -d "csrf=$T&id=$MARC&accio=afegir_exercici&dia=5&exercici=Premsa de cames&series=3&maquina_id=&nota=en lloc de la sentadilla" $B/admin/client.php
curl -s -b $JA -c $JA -o /dev/null -d "csrf=$T&id=$MARC&accio=afegir_exercici&dia=9&exercici=&series=40" $B/admin/client.php; avis $JA "/admin/client.php?id=$MARC"
curl -s -b $JA -c $JA -o /dev/null -d "csrf=$T&id=$MARC&accio=avisar_rutina" $B/admin/client.php
sql "SELECT dia, ordre, exercici, series, maquina_id, nota FROM rutina WHERE usuari_id = $MARC AND dia = 5 ORDER BY ordre"
echo "   Avisos que rep el Marc:"; curl -s -b $JM $B/client/ | tr -d '\n' | grep -o '<h2>Avisos</h2>.*' | grep -o '<td>[^<]*</td><td>' | sed 's/<[^>]*>//g;s/^/   /' | head -4
echo "   Missatges del Marc sense llegir per l'entrenador: $(mariadb -N $BD -e "SELECT COUNT(*) FROM missatges WHERE usuari_id = $MARC AND autor_id = $MARC AND llegit = 0")"
echo "   Un client no pot fer servir el formulari de l'entrenador:"; T=$(csrf $JM /client/); curl -s -b $JM -o /dev/null -w "   %{http_code}\n" -d "csrf=$T&id=$MARC&accio=missatge&text=hola" $B/admin/client.php

echo "12. Treure un exercici esborra el que s'hi havia apuntat a mà"
T=$(csrf $JA "/admin/client.php?id=$MARC")
echo "   Abans: $(mariadb -N $BD -e "SELECT COUNT(*) FROM registre WHERE rutina_id = $R") sèries"
curl -s -b $JA -c $JA -o /dev/null -d "csrf=$T&id=$MARC&accio=treure_exercici&rutina_id=$R" $B/admin/client.php
echo "   Després: $(mariadb -N $BD -e "SELECT COUNT(*) FROM registre WHERE rutina_id = $R") sèries"

echo "13. El Pau retira el consentiment: recepció esborra les dades del pla"
T=$(csrf $JA "/admin/client.php?id=$PAU")
curl -s -b $JA -c $JA -o /dev/null -d "csrf=$T&id=$PAU&accio=esborrar_dades_pla" $B/admin/client.php
echo "   Sense marcar la confirmació: $(mariadb -N $BD -e "SELECT COUNT(*) FROM questionaris WHERE usuari_id = $PAU") qüestionaris"
curl -s -b $JA -c $JA -o /dev/null -d "csrf=$T&id=$PAU&accio=esborrar_dades_pla&confirmo=1" $B/admin/client.php; avis $JA "/admin/client.php?id=$PAU"
sql "SELECT (SELECT COUNT(*) FROM questionaris WHERE usuari_id = $PAU) AS questionaris, (SELECT COUNT(*) FROM plans WHERE usuari_id = $PAU) AS plans, (SELECT COUNT(*) FROM pagaments WHERE usuari_id = $PAU) AS pagaments"

rm -rf $JA $JM $JP $JL $TMP
