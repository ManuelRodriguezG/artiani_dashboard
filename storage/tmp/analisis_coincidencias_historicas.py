import json
from collections import Counter
from pathlib import Path

root = Path(r"C:\xampp\htdocs\panel_de_control")
preview = json.loads((root / "docs" / "erp_produccion_pedidos_vidrio_preview_2026-10-22.json").read_text(encoding="utf-8"))
profiles = json.loads((root / "docs" / "erp_produccion_perfiles_import_payload_2026-10-22.json").read_text(encoding="utf-8"))

def norm(value):
    return "".join(ch.lower() for ch in str(value or "") if ch.isalnum())

def piece_type(value):
    value = norm(value)
    if value.startswith("later"):
        return "lateral"
    if "frontal" in value or "respaldo" in value:
        return "frontal_respaldo"
    return value

def signature(item):
    return (
        piece_type(item.get("tipo_crudo", item.get("nombre"))),
        round(float(item.get("largo_cm", item.get("largo", 0))), 2),
        round(float(item.get("ancho_cm", item.get("ancho", 0))), 2),
        round(float(item.get("espesor_mm", item.get("espesor", 0))), 2),
    )

def profile_matches(profile, code):
    data = profile.get("datos", {})
    aliases = [data.get("codigo"), *(data.get("alias_historicos") or [])]
    return norm(code) in {norm(alias) for alias in aliases}

rows = []
matched = 0
unmatched = Counter()
for order in preview["pedidos"]:
    codes = sorted({item["codigo_crudo"] for item in order["piezas"]})
    profile = next((item for item in profiles if any(profile_matches(item, code) for code in codes)), None)
    active = [signature(piece) for piece in profile.get("piezas", [])] if profile else []
    differences = []
    for item in order["piezas"]:
        if signature(item) not in active:
            differences.append(
                f'{item["tipo_crudo"]} {item["largo_cm"]}x{item["ancho_cm"]} cm {item["espesor_mm"]} mm'
            )
    if profile:
        matched += 1
    else:
        for code in codes:
            unmatched[code] += 1
    rows.append({
        "hoja": order["hoja_origen"],
        "fila": order["fila_inicio"],
        "codigo_historico": ", ".join(codes),
        "perfil_maestro": profile["datos"].get("codigo") if profile else "",
        "coincide_receta_activa": bool(profile) and not differences,
        "diferencias": differences,
        "piezas": order["piezas_total"],
        "espesores": order["espesores_mm"],
        "hoja_m": [order["hoja_largo_m"], order["hoja_ancho_m"]],
    })

output = root / "docs" / "erp_produccion_coincidencias_historicas_2026-10-22.md"
lines = [
    "# Coincidencias entre pedidos historicos y perfiles activos",
    "",
    "Analisis de solo lectura. Compara cada bloque del preview con el payload de perfiles importados; no cambia perfiles ni base de datos.",
    "",
    f'- Bloques analizados: **{len(rows)}**',
    f'- Bloques relacionados con un perfil maestro: **{matched}**',
    f'- Bloques sin relacion automatica: **{len(rows) - matched}**',
    "",
    "## Detalle",
    "",
    "| Hoja | Fila | Codigo historico | Perfil maestro | Receta coincide | Diferencias |",
    "|---|---:|---|---|---|---|",
]
for row in rows:
    differences = "; ".join(row["diferencias"][:3]) or "Sin diferencias detectadas"
    lines.append(
        f'| {row["hoja"]} | {row["fila"]} | {row["codigo_historico"]} | '
        f'{row["perfil_maestro"] or "Pendiente"} | '
        f'{"Si" if row["coincide_receta_activa"] else "Revisar"} | {differences} |'
    )
lines.extend([
    "",
    "## Codigos sin relacion automatica",
    "",
    *[f"- {code}: {count} bloque(s)" for code, count in unmatched.most_common()],
])
output.write_text("\n".join(lines) + "\n", encoding="utf-8")
print(json.dumps({"archivo": str(output), "bloques": len(rows), "relacionados": matched}, ensure_ascii=False))
