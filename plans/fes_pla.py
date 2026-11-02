"""
GYM-ZERO · Fa el PDF del pla personalitzat d'un client a partir del seu qüestionari.

1. A la fitxa del client (Administració > Plans i seguiment) descarrega les respostes en JSON.
2. python3 fes_pla.py questionari_2.json
3. Revisa el PDF i puja'l a la mateixa fitxa. El client el descarrega des de La meva àrea.

Les respostes tenen els mateixos noms que les columnes de la fulla de Google del formulari d'APP GYM
(el títol de cada pregunta en castellà), així que també serveix una fila d'aquella fulla.

Segueix el mètode d'APP GYM (càlculs F01-F08 i F14, regles de seguretat i divisions PR-01 a PR-05)
i fa servir el disseny de diseno_pdf_moderno.py. Els exercicis són els de les màquines de Gym Zero,
cadascun amb dues alternatives. Al final escriu la rutina per entrar-la a la llibreta del client.
"""
import argparse
import json
import re
import sys
import unicodedata
from datetime import date, datetime
from pathlib import Path

from reportlab.platypus import PageBreak, KeepTogether

from diseno_pdf_moderno import (Titulo, Fila, Mosaico, Tarjeta, EscalaEsfuerzo, Semana, Ejercicio, Comida,
                                documento, P, esp, puntos, VERDE, VERDE_CLARO, NARANJA, NARANJA_CLARO,
                                AZUL, AZUL_CLARO, ROJO, GRIS_CLARO)

IDIOMA = "ca"

# ---------------------------------------------------------------- exercicis
# clau: (nom, màquina amb polsera o None, grup, molèsties que l'eviten, [2 alternatives], truc, què fer si hi ha molèstia)
EXERCICIS = {
    "press":      ("Press de banca", "PRESS-01", "pit", {"espatlla"}, ["Contractora de pit", "Flexions"],
                   "Baixa fins a tocar el pit, sense rebotar.", "Press amb manuelles, agafada neutra"),
    "contractora": ("Contractora de pit", "CONTR-01", "pit", {"espatlla"}, ["Obertures amb manuelles", "Press amb manuelles"],
                    "Colzes una mica doblegats tota l'estona.", "Press amb manuelles, agafada neutra"),
    "militar":    ("Press militar", "MILITAR-01", "espatlla", {"espatlla", "coll"}, ["Press d'espatlla amb manuelles", "Elevacions laterals"],
                   "Abdomen fort i sense arquejar l'esquena.", "Face pull en politja"),
    "laterals":   ("Elevacions laterals", "LATERAL-01", "espatlla", {"espatlla"}, ["Laterals amb manuelles", "Laterals en politja"],
                   "Puja fins a l'altura de les espatlles, no més.", "Face pull en politja"),
    "rem":        ("Rem assegut", "REM-01", "esquena", set(), ["Rem amb manuella", "Rem en politja baixa"],
                   "Porta els colzes enrere i junta les escàpules.", None),
    "jalo":       ("Jaló al pit", "JALO-01", "esquena", {"espatlla"}, ["Dominades amb ajuda", "Rem amb manuella"],
                   "Baixa la barra fins a la barbeta, pit amunt.", "Rem en politja baixa"),
    "curl":       ("Curl de bíceps", "CURL-01", "bíceps", {"colze_canell"}, ["Curl amb manuelles", "Curl en politja"],
                   "Colzes quiets al costat del cos.", "Curl martell amb manuelles"),
    "triceps":    ("Extensió de tríceps", "TRICEP-01", "tríceps", {"colze_canell"}, ["Extensió en politja", "Fons a la banqueta"],
                   "Només es mou l'avantbraç.", "Extensió en politja amb corda"),
    "sentadilla": ("Sentadilla", "SQUAT-01", "cames", {"genoll", "lumbar", "maluc"}, ["Premsa de cames", "Sentadilla amb manuella"],
                   "Genolls en la direcció de les puntes dels peus.", "Pont de glutis a terra"),
    "extq":       ("Extensió de quàdriceps", "EXTQ-01", "cames", {"genoll"}, ["Sentadilla a la paret", "Pujades a un banc"],
                   "Puja en 1 segon i baixa en 3.", "Curl femoral estirat"),
    "femoral":    ("Curl femoral assegut", "FEMS-01", "cames", set(), ["Curl femoral estirat", "Pont de glutis amb una cama"],
                   "No aixequis el maluc del seient.", None),
    "hip":        ("Hip thrust", "HIP-01", "glutis", {"lumbar"}, ["Pont de glutis a terra", "Abductora"],
                   "Aguanta 1 segon a dalt apretant els glutis.", "Abductora"),
    "talons":     ("Elevació de talons", "TALOD-01", "cames", {"turmell"}, ["Talons asseguts", "Talons en un esglaó"],
                   "Baixa a poc a poc fins a estirar del tot.", "Talons asseguts"),
    "crunch":     ("Crunch en màquina", "CRUNCH-01", "abdomen", {"lumbar", "coll"}, ["Planxa", "Crunch a terra"],
                   "Treu l'aire mentre puges.", "Planxa"),
}

