import json
from collections import Counter, defaultdict
from pathlib import Path

root = Path(r"C:\xampp\htdocs\panel_de_control")
preview = json.loads((root / "docs" / "erp_produccion_pedidos_vidrio_preview_2026-10-22.json").read_text(encoding="utf-8"))
profiles = json.loads((root / "docs" / "erp_produccion_perfiles_import_payload_2026-10-22.json").read_text(encoding="utf-8"))

def norm(value):
    return "".join(ch.lower() for ch in str(value or "") if ch.isalnum())

def profile_for_code(code):
    code_norm = norm(code)
    for profile in profiles:
        data = profile.get("datos", {})
        aliases = [data.get("codigo"), *(data.get("alias_historicos") or [])]
        if code_norm in {norm(alias) for alias in aliases}:
            return data.get("codigo")
    return None

def signature(piece):
    return (
        piece.get("tipo_crudo", ""),
        round(float(piece.get("largo_cm", 0)), 2),
        round(float(piece.get("ancho_cm", 0)), 2),
        round(float(piece.get("espesor_mm", 0)), 2),
    )

groups = defaultdict(lambda: {"bloques": 0, "piezas": Counter(), "hojas": Counter(), "fuentes": []})
mixtos = []
pendientes = Counter()
for order in preview["pedidos"]:
    codes = sorted({item["codigo_crudo"] for item in order["piezas"]})
    masters = sorted({profile_for_code(code) for code in codes if profile_for_code(code)})
    unknown = sorted(code for code in codes if not profile_for_code(code))
    if len(codes) > 1 or len(masters) > 1:
        mixtos.append((order, masters, unknown))
        continue
    if not masters:
        for code in codes:
            pendientes[code] += 1
        continue
    master = masters[0]
    group = groups[master]
    group["bloques"] += 1
    group["hojas"][f'{order["hoja_largo_m"]}x{order["hoja_ancho_m"]} m'] += 1
    group["fuentes"].append(f'{order["hoja_origen"]} fila {order["fila_inicio"]}')
    for piece in order["piezas"]:
        group["piezas"][signature(piece)] += piece["cantidad"]

output = root / "docs" / "erp_produccion_variantes_por_perfil_2026-10-22.md"
lines = [
    "# Variantes historicas agrupadas por perfil",
    "",
    "Analisis de solo lectura. Agrupa pedidos de un solo perfil para preparar recetas historicas completas; no modifica perfiles ni base de datos.",
    "",
    f"- Perfiles con bloques agrupables: **{len(groups)}**",
    f"- Bloques mixtos separados para revision: **{len(mixtos)}**",
    f"- Codigos sin relacion automatica: **{sum(pendientes.values())} bloques**",
    "",
]
for master in sorted(groups):
    group = groups[master]
    lines.extend([
        f"## {master}",
        "",
        f'- Bloques historicos: **{group["bloques"]}**',
        f'- Hojas observadas: {", ".join(f"{key} ({count})" for key, count in group["hojas"].items())}',
        "",
        "| Pieza historica | Largo cm | Ancho cm | Espesor mm | Apariciones |",
        "|---|---:|---:|---:|---:|",
    ])
    for (piece_type, largo, ancho, espesor), count in sorted(group["piezas"].items()):
        lines.append(f"| {piece_type} | {largo} | {ancho} | {espesor} | {count} |")
    lines.append("")

lines.extend(["## Bloques mixtos", "", "| Hoja | Fila | Maestros detectados | Codigos pendientes |", "|---|---:|---|---|"])
for order, masters, unknown in mixtos:
    codes = ", ".join(sorted({item["codigo_crudo"] for item in order["piezas"]}))
    lines.append(
        f'| {order["hoja_origen"]} | {order["fila_inicio"]} | '
        f'{", ".join(masters) or "Ninguno"} | {", ".join(unknown) or "Ninguno"} |'
    )
lines.extend(["", "## Codigos pendientes", ""])
for code, count in pendientes.most_common():
    lines.append(f"- {code}: {count} bloque(s)")

output.write_text("\n".join(lines) + "\n", encoding="utf-8")
print(json.dumps({"archivo": str(output), "perfiles": len(groups), "mixtos": len(mixtos), "pendientes": sum(pendientes.values())}, ensure_ascii=False))
