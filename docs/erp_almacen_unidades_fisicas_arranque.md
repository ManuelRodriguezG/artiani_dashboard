# ERP Almacen - ALM-UNIDAD-001 unidades fisicas cerradas

Fecha: 2026-06-25
Modulo: ERP > Almacen / Inventario / Preparacion-Empaque
Estado: auditoria y diseno previo a migracion

## Objetivo

Permitir que una etiqueta/unidad fisica recibida represente una unidad cerrada real con contenido base conocido. Esto debe permitir:

- vender una unidad cerrada completa en el futuro;
- abrir una unidad cerrada para preparacion/empaque;
- descontar inventario en unidad base;
- conservar trazabilidad por etiqueta, lote, caducidad, recepcion y preparacion.

## Contexto validado

UAT real:

- Recepcion: `REC-OC-25`
- SKU: `TP-40311`
- Compra recibida: `3` unidades fisicas
- Contenido por unidad: `4 kg`
- Inventario generado: `12 kg`
- Etiquetas generadas: `3`

Estado actual de etiquetas:

| id unidad | codigo | lote recepcion | existencia | contenido inferido |
| --- | --- | --- | --- | --- |
| 32 | `INV-00003-28-0001` | 28 | 32 | `4 kg` |
| 33 | `INV-00003-29-0001` | 29 | 33 | `4 kg` |
| 34 | `INV-00003-29-0002` | 29 | 33 | `4 kg` |

El contenido se puede inferir por:

```text
erp_almacen_recepciones_lotes.cantidad_base / erp_almacen_recepciones_lotes.cantidad_compra
```

Pero no esta persistido por etiqueta.

## Auditoria tecnica

### `erp_inventario_unidades`

Actualmente guarda:

- identidad/codigo unico;
- serie fabricante o etiqueta interna;
- producto/SKU;
- recepcion/lote/existencia;
- almacen/ubicacion;
- lote/caducidad;
- estatus;
- estado de impresion/pegado;
- origen;
- venta futura;
- observaciones.

No guarda:

- cantidad base original por unidad fisica;
- cantidad base disponible por unidad fisica;
- unidad base;
- estado fisico operativo de la unidad cerrada;
- preparacion que la abrio/consumio;
- movimiento de salida que consumio esa unidad.

### Recepcion

`Almacenes::generar_unidades_inventario()` crea una etiqueta por unidad capturada.

Problema:

- usa la cantidad capturada para contar etiquetas;
- liga cada etiqueta a lote/existencia;
- no guarda el contenido base por etiqueta.

En `REC-OC-25` esto funciona visualmente porque hay 3 etiquetas, pero no queda persistido que cada una equivale a `4 kg`.

### Preparacion/Empaque

Preparacion consume hoy por:

```text
erp_almacen_preparaciones.id_existencia_origen
erp_almacen_preparacion_consumos.id_existencia_inventario
```

No consume por:

```text
id_inventario_unidad
```

Impacto:

- puede consumir `4 kg` de una existencia;
- no puede marcar cual etiqueta/unidad fisica fue abierta;
- si hay varias etiquetas en la misma existencia, no hay trazabilidad exacta de la unidad fisica consumida.

### Inventario / Diagnosticos

Hay diagnosticos que comparan:

```text
existencia.cantidad vs COUNT(unidades)
```

Eso solo es valido si cada etiqueta equivale a `1` unidad base. Para granel o unidades cerradas con contenido, debe compararse contra suma de contenido base disponible de unidades.

## Hallazgos

### ALM-UNIDAD-H001 - Etiqueta sin contenido base propio

Cada etiqueta identifica la unidad fisica, pero no guarda cuantos kg/pza/litros representa.

Impacto: alto para venta de unidad cerrada y preparacion por etiqueta.

### ALM-UNIDAD-H002 - Preparacion no consume unidad fisica especifica

Preparacion consume una existencia, no una etiqueta.

Impacto: alto para trazabilidad cuando se abre una unidad cerrada.

### ALM-UNIDAD-H003 - Diagnostico unidades vs existencia asume 1 etiqueta = 1 unidad base