# Divisions segons els dies (PR-01 a PR-05), amb els dies de la setmana on toca (1 = dilluns)
DIVISIONS = {
    2: ("Cos sencer A i B", [1, 4], [("Cos sencer A", ["sentadilla", "press", "rem", "laterals", "crunch"]),
                                     ("Cos sencer B", ["hip", "jalo", "militar", "femoral", "curl", "triceps"])]),
    3: ("Cos sencer A, B i C", [1, 3, 5], [("Cos sencer A", ["sentadilla", "press", "rem", "laterals", "crunch"]),
                                         ("Cos sencer B", ["hip", "jalo", "militar", "femoral", "curl", "triceps"]),
                                         ("Cos sencer C", ["extq", "contractora", "rem", "talons", "laterals", "crunch"])]),
    4: ("Tors i cames, dues vegades", [1, 2, 4, 5], [("Tors A", ["press", "rem", "militar", "jalo", "curl", "triceps"]),
                                                    ("Cames A", ["sentadilla", "femoral", "extq", "talons", "crunch"]),
                                                    ("Tors B", ["contractora", "jalo", "laterals", "rem", "triceps", "curl"]),
                                                    ("Cames B", ["hip", "extq", "femoral", "talons", "crunch"])]),
    5: ("Tors, cames, empenta, estirada i cames", [1, 2, 3, 5, 6],
        [("Tors", ["press", "rem", "militar", "jalo", "curl", "triceps"]),
         ("Cames (quàdriceps)", ["sentadilla", "extq", "femoral", "talons"]),
         ("Empenta", ["press", "contractora", "militar", "laterals", "triceps"]),
         ("Estirada", ["jalo", "rem", "curl", "crunch"]),
         ("Cames (glutis)", ["hip", "femoral", "extq", "talons", "crunch"])]),
    6: ("Empenta, estirada i cames, dues vegades", [1, 2, 3, 4, 5, 6],
        [("Empenta A", ["press", "militar", "contractora", "laterals", "triceps"]),
         ("Estirada A", ["jalo", "rem", "curl", "crunch"]),
         ("Cames A", ["sentadilla", "extq", "femoral", "talons"]),
         ("Empenta B", ["contractora", "press", "laterals", "triceps"]),
         ("Estirada B", ["rem", "jalo", "curl", "crunch"]),
         ("Cames B", ["hip", "femoral", "extq", "talons"])]),
}

# Zones que vol millorar: tenen una sèrie més (PR-06) i, si són grups grans, van primer (PR-11)
ZONES = {"Glúteo": {"glutis"}, "Piernas": {"cames"}, "Pecho": {"pit"}, "Espalda": {"esquena"}, "Hombros": {"espatlla"},
         "Brazos": {"bíceps", "tríceps"}, "Abdomen": {"abdomen"}}
ODIATS = {"Sentadilla": {"sentadilla"}, "Press banca": {"press"}, "Remo": {"rem"}, "Press militar": {"militar"},
          "Hip thrust": {"hip"}, "Ejercicios de brazos": {"curl", "triceps"}, "Abdominales": {"crunch"}}
