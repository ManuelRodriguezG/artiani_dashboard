import json
from collections import Counter, defaultdict
from pathlib import Path

root = Path(r"C:\xampp\htdocs\panel_de_control")
preview = json.loads((root / "docs" / "erp_produccion_pedidos_vidrio_preview_2026-10-22.json").read_text(encoding="utf-8"))
profiles = json.loads((root / "docs" / "erp_produccion_perfiles_import_payload_2026-10-22.json").read_text(encoding="utf-8"))

def norm(value):
    return "".join(char.lower() for char in str(value or "") if char.isalnum())

aliases = {}
for profile in profiles:
    data = profile.get("datos", {})
    code = data.get("codigo")
    if code:
        aliases[norm(code)] = code
    for alias in data.get("alias_historicos") or []:
        if alias:
            aliases[norm(alias)] = code

manual_relations = {
    norm("TORO-H4040"): "PERA-H4040",
    norm("TORO-C4040"): "PERA-C4040",
    norm("Pec-10040"): "PERA-1004040",
    norm("TORO-2040CL"): "PERA-C2040",
    norm("TORO-2040CFR"): "PERA-C2040",
    norm("TORO-2040CP"): "PERA-C2040",
    norm("TORO-20HL"): "PERA-H3820",
    norm("TORO-20HP"): "PERA-H3820",
}

stats = defaultdict(lambda: {"bloques": 0, "piezas": 0, "hojas": Counter(), "espesores": Counter(), "validos": 0})
unmatched = Counter()
manual_profiles = set()
for order in preview.get("pedidos", []):
    pieces_by_code = defaultdict(list)
    for piece in order.get("piezas", []):
        raw = str(piece.get("codigo_crudo", ""))
        code = aliases.get(norm(raw)) or manual_relations.get(norm(raw))
        if not code:
            unmatched[raw] += int(piece.get("cantidad", 0) or 0)
            continue
        pieces_by_code[code].append(piece)
        if norm(raw) in manual_relations:
            manual_profiles.add(code)
    for code, code_pieces in pieces_by_code.items():
        item = stats[code]
        item["bloques"] += 1
        item["piezas"] += sum(int(piece.get("cantidad", 0) or 0) for piece in code_pieces)
        sheet = f"{float(order.get('hoja_largo_m', 0)):g}x{float(order.get('hoja_ancho_m', 0)):g} m"
        item["hojas"][sheet] += 1
        for piece in code_pieces:
            item["espesores"][float(piece.get("espesor_mm", 0) or 0)] += int(piece.get("cantidad", 0) or 0)
        if str(order.get("estatus_origen", "")).lower() == "validada":
            item["validos"] += 1

rows = sorted(stats.items(), key=lambda pair: (-pair[1]["bloques"], pair[0]))
output = root / "docs" / "erp_produccion_frecuencia_perfiles_historicos_2026-10-23.md"
lines = [
    "# Frecuencia historica por perfil",
    "",
    "Analisis de solo lectura. Cuenta en que bloques aparece cada perfil relacionado por codigo o alias, cuantas piezas contiene el bloque y que hojas/espesores se observaron.",
    "",
    f"- Bloques analizados: **{len(preview.get('pedidos', []))}**",
    f"- Perfiles relacionados: **{len(rows)}**",
    f"- Codigos sin relacion automatica: **{len(unmatched)}**",
    "",
    "| Perfil | Relacion | Bloques | Piezas en bloques | Validados | Hojas observadas | Espesores observados |",
    "|---|---|---:|---:|---:|---|---|",
]
for code, item in rows:
    sheets = ", ".join(f"{sheet} ({count})" for sheet, count in item["hojas"].most_common())
    thicknesses = ", ".join(f"{value:g} mm ({count})" for value, count in sorted(item["espesores"].items()))
    relation = "Manual confirmada" if code in manual_profiles else "Alias automatizado"
    lines.append(f"| {code} | {relation} | {item['bloques']} | {item['piezas']} | {item['validos']} | {sheets} | {thicknesses} |")

lines.extend(["", "## Codigos sin relacion automatica", ""])
if unmatched:
    lines.extend(f"- {code}: {count} piezas historicas" for code, count in unmatched.most_common())
else:
    lines.append("No hay codigos pendientes en este corte del analisis.")
lines.extend([
    "",
    "## Uso en el planeador",
    "",
    "- Priorizar primero los perfiles con mas bloques historicos para validar el acomodo visual contra el proveedor.",
    "- Mantener separadas las variantes de hoja y espesor; la frecuencia no autoriza sustituir una receta confirmada.",
    "- Usar los bloques mixtos como combinaciones de pedido, no como una receta unica de pecera.",
    "- Las relaciones manuales confirmadas se documentan en `docs/erp_produccion_relaciones_historicas_confirmadas_2026-10-23.md`; no crean perfiles ni sustituyen recetas.",
])
output.write_text("\n".join(lines) + "\n", encoding="utf-8")
print(json.dumps({"archivo": str(output), "perfiles": len(rows), "sin_relacion": len(unmatched)}, ensure_ascii=False))
