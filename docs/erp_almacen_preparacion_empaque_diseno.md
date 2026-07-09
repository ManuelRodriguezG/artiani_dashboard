# ERP Almacen - Preparacion/Empaque de presentaciones

Fecha: 2026-06-20

Modulo: ERP > Almacen > Preparacion/Empaque

Clave de trabajo: `ALM-PREP-001`

## Objetivo

Disenar el flujo operativo para preparar presentaciones fisicas desde existencia base sin tocar Ventas/ecommerce y sin crear stock desde Catalogo.

El caso base es:

- SKU base `TP-40372` en `KG`.
- Recepcion de costal cerrado de `4.000 kg`: crea existencia base.
- Recepcion de bolsa proveedor de `500 g` lista para vender: crea existencia del SKU presentacion.
- Preparacion interna de bolsas de `25 g`, `50 g` o `100 g`: consume existencia base y crea existencia de SKU presentacion.
- Ejemplo controlado: consumir `0.500 kg` de `TP-40372` para crear `20` unidades de `TP-40372-BOLSA-25G`.

## Decisiones base

- Catalogo define SKU, unidad base, reglas, codigos internos, factores y presentaciones.
- Catalogo no crea existencias ni etiquetas fisicas.
- Almacen Recepcion crea existencia si el proveedor entrega una presentacion cerrada/lista para vender.
- Almacen Preparacion/Empaque crea existencia si el negocio embolsa, corta o arma internamente.
- Inventario registra y consulta saldos/movimientos; no debe ser la pantalla donde se prepara producto.
- Ventas/ecommerce queda fuera de esta fase.
- Toda preparacion confirmada debe dejar kardex con salida del SKU base y entrada del SKU presentacion con el mismo folio.
- Las etiquetas de bolsas propias se generan en Preparacion/Empaque porque ahi nace la unidad fisica.

## Auditoria del flujo actual

### 1. Existencias desde recepcion

`Almacenes::guardar_recepcion_almacen()` ejecuta la recepcion dentro de transaccion:

- Valida orden, detalle, SKU ERP, almacen y cantidades pendientes.
- Consulta reglas de control de inventario del SKU.
- Valida lote/caducidad si aplica.
- Crea registro en `erp_almacen_recepciones_lotes`.
- Actualiza o crea saldo en `erp_inventario_existencias`.
- Inserta movimiento en `erp_inventario_movimientos`.
- Genera unidades/etiquetas en `erp_inventario_unidades` si el SKU lo requiere.
- Actualiza detalle de recepcion, orden de compra y notificaciones.

La llave logica de existencia actual considera:

- `id_producto`
- `id_sku_erp`
- `id_almacen`
- `lote`
- `fecha_caducidad`
- `ubicacion_id`

Esto sirve para stock base y tambien para stock de presentacion, siempre que se use el SKU correcto.

### 2. Movimientos de inventario

`Almacenes::insertar_movimiento_inventario()` registra hoy entradas por recepcion:

- `tipo_movimiento='entrada'`
- `origen_tipo='recepcion_compra'`
- `origen_id=id_recepcion_almacen`
- `origen_detalle_id=id_recepcion_detalle`
- `id_recepcion_lote`
- `id_existencia_inventario`
- `referencia=folio de recepcion`
- `existencia_anterior` y `existencia_nueva`

La tabla `erp_inventario_movimientos` ya tiene campos suficientes para registrar la salida base y entrada presentacion de una preparacion, usando otro `origen_tipo`.

### 3. Unidades y etiquetas

`Almacenes::generar_unidades_inventario()` crea unidades en `erp_inventario_unidades` con:

- `tipo_identidad='etiqueta_interna'`
- `estado_etiqueta='pendiente_impresion'`
- `origen_tipo='recepcion_compra'`
- `origen_id=id_recepcion_almacen`
- `origen_detalle_id=id_recepcion_detalle`
- lote, caducidad, almacen, ubicacion y existencia.

El flujo de Almacen > Etiquetado ya permite imprimir y marcar como pegadas etiquetas unitarias.

### 4. `origen_tipo` para preparacion

`erp_inventario_unidades.origen_tipo` y `erp_inventario_movimientos.origen_tipo` pueden distinguir `recepcion_compra` de `preparacion_presentacion`.