MOLESTIES = {"espatlla": "l'espatlla", "colze_canell": "el colze o el canell", "lumbar": "l'esquena", "genoll": "el genoll",
             "turmell": "el turmell", "maluc": "el maluc", "coll": "el coll"}
OBJECTIUS = {"perdre_greix": "Perdre greix", "guanyar_muscul": "Guanyar múscul", "recomposicio": "Perdre greix i guanyar múscul",
             "forca": "Guanyar força", "salut": "Forma i salut"}

# ---------------------------------------------------------------- aliments (per 100 g; l'ou, per unitat)
# nom, proteïna, hidrats, greix, kcal, què el treu (dieta o al·lèrgia)
PROTEINES = [
    ("pit de pollastre (cru)", 23, 0, 2, 110, {"vegetariana", "vegana"}),
    ("tonyina al natural", 25, 0, 1, 110, {"vegetariana", "vegana", "peix"}),
    ("skyr o iogurt proteic", 10, 4, 0.2, 60, {"vegana", "lactosa"}),
    ("tofu ferm", 13, 2, 7, 120, {"soja"}),
    ("llenties cuites", 9, 20, 0.4, 116, {"llegums"}),
]
HIDRATS = [
    ("arròs (pes en cru)", 7, 78, 1, 350, set()),
    ("pa", 9, 48, 3, 250, {"gluten"}),
    ("flocs de civada", 13, 60, 7, 370, {"gluten"}),
    ("patata", 2, 17, 0.1, 77, set()),
]


def ascii(text):
    return re.sub(r"[^A-Za-z0-9]+", "_", unicodedata.normalize("NFKD", text).encode("ascii", "ignore").decode()).strip("_").lower()


def arrodoneix(g, pas=5):
    return int(max(pas, round(g / pas) * pas))


# ---------------------------------------------------------------- respostes del qüestionari
def llista(v):
    """A la web les respostes múltiples són una llista; a la fulla de Google, un text separat per comes."""
    return list(v) if isinstance(v, list) else [x.strip() for x in str(v or "").split(",") if x.strip()]


def hora(v):
    """'07:30 (mañana)' -> (7, 30). Si no n'hi ha cap ('No trabajo...', '> 02:00'), torna None."""
    m = re.match(r"(\d{2}):(\d{2})", str(v or ""))
    return (int(m.group(1)), int(m.group(2))) if m else None


