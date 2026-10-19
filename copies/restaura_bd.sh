#!/bin/bash
# GYM-ZERO · Restaurar una còpia de seguretat
#
# Ús:  bash restaura_bd.sh <fitxer.sql.gz> [base_de_dades]
#
# Per defecte restaura a una base de dades nova, gymzero_prova, per comprovar
# que la còpia funciona sense tocar la de veritat.
# Per recuperar la base de dades real s'ha d'indicar gymzero_db i confirmar-ho.

FITXER="$1"
BD="${2:-gymzero_prova}"
USUARI_BD="root"
CREDENCIALS="$HOME/.gymzero-bd.cnf"

if [ -x /opt/lampp/bin/mysql ]; then
    MYSQL=/opt/lampp/bin/mysql
else
    MYSQL=mysql
fi

OPCIONS=()
if [ -f "$CREDENCIALS" ]; then
    OPCIONS+=(--defaults-extra-file="$CREDENCIALS")
fi
OPCIONS+=(-u "$USUARI_BD" --default-character-set=utf8mb4)

if [ -z "$FITXER" ] || [ ! -f "$FITXER" ]; then
    echo "Ús: bash restaura_bd.sh <fitxer.sql.gz> [base_de_dades]"
    echo "Còpies disponibles:"
    ls -1t "$HOME"/gymzero-copies/*.sql.gz 2>/dev/null | head -5
    exit 1
fi

if ! gzip -t "$FITXER"; then
    echo "El fitxer està malmès, no es pot restaurar."
    exit 1
fi

if [ "$BD" = "gymzero_db" ]; then
    echo "ATENCIÓ: se substituiran TOTES les dades actuals de gymzero_db per les de la còpia."
    read -r -p "Escriu SI per continuar: " resposta
    if [ "$resposta" != "SI" ]; then
        echo "Cancel·lat."
        exit 1
    fi
fi

"$MYSQL" "${OPCIONS[@]}" -e "CREATE DATABASE IF NOT EXISTS \`$BD\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" || exit 1

if zcat "$FITXER" | "$MYSQL" "${OPCIONS[@]}" "$BD"; then
    echo "Restaurada $(basename "$FITXER") a la base de dades $BD."
    echo "Files per taula:"
    "$MYSQL" "${OPCIONS[@]}" "$BD" -e \
        "SELECT 'usuaris' AS taula, COUNT(*) AS files FROM usuaris
         UNION ALL SELECT 'classes', COUNT(*) FROM classes
         UNION ALL SELECT 'reserves', COUNT(*) FROM reserves
         UNION ALL SELECT 'series', COUNT(*) FROM series
         UNION ALL SELECT 'repeticions', COUNT(*) FROM repeticions"
else
    echo "ERROR: no s'ha pogut restaurar."
    exit 1
fi