Hallazgo: `InventarioErp::listarEtiquetas()` actualmente asume recepcion porque une contra `erp_almacen_recepciones`. Para etiquetas nacidas en preparacion, debe mostrar origen generico o unir tambien contra la tabla nueva de preparaciones.

### 5. Tablas actuales reutilizables

Sirven para el flujo:

- `erp_catalogo_sku_presentaciones`: relaciona SKU base con SKU presentacion y factor de salida.
- `erp_inventario_existencias`: saldo por SKU, almacen, lote, caducidad y ubicacion.
- `erp_inventario_movimientos`: kardex de salida/entrada.
- `erp_inventario_unidades`: unidades fisicas etiquetables.
- `erp_almacen_ubicaciones`: ubicacion de origen/destino.
- `erp_productos_control_inventario` y `erp_catalogo_sku_reglas_inventario`: reglas de lote, caducidad, etiqueta y control.

No conviene reutilizar `erp_almacen_recepciones_lotes` como detalle de preparacion, porque representa lotes recibidos de compra. Preparacion necesita su propio folio y sus propias lineas de consumo/resultado.

## Hallazgos

| ID | Severidad | Hallazgo | Recomendacion |
| --- | --- | --- | --- |
| `ALM-PREP-H001` | Alta | No existe encabezado/detalle de preparacion para explicar una conversion fisica de base a presentacion. | Crear tablas de preparacion, consumos y resultados antes de implementar UI. |
| `ALM-PREP-H002` | Media | Las etiquetas ya soportan `origen_tipo`, pero la consulta de inventario/etiquetas asume recepcion. | Extender consultas para mostrar folio de preparacion cuando `origen_tipo='preparacion_presentacion'`. |
| `ALM-PREP-H003` | Media | `id_recepcion_lote` es especifico de recepcion; no debe ser el unico soporte de trazabilidad para preparacion. | Guardar trazabilidad en lineas de consumo y resultado; usar `origen_tipo/origen_id/origen_detalle_id` como relacion principal. |
| `ALM-PREP-H004` | Media | Existencias y movimientos usan `DECIMAL(12,4)`, mientras Catalogo define factores `DECIMAL(18,6)`. | Para la primera version se puede operar con 25 g (`0.0250 kg`), pero conviene evaluar migracion a mayor precision antes de usar fracciones menores. |
| `ALM-PREP-H005` | Baja | El costo de empaque/merma operativa no esta modelado como componente separado. | Iniciar con costo proporcional del insumo base y dejar empaque/merma avanzada para una fase posterior. |
| `ALM-PREP-H006` | Alta | `TP-40372` muestra solo `5 kg` disponibles porque la compra `REC-OC-20` entro como `5` unidades base, aunque operativamente fueron `5 costales de 4 kg`. | Resolver primero `CAT-GRANEL-H001`: configurar factor compra -> inventario y definir correccion controlada del saldo historico antes de usar esta existencia para pruebas de preparacion. |
| `ALM-PREP-H007` | Media | Existe un borrador viejo `PREP-20260621-0001` ligado al almacen historico `MINA1105-BAJA`. | No confirmarlo ni borrarlo sin autorizacion; el flujo operativo debe ocultar almacenes inactivos y validar de nuevo al confirmar. |
| `ALM-PREP-H008` | Media | Las presentaciones de `TP-40372` estan configuradas con `generar_etiqueta_interna=0`. | Si el UAT debe validar etiquetas de bolsas propias, primero activar la regla de etiqueta en Catalogo/Inventario para el SKU presentacion elegido. |
| `ALM-PREP-H009` | Media | El folio generado fue `PREP-20260622-0001`, pero `fecha_preparacion` quedo `2026-06-21 22:03:55`. | Mitigado para operaciones futuras fijando zona horaria PHP del proyecto en `America/Mexico_City`; no renombrar folio historico. |
| `ALM-PREP-H010` | Alta | La UI de preparacion selecciona SKU base y presentacion, pero no permite elegir explicitamente la existencia fisica/lote/ubicacion origen. | Agregar selector obligatorio o FEFO visible de existencia origen antes de confirmar, mostrando lote, caducidad, ubicacion, cantidad y codigo de existencia. |
| `ALM-PREP-H011` | Alta | El modelo actual cubre base -> presentacion, pero el negocio tambien necesita presentacion -> presentacion, por ejemplo bolsa proveedor `500 g` -> bolsas internas de `25 g`, `50 g` o `100 g`. | Generalizar la regla como transformacion/empaque desde cualquier SKU origen inventariable hacia SKU resultado, conservando trazabilidad del SKU y existencia origen. |
| `ALM-PREP-H012` | Media | Al consumir por completo una existencia origen en preparacion, la cantidad quedaba en `0` pero el estado podia permanecer `disponible`. | Mitigado: `Almacenes::aplicar_salida_preparacion()` marca `agotada` cuando `cantidad_disponible` llega a `0`. |

