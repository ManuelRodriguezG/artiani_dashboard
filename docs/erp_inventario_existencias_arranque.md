# ERP Inventario - Existencias arranque

Documentacion IA: Codex GPT-5  
Fecha de arranque sugerida: 2026-06-19  
Modulo recomendado despues de: `docs/erp_almacen_recepciones_cierre.md`

## Objetivo del modulo

Dejar confiable la consulta y operacion de existencias antes de avanzar a Ventas/Garantias.

El modulo debe responder con seguridad:

- Que stock existe.
- En que almacen esta.
- En que lote/caducidad/ubicacion esta cuando aplique.
- Que cantidad esta disponible.
- Que movimientos explican ese saldo.
- Como se hacen ajustes y traspasos sin tocar legacy.
- Como se relacionan unidades etiquetadas con existencias.

## Reglas de trabajo

- Tareas pequenas.
- UAT documentado.
- Evidencia por folio o SKU.
- Hallazgos con ID `INV-H###`.
- Respaldo externo antes de cualquier escritura en BD.
- No mezclar ERP nuevo con endpoints legacy.
- El usuario hace pruebas visuales cuando impliquen navegar/validar pantalla; Codex hace pruebas tecnicas rapidas.

## Lectura inicial en el siguiente chat

1. `AGENTS.md`
2. `docs/erp_plan_maestro_fundamentos.md`
3. `docs/erp_almacen_recepciones_cierre.md`
4. `docs/erp_almacen_recepciones_tareas_vivas.md`
5. Este documento: `docs/erp_inventario_existencias_arranque.md`

## Archivos clave

Controladores:

- `app/controladores/Inventario.php`
- `app/controladores/Almacen.php`

Modelos:

- `app/modelos/InventarioErp.php`
- `app/modelos/Almacenes.php`
- `app/modelos/AlmacenEsquema.php`

Vistas:

- `app/vistas/paginas/apps/erp/inventarios/existencias.php`
- `app/vistas/paginas/apps/erp/inventarios/operacion.php`

JS:

- `public/assets/js/custom/apps/erp/inventarios/existencias_erp.js`
- `public/assets/js/custom/apps/erp/inventarios/operacion_erp.js`

Tablas ERP principales:

- `erp_inventario_existencias`
- `erp_inventario_movimientos`
- `erp_inventario_unidades`
- `erp_almacenes`
- `erp_almacen_ubicaciones`
- `erp_almacen_recepciones_lotes`
- `erp_catalogo_skus`
- `erp_catalogo_productos`
- `erp_catalogo_sku_reglas_inventario`

## Estado recibido desde Almacen/Recepciones

Folios base:

- `REC-OC-24`: recibida, sirve como evidencia cerrada de recepcion completa.
- `REC-OC-20`: parcial, sirve como evidencia extendida pero no debe moverse mas sin autorizacion.

Existencias relevantes creadas en UAT:

- `SAL-50L`: `5.0000` disponibles desde `REC-OC-24`.
- `TP-7838`: `5.0000` disponibles con lote/caducidad desde `REC-OC-20`.
- `TP-7840`: `5.0000` disponibles con lote/caducidad desde `REC-OC-20`.
- `SCF-800`: `1.0000` disponible con unidad etiquetada.
- `SHF-600`: `3.0000` disponibles con 3 unidades etiquetadas.
- `SP-2823`: `1.0000` disponible sin lote/caducidad/etiqueta.

Unidades etiquetadas:

- `ART-00001-23-0001`: `SCF-800`, `pegada`.
- `ART-00001-24-0001`: `SHF-600`, `pegada`.
- `ART-00001-24-0002`: `SHF-600`, `pegada`.
- `ART-00001-24-0003`: `SHF-600`, `pegada`.

## Riesgos a cuidar

- `Inventario.php` contiene endpoints legacy deshabilitados con `legadoDeshabilitado`; no reactivarlos.
- No usar tablas legacy como fuente de verdad si existe equivalente ERP.
- No hacer ajustes para "corregir" saldos sin respaldo y evidencia.
- No borrar movimientos; todo ajuste debe dejar kardex.
- No mezclar etiqueta de trazabilidad interna con serie de fabricante.
- No vender ni apartar stock desde Inventario; Ventas sera otro modulo.

## Pendiente de diseno transversal - Venta a granel

Fecha: 2026-06-19  
Area origen: Catalogo ERP / Almacen / Inventario / Ventas  
Estado: Diseno inicial de Catalogo documentado; columnas base de Catalogo aplicadas con respaldo previo  
Documento de Catalogo: `docs/erp_catalogo_venta_granel.md`

Regla recomendada:

- Catalogo debe definir si un SKU se maneja a granel.
- Catalogo debe guardar la unidad base de inventario y precision decimal:
  - Ejemplos: kilogramo, gramo, litro, mililitro, metro.
- Catalogo debe definir conversiones de compra/recepcion contra unidad base:
  - Ejemplo: comprar 1 costal de 25 kg debe entrar como `25.000 kg` de existencia.
  - Ejemplo: comprar 1 caja de 12 litros debe entrar como `12.000 l` o `12000 ml`, segun unidad base decidida.
- Inventario debe conservar saldos en unidad base, no en presentaciones de venta.
- Almacen debe recibir usando la presentacion comprada, pero convertir a unidad base al afectar existencia.
- Ventas debe descontar cantidades fraccionarias contra la misma unidad base:
  - Ejemplo: vender `0.250 kg` descuenta `0.250` de existencia.
- Lote/caducidad/FEFO siguen aplicando por lote recibido, aunque la venta sea parcial.
- No crear un SKU nuevo por cada fraccion vendida salvo que sea una presentacion empacada real distinta.

Orden recomendado para implementarlo bien:

1. Catalogo: unidad base, precision decimal, permite venta fraccionaria, incremento minimo de venta, conversiones de compra/venta.
2. Recepcion de Almacen: convertir cantidad recibida a unidad base y guardar lote/caducidad.
3. Inventario: mostrar unidad base, disponible decimal y kardex decimal.
4. Ventas: permitir captura fraccionaria, validar minimo/incremento y descontar por FEFO cuando aplique.
5. Costos: calcular costo promedio por unidad base.

Decision pendiente:

- Elegir politica de unidad base por familia/SKU. Recomendacion inicial:
  - Peso: kg con 3 decimales si se vende por gramos.
  - Liquidos: litro con 3 decimales o ml entero, segun como se facture/capture.
  - Longitud: metro con 3 decimales si se corta por centimetros/milimetros.

No implementar venta a granel solo desde Inventario; primero debe quedar definido en Catalogo para que Compras, Almacen, Inventario, Costos y Ventas usen la misma verdad.

Avance de Catalogo 2026-06-19:

- Auditoria actual: todos los SKU usan `PZA` como unidad base y `factor_unidad_base=1.000000`.
- `erp_catalogo_unidades` ya tiene unidades de masa, volumen y longitud con decimales permitidos.
- `erp_catalogo_sku_proveedores` ya guarda unidad de compra y `factor_conversion`, por lo que la conversion compra -> inventario debe vivir ahi.
- Estructura explicita para venta fraccionaria por SKU ya aplicada en `erp_catalogo_sku_reglas_inventario`: `permite_venta_fraccionaria`, `precision_decimal`, `incremento_minimo_venta`, `unidad_venta_label` y `permite_etiqueta_fraccionada`.
- Respaldo previo: `storage/backups/artianilocal_catalogo_granel_20260619_0902.sql`.
- DDL aplicado y verificado en `docs/erp_catalogo_venta_granel.md`.
- Captura, guardado y validaciones base de granel implementadas en Catalogo ERP; falta validacion visual/UAT de alta y edicion de SKU granel antes de tocar Almacen, Inventario o Ventas.
- UAT tecnico backend sin escritura ejecutado con `storage/uat/uat_catalogo_granel_validaciones.php`: `ok=true`, sin fallas. Sigue pendiente UAT visual en navegador.

Decision transversal 2026-06-20 - Presentaciones, existencias y etiquetas:

- Catalogo no debe generar existencias ni etiquetas fisicas. Catalogo solo define SKU, unidad base, conversiones, presentaciones permitidas, codigos internos y reglas de etiqueta.
- Compras debe pedir el SKU o presentacion que se compra al proveedor, usando la unidad/factor configurado en proveedor.
- Almacen Recepcion debe crear existencia cuando el producto llega fisicamente.
- Inventario debe registrar y consultar saldos/movimientos; no debe ser el lugar donde se "prepara" producto.
- Ventas/ecommerce debe consumir existencia disponible de SKU vendible o disparar una tarea de preparacion si se autoriza venta bajo demanda.

Flujos esperados:

1. Producto recibido ya empacado y vendible:
   - Ejemplo: proveedor entrega bolsas cerradas de `500 g`.
   - La orden/recepcion debe usar el SKU presentacion vendible.
   - Almacen Recepcion crea existencia de esa presentacion.
   - Si requiere codigo interno o etiqueta de trazabilidad, la etiqueta se genera en Almacen > Recepcion/Etiquetado.

2. Producto recibido como base/granel:
   - Ejemplo: proveedor entrega costal de `4 kg`.
   - La orden/recepcion puede comprar costal, pero Almacen convierte a la unidad base del SKU, por ejemplo `4.000 kg`.
   - Esta recepcion no debe crear automaticamente bolsas de `25 g`, `50 g` o `100 g`, porque todavia no existen fisicamente.
   - La etiqueta de recepcion, si aplica, identifica lote/contenedor/base recibida, no cada futura bolsa.

3. Producto embolsado o preparado internamente:
   - Debe vivir en una tarea/pantalla futura de Almacen, recomendada como `Almacen > Preparacion/Empaque`.
   - La operacion consume existencia base y crea existencia de la presentacion terminada.
   - Ejemplo: consumir `0.500 kg` de `TP-40372` para crear `20` unidades de `TP-40372-BOLSA-25G`.
   - En ese momento se generan las etiquetas de las bolsas preparadas, porque ya existe la unidad fisica que se va a etiquetar.
   - Debe conservar trazabilidad de lote/caducidad desde la existencia base hacia la presentacion preparada.

Movimientos requeridos para la futura preparacion:

- Salida de inventario del SKU base por consumo/preparacion.
- Entrada de inventario del SKU presentacion por produccion/preparacion.
- Ambos movimientos deben compartir un folio de preparacion.
- Si se generan etiquetas, `erp_inventario_unidades.origen_tipo` deberia distinguir `preparacion_presentacion` de `recepcion_compra`.

Impacto por modulo que se debe auditar antes de implementar:

- Almacen:
  - Reusar el flujo actual de etiquetado para imprimir/marcar etiquetas generadas por recepcion y por preparacion.
  - Agregar o disenar flujo de preparacion/empaque con consumo base, salida/entrada y lote/caducidad heredados.
- Inventario:
  - Mostrar existencia del SKU base y existencia de cada SKU presentacion como saldos separados.
  - Mostrar kardex de conversion/preparacion para explicar por que bajo el base y subio la presentacion.
- Compras:
  - Permitir recibir presentaciones ya empacadas cuando se compren asi al proveedor.
  - Mantener conversion compra -> inventario para costales, cajas o bolsas cerradas.
- Ventas/ecommerce:
  - Si la presentacion esta en modo `Preparada`, vender solo contra existencia de la presentacion.
  - Si se autoriza `Bajo demanda`, no descontar magicamente: debe generar pendiente/tarea de preparacion o reserva de base antes de entregar.

Tarea recomendada siguiente:

- `ALM-PREP-001`: Auditar esquema y flujo actual de Almacen para disenar `Preparacion/Empaque de presentaciones`, sin modificar Ventas ni Inventario todavia.
- Revisar especialmente `erp_inventario_existencias`, `erp_inventario_movimientos`, `erp_inventario_unidades`, `Almacenes::generar_unidades_inventario()` y Almacen > Etiquetado.
- Diseno preparado en `docs/erp_almacen_preparacion_empaque_diseno.md`; DDL propuesto, no ejecutado, en `docs/erp_almacen_preparacion_empaque_schema_propuesta.sql`.
- Pendiente transversal detectado: `ALM-CFG-001` para CRUD de almacenes, sucursales operativas y ubicaciones internas. Arranque documentado en `docs/erp_almacen_sucursales_almacenes_arranque.md`.

Hallazgo relacionado 2026-06-21:

- `CAT-GRANEL-H001` documenta que `TP-40372` quedo con `5 kg` en inventario aunque la operacion real fue `5 costales de 4 kg = 20 kg`.
- Causa encontrada: relacion proveedor-SKU `id_sku_proveedor=2310` con unidad compra `CAJA` y `factor_conversion=1.000000`.
- Criterio correcto: `erp_catalogo_sku_proveedores.factor_conversion` debe expresar cuantas unidades base entran por una unidad de compra; para este caso debe ser `4.000000`.
- Inventario no debe corregir este saldo silenciosamente; la correccion historica de `REC-OC-20` requiere respaldo externo, autorizacion y movimiento/ajuste documentado.
- Correccion de Catalogo aplicada el 2026-06-21 con respaldos `storage/backups/artianilocal_catalogo_granel_tp40372_20260621.sql` y `storage/backups/artianilocal_catalogo_granel_revert_costal_20260621.sql`: unidad compra `CAJA`, factor `4.000000`, unidad base `KG`; se elimino la unidad `COSTAL`.
- Correccion posterior: `TP-40372` queda con `erp_catalogo_skus.factor_unidad_base=4.000000` editable desde Catalogo junto a `Unidad base`.
- Pendiente: corregir el saldo historico de `REC-OC-20` como tarea de inventario/almacen separada.

## Primeras tareas sugeridas

| ID | Tarea | Alcance | Tipo | Autorizacion |
| --- | --- | --- | --- | --- |
| INV-T001 | Auditar endpoints y vistas actuales de Inventario ERP | `Inventario.php`, vistas y JS | Lectura | No |
| INV-T002 | Validar listado de existencias con SKUs UAT | `SAL-50L`, `TP-7838`, `SHF-600`, `SP-2823` | SELECT/modelo | No |
| INV-T003 | Validar kardex por folio y SKU | `REC-OC-24`, `REC-OC-20` | SELECT/modelo | No |
| INV-T004 | Revisar filtros UI de existencias | Pantalla `Inventario > Existencias` | Visual/manual | Usuario |
| INV-T005 | Auditar ajuste ERP antes de ejecutar | `InventarioErp::aplicarAjuste` | Lectura/prueba negativa | No |
| INV-T006 | Ejecutar ajuste controlado pequeno | SKU no critico | Escritura | Si, con respaldo |
| INV-T007 | Auditar traspaso ERP antes de ejecutar | `InventarioErp::aplicarTraspaso` | Lectura/prueba negativa | No |
| INV-T008 | Ejecutar traspaso controlado pequeno | Existencia disponible | Escritura | Si, con respaldo |
| INV-T009 | Revisar unidades etiquetadas vs existencia | `erp_inventario_unidades` | SELECT | No |
| INV-T010 | Documentar cierre de Inventario base | Docs | Escritura archivo | No |

## UAT propuestos

- `UAT-INV-001`: Listado de existencias muestra saldos recibidos por Almacen.
- `UAT-INV-002`: Kardex muestra movimientos por recepcion y referencia.
- `UAT-INV-003`: Busqueda por SKU/producto/folio funciona.
- `UAT-INV-004`: Ajuste de entrada/salida genera movimiento y actualiza existencia.
- `UAT-INV-005`: Traspaso entre almacenes genera salida y entrada.
- `UAT-INV-006`: No permite salida/traspaso mayor a disponible.
- `UAT-INV-007`: Existencias con lote/caducidad conservan FEFO como criterio documentado.
- `UAT-INV-008`: Unidades etiquetadas se pueden rastrear hasta existencia/recepcion.

## Avance de arranque 2026-06-19

### INV-T001 - Auditoria inicial de endpoints y pantallas

Estado: Hecho tecnico de solo lectura.

Resultado:

- `Inventario.php` protege el controlador con sesion.
- Rutas ERP actuales:
  - `/inventario/productos_existencias`
  - `/inventario/inicial`
  - `/inventario/transpaso`
  - `/inventario/buscar_skus_erp`
  - `/inventario/catalogos_erp`
  - `/inventario/existencias_erp`
  - `/inventario/movimientos_erp`
  - `/inventario/ajustar_erp`
  - `/inventario/traspasar_erp`
- Permisos usados:
  - `inventario.ver`
  - `inventario.ajustar`
  - `inventario.traspasar`
- Endpoints legacy de Inventario siguen deshabilitados con `legadoDeshabilitado`.
- `Core.php` valida CSRF en POST autenticados y tiene auditoria explicita para `Inventario.ajustar_erp` e `Inventario.traspasar_erp`.

Correccion tecnica aplicada sin escritura en BD:

- `public/assets/js/custom/apps/erp/inventarios/operacion_erp.js` ahora envia `X-CSRF-Token` en POST de ajuste/traspaso. Antes la pantalla podia fallar por CSRF antes de llegar al modelo.

### INV-T002 - Existencias UAT por SKU

Estado: Hecho tecnico de solo lectura.

Evidencia:

| SKU | Registros | Cantidad | Disponible |
| --- | ---: | ---: | ---: |
| `SAL-50L` | 2 | `5.0000` | `5.0000` |
| `TP-7838` | 1 | `5.0000` | `5.0000` |
| `TP-7840` | 1 | `5.0000` | `5.0000` |
| `SCF-800` | 1 | `1.0000` | `1.0000` |
| `SHF-600` | 1 | `3.0000` | `3.0000` |
| `SP-2823` | 1 | `1.0000` | `1.0000` |

