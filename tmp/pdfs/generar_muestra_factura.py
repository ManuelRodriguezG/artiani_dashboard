from pathlib import Path
from xml.etree import ElementTree as ET

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_RIGHT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.lib.utils import ImageReader
from reportlab.platypus import Flowable, Image, KeepTogether, Paragraph, SimpleDocTemplate, Spacer, Table, TableStyle


ROOT = Path(r"C:\xampp\htdocs\panel_de_control")
XML_PATH = Path(r"C:\Users\aleja\AppData\Local\Temp\c3ab397b-0feb-4f19-8b46-76d9a169a960.xml")
LOGO_PATH = ROOT / "public" / "assets" / "media" / "logos" / "artiani-logo-pdf.jpg"
OUTPUT_PATH = ROOT / "output" / "pdf" / "factura_artiani_fiscal_actual.pdf"

CORAL = colors.HexColor("#e9485b")
TEAL = colors.HexColor("#0b7376")
INK = colors.HexColor("#202124")
PALE_TEAL = colors.HexColor("#f1fbfb")
GRID = colors.HexColor("#d7e1e4")


def child(node, local_name):
    for item in list(node):
        if item.tag.rsplit("}", 1)[-1] == local_name:
            return item
    return None


def children(node, local_name):
    return [item for item in list(node) if item.tag.rsplit("}", 1)[-1] == local_name]


def attr(node, name, fallback="-"):
    return (node.attrib.get(name) or fallback).strip()


def money(value):
    try:
        return f"${float(value):,.2f} MXN"
    except (TypeError, ValueError):
        return "$0.00 MXN"


def safe(value):
    return str(value or "-").replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")


class MiniQr(Flowable):
    def __init__(self, size=38 * mm):
        super().__init__()
        self.width = size
        self.height = size
        self.size = size

    def draw(self):
        canvas = self.canv
        cells = 29
        cell = self.size / cells
        canvas.setFillColor(colors.white)
        canvas.rect(0, 0, self.size, self.size, stroke=0, fill=1)
        canvas.setFillColor(colors.black)
        for row in range(cells):
            for col in range(cells):
                filled = None
                for x, y in ((3, 3), (cells - 4, 3), (3, cells - 4)):
                    if abs(col - x) <= 3 and abs(row - y) <= 3:
                        edge = max(abs(col - x), abs(row - y)) == 3
                        center = abs(col - x) <= 1 and abs(row - y) <= 1
                        filled = edge or center
                        break
                if filled is None:
                    filled = ((row * 17 + col * 31 + row * col) % 7) < 3
                if filled:
                    canvas.rect(col * cell, self.size - (row + 1) * cell, cell + 0.2, cell + 0.2, stroke=0, fill=1)


def paragraph(text, style):
    return Paragraph(text, style)


root = ET.parse(XML_PATH).getroot()
timbre = None
complemento = child(root, "Complemento")
if complemento is not None:
    timbre = child(complemento, "TimbreFiscalDigital")
emisor = child(root, "Emisor")
receptor = child(root, "Receptor")
conceptos = children(child(root, "Conceptos"), "Concepto") if child(root, "Conceptos") is not None else []
impuestos = child(root, "Impuestos")

styles = getSampleStyleSheet()
normal = ParagraphStyle("normal", parent=styles["Normal"], fontName="Helvetica", fontSize=7.2, leading=9, textColor=INK, spaceAfter=0)
small = ParagraphStyle("small", parent=normal, fontSize=6.4, leading=7.5)
tiny = ParagraphStyle("tiny", parent=normal, fontName="Courier", fontSize=5.3, leading=6.2)
white = ParagraphStyle("white", parent=normal, textColor=colors.white, fontName="Helvetica-Bold", fontSize=7.2, leading=8.5, alignment=TA_CENTER)
section = ParagraphStyle("section", parent=white, fontSize=7.3, textTransform="uppercase")
right = ParagraphStyle("right", parent=normal, alignment=TA_RIGHT)
title = ParagraphStyle("title", parent=normal, textColor=TEAL, fontName="Helvetica-Bold", fontSize=17, leading=18, alignment=TA_RIGHT)
label = ParagraphStyle("label", parent=normal, textColor=TEAL, fontName="Helvetica-Bold", fontSize=6.5, leading=7.5)

folio = f"{attr(root, 'Serie', '')} {attr(root, 'Folio', '')}".strip()
fecha = attr(root, "Fecha", "-").replace("T", " ")
fecha_timbrado = attr(timbre, "FechaTimbrado", "-").replace("T", " ") if timbre is not None else "-"
uuid = attr(timbre, "UUID", "-") if timbre is not None else "-"
sello_cfdi = attr(timbre, "SelloCFD", attr(root, "Sello", "No disponible")) if timbre is not None else attr(root, "Sello", "No disponible")
sello_sat = attr(timbre, "SelloSAT", "No disponible") if timbre is not None else "No disponible"
no_cert_sat = attr(timbre, "NoCertificadoSAT", "-") if timbre is not None else "-"

