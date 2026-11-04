#!/bin/bash
set -e
cd "$(dirname "$0")"
DESTI="$HOME/gymzero-copies"
FITXER_CLAU="$HOME/.gymzero-copia.clau"

umask 077
mkdir -p "$DESTI"
chmod 700 "$DESTI"
cp baixa_copia.sh "$DESTI/baixa_copia.sh"
chmod 700 "$DESTI/baixa_copia.sh"

echo "Enganxa la clau del PC que surt al panell d'administració de gymzero.cat"
echo "(Administració › Còpies de seguretat › Generar la clau del PC) i prem Enter:"
read -rs CLAU
echo
if ! [[ "$CLAU" =~ ^[0-9a-f]{48}$ ]]; then
    echo "Aquesta clau no és correcta (han de ser 48 lletres i números). No s'ha canviat res."
    exit 1
fi
printf '%s\n' "$CLAU" > "$FITXER_CLAU"
chmod 600 "$FITXER_CLAU"

( crontab -l 2>/dev/null | grep -v 'gymzero-copies/baixa_copia.sh' || true
  echo "17 * * * * bash $DESTI/baixa_copia.sh"
  echo "@reboot sleep 120 && bash $DESTI/baixa_copia.sh"
) | crontab -

echo "Provant la primera descàrrega..."
if bash "$DESTI/baixa_copia.sh" && compgen -G "$DESTI/gymzero_*.sql.gz" > /dev/null; then
    echo "Fet. Còpies a $DESTI:"
    ls -1t "$DESTI"/gymzero_*.sql.gz | head -3
else
    echo "No ha anat bé. Mira què diu $DESTI/baixades.log:"
    tail -3 "$DESTI/baixades.log"
    exit 1
fi