Detalle relevante:

- `SAL-50L`: dos existencias por lotes `UAT-REC-OC-24-P1` y `UAT-REC-OC-24-C1`, total `5.0000`.
- `TP-7838`: lote `UAT-ALM-006-TP7838`, caducidad `2027-12-31`, disponible `5.0000`.
- `TP-7840`: lote `UAT-ALM-006-TP7840`, caducidad `2027-12-31`, disponible `5.0000`.
- `SCF-800`: existencia normal con unidad etiquetada, disponible `1.0000`.
- `SHF-600`: existencia normal con tres unidades etiquetadas, disponible `3.0000`.
- `SP-2823`: existencia normal sin lote ni etiqueta, disponible `1.0000`.

### INV-T003 - Kardex, lotes y unidades

Estado: Hecho tecnico de solo lectura.

Evidencia por folio:

| Folio | Estatus | Lotes | Cantidad lotes | Movimientos | Cantidad movimientos |
| --- | --- | ---: | ---: | ---: | ---: |
| `REC-OC-20` | `parcial` | 6 | `15.0000` | 6 | `15.0000` |
| `REC-OC-24` | `recibida` | 2 | `5.0000` | 2 | `5.0000` |

Unidades etiquetadas:

- `SCF-800`: `ART-00001-23-0001`, estado `pegada`, estatus `disponible`, origen `REC-OC-20`.
- `SHF-600`: `ART-00001-24-0001`, `ART-00001-24-0002`, `ART-00001-24-0003`, estado `pegada`, estatus `disponible`, origen `REC-OC-20`.

Validaciones rapidas:

- Existencias negativas: `0`.
- Movimientos con existencia inexistente: `0`.
- Unidades con SKU inexistente: `0`.

### INV-H001 - Kardex de recepciones sin saldo anterior/nuevo

Fecha: 2026-06-19  
Area: Inventario / Almacen / Kardex  
Prioridad: Media antes de cerrar Inventario base  
Estado: Corregido para movimientos futuros; historico pendiente de autorizacion si se desea backfill

Descripcion:

Los 8 movimientos existentes de `origen_tipo='recepcion_compra'` tienen `existencia_anterior=NULL` y `existencia_nueva=NULL`. La pantalla de Kardex consume esos campos y puede mostrar `0.00 / 0.00`, aunque las cantidades de movimiento y existencias sean correctas.

Impacto:

- No rompe saldos actuales.
- Si afecta lectura operativa del kardex historico, porque no explica visualmente el saldo antes/despues.

Correccion aplicada en codigo:

- `app/modelos/Almacenes.php` ahora conserva el saldo anterior y nuevo al actualizar existencia desde recepcion y los inserta en `erp_inventario_movimientos`.
- No se hizo `UPDATE` historico en BD.

Autorizacion requerida:

- Si se quiere corregir los 8 movimientos historicos de UAT para que el kardex muestre antes/despues, hace falta respaldo externo y autorizacion explicita para ejecutar backfill controlado.

Cierre aplicado:

- Autorizacion recibida en conversacion: continuar con tareas necesarias.
- Respaldo externo: `storage/backups/artianilocal_panel_20260619_antes_inv_h001_kardex_backfill.sql`.
- Backfill aplicado a 8 movimientos `recepcion_compra`.
- Verificacion posterior:
  - Movimientos de recepcion: `8`.
  - `existencia_anterior` nulos: `0`.
  - `existencia_nueva` nulos: `0`.
  - Saldos UAT sin cambios.

### INV-T005 / INV-T007 - Pruebas negativas de ajuste y traspaso

Estado: Hecho tecnico sin escritura efectiva.

Script de evidencia:

- `storage/uat/uat_inv_pruebas_negativas.php`

Resultado:

- Salida mayor a disponible para `SP-2823`: bloqueada con `Existencia insuficiente para el SKU SP-2823`.
- Traspaso con almacen origen y destino iguales: bloqueado con `Selecciona almacenes diferentes y agrega items`.
- Movimientos antes/despues: `8`.
- `SP-2823` siguio en `1.0000` cantidad y `1.0000` disponible.

### INV-T006 / INV-T008 - UAT real controlado de ajuste y traspaso

Estado: Hecho tecnico con respaldo previo.

Respaldo externo:

- `storage/backups/artianilocal_panel_20260619_antes_uat_inv_ajuste_traspaso.sql`.

Script de evidencia:

- `storage/uat/uat_inv_ajuste_traspaso_controlado.php`

SKU usado:

- `SP-2823`
- `id_sku=1138`
- Existencia origen: `id_existencia_inventario=24`
- Almacen origen: `3` (`Francisco Javier Mina 971`)
- Ubicacion origen: `12` (`UAT-ALM-013`)
- Cantidad UAT: `1.0000`

Movimientos generados:

| Movimiento | Referencia | Tipo | Almacen | Existencia | Cantidad | Antes | Despues |
| --- | --- | --- | ---: | ---: | ---: | ---: | ---: |
| 27 | `AJU-20260619170305` | entrada ajuste | 3 | 24 | `1.0000` | `1.0000` | `2.0000` |
| 28 | `AJU-20260619170306` | salida ajuste | 3 | 24 | `1.0000` | `2.0000` | `1.0000` |
| 29 | `TRA-20260619170307` | salida traspaso | 3 | 24 | `1.0000` | `1.0000` | `0.0000` |
| 30 | `TRA-20260619170307` | entrada traspaso | 1 | 25 | `1.0000` | `0.0000` | `1.0000` |
| 31 | `TRA-20260619170308` | salida traspaso | 1 | 25 | `1.0000` | `1.0000` | `0.0000` |
| 32 | `TRA-20260619170308` | entrada traspaso | 3 | 24 | `1.0000` | `0.0000` | `1.0000` |

Resultado final:

- `SP-2823` queda con total `1.0000` y disponible `1.0000`.
- Existencia `24` queda `disponible` en almacen `3`, ubicacion `12`.
- Existencia `25` queda `agotada` en almacen `1`, cantidad `0.0000`.
- Existencias negativas: `0`.
- Movimientos sin existencia: `0`.
- Unidades con SKU inexistente: `0`.

### INV-H002 - Existencias agotadas no deben ensuciar listado operativo

Fecha: 2026-06-19  
Area: Inventario > Existencias  
Prioridad: Baja/Media de UX operativa  
Estado: Corregido en listado normal

Descripcion:

El UAT de traspaso ida/regreso deja una existencia agotada en el almacen destino. Esto es correcto para trazabilidad, pero en la pantalla principal puede confundir si se muestra junto con stock disponible.

Correccion aplicada:

- `InventarioErp::listarExistencias()` oculta por defecto filas con `cantidad=0`, `cantidad_disponible=0` y `cantidad_apartada=0`.
- Se conserva soporte tecnico para incluirlas con filtro `incluir_agotadas=1`.

Verificacion:

- `SP-2823` en listado normal: `1` registro.
- `SP-2823` incluyendo agotadas: `2` registros.

### INV-T004 - Preparacion de revision visual de Existencias

Estado: Preparado tecnicamente; pendiente validacion visual del usuario.

Cambios aplicados sin escritura en BD:

- `app/vistas/paginas/apps/erp/inventarios/existencias.php`
  - Los botones `Ajuste` y `Traspaso` ahora se muestran solo si la sesion tiene `inventario.ajustar` o `inventario.traspasar`.
  - Se agrego filtro visual `Agotadas`.
  - Se versiono el asset JS con `v=20260619-2`.
- `public/assets/js/custom/apps/erp/inventarios/existencias_erp.js`
  - El filtro `Agotadas` envia `incluir_agotadas=1`.
  - Las filas agotadas se distinguen con badge `Agotada` cuando se incluyen.
  - Kardex muestra `-` si algun saldo anterior/nuevo viniera nulo.
  - Kardex muestra tambien `codigo_existencia` bajo la referencia.

Validacion visual pendiente:

- Abrir `/inventario/productos_existencias`.
- Buscar `SP-2823`: debe aparecer 1 existencia operativa.
- Activar `Agotadas`: debe aparecer tambien la existencia agotada del traspaso UAT.
- Buscar `REC-OC-24`: kardex debe mostrar movimientos de `SAL-50L` con antes/despues.
- Buscar `TRA-20260619170307`: kardex debe mostrar salida/entrada del traspaso UAT.
- Confirmar que botones `Ajuste` y `Traspaso` respetan permisos del usuario.

### INV-H003 - Captura de cantidad en ajuste/traspaso era input numerico generico

Fecha: 2026-06-19  
Area: Inventario > Ajuste / Traspaso  
Prioridad: Media de UX operativa  
Estado: Corregido tecnicamente; pendiente validacion visual

Descripcion:

La pantalla de operacion usaba input numerico generico para cantidades. Segun `docs/erp_ux_operativa.md`, las cantidades operativas deben tener controles estables y reducir errores de captura.

Correccion aplicada:

- `app/vistas/paginas/apps/erp/inventarios/operacion.php`
  - Se agrego estilo local para control de cantidad estable.
  - Se versiono el asset JS con `v=20260619-2`.
- `public/assets/js/custom/apps/erp/inventarios/operacion_erp.js`
  - Cantidad ahora se captura con control `- [cantidad] +`.
  - Se permite escritura manual decimal con normalizacion.
  - Se valida antes de enviar:
    - almacen obligatorio en ajuste;
    - almacenes diferentes en traspaso;
    - cantidades mayores a cero;
    - salida/traspaso no mayor al disponible mostrado.
  - Se conserva CSRF por `X-CSRF-Token`.

Validacion visual pendiente:

- Abrir `/inventario/inicial`.
- Agregar `SP-2823`.
- Confirmar que el control `- [1] +` no rompe la tabla.
- Probar salida de `2` con disponible `1`: debe bloquearse antes de enviar.
- Abrir `/inventario/transpaso`.
- Confirmar que origen/destino iguales se bloquea antes de enviar.

Siguiente autorizacion requerida:

- No ejecutar mas ajustes o traspasos reales hasta que el usuario valide visualmente estas pantallas o autorice otro UAT con respaldo.

### INV-T009 - Unidades etiquetadas visibles desde Inventario

Estado: Hecho tecnico read-only; pendiente validacion visual.

Objetivo:

Inventario debe poder consultar unidades etiquetadas y rastrearlas hasta recepcion/almacen sin entrar al flujo operativo de impresion/pegado de Almacen.

Cambios aplicados sin escritura en BD:

- `app/controladores/Inventario.php`
  - Nuevo endpoint read-only `/inventario/unidades_erp`.
  - Permiso requerido: `inventario.ver`.
  - Reutiliza `InventarioErp::listarEtiquetas()`.
- `app/vistas/paginas/apps/erp/inventarios/existencias.php`
  - Nueva pestana `Unidades`.
  - Tabla: codigo, SKU/producto, almacen, lote/caducidad, origen y estado.
- `public/assets/js/custom/apps/erp/inventarios/existencias_erp.js`
  - Carga unidades junto con existencias y kardex.
  - Usa los mismos filtros `q` e `id_almacen`.
  - Permite abrir directo con `#unidades`.
  - Muestra resumen por estado: unidades, pendientes, impresas y pegadas.

Evidencia tecnica:

- `SCF-800`: 1 unidad, estado `pegada`.
- `SHF-600`: 3 unidades, estado `pegada`.
- Unidades con origen `REC-OC-20`: `4`.

Validacion visual pendiente:

- Abrir `/inventario/productos_existencias#unidades`.
- Buscar `SCF-800`: debe aparecer `ART-00001-23-0001`.
- Buscar `SHF-600`: deben aparecer `ART-00001-24-0001`, `ART-00001-24-0002`, `ART-00001-24-0003`.
- Buscar `REC-OC-20`: debe mostrar las 4 unidades etiquetadas.
- Confirmar que Inventario solo consulta; impresion/pegado sigue viviendo en Almacen > Etiquetado.

### INV-T011 - Correccion documentada de saldo TP-40372 por CAT-GRANEL-H001

Fecha: 2026-06-21  
Origen operativo: `REC-OC-20`  
Motivo: `CAT-GRANEL-H001`  
Area: Inventario > Existencias / Kardex  
Estado: Hecho con respaldo previo

Objetivo:

Corregir saldo historico de `TP-40372` sin tocar Catalogo, Ventas/ecommerce ni reabrir Recepcion.

Contexto:

- SKU: `TP-40372`
- Unidad base configurada en Catalogo: `KG`
- Factor unidad base: `4.000000`
- Proveedor: `SUNNY`
- Unidad compra: `CAJA`
- Factor conversion: `4.000000`
- La recepcion historica registro `5 kg`.
- Operativamente eran `5` unidades de compra x `4 kg` = `20 kg`.

Evidencia antes:

| Existencia | Lote | Caducidad | Almacen | Ubicacion | Cantidad | Disponible |
| ---: | --- | --- | --- | --- | ---: | ---: |
| 26 | `L1` | `2026-10-30` | `Francisco Javier Mina 971` | `E1-C2-P1-A1-N3` | `4.0000` | `4.0000` |
| 27 | `L2` | `2027-01-29` | `Francisco Javier Mina 971` | `E1-C2-P1-A1-N3` | `1.0000` | `1.0000` |

Movimientos de recepcion originales:

| Movimiento | Referencia | Origen | Existencia | Lote | Cantidad | Antes | Despues |
| ---: | --- | --- | ---: | --- | ---: | ---: | ---: |
| 33 | `REC-OC-20` | `recepcion_compra` | 26 | `L1` | `4.0000` | `0.0000` | `4.0000` |
| 34 | `REC-OC-20` | `recepcion_compra` | 27 | `L2` | `1.0000` | `0.0000` | `1.0000` |

Mejora aplicada antes del ajuste:

- `InventarioErp::aplicarAjuste()` y `InventarioErp::aplicarTraspaso()` ahora aceptan `referencia` documentada opcional.
- La referencia se normaliza a `A-Z`, numeros, guion y guion bajo.
- Si no se envia referencia, conserva comportamiento anterior `AJU-YYYYMMDDHHMMSS` o `TRA-YYYYMMDDHHMMSS`.

Respaldo externo:

- `storage/backups/artianilocal_panel_20260621_antes_cat_granel_h001_tp40372.sql`

Script de evidencia:

- `storage/uat/uat_inv_cat_granel_h001_tp40372.php`

Ajuste aplicado:

| Movimiento | Referencia | Origen | Existencia | Lote | Cantidad | Antes | Despues |
| ---: | --- | --- | ---: | --- | ---: | ---: | ---: |
| 35 | `CAT-GRANEL-H001` | `ajuste` | 26 | `L1` | `12.0000` | `4.0000` | `16.0000` |
| 36 | `CAT-GRANEL-H001` | `ajuste` | 27 | `L2` | `3.0000` | `1.0000` | `4.0000` |

Observacion del ajuste:

`CAT-GRANEL-H001 correccion historica REC-OC-20 TP-40372: conversion granel 5 cajas x 4 kg = 20 kg; ajuste L1 +12 kg y L2 +3 kg sin reabrir recepcion`

Evidencia despues:

| Existencia | Lote | Caducidad | Almacen | Ubicacion | Cantidad | Disponible |
| ---: | --- | --- | --- | --- | ---: | ---: |
| 26 | `L1` | `2026-10-30` | `Francisco Javier Mina 971` | `E1-C2-P1-A1-N3` | `16.0000` | `16.0000` |
| 27 | `L2` | `2027-01-29` | `Francisco Javier Mina 971` | `E1-C2-P1-A1-N3` | `4.0000` | `4.0000` |

Resultado:

- Total `TP-40372`: `20.0000 kg`.
- Disponible `TP-40372`: `20.0000 kg`.
- Existencias negativas: `0`.
- Movimientos sin existencia: `0`.
- Movimientos con referencia `CAT-GRANEL-H001`: `2`.

Validacion visual pendiente:

- Abrir `/inventario/productos_existencias`.
- Buscar `TP-40372`.
- Confirmar L1 `16.00`, L2 `4.00`, total `20.00`.
- Buscar `CAT-GRANEL-H001` en Kardex y confirmar movimientos 35/36.

### INV-T012 - Impacto de ALM-PREP-001 en existencias

Fecha: 2026-06-21  
Documento origen: `docs/erp_almacen_preparacion_empaque_diseno.md`

Preparacion confirmada:

- Folio: `PREP-20260622-0001`.
- Almacen: `BOD971`.
- Salida base: `0.5000 kg` de `TP-40372`, lote `L1`.
- Entrada presentacion: `20.0000` unidades de `TP-40372-25GR`, lote `L1`.
- Movimientos: salida `37`, entrada `38`.
- Etiquetas generadas: `20`, rango `P25-P000002-0001` a `P25-P000002-0020`.

Saldos esperados despues de la preparacion:

- `TP-40372`, lote `L1`: `15.5000 kg` disponibles.
- `TP-40372`, lote `L2`: `4.0000 kg` disponibles.
- `TP-40372-25GR`, lote `L1`: `20.0000` disponibles.

Validacion pendiente en Inventario:

- Existencias debe mostrar SKU base y SKU presentacion como saldos separados.
- Kardex debe mostrar salida y entrada con la misma referencia `PREP-20260622-0001`.
- Etiquetado debe mostrar etiquetas con `origen_tipo='preparacion_presentacion'`.

