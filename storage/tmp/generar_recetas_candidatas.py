import json
from collections import Counter, defaultdict
from pathlib import Path

root = Path(r"C:\xampp\htdocs\panel_de_control")
preview = json.loads((root / "docs" / "erp_produccion_pedidos_vidrio_preview_2026-10-22.json").read_text(encoding="utf-8"))
profiles = json.loads((root / "storage" / "tmp" / "perfiles_actuales_2026-10-22.json").read_text(encoding="utf-8"))

def norm(value):
    return "".join(ch.lower() for ch in str(value or "") if ch.isalnum())

def kind(value):
    value = norm(value)
    if value.startswith("later"):
        return "Lateral"
    if "frontal" in value:
        return "Frontal"
    if "respaldo" in value:
        return "Respaldo"
    return str(value or "Pieza")

def profile_for_code(code):
    code_norm = norm(code)
    for profile in profiles:
        data = profile.get("datos", {})
        aliases = [data.get("codigo"), *(data.get("alias_historicos") or [])]
        if code_norm in {norm(alias) for alias in aliases}:
            return profile
    return None

def signature(piece):
    return (
        kind(piece.get("tipo_crudo", piece.get("nombre"))),
        round(float(piece.get("largo_cm", piece.get("largo", 0))), 2),
        round(float(piece.get("ancho_cm", piece.get("ancho", 0))), 2),
        round(float(piece.get("espesor_mm", piece.get("espesor", 0))), 2),
    )

def expand(pieces):
    output = []
    for piece in pieces:
        for _ in range(max(1, int(piece.get("cantidad", 1)))):
            output.append(piece)
    return output

def bands(pieces, sheet_largo, sheet_ancho):
    rows = []
    for piece in sorted(pieces, key=lambda item: max(item[1], item[2]), reverse=True):
        largo, ancho = piece[1], piece[2]
        choices = [(largo, ancho), (ancho, largo)]
        placed = False
        for row in rows:
            for width, height in choices:
                if row["used"] + width <= sheet_largo and height <= row["height"]:
                    row["used"] += width
                    placed = True
                    break
            if placed:
                break
        if not placed:
            for width, height in choices:
                used_height = sum(row["height"] for row in rows)
                if used_height + height <= sheet_ancho:
                    rows.append({"height": height, "used": width})
                    placed = True
                    break
        if not placed:
            return None
    used_height = sum(row["height"] for row in rows)
    sheets = 1 if rows else 0
    return {"bandas": len(rows), "alto_usado": used_height, "hojas": sheets}

def metric(pieces, sheet_largo, sheet_ancho):
    sheet_area = sheet_largo * sheet_ancho
    piece_area = sum(item[1] * item[2] for item in pieces)
    placement = bands(pieces, sheet_largo, sheet_ancho)
    if not placement:
        return {"hojas": None, "desperdicio": None, "area_piezas": piece_area}
    sheet_count = placement["hojas"]
    return {
        "hojas": sheet_count,
        "desperdicio": round(max(0, 1 - piece_area / (sheet_area * sheet_count)) * 100, 1),
        "area_piezas": round(piece_area / 10000, 4),
        "bandas": placement["bandas"],
    }

single_orders = []
for order in preview["pedidos"]:
    codes = sorted({item["codigo_crudo"] for item in order["piezas"]})
    masters = [profile_for_code(code) for code in codes]
    masters = [profile for profile in masters if profile]
    if len(codes) == 1 and len(masters) == 1:
        single_orders.append((order, masters[0]))

groups = defaultdict(list)
for order, profile in single_orders:
    groups[profile["datos"]["codigo"]].append(order)

rows = []
for master, orders in sorted(groups.items()):
    profile = profile_for_code(master)
    signatures = Counter(signature(piece) for order in orders for piece in order["piezas"])
    candidate = []
    active = []
    for piece in profile.get("piezas", []):
        active.append((
            kind(piece.get("nombre")),
            float(piece.get("largo", 0)),
            float(piece.get("ancho", 0)),
            float(piece.get("espesor", 0)),
        ))
    for name in sorted({sig[0] for sig in signatures}):
        options = [(sig, count) for sig, count in signatures.items() if sig[0] == name]
        sig, _ = max(options, key=lambda item: item[1])
        quantity = max(1, sum(int(piece.get("cantidad", 1)) for piece in profile.get("piezas", []) if kind(piece.get("nombre")) == name))
        candidate.extend([sig] * quantity)
    sheet_sizes = sorted({
        (round(order["hoja_largo_m"] * 100), round(order["hoja_ancho_m"] * 100))
        for order in orders
        if order["hoja_largo_m"] and order["hoja_ancho_m"]
    })
    if not sheet_sizes:
        sheet_sizes = [(260, 180) if candidate and candidate[0][3] < 5.5 else (360, 260)]
    if any(piece[3] >= 5.5 for piece in candidate):
        sheet_sizes = [(260, 180), (360, 260)]
    rows.append({
        "perfil": master,
        "bloques": len(orders),
        "candidate": candidate,
        "active": active,
        "sheet_sizes": sheet_sizes,
        "metrics_candidate": {f"{largo}x{ancho}": metric(candidate, largo, ancho) for largo, ancho in sheet_sizes},
        "metrics_active": {f"{largo}x{ancho}": metric(active, largo, ancho) for largo, ancho in sheet_sizes},
    })

output = root / "docs" / "erp_produccion_recetas_candidatas_2026-10-23.md"
lines = [
    "# Recetas candidatas por frecuencia historica",
    "",
    "Analisis de solo lectura. La receta candidata toma la variante mas frecuente por tipo de pieza dentro de pedidos de un solo perfil. No sustituye la receta activa.",
    "",
    f"- Perfiles analizados: **{len(rows)}**",
    "- La merma aqui es geometrica por area de hoja; la vista visual del modulo sigue siendo la referencia operativa.",
    "",
]
for row in rows:
    lines.extend([
        f'## {row["perfil"]}',
        "",
        f'- Bloques individuales observados: **{row["bloques"]}**',
        "",
        "| Receta | Pieza | Medida | Espesor |",
        "|---|---|---|---:|",
    ])
    for label, recipe in (("Candidata", row["candidate"]), ("Activa", row["active"])):
        for piece in recipe:
            lines.append(f"| {label} | {piece[0]} | {piece[1]} x {piece[2]} cm | {piece[3]} mm |")
    lines.extend(["", "| Hoja | Candidata | Activa |", "|---|---|---|"])
    for size in row["metrics_candidate"]:
        candidate_metric = row["metrics_candidate"][size]
        active_metric = row["metrics_active"][size]
        c = "No cabe" if candidate_metric["hojas"] is None else f'{candidate_metric["hojas"]} hoja(s), {candidate_metric["desperdicio"]}%'
        a = "No cabe" if active_metric["hojas"] is None else f'{active_metric["hojas"]} hoja(s), {active_metric["desperdicio"]}%'
        lines.append(f"| {size} cm | {c} | {a} |")
    lines.append("")

output.write_text("\n".join(lines) + "\n", encoding="utf-8")
print(json.dumps({"archivo": str(output), "perfiles": len(rows)}, ensure_ascii=False))