El diagnostico `diagnosticoUnidadesVsExistencia()` puede marcar falsas diferencias cuando una etiqueta representa 4 kg.

Impacto: medio-alto para auditoria de inventario.

### ALM-UNIDAD-H004 - Estado fisico de unidad cerrada no existe

`estatus` indica disponibilidad general y `estado_etiqueta` indica impresion/pegado, pero falta estado fisico:

- cerrada;
- abierta;
- consumida;
- vendida;
- cancelada.

Impacto: alto para operacion.

## Modelo recomendado

### Regla conceptual

Inventario contable:

```text
erp_inventario_existencias = saldo por SKU/lote/caducidad/ubicacion en unidad base
```

Unidad fisica:

```text
erp_inventario_unidades = identidad fisica etiquetada
```

Una unidad fisica puede representar:

- 1 pieza;
- 4 kg;
- 500 g;
- una presentacion preparada;
- una unidad con serie fabricante.

## Propuesta de estructura

No aplicada. Requiere respaldo externo y autorizacion.

### Opcion minima: columnas en `erp_inventario_unidades`

```sql
ALTER TABLE erp_inventario_unidades
  ADD COLUMN cantidad_base_original DECIMAL(18,6) NOT NULL DEFAULT 0.000000 AFTER fecha_caducidad,
  ADD COLUMN cantidad_base_disponible DECIMAL(18,6) NOT NULL DEFAULT 0.000000 AFTER cantidad_base_original,
  ADD COLUMN unidad_base VARCHAR(40) NULL AFTER cantidad_base_disponible,
  ADD COLUMN estado_fisico VARCHAR(30) NOT NULL DEFAULT 'cerrada' AFTER estado_etiqueta,
  ADD COLUMN id_preparacion_consumo INT NULL AFTER origen_detalle_id,
  ADD COLUMN id_movimiento_consumo INT NULL AFTER id_preparacion_consumo,
  ADD COLUMN fecha_consumo DATETIME NULL AFTER id_movimiento_consumo;
```

Estados permitidos sugeridos:

- `cerrada`
- `abierta`
- `consumida`
- `vendida`
- `cancelada`

### Opcion mas robusta: tabla de movimientos por unidad fisica

Si se quiere permitir consumo parcial de una misma etiqueta, conviene una tabla adicional:

```sql
CREATE TABLE erp_inventario_unidad_movimientos (
  id_unidad_movimiento INT NOT NULL AUTO_INCREMENT,
  id_inventario_unidad INT NOT NULL,
  tipo_movimiento VARCHAR(40) NOT NULL,
  origen_tipo VARCHAR(50) NULL,
  origen_id INT NULL,
  origen_detalle_id INT NULL,
  cantidad_base DECIMAL(18,6) NOT NULL,
  cantidad_base_anterior DECIMAL(18,6) NOT NULL,
  cantidad_base_nueva DECIMAL(18,6) NOT NULL,
  observaciones TEXT NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_unidad_movimiento),
  KEY idx_unidad_mov_unidad (id_inventario_unidad),
  KEY idx_unidad_mov_origen (origen_tipo, origen_id)
);
```

Recomendacion inicial:

- Aplicar opcion minima primero.
- Evaluar tabla de movimientos si se habilita consumo parcial real de una unidad fisica.

## Cambios de flujo recomendados

### Recepcion

Al generar etiquetas:

1. Calcular contenido por unidad:

```text
contenido_base = cantidad_base_lote / cantidad_compra_lote
```

2. Guardar por etiqueta:

```text
cantidad_base_original = contenido_base
cantidad_base_disponible = contenido_base
unidad_base = unidad_base_lote
estado_fisico = cerrada
```

### Preparacion/Empaque

Agregar modo de origen:

- por existencia;
- por unidad fisica etiquetada.

Si se selecciona unidad fisica:

1. Validar que `estado_fisico = cerrada` o `abierta` con saldo suficiente.
2. Validar que `cantidad_base_disponible >= cantidad_base_consumida`.
3. Descontar existencia en unidad base.
4. Actualizar unidad:
   - si consumo total: `cantidad_base_disponible = 0`, `estado_fisico = consumida`;
   - si consumo parcial: `estado_fisico = abierta`, saldo restante.
