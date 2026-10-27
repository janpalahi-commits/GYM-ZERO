#!/bin/bash
# Proves de l'API del torn d'entrada a les sales (api/entrada.php)
B=${1:-http://127.0.0.1:8090}/api/entrada.php
envia(){ echo "   $(curl -s -w ' [HTTP %{http_code}]' -H "X-Token: $1" -H 'Content-Type: application/json' -d "$2" $B)"; }
echo "1. GET (no permès):";                   echo "   $(curl -s -w ' [HTTP %{http_code}]' $B)"
echo "2. JSON trencat:";                      envia token-TORN-01 '{torn:'
echo "3. Sense clau:";                        envia '' '{"torn":"TORN-01","targeta":"04A1B2C3"}'
echo "4. Clau d'una màquina:";                envia token-PRESS-01 '{"torn":"TORN-01","targeta":"04A1B2C3"}'
echo "5. Codi de polsera mal format:";        envia token-TORN-01 '{"torn":"TORN-01","targeta":"hola"}'
echo "6. Polsera desconeguda:";               envia token-TORN-01 '{"torn":"TORN-01","targeta":"FFFFFFFF"}'
echo "7. Marc (quota pagada):";               envia token-TORN-01 '{"torn":"TORN-01","targeta":"04A1B2C3"}'
echo "8. Marc en minúscules:";                envia token-TORN-01 '{"torn":"TORN-01","targeta":"04a1b2c3"}'
echo "9. Laia (quota caducada):";             envia token-TORN-01 '{"torn":"TORN-01","targeta":"04D4E5F6"}'
echo "10. Pau (li caduca d'aquí a 5 dies):";  envia token-TORN-01 '{"torn":"TORN-01","targeta":"04078899"}'
echo "11. Admin (personal, sense quota):";    envia token-TORN-01 '{"torn":"TORN-01","targeta":"AD000001"}'
echo "12. Pau amb el compte desactivat:";     mariadb gymzero_db -e "update usuaris set actiu=0 where id=4"
envia token-TORN-01 '{"torn":"TORN-01","targeta":"04078899"}'
mariadb gymzero_db -e "update usuaris set actiu=1 where id=4"
echo "13. La Laia paga un mes i torna a provar:"; mariadb gymzero_db -e "update usuaris set quota_fins = curdate() + interval 1 month - interval 1 day where id=3"
envia token-TORN-01 '{"torn":"TORN-01","targeta":"04D4E5F6"}'
mariadb gymzero_db -e "update usuaris set quota_fins = curdate() - interval 3 day where id=3"
echo "14. Torn donat de baixa:";            mariadb gymzero_db -e "update torns set estat='baixa' where codi='TORN-01'"
envia token-TORN-01 '{"torn":"TORN-01","targeta":"04A1B2C3"}'
mariadb gymzero_db -e "update torns set estat='activa' where codi='TORN-01'"
echo "15. Entrades apuntades avui:"
mariadb gymzero_db -e "select time(e.data_hora) hora, coalesce(u.nom, e.targeta) qui, e.permesa, e.motiu from entrades e left join usuaris u on u.id=e.usuari_id where e.data_hora >= curdate() order by e.id"
