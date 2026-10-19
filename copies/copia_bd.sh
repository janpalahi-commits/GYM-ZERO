#!/bin/bash
# GYM-ZERO · Còpia de seguretat de la base de dades
#
# Fa una còpia de tota la base de dades (estructura + dades), la comprimeix,
# comprova que el fitxer està bé, esborra les còpies antigues i avisa els
# administradors amb una notificació a la web.
#
# Ús:    bash copia_bd.sh
# Cron:  0 20 * * * bash /home/USUARI/gymzero-copies/copia_bd.sh   (cada dia a les 20:00)

# --- Configuració -----------------------------------------------------
BD="gymzero_db"
USUARI_BD="root"                     # a l'XAMPP, root sense contrasenya
DESTI="$HOME/gymzero-copies"         # fora de htdocs: les còpies no han de ser accessibles des de la web
GUARDAR=14                           # quantes còpies es guarden (les més antigues s'esborren)

# Si hi ha contrasenya, es posa en aquest fitxer (no dins del script):
#   [client]
#   password=la-contrasenya
# i es protegeix amb: chmod 600 ~/.gymzero-bd.cnf
CREDENCIALS="$HOME/.gymzero-bd.cnf"

# Programes de l'XAMPP si existeixen; si no, els del sistema
if [ -x /opt/lampp/bin/mysqldump ]; then
    MYSQLDUMP=/opt/lampp/bin/mysqldump
    MYSQL=/opt/lampp/bin/mysql
else
    MYSQLDUMP=mysqldump
    MYSQL=mysql
fi
# ----------------------------------------------------------------------

OPCIONS=()
if [ -f "$CREDENCIALS" ]; then
    OPCIONS+=(--defaults-extra-file="$CREDENCIALS")
fi
OPCIONS+=(-u "$USUARI_BD" --default-character-set=utf8mb4)

mkdir -p "$DESTI"
FITXER="$DESTI/${BD}_$(date +%Y-%m-%d_%H%M%S).sql.gz"
LOG="$DESTI/copies.log"

apunta() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') $1" | tee -a "$LOG"
}

# Deixa un avís a l'àrea de tots els administradors actius
avisa_admins() {
    local missatge="${1//\'/\'\'}"   # escapa les cometes per a SQL
    "$MYSQL" "${OPCIONS[@]}" "$BD" -e \
        "INSERT INTO notificacions (usuari_id, tipus, missatge)
         SELECT id, 'sistema', '$missatge' FROM usuaris WHERE rol = 'admin' AND actiu = 1" 2>/dev/null
}

# 1. Fer la còpia
#    --single-transaction: còpia coherent sense aturar la web (InnoDB)
#    No es copien procediments ni funcions (--routines) perquè el projecte no en té
#    i a algunes instal·lacions d'XAMPP actualitzades fa fallar mysqldump.
ERRORS=$(mktemp)
"$MYSQLDUMP" "${OPCIONS[@]}" --single-transaction --triggers "$BD" 2>"$ERRORS" | gzip > "$FITXER"

# PIPESTATUS[0] és el resultat de mysqldump (el de gzip seria el [1])
if [ "${PIPESTATUS[0]}" -ne 0 ]; then
    rm -f "$FITXER"
    apunta "ERROR: no s'ha pogut fer la còpia de $BD. Motiu:"
    tee -a "$LOG" < "$ERRORS"
    rm -f "$ERRORS"
    avisa_admins "ERROR: la còpia de seguretat de la base de dades ha fallat. Mira el fitxer copies.log."
    exit 1
fi

rm -f "$ERRORS"

# 2. Comprovar que el fitxer comprimit està bé i que la còpia és sencera
if ! gzip -t "$FITXER" || ! zcat "$FITXER" | tail -1 | grep -q "Dump completed"; then
    rm -f "$FITXER"
    apunta "ERROR: la còpia de $BD ha quedat incompleta"
    avisa_admins "ERROR: la còpia de seguretat ha quedat incompleta. Mira el fitxer copies.log."
    exit 1
fi

MIDA=$(du -h "$FITXER" | cut -f1)
TAULES=$(zcat "$FITXER" | grep -c "^CREATE TABLE")
apunta "OK: $(basename "$FITXER") ($MIDA, $TAULES taules)"

# 3. Esborrar les còpies més antigues, deixant-ne les últimes $GUARDAR
ls -1t "$DESTI"/"${BD}"_*.sql.gz | tail -n +$((GUARDAR + 1)) | while read -r antiga; do
    rm -f "$antiga"
    apunta "Esborrada còpia antiga: $(basename "$antiga")"
done

avisa_admins "Còpia de seguretat feta: $(basename "$FITXER") ($MIDA)."
exit 0
