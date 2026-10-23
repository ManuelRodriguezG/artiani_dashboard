import re
from pathlib import Path

root = Path(r"C:\xampp\htdocs\panel_de_control")
source = (root / "docs" / "erp_produccion_recetas_candidatas_2026-10-23.md").read_text(encoding="utf-8")
sections = re.split(r"(?m)^## ", source)[1:]
rows = []
confirmados = {
    "PERA-4025": 3,
    "PERA-6030": 4,
    "PERA-8050": 6,
    "PERA-C3050": 3,
    "PERA-P5030": 4,
}

for section in sections:
    lines = section.splitlines()
    profile = lines[0].strip()
    candidate = []
    active = []
    mode = None
    metrics = []
    for line in lines:
        if line == "| Receta | Pieza | Medida | Espesor |":
            mode = "recipe"
        elif line == "| Hoja | Candidata | Activa |":
            mode = "metrics"
        elif line.startswith("| Candidata |") or line.startswith("| Activa |"):
            cells = [cell.strip() for cell in line.strip("|").split("|")]
            if len(cells) == 4:
                item = (cells[1], cells[2], cells[3])
                (candidate if cells[0] == "Candidata" else active).append(item)
        elif line.startswith("| Hoja |"):
            mode = "metrics"
        elif line.startswith("| ") and line.count("|") >= 5 and not line.startswith("|---"):
            cells = [cell.strip() for cell in line.strip("|").split("|")]
            if mode in ("candidate", "active") and len(cells) == 4:
                item = (cells[1], cells[2], cells[3])
                (candidate if mode == "candidate" else active).append(item)
            elif mode == "metrics" and len(cells) == 3:
                metrics.append(tuple(cells))
    if set(candidate) != set(active) and profile not in confirmados:
        rows.append({"perfil": profile, "candidate": candidate, "active": active, "metrics": metrics})

output = root / "docs" / "erp_produccion_revision_recetas_2026-10-23.md"
lines = [
    "# Revision de recetas candidatas",
    "",
    "Lista de perfiles donde la variante historica mas frecuente no coincide con la receta activa. Las diferencias ya confirmadas por el dueno se consideran aceptadas; no se modifica la base de datos desde este reporte.",
    "",
    f"- Perfiles que requieren confirmacion: **{len(rows)}**",
    f"- Diferencias historicas aceptadas: **{len(confirmados)} perfiles** (`PERA-4025`, `PERA-6030`, `PERA-8050`, `PERA-C3050`, `PERA-P5030`).",
    "",
]
for row in rows:
    lines.extend([
        f'## {row["perfil"]}',
        "",
        "### Candidata historica",
        "",
        "| Pieza | Medida | Espesor |",
        "|---|---|---:|",
    ])
    for item in row["candidate"]:
        lines.append(f"| {item[0]} | {item[1]} | {item[2]} |")
    lines.extend([
        "",
        "### Receta activa",
        "",
        "| Pieza | Medida | Espesor |",
        "|---|---|---:|",
    ])
    for item in row["active"]:
        lines.append(f"| {item[0]} | {item[1]} | {item[2]} |")
    lines.extend(["", "### Comparacion de hojas", "", "| Hoja | Candidata | Activa |", "|---|---|---|"])
    for metric in row["metrics"]:
        lines.append(f"| {metric[0]} | {metric[1]} | {metric[2]} |")
    lines.append("")

output.write_text("\n".join(lines) + "\n", encoding="utf-8")
print(f"{len(rows)} perfiles")