header_left = [
    Image(str(LOGO_PATH), width=47 * mm, height=20 * mm, kind="proportional"),
    paragraph(f"<b>{safe(attr(emisor, 'Nombre'))}</b>", normal),
    paragraph(f"RFC: {safe(attr(emisor, 'Rfc'))}", small),
    paragraph("Régimen fiscal: 612 - Personas físicas con actividades empresariales", small),
    paragraph(f"Lugar de expedición: {safe(attr(root, 'LugarExpedicion'))}", small),
]
header_right = [
    paragraph("FACTURA", ParagraphStyle("invoice", parent=label, alignment=TA_RIGHT, fontSize=8.2)),
    paragraph(safe(folio), title),
    paragraph(f"Fecha de emisión: {safe(fecha)}", small),
    paragraph(f"Fecha de certificación: {safe(fecha_timbrado)}", small),
]
header = Table([[header_left, header_right]], colWidths=[125 * mm, 48 * mm])
header.setStyle(TableStyle([
    ("VALIGN", (0, 0), (-1, -1), "TOP"),
    ("ALIGN", (1, 0), (1, 0), "RIGHT"),
    ("LINEBELOW", (0, 0), (-1, -1), 2.2, TEAL),
    ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
]))

receiver_bar = Table([[paragraph("Receptor del comprobante fiscal", white), paragraph("Folio fiscal", white)]], colWidths=[86.5 * mm, 86.5 * mm])
receiver_bar.setStyle(TableStyle([
    ("BACKGROUND", (0, 0), (0, 0), TEAL), ("BACKGROUND", (1, 0), (1, 0), TEAL),
    ("VALIGN", (0, 0), (-1, -1), "MIDDLE"), ("TOPPADDING", (0, 0), (-1, -1), 4), ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
]))

receiver_left = [
    paragraph(f"<b>{safe(attr(receptor, 'Nombre'))}</b>", normal),
    paragraph(f"RFC: {safe(attr(receptor, 'Rfc'))}", small),
    paragraph("Régimen fiscal: 612 - Personas físicas con actividades empresariales", small),
    paragraph(f"Domicilio fiscal: {safe(attr(receptor, 'DomicilioFiscalReceptor'))}", small),
    paragraph("Uso CFDI: G01 - Adquisición de mercancías", small),
    paragraph("Exportación: 01 - No aplica", small),
]
receiver_right = [
    paragraph(f"<b>{safe(uuid)}</b>", small),
    paragraph(f"No. certificado digital: {safe(attr(root, 'NoCertificado'))}", small),
    paragraph(f"No. certificado SAT: {safe(no_cert_sat)}", small),
]
receiver = Table([[receiver_left, receiver_right]], colWidths=[100 * mm, 73 * mm])
receiver.setStyle(TableStyle([
    ("BOX", (0, 0), (-1, -1), 0.5, GRID), ("LINEBEFORE", (1, 0), (1, 0), 0.5, GRID),
    ("VALIGN", (0, 0), (-1, -1), "TOP"), ("LEFTPADDING", (0, 0), (-1, -1), 5), ("RIGHTPADDING", (0, 0), (-1, -1), 5),
    ("TOPPADDING", (0, 0), (-1, -1), 5), ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
]))

item_rows = [[paragraph("Cantidad", white), paragraph("Unidad", white), paragraph("Descripción", white), paragraph("Precio", white), paragraph("Descuento", white), paragraph("Importe", white)]]
for item in conceptos:
    item_rows.append([
        paragraph(safe(attr(item, "Cantidad", "0")), normal),
        paragraph(safe(attr(item, "Unidad", attr(item, "ClaveUnidad", "-"))), normal),
        paragraph(f"<b>{safe(attr(item, 'Descripcion'))}</b><br/><font size=5.8>Clave SAT: {safe(attr(item, 'ClaveProdServ'))}</font>", normal),
        paragraph(money(attr(item, "ValorUnitario", "0")), right),
        paragraph(money("0"), right),
        paragraph(money(attr(item, "Importe", "0")), right),
    ])
items = Table(item_rows, colWidths=[18 * mm, 20 * mm, 67 * mm, 23 * mm, 22 * mm, 23 * mm], repeatRows=1)
items.setStyle(TableStyle([
    ("BACKGROUND", (0, 0), (-1, 0), CORAL), ("GRID", (0, 0), (-1, -1), 0.35, GRID),
    ("VALIGN", (0, 0), (-1, -1), "TOP"), ("LEFTPADDING", (0, 0), (-1, -1), 3), ("RIGHTPADDING", (0, 0), (-1, -1), 3),
    ("TOPPADDING", (0, 0), (-1, -1), 3.5), ("BOTTOMPADDING", (0, 0), (-1, -1), 3.5),
    ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, colors.HexColor("#fffafa")]),
]))