5. Guardar `id_preparacion_consumo` e `id_movimiento_consumo`.

### Venta futura

Venta de unidad cerrada:

1. Leer etiqueta.
2. Validar unidad disponible/cerrada.
3. Descontar `cantidad_base_disponible` de existencia.
4. Marcar unidad `vendida`.

## Backfill recomendado

Para etiquetas ya existentes desde recepcion:

```sql
UPDATE erp_inventario_unidades u
INNER JOIN erp_almacen_recepciones_lotes l ON l.id_recepcion_lote = u.id_recepcion_lote
SET u.cantidad_base_original = ROUND(l.cantidad_base / NULLIF(l.cantidad_compra, 0), 6),
    u.cantidad_base_disponible = ROUND(l.cantidad_base / NULLIF(l.cantidad_compra, 0), 6),
    u.unidad_base = l.unidad_base,
    u.estado_fisico = 'cerrada'
WHERE u.origen_tipo = 'recepcion_compra'
  AND COALESCE(l.cantidad_compra, 0) > 0;
```

Nota: antes de aplicar, revisar etiquetas ya vendidas/canceladas cuando exista venta productiva.

## UAT propuesto

### UAT-ALM-UNIDAD-001 - Backfill de REC-OC-25

Esperado:

- Las 3 etiquetas de `REC-OC-25` quedan con:
  - `cantidad_base_original = 4.000000`
  - `cantidad_base_disponible = 4.000000`
  - `unidad_base = kg`
  - `estado_fisico = cerrada`

### UAT-ALM-UNIDAD-002 - Preparacion desde etiqueta cerrada

Caso:

- Seleccionar `INV-00003-29-0001`
- Preparar presentaciones consumiendo `4 kg`

Esperado:

- Existencia origen baja `4 kg`.
- Etiqueta origen queda `consumida`.
- Preparacion guarda referencia a `id_inventario_unidad`.
- Presentaciones generadas conservan origen `preparacion_presentacion`.

## Criterio para pedir autorizacion

Se requiere autorizacion para:

1. Crear respaldo externo.
2. Alterar `erp_inventario_unidades`.
3. Hacer backfill de etiquetas existentes.
4. Ajustar Preparacion/Empaque para seleccionar unidad fisica.

Sin esa autorizacion, no aplicar migraciones.

## Avance aplicado

Fecha: 2026-06-25

Respaldo previo:

- `storage/backups/artianilocal_pre_alm_unidad_001_20260625_000940.sql`

Migracion aplicada en `erp_inventario_unidades`:

- `cantidad_base_original DECIMAL(18,6)`
- `cantidad_base_disponible DECIMAL(18,6)`
- `unidad_base VARCHAR(40)`
- `estado_fisico VARCHAR(30) DEFAULT 'cerrada'`
- `id_preparacion_consumo INT NULL`
- `id_movimiento_consumo INT NULL`
- `fecha_consumo DATETIME NULL`

Indices aplicados:

- `idx_inventario_unidad_estado_fisico`
- `idx_inventario_unidad_preparacion_consumo`

Backfill:

- Unidades nacidas de recepcion calculan contenido por:

```text
cantidad_base_original = cantidad_base_lote / cantidad_compra_lote
cantidad_base_disponible = cantidad_base_lote / cantidad_compra_lote
```

- Unidades sin lote de recepcion reciben fallback generico `1 pza` si no tenian contenido.
- No quedaron unidades sin contenido base.

Codigo actualizado:

- Recepcion genera unidades fisicas con contenido base propio.
- Preparacion genera unidades de presentacion con `1 pza` por etiqueta.
- Diagnostico de unidades vs existencia compara contra `SUM(cantidad_base_disponible)` y no contra `COUNT(etiquetas)`.

Validacion REC-OC-25:

| unidad | existencia | contenido original | contenido disponible | estado fisico |
| --- | --- | --- | --- | --- |
| `INV-00003-28-0001` | `EXI-1017-32` | `4.000000 kg` | `4.000000 kg` | `cerrada` |
| `INV-00003-29-0001` | `EXI-1017-33` | `4.000000 kg` | `4.000000 kg` | `cerrada` |
| `INV-00003-29-0002` | `EXI-1017-33` | `4.000000 kg` | `4.000000 kg` | `cerrada` |

