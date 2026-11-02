"""
Sistema de diseño moderno para los PDFs de planes (APP GYM).
- Letra grande y poco texto, pensado para cualquier edad.
- Iconos vectoriales dibujados (sin emojis ni imágenes externas).
- Tarjetas, insignias, escala de esfuerzo por colores y calendario semanal.
- Todos los textos fijos salen de TXT[idioma] para poder generar el plan en cualquier idioma.
"""
from reportlab.lib.pagesizes import A4
from reportlab.platypus import (BaseDocTemplate, PageTemplate, Frame, Paragraph, Spacer, PageBreak,
                                Flowable, KeepTogether)
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.colors import HexColor, white, black
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.fonts import addMapping
from urllib.parse import quote_plus

F = "/usr/share/fonts/truetype/liberation/"
pdfmetrics.registerFont(TTFont("Sans", F + "LiberationSans-Regular.ttf"))
pdfmetrics.registerFont(TTFont("Sans-Bold", F + "LiberationSans-Bold.ttf"))
pdfmetrics.registerFont(TTFont("Sans-Italic", F + "LiberationSans-Italic.ttf"))
pdfmetrics.registerFont(TTFont("Sans-BoldItalic", F + "LiberationSans-BoldItalic.ttf"))
addMapping("Sans", 0, 0, "Sans"); addMapping("Sans", 1, 0, "Sans-Bold")
addMapping("Sans", 0, 1, "Sans-Italic"); addMapping("Sans", 1, 1, "Sans-BoldItalic")

# ---------------------------------------------------------------- paleta
VERDE = HexColor("#2E7D32")
VERDE_CLARO = HexColor("#E8F5E9")
NARANJA = HexColor("#EF6C00")
NARANJA_CLARO = HexColor("#FFF3E0")
AZUL = HexColor("#1565C0")
AZUL_CLARO = HexColor("#E3F2FD")
GRIS = HexColor("#555555")
GRIS_CLARO = HexColor("#F4F4F4")
TEXTO = HexColor("#222222")
ROJO = HexColor("#C62828")
AMARILLO = HexColor("#F9A825")

PAGE_W, PAGE_H = A4
MARGEN = 42
ANCHO = PAGE_W - 2 * MARGEN

# ---------------------------------------------------------------- estilos
S = {
    "h1": ParagraphStyle("h1", fontName="Sans-Bold", fontSize=26, leading=30, textColor=VERDE, spaceAfter=6),
    "h2": ParagraphStyle("h2", fontName="Sans-Bold", fontSize=18, leading=22, textColor=TEXTO, spaceBefore=10, spaceAfter=6),
    "sub": ParagraphStyle("sub", fontName="Sans", fontSize=13, leading=17, textColor=GRIS, spaceAfter=12),
    "body": ParagraphStyle("body", fontName="Sans", fontSize=12.5, leading=17, textColor=TEXTO, spaceAfter=6),
    "big": ParagraphStyle("big", fontName="Sans-Bold", fontSize=15, leading=19, textColor=TEXTO),
    "small": ParagraphStyle("small", fontName="Sans", fontSize=11, leading=14.5, textColor=GRIS),
    "card_t": ParagraphStyle("card_t", fontName="Sans-Bold", fontSize=14, leading=17.5, textColor=TEXTO),
    "card_b": ParagraphStyle("card_b", fontName="Sans", fontSize=11.5, leading=15, textColor=TEXTO),
    "tile_n": ParagraphStyle("tile_n", fontName="Sans-Bold", fontSize=17, leading=20, textColor=TEXTO),
    "tile_l": ParagraphStyle("tile_l", fontName="Sans", fontSize=11, leading=13.5, textColor=GRIS),
}