### Nota de bloqueo operativo para `TP-40372`

Preparacion/Empaque no debe "inventar" los `20 kg` si Inventario solo tiene `5 kg` registrados. El flujo de preparacion debe confiar en existencias reales.

El problema detectado esta antes de Preparacion:

- SKU base `TP-40372`: unidad base `KG`.
- Relacion proveedor `SUNNY`: unidad compra `CAJA`, `factor_conversion=1.000000`.
- Recepcion `REC-OC-20`: registro `5.0000` como inventario base.
- Operacion real esperada: `5 costales x 4 kg = 20 kg`.

Referencia de Catalogo: `CAT-GRANEL-H001` en `docs/erp_catalogo_venta_granel.md`.

### Auditoria previa a UAT 2026-06-21

Estado actual:

- Almacen operativo para preparacion: `BOD971`.
- Almacenes `ACUARIO967` y `MASCOTAS971` no permiten preparacion.
- Borrador historico: `PREP-20260621-0001`, estatus `borrador`, almacen `MINA1105-BAJA`, no apto para confirmar.
- Existencia base `TP-40372` en `BOD971`:
  - Lote `L1`: `16.0000 kg` disponibles, caducidad `2026-10-30`.
  - Lote `L2`: `4.0000 kg` disponibles, caducidad `2027-01-29`.
  - Total disponible: `20.0000 kg`.
- Presentaciones preparables activas:
  - `TP-40372-25GR`, factor `0.025000 kg`.
  - `TP-40372-50GR`, factor `0.050000 kg`.
  - `TP-40372-100GR`, factor `0.100000 kg`.
  - `TP-40372-500GR`, factor `0.500000 kg`.
- Etiquetas de presentaciones: desactivadas actualmente (`generar_etiqueta_interna=0`).

Refuerzos aplicados:

- `Almacenes::consultar_presentaciones_preparables()` valida que el almacen permita preparacion cuando se filtra por almacen.
- `Almacenes::confirmar_preparacion()` vuelve a validar almacen activo con `permite_preparacion` antes de afectar inventario.
- La UI de `Preparacion/Empaque` carga solo almacenes con `permite_preparacion=1`.
- El listado operativo de preparaciones usa `solo_operativos=1` para ocultar borradores ligados a almacenes historicos/inactivos.

Validaciones tecnicas:

- `C:\xampp\php\php.exe -l app\modelos\Almacenes.php`: OK.
- `node --check public\assets\js\custom\apps\erp\almacen\preparacion_empaque\preparacion_empaque.js`: OK.

### UAT propuesto: ALM-PREP-001

Opcion sin etiquetas:

1. Crear borrador en `BOD971`.
2. Seleccionar SKU base `TP-40372`.
3. Seleccionar presentacion `TP-40372-25GR`.
4. Capturar `20` unidades.
5. Validar resumen: consumo esperado `0.500000 kg`.
6. Confirmar preparacion.
7. Verificar kardex:
   - salida `0.5000 kg` del SKU base `TP-40372`;
   - entrada `20.0000` del SKU presentacion `TP-40372-25GR`;
   - ambos con el mismo folio `PREP-*`.

Opcion con etiquetas:

1. Antes del UAT, activar `generar_etiqueta_interna=1` y prefijo sugerido para el SKU presentacion elegido.
2. Ejecutar los pasos de la opcion sin etiquetas.
3. Verificar etiquetas en `erp_inventario_unidades` con `origen_tipo='preparacion_presentacion'`.
4. Validar pantalla de Etiquetado para imprimir/marcar etiquetas.

Autorizacion requerida:

- Respaldo externo antes de confirmar preparacion, porque afectara existencias y kardex.
- Si se desea UAT con etiquetas, autorizar tambien el cambio de configuracion del SKU presentacion.

## Evidencia UAT: ALM-PREP-001

Fecha de ejecucion: 2026-06-21  
Folio generado por sistema: `PREP-20260622-0001`

Autorizacion:

- Usuario autoriza ejecutar tareas necesarias hasta proxima autorizacion.
- Alcance autorizado: UAT con etiquetas para preparacion controlada de presentacion.

Respaldo previo:

- `storage/backups/artianilocal_alm_prep_001_20260621_antes_uat.sql`
- Tamano: `26641188` bytes.

Configuracion aplicada:

- SKU presentacion: `TP-40372-25GR`.
- `generar_etiqueta_interna=1`.
- `prefijo_etiqueta_interna='P25'`.

Operacion ejecutada:

- Almacen: `BOD971`.
- SKU base: `TP-40372`.
- SKU presentacion: `TP-40372-25GR`.
- Unidades preparadas: `20`.
- Factor: `0.025000 kg`.
- Consumo base esperado: `0.500000 kg`.
- Lote consumido por FEFO: `L1`, caducidad `2026-10-30`.

Resultado:

- Borrador creado: `id_preparacion_almacen=2`.
- Confirmacion exitosa.
- Movimiento salida: `37`.
- Movimiento entrada: `38`.
- Etiquetas generadas: `20`.

Kardex verificado:

- Movimiento `37`: salida `0.5000` de `TP-40372`, existencia `16.0000 -> 15.5000`, referencia `PREP-20260622-0001`.
- Movimiento `38`: entrada `20.0000` de `TP-40372-25GR`, existencia `0.0000 -> 20.0000`, referencia `PREP-20260622-0001`.
- Ambos movimientos usan `origen_tipo='preparacion_presentacion'` y `origen_id=2`.

Existencias verificadas:

- `TP-40372` en `BOD971`, lote `L1`: `15.5000 kg` disponibles.
- `TP-40372` en `BOD971`, lote `L2`: `4.0000 kg` disponibles.
- `TP-40372-25GR` en `BOD971`, lote `L1`: `20.0000` disponibles.

Etiquetas verificadas:

- `20` unidades en `erp_inventario_unidades`.
- Estado: `pendiente_impresion`.
- Estatus: `disponible`.
- Rango:
  - `P25-P000002-0001`
  - `P25-P000002-0020`
- `origen_tipo='preparacion_presentacion'`.
- `origen_id=2`.

Validaciones tecnicas:

- `C:\xampp\php\php.exe -l storage\uat\uat_alm_prep_001.php`: OK.
- Script UAT protegido contra reejecucion accidental; requiere `--execute` explicito para volver a afectar inventario.

Cierre:

- `ALM-PREP-001` aprobado tecnicamente.
- Pendiente visual: revisar en `Almacen > Etiquetado` que las etiquetas de `PREP-20260622-0001` se puedan imprimir y marcar segun proceso fisico.
- Pendiente de decision: conservar o cancelar el borrador historico `PREP-20260621-0001` ligado a `MINA1105-BAJA`; no afecta operacion nueva porque queda oculto/bloqueado.
- `ALM-PREP-H009` mitigado para folios futuros con `date_default_timezone_set('America/Mexico_City')` en `app/config/configuracion.php`.
- Verificacion: `storage/uat/uat_timezone_check.php` reporta `app_timezone=America/Mexico_City`, `php_timezone=America/Mexico_City`, `DATE_NOW` y `date()` alineados.

Evidencia posterior de etiquetado:

- Usuario imprime etiquetas de `PREP-20260622-0001`.
- Verificacion BD: `20` etiquetas en estado `impresa`.
- No hay `fecha_etiquetado`; falta marcar `pegada` si ya fueron adheridas fisicamente.

## Decision de trazabilidad origen para preparacion

Observacion operativa:

- En la preparacion no basta con seleccionar "voy a preparar `TP-40372-25GR` desde `TP-40372`".
- Tambien debe identificarse la existencia fisica exacta desde donde se prepara:
  - almacen;
  - ubicacion;
  - codigo de existencia;
  - lote;
  - caducidad;
  - cantidad disponible;
  - si aplica, etiqueta/contenedor fisico origen.

Criterio recomendado:

- La regla de Catalogo define que conversiones estan permitidas.
- Almacen Preparacion decide con que existencia fisica se ejecuta la conversion.
- El operador debe ver el origen fisico antes de confirmar.
- Por defecto se puede sugerir FEFO/FIFO, pero la pantalla debe mostrar que lote se consumira.
- Si el operador necesita elegir otro lote, debe poder hacerlo con validacion de stock.
- No se deben mezclar lotes en un mismo resultado salvo que exista una regla explicita de mezcla; la primera version debe mantener un lote origen por preparacion/resultado.

Casos soportados que debe contemplar el diseno robusto:

1. Compra costal/base:
   - Origen: `TP-40372`, lote `L1`, `16 kg`.
   - Resultado: `TP-40372-25GR`, `TP-40372-50GR` o `TP-40372-100GR`.

2. Compra presentacion proveedor lista para vender:
   - Origen: `TP-40372-500GR` recibido desde proveedor.
   - Resultado: existencia directa del SKU presentacion, sin preparacion interna.

3. Reempaque interno desde presentacion de proveedor:
   - Origen: existencia fisica `TP-40372-500GR`, lote/caducidad de proveedor.
   - Resultado: bolsas internas `TP-40372-25GR`, `TP-40372-50GR` o `TP-40372-100GR`.
   - La preparacion debe consumir unidades/cantidad del SKU origen `TP-40372-500GR` y crear unidades del SKU resultado.

Implicacion tecnica:

- `erp_almacen_preparacion_consumos.id_existencia_inventario` ya conserva la existencia fisica consumida.
- Falta exponer esa seleccion en la UI y permitir que el consumo no dependa solo de FEFO automatico.
- Para presentacion -> presentacion, `erp_catalogo_sku_presentaciones` puede quedarse corta si se interpreta solo como SKU base -> SKU presentacion; se debe evaluar renombrar conceptualmente a reglas de transformacion o agregar una tabla de conversion/preparacion mas general.

### Auditoria tecnica de transformacion/reempaque 2026-06-21

Estado de tablas:

- `erp_catalogo_sku_presentaciones` usa:
  - `id_sku_base`;
  - `id_sku_presentacion`;
  - `factor_salida_base`;
  - indice unico sobre `id_sku_presentacion`.
- Ese indice unico evita que un mismo SKU resultado tenga mas de un origen.
- Ejemplo problematico:
  - `TP-40372-100GR` ya existe como resultado de `TP-40372`.
  - Tambien deberia poder existir como resultado de `TP-40372-500GR`.
  - La tabla actual no expresa bien ambos caminos.

Estado de preparacion:

- `erp_almacen_preparaciones` guarda el borrador con SKU origen conceptual y SKU resultado, pero no guarda la existencia fisica origen antes de confirmar.
- `erp_almacen_preparacion_consumos` guarda `id_existencia_inventario`, pero se crea hasta confirmar.
- Por eso hoy el sistema puede documentar de donde salio el producto despues de confirmar, pero el operador no puede elegirlo desde el borrador.

Conclusion:

- Para un ERP robusto conviene separar:
  - Catalogo de presentaciones vendibles.
  - Reglas de transformacion/reempaque.
  - Ejecucion fisica desde una existencia origen.
- No conviene forzar `erp_catalogo_sku_presentaciones` para todos los reempaques, porque una misma presentacion vendible puede fabricarse desde varios origenes.

DDL propuesto:

- `docs/erp_almacen_preparacion_transformaciones_propuesta.sql`

Tabla propuesta:

- `erp_catalogo_sku_transformaciones`
  - `id_sku_origen`
  - `id_sku_resultado`
  - `cantidad_origen`
  - `unidades_resultado`
  - `tipo_transformacion`
  - `merma_porcentaje`
  - `requiere_empaque`
  - `estatus`