### INV-T013 - Impacto de reempaque ALM-PREP-T015

Fecha: 2026-06-21  
Documento origen: `docs/erp_almacen_preparacion_empaque_diseno.md`

Preparaciones confirmadas:

- `PREP-20260621-0002`: `TP-40372` lote `L1` salida `0.5000 kg` -> entrada `1.0000` de `TP-40372-500GR`.
- `PREP-20260621-0003`: `TP-40372-500GR` existencia `EXI-50-29` salida `1.0000` -> entrada `5.0000` de `TP-40372-100GR`.

Movimientos:

- `39`: salida `TP-40372`, referencia `PREP-20260621-0002`.
- `40`: entrada `TP-40372-500GR`, referencia `PREP-20260621-0002`.
- `41`: salida `TP-40372-500GR`, referencia `PREP-20260621-0003`.
- `42`: entrada `TP-40372-100GR`, referencia `PREP-20260621-0003`.

Saldos esperados despues:

- `TP-40372`, lote `L1`: `15.0000 kg`.
- `TP-40372`, lote `L2`: `4.0000 kg`.
- `TP-40372-500GR`, `EXI-50-29`: `0.0000`, estado `agotada`.
- `TP-40372-100GR`, `EXI-50-30`: `5.0000`, estado `disponible`.

Etiquetas:

- `5` etiquetas `P100-P000004-0001` a `P100-P000004-0005`.
- Estado inicial: `pendiente_impresion`.
- Estado verificado despues de UAT visual: `pegada`.
- `fecha_impresion`: `2026-06-21 23:09:01`.
- `fecha_etiquetado`: `2026-06-21 23:09:38`.
- Origen: `preparacion_presentacion`, `origen_id=4`.

### INV-H004 - Busqueda de trazabilidad no encontraba existencias agotadas por folio/etiqueta

Fecha: 2026-06-21  
Area: Inventario > Existencias / Kardex / Unidades  
Prioridad: Media para cierre de trazabilidad de preparacion  
Estado: Corregido tecnicamente; pendiente validacion visual

Descripcion:

El listado operativo de Existencias oculta correctamente registros agotados para no ensuciar la consulta diaria. Sin embargo, al buscar trazabilidad por folio `PREP-*`, motivo `CAT-*`, recepcion `REC-*`, codigo de existencia o etiqueta interna, Inventario debe mostrar tambien las existencias agotadas relacionadas.

Caso detectado:

- `TP-40372-500GR` queda correctamente en `0.0000`, estado `agotada`.
- Es pieza clave para explicar `PREP-20260621-0003`.
- Sin una busqueda de trazabilidad ampliada, el operador podia ver el Kardex pero no la existencia agotada relacionada desde la pestana Existencias.

Correccion aplicada sin escritura en BD:

- `InventarioErp::listarExistencias()` ahora busca tambien por:
  - referencia de movimiento;
  - codigo de existencia en movimientos;
  - codigo de etiqueta interna;
  - serie/codigo unico de unidad;
  - folio de recepcion;
  - folio de preparacion.
- Una existencia agotada se sigue ocultando en el listado normal, pero aparece cuando coincide con una busqueda explicita por SKU, codigo de existencia, folio o etiqueta.
- `InventarioErp::listarMovimientos()` ahora permite buscar por codigo de existencia, lote, origen y etiqueta/unidad relacionada.
- La UI muestra `codigo_existencia` debajo del SKU y resume cuantas agotadas aparecen en el resultado.
- En Kardex, `origen_tipo='preparacion_presentacion'` se distingue visualmente.

Evidencia tecnica:

| Busqueda | Existencias encontradas | Resultado clave |
| --- | ---: | --- |
| `PREP-20260621-0003` | 2 | `TP-40372-100GR` disponible y `TP-40372-500GR` agotada |
| `P100-P000004-0001` | 1 | `TP-40372-100GR`, `EXI-50-30`, disponible |
| `CAT-GRANEL-H001` | 2 | `TP-40372` L1/L2 relacionadas al ajuste |
| `TP-40372-500GR` | 1 | `EXI-50-29`, `0.0000`, `agotada` |

Validaciones:

- `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\inventarios\existencias.php`: OK.
- `node --check public\assets\js\custom\apps\erp\inventarios\existencias_erp.js`: OK.

Validacion visual pendiente:

- Abrir `/inventario/productos_existencias`.
- Buscar `PREP-20260621-0003`: deben aparecer `TP-40372-100GR` disponible y `TP-40372-500GR` agotada.
- Buscar `TP-40372-500GR`: debe aparecer `EXI-50-29` como agotada aunque el filtro `Agotadas` este apagado.
- Buscar `P100-P000004-0001`: debe aparecer la existencia `EXI-50-30` y la unidad pegada en la pestana Unidades.

### INV-T014 - Validacion visual/render de trazabilidad PREP

Fecha: 2026-06-21  
Area: Inventario > Existencias / Kardex / Unidades  
Estado: Hecho tecnico con fixture visual; pendiente validacion en navegador con sesion real del usuario

Objetivo:

Validar que la mejora de `INV-H004` se renderiza correctamente en la interfaz, sin ejecutar escrituras en BD.

Alcance:

- Se intento abrir la pantalla real `/inventario/productos_existencias`.
- La ruta redirige correctamente a `/autenticacion/login` cuando no hay sesion activa.
- No se pidieron ni usaron credenciales.
- No se modificaron usuarios, roles ni permisos.
- Se creo un fixture visual controlado que usa el JS real `existencias_erp.js` con datos equivalentes al UAT `PREP-20260621-0003`.

Artefactos:

- `public/assets/uat/inv_existencias_visual_fixture.html`
- `storage/uat/uat_inv_visual_render_check.js`

Validacion ejecutada:

- `node --check storage\uat\uat_inv_visual_render_check.js`: OK.
- `node storage\uat\uat_inv_visual_render_check.js`: `ok=true`.

Checks aprobados:

- Existencias incluye `TP-40372-100GR`.
- Existencias incluye `TP-40372-500GR` como `Agotada`.
- Resumen muestra `Agotadas 1`.
- Kardex muestra `origen_tipo='preparacion_presentacion'`.
- Kardex muestra referencia `PREP-20260621-0003`.
- Unidades muestra `P100-P000004-0001` como `Pegada`.

HTML renderizado clave:

- Resumen: `Registros 2`, `Existencia 5.00`, `Disponible 5.00`, `Agotadas 1`.
- Existencias: `TP-40372-100GR` con `EXI-50-30` disponible y `TP-40372-500GR` con `EXI-50-29` agotada.
- Kardex: entrada `+5.00` a `TP-40372-100GR` y salida `-1.00` de `TP-40372-500GR`, ambas con `PREP-20260621-0003`.
- Unidades: `P100-P000004-0001`, origen `PREP-20260621-0003`, estado `Pegada`.

Nota:

La captura grafica automatizada con Chrome/Edge headless no genero PNG desde esta sesion, aunque el render de DOM con el JS real si fue validado. La validacion visual final en navegador debe hacerse con una sesion real del usuario:

- Abrir `/inventario/productos_existencias`.
- Buscar `PREP-20260621-0003`.
- Confirmar que aparecen las filas y badges descritos arriba.

### INV-H005 - Unidades no debe interpretarse como listado de existencias

Fecha: 2026-06-22  
Area: Inventario > Unidades  
Prioridad: Baja/Media de UX operativa  
Estado: Aclarado tecnicamente; ayuda visual aplicada

Observacion de UAT:

Al buscar `TP-40372-500GR` en la pestana `Unidades`, el usuario espera verlo como agotado. No aparece.

Resultado de auditoria:

- `PREP-20260621-0002` creo la existencia `TP-40372-500GR`, `EXI-50-29`, pero con `etiquetas_generadas=0`.
- `EXI-50-29` fue consumida por `PREP-20260621-0003` y quedo en `0.0000`, estado `agotada`.
- La pestana `Unidades` consulta `erp_inventario_unidades`; por eso solo muestra SKUs que generan etiqueta interna.
- `TP-40372-500GR` no tiene unidad/etiqueta individual registrada, por lo tanto su trazabilidad debe verse en:
  - `Existencias`: `EXI-50-29`, agotada;
  - `Kardex`: entrada en `PREP-20260621-0002` y salida en `PREP-20260621-0003`.

Evidencia:

| Folio | SKU resultado | Existencia | Cantidad | Estado existencia | Etiquetas generadas |
| --- | --- | --- | ---: | --- | ---: |
| `PREP-20260621-0002` | `TP-40372-500GR` | `EXI-50-29` | `0.0000` | `agotada` | `0` |
| `PREP-20260621-0003` | `TP-40372-100GR` | `EXI-50-30` | `5.0000` | `disponible` | `5` |

Correccion UX aplicada:

- Cuando la pestana `Unidades` no encuentra registros, el mensaje ahora aclara que solo muestra SKUs con etiqueta interna.
- El mensaje recomienda revisar `Existencias` o `Kardex` para saldos sin etiqueta, agotados o movimientos de preparacion.

Validacion esperada:

- Buscar `TP-40372-500GR` en `Existencias`: debe aparecer `EXI-50-29` como `Agotada`.
- Buscar `TP-40372-500GR` en `Kardex`: deben aparecer movimientos `40` entrada y `41` salida.
- Buscar `TP-40372-500GR` en `Unidades`: puede mostrar `Sin unidades etiquetadas`; eso es correcto.

### INV-T015 - Operaciones por existencia fisica especifica

Fecha: 2026-06-22  
Area: Inventario > Ajuste / Traspaso  
Estado: Corregido tecnicamente; pendiente UAT real con respaldo antes de escritura BD

Objetivo:

Evitar que un ajuste de salida o traspaso descuente una existencia diferente a la que el operador cree estar seleccionando.

Hallazgo:

- `InventarioErp::aplicarAjuste()` y `InventarioErp::aplicarTraspaso()` ya soportaban `id_existencia_inventario`.
- La UI no exponia ese selector.
- En salidas/traspasos, los campos `lote` y `fecha_caducidad` se veian editables, pero el backend no los usaba como filtro de salida.
- Eso podia generar una falsa sensacion de control: el operador capturaba lote/caducidad, pero el modelo podia descontar por FEFO o por la primera existencia disponible.

Decision ERP robusta:

- Para entradas de ajuste:
  - mantener captura de lote/caducidad/ubicacion, porque puede crear o incrementar una existencia.
- Para salidas de ajuste y traspasos:
  - obligar a elegir una existencia fisica real `EXI-*`;
  - mostrar lote, caducidad, ubicacion y disponible desde la existencia;
  - enviar `id_existencia_inventario` al backend;
  - validar que la cantidad no supere el disponible de esa existencia.

Cambios aplicados sin escritura en BD:

- `app/vistas/paginas/apps/erp/inventarios/operacion.php`
  - La columna cambia conceptualmente a `Existencia / lote`.
  - Asset versionado a `v=20260622-1`.
- `public/assets/js/custom/apps/erp/inventarios/operacion_erp.js`
  - Detecta si la operacion es salida o traspaso.
  - Al agregar SKU en salida/traspaso, consulta `/inventario/existencias_erp` por almacen origen.
  - Filtra existencias con `cantidad_disponible > 0`.
  - Renderiza selector de existencia `EXI-* | lote | ubicacion | disponible`.
  - Guarda `id_existencia_inventario`, lote, caducidad, ubicacion y disponible de la existencia seleccionada.
  - Bloquea aplicar si no hay existencia fisica seleccionada.
  - Limpia partidas al cambiar almacen origen o tipo de ajuste para evitar mezclar disponibilidad de otro contexto.

Evidencia tecnica read-only:

| Busqueda | Resultado |
| --- | --- |
| `TP-40372` en almacen `BOD971` | `EXI-50-26` disponible `15.0000`, `EXI-50-27` disponible `4.0000` |
| `TP-40372-500GR` en almacen `BOD971` | `EXI-50-29` existe, pero disponible `0.0000`, estado `agotada` |

Validaciones:

- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\inventarios\operacion.php`: OK.
- `node --check public\assets\js\custom\apps\erp\inventarios\operacion_erp.js`: OK.
- `node --check storage\uat\uat_inv_operacion_existencia_check.js`: OK.
- `node storage\uat\uat_inv_operacion_existencia_check.js`: `ok=true`.

Validacion visual sugerida:

- Abrir `/inventario/inicial`.
- Seleccionar almacen `BOD971`.
- Tipo `Salida`.
- Buscar `TP-40372`.
- Debe aparecer selector de existencia con `EXI-50-26` y `EXI-50-27`.
- Cambiar tipo a `Entrada`: la tabla debe limpiarse y volver a permitir lote/caducidad manual.
- Abrir `/inventario/transpaso`.
- Seleccionar origen `BOD971` y un destino distinto.
- Buscar `TP-40372`.
- Debe aparecer selector de existencia origen.
- Buscar `TP-40372-500GR`: debe permitir agregar SKU pero mostrar sin existencia disponible o bloquear al aplicar.

UAT real pendiente:

- No ejecutar ajuste o traspaso real sin respaldo externo previo.
- Caso recomendado: usar una cantidad pequena contra una existencia UAT y revertirla con movimiento documentado, validando kardex antes/despues.

### INV-T016 - Inventario inicial robusto para apertura de tiendas

Fecha: 2026-06-22  
Area: Inventario > Inicial / Ajuste entrada  
Estado: Diseno y mejoras UI parciales; pendiente modelo de etiquetas y UAT con respaldo

Origen:

El negocio ya tiene tiendas operando con stock historico. Ese stock no debe cargarse como Compras/Recepcion si no proviene de una orden/recepcion real. Debe cargarse como apertura o inventario inicial documentado.

Decision ERP robusta:

- Las ubicaciones deben darse de alta primero en `Almacen > Configuracion`, no crearse libremente desde Inventario.
- Inventario inicial debe mover stock contra ubicaciones existentes.
- Inventario inicial debe permitir varias lineas del mismo SKU cuando cambian lote, caducidad o ubicacion.
- Salidas/traspasos deben permitir varias lineas del mismo SKU cuando cambia la existencia fisica `EXI-*`.
- Para SKUs fraccionarios/granel, la UI debe mostrar unidad e incremento configurado desde Catalogo.
- Si el SKU genera etiqueta interna, la entrada inicial debe poder generar unidades/etiquetas con `origen_tipo='inventario_inicial'`; esto requiere mejora de modelo antes de ejecutar UAT real.

Cambios aplicados sin escritura en BD:

- `InventarioErp::buscarSkus()` ahora devuelve reglas utiles para UI:
  - `permite_venta_fraccionaria`;
  - `precision_decimal`;
  - `incremento_minimo_venta`;
  - `unidad_venta_label`;
  - `generar_etiqueta_interna`;
  - `requiere_lote`;
  - `requiere_caducidad`.
- `public/assets/js/custom/apps/erp/inventarios/operacion_erp.js`:
  - permite multiples lineas del mismo SKU;
  - en salida/traspaso intenta asignar una existencia libre distinta cuando se agrega el mismo SKU varias veces;
  - valida acumulado por `id_existencia_inventario` para no exceder disponible;
  - usa `incremento_minimo_venta` como paso de botones `+/-`;
  - muestra badges `Fraccionario` y `Etiqueta`;
  - bloquea decimales en SKUs que no permiten venta fraccionaria.

Validaciones tecnicas:

- `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\inventarios\operacion.php`: OK.
- `node --check public\assets\js\custom\apps\erp\inventarios\operacion_erp.js`: OK.
- `node storage\uat\uat_inv_operacion_existencia_check.js`: `ok=true`.

Validacion BD pendiente:

- MySQL local quedo no responsivo durante la validacion inicial (`mysqladmin ping` sin respuesta).
- Validacion retomada en `INV-T017` despues de recuperar MySQL: `buscarSkus()` ya devuelve unidad/reglas para SKUs reales.

Siguiente paso tecnico recomendado:

- Crear en `InventarioErp` un flujo separado o modo documentado de `inventario_inicial` que:
  - use referencia obligatoria, por ejemplo `INV-INICIAL-YYYYMMDD-*`;
  - valide reglas de lote/caducidad desde Catalogo;
  - genere movimiento de entrada;
  - si `generar_etiqueta_interna=1`, cree unidades en `erp_inventario_unidades`;
  - use `origen_tipo='inventario_inicial'`;
  - no reemplace Compras/Recepcion.

Autorizacion requerida para continuar:

- Para cualquier UAT real de carga inicial que escriba existencias, movimientos o etiquetas:
  - respaldo externo previo;
  - folio/documento de prueba;
  - evidencia antes/despues.

### INV-T017 - Flujo documentado de inventario inicial con etiquetas

Fecha: 2026-06-22  
Area: Inventario > Inicial / Ajuste entrada  
Estado: UAT real controlado aprobado tecnicamente

Objetivo:

Separar operativamente una carga de apertura/inventario inicial de un ajuste comun, para que el stock historico de tiendas quede trazable sin simular compras, recepciones o preparaciones.

Decision ERP robusta:

- Inventario inicial usa `origen_tipo='inventario_inicial'`.
- La referencia documental es obligatoria y debe iniciar con `INV-INICIAL-`.
- Solo permite entradas; las salidas se mantienen como `ajuste`.
- Respeta reglas de Catalogo:
  - SKU con lote requerido debe capturar lote.
  - SKU con caducidad requerida debe capturar caducidad.
  - SKU sin venta fraccionaria no permite decimales.
  - SKU con etiqueta interna debe cargarse en unidades enteras.
- Si `generar_etiqueta_interna=1`, crea unidades en `erp_inventario_unidades` con:
  - `origen_tipo='inventario_inicial'`;
  - `origen_id=id_movimiento_inventario`;
  - `origen_detalle_id=indice de partida`;
  - `estado_etiqueta='pendiente_impresion'`;
  - `id_existencia_inventario`, almacen, ubicacion, lote y caducidad de la existencia creada/incrementada.

Cambios aplicados sin ejecutar UAT real de escritura:

- `app/modelos/InventarioErp.php`
  - `aplicarAjuste()` ahora distingue `documento_operacion='inventario_inicial'`.
  - `referenciaMovimiento()` puede exigir folio y validar prefijo `INV-INICIAL-`.
  - `consultarSku()` carga reglas de inventario desde Catalogo.
  - `validarReglasItem()` centraliza reglas de lote, caducidad, enteros y etiquetas.
  - `generarUnidadesInventarioInicial()` crea etiquetas internas cuando corresponde.
  - `listarEtiquetas()` muestra referencias de inventario inicial usando el movimiento origen.
- `app/vistas/paginas/apps/erp/inventarios/operacion.php`
  - Agrega documento `Inventario inicial` / `Ajuste documentado`.
  - Agrega campo `Referencia`.
  - Versiona `operacion_erp.js` a `v=20260622-2`.
- `public/assets/js/custom/apps/erp/inventarios/operacion_erp.js`
  - Envia `documento_operacion` y `referencia`.
  - Exige prefijo `INV-INICIAL-` para inventario inicial.
  - Deshabilita inventario inicial cuando el tipo es salida.
  - Valida lote/caducidad antes de enviar.
  - Bloquea decimales para SKUs con etiqueta interna.
- `storage/uat/uat_inv_operacion_existencia_check.js`
  - Agrega checks de referencia, documento, lote/caducidad y limpieza al cambiar documento.

Validacion BD read-only ya con MySQL estable:

| SKU | Disponible almacen 3 | Unidad UI | Etiqueta | Lote requerido | Caducidad requerida |
| --- | ---: | --- | ---: | ---: | ---: |
| `TP-40372` | `19.0000` | `kg` | `0` | `1` | `1` |
| `TP-40372-25GR` | `20.0000` | `g` | `1` | `1` | `1` |
| `TP-40372-100GR` | `5.0000` | `g` | `1` | `1` | `1` |
| `TP-40372-500GR` | `0.0000` | `pza` | `0` | `1` | `1` |

Validaciones tecnicas:

- `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\inventarios\operacion.php`: OK.
- `node --check public\assets\js\custom\apps\erp\inventarios\operacion_erp.js`: OK.
- `node storage\uat\uat_inv_operacion_existencia_check.js`: `ok=true`.

UAT real ejecutado:

- Respaldo externo previo:
  - `storage/backups/artianilocal_panel_20260622_092114_antes_inv_inicial_uat01.sql`
- Script protegido:
  - `storage/uat/uat_inv_inicial_etiquetas.php`
  - Sin `--execute` no afecta inventario.
  - Bloquea reejecucion si ya existe la referencia.
- Folio:
  - `INV-INICIAL-20260622-UAT01`
- SKU:
  - `TP-40372-25GR`
- Almacen:
  - `3` / `BOD971`
- Ubicacion:
  - `12` / `UAT-ALM-013`
- Lote:
  - `UAT-INV-INICIAL-25GR`
- Caducidad:
  - `2027-12-31`
- Cantidad:
  - `1.0000`

Evidencia antes:

| Indicador | Valor |
| --- | ---: |
| Existencias previas del SKU en almacen 3 | `1` |
| Movimientos con referencia `INV-INICIAL-20260622-UAT01` | `0` |
| Unidades con referencia `INV-INICIAL-20260622-UAT01` | `0` |

Resultado:

| Elemento | Valor |
| --- | --- |
| Respuesta modelo | `Inventario inicial ERP aplicado` |
| Movimientos generados | `1` |
| Etiquetas generadas | `1` |
| Origen tipo | `inventario_inicial` |
| Existencia | `EXI-50-31` |
| Movimiento | `43` |
| Etiqueta | `P25-II000043-0001` |

Evidencia despues:

| Vista | Busqueda | Resultado |
| --- | --- | --- |
| Existencias | `INV-INICIAL-20260622-UAT01` | `EXI-50-31`, `TP-40372-25GR`, lote `UAT-INV-INICIAL-25GR`, disponible `1.0000`, estado `disponible` |
| Kardex | `INV-INICIAL-20260622-UAT01` | Movimiento `43`, entrada `1.0000`, origen `inventario_inicial`, existencia `EXI-50-31` |
| Unidades | `INV-INICIAL-20260622-UAT01` | `P25-II000043-0001`, estado `pendiente_impresion`, origen `inventario_inicial` |

Conclusion:

- Inventario inicial ya puede cargar stock historico documentado sin tocar Compras/Recepcion.
- El Kardex distingue la entrada con `origen_tipo='inventario_inicial'`.
- Las etiquetas quedan consultables desde Inventario > Existencias > Unidades por folio de inventario inicial.

### INV-T018 - Puente Inventario > Unidades hacia Almacen > Etiquetado

Fecha: 2026-06-22  
Area: Inventario > Existencias > Unidades / Almacen > Etiquetado  
Estado: UAT real controlado aprobado tecnicamente

Objetivo:

Permitir que Inventario consulte la trazabilidad de unidades/etiquetas, pero que la operacion fisica de imprimir y marcar pegada siga viviendo en Almacen > Etiquetado.

Decision ERP robusta:

- Inventario no duplica botones de impresion/pegado.
- Inventario muestra una accion `Etiquetado` que abre `/almacen/etiquetado` con:
  - codigo de etiqueta;
  - estado de etiqueta;
  - almacen.
- Almacen conserva los endpoints operativos:
  - `/almacen/etiquetas_marcar_impresas_erp`;
  - `/almacen/etiquetas_marcar_pegadas_erp`;
  - auditoria bajo modulo `almacen`.

Cambios aplicados:

- `app/vistas/paginas/apps/erp/inventarios/existencias.php`
  - Agrega columna `Acciones` en pestana `Unidades`.
  - Versiona `existencias_erp.js` a `v=20260622-2`.
- `public/assets/js/custom/apps/erp/inventarios/existencias_erp.js`
  - Agrega `urlEtiquetado(item)`.
  - Renderiza boton `Etiquetado` por unidad.
- `storage/uat/uat_inv_visual_render_check.js`
  - Valida que la pestana Unidades genere enlace a `/almacen/etiquetado`.

Validaciones tecnicas:

- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\inventarios\existencias.php`: OK.
- `node --check public\assets\js\custom\apps\erp\inventarios\existencias_erp.js`: OK.
- `node storage\uat\uat_inv_visual_render_check.js`: `ok=true`.

UAT real de estado de etiqueta:

- Respaldo externo previo:
  - `storage/backups/artianilocal_panel_20260622_092916_antes_inv_etiqueta_uat01.sql`
- Script protegido:
  - `storage/uat/uat_inv_etiqueta_inicial_estado.php`
  - Sin `--execute` no afecta estado.
- Etiqueta:
  - `P25-II000043-0001`
- Referencia:
  - `INV-INICIAL-20260622-UAT01`
- Estado antes:
  - `pendiente_impresion`
- Estado despues:
  - `pegada`
- Fechas registradas:
  - `fecha_impresion=2026-06-22 09:29:52`
  - `fecha_etiquetado=2026-06-22 09:29:52`

Evidencia despues:

| Busqueda | Resultado |
| --- | --- |
| `INV-INICIAL-20260622-UAT01` en Unidades | `P25-II000043-0001`, estado `pegada`, origen `inventario_inicial` |
| `P25-II000043-0001` en Unidades | `INV-INICIAL-20260622-UAT01`, estado `pegada`, origen `inventario_inicial` |

Conclusion:

- El ciclo de inventario inicial con etiqueta queda probado: entrada documentada -> unidad generada -> etiqueta impresa/pegada -> consulta por folio/codigo.
- Inventario queda como trazabilidad; Almacen queda como operacion fisica de etiquetado.

## INV-T019 - Ficha unica de trazabilidad en Existencias/Kardex

Fecha: 2026-06-22

Objetivo:

Permitir consultar desde Inventario una ficha de lectura que una existencia, movimientos de kardex y unidades/etiquetas relacionadas por codigo de existencia, folio documental, SKU o etiqueta.

Decision ERP robusta:

- Inventario conserva naturaleza de consulta/auditoria.
- La ficha no escribe base de datos.
- La ficha ayuda a validar folios operativos (`REC-*`, `PREP-*`, `INV-INICIAL-*`) sin reabrir recepciones/preparaciones ni hacer ajustes.
- La operacion fisica de etiquetas sigue en Almacen > Etiquetado.

Cambios aplicados:

- `app/modelos/InventarioErp.php`
  - Agrega `consultarTrazabilidad($filtros)`.
  - Relaciona `erp_inventario_existencias`, `erp_inventario_movimientos` y `erp_inventario_unidades`.
  - Soporta busqueda por:
    - `codigo_existencia`;
    - SKU exacto;
    - referencia de movimiento;
    - folio de recepcion/preparacion;
    - codigo unico, etiqueta interna o serie.
- `app/controladores/Inventario.php`
  - Agrega endpoint read-only `/inventario/trazabilidad_erp`.
  - Usa permiso `inventario.ver`.
- `app/vistas/paginas/apps/erp/inventarios/existencias.php`
  - Agrega modal `inventario_trazabilidad_modal`.
  - Agrega columna `Acciones` en Existencias y Kardex.
  - Versiona `existencias_erp.js` a `v=20260622-3`.
- `public/assets/js/custom/apps/erp/inventarios/existencias_erp.js`
  - Agrega boton `Trazabilidad` en Existencias, Kardex y Unidades.
  - Renderiza ficha con secciones:
    - Existencias;
    - Kardex;
    - Unidades.
- `storage/uat/uat_inv_trazabilidad_readonly.php`
  - UAT repetible de solo lectura para validar trazabilidad por claves reales.
- `storage/uat/uat_inv_visual_render_check.js`
  - Valida render de botones `data-trazabilidad`.

Validaciones tecnicas:

- `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php`: OK.
- `C:\xampp\php\php.exe -l app\controladores\Inventario.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\inventarios\existencias.php`: OK.
- `node --check public\assets\js\custom\apps\erp\inventarios\existencias_erp.js`: OK.
- `node --check storage\uat\uat_inv_visual_render_check.js`: OK.
- `C:\xampp\php\php.exe -l storage\uat\uat_inv_trazabilidad_readonly.php`: OK.
- `node storage\uat\uat_inv_visual_render_check.js`: `ok=true`.
- `C:\xampp\php\php.exe storage\uat\uat_inv_trazabilidad_readonly.php`: `ok=true`.

Evidencia UAT read-only:

| Clave | Existencias | Movimientos | Unidades |
| --- | ---: | ---: | ---: |
| `EXI-50-31` | 1 | 1 | 1 |
| `P25-II000043-0001` | 1 | 1 | 1 |
| `INV-INICIAL-20260622-UAT01` | 1 | 1 | 1 |

Resultado:

- Desde Existencias, Kardex o Unidades se puede abrir ficha de trazabilidad.
- La ficha permite comprobar el ciclo `INV-INICIAL-20260622-UAT01` -> `EXI-50-31` -> `P25-II000043-0001`.
- No hubo escrituras de base de datos en este punto.

## INV-T020 - Diagnostico operativo read-only de existencias

Fecha: 2026-06-22

Objetivo:

Agregar a Inventario > Existencias un control de salud operativo que detecte problemas de saldo y pendientes de trazabilidad sin mover inventario.

Decision ERP robusta:

- El diagnostico es de consulta, no corrige saldos automaticamente.
- Los hallazgos criticos deben llevar a ajuste documentado con respaldo, no a edicion silenciosa.
- Las etiquetas pendientes se muestran como trabajo operativo para Almacen > Etiquetado.
- La caducidad se evalua contra `erp_catalogo_sku_reglas_inventario.dias_alerta_caducidad`, con 90 dias como respaldo si no hay regla util.

Controles incluidos:

- Existencias negativas.
- Descuadre entre `cantidad`, `cantidad_apartada` y `cantidad_disponible`.
- Estado de existencia inconsistente (`agotada` vs saldo real).
- Existencias vencidas con disponible.
- Existencias proximas a caducar con disponible.
- Etiquetas pendientes de ciclo fisico (`pendiente_impresion`, `impresa`, `reimpresa`).
- SKUs con etiqueta interna donde unidades disponibles no coinciden con existencia.

Cambios aplicados:

- `app/modelos/InventarioErp.php`
  - Agrega `diagnosticoOperativo($filtros)`.
  - Agrega helpers privados de diagnostico por existencia, etiquetas y unidades.
- `app/controladores/Inventario.php`
  - Agrega endpoint read-only `/inventario/diagnostico_erp`.
  - Usa permiso `inventario.ver`.
- `app/vistas/paginas/apps/erp/inventarios/existencias.php`
  - Agrega contenedor `inventario_diagnostico`.
  - Versiona `existencias_erp.js` a `v=20260622-4`.
- `public/assets/js/custom/apps/erp/inventarios/existencias_erp.js`
  - Consulta diagnostico al cargar/recargar.
  - Renderiza hallazgos con severidad `danger`, `warning` o `info`.
- `storage/uat/uat_inv_diagnostico_readonly.php`
  - UAT repetible de solo lectura.
- `storage/uat/uat_inv_visual_render_check.js`
  - Valida que el panel visual muestre hallazgos de diagnostico.

Validaciones tecnicas:

- `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php`: OK.
- `C:\xampp\php\php.exe -l app\controladores\Inventario.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\inventarios\existencias.php`: OK.
- `node --check public\assets\js\custom\apps\erp\inventarios\existencias_erp.js`: OK.
- `C:\xampp\php\php.exe -l storage\uat\uat_inv_diagnostico_readonly.php`: OK.
- `node --check storage\uat\uat_inv_visual_render_check.js`: OK.
- `node storage\uat\uat_inv_visual_render_check.js`: `ok=true`.
- `C:\xampp\php\php.exe storage\uat\uat_inv_diagnostico_readonly.php`: `ok=true`.

Evidencia UAT read-only:

| Severidad | Hallazgo | Total |
| --- | --- | ---: |
| `info` | `INV-DIAG-ETQ` - Etiquetas pendientes de ciclo fisico | 20 |

Resultado:

- No hay hallazgos criticos de saldo en la auditoria actual.
- No hay advertencias de descuadre/caducidad en la auditoria actual.
- Hay `20` etiquetas en ciclo fisico pendiente, operativamente visibles para dar continuidad en Almacen > Etiquetado.
- No hubo escrituras de base de datos en este punto.

Seguimiento operativo 2026-06-22:

- El usuario entro a resolver el ciclo fisico de etiquetas en Almacen > Etiquetado.
- Validacion read-only posterior:
  - `C:\xampp\php\php.exe storage\uat\uat_inv_diagnostico_readonly.php`: `ok=true`.
  - `total_hallazgos=0`.
  - `criticos=0`.
  - `advertencias=0`.
  - `informativos=0`.
  - `erp_inventario_unidades`: `30` unidades activas en `estado_etiqueta='pegada'`.
- Resultado: diagnostico operativo limpio despues del cierre de etiquetas.

## Cierre recomendado de Inventario antes de operar

Fecha: 2026-06-22

Objetivo:

Llevar Inventario/Existencias a un nivel operativo suficientemente robusto para iniciar uso real, sin intentar meter dentro de Inventario reglas que pertenecen a Costos/Rentabilidad, Ventas, Compras o Almacen.

Decision de arquitectura:

- Inventario debe cerrar como fuente confiable de stock, movimientos, trazabilidad, ajustes documentados, conteos y apartados basicos.
- La utilidad real, margen neto, gastos sobre venta, listas mayoreo y rentabilidad por canal deben vivir en Costos/Rentabilidad y Ventas, alimentados por Compras e Inventario.
- No conviene esperar a tener todo el ERP terminado para operar, pero si conviene cerrar Inventario base con controles que eviten descuadres desde el dia uno.

Alcance recomendado para cerrar Inventario al 85-90%:

1. Ya cubierto:
   - Existencias por SKU, almacen, lote, caducidad y ubicacion.
   - Kardex con referencia y origen.
   - Ajuste/traspaso documentado.
   - Inventario inicial con etiquetas.
   - Preparacion/empaque reflejada en saldos separados.
   - Trazabilidad por existencia, folio y etiqueta.
   - Diagnostico operativo read-only.
   - Etiquetas cerradas como `pegada`.
2. Cierre inmediato dentro de Inventario:
   - Motivos controlados de ajuste.
   - Conteo fisico / inventario ciclico.
   - Apartado/reserva basica para preparar Ventas sin sobre-vender.
   - Reporte read-only de valuacion de inventario por costo promedio.
3. Fase posterior o modulo vecino:
   - Costos/Rentabilidad: costo sin impuesto, precio sin impuesto, margen bruto, gastos estimados y utilidad neta estimada.
   - Ventas/Mayoreo: listas de precio por canal, descuentos, alianzas, comisiones, credito/cobranza.
   - Traspasos avanzados: stock en transito con recepcion confirmada en destino.
   - Alertas persistentes: convertir diagnostico read-only en pendientes accionables.