# ---------------------------------------------------------------- textos fijos traducibles
TXT = {
    "es": {
        "plan_de": "Plan de", "pagina": "Página", "series": "series", "veces": "repeticiones",
        "segundos": "segundos", "por_pierna": "por pierna",
        "ocupada": "¿Ocupada o te molesta? Haz", "o": "o", "truco": "Truco", "video": "Ver cómo se hace",
        "descanso": "Descanso", "esfuerzo": "Esfuerzo",
        "esf_3": "Te quedan 3", "esf_2": "Te quedan 2", "esf_1": "Te queda 1", "esf_0": "No puedes más",
        "esf_3d": "Fácil", "esf_2d": "Cuesta", "esf_1d": "Muy duro", "esf_0d": "Al límite",
        "si_no": "Si no tienes o no te apetece", "otra_opcion": "Otra opción completa",
        "dias": ["L", "M", "X", "J", "V", "S", "D"],
        "dias_largos": ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"],
        "siguiente_fase": "Pasas a la siguiente fase cuando…",
        "video_q": "técnica",
    },
    "ca": {
        "plan_de": "Pla de", "pagina": "Pàgina", "series": "sèries", "veces": "repeticions",
        "segundos": "segons", "por_pierna": "per cama",
        "ocupada": "Ocupada o et molesta? Fes", "o": "o", "truco": "Truc", "video": "Mira com es fa",
        "descanso": "Descans", "esfuerzo": "Esforç",
        "esf_3": "Te'n queden 3", "esf_2": "Te'n queden 2", "esf_1": "Te'n queda 1", "esf_0": "No pots més",
        "esf_3d": "Fàcil", "esf_2d": "Costa", "esf_1d": "Molt dur", "esf_0d": "Al límit",
        "si_no": "Si no en tens o no et ve de gust", "otra_opcion": "Una altra opció completa",
        "dias": ["Dl", "Dt", "Dc", "Dj", "Dv", "Ds", "Dg"],
        "dias_largos": ["Dilluns", "Dimarts", "Dimecres", "Dijous", "Divendres", "Dissabte", "Diumenge"],
        "siguiente_fase": "Passes a la fase següent quan…",
        "video_q": "tècnica",
    },
    "en": {
        "plan_de": "Plan for", "pagina": "Page", "series": "sets", "veces": "reps",
        "segundos": "seconds", "por_pierna": "each leg",
        "ocupada": "Busy or uncomfortable? Do", "o": "or", "truco": "Tip", "video": "See how it's done",
        "descanso": "Rest", "esfuerzo": "Effort",
        "esf_3": "3 left", "esf_2": "2 left", "esf_1": "1 left", "esf_0": "Can't do more",
        "esf_3d": "Easy", "esf_2d": "Hard", "esf_1d": "Very hard", "esf_0d": "All out",
        "si_no": "If you don't have it or don't fancy it", "otra_opcion": "Another full option",
        "dias": ["M", "T", "W", "T", "F", "S", "S"],
        "dias_largos": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
        "siguiente_fase": "You move to the next phase when…",
        "video_q": "technique",
    },
    "fr": {
        "plan_de": "Programme de", "pagina": "Page", "series": "séries", "veces": "répétitions",
        "segundos": "secondes", "por_pierna": "par jambe",
        "ocupée": "", "ocupada": "Occupée ou gênant ? Fais", "o": "ou", "truco": "Astuce", "video": "Voir comment faire",
        "descanso": "Repos", "esfuerzo": "Effort",
        "esf_3": "Il en reste 3", "esf_2": "Il en reste 2", "esf_1": "Il en reste 1", "esf_0": "Plus possible",
        "esf_3d": "Facile", "esf_2d": "Difficile", "esf_1d": "Très dur", "esf_0d": "À fond",
        "si_no": "Si tu n'en as pas ou si tu n'en as pas envie", "otra_opcion": "Autre option complète",
        "dias": ["L", "M", "M", "J", "V", "S", "D"],
        "dias_largos": ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche"],
        "siguiente_fase": "Tu passes à la phase suivante quand…",
        "video_q": "technique",
    },
}