def llegeix(f):
    """Passa les respostes del formulari (columnes en castellà) a les dades que fa servir el pla."""
    def tria(columna, opcions, si_no=None):
        return opcions.get(f.get(columna, ""), si_no)

    naixement = datetime.strptime(f["Fecha de nacimiento"], "%d/%m/%Y").date()
    avui = date.today()
    r = dict(
        sexe=tria("Sexo", {"Hombre": "home", "Mujer": "dona"}),
        edat=avui.year - naixement.year - ((avui.month, avui.day) < (naixement.month, naixement.day)),
        alcada_cm=float(f["Altura (cm)"]),
        pes_kg=float(f["Peso actual (kg)"]),
        nivell=tria("¿Cuánto tiempo llevas entrenando con pesas de forma constante?",
                    {"Nunca he entrenado": "mai", "Menos de 6 meses": "menys_6m", "Entre 6 meses y 2 años": "6m_2a",
                     "Más de 2 años": "mes_2a"}, "mai"),
        dies=int(str(f["¿Cuántos días a la semana puedes entrenar de verdad?"])[0]),
        minuts=tria("¿Cuánto tiempo tienes por sesión?",
                    {"30-45 min": "30_45", "45-60 min": "45_60", "60-90 min": "60_90", "Más de 90 min": "mes_90"}, "45_60"),
        hora_entreno=hora(f.get("¿A qué hora sueles empezar a entrenar?")) or (18, 0),
        objectiu=tria("¿Cuál es tu objetivo principal?",
                      {"Perder grasa": "perdre_greix", "Ganar músculo": "guanyar_muscul",
                       "Perder grasa y ganar músculo a la vez": "recomposicio", "Ganar fuerza": "forca",
                       "Salud y mantenerme en forma": "salut"}, "salut"),
        hora_llevar=hora(f.get("¿A qué hora te levantas normalmente entre semana?")) or (7, 0),
        apats=min(int(str(f.get("¿Cuántas comidas al día te verías capaz de hacer?") or "4")[0]), 5),
        idioma=f.get("¿En qué idioma quieres tu plan?", ""),
    )
    ritme = str(f.get("¿A qué ritmo quieres avanzar?", ""))
    r["ritme"] = "suau" if ritme.startswith("Tranquilo") else "rapid" if ritme.startswith("Rápido") else "normal"

    # Activitat fora del gimnàs (F05): la feina, i si camina molt puja un graó
    r["activitat"] = tria("¿Cómo es tu trabajo o estudio?",
                          {"Sentado casi todo el día": "assegut", "De pie o caminando": "dempeus",
                           "Físico (cargo peso, obra, almacén…)": "fisic"}, "assegut")
    if f.get("¿Cuántos pasos haces al día más o menos?") in ("8.000-12.000", "Más de 12.000") and r["activitat"] != "fisic":
        r["activitat"] = "actiu"

    # Son: hores entre que se'n va a dormir i es lleva, i com diu que dorm
    dormir = hora(f.get("¿A qué hora te acuestas normalmente entre semana?"))
    if dormir:
        hores = ((r["hora_llevar"][0] * 60 + r["hora_llevar"][1]) - (dormir[0] * 60 + dormir[1])) % (24 * 60) / 60
    else:
        hores = 5  # se'n va a dormir després de les 2
    r["poc_son"] = hores < 6 or f.get("¿Cómo duermes?") == "Mal, me despierto cansado"

    r["zones"] = set().union(*[ZONES.get(z, set()) for z in llista(f.get("¿Qué zonas quieres mejorar más?"))])
    odiats = llista(f.get("¿Qué ejercicios odias o no quieres hacer?"))
    r["odia"] = set().union(*[ODIATS.get(e, set()) for e in odiats])
    r["excloure"] = " ".join([e for e in odiats if e not in ODIATS] + [str(f.get("¿Qué ejercicios o movimientos te duelen?", ""))]).lower()

    lesions = {"Hombro": "espatlla", "Codo": "colze_canell", "Muñeca": "colze_canell", "Cuello": "coll",
               "Espalda baja (lumbar)": "lumbar", "Cadera": "maluc", "Rodilla": "genoll", "Tobillo": "turmell"}
    r["lesions"] = sorted({lesions[x] for x in llista(f.get("¿Dónde?")) if x in lesions})
    altres_molesties = [x for x in llista(f.get("¿Dónde?")) if x not in lesions]

    # Dieta: el que no pot o no vol menjar treu aliments de la llista
    dieta = f.get("¿Sigues alguna dieta especial?", "Ninguna")
    treure = {"Vegetariana": {"vegetariana"}, "Vegana": {"vegetariana", "vegana"}}.get(dieta, set())
    etiquetes = {"Lactosa": "lactosa", "Leche o yogur": "lactosa", "Gluten": "gluten", "Avena": "gluten", "Pescado": "peix",
                 "Marisco": "peix", "Huevo": "ou", "Huevos": "ou", "Legumbres": "llegums", "Frutos secos": "fruits_secs"}
    for x in llista(f.get("¿Tienes alguna alergia o intolerancia alimentaria?")) + llista(f.get("¿Qué alimentos NO comes o no te gustan nada?")):
        if x in etiquetes:
            treure.add(etiquetes[x])
    r["treure"] = treure

    # Seguretat (punt 1 del mètode): sense el vistiplau del metge no es fa el pla
    r["bloquejants"] = []
    if f.get("¿Algún médico te ha dicho que tienes un problema de corazón o tensión alta?") == "Sí":
        r["bloquejants"].append("un problema de cor o pressió alta")
    if f.get("¿Sientes dolor en el pecho, mareos o pierdes el conocimiento al hacer esfuerzo?") == "Sí":
        r["bloquejants"].append("dolor al pit o marejos fent esforç")
    if f.get("¿Estás embarazada, has dado a luz en el último año o das el pecho?", "No") not in ("", "No"):
        r["bloquejants"].append("embaràs, part recent o lactància")
    r["malalties"] = [m for m in llista(f.get("¿Tienes alguna enfermedad diagnosticada?")) if m != "Ninguna"]
    r["medicacio"] = str(f.get("¿Tomas alguna medicación de forma habitual? ¿Cuál?", "")).strip()

    # Coses que ha de saber l'entrenador però que no van al PDF
    r["per_entrenador"] = []
    if f.get("¿Tienes o has tenido una relación difícil con la comida? (atracones, comer muy poco a propósito, compensar con ejercicio)") == "Sí":
        r["per_entrenador"].append("Té o ha tingut una relació difícil amb el menjar: revisa la dieta i no la facis estricta.")
    if f.get("Si eres menor de 18 años: ¿tu padre, madre o tutor está de acuerdo?") == "Sí, está de acuerdo":
        r["per_entrenador"].append("És menor d'edat (té el permís del pare, la mare o el tutor).")
    if altres_molesties:
        r["per_entrenador"].append("Té molèsties a " + ", ".join(altres_molesties) + ": l'eina no les té en compte, revisa els exercicis.")
    if r["idioma"] and r["idioma"] != "Català":
        r["per_entrenador"].append(f"Ha demanat el pla en {r['idioma']}: aquesta eina el fa en català.")
    if f.get("Aparte del gimnasio, ¿practicas algún deporte o actividad física?") == "Sí":
        r["per_entrenador"].append("Fa esport a part del gimnàs: mira els dies i que no es trepitgin amb la rutina.")
    return r


