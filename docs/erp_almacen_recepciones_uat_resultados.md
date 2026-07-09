# ERP Almacen - Recepciones UAT

Documentacion IA: Codex GPT-5  
Fecha de arranque: 2026-06-18  
Estado: UAT base REC-OC-24 ejecutado con respaldo externo previo.

## Metodo

- Auditoria inicial tomada con consultas `SELECT` puntuales sobre `REC-OC-24` y `REC-OC-20`.
- Antes de guardar recepcion se genero respaldo externo en `C:\Users\aleja\Documents\RespaldosBD\panel\artianilocal_panel_20260618_081947_antes_uat_alm_rec_oc_24.sql`.
- Se ejecutaron guardados controlados solo sobre `REC-OC-24`.
- No mezclar flujo ERP nuevo con endpoints legacy de inventario.

## Matriz inicial

| ID | Escenario | Recepcion/Orden | Resultado | Evidencia | Hallazgo |
| --- | --- | --- | --- | --- | --- |
| UAT-ALM-001 | Listado de recepciones muestra ordenes enviadas | REC-OC-20 / REC-OC-24 | Aprobado tecnico | Modelo/listado devuelve REC-OC-20 pendiente y REC-OC-24 recibida con cantidades correctas |  |
| UAT-ALM-002 | Ver recepcion carga detalle correcto | REC-OC-20 / REC-OC-24 | Aprobado tecnico | Consulta completa carga encabezado y detalle de ambas recepciones; REC-OC-20 conserva 13 partidas pendientes y REC-OC-24 1 partida recibida |  |
| UAT-ALM-003 | Recepcion parcial actualiza recepcion y orden parcial | REC-OC-24 / OC-2026-000024 | Aprobado | Recibidas 2/5 unidades; recepcion y orden quedaron `parcial`; 1 lote y 1 movimiento |  |
| UAT-ALM-004 | Recepcion completa actualiza recepcion y orden recibida | REC-OC-24 / OC-2026-000024 | Aprobado | Recibidas 5/5 unidades; recepcion y orden quedaron `recibida`; 2 lotes y 2 movimientos |  |
| UAT-ALM-005 | No duplica movimientos al guardar dos veces | REC-OC-24 / OC-2026-000024 | Aprobado | Reintento sobre recepcion `recibida` fue bloqueado; lotes y movimientos permanecen en 2 por 5 unidades | ALM-H001 |
| UAT-ALM-006 | Lote/caducidad/ubicacion requeridos segun reglas | REC-OC-24 / OC-2026-000024 | Pendiente | UI y backend validan lote/caducidad segun reglas; falta prueba real |  |
| UAT-ALM-007 | Existencias se actualizan correctamente | REC-OC-24 / OC-2026-000024 | Aprobado | Existencia para producto 319 / SKU 415 en almacen 3 suma 5 disponibles |  |
| UAT-ALM-008 | Notificacion de recepcion pendiente se cierra al atender | REC-OC-24 / OC-2026-000024 | Aprobado | Notificacion id 13 quedo `resuelta` al iniciar recepcion parcial |  |

Mapeo con Compras:

- `UAT-ALM-003` cierra `UAT-COM-008`.
- `UAT-ALM-004` cierra `UAT-COM-009`.
- `UAT-ALM-008` cierra `UAT-NOT-006`.

## Evidencia inicial 2026-06-18

### REC-OC-24 / OC-2026-000024

Resultado esperado de auditoria: recepcion pendiente, solo producto fisico, sin movimientos previos.

Resultado obtenido:

- Orden `OC-2026-000024`: `id_orden_compra=24`, estatus `enviada`, almacen destino `3`, total `1906.00`.
- Recepcion `REC-OC-24`: `id_recepcion_almacen=2`, estatus `pendiente`, sin fecha de inicio ni cierre.
- Detalle: 1 partida, `id_recepcion_detalle=14`, `SAL-50L`, `id_sku_erp=415`, tipo `producto`, cantidad ordenada `5.0000`, recibida `0.0000`, pendiente `5.0000`.
- No inventariables en recepcion: `0`.
- Lotes previos: `0`.
- Movimientos previos: `0`.
- Notificacion de almacen: `id_notificacion=13`, tipo `compra_orden_enviada_recepcion_pendiente`, estatus `pendiente`.

Conclusion: folio recomendado para UAT corto de recepcion parcial/completa. Antes de guardar, respaldar BD.

### REC-OC-20 / OC-2026-000020

Resultado esperado de auditoria: recepcion pendiente amplia para prueba extendida, sin movimientos previos.

Resultado obtenido:

- Orden `OC-2026-000020`: `id_orden_compra=20`, estatus `enviada`, almacen destino `3`, total `13396.89`.
- Recepcion `REC-OC-20`: `id_recepcion_almacen=1`, estatus `pendiente`, sin fecha de inicio ni cierre.
- Detalle: 13 partidas, `105.0000` unidades ordenadas, `0.0000` recibidas, `105.0000` pendientes.
- No inventariables en recepcion: `0`.
- Lotes previos: `0`.
- Movimientos previos: `0`.
- Notificacion de almacen: `id_notificacion=11`, tipo `compra_orden_enviada_recepcion_pendiente`, estatus `pendiente`.