# ---------------------------------------------------------------- iconos vectoriales
def icono(c, nombre, x, y, s, color=VERDE):
    """Dibuja un icono de tamaño s con la esquina inferior izquierda en (x, y)."""
    c.saveState()
    c.setStrokeColor(color)
    c.setFillColor(color)
    c.setLineWidth(max(1.2, s / 14))
    c.setLineCap(1)
    c.setLineJoin(1)
    cx, cy = x + s / 2, y + s / 2
    if nombre == "pesa":
        c.rect(x + s * .05, cy - s * .22, s * .14, s * .44, fill=1, stroke=0)
        c.rect(x + s * .19, cy - s * .32, s * .10, s * .64, fill=1, stroke=0)
        c.rect(x + s * .71, cy - s * .32, s * .10, s * .64, fill=1, stroke=0)
        c.rect(x + s * .81, cy - s * .22, s * .14, s * .44, fill=1, stroke=0)
        c.rect(x + s * .29, cy - s * .05, s * .42, s * .10, fill=1, stroke=0)
    elif nombre == "plato":
        c.circle(cx, cy, s * .30, stroke=1, fill=0)
        c.circle(cx, cy, s * .18, stroke=1, fill=0)
        c.line(x + s * .06, y + s * .15, x + s * .06, y + s * .85)
        for dx in (-.04, 0, .04):
            c.line(x + s * (.06 + dx), y + s * .65, x + s * (.06 + dx), y + s * .85)
        c.line(x + s * .94, y + s * .15, x + s * .94, y + s * .85)
        p = c.beginPath(); p.moveTo(x + s * .94, y + s * .85); p.curveTo(x + s * .86, y + s * .7, x + s * .86, y + s * .55, x + s * .94, y + s * .5)
        c.drawPath(p, stroke=1, fill=0)
    elif nombre == "gota":
        p = c.beginPath()
        p.moveTo(cx, y + s * .95)
        p.curveTo(cx + s * .05, y + s * .7, cx + s * .35, y + s * .5, cx + s * .32, y + s * .32)
        p.curveTo(cx + s * .3, y + s * .08, cx - s * .3, y + s * .08, cx - s * .32, y + s * .32)
        p.curveTo(cx - s * .35, y + s * .5, cx - s * .05, y + s * .7, cx, y + s * .95)
        c.drawPath(p, stroke=0, fill=1)
    elif nombre == "luna":
        c.circle(cx, cy, s * .38, stroke=0, fill=1)
        c.setFillColor(white)
        c.circle(cx + s * .17, cy + s * .12, s * .32, stroke=0, fill=1)
    elif nombre == "calendario":
        c.roundRect(x + s * .1, y + s * .1, s * .8, s * .72, s * .08, stroke=1, fill=0)
        c.rect(x + s * .1, y + s * .62, s * .8, s * .2, stroke=0, fill=1)
        c.line(x + s * .3, y + s * .75, x + s * .3, y + s * .92)
        c.line(x + s * .7, y + s * .75, x + s * .7, y + s * .92)
        for i in range(3):
            for j in range(2):
                c.circle(x + s * (.28 + i * .22), y + s * (.42 - j * .18), s * .04, stroke=0, fill=1)
    elif nombre == "grafica":
        c.line(x + s * .12, y + s * .12, x + s * .12, y + s * .9)
        c.line(x + s * .12, y + s * .12, x + s * .9, y + s * .12)
        p = c.beginPath(); p.moveTo(x + s * .2, y + s * .25); p.lineTo(x + s * .42, y + s * .45)
        p.lineTo(x + s * .58, y + s * .38); p.lineTo(x + s * .85, y + s * .75)
        c.drawPath(p, stroke=1, fill=0)
        p = c.beginPath(); p.moveTo(x + s * .85, y + s * .75); p.lineTo(x + s * .7, y + s * .73); p.lineTo(x + s * .83, y + s * .6)
        p.close(); c.drawPath(p, stroke=0, fill=1)
    elif nombre == "check":
        c.circle(cx, cy, s * .42, stroke=0, fill=1)
        c.setStrokeColor(white)
        c.setLineWidth(max(1.6, s / 9))
        p = c.beginPath(); p.moveTo(cx - s * .2, cy); p.lineTo(cx - s * .05, cy - s * .15); p.lineTo(cx + s * .22, cy + s * .15)
        c.drawPath(p, stroke=1, fill=0)
    elif nombre == "aviso":
        p = c.beginPath(); p.moveTo(cx, y + s * .92); p.lineTo(x + s * .95, y + s * .1); p.lineTo(x + s * .05, y + s * .1); p.close()
        c.drawPath(p, stroke=0, fill=1)
        c.setFillColor(white)
        c.rect(cx - s * .04, y + s * .36, s * .08, s * .32, stroke=0, fill=1)
        c.circle(cx, y + s * .24, s * .05, stroke=0, fill=1)
    elif nombre == "reloj":
        c.circle(cx, cy, s * .42, stroke=1, fill=0)
        c.line(cx, cy, cx, cy + s * .26)
        c.line(cx, cy, cx + s * .2, cy - s * .08)
    elif nombre == "cambio":
        for sgn in (1, -1):
            yy = cy + sgn * s * .16
            x0, x1 = (x + s * .12, x + s * .82) if sgn > 0 else (x + s * .88, x + s * .18)
            c.line(x0, yy, x1, yy)
            d = s * .12 * (1 if sgn > 0 else -1)
            p = c.beginPath(); p.moveTo(x1 + d * .3, yy); p.lineTo(x1 - d * .8, yy + s * .11); p.lineTo(x1 - d * .8, yy - s * .11); p.close()
            c.drawPath(p, stroke=0, fill=1)
    elif nombre == "play":
        c.circle(cx, cy, s * .42, stroke=0, fill=1)
        c.setFillColor(white)
        p = c.beginPath(); p.moveTo(cx - s * .12, cy + s * .2); p.lineTo(cx + s * .2, cy); p.lineTo(cx - s * .12, cy - s * .2); p.close()
        c.drawPath(p, stroke=0, fill=1)
    elif nombre == "corazon":
        p = c.beginPath()
        p.moveTo(cx, y + s * .12)
        p.curveTo(x + s * .05, y + s * .45, x + s * .1, y + s * .9, cx, y + s * .7)
        p.curveTo(x + s * .9, y + s * .9, x + s * .95, y + s * .45, cx, y + s * .12)
        c.drawPath(p, stroke=0, fill=1)
    elif nombre == "carrito":
        c.line(x + s * .05, y + s * .82, x + s * .2, y + s * .82)
        p = c.beginPath(); p.moveTo(x + s * .2, y + s * .82); p.lineTo(x + s * .32, y + s * .35)
        p.lineTo(x + s * .85, y + s * .35); p.lineTo(x + s * .93, y + s * .7); p.lineTo(x + s * .25, y + s * .7)
        c.drawPath(p, stroke=1, fill=0)
        c.circle(x + s * .38, y + s * .18, s * .07, stroke=0, fill=1)
        c.circle(x + s * .78, y + s * .18, s * .07, stroke=0, fill=1)
    elif nombre == "fuego":
        p = c.beginPath()
        p.moveTo(cx, y + s * .95)
        p.curveTo(cx + s * .45, y + s * .55, cx + s * .4, y + s * .1, cx, y + s * .08)
        p.curveTo(cx - s * .4, y + s * .1, cx - s * .45, y + s * .45, cx - s * .12, y + s * .65)
        p.curveTo(cx - s * .1, y + s * .45, cx, y + s * .5, cx, y + s * .95)
        c.drawPath(p, stroke=0, fill=1)
    elif nombre == "camino":
        for i, xx in enumerate((.15, .5, .85)):
            c.circle(x + s * xx, cy, s * .12, stroke=0, fill=1)
        c.line(x + s * .27, cy, x + s * .38, cy)
        c.line(x + s * .62, cy, x + s * .73, cy)
    c.restoreState()