# ---------------------------------------------------------------- càlculs (fórmules 4A del mètode)
def calculs(r, dies):
    pes, alcada, edat = float(r["pes_kg"]), float(r["alcada_cm"]), int(r["edat"])
    s = {"home": 5, "dona": -161}.get(r["sexe"], -78)
    bmr = 10 * pes + 6.25 * alcada - 5 * edat + s                                            # F03
    punts = dies + {"assegut": 0, "dempeus": 1, "actiu": 2, "fisic": 3}[r["activitat"]]
    fa = 1.2 if punts == 0 else 1.375 if punts <= 3 else 1.55 if punts <= 5 else 1.725 if punts <= 7 else 1.9   # F05
    tdee = bmr * fa                                                                           # F06
    ritme = r["ritme"]
    if r["objectiu"] == "perdre_greix":
        ajust = {"suau": -0.10, "normal": -0.20, "rapid": -0.25}[ritme]
    elif r["objectiu"] == "guanyar_muscul":
        sostre = {"mes_2a": 0.05, "6m_2a": 0.10}.get(r["nivell"], 0.15)                       # com més nivell, menys excedent
        ajust = min({"suau": 0.05, "normal": 0.10, "rapid": 0.15}[ritme], sostre)
    elif r["objectiu"] == "recomposicio":
        ajust = {"suau": 0, "normal": -0.05, "rapid": -0.10}[ritme]
    else:
        ajust = 0.05 if r["objectiu"] == "forca" else 0
    kcal = max(tdee * (1 + ajust), bmr)                                                       # F07
    imc = pes / (alcada / 100) ** 2                                                           # F01
    pes_ref = 25 * (alcada / 100) ** 2 if imc > 30 else pes                                   # amb obesitat, proteïna sobre un pes de referència
    g_prot = {"perdre_greix": 2.0, "recomposicio": 2.0, "guanyar_muscul": 1.8, "forca": 1.8}.get(r["objectiu"], 1.6)
    prot = g_prot * pes_ref                                                                   # F08
    greix = 0.8 * pes
    hidr = max((kcal - 4 * prot - 9 * greix) / 4, 0)
    hores = {"30_45": 0.75, "45_60": 0.9, "60_90": 1.25, "mes_90": 1.6}[r["minuts"]]
    aigua = 35 * pes + 500 * hores                                                            # F14
    return dict(bmr=bmr, tdee=tdee, kcal=round(kcal, -1), prot=round(prot), greix=round(greix), hidr=round(hidr),
                aigua=round(aigua, -2), imc=imc)