## INV-T021 - Motivos controlados de ajuste

Fecha: 2026-06-22

Objetivo:

Evitar ajustes genericos sin contexto operativo. Cada ajuste debe indicar un motivo controlado visible en kardex/observaciones.

Decision ERP robusta:

- No se agrega columna nueva todavia para evitar migracion antes de cerrar el diseno de conteos/apartados.
- El motivo queda validado por backend y registrado en `erp_inventario_movimientos.observaciones` con prefijo `motivo:*`.
- En fase futura, si el volumen lo justifica, se puede agregar columna `motivo_movimiento` e historizar catalogo de motivos.

Motivos permitidos:

Entradas:

- `inventario_inicial`
- `sobrante_conteo`
- `correccion_documentada`
- `devolucion_cliente`
- `recuperacion`

Salidas:

- `faltante_conteo`
- `merma`
- `caducado`
- `danado`
- `uso_interno`
- `robo_perdida`
- `correccion_documentada`

Cambios aplicados:

- `app/vistas/paginas/apps/erp/inventarios/operacion.php`
  - Agrega selector `Motivo` en ajustes.
  - Versiona `operacion_erp.js` a `v=20260622-3`.
- `public/assets/js/custom/apps/erp/inventarios/operacion_erp.js`
  - Carga motivos segun documento/tipo.
  - Valida motivo obligatorio.
  - Envia `motivo_ajuste` al backend.
- `app/modelos/InventarioErp.php`
  - Valida motivos permitidos por tipo/documento.
  - Agrega motivo a observaciones del movimiento.
  - Devuelve `motivo_ajuste` en respuesta.
- `storage/uat/uat_inv_operacion_existencia_check.js`
  - Valida que la UI envie y valide motivos.

Validaciones tecnicas:

- `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\inventarios\operacion.php`: OK.
- `node --check public\assets\js\custom\apps\erp\inventarios\operacion_erp.js`: OK.
- `node storage\uat\uat_inv_operacion_existencia_check.js`: `ok=true`.

Pendiente:

- UAT visual en navegador:
  - Abrir `/inventario/inicial`.
  - Verificar que `Inventario inicial` muestre motivo `Inventario inicial`.
  - Cambiar a `Ajuste documentado` y validar motivos de entrada.
  - Cambiar tipo a `Salida` y validar motivos de salida.
- No ejecutar ajuste real sin respaldo externo si se va a escribir BD.

## Cierre pendiente - Conteo fisico y apartados

Inventario no debe cerrarse para uso real sin dejar, al menos, el diseno y una primera version de:

### Conteo fisico / inventario ciclico

Objetivo:

- Registrar conteos por almacen/ubicacion/SKU/lote.
- Comparar sistema vs fisico.
- Generar diferencias.
- Aplicar ajuste documentado solo al cerrar conteo, con respaldo y auditoria.

Tablas sugeridas antes de implementar:

- `erp_inventario_conteos`
- `erp_inventario_conteos_detalle`

Estados sugeridos:

- `borrador`
- `en_conteo`
- `validado`
- `cerrado`
- `cancelado`

### Apartados / reservas basicas

Objetivo:

- Separar existencia fisica de disponibilidad comercial.
- Preparar integracion con Ventas, ecommerce, mayoreo y alianzas.
- Evitar sobreventa cuando haya pedidos, apartados o preparaciones pendientes.

Tabla sugerida antes de implementar:

- `erp_inventario_reservas`

Estados sugeridos:

- `activa`
- `consumida`
- `liberada`
- `cancelada`
- `vencida`

Regla:

- Toda reserva debe afectar `cantidad_apartada` y `cantidad_disponible`, dejando `cantidad` como existencia fisica.
- Las reservas no deben vender ni descontar stock; solo comprometen disponibilidad.

### Valuacion read-only de inventario

Objetivo:

- Mostrar valor de inventario por costo promedio sin meterse todavia a utilidad real.
- La utilidad real debe pasar a Costos/Rentabilidad porque requiere precio sin impuesto, gastos operativos, descuentos, canal y cliente.

Campos base:

- SKU.
- Producto.
- Almacen.
- Lote/caducidad.
- Cantidad.
- Costo promedio.
- Valor total.

## INV-T022 - Valuacion read-only de inventario

Fecha: 2026-06-22

Objetivo:

Agregar una vista de valuacion bruta de inventario por SKU/almacen usando cantidad actual y costo promedio, sin calcular utilidad ni margen neto.

Decision ERP robusta:

- Inventario puede mostrar valor de stock porque ya tiene cantidad y costo promedio.
- La utilidad real no se calcula aqui; debe vivir en Costos/Rentabilidad porque requiere precio sin impuesto, gastos estimados, descuentos, canal, cliente y condiciones comerciales.
- La valuacion es read-only y no mueve inventario.

Cambios aplicados:

- `app/modelos/InventarioErp.php`
  - Agrega `valuacionInventario($filtros)`.
  - Agrupa por SKU y almacen.
  - Calcula cantidad total, disponible, apartada, costo promedio estimado y valor total.
- `app/controladores/Inventario.php`
  - Agrega endpoint `/inventario/valuacion_erp`.
  - Usa permiso `inventario.ver`.
- `app/vistas/paginas/apps/erp/inventarios/existencias.php`
  - Agrega pestana `Valuacion`.
  - Versiona `existencias_erp.js` a `v=20260622-5`.
- `public/assets/js/custom/apps/erp/inventarios/existencias_erp.js`
  - Consulta valuacion con los mismos filtros de Existencias.
  - Renderiza resumen y tabla de valor.
- `storage/uat/uat_inv_valuacion_readonly.php`
  - UAT real read-only.
- `storage/uat/uat_inv_visual_render_check.js`
  - Valida render de valuacion.

Validaciones tecnicas:

- `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php`: OK.
- `C:\xampp\php\php.exe -l app\controladores\Inventario.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\inventarios\existencias.php`: OK.
- `node --check public\assets\js\custom\apps\erp\inventarios\existencias_erp.js`: OK.
- `C:\xampp\php\php.exe -l storage\uat\uat_inv_valuacion_readonly.php`: OK.
- `node --check storage\uat\uat_inv_visual_render_check.js`: OK.
- `node storage\uat\uat_inv_visual_render_check.js`: `ok=true`.
- `C:\xampp\php\php.exe storage\uat\uat_inv_valuacion_readonly.php`: `ok=true`.

Evidencia UAT read-only:

| Campo | Valor |
| --- | ---: |
| SKUs valuados | 9 |
| Cantidad total | 65 |
| Disponible total | 65 |
| Apartada total | 0 |
| Valor total estimado | 18836.3118 |

Resultado:

- Inventario ya permite consultar valor bruto de stock por costo promedio.
- Esta informacion servira como insumo para Costos/Rentabilidad, pero no sustituye el calculo de utilidad real.
- No hubo escrituras de base de datos.

## INV-T023 - DDL propuesto para conteos y reservas

Fecha: 2026-06-22

Objetivo:

Dejar preparada la estructura propuesta para cerrar Inventario con conteo fisico/inventario ciclico y reservas basicas, sin aplicar migraciones todavia.

Archivo creado:

- `docs/erp_inventario_conteos_reservas_schema_propuesta.sql`

Tablas propuestas:

- `erp_inventario_conteos`
- `erp_inventario_conteos_detalle`
- `erp_inventario_reservas`

Decision ERP robusta:

- Conteo fisico debe generar diferencias y solo mover stock al cerrar conteo.
- Reservas deben comprometer disponibilidad sin descontar existencia fisica.
- Ambas piezas requieren respaldo externo y autorizacion antes de aplicar DDL o ejecutar UAT con escritura.

Siguiente autorizacion requerida:

- Aplicar DDL de conteos/reservas en BD local.
- Crear endpoints y pantallas operativas que escriban en esas tablas.
- Ejecutar UAT real de conteo fisico o reserva basica.

## INV-T024 - Conteo fisico basico con snapshot y captura

Fecha: 2026-06-22

Objetivo:

Crear la primera version operativa de conteo fisico/inventario ciclico, con snapshot del sistema y captura de cantidad fisica, sin aplicar ajustes automaticos al stock.

Respaldo externo previo:

- `C:\xampp\panel_db_backups\artianilocal_panel_20260622_210431_antes_inv_conteos_reservas.sql`
- Tamano verificado: `53218140` bytes.

DDL aplicado:

- Archivo fuente: `docs/erp_inventario_conteos_reservas_schema_propuesta.sql`
- Tablas creadas:
  - `erp_inventario_conteos`
  - `erp_inventario_conteos_detalle`
  - `erp_inventario_reservas`
- Validacion:
  - `SHOW TABLES`: OK.
  - `SHOW COLUMNS`: OK.
  - `SHOW INDEX`: OK.

Cambios aplicados:

- `app/controladores/Inventario.php`
  - Agrega vista `/inventario/conteos`.
  - Agrega endpoints:
    - `/inventario/conteos_listar_erp`
    - `/inventario/conteo_crear_erp`
    - `/inventario/conteo_consultar_erp`
    - `/inventario/conteo_capturar_erp`
  - Usa permiso `inventario.conteo`.
- `app/modelos/InventarioErp.php`
  - Agrega `listarConteos()`.
  - Agrega `crearConteo()`.
  - Agrega `consultarConteo()`.
  - Agrega `capturarConteo()`.
  - Agrega folio `CON-YYYYMMDD-####`.
  - El conteo crea snapshot desde `erp_inventario_existencias`.
  - La captura calcula `diferencia` y `costo_diferencia`.
- `app/vistas/paginas/apps/erp/inventarios/conteos.php`
  - Nueva pantalla de conteos fisicos.
- `public/assets/js/custom/apps/erp/inventarios/conteos_erp.js`
  - Carga catalogos.
  - Crea conteos.
  - Lista conteos.
  - Abre detalle.
  - Guarda captura fisica.
- `app/vistas/paginas/apps/erp/inventarios/existencias.php`
  - Agrega boton `Conteos`.
- `storage/uat/uat_inv_conteo_fisico_basico.php`
  - UAT con dry-run por defecto y ejecucion controlada con `--execute`.

UAT real controlado:

- Script:
  - `C:\xampp\php\php.exe storage\uat\uat_inv_conteo_fisico_basico.php --execute`
- Folio creado:
  - `CON-20260622-0001`
- Resultado:
  - `id_conteo_inventario=1`
  - `partidas_snapshot=12`
  - `partidas_actualizadas=1`
  - SKU capturado: `SAL-50L`
  - Existencia: `EXI-319-18`
  - Cantidad sistema: `2.000000`
  - Cantidad fisica: `2.000000`
  - Diferencia: `0.000000`

Validacion posterior:

| Revision | Resultado |
| --- | --- |
| Diagnostico operativo | `0` hallazgos |
| Conteo `CON-20260622-0001` | `en_conteo`, `12` partidas, `1` capturada, `0` diferencias |
| Existencia total | `65.0000` |
| Disponible total | `65.0000` |
| Apartada total | `0.0000` |
| Valuacion total | `18836.3118` |

Decision ERP robusta:

- Capturar conteo no ajusta inventario.
- El cierre/aplicacion de diferencias debe ser una accion separada, con previsualizacion, respaldo y autorizacion.
- Las reservas ya tienen tabla, pero aun no se implementan endpoints para no mezclar conteo con Ventas/apartados.

Siguiente autorizacion requerida:

- Implementar y probar cierre de conteo que genere ajustes documentados por diferencia.
- Implementar reservas basicas que afecten `cantidad_apartada` y `cantidad_disponible`.

## INV-T025 - Cierre de conteo sin diferencias

Fecha: 2026-06-22

Objetivo:

Implementar la accion separada de cierre de conteo, manteniendo la regla de que capturar conteo no ajusta inventario y que solo el cierre puede generar movimientos si existen diferencias.

Respaldo externo previo:

- `C:\xampp\panel_db_backups\artianilocal_panel_20260622_211629_antes_inv_conteo_cierre_uat.sql`
- Tamano verificado: `53235392` bytes.

Cambios aplicados:

- `app/controladores/Inventario.php`
  - Agrega `/inventario/conteo_preview_cierre_erp`.
  - Agrega `/inventario/conteo_cerrar_erp`.
- `app/modelos/InventarioErp.php`
  - Agrega `previewCerrarConteo()`.
  - Agrega `cerrarConteo()`.
  - Agrega `resumenCierreConteo()`.
  - El cierre exige que todas las partidas esten capturadas.
  - Si existen diferencias, genera movimientos con `origen_tipo='conteo_fisico'`.
  - Si una existencia con diferencia tiene `cantidad_apartada>0`, bloquea el cierre para no modificar stock comprometido.
- `app/vistas/paginas/apps/erp/inventarios/conteos.php`
  - Agrega boton `Cerrar conteo`.
- `public/assets/js/custom/apps/erp/inventarios/conteos_erp.js`
  - Agrega preview de cierre.
  - Agrega confirmacion antes de cerrar.
  - Conserva motivo seleccionado en captura.
- `storage/uat/uat_inv_conteo_cierre_sin_diferencias.php`
  - UAT con dry-run por defecto y ejecucion controlada con `--execute`.

UAT real controlado:

- Script:
  - `C:\xampp\php\php.exe storage\uat\uat_inv_conteo_cierre_sin_diferencias.php --execute`
- Folio:
  - `CON-20260622-0001`
- Captura:
  - `12` partidas actualizadas con fisico igual a sistema.
- Preview antes de cierre:
  - Partidas: `12`
  - Pendientes: `0`
  - Capturadas: `12`
  - Diferencias: `0`
  - Sobrante: `0.000000`
  - Faltante: `0.000000`
  - Costo diferencia: `0.000000`
- Cierre:
  - Estado final: `cerrado`
  - Movimientos de inventario generados: `0`

Validacion posterior:

| Revision | Resultado |
| --- | --- |
| Conteo `CON-20260622-0001` | `cerrado`, `12` partidas, `12` capturadas, `0` diferencias |
| Movimientos con referencia `CON-20260622-0001` | `0` |
| Diagnostico operativo | `0` hallazgos |
| Existencia total | `65.0000` |
| Disponible total | `65.0000` |
| Apartada total | `0.0000` |
| Valuacion total | `18836.3118` |

Decision ERP robusta:

- El cierre ya esta separado de la captura.
- El cierre sin diferencias no mueve stock.
- El cierre con diferencias queda implementado tecnicamente, pero requiere UAT especifico con una diferencia controlada antes de usarlo en operacion real.

Siguiente autorizacion recomendada:

- Ejecutar UAT de cierre con diferencia pequena controlada, con respaldo externo, validando que genere ajuste documentado y luego revertir/compensar si aplica.
- Implementar reservas basicas como siguiente pieza para prevenir sobreventa antes de conectar Ventas.

## INV-T026 - UAT cierre de conteo con diferencia compensada

Fecha: 2026-06-22

Objetivo:

Validar que el cierre de conteo con diferencia genere movimientos documentados en kardex y que una compensacion controlada pueda dejar el saldo final igual al inicial.

Respaldo externo previo:

- `C:\xampp\panel_db_backups\artianilocal_panel_20260622_212327_antes_inv_conteo_diferencia_uat.sql`
- Tamano verificado: `53238108` bytes.

Script UAT:

- `storage/uat/uat_inv_conteo_diferencia_compensada.php`
- Dry-run:
  - `C:\xampp\php\php.exe storage\uat\uat_inv_conteo_diferencia_compensada.php`
- Ejecucion:
  - `C:\xampp\php\php.exe storage\uat\uat_inv_conteo_diferencia_compensada.php --execute`

Caso probado:

- SKU: `TP-40372`
- Existencia: `EXI-50-26`
- Delta: `0.1000 kg`

Conteo positivo:

- Folio: `CON-20260622-0002`
- Snapshot inicial: `15.000000`
- Fisico capturado: `15.100000`
- Diferencia: `+0.100000`
- Motivo: `sobrante_conteo`
- Movimiento generado:
  - Tipo: `entrada`
  - Origen: `conteo_fisico`
  - Cantidad: `0.1000`
  - Existencia anterior: `15.0000`
  - Existencia nueva: `15.1000`

Conteo compensatorio:

- Folio: `CON-20260622-0003`
- Snapshot inicial: `15.100000`
- Fisico capturado: `15.000000`
- Diferencia: `-0.100000`
- Motivo: `faltante_conteo`
- Movimiento generado:
  - Tipo: `salida`
  - Origen: `conteo_fisico`
  - Cantidad: `0.1000`
  - Existencia anterior: `15.1000`
  - Existencia nueva: `15.0000`

Validacion posterior:

| Revision | Resultado |
| --- | --- |
| `CON-20260622-0002` | `cerrado`, `12` capturadas, `1` diferencia |
| `CON-20260622-0003` | `cerrado`, `12` capturadas, `1` diferencia |
| Kardex `CON-20260622-0002` | entrada `0.1000`, `origen_tipo='conteo_fisico'`, motivo `sobrante_conteo` |
| Kardex `CON-20260622-0003` | salida `0.1000`, `origen_tipo='conteo_fisico'`, motivo `faltante_conteo` |
| `EXI-50-26` final | `15.0000`, disponible `15.0000`, apartada `0.0000` |
| Diagnostico operativo | `0` hallazgos |
| Valuacion total | `18836.3118` |

Resultado:

- El cierre de conteo con diferencia genera kardex correctamente.
- El saldo final quedo igual despues de la compensacion.
- El modulo ya tiene el ciclo completo: crear conteo -> capturar -> previsualizar -> cerrar -> generar movimiento documentado cuando hay diferencia.

## INV-T027 - Reservas basicas de inventario

Objetivo:

- Implementar apartados operativos sobre existencias existentes para comprometer disponibilidad sin descontar stock fisico.
- Preparar el terreno para Ventas, ecommerce, mayoreo y alianzas sin acoplar todavia esos modulos.

Decision ERP:

- Una reserva modifica solo `cantidad_apartada` y `cantidad_disponible`.
- La columna `cantidad` representa existencia fisica y no debe cambiar al reservar o liberar.
- Consumo de reserva queda fuera de esta tarea porque debe amarrarse a un documento operativo posterior: venta, pedido, mayoreo, alianza o salida autorizada.

Archivos modificados:

- `app/controladores/Inventario.php`
  - Agrega vista `/inventario/reservas`.
  - Agrega endpoints:
    - `/inventario/reservas_listar_erp`
    - `/inventario/reserva_crear_erp`
    - `/inventario/reserva_liberar_erp`
- `app/modelos/InventarioErp.php`
  - Agrega `listarReservas()`.
  - Agrega `crearReserva()`.
  - Agrega `liberarReserva()`.
  - Agrega folio `RES-YYYYMMDD-0001`.
  - Valida disponible suficiente al crear.
  - Valida cantidad apartada suficiente al liberar.
- `app/vistas/paginas/apps/erp/inventarios/reservas.php`
  - Nueva pantalla de reservas.
- `public/assets/js/custom/apps/erp/inventarios/reservas_erp.js`
  - Busca existencias disponibles.
  - Selecciona existencia especifica por lote/ubicacion.
  - Crea reserva.
  - Lista reservas.
  - Libera reservas activas.
- `app/vistas/paginas/apps/erp/inventarios/existencias.php`
  - Agrega boton `Reservas`.
- `storage/uat/uat_inv_reserva_basica.php`
  - UAT seco por defecto.
  - Con `--execute`, crea y libera una reserva controlada.

Respaldo externo antes de escritura:

- `C:\xampp\panel_db_backups\artianilocal_panel_20260622_213309_antes_inv_reserva_uat.sql`
- Tamano: `53251656` bytes.

Validaciones tecnicas:

| Comando | Resultado |
| --- | --- |
| `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php` | OK |
| `C:\xampp\php\php.exe -l app\controladores\Inventario.php` | OK |
| `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\inventarios\reservas.php` | OK |
| `node --check public\assets\js\custom\apps\erp\inventarios\reservas_erp.js` | OK |
| `C:\xampp\php\php.exe -l storage\uat\uat_inv_reserva_basica.php` | OK |
| `C:\xampp\php\php.exe storage\uat\uat_inv_reserva_basica.php` | `dry_run=true` |

UAT ejecutado:

- Script: `storage/uat/uat_inv_reserva_basica.php --execute`
- SKU: `TP-40372`
- Existencia: `EXI-50-26`
- Reserva: `0.1000`
- Folio generado: `RES-20260622-0001`

Evidencia:

| Momento | Cantidad | Disponible | Apartada |
| --- | ---: | ---: | ---: |
| Inicial | `15.0000` | `15.0000` | `0.0000` |
| Despues de crear reserva | `15.0000` | `14.9000` | `0.1000` |
| Despues de liberar reserva | `15.0000` | `15.0000` | `0.0000` |

Validacion posterior:

| Revision | Resultado |
| --- | --- |
| Reserva `RES-20260622-0001` | `liberada`, reservada `0.100000`, liberada `0.100000` |
| Existencia `EXI-50-26` | cantidad `15.0000`, disponible `15.0000`, apartada `0.0000` |
| Diagnostico operativo | `0` hallazgos |
| Valuacion total | `18836.3118` |

Resultado:

- Inventario ya puede apartar stock por existencia/lote/ubicacion sin afectar kardex fisico.
- Conteos bloquean cierre con diferencias si una existencia tiene apartados.
- La disponibilidad ya queda preparada para flujos posteriores de venta, ecommerce, mayoreo o alianzas.

Siguiente pieza recomendada:

- Definir politicas de consumo de reserva por documento origen antes de conectar con Ventas/ecommerce.
- Alternativa conservadora para cerrar esta seccion: dejar reservas manuales operativas y pasar al siguiente modulo, documentando que consumo automatico pertenece a Ventas/Pedidos.

## INV-T028 - Diagnostico operativo de reservas

Objetivo:

- Extender el diagnostico de Inventario para detectar inconsistencias propias de reservas/apartados.
- Mantenerlo read-only, sin escrituras ni migraciones.

Reglas validadas:

- `cantidad_apartada` debe cuadrar contra la suma pendiente de reservas `activa`.
- Una reserva `activa` con `fecha_vencimiento < NOW()` y pendiente mayor a cero debe generar advertencia.
- Una reserva `activa` sin pendiente, o una reserva no activa con pendiente, debe generar advertencia de estatus.

Cambios aplicados:

- `app/modelos/InventarioErp.php`
  - Agrega diagnostico `INV-DIAG-RES-SALDO`.
  - Agrega diagnostico `INV-DIAG-RES-VENC`.
  - Agrega diagnostico `INV-DIAG-RES-EST`.
- `public/assets/js/custom/apps/erp/inventarios/existencias_erp.js`
  - Muestra folio y cantidad pendiente cuando el hallazgo viene de reservas.
  - Actualiza el mensaje de diagnostico limpio para incluir reservas inconsistentes.

Validaciones tecnicas:

| Comando | Resultado |
| --- | --- |
| `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php` | OK |
| `node --check public\assets\js\custom\apps\erp\inventarios\existencias_erp.js` | OK |
| `C:\xampp\php\php.exe storage\uat\uat_inv_diagnostico_readonly.php` | `ok=true`, `0` hallazgos |
| `C:\xampp\php\php.exe storage\uat\uat_inv_valuacion_readonly.php` | `ok=true`, valor total `18836.3118` |

Resultado:

- Inventario ya audita reservas como parte del diagnostico operativo.
- No hubo escrituras de BD.
- El saldo actual sigue limpio: cantidad total `65`, disponible total `65`, apartada total `0`.

## INV-T029 - Cierre formal de Inventario/Existencias

Objetivo:

- Dejar una frontera operativa clara para iniciar uso real de Inventario/Existencias con una base robusta.
- Documentar que piezas quedan listas, que piezas quedan intencionalmente fuera y que pruebas manuales deben hacerse antes de operar.

Estado funcional listo:

| Area | Estado | Evidencia |
| --- | --- | --- |
| Existencias por SKU/almacen/lote/ubicacion | Listo | `existencias_erp`, pantalla Existencias |
| Kardex ERP | Listo | movimientos con `REC-*`, `CAT-GRANEL-H001`, `PREP-*`, `CON-*` |
| Trazabilidad ficha unica | Listo | endpoint `/inventario/trazabilidad_erp` |
| Diagnostico operativo | Listo | `0` hallazgos actuales |
| Valuacion bruta de inventario | Listo | valor total `18836.3118` |
| Ajustes documentados | Listo | motivos controlados y referencia documental |
| Inventario inicial | Listo | permite lote/caducidad/ubicacion y etiquetas cuando aplica |
| Conteo fisico/ciclico | Listo primera version | crear, capturar, previsualizar, cerrar y generar movimientos |
| Reservas/apartados | Listo primera version | crear, listar, liberar, diagnosticar |
| Etiquetado de unidades | Listo para consulta/seguimiento | unidades en estado `pegada`, `impresa`, `pendiente_impresion` |

Reglas de frontera:

- Inventario muestra y protege saldos; no vende.
- Inventario puede apartar disponibilidad, pero no debe decidir consumo comercial de reservas.
- El consumo de reservas pertenece a un documento origen: venta, pedido, mayoreo, alianza, salida autorizada o ecommerce.
- La utilidad real no pertenece a Inventario; pertenece a Costos/Rentabilidad.
- Compras/Recepcion y Preparacion/Empaque son los modulos que originan entradas/preparaciones; Inventario no debe recrear esos procesos.
- Catalogo define SKUs, unidades, factores y reglas; no debe corregir saldos historicos.

Checklist UAT manual antes de uso real:

1. Abrir `/inventario/productos_existencias`.
2. Filtrar `TP-40372` y validar:
   - lote `L1`: `15.0000 kg`
   - lote `L2`: `4.0000 kg`
   - total base: `19.0000 kg` despues de preparaciones.
3. Filtrar `TP-40372-25GR` y validar:
   - `20.0000` disponibles.
   - etiquetas `P25-*` visibles desde Unidades/Trazabilidad.
4. Filtrar `TP-40372-100GR` y validar:
   - `5.0000` disponibles.
   - etiquetas `P100-P000004-0001` a `P100-P000004-0005` en estado `pegada`.
5. Filtrar `TP-40372-500GR` con agotadas incluidas y validar:
   - existencia `0.0000`.
   - estado `agotada`.
   - movimiento de consumo en `PREP-20260621-0003`.
6. En Kardex buscar:
   - `REC-OC-20`
   - `CAT-GRANEL-H001`
   - `PREP-20260622-0001`
   - `PREP-20260621-0002`
   - `PREP-20260621-0003`
   - `CON-20260622-0002`
   - `CON-20260622-0003`
7. Abrir Trazabilidad desde:
   - `EXI-50-26`
   - una etiqueta `P100-*`
   - un folio `PREP-*`
8. Abrir `/inventario/conteos`.
   - Crear un conteo pequeno solo si se hara con respaldo.
   - Validar que capturar no mueve stock.
   - Validar que cerrar sin diferencias no genera movimientos.
9. Abrir `/inventario/reservas`.
   - Crear una reserva pequena solo si se hara con respaldo.
   - Validar que baja `cantidad_disponible`, sube `cantidad_apartada` y no cambia `cantidad`.
   - Liberar la reserva y validar regreso a saldo inicial.
10. Confirmar que el panel de Diagnostico operativo no tenga hallazgos.
11. Confirmar que Valuacion siga cuadrando con el total esperado.

Criterio de uso real:

- Si Diagnostico operativo muestra `0` hallazgos, Kardex contiene los folios esperados y las existencias coinciden con el conteo fisico real, Inventario/Existencias puede considerarse listo para uso inicial controlado.
- Si aparece un hallazgo, no se debe corregir con SQL manual. Debe corregirse con ajuste documentado, conteo fisico o flujo origen segun corresponda.
- Antes de movimientos reales de ajuste, conteo con diferencia o reservas operativas, crear respaldo externo.

Pendientes intencionales fuera de Inventario:

| Pendiente | Modulo futuro | Motivo |
| --- | --- | --- |
| Consumir reservas automaticamente | Ventas/Pedidos/Mayoreo | Requiere documento comercial y reglas de pago/entrega |
| Publicar disponibilidad a ecommerce | Ecommerce/Ventas | Requiere reglas por canal, seguridad y sincronizacion |
| Calcular utilidad real | Costos/Rentabilidad | Requiere precio sin impuesto, costo sin impuesto, gastos, canal y descuentos |
| Reorden automatico | Compras/Planeacion | Requiere demanda, minimo/maximo, proveedor y lead time |
| Transferencias multi-tienda con autorizacion avanzada | Inventario/Almacen futuro | Requiere flujo de solicitud, envio, recepcion y diferencias |

Conclusion:

- Inventario/Existencias queda cerrado como base operativa robusta para saldos, kardex, trazabilidad, diagnostico, valuacion, conteos y reservas basicas.
- El siguiente crecimiento natural no es seguir metiendo logica comercial aqui, sino avanzar al modulo que consumira inventario: Ventas/Pedidos/Mayoreo o Costos/Rentabilidad, segun prioridad del negocio.

## INV-T030 - Inventario inicial real multi-tienda con granel y unidades fisicas

Fecha: 2026-07-09

Origen:

- El negocio ya tiene mercancia fisica en tiendas.
- Esa mercancia no debe simularse como compra/recepcion si no existe documento real de recepcion.
- Inventario inicial debe poder capturar stock historico en la misma logica robusta que Recepcion: unidad base, unidad de compra y unidad fisica trazable.

Decision ERP robusta:

- Inventario inicial sigue siendo un movimiento documentado con referencia obligatoria `INV-INICIAL-*`.
- El Kardex y la existencia agregada siempre guardan cantidad en unidad base.
- La pantalla puede capturar distintas formas operativas, pero el backend normaliza antes de mover inventario.
- Las ubicaciones deben existir antes en Almacen; Inventario inicial no crea ubicaciones libres.
- Las unidades fisicas abiertas son stock disponible, pero no representan unidad cerrada vendible.

Modos soportados en Inventario inicial:

| Modo | Uso operativo | Cantidad base resultante |
| --- | --- | --- |
| Unidad base | Conteo directo en kg, pza, g, ml, etc. | `cantidad` |
| Unidad compra | Cajas, costales o empaques de proveedor. | `cantidad_compra * factor_conversion` |
| Unidad cerrada | Piezas fisicas trazables con etiqueta. | `cantidad_unidades_fisicas * contenido_base_original` |
| Unidad abierta | Bolsa/caja ya abierta en tienda. | `contenido_base_disponible` |

Cambios aplicados sin escritura de BD:

- `app/modelos/InventarioErp.php`
  - `buscarSkus()` expone `factor_unidad_base`, `unidad_base_label`, `factor_conversion_compra` y `unidad_compra_label`.
  - `aplicarAjuste()` normaliza cantidad base para `documento_operacion='inventario_inicial'`.
  - Agrega `cantidadBaseAjuste()` para capturas por unidad base, unidad compra, unidad cerrada y unidad abierta.
  - `generarUnidadesInventarioInicial()` ahora puede crear unidad fisica trazable aun cuando el SKU no genere etiqueta automaticamente, si el modo de captura lo solicita.
  - Las unidades de inventario inicial guardan `cantidad_base_original`, `cantidad_base_disponible`, `unidad_base` y `estado_fisico`.
- `public/assets/js/custom/apps/erp/inventarios/operacion_erp.js`
  - En Inventario inicial muestra modo de captura por partida.
  - Permite capturar unidad compra, factor, unidades fisicas, contenido original y contenido disponible.
  - Calcula visualmente la cantidad base resultante antes de aplicar.
  - Valida que una unidad abierta no tenga disponible mayor al contenido original.
- `app/vistas/paginas/apps/erp/inventarios/operacion.php`
  - Versiona `operacion_erp.js` a `v=20260709-1`.
- `storage/uat/uat_inv_inicial_real_preflight_readonly.php`
  - Agrega preflight flexible para validar tienda, SKU, modo de captura, referencia, lote/caducidad y cantidad base calculada.
  - Devuelve payload recomendado, advertencias y comando aplicador futuro.
- `storage/uat/uat_inv_inicial_real_apply_authorized.php`
  - Agrega aplicador bloqueado por defecto.
  - Requiere `--autorizar=INV_INICIAL_REAL_UAT` y respaldo externo legible.
  - No aplica si la referencia ya existe en Kardex.

Validaciones tecnicas:

| Comando | Resultado |
| --- | --- |
| `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php` | OK |
| `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\inventarios\operacion.php` | OK |
| `node --check public\assets\js\custom\apps\erp\inventarios\operacion_erp.js` | OK |
| `C:\xampp\php\php.exe -l storage\uat\uat_inv_inicial_real_preflight_readonly.php` | OK |
| `C:\xampp\php\php.exe -l storage\uat\uat_inv_inicial_real_apply_authorized.php` | OK |
| `node --check storage\uat\uat_inv_operacion_existencia_check.js` | OK |
| `node storage\uat\uat_inv_operacion_existencia_check.js` | `ok=true`, incluye modos de inventario inicial real |

Preflight read-only ejecutado:

- Comando:
  - `C:\xampp\php\php.exe storage\uat\uat_inv_inicial_real_preflight_readonly.php --id_almacen=3 --sku=TP-40372 --modo=unidad_compra --cantidad_compra=5 --factor_conversion=4 --lote=UAT-INV-INICIAL-REAL --caducidad=2027-12-31 --referencia=INV-INICIAL-ACUARIO-20260709-UAT01`
- Resultado:
  - `ok=true`
  - almacen `3` / `BOD971`
  - SKU `TP-40372`
  - modo `unidad_compra`
  - cantidad base calculada `20 kg`
  - bloqueos `[]`
  - advertencia: unidad compra preferida aparece como `kg`, aunque el factor de conversion es `4.000000`; validar en Catalogo/Proveedor si se esperaba `CAJA`.

Prueba de seguridad del aplicador:

- Ejecutado con respaldo placeholder `C:\xampp\panel_db_backups\RESPALDO_PENDIENTE.sql`.
- Resultado esperado: `ok=false`.
- Bloqueo confirmado: `Respaldo externo no valido o no legible`.
- No hubo escrituras de BD.

Validacion adicional de UI/JS:

- `storage/uat/uat_inv_operacion_existencia_check.js` ahora cubre:
  - deteccion de Inventario inicial;
  - calculo de cantidad base por modo;
  - unidad compra;
  - unidad fisica cerrada;
  - unidad fisica abierta;
  - validacion de disponible <= contenido original;
  - render del selector `data-partida-modo-captura`;
  - envio de campos de contenido base en `items`.

Hallazgo INV-H030-01:

- Preflight read-only con unidad fisica abierta:
  - `TP-40372`
  - modo `unidad_fisica_abierta`
  - contenido original `4 kg`
  - contenido disponible `2.5 kg`
  - referencia `INV-INICIAL-ACUARIO-20260709-UAT-ABIERTA`
- Resultado:
  - `ok=false`
  - bloqueo: `El SKU no permite fraccionarios y la cantidad base calculada no es entera`.
- Interpretacion:
  - Inventario esta protegiendo correctamente la regla de Catalogo.
  - Para operar `TP-40372` como granel real, Catalogo debe tener `permite_venta_fraccionaria=1` y precision/incremento coherente para `kg`.
  - No se corrige desde Inventario porque Catalogo define la politica del SKU.

Resolucion INV-H030-01:

- Fecha: 2026-07-10.
- Catalogo fue corregido por el dueno del proyecto.
- Validacion read-only posterior:
  - `TP-40372`
  - `permite_venta_fraccionaria=1`
  - `precision_decimal=3`
  - `unidad_venta_label=kg`
  - `incremento_minimo_venta=1.000000`
- Preflight read-only repetido con unidad fisica abierta:
  - modo `unidad_fisica_abierta`
  - contenido original `4 kg`
  - contenido disponible `2.5 kg`
  - referencia `INV-INICIAL-ACUARIO-20260709-UAT-ABIERTA`
  - resultado `ok=true`
  - cantidad base calculada `2.5`
  - bloqueos `[]`
- Observacion:
  - El incremento minimo `1.000000 kg` puede ser demasiado alto para UX de venta/captura granel si se vendera por gramos o fracciones menores.
  - No bloquea el preflight ni la captura manual, pero Catalogo deberia definir si el incremento operativo sera `0.001`, `0.01`, `0.1` o `1`.

Preflight read-only posterior para unidad compra:

- `TP-40372`
- modo `unidad_compra`
- `5 x 4 kg = 20 kg`
- referencia `INV-INICIAL-ACUARIO-20260709-UAT01`
- resultado `ok=true`
- bloqueos `[]`
- advertencia vigente:
  - unidad compra preferida aparece como `kg`; si operativamente debe decir `CAJA`, corresponde a Catalogo/Proveedor.

Preflight read-only con ubicacion operativa:

- Fecha: 2026-07-10.
- Ubicacion elegida para UAT controlado:
  - `id_ubicacion=13`
  - `E1-C2-P1-A1-N3`
- Unidad abierta:
  - comando con `--modo=unidad_fisica_abierta`
  - contenido original `4 kg`
  - contenido disponible `2.5 kg`
  - referencia `INV-INICIAL-ACUARIO-20260710-UAT-ABIERTA`
  - resultado `ok=true`
  - bloqueos `[]`
- Unidad compra:
  - comando con `--modo=unidad_compra`
  - `5 x 4 kg = 20 kg`
  - referencia `INV-INICIAL-ACUARIO-20260710-UAT01`
  - resultado `ok=true`
  - bloqueos `[]`
  - advertencia vigente: unidad compra preferida aparece como `kg`.

Decision para el primer UAT con escritura:

- Recomendado ejecutar primero `unidad_fisica_abierta` porque valida el caso nuevo mas delicado:
  - crea existencia por `2.5 kg`;
  - crea una unidad fisica abierta con original `4 kg` y disponible `2.5 kg`;
  - debe verse en Existencias como stock disponible, no unidad cerrada.
- Despues ejecutar `unidad_compra` para validar entrada historica por caja/factor.

Respaldo previo generado:

- Fecha: 2026-07-10.
- Archivo:
  - `C:\xampp\panel_db_backups\artianilocal_panel_20260710_antes_inv_inicial_real_uat_abierta.sql`
- Resultado:
  - `error=false`
  - bytes `28223019`
  - base `artianilocal`
- Estado:
  - listo para solicitar autorizacion de escritura UAT `unidad_fisica_abierta`.

UAT pendiente con autorizacion:

1. Crear respaldo externo antes de cualquier escritura.
2. Ejecutar preflight read-only para una tienda real y un SKU granel.
3. Cargar un folio controlado, por ejemplo `INV-INICIAL-ACUARIO-20260709-UAT01`.
4. Validar en Existencias:
   - saldo agregado en unidad base;
   - lote/caducidad/ubicacion;
   - unidad fisica cerrada o abierta si aplica;
   - diferencia saldo vs unidades trazables en cero cuando toda la existencia sea trazable.
5. Validar Kardex por referencia `INV-INICIAL-*`.
6. Validar Unidades/Etiquetado si se generaron unidades fisicas.

Regla de cierre:

- No cargar inventario real de tiendas hasta ejecutar respaldo externo y UAT por tienda/SKU.
- No corregir inventario inicial con SQL manual; usar movimiento documentado o conteo fisico segun corresponda.

### INV-T030-UAT01 - Escritura controlada unidad fisica abierta

Fecha: 2026-07-10

Autorizacion recibida:

```text
AUTORIZO INVENTARIO INICIAL REAL UAT unidad_fisica_abierta usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_20260710_antes_inv_inicial_real_uat_abierta.sql
```

Respaldo externo:

- `C:\xampp\panel_db_backups\artianilocal_panel_20260710_antes_inv_inicial_real_uat_abierta.sql`
- Base: `artianilocal`
- Bytes: `28223019`

Payload aplicado:

| Campo | Valor |
| --- | --- |
| Referencia | `INV-INICIAL-ACUARIO-20260710-UAT-ABIERTA` |
| SKU | `TP-40372` |
| ID SKU | `146` |
| Almacen | `3` / `BOD971` |
| Ubicacion | `13` / `E1-C2-P1-A1-N3` |
| Modo captura | `unidad_fisica_abierta` |
| Lote | `UAT-INV-INICIAL-ABIERTA` |
| Caducidad | `2027-12-31` |
| Contenido original | `4.000000 kg` |
| Contenido disponible | `2.500000 kg` |

Resultado del aplicador:

| Indicador | Valor |
| --- | --- |
| `ok` | `true` |
| Mensaje | `Inventario inicial ERP aplicado` |
| Movimientos | `1` |
| Etiquetas/unidades generadas | `1` |
| Origen tipo | `inventario_inicial` |

Evidencia antes/despues:

| Indicador | Antes | Despues |
| --- | ---: | ---: |
| Cantidad SKU/almacen | `18.9500` | `21.4500` |
| Disponible SKU/almacen | `18.9500` | `21.4500` |
| Existencias SKU/almacen | `2` | `3` |
| Movimientos referencia | `0` | `1` |
| Unidades referencia | `0` | `1` |

Evidencia generada:

| Elemento | Valor |
| --- | --- |
| Existencia | `EXI-50-35` |
| Movimiento | `83` |
| Unidad | `INV-II000083-0001` |
| Estado fisico | `abierta` |
| Estado etiqueta | `pendiente_impresion` |
| Estatus unidad | `disponible` |
| Cantidad existencia | `2.5000 kg` |
| Contenido unidad original | `4.000000 kg` |
| Contenido unidad disponible | `2.500000 kg` |

Validacion desde `listarExistencias()`:

- Busqueda por `INV-INICIAL-ACUARIO-20260710-UAT-ABIERTA`.
- Resultado:
  - `EXI-50-35`
  - `cantidad=2.5000`
  - `cantidad_disponible=2.5000`
  - `unidades_abiertas=1`
  - `contenido_base_original=4.000000`
  - `contenido_base_disponible=2.500000`
  - `unidad_base_trazable=kg`
  - `diferencia_contenido_unidades=0.000000`

Conclusion:

- Inventario inicial real ya puede registrar unidad fisica abierta de producto granel.
- La existencia agregada y la unidad fisica trazable cuadran.
- La unidad abierta queda disponible para Preparacion/POS futuro y no representa unidad cerrada vendible.

### INV-T030-UAT02 - Preflight unidad compra / factor

Fecha: 2026-07-10

Objetivo:

- Validar el segundo caso de inventario inicial real: captura por unidad de compra y factor de conversion.
- No escribir BD hasta contar con respaldo y autorizacion puntual.

Preflight read-only:

| Campo | Valor |
| --- | --- |
| Referencia | `INV-INICIAL-ACUARIO-20260710-UAT-CAJA` |
| SKU | `TP-40372` |
| ID SKU | `146` |
| Almacen | `3` / `BOD971` |
| Ubicacion | `13` / `E1-C2-P1-A1-N3` |
| Modo captura | `unidad_compra` |
| Cantidad compra | `5` |
| Factor conversion | `4` |
| Cantidad base calculada | `20 kg` |
| Lote | `UAT-INV-INICIAL-REAL-CAJA` |
| Caducidad | `2027-12-31` |
| Resultado | `ok=true` |
| Bloqueos | `[]` |

Advertencia vigente:

- La unidad compra preferida del SKU aparece como `kg`; si operativamente debe decir `CAJA`, corresponde a Catalogo/Proveedor.
- El factor `4.000000` si esta disponible y el preflight calcula correctamente `20 kg`.

Estado previo actual:

- Referencias `INV-INICIAL-ACUARIO-20260710-UAT%`:
  - movimientos `1`;
  - cantidad `2.5000 kg`.

Respaldo previo generado:

- Archivo:
  - `C:\xampp\panel_db_backups\artianilocal_panel_20260710_antes_inv_inicial_real_uat_caja.sql`
- Resultado:
  - `error=false`
  - bytes `28224118`
  - base `artianilocal`
- Estado:
  - listo para solicitar autorizacion de escritura UAT `unidad_compra`.

### INV-T030-UAT02 - Escritura controlada unidad compra / factor

Fecha: 2026-07-10

Autorizacion:

- El dueno del proyecto indico continuar despues de revisar preflight y respaldo.

Respaldo externo:

- `C:\xampp\panel_db_backups\artianilocal_panel_20260710_antes_inv_inicial_real_uat_caja.sql`
- Base: `artianilocal`
- Bytes: `28224118`

Payload aplicado:

| Campo | Valor |
| --- | --- |
| Referencia | `INV-INICIAL-ACUARIO-20260710-UAT-CAJA` |
| SKU | `TP-40372` |
| ID SKU | `146` |
| Almacen | `3` / `BOD971` |
| Ubicacion | `13` / `E1-C2-P1-A1-N3` |
| Modo captura | `unidad_compra` |
| Cantidad compra | `5` |
| Factor conversion | `4` |
| Cantidad base | `20.0000 kg` |
| Lote | `UAT-INV-INICIAL-REAL-CAJA` |
| Caducidad | `2027-12-31` |

Resultado del aplicador:

| Indicador | Valor |
| --- | --- |
| `ok` | `true` |
| Mensaje | `Inventario inicial ERP aplicado` |
| Movimientos | `1` |
| Etiquetas/unidades generadas | `0` |
| Origen tipo | `inventario_inicial` |

Evidencia antes/despues:

| Indicador | Antes | Despues |
| --- | ---: | ---: |
| Cantidad SKU/almacen | `21.4500` | `41.4500` |
| Disponible SKU/almacen | `21.4500` | `41.4500` |
| Existencias SKU/almacen | `3` | `4` |
| Movimientos referencia | `0` | `1` |
| Unidades referencia | `0` | `0` |

Evidencia generada:

| Elemento | Valor |
| --- | --- |
| Existencia | `EXI-50-36` |
| Movimiento | `84` |
| Cantidad existencia | `20.0000 kg` |
| Disponible | `20.0000 kg` |
| Lote | `UAT-INV-INICIAL-REAL-CAJA` |
| Ubicacion | `E1-C2-P1-A1-N3` |

Validacion desde `listarExistencias()`:

- Busqueda por `INV-INICIAL-ACUARIO-20260710-UAT-CAJA`.
- Resultado:
  - `EXI-50-36`
  - `cantidad=20.0000`
  - `cantidad_disponible=20.0000`
  - `unidades_total=0`
  - `diferencia_contenido_unidades=20.000000`

Interpretacion operativa:

- `unidad_compra` registra saldo agregado convertido a unidad base.
- No crea unidad fisica trazable ni etiqueta.
- La diferencia trazable de `20.000000` es esperada en este modo porque no toda existencia agregada tiene unidad fisica asociada.
- Si se quiere que cada caja quede identificada, etiquetable y con estado fisico, debe usarse `unidad_fisica_cerrada`.

Conclusion:

- Inventario inicial real ya puede cargar stock historico por unidad de compra/factor sin tocar Compras ni Recepcion.
- Kardex conserva folio y referencia documental.
- Queda validada la diferencia entre carga agregada y unidad fisica trazable.

### INV-T030-UAT03 - Preflight unidad fisica cerrada

Fecha: 2026-07-11

Contexto de ruta:

- Trabajo realizado sobre `C:\xampp\htdocs\panel_de_control`.
- Se valida explicitamente la ruta porque el entorno base puede mostrar `C:\xampp\htdocs\panel`.

Objetivo:

- Validar el tercer modo de inventario inicial real: unidades fisicas cerradas.
- Este modo debe crear saldo agregado y unidades fisicas trazables/etiquetables.

Preflight read-only:

| Campo | Valor |
| --- | --- |
| Referencia | `INV-INICIAL-ACUARIO-20260711-UAT-CERRADA` |
| SKU | `TP-40372` |
| ID SKU | `146` |
| Almacen | `3` / `BOD971` |
| Ubicacion | `13` / `E1-C2-P1-A1-N3` |
| Modo captura | `unidad_fisica_cerrada` |
| Unidades fisicas | `2` |
| Contenido por unidad | `4 kg` |
| Cantidad base calculada | `8 kg` |
| Lote | `UAT-INV-INICIAL-CERRADA` |
| Caducidad | `2027-12-31` |
| Resultado | `ok=true` |
| Bloqueos | `[]` |

Estado previo actual:

- `TP-40372` en almacen `3`:
  - cantidad `41.4500`
  - disponible `41.4500`
  - existencias `4`

Respaldo previo generado:

- Archivo:
  - `C:\xampp\panel_db_backups\artianilocal_panel_20260711_antes_inv_inicial_real_uat_cerrada.sql`
- Resultado:
  - `error=false`
  - bytes `28227854`
  - base `artianilocal`
- Estado:
  - listo para solicitar autorizacion de escritura UAT `unidad_fisica_cerrada`.

### INV-T030-UAT03 - Escritura controlada unidad fisica cerrada

Fecha: 2026-07-11

Autorizacion recibida:

```text
AUTORIZO INVENTARIO INICIAL REAL UAT unidad_fisica_cerrada usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_20260711_antes_inv_inicial_real_uat_cerrada.sql
```

Respaldo externo:

- `C:\xampp\panel_db_backups\artianilocal_panel_20260711_antes_inv_inicial_real_uat_cerrada.sql`
- Base: `artianilocal`
- Bytes: `28227854`

Payload aplicado:

| Campo | Valor |
| --- | --- |
| Referencia | `INV-INICIAL-ACUARIO-20260711-UAT-CERRADA` |
| SKU | `TP-40372` |
| ID SKU | `146` |
| Almacen | `3` / `BOD971` |
| Ubicacion | `13` / `E1-C2-P1-A1-N3` |
| Modo captura | `unidad_fisica_cerrada` |
| Unidades fisicas | `2` |
| Contenido por unidad | `4.000000 kg` |
| Cantidad base | `8.0000 kg` |
| Lote | `UAT-INV-INICIAL-CERRADA` |
| Caducidad | `2027-12-31` |

Resultado del aplicador:

| Indicador | Valor |
| --- | --- |
| `ok` | `true` |
| Mensaje | `Inventario inicial ERP aplicado` |
| Movimientos | `1` |
| Etiquetas/unidades generadas | `2` |
| Origen tipo | `inventario_inicial` |

Evidencia antes/despues:

| Indicador | Antes | Despues |
| --- | ---: | ---: |
| Cantidad SKU/almacen | `41.4500` | `49.4500` |
| Disponible SKU/almacen | `41.4500` | `49.4500` |
| Existencias SKU/almacen | `4` | `5` |
| Movimientos referencia | `0` | `1` |
| Unidades referencia | `0` | `2` |

Evidencia generada:

| Elemento | Valor |
| --- | --- |
| Existencia | `EXI-50-37` |
| Movimiento | `85` |
| Unidad 1 | `INV-II000085-0001` |
| Unidad 2 | `INV-II000085-0002` |
| Estado fisico | `cerrada` |
| Estado etiqueta | `pendiente_impresion` |
| Estatus unidad | `disponible` |
| Contenido por unidad | `4.000000 kg` |

Validacion desde `listarExistencias()`:

- Busqueda por `INV-INICIAL-ACUARIO-20260711-UAT-CERRADA`.
- Resultado:
  - `EXI-50-37`
  - `cantidad=8.0000`
  - `cantidad_disponible=8.0000`
  - `unidades_total=2`
  - `unidades_cerradas=2`
  - `unidades_abiertas=0`
  - `contenido_base_original=8.000000`
  - `contenido_base_disponible=8.000000`
  - `unidad_base_trazable=kg`
  - `diferencia_contenido_unidades=0.000000`

Conclusion:

- Inventario inicial real ya puede registrar unidades fisicas cerradas trazables.
- La existencia agregada y las unidades fisicas cuadran.
- Las unidades quedan pendientes de impresion/pegado para continuar en Almacen > Etiquetado.

## INV-T031 - Cierre read-only de Inventario Inicial Real

Fecha: 2026-07-11

Objetivo:

- Validar automaticamente las tres modalidades de Inventario Inicial Real ya aplicadas.
- Mantener la validacion read-only para no mover inventario ni modificar etiquetas.