Conclusion: folio util para prueba extendida, pero se mantiene en reserva por tener mas partidas y pagos/notas en Compras.

## UAT ejecutado 2026-06-18

### UAT-ALM-001 - Listado de recepciones muestra ordenes enviadas

Folios revisados: `REC-OC-20` / `REC-OC-24`

Resultado obtenido por evidencia tecnica:

- `Almacenes::consultar_recepciones_almacen` devuelve `REC-OC-24` con orden `OC-2026-000024`, estatus `recibida`, 1 partida, ordenada `5.0000`, recibida `5.0000`, pendiente `0.0000`.
- `Almacenes::consultar_recepciones_almacen` devuelve `REC-OC-20` con orden `OC-2026-000020`, estatus `pendiente`, 13 partidas, ordenada `105.0000`, recibida `0.0000`, pendiente `105.0000`.
- `listing_recepciones.js` muestra accion `Ver recepcion` para `recibida`/`cancelada` y `Recibir` para pendientes/parciales.
- Filtro de estatus contempla `pendiente`, `parcial`, `recibida` y `cancelada`.

Resultado: Aprobado tecnico. Falta validacion visual en navegador por usuario para UX final.

### UAT-ALM-002 - Ver recepcion carga detalle correcto

Folios revisados: `REC-OC-20` / `REC-OC-24`

Resultado obtenido por evidencia tecnica:

- `Almacenes::consultar_recepcion_almacen_completa(1)` devuelve `REC-OC-20`, proveedor `SUNNY`, almacen `Francisco Javier Mina 971`, estatus `pendiente`, 13 partidas y 105 pendientes.
- `Almacenes::consultar_recepcion_almacen_completa(2)` devuelve `REC-OC-24`, proveedor `SUNNY`, almacen `Francisco Javier Mina 971`, estatus `recibida`, 1 partida `SAL-50L`, ordenada `5.0000`, recibida `5.0000`, pendiente `0.0000`.
- `recibir.js` deshabilita el boton de guardado cuando la recepcion esta `recibida` o `cancelada`.
- Ajuste UX 2026-06-18: la pantalla de recepcion cambia el color del badge de estatus y muestra `Recepcion cerrada` cuando ya no permite movimientos.

Resultado: Aprobado tecnico. Falta validacion visual en navegador por usuario para UX final.

### UAT-ALM-003 - Recepcion parcial actualiza recepcion y orden parcial

Folio/Orden: `REC-OC-24` / `OC-2026-000024`  
Usuario/Rol: ejecucion tecnica local, usuario de sesion no aplicado (`recibido_por` nulo)  
Respaldo previo: `C:\Users\aleja\Documents\RespaldosBD\panel\artianilocal_panel_20260618_081947_antes_uat_alm_rec_oc_24.sql`

Accion:

- Se recibieron `2.0000` de `5.0000` unidades de `SAL-50L`.
- Lote usado: `UAT-REC-OC-24-P1`.
- Caducidad: `2027-12-31`.
- Ubicacion: `UAT-ALM-REC-24`.

Resultado obtenido:

- Respuesta backend: `error=false`, `estatus_recepcion=parcial`, `estatus_orden=parcial`, `lotes=1`, `movimientos=1`, `incidencias=0`.
- Orden `OC-2026-000024`: estatus `parcial`.
- Detalle de orden `SAL-50L`: cantidad `5.000000`, cantidad recibida `2.000000`.
- Recepcion `REC-OC-24`: estatus `parcial`, fecha de inicio `2026-06-18 00:20:16`, sin fecha de cierre.
- Detalle de recepcion: ordenada `5.0000`, recibida `2.0000`, pendiente `3.0000`, estatus `parcial`.
- Lote `LOT-2-18`: cantidad `2.0000`, estatus `disponible`.
- Movimiento `18`: entrada por `2.0000`, `origen_tipo='recepcion_compra'`, referencia `REC-OC-24`.
- Notificacion `13`: estatus `resuelta`, fecha resolucion `2026-06-18 00:20:16`.

Resultado: Aprobado. Cierra `UAT-COM-008`.

### UAT-ALM-004 - Recepcion completa actualiza recepcion y orden recibida

Folio/Orden: `REC-OC-24` / `OC-2026-000024`  
Respaldo previo: `C:\Users\aleja\Documents\RespaldosBD\panel\artianilocal_panel_20260618_081947_antes_uat_alm_rec_oc_24.sql`

Accion:

- Se recibieron las `3.0000` unidades restantes de `SAL-50L`.
- Lote usado: `UAT-REC-OC-24-C1`.
- Caducidad: `2027-12-31`.
- Ubicacion: `UAT-ALM-REC-24`.

Resultado obtenido:

- Respuesta backend: `error=false`, `estatus_recepcion=recibida`, `estatus_orden=recibida`, `lotes=1`, `movimientos=1`, `incidencias=0`.
- Orden `OC-2026-000024`: estatus `recibida`.
- Detalle de orden `SAL-50L`: cantidad `5.000000`, cantidad recibida `5.000000`.
- Cargo `Empaque y maniobra`: sigue como `tipo_item=cargo`, cantidad recibida `0.000000`, fuera del detalle de recepcion.
- Recepcion `REC-OC-24`: estatus `recibida`, fecha cierre `2026-06-18 00:20:47`.
- Detalle de recepcion: ordenada `5.0000`, recibida `5.0000`, pendiente `0.0000`, estatus `recibida`.
- Lotes: `LOT-2-18` por `2.0000` y `LOT-2-19` por `3.0000`.
- Movimientos: `18` por `2.0000` y `19` por `3.0000`, ambos entrada de recepcion.
- Existencias para producto `319`, SKU `415`, almacen `3`: cantidad `5.0000`, disponible `5.0000`.

Resultado: Aprobado. Cierra `UAT-COM-009`.

### UAT-ALM-005 - No duplica movimientos en reintento

Folio/Orden: `REC-OC-24` / `OC-2026-000024`

Accion:

- Se intento guardar una unidad adicional sobre la recepcion ya `recibida`.
- Lote de prueba bloqueada: `UAT-REC-OC-24-DUP`.

Resultado obtenido:

- Respuesta backend: `error=true`, mensaje `La recepcion ya no permite movimientos`.
- Lotes posteriores: `2`, cantidad total `5.0000`.
- Movimientos posteriores: `2`, cantidad total `5.0000`.
- Lote `UAT-REC-OC-24-DUP`: `0` registros.

Resultado: Aprobado. ALM-H001 queda cerrado para el caso de reintento posterior a cierre.

### UAT-ALM-007 - Existencias actualizadas correctamente

Folio/Orden: `REC-OC-24` / `OC-2026-000024`

Resultado obtenido:

- Existencia acumulada para producto `319`, SKU `415`, almacen `3`, lotes UAT: `5.0000`.
- Disponible acumulado: `5.0000`.
- Ultimos movimientos vinculados a las existencias creadas: `18` y `19`.
- `InventarioErp::listarExistencias` con almacen `3` y busqueda `SAL-50L` devuelve:
  - `EXI-319-18`, lote `UAT-REC-OC-24-P1`, cantidad disponible `2.0000`.
  - `EXI-319-19`, lote `UAT-REC-OC-24-C1`, cantidad disponible `3.0000`.
- `InventarioErp::listarMovimientos` con almacen `3` y busqueda `REC-OC-24` devuelve:
  - Movimiento `19`, entrada `3.0000`, referencia `REC-OC-24`, observacion `UAT-ALM-004 recepcion completa REC-OC-24`.
  - Movimiento `18`, entrada `2.0000`, referencia `REC-OC-24`, observacion `UAT-ALM-003 recepcion parcial REC-OC-24`.
- `InventarioErp::buscarSkus('SAL-50L', 3)` devuelve existencia disponible `5.0000`.

Resultado: Aprobado.

### UAT-ALM-008 - Notificacion de recepcion pendiente se cierra al atender

Folio/Orden: `REC-OC-24` / `OC-2026-000024`

Resultado obtenido:

- Antes de recibir: notificacion `13` tipo `compra_orden_enviada_recepcion_pendiente`, estatus `pendiente`.
- Al guardar parcial: notificacion `13` quedo `resuelta`, fecha resolucion `2026-06-18 00:20:16`.
- Al completar recepcion se mantuvo resuelta.

Resultado: Aprobado. Cierra `UAT-NOT-006`.

## Contrato tecnico observado

- Endpoint listado: `Almacen::obtener_recepciones`, permiso `almacen.ver`.
- Endpoint consulta: `Almacen::consultar_recepcion`, permiso `almacen.ver`.
- Endpoint guardado: `Almacen::guardar_recepcion`, permiso `almacen.recibir`, auditoria explicita `almacen/guardar_recepcion`.
- `Almacenes::guardar_recepcion_almacen` crea lote, actualiza existencia, inserta movimiento, sincroniza detalle de orden, cambia recepcion y orden a `parcial` o `recibida`, y cierra notificacion de recepcion pendiente.
- `Almacenes::consultar_detalle_orden_compra_para_recepcion` excluye `servicio`, `cargo`, `adicional` y `no_inventariable`.
- Los movimientos de recepcion se insertan con `origen_tipo='recepcion_compra'`, `origen_id=id_recepcion_almacen` y `origen_detalle_id=id_recepcion_detalle`.

## Verificaciones tecnicas

- `C:\xampp\php\php.exe -l app\modelos\Almacenes.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\almacen\recibir\recibir.js`: sin errores.
- `node --check public\assets\js\custom\apps\erp\almacen\recibir\recepcion.js`: sin errores.
- `node --check public\assets\js\custom\apps\erp\almacen\mostrar_recepciones\listing_recepciones.js`: sin errores.