# ---------------------------------------------------------------- rutina
def dies_efectius(r):
    """Els dies que pot venir no són els que ha de venir: el nivell i el son també manen (punt 3 del mètode)."""
    dies = int(r["dies"])
    dies = min(dies, {"mai": 3, "menys_6m": 4}.get(r["nivell"], 6))
    if r["poc_son"]:
        dies = min(dies, 4)
    if dies == 6 and r["nivell"] != "mes_2a":
        dies = 5
    return dies


def rutina(r):
    dies = dies_efectius(r)
    nom_divisio, dies_setmana, sessions = DIVISIONS[dies]
    max_ex = {"30_45": 4, "45_60": 5, "60_90": 6, "mes_90": 7}[r["minuts"]]                # PR-12
    mes_series = r["zones"]
    primer = r["zones"] & {"pit", "esquena", "cames", "glutis"}      # els braços i l'abdomen no passen davant dels bàsics
    lesions = set(r["lesions"])
    excloure = r["excloure"]
    principiant = r["nivell"] in ("mai", "menys_6m")
    sortida = []
    for (titol, claus), dia in zip(sessions, dies_setmana):
        # PR-11: primer la zona que vol prioritzar
        claus = sorted(claus, key=lambda k: EXERCICIS[k][2] not in primer)[:max_ex]
        llista = []
        for k in claus:
            nom, maquina, grup, evitar, alts, truc, segur = EXERCICIS[k]
            nota = None
            if evitar & lesions:
                # Un exercici que no carregui la zona que fa mal (ja no és de màquina amb polsera)
                nota = "Canviat per cuidar " + MOLESTIES[sorted(evitar & lesions)[0]]
                nom, maquina, truc = segur, None, "Sense dolor. Si fa mal, para."
            elif k in r["odia"] or re.search(r"\b" + re.escape(nom.lower().split()[0]) + r"\b", excloure):
                nota = "Canviat: ens vas dir que no el volies fer"
                nom, maquina, alts = alts[0], None, [alts[1], nom]
            series = 3 + (1 if grup in mes_series and not principiant else 0)                 # PR-06
            reps = "10-12" if principiant else "8-12" if r["nivell"] == "6m_2a" else "6-10"
            if grup in ("abdomen", "bíceps", "tríceps") or k in ("laterals", "talons", "extq"):
                reps = "12-15"
            llista.append(dict(n=nom, s=series, r=reps, nota=nota, truco=truc, alt=alts, maquina=maquina))
        sortida.append((dia, titol, llista))
    return nom_divisio, sortida


# ---------------------------------------------------------------- menjar
def aliments_permesos(aliments, r):
    return [a for a in aliments if not (a[5] & r["treure"])]


def apats(r, n):
    """Hores dels àpats a partir de quan es lleva, i quin és el de després d'entrenar."""
    h, m = r["hora_llevar"]
    he, me = r["hora_entreno"]
    inici, entreno = h * 60 + m + 30, he * 60 + me
    pas = (13 * 60) // max(n - 1, 1)                     # repartits en unes 13 hores
    hores = [inici + i * pas for i in range(n)]
    # L'àpat que arriba entre 45 minuts i 3 hores després d'acabar d'entrenar (si n'hi ha cap)
    despres = next((i for i, t in enumerate(hores) if entreno + 60 + 45 <= t <= entreno + 60 + 180), None)
    noms = ["Esmorzar", "Mig matí", "Dinar", "Berenar", "Sopar", "Ressopó"]
    noms = {2: ["Dinar", "Sopar"], 3: ["Esmorzar", "Dinar", "Sopar"], 4: ["Esmorzar", "Dinar", "Berenar", "Sopar"]}.get(n, noms[:n])
    return [(f"{t // 60 % 24:02d}:{t % 60:02d}", noms[i], i == despres) for i, t in enumerate(hores)]