payment_left = [
    paragraph(f"<b>Lugar de expedición:</b> {safe(attr(root, 'LugarExpedicion'))}", small),
    paragraph("<b>Forma de pago:</b> 03 - Transferencia electrónica de fondos", small),
    paragraph("<b>Método de pago:</b> PUE - Pago en una sola exhibición", small),
    paragraph(f"<b>Moneda:</b> {safe(attr(root, 'Moneda', 'MXN'))}", small),
]
total_rows = [
    [paragraph("Subtotal", small), paragraph(money(attr(root, "SubTotal", "0")), right)],
    [paragraph("Descuento", small), paragraph(money("0"), right)],
    [paragraph("Impuestos", small), paragraph(money(attr(impuestos, "TotalImpuestosTrasladados", "0")), right)],
    [paragraph("<b>Total</b>", white), paragraph(f"<b>{money(attr(root, 'Total', '0'))}</b>", ParagraphStyle("totalright", parent=white, alignment=TA_RIGHT, fontSize=8.8))],
]
totals = Table(total_rows, colWidths=[30 * mm, 30 * mm])
totals.setStyle(TableStyle([
    ("LINEABOVE", (0, 0), (-1, 0), 1.2, TEAL), ("LINEBELOW", (0, 0), (-1, -2), 0.3, GRID),
    ("BACKGROUND", (0, -1), (-1, -1), CORAL), ("LEFTPADDING", (0, 0), (-1, -1), 4), ("RIGHTPADDING", (0, 0), (-1, -1), 4),
    ("TOPPADDING", (0, 0), (-1, -1), 4), ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
]))
payment = Table([[payment_left, totals]], colWidths=[103 * mm, 70 * mm])
payment.setStyle(TableStyle([
    ("BOX", (0, 0), (-1, -1), 0.5, GRID), ("LINEBEFORE", (1, 0), (1, 0), 0.5, GRID),
    ("VALIGN", (0, 0), (-1, -1), "TOP"), ("LEFTPADDING", (0, 0), (-1, -1), 5), ("RIGHTPADDING", (0, 0), (-1, -1), 5),
    ("TOPPADDING", (0, 0), (-1, -1), 6), ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
]))

seals = Table([
    [MiniQr(), [paragraph("Sello digital del CFDI", label), paragraph(safe(sello_cfdi[:360]), tiny), paragraph("Sello digital del SAT", label), paragraph(safe(sello_sat[:360]), tiny)]]
], colWidths=[45 * mm, 128 * mm])
seals.setStyle(TableStyle([
    ("BOX", (0, 0), (-1, -1), 0.5, GRID), ("VALIGN", (0, 0), (-1, -1), "TOP"),
    ("ALIGN", (0, 0), (0, 0), "CENTER"), ("LEFTPADDING", (0, 0), (-1, -1), 5), ("RIGHTPADDING", (0, 0), (-1, -1), 5),
    ("TOPPADDING", (0, 0), (-1, -1), 5), ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
]))

chain = "||4.0|" + uuid + "|" + fecha_timbrado + "|" + attr(timbre, "RfcProvCertif", "-") + "|" + sello_cfdi + "|" + no_cert_sat + "||"
story = [header, Spacer(1, 3), receiver_bar, receiver, Spacer(1, 5), items, Spacer(1, 5), Table([[paragraph("Información de pago y totales", white)]], colWidths=[173 * mm], style=[("BACKGROUND", (0, 0), (-1, -1), TEAL), ("TOPPADDING", (0, 0), (-1, -1), 4), ("BOTTOMPADDING", (0, 0), (-1, -1), 4)]), payment, Spacer(1, 5), Table([[paragraph("Código bidimensional", white), paragraph("Sellos digitales", white)]], colWidths=[86.5 * mm, 86.5 * mm], style=[("BACKGROUND", (0, 0), (0, 0), TEAL), ("BACKGROUND", (1, 0), (1, 0), TEAL), ("TOPPADDING", (0, 0), (-1, -1), 4), ("BOTTOMPADDING", (0, 0), (-1, -1), 4)]), seals, Table([[paragraph("Cadena original del complemento de certificación digital del SAT", white)]], colWidths=[173 * mm], style=[("BACKGROUND", (0, 0), (-1, -1), TEAL), ("TOPPADDING", (0, 0), (-1, -1), 4), ("BOTTOMPADDING", (0, 0), (-1, -1), 4)]), paragraph(safe(chain), tiny), Spacer(1, 4), Table([[paragraph("Este documento es una representación impresa de un CFDI · Versión 4.0", white)]], colWidths=[173 * mm], style=[("BACKGROUND", (0, 0), (-1, -1), TEAL), ("TOPPADDING", (0, 0), (-1, -1), 4), ("BOTTOMPADDING", (0, 0), (-1, -1), 4)])]

doc = SimpleDocTemplate(str(OUTPUT_PATH), pagesize=A4, rightMargin=18 * mm, leftMargin=18 * mm, topMargin=12 * mm, bottomMargin=12 * mm)
doc.build(story)
print(OUTPUT_PATH)
