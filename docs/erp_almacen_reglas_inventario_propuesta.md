# ERP Almacen - Propuesta de reglas de inventario por SKU

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-18  
Estado: Piloto aplicado en 5 SKUs; no masificar sin respaldo externo y autorizacion.

## Objetivo

Definir reglas robustas para que Recepciones pueda exigir lote, caducidad, serie/etiqueta individual y estrategia de salida segun el tipo real de producto.

La regla debe vivir en `erp_catalogo_sku_reglas_inventario`, porque Recepciones consulta esas banderas por `id_sku_erp` y el backend las valida antes de crear lotes, existencias, movimientos o unidades.

## Politica por area responsable

En un ERP bien hecho, Almacen no decide libremente si un producto lleva lote, caducidad, serie o etiqueta interna. Almacen ejecuta la politica definida por Catalogo.

Responsabilidades:

| Area | Debe saber/decidir | No deberia decidir |
| --- | --- | --- |
| Catalogo ERP | Si el SKU controla inventario, lote, caducidad, serie fabricante, etiqueta de trazabilidad, estrategia de salida y reglas de venta. | Cantidades realmente recibidas en una orden. |
| Compras | Que se pidio, a quien, costo, proveedor y condiciones. | Existencia disponible o etiquetas fisicas. |
| Almacen | Que llego, cuantas piezas, lote/caducidad capturados, ubicacion, incidencias y aplicar etiquetas que Catalogo exige. | Cambiar la politica base del SKU sin autorizacion. |
| Inventario | Existencias, movimientos, unidades, lotes y estados. | Decidir si un SKU deberia caducar o llevar etiqueta. |
| Ventas | Escanear/asociar unidad cuando Catalogo lo exige. | Omitir etiqueta/serie obligatoria sin excepcion controlada. |

Regla UX:

- No usar un checkbox libre de "Etiquetas" en Recepcion como si Almacen decidiera.
- Mostrar en Recepcion la regla que viene de Catalogo: `Etiqueta trazabilidad`, `Serie fabricante`, `Lote`, `Caducidad`.
- La impresion/pegado de etiqueta debe ser una accion operativa separada.
- Si algun dia se permite una excepcion desde Almacen, debe llamarse "Etiqueta excepcional", pedir motivo, requerir permiso y dejar auditoria/incidencia.

Lote y caducidad:

- La obligatoriedad debe venir de Catalogo por SKU/familia/categoria.
- Recepcion debe poder capturar lote/caducidad si el proveedor lo trae, incluso cuando no sea obligatorio, siempre que no estorbe el flujo.
- Si Catalogo marca `requiere_lote=1` o `requiere_caducidad=1`, Recepcion debe bloquear guardado sin esos datos.
- Si un producto normalmente no caduca, no conviene obligar caducidad solo porque un proveedor mande un codigo de lote ocasional.
- Si Almacen detecta que un SKU deberia llevar lote/caducidad pero Catalogo no lo exige, debe generar incidencia/revision para Catalogo, no cambiarlo informalmente.

## Auditoria actual

Consulta de solo lectura del 2026-06-18:

- Reglas existentes en `erp_catalogo_sku_reglas_inventario`: 1744.
- SKUs con `requiere_lote=1`: 0.
- SKUs con `requiere_caducidad=1`: 0.
- SKUs con `requiere_serie=1`: 0.

Clasificacion textual preliminar, no definitiva:

| Categoria sugerida | SKUs candidatos | Regla ERP recomendada |
| --- | ---: | --- |
| `caducidad_lote_probable` | 593 | Exigir lote y caducidad; salida FEFO/FIFO |
| `serie_o_garantia_probable` | 109 | Evaluar serie solo si se capturara codigo unico por pieza |
| `sin_control_probable` | 1042 | Control normal de existencia, sin lote/caducidad/serie |

`REC-OC-20` contiene candidatos claros:

| SKU | Producto | Categoria sugerida |
| --- | --- | --- |
| `TP-7838` | Alimento monte verde para periquitos australianos 500 gr tropifit | `caducidad_lote_probable` |
| `TP-7840` | Alimento monte verde para cacatuas, loros y guacamayas 500 gr tropifit | `caducidad_lote_probable` |
| `SFF-03` | Alimento premium para pez goldfish 90 grs | `caducidad_lote_probable` |
| `SFF-303` | ALIMENTO SUNNY IMPORTADO GOLDFISH PELLETS FLOTANTES 300g (by TROPICAL) | `caducidad_lote_probable` |
| `TP-40372` | Alimento churro blanco para peces agranel | `caducidad_lote_probable` |

## Tipos recomendados y por que

### Producto fisico normal

Ejemplos:

- Accesorios simples.
- Jaulas.
- Bebederos.
- Repuestos sin vencimiento.

Regla:

- `controla_inventario=1`
- `requiere_lote=0`
- `requiere_caducidad=0`
- `requiere_serie=0`
- `permite_existencia_negativa=0`
- `estrategia_salida='FIFO'`

Por que:

- Son existencias vendibles, pero no necesitan trazabilidad por vencimiento ni serial individual.
- FIFO mantiene una rotacion simple sin meter captura extra al almacenista.

### Consumible con vencimiento

Ejemplos:

- Alimentos.
- Medicamentos.
- Vitaminas o suplementos.
- Quimicos, acondicionadores o tratamientos.
- Producto a granel que se recibe por lote proveedor.

Regla:

- `controla_inventario=1`
- `requiere_lote=1`
- `requiere_caducidad=1`
- `requiere_serie=0`
- `permite_existencia_negativa=0`
- `estrategia_salida='FEFO'` si el sistema soporta salida por caducidad; si aun no, usar `FIFO` temporalmente y documentar deuda.
- `dias_alerta_caducidad=90`
- `dias_minimos_recepcion=30`

Por que:

- Permite rastrear compras/proveedores ante caducidad, devoluciones o incidencias sanitarias.
- Evita recibir mercancia vencida o demasiado proxima a vencer sin generar incidencia.
- FEFO es lo correcto para caducidad: sale primero lo que vence primero.

### Equipo con serie o garantia

Ejemplos:

- Bombas.
- Filtros de alto valor.
- Lamparas/electronicos.
- Calentadores o motores.

Regla recomendada por fases:

- Fase actual conservadora:
  - `controla_inventario=1`
  - `requiere_lote=0`
  - `requiere_caducidad=0`
  - `requiere_serie=0`
  - `estrategia_salida='FIFO'`
- Fase robusta cuando se quiera trazabilidad por pieza:
  - `requiere_serie=1`
  - La recepcion debe capturar o generar un codigo unico por unidad.

Por que:

- La serie ayuda a garantias, devoluciones y rastreo por pieza.
- Pero activarla sin proceso operativo claro vuelve lenta la recepcion y puede bloquear entradas. Conviene implementarla cuando tambien exista consulta/impresion/venta por serie.

### Etiqueta de trazabilidad interna

Ejemplos:

- Productos que tambien venden otros negocios y que conviene identificar como vendidos por la empresa.
- Equipos sin serie de fabricante, pero con riesgo de devolucion/cambio fraudulento.
- Productos con garantia propia de tienda.
- Productos de valor medio/alto donde conviene rastrear unidad vendida.

Regla recomendada:

- No confundir con `requiere_serie`.
- Mantener `requiere_serie=0` si el producto no trae numero de serie real de fabricante.
- Usar una regla separada tipo `generar_etiqueta_individual=1` o `generar_etiqueta_interna=1`.
- Al recibir, generar un `codigo_unico` interno por pieza en `erp_inventario_unidades`.
- Al vender, asociar ese `codigo_unico` a la venta/detalle de venta.
- En devoluciones/garantias, validar que el codigo pertenece a la empresa y que estuvo vendido.