def targetes_menjar(r, c):
    n = int(r["apats"])
    prots = aliments_permesos(PROTEINES, r)
    hidrs = aliments_permesos(HIDRATS, r)
    p_apat, h_apat = c["prot"] / n, c["hidr"] / n
    sortida = []
    for hora, nom, despres in apats(r, n):
        items, alts = [], []
        if prots:
            items.append(f"<b>{arrodoneix(p_apat / prots[0][1] * 100)} g</b> de {prots[0][0]}")
            alts += [f"{arrodoneix(p_apat / a[1] * 100)} g de {a[0]}" for a in prots[1:3]]
        hidr = h_apat * (1.3 if despres else 0.9)
        if hidrs:
            items.append(f"<b>{arrodoneix(hidr / hidrs[0][2] * 100)} g</b> de {hidrs[0][0]}")
            alts += [f"{arrodoneix(hidr / a[2] * 100)} g de {a[0]}" for a in hidrs[1:3]]
        items.append("Verdura o fruita, la que vulguis" if nom != "Mig matí" else "Una peça de fruita")
        items.append("1 cullerada d'oli d'oliva (10 g)")
        titol = nom + (" · després d'entrenar" if despres else "")
        sortida.append(Comida(hora, titol, items, alts=alts, idioma=IDIOMA))
    return sortida


# ---------------------------------------------------------------- seguretat (punt 1 del mètode)
def avisos_salut(r):
    avisos = []
    if r["malalties"] or r["medicacio"].lower() not in ("", "cap", "ninguna", "no", "none", "aucun", "aucune"):
        avisos.append("Ens has dit que tens alguna malaltia o que prens medicació: la dieta és una pauta general. "
                      "Comenta el pla amb el teu metge abans de començar.")
    for l in r["lesions"]:
        zona = MOLESTIES[l]
        avisos.append(f"Molèstia {'al ' + zona[3:] if zona.startswith('el ') else 'a ' + zona}: hem triat exercicis que no la carreguen. Si fa mal, para i ho mirem. Si dura, ves al fisio.")
    return r["bloquejants"], avisos


