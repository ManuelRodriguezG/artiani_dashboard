# Frecuencia historica por perfil

Analisis de solo lectura. Cuenta en que bloques aparece cada perfil relacionado por codigo o alias, cuantas piezas contiene el bloque y que hojas/espesores se observaron.

- Bloques analizados: **66**
- Perfiles relacionados: **28**
- Codigos sin relacion automatica: **2**

| Perfil | Relacion | Bloques | Piezas en bloques | Validados | Hojas observadas | Espesores observados |
|---|---|---:|---:|---:|---|---|
| PERA-5030 | Alias automatizado | 15 | 75 | 15 | 2.6x1.8 m (15) | 3 mm (75) |
| PERA-P5030 | Alias automatizado | 9 | 23 | 0 | 3.6x2.6 m (5), 1.8x2.6 m (4) | 6 mm (23) |
| PERA-12050 | Alias automatizado | 6 | 30 | 0 | 3.6x2.6 m (6) | 6 mm (30) |
| PERA-C3050 | Alias automatizado | 6 | 30 | 5 | 2.6x1.8 m (5), 3.6x2.6 m (1) | 3 mm (30) |
| PERA-4025 | Alias automatizado | 5 | 25 | 5 | 2.6x1.8 m (5) | 3 mm (25) |
| TORO-6030 | Alias automatizado | 5 | 25 | 0 | 3.6x2.6 m (5) | 6 mm (25) |
| PERA-10050 | Alias automatizado | 4 | 20 | 0 | 3.6x2.6 m (4) | 6 mm (20) |
| PERA-10030 | Alias automatizado | 3 | 15 | 0 | 3.6x2.6 m (3) | 6 mm (15) |
| PERA-1515 | Alias automatizado | 3 | 15 | 3 | 2.6x1.8 m (3) | 3 mm (15) |
| PERA-3020 | Alias automatizado | 3 | 15 | 3 | 2.6x1.8 m (3) | 3 mm (15) |
| PERA-C2240 | Alias automatizado | 3 | 9 | 3 | 2.6x1.8 m (3) | 3 mm (9) |
| PERA-C4040 | Manual confirmada | 3 | 15 | 2 | 3.6x2.6 m (3) | 3 mm (10), 6 mm (5) |
| PERA-C4060 | Alias automatizado | 3 | 11 | 0 | 3.6x2.6 m (3) | 6 mm (11) |
| PERA-1004040 | Manual confirmada | 2 | 10 | 0 | 3.6x2.6 m (2) | 6 mm (10) |
| PERA-2020 | Alias automatizado | 2 | 10 | 2 | 2.6x1.8 m (2) | 3 mm (10) |
| PERA-5050 | Alias automatizado | 2 | 10 | 0 | 3.6x2.6 m (2) | 6 mm (10) |
| PERA-5060 | Alias automatizado | 2 | 8 | 0 | 3.6x2.6 m (2) | 6 mm (8) |
| PERA-6050 | Alias automatizado | 2 | 10 | 0 | 3.6x2.6 m (2) | 6 mm (10) |
| PERA-6060 | Alias automatizado | 2 | 10 | 0 | 3.6x2.6 m (2) | 6 mm (10) |
| PERA-B4020 | Alias automatizado | 2 | 4 | 2 | 2.6x1.8 m (2) | 3 mm (4) |
| PERA-C2040 | Manual confirmada | 2 | 10 | 2 | 2.6x1.8 m (2) | 3 mm (10) |
| PERA-C2222 | Alias automatizado | 2 | 6 | 2 | 2.6x1.8 m (2) | 3 mm (6) |
| PERA-H4040 | Manual confirmada | 2 | 10 | 2 | 3.6x2.6 m (2) | 6 mm (10) |
| PERA-12040 | Alias automatizado | 1 | 5 | 0 | 3.6x2.6 m (1) | 6 mm (5) |
| PERA-4040 | Alias automatizado | 1 | 5 | 1 | 2.6x1.8 m (1) | 3 mm (5) |
| PERA-8050 | Alias automatizado | 1 | 5 | 0 | 1.8x2.6 m (1) | 6 mm (5) |
| PERA-B10025 | Alias automatizado | 1 | 6 | 0 | 3.6x2.6 m (1) | 6 mm (6) |
| PERA-C4050 | Alias automatizado | 1 | 5 | 0 | 3.6x2.6 m (1) | 6 mm (5) |

## Codigos sin relacion automatica

- TORO-20HL: 12 piezas historicas
- TORO-20HP: 2 piezas historicas

## Uso en el planeador

- Priorizar primero los perfiles con mas bloques historicos para validar el acomodo visual contra el proveedor.
- Mantener separadas las variantes de hoja y espesor; la frecuencia no autoriza sustituir una receta confirmada.
- Usar los bloques mixtos como combinaciones de pedido, no como una receta unica de pecera.
- Las relaciones manuales confirmadas se documentan en `docs/erp_produccion_relaciones_historicas_confirmadas_2026-10-23.md`; no crean perfiles ni sustituyen recetas.