Por que:

- La serie de fabricante identifica una unidad que ya viene marcada por proveedor/fabricante.
- La etiqueta interna identifica una unidad marcada por la empresa para probar origen/venta propia.
- Separarlas evita bloquear recepciones por esperar una serie que no existe.
- Permite usar etiquetas dificiles de retirar sin afirmar que el producto trae serie oficial.

Configuracion correcta:

- La decision base debe vivir en Catalogo ERP/SKU, no en Almacen.
- Almacen solo ejecuta la regla al recibir: imprime/genera etiqueta si el SKU lo pide.
- Inventario conserva la unidad individual y su estatus.
- Ventas debe consumir o asociar la unidad al vender.
- Garantias/devoluciones debe consultar la unidad para validar origen.

Pendiente de implementacion:

- Separar explicitamente en esquema/UI:
  - `requiere_serie_fabricante`
  - `generar_etiqueta_interna`
  - `prefijo_etiqueta_interna`
  - `plantilla_etiqueta`
  - `adhesivo_seguridad` o nota operativa de etiqueta
- Definir formato de codigo interno y politica de impresion.
- Agregar consulta/escaneo de unidad en ventas y devoluciones antes de activar masivamente.

### Servicio, cargo o no inventariable

Ejemplos:

- Fletes.
- Servicios.
- Cargos adicionales.

Regla:

- `controla_inventario=0`

Por que:

- No debe preparar recepcion ni existencia.
- Compras puede pagarlo, pero Almacen no debe recibirlo.

## Recomendacion para avanzar

No activar reglas masivas por texto automaticamente. El texto ayuda a encontrar candidatos, pero no sustituye decision operativa.

Orden sugerido:

1. Crear preview exportable de candidatos `caducidad_lote_probable`.
2. Revisar primero los 5 SKUs de alimentos presentes en `REC-OC-20`.
3. Autorizar respaldo externo.
4. Activar lote/caducidad solo en esos SKUs piloto.
5. Ejecutar `UAT-ALM-006` en UI sobre `REC-OC-20` antes de guardar recepcion completa.
6. Si funciona, extender por familias/categorias con lotes pequenos y evidencia.

## SQL conceptual piloto

No ejecutar sin autorizacion.

```sql
UPDATE erp_catalogo_sku_reglas_inventario r
INNER JOIN erp_catalogo_skus s
    ON s.id_sku = r.id_sku
SET r.requiere_lote = 1,
    r.requiere_caducidad = 1,
    r.requiere_serie = 0,
    r.estrategia_salida = 'FEFO',
    r.dias_alerta_caducidad = 90,
    r.dias_minimos_recepcion = 30,
    r.fecha_actualizacion = NOW()
WHERE s.sku IN ('TP-7838', 'TP-7840', 'SFF-03', 'SFF-303', 'TP-40372');
```

## Punto de autorizacion

Antes de aplicar cualquier `UPDATE` sobre `erp_catalogo_sku_reglas_inventario` se requiere:

- Respaldo externo de BD o de las tablas afectadas.
- Preview de filas antes/despues.
- Autorizacion explicita del dueno.
- Prueba visual y tecnica posterior por folio.

## Piloto aplicado

Fecha: 2026-06-18.

- SKUs piloto: `TP-7838`, `TP-7840`, `SFF-03`, `SFF-303`, `TP-40372`.
- Respaldo de reglas: `artianilocal_panel_20260619_almacen_reglas_piloto_rec_oc_20.sql`.
- Respaldo antes de recepcion real: `artianilocal_panel_20260619_antes_uat_alm_006_rec_oc_20.sql`.
- Resultado de recepcion real: `REC-OC-20` recibio 1 unidad de `TP-7838` con lote y caducidad, creando lote, movimiento y existencia sin incidencias.

No se aplico masificacion a los 593 candidatos textuales.