# ---------------------------------------------------------------- flowables
class Icono(Flowable):
    def __init__(self, nombre, s=28, color=VERDE):
        super().__init__()
        self.nombre, self.s, self.color = nombre, s, color
        self.width = self.height = s

    def draw(self):
        icono(self.canv, self.nombre, 0, 0, self.s, self.color)


class Titulo(Flowable):
    """Título de sección grande con icono a la izquierda y subtítulo opcional."""
    def __init__(self, texto, icono_n=None, sub=None, color=VERDE):
        super().__init__()
        self.texto, self.icono_n, self.sub_t, self.color = texto, icono_n, sub, color

    def wrap(self, aw, ah):
        self.aw = aw
        st = ParagraphStyle("t", parent=S["h1"], textColor=self.color)
        off = 44 if self.icono_n else 0
        self.p = Paragraph(self.texto, st)
        _, h1 = self.p.wrap(aw - off, ah)
        h2 = 0
        if self.sub_t:
            self.ps = Paragraph(self.sub_t, S["sub"])
            _, h2 = self.ps.wrap(aw - off, ah)
        self.h1, self.h2 = h1, h2
        self.height = max(h1 + h2 + 6, 40) + 10
        return aw, self.height

    def draw(self):
        off = 44 if self.icono_n else 0
        top = self.height - 6
        if self.icono_n:
            icono(self.canv, self.icono_n, 0, top - 34, 34, self.color)
        self.p.drawOn(self.canv, off, top - self.h1)
        if self.sub_t:
            self.ps.drawOn(self.canv, off, top - self.h1 - self.h2 - 2)