Cuadre por existencia:

- `EXI-1017-32`: existencia `4.0000`, etiquetas `4.000000`, `1` etiqueta.
- `EXI-1017-33`: existencia `8.0000`, etiquetas `8.000000`, `2` etiquetas.

Pendiente historico cerrado:

- `ALM-UNIDAD-002`: permitir en Preparacion/Empaque seleccionar una unidad fisica/etiqueta como origen, marcarla `consumida` o `abierta`, y enlazar `id_preparacion_consumo`/`id_movimiento_consumo`.
- Estado: aplicado y validado con `PREP-20260625-0002`.

## ALM-UNIDAD-002 - Propuesta preparada

Fecha: 2026-06-25

Objetivo:

- Que Preparacion/Empaque no consuma solo una existencia agregada.
- Debe consumir una unidad fisica especifica cuando el origen tenga etiquetas/unidades trazables.
- La regla es generica: aplica a cualquier SKU y unidad base (`kg`, `pza`, `l`, `m`, etc.), no a un producto particular.

Modelo recomendado:

- `erp_almacen_preparaciones.id_unidad_origen` guarda la unidad fisica seleccionada en el borrador.
- `erp_almacen_preparacion_consumos.id_inventario_unidad` guarda la unidad fisica realmente consumida al confirmar.
- `erp_inventario_unidades` ya tiene los campos para dejar evidencia del consumo:
  - `estado_fisico`
  - `cantidad_base_disponible`
  - `id_preparacion_consumo`
  - `id_movimiento_consumo`
  - `fecha_consumo`

Estados fisicos sugeridos:

- `cerrada`: unidad completa disponible.
- `consumida`: unidad usada por completo en una preparacion.
- `abierta`: unidad usada parcialmente; queda saldo fisico disponible.
- `agotada`: unidad sin saldo disponible por consumo parcial acumulado.

Propuesta DDL:

```sql
ALTER TABLE erp_almacen_preparaciones
  ADD COLUMN id_unidad_origen INT NULL AFTER id_existencia_origen,
  ADD KEY idx_almacen_preparacion_unidad_origen (id_unidad_origen);

ALTER TABLE erp_almacen_preparacion_consumos
  ADD COLUMN id_inventario_unidad INT NULL AFTER id_existencia_inventario,
  ADD COLUMN cantidad_unidad_antes DECIMAL(18,6) NOT NULL DEFAULT 0.000000 AFTER id_inventario_unidad,
  ADD COLUMN cantidad_unidad_despues DECIMAL(18,6) NOT NULL DEFAULT 0.000000 AFTER cantidad_unidad_antes,
  ADD COLUMN estado_unidad_despues VARCHAR(30) NULL AFTER cantidad_unidad_despues,
  ADD KEY idx_prep_consumo_unidad (id_inventario_unidad);
```

Reglas de validacion:

- Si una existencia origen tiene unidades fisicas disponibles, Preparacion debe pedir seleccionar una.
- La unidad seleccionada debe pertenecer a la existencia, almacen y SKU origen.
- `cantidad_base_disponible` de la unidad debe cubrir la cantidad requerida, o permitir consumo parcial solo si el flujo lo declara como `abierta`.
- Al confirmar:
  - baja existencia base;
  - registra kardex de salida;
  - registra consumo;
  - actualiza la unidad fisica origen.

Reglas de UI:

- En "Existencia origen" se debe mostrar primero el saldo agregado.
- Debajo, si existen etiquetas/unidades, se muestran como opciones seleccionables:
  - codigo de etiqueta;
  - contenido disponible;
  - lote/caducidad;
  - estado fisico.
- El operador elige la unidad exacta antes de guardar/confirmar.

Criterio UAT:

- Preparar desde una unidad cerrada de 4 kg.
- Consumir 4 kg para generar presentaciones.
- La unidad origen queda `consumida`, con `cantidad_base_disponible = 0`.
- El consumo conserva `id_inventario_unidad`.
- Las etiquetas de resultado conservan `origen_tipo = preparacion_presentacion`.

