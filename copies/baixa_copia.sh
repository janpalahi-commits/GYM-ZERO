#!/bin/bash
# GYM-ZERO · Còpia de seguretat fora del servidor (la "1" de la regla 3-2-1)
#
# El PC de recepció es descarrega de gymzero.cat l'última còpia de la base de dades
# i en guarda les 30 últimes a ~/gymzero-copies. El cron l'executa cada hora i en engegar
# l'ordinador: si avui ja en té una, no fa res. Si l'ordinador ha estat apagat, es posa al dia sol.
# S'instal·la amb installa_pc.sh. El que passa queda apuntat a ~/gymzero-copies/baixades.log.

set -u
DESTI="$HOME/gymzero-copies"
FITXER_CLAU="$HOME/.gymzero-copia.clau"     # la clau del panell d'administració (només la pot llegir l'usuari)
URL="${GYMZERO_URL:-https://gymzero.cat}/api/copia.php"
GUARDAR=30

umask 077                                     # tot el que es crea, només per a l'usuari: hi ha dades de salut
mkdir -p "$DESTI"
LOG="$DESTI/baixades.log"
apunta() { echo "$(date '+%Y-%m-%d %H:%M:%S') $1" >> "$LOG"; }

# Avui ja en tenim una? (el nom de cada còpia porta la data)
if compgen -G "$DESTI/gymzero_$(date +%Y-%m-%d)_*.sql.gz" > /dev/null; then
    exit 0
fi
if [ ! -r "$FITXER_CLAU" ]; then
    apunta "ERROR: falta la clau ($FITXER_CLAU). Torna a executar installa_pc.sh."
    exit 1
fi

TEMP=$(mktemp "$DESTI/.baixant.XXXXXX")
CAPS=$(mktemp)
PETICIO=$(mktemp)
trap 'rm -f "$TEMP" "$CAPS" "$PETICIO"' EXIT
# La clau va en un fitxer i no a la línia d'ordres, perquè no surti a la llista de processos
printf 'X-Token: %s\n' "$(tr -d '[:space:]' < "$FITXER_CLAU")" > "$PETICIO"

CODI=$(curl -sS --max-time 300 -o "$TEMP" -D "$CAPS" -w '%{http_code}' -H @"$PETICIO" "$URL" 2>&1)
if [ "$CODI" != "200" ]; then
    apunta "ERROR: no s'ha pogut baixar la còpia (resposta $CODI: $(head -c 100 "$TEMP" | tr '\n' ' '))"
    exit 1
fi

NOM=$(grep -i '^X-Copia:' "$CAPS" | cut -d' ' -f2 | tr -d '\r\n')
if ! [[ "$NOM" =~ ^gymzero_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{6}\.sql\.gz$ ]]; then
    apunta "ERROR: el servidor no ha dit bé el nom de la còpia ($NOM)"
    exit 1
fi
if [ -f "$DESTI/$NOM" ]; then
    exit 0                                    # és la mateixa que ja tenim (avui encara no se n'ha fet cap de nova)
fi

# Comprovar que el fitxer està sencer abans de guardar-lo
if ! gzip -t "$TEMP" 2>/dev/null || [ "$(zcat "$TEMP" | tail -1)" != "-- Fi de la còpia" ]; then
    apunta "ERROR: $NOM ha arribat malmesa; es tornarà a provar d'aquí a una hora"
    exit 1
fi
mv "$TEMP" "$DESTI/$NOM"

# Esborrar les més antigues, deixant-ne les últimes $GUARDAR
ls -1t "$DESTI"/gymzero_*.sql.gz | tail -n +$((GUARDAR + 1)) | while read -r antiga; do
    rm -f "$antiga"
    apunta "Esborrada còpia antiga: $(basename "$antiga")"
done

apunta "OK: $NOM ($(du -h "$DESTI/$NOM" | cut -f1))"
