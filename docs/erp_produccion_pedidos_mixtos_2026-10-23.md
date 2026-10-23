# Analisis de pedidos mixtos historicos

Analisis de solo lectura sobre `Pedidos Peceras.xlsx`. Agrupa bloques por medida de hoja y espesor, y separa los bloques que contienen varios codigos o varios espesores.

- Bloques analizados: **66**
- Bloques mixtos por codigo o espesor: **22**
- Bloques de un solo codigo y espesor: **44**

## Agrupacion por hoja y espesor

| Hoja | Espesores | Bloques | Piezas | Validados | Sin estado |
|---|---|---:|---:|---:|---:|
| 1.8 x 2.6 m | 6 mm | 5 | 9 | 0 | 5 |
| 2.6 x 1.8 m | 3 mm | 36 | 213 | 36 | 0 |
| 3.6 x 2.6 m | 3 mm, 6 mm | 3 | 30 | 2 | 1 |
| 3.6 x 2.6 m | 6 mm | 22 | 184 | 0 | 22 |

## Bloques mixtos para planeacion

Estos bloques deben conservarse como pedidos compuestos. El planeador debe separarlos internamente por espesor y medida de hoja, pero no perder la relacion con el pedido original.

| Hoja | Fila | Espesores | Codigos y piezas | Estado |
|---|---:|---|---|---|
| Pedido 2 | 38 | 3 mm, 6 mm / 3.6 x 2.6 m | PEC-3050 (5 piezas); PEC-T6030 (5 piezas) | Sin estado |
| Pedido 2 | 63 | 6 mm / 3.6 x 2.6 m | PEC-6050 (5 piezas); PEC-6060 (5 piezas) | Sin estado |
| Pedido 2 | 75 | 6 mm / 3.6 x 2.6 m | PEC-12250 (5 piezas); PEC-4060 (5 piezas) | Sin estado |
| Pedido 2 | 87 | 6 mm / 3.6 x 2.6 m | PEC-10030 (5 piezas); PEC-10050 (5 piezas) | Sin estado |
| Pedido 2-3mm | 28 | 3 mm / 2.6 x 1.8 m | PEC-2020 (5 piezas); PEC-4025 (5 piezas) | validada |
| Pedido 2-3mm | 39 | 3 mm / 2.6 x 1.8 m | PEC-1515 (5 piezas); PERA-B4020L (2 piezas); PERA-C2020L (2 piezas); PERA-C2020P (1 piezas); PERA-C2040L (2 piezas); PERA-C2040P (1 piezas) | validada |
| Pedido 2-6mm | 3 | 6 mm / 3.6 x 2.6 m | PEC-10050 (5 piezas); PEC-12250 (5 piezas); PEC-5050 (5 piezas) | Sin estado |
| Pedido 2-6mm | 27 | 6 mm / 3.6 x 2.6 m | PEC-5050 (5 piezas); PEC-C4040 (5 piezas); PEC-C4050 (5 piezas) | Sin estado |
| Pedido 2-6mm | 42 | 6 mm / 3.6 x 2.6 m | PEC-12240 (5 piezas); PEC-4060 (5 piezas) | Sin estado |
| Pedido 2-6mm | 53 | 6 mm / 3.6 x 2.6 m | PEC-6050 (5 piezas); PEC-6060 (5 piezas) | Sin estado |
| Pedido 3 | 61 | 3 mm / 2.6 x 1.8 m | PERA-B4020L (2 piezas); PERA-C2020L (2 piezas); PERA-C2020P (1 piezas); PERA-C2040L (2 piezas); PERA-C2040P (1 piezas) | validada |
| Pedido 3 | 89 | 3 mm, 6 mm / 3.6 x 2.6 m | TORO-C4040 (5 piezas); TORO-H4040 (5 piezas) | validada |
| Pedido 3 | 100 | 3 mm / 2.6 x 1.8 m | TORO-2040CFR (2 piezas); TORO-2040CL (2 piezas); TORO-2040CP (1 piezas); TORO-20HL (6 piezas); TORO-20HP (1 piezas) | validada |
| Pedido 4 | 10 | 3 mm / 2.6 x 1.8 m | PEC-4040 (5 piezas); PERA-C2040L (2 piezas); PERA-C2040P (1 piezas) | validada |
| Pedido 4 | 22 | 3 mm, 6 mm / 3.6 x 2.6 m | TORO-C4040 (5 piezas); TORO-H4040 (5 piezas) | validada |
| Pedido 4 | 34 | 3 mm / 2.6 x 1.8 m | TORO-2040CFR (2 piezas); TORO-2040CL (2 piezas); TORO-2040CP (1 piezas); TORO-20HL (6 piezas); TORO-20HP (1 piezas) | validada |
| Pedido 4 | 71 | 6 mm / 3.6 x 2.6 m | PEC-10030 (5 piezas); PEC-10050 (5 piezas) | Sin estado |
| Pedido 4 | 82 | 6 mm / 3.6 x 2.6 m | PEC-12250 (5 piezas); PEC-5060 (5 piezas) | Sin estado |
| Pedido 5 | 3 | 6 mm / 3.6 x 2.6 m | PEC-12250 (5 piezas); PEC-4060 (1 piezas); PEC-5060 (3 piezas) | Sin estado |
| Pedido 5 | 12 | 6 mm / 3.6 x 2.6 m | PEC-10030 (5 piezas); PEC-10050 (5 piezas) | Sin estado |
| Pedido 6 | 2 | 6 mm / 3.6 x 2.6 m | PEC-10040 (5 piezas); PEC-12250 (5 piezas); PEC-B10025 (6 piezas) | Sin estado |
| Pedido 11 | 26 | 6 mm / 3.6 x 2.6 m | PEC-10040 (5 piezas); PEC-12250 (5 piezas) | Sin estado |

## Regla para el modulo

- Un bloque con un solo espesor puede alimentar una plantilla de pedido comparable.
- Un bloque mixto debe conservar sus partidas, pero generar calculo visual separado por espesor y por hoja.
- Los bloques con 1.8 x 2.6 m deben conservar la orientacion original aunque el area sea igual a 2.6 x 1.8 m.
- Las cantidades historicas son evidencia de compra; no sustituyen automaticamente la receta vigente del perfil.