Columnas propuestas en preparacion:

- `erp_almacen_preparaciones.id_sku_transformacion`
- `erp_almacen_preparaciones.id_existencia_origen`
- `erp_almacen_preparaciones.cantidad_origen_consumida`

Ejemplos:

| Origen | Cantidad origen | Resultado | Unidades resultado | Caso |
| --- | ---: | --- | ---: | --- |
| `TP-40372` | `0.025000 kg` | `TP-40372-25GR` | `1` | embolsado desde granel |
| `TP-40372` | `0.500000 kg` | `TP-40372-500GR` | `1` | bolsa propia de 500 g |
| `TP-40372-500GR` | `1.000000 pza` | `TP-40372-100GR` | `5` | reempaque de bolsa proveedor |

Regla operativa:

- Si el origen tiene etiqueta/unidad fisica individual, al confirmar debe quedar como consumida/reempacada.
- Si el resultado genera etiquetas, las nuevas unidades nacen con `origen_tipo='preparacion_presentacion'`.
- El lote/caducidad del resultado se hereda de la existencia origen.
- El costo del resultado se prorratea desde el costo total del origen consumido.

Orden recomendado para implementar:

1. Revisar y autorizar DDL de transformaciones. Hecho.
2. Respaldar BD. Hecho: `storage/backups/artianilocal_alm_prep_transformaciones_20260621_antes_ddl.sql`.
3. Aplicar tabla/columnas propuestas. Hecho.
4. Migrar o duplicar reglas actuales `erp_catalogo_sku_presentaciones` a `erp_catalogo_sku_transformaciones`. Hecho.
5. Ajustar UI para iniciar por SKU/existencia origen. Implementado como selector de existencia origen.
6. Ajustar confirmacion para usar `id_existencia_origen` seleccionado, no solo FEFO automatico. Hecho.
7. Agregar UAT: `TP-40372-500GR -> 5 x TP-40372-100GR`. Hecho con folios `PREP-20260621-0002` y `PREP-20260621-0003`.

## Evidencia UAT: ALM-PREP-T015 reempaque

Fecha de ejecucion: 2026-06-21

Respaldo externo previo:

- `storage/backups/artianilocal_alm_prep_reempaque_20260621_antes_uat.sql`
- Tamano: `26652586` bytes.

Script:

- `storage/uat/uat_alm_prep_reempaque_500_a_100.php`

Caso probado:

1. Preparar una existencia controlada `TP-40372-500GR` desde `0.5000 kg` de `TP-40372`, lote `L1`.
2. Seleccionar esa existencia fisica `EXI-50-29` como origen.
3. Reempacar `1` unidad de `TP-40372-500GR` en `5` unidades de `TP-40372-100GR`.

Evidencia:

- Folio origen intermedio: `PREP-20260621-0002`.
- Folio reempaque: `PREP-20260621-0003`.
- Movimientos generados:
  - `39`: salida `0.5000` de `TP-40372`, `EXI-50-26`, referencia `PREP-20260621-0002`.
  - `40`: entrada `1.0000` de `TP-40372-500GR`, `EXI-50-29`, referencia `PREP-20260621-0002`.
  - `41`: salida `1.0000` de `TP-40372-500GR`, `EXI-50-29`, referencia `PREP-20260621-0003`.
  - `42`: entrada `5.0000` de `TP-40372-100GR`, `EXI-50-30`, referencia `PREP-20260621-0003`.
- `EXI-50-29` queda en `0.0000` y estado `agotada`.
- `EXI-50-30` queda en `5.0000` y estado `disponible`.
- Se generaron `5` etiquetas `P100-P000004-0001` a `P100-P000004-0005`, `origen_tipo='preparacion_presentacion'`, `origen_id=4`.
- Cierre visual: usuario confirmo impresion y pegado fisico; verificacion BD con `5` etiquetas en estado `pegada`, `fecha_impresion=2026-06-21 23:09:01`, `fecha_etiquetado=2026-06-21 23:09:38`.

Conclusion:

- El flujo ya permite preparar desde una existencia fisica exacta y reempacar presentacion -> presentacion sin crear bolsas teoricas desde Catalogo.
- La trazabilidad queda por folio de preparacion, existencia origen, lote/caducidad heredados, movimientos de salida/entrada y etiquetas del resultado.

## Modelo operativo recomendado

### Folio

Crear un folio propio de preparacion:

- Formato sugerido: `PREP-YYYYMMDD-0001` o `PREP-000001`.
- Debe compartirse en el movimiento de salida base y entrada presentacion.
- Debe aparecer en Kardex, etiquetas y auditoria.

### Estados

Estados sugeridos:

- `borrador`: capturado pero sin afectar inventario.
- `confirmada`: afecto inventario y genero movimientos/unidades.
- `cancelada`: anulada antes de confirmar o revertida con movimientos inversos en fase posterior.

Para primera version robusta, se recomienda permitir cancelar solo en `borrador`. La cancelacion de una preparacion confirmada requiere reglas mas finas porque puede haber etiquetas impresas, pegadas o unidades ya vendidas en fases futuras.

### Reglas de validacion

- La relacion debe existir en `erp_catalogo_sku_presentaciones`.
- `estatus='activo'`.
- `consume_stock_base_en='preparacion'`.
- `modo_disponibilidad` debe permitir stock terminado: `preparada` o `mixta`.
- `factor_salida_base > 0`.
- El SKU base y SKU presentacion no deben ser el mismo.
- La cantidad de unidades a preparar debe ser entera y mayor a cero.
- Consumo base = `unidades * factor_salida_base`.
- Si se usa merma, consumo base = `unidades * factor_salida_base * (1 + merma_porcentaje / 100)`.
- Debe existir stock disponible suficiente del SKU base en el almacen.
- La preparacion no debe dejar existencia negativa.
- Si hay lote/caducidad en el insumo base, el resultado debe conservar lote/caducidad.
- Las etiquetas se generan solo si el SKU presentacion lo requiere.

### Lote y caducidad

Regla recomendada para primera version:

- No mezclar lotes/caducidades dentro de una misma linea de resultado.
- Si se consumen dos lotes, se crean dos resultados separados.
- La presentacion hereda `lote` y `fecha_caducidad` de la existencia base.
- Si el producto base no maneja lote/caducidad, la presentacion tampoco debe inventarlos.

Esta regla es mas estricta, pero evita perder trazabilidad. Mas adelante se podria permitir mezcla controlada con una tabla de composicion por lote.

### Movimientos requeridos

Por cada preparacion confirmada:

1. Salida del SKU base:
   - `tipo_movimiento='salida'`
   - `origen_tipo='preparacion_presentacion'`
   - `origen_id=id_preparacion`
   - `origen_detalle_id=id_preparacion_consumo`
   - `referencia=folio preparacion`

2. Entrada del SKU presentacion:
   - `tipo_movimiento='entrada'`
   - `origen_tipo='preparacion_presentacion'`
   - `origen_id=id_preparacion`
   - `origen_detalle_id=id_preparacion_resultado`
   - `referencia=folio preparacion`

Ambos movimientos deben quedar en la misma transaccion.

### Etiquetas

Las unidades de presentacion preparadas deben usar `erp_inventario_unidades`:

- `origen_tipo='preparacion_presentacion'`
- `origen_id=id_preparacion`
- `origen_detalle_id=id_preparacion_resultado`
- `id_recepcion_almacen=NULL`
- `id_recepcion_lote=NULL` salvo que se decida conservar referencia secundaria al lote de recepcion original.

El folio que debe mostrarse en Etiquetado es el folio de preparacion, no la orden de compra. La etiqueta fisica para cliente no debe mostrar orden de compra ni datos internos de proveedor.

## DDL propuesto

Archivo SQL separado: `docs/erp_almacen_preparacion_empaque_schema_propuesta.sql`.

Resumen de tablas:

- `erp_almacen_preparaciones`: encabezado y folio.
- `erp_almacen_preparacion_consumos`: existencias base consumidas.
- `erp_almacen_preparacion_resultados`: existencias de presentacion creadas.

No ejecutar sin:

1. Respaldo externo con `mysqldump`.
2. Revision del DDL.
3. Autorizacion explicita del dueno.

## UX recomendada