class Tarjeta(Flowable):
    """Tarjeta redondeada con barra de color y contenido (lista de Paragraph)."""
    def __init__(self, contenido, fondo=GRIS_CLARO, acento=VERDE, pad=12, icono_n=None, ancho=None):
        super().__init__()
        self.contenido, self.fondo, self.acento, self.pad = contenido, fondo, acento, pad
        self.icono_n, self.ancho_fijo = icono_n, ancho

    def wrap(self, aw, ah):
        self.w = self.ancho_fijo or aw
        off = 34 if self.icono_n else 0
        inner = self.w - 2 * self.pad - 6 - off
        self.hs = [p.wrap(inner, ah)[1] for p in self.contenido]
        self.h = sum(self.hs) + 2 * self.pad + 3 * (len(self.contenido) - 1)
        self.h = max(self.h, 40 if self.icono_n else 0)
        return self.w, self.h

    def draw(self):
        c = self.canv
        c.setFillColor(self.fondo)
        c.roundRect(0, 0, self.w, self.h, 10, stroke=0, fill=1)
        c.setFillColor(self.acento)
        c.roundRect(0, 0, 6, self.h, 3, stroke=0, fill=1)
        off = 0
        if self.icono_n:
            icono(c, self.icono_n, self.pad + 4, self.h - self.pad - 26, 26, self.acento)
            off = 34
        y = self.h - self.pad
        for p, h in zip(self.contenido, self.hs):
            y -= h
            p.drawOn(c, self.pad + 6 + off, y)
            y -= 3

    def split(self, aw, ah):
        return []


class Fila(Flowable):
    """Varias tarjetas/flowables en fila con el mismo alto."""
    def __init__(self, items, gap=10):
        super().__init__()
        self.items, self.gap = items, gap

    def wrap(self, aw, ah):
        self.aw = aw
        n = len(self.items)
        w = (aw - self.gap * (n - 1)) / n
        for it in self.items:
            it.ancho_fijo = w
        hs = [it.wrap(w, ah)[1] for it in self.items]
        self.h = max(hs)
        for it in self.items:
            it.h = self.h
        self.wi = w
        return aw, self.h

    def draw(self):
        for i, it in enumerate(self.items):
            it.drawOn(self.canv, i * (self.wi + self.gap), 0)


class Mosaico(Flowable):
    """Baldosa con icono, número grande y etiqueta (para 'tu plan en un vistazo')."""
    def __init__(self, icono_n, valor, etiqueta, color=VERDE, fondo=VERDE_CLARO):
        super().__init__()
        self.icono_n, self.valor, self.etiqueta, self.color, self.fondo = icono_n, valor, etiqueta, color, fondo
        self.ancho_fijo = None

    def wrap(self, aw, ah):
        self.w = self.ancho_fijo or aw
        self.pv = Paragraph(self.valor, S["tile_n"])
        self.pl = Paragraph(self.etiqueta, S["tile_l"])
        self.hv = self.pv.wrap(self.w - 24, ah)[1]
        self.hl = self.pl.wrap(self.w - 24, ah)[1]
        self.h = getattr(self, "h", 0) or (12 + 30 + 8 + self.hv + 2 + self.hl + 12)
        return self.w, self.h

    def draw(self):
        c = self.canv
        c.setFillColor(self.fondo)
        c.roundRect(0, 0, self.w, self.h, 12, stroke=0, fill=1)
        icono(c, self.icono_n, 12, self.h - 12 - 30, 30, self.color)
        y = self.h - 12 - 30 - 8 - self.hv
        self.pv.drawOn(c, 12, y)
        self.pl.drawOn(c, 12, y - 2 - self.hl)


