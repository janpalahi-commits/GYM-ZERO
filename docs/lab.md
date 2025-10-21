# LAB — Centre científic (simulat)

## Objectiu
Demostrar pipeline de dades: captura (simulada) → ingesta → emmagatzematge → gràfics → informe.

## Esquema de dades
- `members`, `tests`, `metrics` (clau-valor amb unitats i timestamps)

## Flux
1. Generar CSV (simulador)
2. Importar al panel → guardar a DB
3. Mostrar gràfiques (Chart.js)
4. Exportar informe (HTML/PDF)

## Casos d’ús LAB
- Registrar consentiment
- Executar proves i importar dades
- Veure dashboard per atleta
- Generar informe