Script agregado:

- `storage/uat/uat_inv_inicial_real_cierre_readonly.php`

Cobertura:

| Modalidad | Referencia | Esperado |
| --- | --- | --- |
| Unidad abierta | `INV-INICIAL-ACUARIO-20260710-UAT-ABIERTA` | `2.5 kg`, `1` abierta, diferencia `0` |
| Unidad compra | `INV-INICIAL-ACUARIO-20260710-UAT-CAJA` | `20 kg`, sin unidades fisicas, diferencia `20` esperada |
| Unidad cerrada | `INV-INICIAL-ACUARIO-20260711-UAT-CERRADA` | `8 kg`, `2` cerradas, diferencia `0` |

Validaciones tecnicas:

| Comando | Resultado |
| --- | --- |
| `C:\xampp\php\php.exe -l storage\uat\uat_inv_inicial_real_cierre_readonly.php` | OK |
| `C:\xampp\php\php.exe storage\uat\uat_inv_inicial_real_cierre_readonly.php` | `ok=true`, `fallos=[]` |
| `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php` | OK |
| `node --check public\assets\js\custom\apps\erp\inventarios\operacion_erp.js` | OK |

Evidencia validada:

- `EXI-50-35`
  - `2.5000 kg`
  - unidad `INV-II000083-0001`
  - `estado_fisico=abierta`
  - `estado_etiqueta=pendiente_impresion`
  - diferencia `0.000000`
- `EXI-50-36`
  - `20.0000 kg`
  - sin unidades fisicas
  - diferencia trazable `20.000000`, esperada por ser saldo agregado
- `EXI-50-37`
  - `8.0000 kg`
  - unidades `INV-II000085-0001` y `INV-II000085-0002`
  - `estado_fisico=cerrada`
  - `estado_etiqueta=pendiente_impresion`
  - diferencia `0.000000`

UAT manual pendiente:

1. Abrir `/inventario/productos_existencias`.
2. Buscar las tres referencias.
3. Validar Existencias, Kardex y pestana Unidades.
4. Continuar a `/almacen/etiquetado` para imprimir/pegar:
   - `INV-II000083-0001`;
   - `INV-II000085-0001`;
   - `INV-II000085-0002`.

Decision de cierre:

- Inventario Inicial Real queda tecnicamente validado para:
  - saldos agregados por unidad compra;
  - unidades fisicas abiertas;
  - unidades fisicas cerradas.
- Antes de cargar inventario real de tiendas, conviene definir folios por tienda y usar conteo fisico real.

## INV-UA-T001 - Visibilidad de unidades fisicas abiertas

Fecha: 2026-06-25

Origen:

- `docs/erp_almacen_unidades_fisicas_arranque.md`
- `docs/erp_inventario_existencias_unidades_abiertas_handoff.md`

Objetivo:

- Preparar Inventario > Existencias para mostrar unidades fisicas cerradas y abiertas sin confundir disponibilidad contable con unidad cerrada vendible.
- Mantener el cambio read-only: no mueve stock, no toca Ventas/ecommerce y no requiere migracion.

Regla operativa:

- Unidad abierta = stock disponible.
- Unidad abierta no es unidad cerrada vendible.
- Preparacion/Empaque puede consumir una unidad abierta.
- POS futuro podra venderla a granel si el SKU lo permite.
- Ecommerce no debe tomarla como unidad cerrada.

Cambios aplicados:

- `app/modelos/InventarioErp.php`
  - `listarExistencias()` ahora devuelve resumen de unidades fisicas por existencia:
    - `unidades_total`
    - `unidades_disponibles`
    - `unidades_cerradas`
    - `unidades_abiertas`
    - `unidades_consumidas`
    - `unidades_vendidas`
    - `etiquetas_pendientes`
    - `etiquetas_impresas`
    - `etiquetas_pegadas`
    - `contenido_base_original`
    - `contenido_base_disponible`
    - `unidad_base_trazable`
    - `diferencia_contenido_unidades`
  - `listarExistencias()` permite filtrar por `estado_fisico`.
  - `listarEtiquetas()` permite filtrar por `estado_fisico`.
- `app/vistas/paginas/apps/erp/inventarios/existencias.php`
  - Agrega filtro `Estado fisico`.
  - Versiona `existencias_erp.js` a `v=20260625-ua1`.
- `public/assets/js/custom/apps/erp/inventarios/existencias_erp.js`
  - Muestra resumen de unidades cerradas y abiertas en Existencias.
  - Muestra contenido trazable disponible y diferencia contra saldo contable.
  - Muestra estado fisico y contenido base en Unidades.
  - Muestra estado fisico y contenido base en Trazabilidad.
- `storage/uat/uat_inv_unidades_abiertas_readonly.php`
  - UAT read-only para validar la unidad abierta `UAT-EXI-26-20260625-001`.

Validaciones tecnicas:

| Comando | Resultado |
| --- | --- |
| `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php` | OK |
| `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\inventarios\existencias.php` | OK |
| `node --check public\assets\js\custom\apps\erp\inventarios\existencias_erp.js` | OK |
| `C:\xampp\php\php.exe -l storage\uat\uat_inv_unidades_abiertas_readonly.php` | OK |
| `C:\xampp\php\php.exe storage\uat\uat_inv_unidades_abiertas_readonly.php` | `ok=true` |

Evidencia UAT:

| Campo | Valor |
| --- | --- |
| Unidad | `UAT-EXI-26-20260625-001` |
| SKU | `TP-40372` |
| Existencia | `EXI-50-26` |
| Estado fisico | `abierta` |
| Estatus | `disponible` |
| Contenido disponible unidad | `14.950000 kg` |
| Cantidad existencia | `14.9500` |
| Disponible existencia | `14.9500` |
| Unidades abiertas | `1` |
| Contenido trazable existencia | `14.950000` |
| Diferencia saldo vs unidades | `0.000000` |
| Trazabilidad | `1` existencia, `8` movimientos, `1` unidad |
| Diagnostico operativo | `1` hallazgo informativo por etiquetas pendientes |

Resultado:

- Inventario ya muestra la unidad abierta como stock disponible con condicion operativa.
- La existencia contable y el contenido trazable cuadran para `EXI-50-26`.
- No hubo escrituras de BD.
- Queda pendiente una pasada visual en navegador para confirmar que los textos largos no rompan la tabla.

## INV-UA-T002 - UAT visual de unidades abiertas

Fecha: 2026-06-25

Objetivo:

- Validar que la UI de Inventario > Existencias renderiza correctamente unidades fisicas cerradas y abiertas.
- Mantener la prueba read-only y sin navegador autenticado obligatorio.

Script actualizado:

- `storage/uat/uat_inv_visual_render_check.js`

Cobertura agregada:

- Incluye el filtro `inventario_filtro_estado_fisico`.
- Simula existencia con unidad abierta:
  - `EXI-50-26`
  - SKU `TP-40372`
  - Unidad `UAT-EXI-26-20260625-001`
  - `14.9500 kg`
  - estado fisico `abierta`
- Valida resumen de:
  - unidades cerradas;
  - unidades abiertas;
  - agotadas;
  - trazabilidad disponible.
- Valida que la pestana Unidades muestre:
  - estado fisico `Abierta`;
  - contenido `14.9500 kg / 14.9750`;
  - enlace a Etiquetado;
  - boton de Trazabilidad.

Validaciones:

| Comando | Resultado |
| --- | --- |
| `node --check storage\uat\uat_inv_visual_render_check.js` | OK |
| `node storage\uat\uat_inv_visual_render_check.js` | `ok=true` |
| `C:\xampp\php\php.exe storage\uat\uat_inv_unidades_abiertas_readonly.php` | `ok=true` |

Resultado render:

- Resumen muestra `Unid. cerradas 5` y `Unid. abiertas 1`.
- Existencias muestra `Trazable 14.9500 kg` con diferencia `0.0000`.
- Unidades muestra `UAT-EXI-26-20260625-001` como `Abierta`, `Pegada`, `disponible`.
- SKU agotado sin unidades conserva mensaje operativo para revisar Existencias o Kardex.

UAT manual sugerido en navegador:

1. Abrir `/inventario/productos_existencias`.
2. Buscar `UAT-EXI-26-20260625-001`.
3. Confirmar que aparece `EXI-50-26`, `TP-40372`, `14.9500 kg`, `Abierta`.
4. Usar filtro `Estado fisico > Abiertas`.
5. Abrir pestana `Unidades`.
6. Abrir `Trazabilidad` de la unidad.
7. Buscar `REC-OC-25` y validar las 3 unidades cerradas pendientes de impresion.

Resultado:

- Inventario/Existencias queda visual y tecnicamente preparado para unidades fisicas abiertas.
- No hubo escrituras de BD.
- Ventas/ecommerce siguen fuera de alcance.

## INV-UA-T003 - Cierre final de Inventario/Existencias con unidades abiertas

Fecha: 2026-06-25

Objetivo:

- Cerrar formalmente Inventario > Existencias despues de incorporar unidades fisicas cerradas y abiertas.
- Dejar una frontera clara para no iniciar Ventas/POS/Pedidos con Inventario incompleto.

Estado final del modulo:

| Area | Estado | Evidencia |
| --- | --- | --- |
| Existencia agregada por SKU/lote/caducidad/ubicacion | Cerrado | `/inventario/existencias_erp` |
| Kardex ERP por referencia y origen | Cerrado | `/inventario/movimientos_erp` |
| Trazabilidad por existencia, movimiento y unidad | Cerrado | `/inventario/trazabilidad_erp` |
| Unidades fisicas cerradas | Cerrado | `estado_fisico='cerrada'`, contenido base visible |
| Unidades fisicas abiertas | Cerrado | `estado_fisico='abierta'`, contenido disponible visible |
| Unidades consumidas/agotadas | Cerrado primera version | se muestran como estado fisico no disponible |
| Etiquetas pendientes de impresion/pegado | Cerrado primera version | diagnostico `INV-DIAG-ETQ` |
| Diagnostico saldo vs unidades trazables | Cerrado | compara contra `SUM(cantidad_base_disponible)` |
| Reservas/apartados | Cerrado primera version | crear, listar, liberar, diagnosticar |
| Conteo fisico/ciclico | Cerrado primera version | crear, capturar, previsualizar, cerrar |
| Valuacion bruta de inventario | Cerrado | reporte read-only |

Reglas finales:

- Inventario es fuente de saldos, kardex, trazabilidad, diagnostico y valuacion.
- Una existencia agregada representa saldo contable en unidad base.
- Una unidad fisica representa identidad trazable y puede tener contenido base distinto de `1`.
- Una unidad abierta conserva stock disponible, pero no es unidad cerrada vendible.
- Una unidad abierta puede ser consumida por Preparacion/Empaque.
- POS futuro podra vender unidad abierta a granel solo si el SKU/regla comercial lo permite.
- Ecommerce no debe tomar unidades abiertas como unidad cerrada disponible.
- Inventario no debe decidir reglas comerciales de venta, precio, canal, cliente o margen.

Validacion final ejecutada:

| Comando | Resultado |
| --- | --- |
| `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php` | OK |
| `node --check public\assets\js\custom\apps\erp\inventarios\existencias_erp.js` | OK |
| `node storage\uat\uat_inv_visual_render_check.js` | `ok=true` |
| `C:\xampp\php\php.exe storage\uat\uat_inv_unidades_abiertas_readonly.php` | `ok=true` |

Evidencia critica:

- Unidad abierta: `UAT-EXI-26-20260625-001`.
- Existencia: `EXI-50-26`.
- SKU: `TP-40372`.
- Estado fisico: `abierta`.
- Estatus: `disponible`.
- Contenido disponible: `14.950000 kg`.
- Existencia contable: `14.9500`.
- Diferencia saldo vs unidades: `0.000000`.
- Diagnostico operativo: sin descuadre; solo hallazgo informativo por etiquetas pendientes.

UAT manual antes de iniciar Ventas/POS:

1. Abrir `/inventario/productos_existencias`.
2. Buscar `UAT-EXI-26-20260625-001`.
3. Validar que se ve como `Abierta`, `Pegada`, `disponible`.
4. Validar `Trazable 14.9500 kg` y diferencia `0.0000`.
5. Usar filtro `Estado fisico > Abiertas`.
6. Abrir pestana `Unidades`.
7. Abrir modal `Trazabilidad`.
8. Buscar `REC-OC-25` y confirmar las 3 unidades cerradas pendientes de impresion.

Pendientes intencionales fuera de Inventario:

| Pendiente | Modulo dueno | Motivo |
| --- | --- | --- |
| Venta de unidad cerrada | Ventas/POS/Pedidos | Requiere documento comercial y reglas de cobro |
| Venta a granel desde unidad abierta | POS/Ventas | Requiere reglas de SKU, unidad, precio y corte fisico |
| Excluir unidad abierta de ecommerce | Ecommerce/Ventas | Requiere sincronizacion por canal |
| Consumo automatico de reservas | Ventas/Pedidos | Requiere folio/documento origen |
| Utilidad real por canal | Costos/Rentabilidad | Requiere precio, costo, impuesto y gastos |
| Alertas persistentes por hallazgos | Notificaciones | Diagnostico no reemplaza flujo de pendientes |

Criterio de cierre:

- Inventario/Existencias queda cerrado para iniciar el siguiente modulo.
- No se debe agregar logica de venta o ecommerce dentro de Inventario.
- Cualquier movimiento real futuro de inventario debe conservar respaldo externo, UAT, evidencia antes/despues y folio operativo.

## Prompt sugerido para nuevo chat

```text
Estoy continuando el ERP en el modulo Inventario/Existencias despues de cerrar Almacen > Preparacion/Empaque de presentaciones.

Lee primero:
- AGENTS.md
- docs/erp_plan_maestro_fundamentos.md
- docs/erp_ux_operativa.md
- docs/erp_almacen_recepciones_cierre.md
- docs/erp_almacen_preparacion_empaque_diseno.md
- docs/erp_almacen_preparacion_empaque_tareas.md
- docs/erp_inventario_existencias_arranque.md
- app/controladores/Inventario.php
- app/modelos/InventarioErp.php
- app/controladores/Almacen.php
- app/modelos/Almacenes.php

Quiero trabajar igual: tareas pequenas, UAT documentado, evidencia por folio/SKU, hallazgos con ID, respaldo externo antes de escrituras de BD, sin mezclar ERP nuevo con legacy, y que me sugieras decisiones de ERP robusto explicando por que.

Contexto cerrado en Almacen:
- Recepcion ya crea existencias, movimientos y etiquetas cuando el proveedor entrega producto listo.
- Preparacion/Empaque ya consume una existencia fisica origen y crea existencia del SKU resultado.
- Catalogo solo define SKUs, unidades, reglas y transformaciones; no crea existencias.
- Inventario debe consultar saldos, movimientos, kardex y trazabilidad; no debe preparar producto ni crear bolsas teoricas.

Folios/evidencia que deben validarse en Inventario:
- `REC-OC-20`: recepcion historica de `TP-40372`; saldo corregido por `CAT-GRANEL-H001`.
- `CAT-GRANEL-H001`: ajuste documentado de `TP-40372` para reflejar `20 kg`.
- `PREP-20260622-0001`: preparacion de `20` unidades `TP-40372-25GR`, movimientos `37/38`, etiquetas `P25-*`.
- `PREP-20260621-0002`: preparacion intermedia de `1` unidad `TP-40372-500GR`, movimientos `39/40`.
- `PREP-20260621-0003`: reempaque de `1` unidad `TP-40372-500GR` a `5` unidades `TP-40372-100GR`, movimientos `41/42`, etiquetas `P100-P000004-0001` a `P100-P000004-0005` en estado `pegada`.

Saldos esperados actuales en almacen `BOD971`:
- `TP-40372`, lote `L1`: `15.0000 kg`, disponible.
- `TP-40372`, lote `L2`: `4.0000 kg`, disponible.
- `TP-40372-25GR`, lote `L1`: `20.0000`, disponible.
- `TP-40372-100GR`, lote `L1`: `5.0000`, disponible.
- `TP-40372-500GR`, lote `L1`: `0.0000`, estado `agotada`.

Objetivo de esta fase:
Auditar y mejorar Inventario > Existencias/Kardex para que muestre correctamente:
1. saldos separados por SKU y almacen;
2. lote, caducidad, ubicacion y estado de existencia;
3. movimientos con referencia `REC-*`, `CAT-GRANEL-H001` y `PREP-*`;
4. trazabilidad desde una etiqueta/unidad hacia folio de preparacion, existencia origen y movimientos relacionados;
5. diferencia clara entre existencia base en kg, presentaciones disponibles y presentaciones agotadas.

Reglas:
- No tocar Ventas ni ecommerce.
- No mover stock ni ajustar inventario sin respaldo externo y autorizacion.
- No reabrir recepciones ni preparaciones confirmadas para corregir visualizacion.
- Si falta estructura o endpoint, primero auditar y proponer DDL/cambios; no aplicar migraciones sin autorizacion.

Empieza auditando las pantallas/endpoints actuales de Inventario > Existencias y Kardex con estos SKUs/folios: `TP-40372`, `TP-40372-25GR`, `TP-40372-100GR`, `TP-40372-500GR`, `REC-OC-20`, `CAT-GRANEL-H001`, `PREP-20260622-0001`, `PREP-20260621-0002` y `PREP-20260621-0003`. No ejecutes escrituras hasta que necesites mi autorizacion.
```