class EscalaEsfuerzo(Flowable):
    """Escala visual del esfuerzo (RIR) con 4 colores."""
    def __init__(self, idioma="es", marcar=None):
        super().__init__()
        self.t, self.marcar = TXT[idioma], marcar or []
        self.height = 78

    def wrap(self, aw, ah):
        self.width = aw
        return aw, self.height

    def draw(self):
        c = self.canv
        cols = [(VERDE, "esf_3", "esf_3d", 3), (AMARILLO, "esf_2", "esf_2d", 2),
                (NARANJA, "esf_1", "esf_1d", 1), (ROJO, "esf_0", "esf_0d", 0)]
        w = (self.width - 3 * 8) / 4
        for i, (col, k1, k2, n) in enumerate(cols):
            x = i * (w + 8)
            c.setFillColor(col)
            c.roundRect(x, 0, w, self.height, 10, stroke=0, fill=1)
            c.setFillColor(white)
            c.setFont("Sans-Bold", 22)
            c.drawCentredString(x + w / 2, self.height - 30, str(n))
            c.setFont("Sans-Bold", 11.5)
            c.drawCentredString(x + w / 2, self.height - 48, self.t[k1])
            c.setFont("Sans", 10.5)
            c.drawCentredString(x + w / 2, self.height - 63, self.t[k2])
            if n in self.marcar:
                c.setStrokeColor(TEXTO)
                c.setLineWidth(2.5)
                c.roundRect(x - 2, -2, w + 4, self.height + 4, 11, stroke=1, fill=0)


class Semana(Flowable):
    """Calendario semanal: 7 casillas con lo que toca cada día."""
    def __init__(self, dias, idioma="es"):
        # dias: lista de 7 tuplas (texto, color) ; color None = descanso
        super().__init__()
        self.dias, self.t = dias, TXT[idioma]
        self.height = 92

    def wrap(self, aw, ah):
        self.width = aw
        return aw, self.height

    def draw(self):
        c = self.canv
        w = (self.width - 6 * 6) / 7
        for i, (txt, col) in enumerate(self.dias):
            x = i * (w + 6)
            c.setFillColor(col or GRIS_CLARO)
            c.roundRect(x, 0, w, self.height, 9, stroke=0, fill=1)
            fg = white if col else GRIS
            c.setFillColor(fg)
            c.setFont("Sans-Bold", 15)
            c.drawCentredString(x + w / 2, self.height - 22, self.t["dias"][i])
            p = Paragraph(f"<font color='{'#FFFFFF' if col else '#555555'}'>{txt}</font>",
                          ParagraphStyle("d", fontName="Sans-Bold", fontSize=9.5, leading=11.5, alignment=1))
            _, h = p.wrap(w - 6, self.height)
            p.drawOn(c, x + 3, (self.height - 30) / 2 - h / 2 + 4)


class Camino(Flowable):
    """Fases como pasos conectados."""
    def __init__(self, pasos):
        super().__init__()
        self.pasos = pasos  # [(titulo, detalle)]

    def wrap(self, aw, ah):
        self.width = aw
        n = len(self.pasos)
        self.w = (aw - (n - 1) * 18) / n
        self.ps = [(Paragraph(t, ParagraphStyle("ct", fontName="Sans-Bold", fontSize=13, leading=16, textColor=white)),
                    Paragraph(d, ParagraphStyle("cd", fontName="Sans", fontSize=10.5, leading=13.5, textColor=white)))
                   for t, d in self.pasos]
        self.hh = max(a.wrap(self.w - 20, ah)[1] + 6 + b.wrap(self.w - 20, ah)[1] for a, b in self.ps) + 46
        self.height = self.hh
        return aw, self.height

    def draw(self):
        c = self.canv
        colores = [VERDE, AZUL, NARANJA, ROJO]
        for i, (a, b) in enumerate(self.ps):
            x = i * (self.w + 18)
            col = colores[i % 4]
            c.setFillColor(col)
            c.roundRect(x, 0, self.w, self.height, 12, stroke=0, fill=1)
            c.setFillColor(white)
            c.circle(x + 22, self.height - 22, 13, stroke=0, fill=1)
            c.setFillColor(col)
            c.setFont("Sans-Bold", 14)
            c.drawCentredString(x + 22, self.height - 27, str(i + 1))
            ha = a.wrap(self.w - 20, 999)[1]
            a.drawOn(c, x + 10, self.height - 44 - ha)
            hb = b.wrap(self.w - 20, 999)[1]
            b.drawOn(c, x + 10, self.height - 44 - ha - 6 - hb)
            if i < len(self.ps) - 1:
                c.setFillColor(GRIS)
                xx = x + self.w + 4
                p = c.beginPath(); p.moveTo(xx, self.height / 2 + 7); p.lineTo(xx + 10, self.height / 2); p.lineTo(xx, self.height / 2 - 7); p.close()
                c.drawPath(p, stroke=0, fill=1)