# ---------------------------------------------------------------- PDF
def fes_pdf(dades, sortida, vistiplau=False):
    r, nom = llegeix(dades["respostes"]), dades["client"]
    bloquejants, avisos = avisos_salut(r)
    if bloquejants and not vistiplau:
        sys.exit(f"No es fa el pla de {nom}: ha marcat {', '.join(bloquejants)}. "
                 "Primer cal el vistiplau del metge. Quan el tingui, torna-ho a executar amb --vistiplau.")
    dies = dies_efectius(r)
    c = calculs(r, dies)
    divisio, sessions = rutina(r)
    avui = date.today()

    doc = documento(str(sortida), nom, IDIOMA, titulo_pdf=f"Pla de {nom} · Gym Zero")
    h = [Titulo(f"El teu pla, {nom}", "pesa", f"Gym Zero · {avui.strftime('%d/%m/%Y')} · Fase 1"), esp(6),
         Fila([Mosaico("fuego", OBJECTIUS[r["objectiu"]], "objectiu"),
               Mosaico("calendario", f"{dies} dies", "a la setmana"),
               Mosaico("plato", f"{c['kcal']:.0f} kcal", f"{c['prot']} g de proteïna al dia", NARANJA, NARANJA_CLARO)]),
         esp(10),
         Fila([Mosaico("gota", f"{c['aigua'] / 1000:.1f} l", "d'aigua al dia", AZUL, AZUL_CLARO),
               Mosaico("luna", "7-9 h", "de son", AZUL, AZUL_CLARO),
               Mosaico("grafica", "Cada 2 setmanes", "revisió amb l'entrenador")]),
         esp(16)]
    if avisos:
        h.append(Tarjeta([P(a) for a in avisos], fondo=NARANJA_CLARO, acento=NARANJA, icono_n="aviso"))
        h.append(esp(12))

    h.append(Titulo("Per què el teu pla és així", "check"))
    motius = [f"Pots venir {r['dies']} dies, però amb el teu nivell i el teu descans en farem <b>{dies}</b>: així recuperes bé."
              if int(r["dies"]) != dies else f"Vens <b>{dies} dies</b>: fem {divisio[0].lower() + divisio[1:]}.",
              "Cada dia comença per les zones que vols millorar." if r["zones"] else "Treballem tot el cos per igual.",
              f"Gastes unes <b>{c['tdee']:.0f} kcal</b> al dia. Per a «{OBJECTIUS[r['objectiu']].lower()}» en menjaràs unes <b>{c['kcal']:.0f}</b>.",
              "Les màquines de Gym Zero apunten les sèries soles quan hi acostes la polsera. Les altres, apunta-les a la llibreta de la web."]
    h += puntos(motius)
    h += [esp(8), Titulo("L'esforç", "fuego", "Acaba cada sèrie quan et quedin 2 o 3 repeticions."),
          EscalaEsfuerzo(IDIOMA, marcar=[3, 2]), PageBreak()]

    setmana = [("Descans", None)] * 7
    for dia, titol, _ in sessions:
        setmana[dia - 1] = (titol, VERDE)
    h += [Titulo("La teva setmana", "calendario", divisio), Semana(setmana, IDIOMA), esp(14)]
    for dia, titol, llista in sessions:
        bloc = [Titulo(f"{['Dilluns', 'Dimarts', 'Dimecres', 'Dijous', 'Divendres', 'Dissabte', 'Diumenge'][dia - 1]} · {titol}", "pesa")]
        for i, e in enumerate(llista, 1):
            ej = dict(e)
            if e["maquina"]:
                ej["nota"] = (e["nota"] + " · " if e["nota"] else "") + f"Màquina {e['maquina']}: acosta-hi la polsera"
            bloc.append(KeepTogether([Ejercicio(i, ej, IDIOMA), esp(6)]))
        h += bloc + [esp(8)]
    h.append(PageBreak())

    h += [Titulo("El teu menjar", "plato", "Quantitats en grams. Si no t'agrada un aliment, tria'n un de la llista de sota.", NARANJA),
          Fila([Mosaico("fuego", f"{c['kcal']:.0f}", "kcal al dia", NARANJA, NARANJA_CLARO),
                Mosaico("pesa", f"{c['prot']} g", "proteïna", NARANJA, NARANJA_CLARO),
                Mosaico("plato", f"{c['hidr']} g", "hidrats", NARANJA, NARANJA_CLARO),
                Mosaico("gota", f"{c['greix']} g", "greix", NARANJA, NARANJA_CLARO)]), esp(12)]
    for t in targetes_menjar(r, c):
        h += [t, esp(8)]

    h += [esp(6), Titulo("Cada 2 setmanes", "grafica"),
          *puntos(["Pesa't 3 matins i fes la mitjana.",
                   "Entra a La meva àrea de la web i envia la revisió: el pes i com t'ha anat.",
                   "L'entrenador et respon i, si cal, et canvia la rutina a la llibreta."]),
          Tarjeta([P("Aquest pla és orientatiu i el revisa un entrenador de Gym Zero. No substitueix el consell del metge.", "small")],
                  fondo=GRIS_CLARO)]
    doc.build(h)
    return sessions


def main():
    ap = argparse.ArgumentParser(description="Fa el PDF del pla d'un client a partir del JSON del qüestionari.")
    ap.add_argument("questionari", help="fitxer JSON descarregat de la fitxa del client")
    ap.add_argument("--sortida", default=".", help="carpeta on es desa el PDF (per defecte, l'actual)")
    ap.add_argument("--vistiplau", action="store_true", help="el client ja té el vistiplau del metge")
    a = ap.parse_args()

    dades = json.loads(Path(a.questionari).read_text(encoding="utf-8"))
    fitxer = Path(a.sortida) / f"pla_{ascii(dades['client'])}_{date.today().isoformat()}.pdf"
    sessions = fes_pdf(dades, fitxer, a.vistiplau)
    print(f"PDF fet: {fitxer}")
    for nota in llegeix(dades["respostes"])["per_entrenador"]:
        print("Compte: " + nota)
    print("\nRutina per entrar a la fitxa del client (Rutina de la llibreta):")
    for dia, titol, llista in sessions:
        print(f"  {['Dilluns', 'Dimarts', 'Dimecres', 'Dijous', 'Divendres', 'Dissabte', 'Diumenge'][dia - 1]} · {titol}")
        for e in llista:
            print(f"    {e['n']:<32} {e['s']} sèries  {e['maquina'] or 'a mà'}")


if __name__ == "__main__":
    main()
