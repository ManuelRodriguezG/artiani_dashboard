import json
from collections import Counter, defaultdict
from pathlib import Path

root = Path(r"C:\xampp\htdocs\panel_de_control")
source = root / "docs" / "erp_produccion_pedidos_vidrio_preview_2026-10-22.json"
data = json.loads(source.read_text(encoding="utf-8"))
orders = data.get("pedidos", [])

def clean_code(value):
    return " ".join(str(value or "").strip().upper().split())

def block_codes(order):
    counts = Counter()
    for piece in order.get("piezas", []):
        counts[clean_code(piece.get("codigo_crudo"))] += int(piece.get("cantidad", 0) or 0)
    return counts

groups = defaultdict(lambda: {"bloques": 0, "piezas": 0, "validas": 0, "sin_estado": 0})
mixed = []
for order in orders:
    codes = block_codes(order)
    thicknesses = tuple(sorted(float(value) for value in order.get("espesores_mm", [])))
    sheet = (float(order.get("hoja_largo_m", 0)), float(order.get("hoja_ancho_m", 0)))
    key = (sheet, thicknesses)
    bucket = groups[key]
    bucket["bloques"] += 1
    bucket["piezas"] += int(order.get("piezas_total", 0) or 0)
    if str(order.get("estatus_origen", "")).lower() == "validada":
        bucket["validas"] += 1
    else:
        bucket["sin_estado"] += 1
    if len(codes) > 1 or len(thicknesses) > 1:
        mixed.append((order, codes, thicknesses))

output = root / "docs" / "erp_produccion_pedidos_mixtos_2026-10-23.md"
lines = [
    "# Analisis de pedidos mixtos historicos",
    "",
    "Analisis de solo lectura sobre `Pedidos Peceras.xlsx`. Agrupa bloques por medida de hoja y espesor, y separa los bloques que contienen varios codigos o varios espesores.",
    "",
    f"- Bloques analizados: **{len(orders)}**",
    f"- Bloques mixtos por codigo o espesor: **{len(mixed)}**",
    f"- Bloques de un solo codigo y espesor: **{len(orders) - len(mixed)}**",
    "",
    "## Agrupacion por hoja y espesor",
    "",
    "| Hoja | Espesores | Bloques | Piezas | Validados | Sin estado |",
    "|---|---|---:|---:|---:|---:|",
]
for (sheet, thicknesses), values in sorted(groups.items()):
    sheet_text = f"{sheet[0]:g} x {sheet[1]:g} m"
    thickness_text = ", ".join(f"{value:g} mm" for value in thicknesses)
    lines.append(f"| {sheet_text} | {thickness_text} | {values['bloques']} | {values['piezas']} | {values['validas']} | {values['sin_estado']} |")

lines.extend([
    "",
    "## Bloques mixtos para planeacion",
    "",
    "Estos bloques deben conservarse como pedidos compuestos. El planeador debe separarlos internamente por espesor y medida de hoja, pero no perder la relacion con el pedido original.",
    "",
    "| Hoja | Fila | Espesores | Codigos y piezas | Estado |",
    "|---|---:|---|---|---|",
])
for order, codes, thicknesses in mixed:
    sheet_text = f"{float(order.get('hoja_largo_m', 0)):g} x {float(order.get('hoja_ancho_m', 0)):g} m"
    thickness_text = ", ".join(f"{value:g} mm" for value in thicknesses)
    codes_text = "; ".join(f"{code} ({quantity} piezas)" for code, quantity in sorted(codes.items()))
    status = order.get("estatus_origen") or "Sin estado"
    lines.append(f"| {order.get('hoja_origen', '')} | {order.get('fila_inicio', '')} | {thickness_text} / {sheet_text} | {codes_text} | {status} |")

lines.extend([
    "",
    "## Regla para el modulo",
    "",
    "- Un bloque con un solo espesor puede alimentar una plantilla de pedido comparable.",
    "- Un bloque mixto debe conservar sus partidas, pero generar calculo visual separado por espesor y por hoja.",
    "- Los bloques con 1.8 x 2.6 m deben conservar la orientacion original aunque el area sea igual a 2.6 x 1.8 m.",
    "- Las cantidades historicas son evidencia de compra; no sustituyen automaticamente la receta vigente del perfil.",
])
output.write_text("\n".join(lines) + "\n", encoding="utf-8")
print(json.dumps({"archivo": str(output), "bloques": len(orders), "mixtos": len(mixed)}, ensure_ascii=False))
