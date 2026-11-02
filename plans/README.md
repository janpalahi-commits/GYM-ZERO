# Plans personalitzats (tarifes Gimnàs + pla i Gimnàs + pla + seguiment)

Eina en Python que fa el PDF del pla d'entrenament i alimentació d'un client a partir del qüestionari que omple a la web.
Ve del meu projecte APP GYM, adaptada a Gym Zero: el qüestionari de la web és el mateix formulari d'APP GYM (v6, en 4 idiomes), els exercicis són els de les nostres màquines i el PDF surt en català.

Les respostes porten els mateixos noms que les columnes de la fulla de Google d'APP GYM (el títol de cada pregunta en castellà). Per això l'eina també llegeix una fila d'aquella fulla.

## Com funciona

1. El client (amb tarifa de 80 € o 110 €) omple el qüestionari a **La meva àrea > El meu pla**.
2. L'entrenador obre **Administració > Plans i seguiment**, entra a la fitxa del client i clica **Descarregar les respostes (JSON)**.
3. A l'ordinador del gimnàs:
   ```
   pip install reportlab
   python3 fes_pla.py questionari_2.json
   ```
   Surt `pla_<nom>_<data>.pdf` i, a la pantalla, la rutina per entrar-la a la llibreta del client.
4. L'entrenador revisa el PDF i el puja a la fitxa del client (**Pujar el pla**). Al client li surt un avís i el descarrega des de la seva àrea.

El PDF es fa a l'ordinador del gimnàs i no al servidor perquè Dinahosting només executa PHP.

## Fitxers

| Fitxer | Què és |
|---|---|
| `fes_pla.py` | Llegeix el JSON, fa els càlculs i munta el PDF. Si hi ha alguna cosa que l'entrenador ha de mirar a mà (relació difícil amb el menjar, menor d'edat, esport a part, pla demanat en un altre idioma), ho diu a la pantalla |
| `diseno_pdf_moderno.py` | El disseny del PDF (targetes, icones, escala d'esforç, calendari), igual que a APP GYM |
| `exemple_questionari.json` | Respostes d'un client inventat (el Marc de les dades de prova) per provar l'eina |

## Què calcula (mètode d'APP GYM)

- **Seguretat primer:** si el client ha marcat dolor al pit, problemes de cor, tensió alta, embaràs, part recent o lactància, no fa el pla fins que hi ha el vistiplau del metge (`--vistiplau`). Amb una molèstia, canvia els exercicis que carreguen aquella zona.
- **Calories:** metabolisme basal de Mifflin-St Jeor × factor d'activitat (dies d'entrenament + feina i passos), i un ajust segons l'objectiu i el ritme (també recomposició: perdre greix i guanyar múscul alhora).
- **Macros:** proteïna d'1,6 a 2,0 g per kg, greix 0,8 g per kg i la resta hidrats. Aigua: 35 ml per kg més el que es perd entrenant.
- **Rutina:** els dies depenen del nivell i del son, no només dels que pot venir. De 2 a 6 dies (cos sencer, tors i cames, empenta, estirada i cames). Les zones que vol millorar tenen una sèrie més, i si són grups grans van primer. Els exercicis que diu que odia es canvien per una alternativa.
- **Menjar:** àpats repartits des de l'hora que es lleva, amb grams i dues alternatives per a cada aliment, tenint en compte la dieta, les al·lèrgies i el que no menja.

## Provar-ho

```
python3 fes_pla.py exemple_questionari.json
```

Les fonts són les Liberation Sans de Linux (`/usr/share/fonts/truetype/liberation/`). En un altre sistema, instal·la-les o canvia la ruta `F` al principi de `diseno_pdf_moderno.py`.

Les respostes del qüestionari són dades de salut: el JSON i el PDF no es guarden a cap carpeta compartida ni es pugen a GitHub. Quan el pla és a la web, esborra'ls de l'ordinador.