def link_video(nombre, idioma="es"):
    q = quote_plus(f"{nombre} {TXT[idioma]['video_q']}")
    return f"https://www.youtube.com/results?search_query={q}"


class Ejercicio(Flowable):
    """Tarjeta de ejercicio: número, nombre, insignia de series, truco, alternativas y enlace al vídeo."""
    def __init__(self, num, ej, idioma="es"):
        super().__init__()
        self.num, self.ej, self.t, self.idioma = num, ej, TXT[idioma], idioma

    def _insignia(self):
        e, t = self.ej, self.t
        if e.get("tipo") == "seg":
            return f"{e['s']} × {e['r']} {t['segundos']}"
        txt = f"{e['s']} × {e['r']}"
        if e.get("pierna"):
            txt += f" {t['por_pierna']}"
        return txt

    def wrap(self, aw, ah):
        self.width = aw
        e, t = self.ej, self.t
        self.badge = self._insignia()
        self.bw = pdfmetrics.stringWidth(self.badge, "Sans-Bold", 13) + 20
        self.pn = Paragraph(e["n"], S["card_t"])
        inner = aw - 52 - 14
        self.hn = self.pn.wrap(inner - self.bw - 8, ah)[1]
        lines = []
        if e.get("nota"):
            lines.append(Paragraph(f"<font color='#EF6C00'><b>{e['nota']}</b></font>", S["card_b"]))
        if e.get("truco"):
            lines.append(Paragraph(f"<b>{t['truco']}:</b> {e['truco']}", S["card_b"]))
        if e.get("alt"):
            alts = f" {t['o']} ".join(f"<b>{a}</b>" for a in e["alt"])
            lines.append(Paragraph(f"<font color='#555555'>{t['ocupada']}:</font> {alts}", S["card_b"]))
        url = link_video(e.get("video", e["n"]), self.idioma)
        lines.append(Paragraph(f"<a href='{url}' color='#1565C0'><u>{t['video']}</u></a>", S["card_b"]))
        self.lines = lines
        self.hl = [p.wrap(inner, ah)[1] for p in lines]
        self.height = 12 + max(self.hn, 24) + 6 + sum(self.hl) + 3 * len(lines) + 10
        return aw, self.height

    def draw(self):
        c = self.canv
        h, w = self.height, self.width
        c.setFillColor(GRIS_CLARO)
        c.roundRect(0, 0, w, h, 10, stroke=0, fill=1)
        c.setFillColor(VERDE)
        c.circle(24, h - 24, 15, stroke=0, fill=1)
        c.setFillColor(white)
        c.setFont("Sans-Bold", 15)
        c.drawCentredString(24, h - 29.5, str(self.num))
        x0 = 52
        top = h - 12
        self.pn.drawOn(c, x0, top - self.hn)
        # insignia
        bx = w - 14 - self.bw
        c.setFillColor(NARANJA)
        c.roundRect(bx, top - 24, self.bw, 24, 12, stroke=0, fill=1)
        c.setFillColor(white)
        c.setFont("Sans-Bold", 13)
        c.drawCentredString(bx + self.bw / 2, top - 17, self.badge)
        y = top - max(self.hn, 24) - 6
        for p, hh in zip(self.lines, self.hl):
            y -= hh
            p.drawOn(c, x0, y)
            y -= 3