Autorizacion requerida:

- Antes de implementar este flujo se requiere respaldo externo y autorizacion para aplicar el DDL anterior.

## Avance aplicado ALM-UNIDAD-002

Fecha: 2026-06-25

Respaldo previo:

- `storage/backups/artianilocal_pre_alm_unidad_002_20260625_085135.sql`

Migracion aplicada:

- `erp_almacen_preparaciones.id_unidad_origen`
- `erp_almacen_preparacion_consumos.id_inventario_unidad`
- `erp_almacen_preparacion_consumos.cantidad_unidad_antes`
- `erp_almacen_preparacion_consumos.cantidad_unidad_despues`
- `erp_almacen_preparacion_consumos.estado_unidad_despues`
- Indices:
  - `idx_almacen_preparacion_unidad_origen`
  - `idx_prep_consumo_unidad`

Codigo actualizado:

- `Almacenes::consultar_existencias_base_preparacion()` adjunta unidades fisicas disponibles por existencia.
- `Almacenes::guardar_borrador_preparacion()` guarda `id_unidad_origen` y exige unidad fisica cuando la existencia tiene etiquetas/unidades disponibles.
- `Almacenes::confirmar_preparacion()` valida la unidad fisica origen, registra snapshot en consumo y actualiza la unidad como `consumida` o `abierta`.
- UI de Preparacion/Empaque muestra unidades fisicas debajo de cada existencia origen.

Regla aplicada:

- Si la existencia origen tiene unidades fisicas disponibles, el operador debe elegir una unidad exacta.
- Si la existencia no tiene unidades fisicas disponibles, se permite el flujo por existencia para no bloquear saldos antiguos o no trazables.

Validacion de estructura:

- `erp_almacen_preparaciones.id_unidad_origen` existe.
- `erp_almacen_preparacion_consumos.id_inventario_unidad` existe.
- Campos de antes/despues de unidad existen.
- Indices de unidad origen y consumo existen.

Validacion REC-OC-25 antes de UAT:

| unidad | existencia | disponible | estado |
| --- | --- | --- | --- |
| `INV-00003-28-0001` | `EXI-1017-32` | `4.000000 kg` | `cerrada` |
| `INV-00003-29-0001` | `EXI-1017-33` | `4.000000 kg` | `cerrada` |
| `INV-00003-29-0002` | `EXI-1017-33` | `4.000000 kg` | `cerrada` |

UAT manual sugerido:

1. Entrar a `ERP > Almacen > Preparacion/Empaque`.
2. Seleccionar almacen.
3. Seleccionar SKU origen con presentaciones.
4. Seleccionar presentacion a preparar.
5. En "Existencia origen", elegir una existencia.
6. Si aparecen etiquetas/unidades debajo, elegir una unidad fisica especifica.
7. Guardar borrador.
8. Confirmar preparacion.
9. Validar:
   - la existencia origen baja;
   - la unidad fisica origen queda `consumida` si se uso completa o `abierta` si queda saldo;
   - el consumo guarda `id_inventario_unidad`;
   - se genera entrada de presentacion con el mismo folio de preparacion.

## Evidencia UAT ALM-UNIDAD-002A

Fecha: 2026-06-25

Tipo:

- UAT controlada de compatibilidad sin unidad fisica origen.
- Motivo: las transformaciones activas con stock disponible usan `TP-40372` como SKU origen, pero ese stock actual no tiene etiquetas/unidades fisicas disponibles.
- Regla validada: si la existencia no tiene unidades fisicas trazables, Preparacion conserva el flujo por existencia para no bloquear stock anterior.

Respaldo previo:

- `storage/backups/artianilocal_pre_alm_unidad_002_uat_20260625_090632.sql`

Folio:

- `PREP-20260625-0001`

Caso:

- Almacen: `Francisco Javier Mina 971 - Bodega trasera`
- SKU origen: `TP-40372`
- Existencia origen: `EXI-50-26`
- Transformacion: `TP-40372 -> TP-40372-25GR`
- Unidades preparadas: `1`
- Consumo origen: `0.025 kg`

Resultado:

| elemento | valor |
| --- | --- |
| Preparacion | `id_preparacion_almacen=5` |
| Estado | `confirmada` |
| Movimiento salida | `48` |
| Movimiento entrada | `49` |
| Etiquetas generadas | `1` |
| Etiqueta resultado | `P25-P000005-0001` |

Kardex:

| movimiento | tipo | SKU | existencia | cantidad | antes | despues |
| --- | --- | --- | --- | --- | --- | --- |
| `48` | salida | `TP-40372` | `EXI-50-26` | `0.0250` | `15.0000` | `14.9750` |
| `49` | entrada | `TP-40372-25GR` | `EXI-50-28` | `1.0000` | `20.0000` | `21.0000` |

Consumo:

- `id_preparacion_consumo=4`
- `id_existencia_inventario=26`
- `id_inventario_unidad=NULL`
- Correcto para este caso porque la existencia origen no tenia unidad fisica trazable.

Pendiente historico UAT ALM-UNIDAD-002B cerrado:

- Ejecutar preparacion desde una existencia origen que si tenga unidades fisicas disponibles.
- Esperado:
  - `erp_almacen_preparaciones.id_unidad_origen` con valor.
  - `erp_almacen_preparacion_consumos.id_inventario_unidad` con valor.
  - Unidad origen actualizada a `consumida` o `abierta`.
- Estado: validado con unidad `UAT-EXI-26-20260625-001` y folio `PREP-20260625-0002`.

## Evidencia UAT ALM-UNIDAD-002B

Fecha: 2026-06-25

Tipo:

- UAT controlada desde unidad fisica exacta.
- Se regularizo una unidad fisica sobre una existencia real disponible, sin aumentar stock ni crear existencia nueva.
- Objetivo: validar consumo parcial desde una unidad fisica grande y estado `abierta`.

Respaldo previo:

- `storage/backups/artianilocal_pre_alm_unidad_002b_uat_20260625_091511.sql`

Unidad fisica origen creada para UAT:

| campo | valor |
| --- | --- |
| `id_inventario_unidad` | `36` |
| Codigo | `UAT-EXI-26-20260625-001` |
| Existencia | `EXI-50-26` |
| SKU | `TP-40372` |
| Contenido original | `14.975000 kg` |
| Estado inicial | `cerrada` |
| Origen | `regularizacion_trazabilidad_uat` |

Folio:

- `PREP-20260625-0002`

Caso:

- Almacen: `Francisco Javier Mina 971 - Bodega trasera`
- SKU origen: `TP-40372`
- Existencia origen: `EXI-50-26`
- Unidad origen: `UAT-EXI-26-20260625-001`
- Transformacion: `TP-40372 -> TP-40372-25GR`
- Unidades preparadas: `1`
- Consumo origen: `0.025 kg`

Resultado:

| elemento | valor |
| --- | --- |
| Preparacion | `id_preparacion_almacen=6` |
| Estado | `confirmada` |
| `id_unidad_origen` | `36` |
| Movimiento salida | `50` |
| Movimiento entrada | `51` |
| Etiquetas generadas | `1` |
| Etiqueta resultado | `P25-P000006-0001` |

Consumo:

| campo | valor |
| --- | --- |
| `id_preparacion_consumo` | `5` |
| `id_existencia_inventario` | `26` |
| `id_inventario_unidad` | `36` |
| `cantidad_unidad_antes` | `14.975000` |
| `cantidad_unidad_despues` | `14.950000` |
| `estado_unidad_despues` | `abierta` |

Unidad fisica despues de confirmar:

| unidad | disponible | estado fisico | estatus | consumo | movimiento |
| --- | --- | --- | --- | --- | --- |
| `UAT-EXI-26-20260625-001` | `14.950000 kg` | `abierta` | `disponible` | `5` | `50` |

Kardex:

| movimiento | tipo | SKU | existencia | cantidad | antes | despues |
| --- | --- | --- | --- | --- | --- | --- |
| `50` | salida | `TP-40372` | `EXI-50-26` | `0.0250` | `14.9750` | `14.9500` |
| `51` | entrada | `TP-40372-25GR` | `EXI-50-28` | `1.0000` | `21.0000` | `22.0000` |

Resultado operativo:

- Preparacion ya puede consumir desde unidad fisica exacta.
- La unidad origen queda abierta cuando el consumo es parcial.
- La presentacion generada conserva etiqueta de `preparacion_presentacion`.

## Cierre UI Etiquetado

Fecha: 2026-06-25

Objetivo:

- Ajustar Etiquetado para que ya no parezca exclusivo de Recepcion.
- Mostrar etiquetas nacidas de Recepcion, Preparacion/Empaque, Inventario inicial o regularizacion controlada.
- Mostrar contenido fisico disponible y estado fisico de la unidad.

Cambios aplicados:

- Vista `app/vistas/paginas/apps/erp/almacen/etiquetado.php`:
  - texto superior actualizado a etiquetas generadas por recepcion o preparacion;
  - tabla agrega columna `Contenido`.
- Modelo `InventarioErp::listarEtiquetas()`:
  - expone `cantidad_base_original`;
  - expone `cantidad_base_disponible`;
  - expone `unidad_base`;
  - expone `estado_fisico`.
- JS `public/assets/js/custom/apps/erp/almacen/etiquetado/etiquetado.js`:
  - muestra origen legible;
  - muestra contenido fisico;
  - muestra estado de etiqueta y estado fisico juntos;
  - conserva impresion y marcado masivo.

Validacion tecnica:

- PHP sin errores de sintaxis.
- JS sin errores de sintaxis.
- Datos recientes disponibles para validar UI:

| etiqueta | origen | contenido | estado fisico |
| --- | --- | --- | --- |
| `P25-P000006-0001` | `PREP-20260625-0002` | `1.000000 pza` | `cerrada` |
| `UAT-EXI-26-20260625-001` | `regularizacion_trazabilidad_uat` | `14.950000 kg` | `abierta` |
| `INV-00003-29-0002` | `REC-OC-25` | `4.000000 kg` | `cerrada` |

UAT visual manual recomendado:

1. Entrar a `ERP > Almacen > Etiquetado`.
2. Filtrar `Pendiente impresion`.
3. Confirmar que aparecen etiquetas de `PREP-20260625-0002` y `REC-OC-25`.
4. Buscar `UAT-EXI-26-20260625-001`.
5. Confirmar que muestra contenido `14.95 kg` y estado fisico `Abierta`.
6. Confirmar que las etiquetas de preparacion muestran origen `Preparacion/Empaque`.
7. No marcar impresa/pegada si no se quiere mover estados reales.

Siguiente autorizacion probable desde esta etapa:

- Definir politica de venta/preparacion para unidades fisicas `abiertas`, porque impacta Inventario/Ventas.
- Estado: politica documentada; implementacion corresponde al siguiente modulo de Inventario/Existencias.

## Evidencia visual Playwright

Fecha: 2026-06-25

Herramienta:

- Playwright `1.61.1`
- Chromium

Rutas de evidencia:

- `storage/uat/almacen_visual_20260625/01_etiquetado.png`
- `storage/uat/almacen_visual_20260625/02_preparacion_empaque.png`
- `storage/uat/almacen_visual_20260625/03_etiquetado_movil.png`
- `storage/uat/almacen_visual_20260625/04_preparacion_seleccion.png`
- `storage/uat/almacen_visual_20260625/05_etiquetado_uat_abierta.png`

Validaciones:

- Login correcto en entorno local.
- Etiquetado muestra etiquetas de Recepcion y Preparacion/Empaque.
- Etiquetado muestra contenido fisico y estado fisico.
- Unidad abierta `UAT-EXI-26-20260625-001` muestra `14.95 kg` y estado `Abierta`.
- Preparacion/Empaque muestra la existencia `EXI-50-26` y la unidad fisica abierta seleccionable.
- Vista movil de Etiquetado conserva tabla en contenedor con scroll horizontal.

Hallazgos visuales corregidos:

- `ALM-UNIDAD-UI-001`: tabla de Etiquetado en movil se comprimia demasiado.
  - Accion: tabla con ancho minimo dentro de `.table-responsive`.
- `ALM-UNIDAD-UI-002`: tabla de Preparacion/Empaque podia comprimirse en movil.
  - Accion: tabla con ancho minimo dentro de `.table-responsive`.
