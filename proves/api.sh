#!/bin/bash
# Proves de l'API de les màquines (api/serie.php)
B=${1:-http://127.0.0.1:8090}/api/serie.php
REPS='[{"velocitat":0.80,"recorregut":0.41,"durada":0.50},{"velocitat":0.74,"recorregut":0.42,"durada":0.55},{"velocitat":0.66,"recorregut":0.40,"durada":0.62}]'
envia(){ echo "   $(curl -s -w ' [HTTP %{http_code}]' -H "X-Token: $1" -H 'Content-Type: application/json' -d "$2" $B)"; }
echo "1. GET (no permès):";               echo "   $(curl -s -w ' [HTTP %{http_code}]' $B)"
echo "2. JSON trencat:";                  envia token-PRESS-01 '{maquina:'
echo "3. Sense clau:";                    envia '' "{\"maquina\":\"PRESS-01\",\"targeta\":\"04A1B2C3\",\"carrega_kg\":60,\"repeticions\":$REPS}"
echo "4. Clau d'una altra màquina:";      envia token-SQUAT-01 "{\"maquina\":\"PRESS-01\",\"targeta\":\"04A1B2C3\",\"carrega_kg\":60,\"repeticions\":$REPS}"
echo "5. Màquina que no existeix:";       envia token-PRESS-01 "{\"maquina\":\"XXX-99\",\"targeta\":\"04A1B2C3\",\"carrega_kg\":60,\"repeticions\":$REPS}"
echo "6. Polsera desconeguda:";           envia token-PRESS-01 "{\"maquina\":\"PRESS-01\",\"targeta\":\"FFFFFFFF\",\"carrega_kg\":60,\"repeticions\":$REPS}"
echo "7. Càrrega negativa:";              envia token-PRESS-01 "{\"maquina\":\"PRESS-01\",\"targeta\":\"04A1B2C3\",\"carrega_kg\":-5,\"repeticions\":$REPS}"
echo "8. Sense repeticions:";             envia token-PRESS-01 '{"maquina":"PRESS-01","targeta":"04A1B2C3","carrega_kg":60,"repeticions":[]}'
echo "9. Velocitat impossible (9 m/s):";  envia token-PRESS-01 '{"maquina":"PRESS-01","targeta":"04A1B2C3","carrega_kg":60,"repeticions":[{"velocitat":9,"recorregut":0.4,"durada":0.5}]}'
echo "10. Sèrie correcta (Marc, press):";  envia token-PRESS-01 "{\"maquina\":\"PRESS-01\",\"targeta\":\"04A1B2C3\",\"carrega_kg\":60,\"repeticions\":$REPS}"
echo "11. Segona sèrie (ha de ser la 2):"; envia token-PRESS-01 "{\"maquina\":\"PRESS-01\",\"targeta\":\"04A1B2C3\",\"carrega_kg\":62.5,\"repeticions\":$REPS}"
echo "12. Primera sèrie a la sentadilla:"; envia token-SQUAT-01 "{\"maquina\":\"SQUAT-01\",\"targeta\":\"04A1B2C3\",\"carrega_kg\":80,\"repeticions\":$REPS}"
echo "13. 5 sèries alhora de la Laia (números 1..5 sense repetir):"
for i in 1 2 3 4 5; do curl -s -o /dev/null -H 'X-Token: token-REM-01' -d "{\"maquina\":\"REM-01\",\"targeta\":\"04D4E5F6\",\"carrega_kg\":40,\"repeticions\":$REPS}" $B & done; wait
echo "   $(mariadb -N gymzero_db -e "select group_concat(num_serie order by num_serie) from series s join entrenaments e on e.id=s.entrenament_id where e.usuari_id=3 and e.data=curdate()")"
echo "14. Màquina en manteniment:"; mariadb gymzero_db -e "update maquines set estat='manteniment' where codi='REM-01'"
envia token-REM-01 "{\"maquina\":\"REM-01\",\"targeta\":\"04D4E5F6\",\"carrega_kg\":40,\"repeticions\":$REPS}"
mariadb gymzero_db -e "update maquines set estat='activa' where codi='REM-01'"
echo "15. Entrenaments d'avui (un per persona):"
mariadb gymzero_db -e "select u.nom, e.data, count(s.id) series from entrenaments e join usuaris u on u.id=e.usuari_id join series s on s.entrenament_id=e.id where e.data=curdate() group by e.id"