class Comida(Flowable):
    """Tarjeta de comida con hora en círculo, lista de alimentos y alternativas."""
    def __init__(self, hora, titulo, items, alts=None, opcion=None, idioma="es", color=NARANJA):
        super().__init__()
        self.hora, self.titulo, self.items, self.alts, self.opcion = hora, titulo, items, alts or [], opcion
        self.t, self.color = TXT[idioma], color

    def wrap(self, aw, ah):
        self.width = aw
        inner = aw - 80 - 14
        self.ps = [Paragraph(self.titulo, S["card_t"])]
        for it in self.items:
            self.ps.append(Paragraph(f"<font color='#2E7D32'><b>•</b></font>  {it}", S["card_b"]))
        if self.alts:
            self.ps.append(Spacer(1, 2))
            self.ps.append(Paragraph(f"<font color='#555555'><b>{self.t['si_no']}:</b></font>", S["small"]))
            for a in self.alts:
                self.ps.append(Paragraph(a, S["small"]))
        if self.opcion:
            self.ps.append(Paragraph(f"<b>{self.t['otra_opcion']}:</b> {self.opcion}", S["small"]))
        self.hs = [p.wrap(inner, ah)[1] for p in self.ps]
        self.height = max(sum(self.hs) + 3 * len(self.ps) + 22, 80)
        return aw, self.height

    def draw(self):
        c = self.canv
        h, w = self.height, self.width
        c.setFillColor(NARANJA_CLARO)
        c.roundRect(0, 0, w, h, 10, stroke=0, fill=1)
        c.setFillColor(self.color)
        c.circle(38, h - 38, 28, stroke=0, fill=1)
        c.setFillColor(white)
        c.setFont("Sans-Bold", 14 if len(self.hora) <= 5 else 11)
        c.drawCentredString(38, h - 43, self.hora)
        y = h - 12
        for p, hh in zip(self.ps, self.hs):
            y -= hh
            p.drawOn(c, 80, y)
            y -= 3


# ---------------------------------------------------------------- documento
def documento(salida, cliente, idioma="es", titulo_pdf=None):
    t = TXT[idioma]

    def pie(c, doc):
        c.saveState()
        c.setFillColor(VERDE)
        c.rect(0, PAGE_H - 10, PAGE_W, 10, stroke=0, fill=1)
        c.setFont("Sans", 9.5)
        c.setFillColor(GRIS)
        c.drawString(MARGEN, 22, f"{t['plan_de']} {cliente}")
        c.drawRightString(PAGE_W - MARGEN, 22, f"{t['pagina']} {doc.page}")
        c.restoreState()

    doc = BaseDocTemplate(salida, pagesize=A4, leftMargin=MARGEN, rightMargin=MARGEN, topMargin=40, bottomMargin=44,
                          title=titulo_pdf or f"{t['plan_de']} {cliente}", author="Jan")
    frame = Frame(MARGEN, 44, ANCHO, PAGE_H - 84, id="f", leftPadding=0, rightPadding=0, topPadding=0, bottomPadding=0)
    doc.addPageTemplates([PageTemplate(id="p", frames=[frame], onPage=pie)])
    return doc


def P(texto, estilo="body"):
    return Paragraph(texto, S[estilo] if isinstance(estilo, str) else estilo)


def esp(h=10):
    return Spacer(1, h)


class Punto(Flowable):
    """Icono grande + texto. Para listas fáciles de leer."""
    def __init__(self, texto, icono_n="check", color=VERDE, estilo="body"):
        super().__init__()
        self.texto, self.icono_n, self.color, self.estilo = texto, icono_n, color, estilo

    def wrap(self, aw, ah):
        self.width = aw
        self.p = Paragraph(self.texto, S[self.estilo])
        self.hp = self.p.wrap(aw - 40, ah)[1]
        self.height = max(self.hp, 26) + 8
        return aw, self.height

    def draw(self):
        icono(self.canv, self.icono_n, 0, self.height - 8 - 26 + (2 if self.hp > 26 else 0), 26, self.color)
        self.p.drawOn(self.canv, 40, self.height - 8 - self.hp + (0 if self.hp >= 26 else -(26 - self.hp) / 2))


def puntos(items, icono_n="check", color=VERDE):
    return [Punto(it, icono_n, color) for it in items]