- `ALM-UNIDAD-UI-003`: origen de regularizacion UAT mostraba un ID tecnico como texto principal.
  - Accion: mostrar `Regularizacion UAT` como origen principal y el ID como detalle.

Nota:

- Se elimino la captura de login y el script temporal de Playwright para no conservar credenciales en archivos locales.

## Cierre fisico etiquetas UAT

Fecha: 2026-06-25

Respaldo previo:

- `storage/backups/artianilocal_pre_alm_etiquetas_uat_20260625_093632.sql`

Alcance:

- Solo se marcaron etiquetas UAT generadas por Preparacion/Empaque.
- No se movieron etiquetas reales de Recepcion `REC-OC-25`.

Etiquetas actualizadas:

| id | etiqueta | origen | estado anterior | estado final | estado fisico |
| --- | --- | --- | --- | --- | --- |
| `35` | `P25-P000005-0001` | `PREP-20260625-0001` | `pendiente_impresion` | `pegada` | `cerrada` |
| `37` | `P25-P000006-0001` | `PREP-20260625-0002` | `pendiente_impresion` | `pegada` | `cerrada` |

Control de no afectacion:

- `INV-00003-28-0001`, `INV-00003-29-0001` e `INV-00003-29-0002` de `REC-OC-25` siguen en `pendiente_impresion`.

Evidencia visual:

- `storage/uat/almacen_visual_20260625/06_etiqueta_uat_pegada.png`

Resultado:

- Etiquetas de presentacion preparadas completaron ciclo fisico: generada -> impresa -> pegada.
- Preparacion/Empaque queda probado de punta a punta para presentaciones propias.

Siguiente decision operativa cerrada:

- Definir politica de unidades fisicas `abiertas`:
  - si pueden volver a usarse para Preparacion;
  - si se bloquean para venta directa;
  - si requieren re-etiquetado o conteo;
  - si Inventario debe resaltarlas como saldo abierto.
- Estado: politica documentada abajo; implementacion queda para Inventario/Existencias y, mas adelante, Ventas POS/Ecommerce.

## Politica operativa de unidades abiertas

Fecha: 2026-06-25

Decision:

- Una unidad fisica `abierta` conserva inventario disponible.
- Pierde condicion de unidad cerrada vendible.
- Puede usarse como origen para Preparacion/Empaque mientras tenga saldo disponible.
- Puede venderse en punto de venta como venta a granel/fraccionada si el SKU y la sucursal lo permiten.
- No debe publicarse ni venderse en ecommerce como unidad cerrada.

Reglas por canal:

### Punto de venta

- Puede vender desde unidades abiertas cuando el SKU permita venta a granel/fraccionada.
- La venta debe descontar cantidad base disponible.
- Debe conservar lote/caducidad/unidad fisica cuando aplique.

### Ecommerce

- No debe vender unidades abiertas como unidad cerrada.
- Solo debe vender:
  - presentaciones cerradas disponibles;
  - unidades fisicas cerradas vendibles;
  - SKUs habilitados para ecommerce.

### Preparacion/Empaque

- Puede consumir unidades `cerradas` o `abiertas`.
- Si consume parcial:
  - la unidad queda `abierta`;
  - baja `cantidad_base_disponible`.
- Si consume total:
  - la unidad queda `consumida`;
  - `cantidad_base_disponible = 0`.

### Inventario

- Debe mostrar las unidades abiertas como saldo disponible con alerta visual.
- No debe ocultarlas porque siguen siendo stock real.
- Debe diferenciar:
  - disponible cerrado;
  - disponible abierto;
  - consumido/agota saldo;
  - pendiente de impresion/pegado.

Regla corta:

> Unidad abierta = stock disponible, no unidad cerrada vendible. POS puede venderla a granel; Preparacion puede consumirla; ecommerce no debe tomarla como unidad cerrada.

Impacto para el siguiente modulo:

- Inventario/Existencias debe mostrar y filtrar unidades abiertas.
- Ventas POS debera permitir salida fraccionada desde unidades abiertas solo si el SKU lo permite.
- Ecommerce debera excluir unidades abiertas del stock vendible cerrado.