Ruta propuesta: `Almacen > Preparacion/Empaque`.

Flujo de captura recomendado:

1. Seleccionar almacen.
2. Seleccionar el SKU base/producto principal en un selector buscable que solo cargue SKUs con presentaciones preparables.
3. Mostrar solo las presentaciones relacionadas con ese SKU base.
4. Capturar unidades a preparar.
5. Mostrar resumen de validacion con SKU base, descripcion, factor, consumo requerido, existencia disponible y unidades posibles.

La pantalla no debe iniciar desde una lista plana de presentaciones, porque con muchos productos granel la lista se vuelve extensa y confusa para el operador. La preparacion nace desde el producto base que fisicamente se va a consumir.

Pantalla de listado:

- Folio.
- Fecha.
- Almacen.
- SKU base.
- SKU presentacion.
- Unidades preparadas.
- Consumo base.
- Estado.
- Acciones: ver, continuar borrador, confirmar.

Pantalla de captura:

- Seleccionar almacen.
- Buscar SKU base.
- Mostrar existencias disponibles por lote, caducidad y ubicacion.
- Elegir presentacion configurada.
- Capturar unidades a preparar con control entero.
- Mostrar calculo automatico: factor, consumo base, merma y saldo restante.
- Elegir lote/ubicacion origen o aplicar FEFO.
- Elegir ubicacion destino.
- Confirmar preparacion.
- Al confirmar, mostrar link a Etiquetado filtrado por folio si se generaron etiquetas.

Mensajes operativos:

- "No hay stock suficiente del SKU base."
- "La presentacion no esta configurada para preparacion."
- "No se pueden mezclar lotes en una misma linea de resultado."
- "Preparacion confirmada. Se genero salida base, entrada de presentacion y etiquetas pendientes."

## Orden recomendado de implementacion

1. Cerrar este diseno y revisar DDL propuesto.
2. Agregar auditoria de esquema en `AlmacenEsquema` en modo plan, sin ejecutar.
3. Con autorizacion: respaldo externo y aplicacion de DDL.
4. Crear metodos de dominio en `Almacenes`:
   - catalogos de preparacion.
   - validar disponibilidad base.
   - guardar borrador.
   - confirmar preparacion transaccional.
   - generar etiquetas de resultado.
5. Agregar endpoints en `Almacen.php` con permisos y auditoria explicita.
6. Crear vistas/JS de `Almacen > Preparacion/Empaque`.
7. Extender consultas de etiquetas para mostrar origen `preparacion_presentacion`.
8. Ejecutar UAT controlado con evidencia por folio.
9. Despues de cierre estable, evaluar impacto hacia Inventario visual y, en fase posterior, Ventas/ecommerce.

## UAT propuesto

| ID | Objetivo | Evidencia |
| --- | --- | --- |
| `UAT-ALM-PREP-001` | Validar que solo se puedan elegir presentaciones configuradas para preparacion. | Captura con SKU base y presentacion activa. |
| `UAT-ALM-PREP-002` | Bloquear preparacion con stock insuficiente. | Mensaje y cero movimientos creados. |
| `UAT-ALM-PREP-003` | Confirmar preparacion de `20` bolsas de `25 g` consumiendo `0.500 kg`. | Folio `PREP-*`, salida base y entrada presentacion. |
| `UAT-ALM-PREP-004` | Verificar herencia de lote/caducidad. | Resultado conserva lote/caducidad de la existencia base. |
| `UAT-ALM-PREP-005` | Generar etiquetas de bolsas propias. | Unidades con `origen_tipo='preparacion_presentacion'` y estado `pendiente_impresion`. |
| `UAT-ALM-PREP-006` | Imprimir y marcar pegadas etiquetas desde Almacen > Etiquetado. | Estados `pendiente_impresion -> impresa -> pegada`. |
| `UAT-ALM-PREP-007` | Ver kardex completo. | Misma referencia de folio en salida base y entrada presentacion. |

## Criterio de cierre de esta fase

- Documento de diseno preparado.
- Auditoria de tablas y flujo actual documentada.
- DDL propuesto listo para revision.
- Orden de implementacion definido.
- Sin migraciones ni escrituras de BD aplicadas.
