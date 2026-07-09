# ERP Almacen - Recepciones tareas vivas

Documentacion IA: Codex GPT-5  
Fecha de arranque: 2026-06-18

## Reglas activas

- Trabajar por tareas pequenas y verificables.
- No tocar BD sin respaldo externo previo.
- Documentar evidencia por folio.
- Usar hallazgos con ID.
- No mezclar flujo ERP nuevo con legacy.
- No aprobar UAT con supuestos.

## Hallazgos

### ALM-H001 - Idempotencia de guardado de recepcion pendiente por validar

Fecha: 2026-06-18  
Folio/Orden: `REC-OC-24` / `OC-2026-000024`  
Area: Almacen > Recepciones  
Prioridad: Alta para UAT antes de liberar flujo base  
Estado: Cerrado para reintento posterior a cierre

Descripcion:

El backend evita recibir mas de lo pendiente y bloquea recepciones `recibida` o `cancelada`. La prueba de no duplicar movimientos se ejecuto sobre `REC-OC-24` despues de completar la recepcion.

Evidencia:

- `REC-OC-24`: lotes `0`, movimientos `0`.
- `REC-OC-20`: lotes `0`, movimientos `0`.
- `Almacenes::guardar_recepcion_almacen` recalcula cantidad recibida desde lotes y actualiza pendiente.
- `Almacenes::insertar_movimiento_inventario` inserta movimientos con `origen_tipo='recepcion_compra'`.
- Despues de recibir completo `REC-OC-24`: lotes `2`, movimientos `2`, cantidad total `5.0000`.
- Reintento con lote `UAT-REC-OC-24-DUP`: backend respondio `La recepcion ya no permite movimientos`; no se inserto lote ni movimiento adicional.

Decision:

- `UAT-ALM-005` aprobado para reintento posterior a cierre.
- Queda pendiente, para una fase posterior, probar doble submit simultaneo si se requiere garantia de concurrencia a nivel UI/red.
- La consulta de movimientos debe usar `origen_tipo='recepcion_compra'`, `origen_id=id_recepcion_almacen` y `origen_detalle_id=id_recepcion_detalle`.

### ALM-H002 - Nombre de producto con codificacion mixta en evidencia de recepcion/inventario

Fecha: 2026-06-18  
Folio/Orden: `REC-OC-24` / `OC-2026-000024`  
Area: Almacen / Inventario / Catalogo  
Prioridad: Baja  
Estado: Cerrado

Descripcion:

En respuestas tecnicas de recepcion e inventario, el campo de producto aparece con caracteres mojibake en algunos origenes, por ejemplo `L├â┬ímpara...`. El campo `nombre_sku` de `InventarioErp::listarExistencias` aparece correcto como `Lámpara...`, por lo que parece una diferencia de datos/origen entre producto y SKU.

Impacto:

- No bloquea recepcion, existencia ni kardex.
- Puede afectar UX visual y busqueda si el usuario ve el campo de producto afectado.

Decision:

- No corregir durante UAT base de recepciones.
- Revisar en fase de calidad de catalogo/datos si conviene normalizar nombres de producto ERP.

Evidencia ampliada 2026-06-18:

- `erp_almacenes`: 1 registro sospechoso (`San Jos├â┬® 1727`).
- `erp_catalogo_productos`: 1500 registros sospechosos en nombre/descripcion.
- `erp_catalogo_skus`: 418 registros sospechosos en nombre.
- `erp_almacen_recepciones_detalle`: 3 snapshots sospechosos.
- `erp_almacen_ubicaciones`: 0 registros sospechosos.
- `erp_inventario_existencias`: 0 registros sospechosos.

Conclusion:

- El origen principal esta en maestros de Catalogo ERP, no en la recepcion.
- Cualquier correccion requiere preview, respaldo externo y autorizacion antes de `UPDATE`.
- La conversion candidata validada en muestra es doble paso `UTF-8 -> CP850 -> ISO-8859-1`, que devuelve `San José`, `hámster`, `café` y `Lámpara` correctamente.

Correccion aplicada 2026-06-18:

- Respaldo externo: `artianilocal_panel_20260618_almacen_codificacion_erp_almacenes.sql`.
- `erp_almacenes.id_almacen=2`: `almacen` y `calle` corregidos a bytes UTF-8 correctos.
- Verificacion por `HEX`: `San José 1727` queda `53616E204A6F73C3A92031373237`; `san José` queda `73616E204A6F73C3A9`.
- Nota historica: inicialmente quedaban pendientes Catalogo ERP maestro y snapshots de recepcion; se cerraron por bloques con respaldo y verificaciones posteriores.
- CSV historico de trabajo: `docs/erp_almacen_codificacion_pendiente_20260618.csv`, 2223 pares candidatos.

Correccion de nombres aplicada 2026-06-18:

- Respaldo externo: `artianilocal_panel_20260618_almacen_codificacion_catalogo_nombres.sql`.
- Actualizados 726 nombres:
  - `erp_catalogo_productos.nombre`: 305.
  - `erp_catalogo_skus.nombre`: 418.
  - `erp_almacen_recepciones_detalle.nombre_producto`: 3.
- Verificacion por bytes mojibake: 0 pendientes en esas tres columnas.
- Pendiente separado y regenerado desde BD actual: `docs/erp_almacen_codificacion_pendiente_descripciones_20260618.csv`, 1497 descripciones HTML.

Correccion de descripciones aplicada 2026-06-18:

- Respaldo externo: `artianilocal_panel_20260618_almacen_codificacion_catalogo_descripciones.sql`.
- Actualizadas 1497 descripciones HTML de `erp_catalogo_productos.descripcion`.
- Verificacion con bytes estructurales de mojibake (`E2949C`, `E294AC`): 0 pendientes en descripciones, nombres de producto, nombres SKU y snapshots de recepcion.
- Nota: `mysql.exe` puede mostrar acentos validos con simbolos raros por consola, pero los bytes quedaron UTF-8 correctos.

### ALM-H003 - Detalle de recepcion pendiente mostraba `completo` antes de guardar

Fecha: 2026-06-18  
Folio/Orden: `REC-OC-20` / `OC-2026-000020`  
Area: Almacen > Recepciones  
Prioridad: Media  
Estado: Cerrado

Descripcion:

En la prueba visual autenticada, `REC-OC-20` tenia 13 partidas pendientes y 105 unidades pendientes en el listado, pero dentro de la pantalla de recepcion cada partida mostraba la etiqueta `completo` antes de guardar. La BD seguia correcta; el problema era de texto visual porque el formulario precarga `A recibir` con toda la cantidad pendiente y calcula el pendiente proyectado si se guarda.

Decision:

- Mantener la precarga de `A recibir` con la cantidad pendiente, porque agiliza una recepcion completa.
- Cambiar la etiqueta visual de `completo` a estados operativos no persistidos: `listo por guardar`, `parcial por guardar`, `sin captura`, `excedente` o `recibida`.
- Cambiar el encabezado de columna a `Pendiente al guardar` para diferenciarlo del pendiente real mostrado en el listado.
- Versionar los scripts de la vista de recepcion con `?v=20260618-1` para evitar cache de navegador.

Evidencia:

- Antes: `storage/uat/visual/almacen_recibir_rec_oc_20_pendiente_20260618.png`.
- Despues: `storage/uat/visual/almacen_recibir_rec_oc_20_pendiente_final_20260618.png`.
- Recepcion cerrada validada: `storage/uat/visual/almacen_recibir_rec_oc_24_cerrada_ajuste_20260618.png`.
- Verificacion: `node --check public\assets\js\custom\apps\erp\almacen\recibir\recibir.js`.
- Verificacion: `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\almacen\recibir.php`.

### ALM-H004 - Reglas de lote/caducidad/serie apagadas en todos los SKUs

Fecha: 2026-06-18  
Folio/Orden: `REC-OC-20` / `OC-2026-000020`  
Area: Almacen / Catalogo ERP / Inventario  
Prioridad: Alta para `UAT-ALM-006`  
Estado: Cerrado para piloto

Descripcion:

`REC-OC-20` tiene 13 partidas pendientes y sirve para prueba extendida de recepcion, pero sus 13 partidas consultadas por regla de inventario tienen `requiere_lote=0`, `requiere_caducidad=0` y `requiere_serie=0`. La auditoria general de `erp_catalogo_sku_reglas_inventario` encontro 1744 reglas existentes, pero ninguna activa lote, caducidad o serie.

Impacto:

- No bloquea recepcion normal.
- Si se recibe alimento sin lote/caducidad, el ERP no tendra trazabilidad por vencimiento.
- `UAT-ALM-006` no puede aprobarse con el estado actual porque no hay reglas activas que obliguen lote/caducidad/serie.

Evidencia:

- `erp_catalogo_sku_reglas_inventario`: 1744 reglas.
- `requiere_lote=1`: 0.
- `requiere_caducidad=1`: 0.
- `requiere_serie=1`: 0.
- Candidatos textuales preliminares:
  - `caducidad_lote_probable`: 593 SKUs.
  - `serie_o_garantia_probable`: 109 SKUs.
  - `sin_control_probable`: 1042 SKUs.
- En `REC-OC-20`, los alimentos `TP-7838`, `TP-7840`, `SFF-03`, `SFF-303` y `TP-40372` son candidatos piloto para lote/caducidad.

Decision recomendada:

- No activar reglas masivas por texto automaticamente.
- Preparar piloto con los 5 SKUs de alimento de `REC-OC-20`.
- Antes de cualquier `UPDATE`: generar respaldo externo, preview antes/despues y pedir autorizacion explicita.

Piloto aplicado:

- Respaldo externo: `artianilocal_panel_20260619_almacen_reglas_piloto_rec_oc_20.sql`.
- Filas afectadas: 5.
- SKUs actualizados: `TP-7838`, `TP-7840`, `SFF-03`, `SFF-303`, `TP-40372`.
- Resultado:
  - `requiere_lote=1`.
  - `requiere_caducidad=1`.
  - `requiere_serie=0`.
  - `estrategia_salida='FEFO'`.
  - `dias_alerta_caducidad=90`.
  - `dias_minimos_recepcion=30`.
- Verificacion por modelo: `Almacenes::consultar_recepcion_almacen_completa(1)` devuelve lote/caducidad activos para los 5 SKUs piloto.
- Prueba negativa backend sin escrituras:
  - Sin lote ni caducidad: responde `requiere lote`; lotes antes/despues en `REC-OC-20`: `0/0`.
  - Con lote pero sin caducidad: responde `requiere caducidad`; lotes antes/despues en `REC-OC-20`: `0/0`.

Documento de apoyo:

- `docs/erp_almacen_reglas_inventario_propuesta.md`
- `docs/erp_almacen_reglas_inventario_piloto_rec_oc_20.sql`

### ALM-H005 - Etiqueta interna no debe confundirse con serie de fabricante

Fecha: 2026-06-18  
Area: Catalogo ERP / Almacen / Inventario / Ventas  
Prioridad: Alta para diseno de trazabilidad por unidad  
Estado: Cerrado para Almacen; pendiente integracion con Ventas/Garantias

Descripcion:

Hay dos necesidades parecidas pero diferentes:

- `Serie de fabricante`: numero unico que el producto ya trae de origen.
- `Etiqueta de trazabilidad interna`: codigo/etiqueta propia para probar que una unidad fue vendida por la empresa, aunque el producto no traiga serie.

Decision recomendada:

- La configuracion debe vivir en Catalogo ERP por SKU/familia/categoria, no decidirse manualmente en Almacen.
- Almacen debe ejecutar la regla al recibir: generar/imprimir/pegar etiqueta cuando el SKU lo indique.
- Inventario debe guardar cada unidad en `erp_inventario_unidades`.
- Ventas debe asociar la unidad/codigo a la venta.
- Devoluciones/garantias debe validar el codigo para confirmar origen.

Riesgo si se mezcla:

- Activar `requiere_serie` para productos sin serie real puede bloquear recepciones.
- Usar solo etiqueta visual sin guardar unidad en inventario no sirve para comprobar venta propia.

Documento de apoyo:

- `docs/erp_almacen_reglas_inventario_propuesta.md`
- `docs/erp_etiquetas_series_trazabilidad_diseno.md`

Propuesta de diseno 2026-06-18:

- No usar `requiere_serie` como sinonimo de etiqueta interna.
- Catalogo debe separar `requiere_serie_fabricante` de `generar_etiqueta_interna`.
- `erp_inventario_unidades` debe guardar `id_sku_erp`, serie real, etiqueta interna y estado de impresion/pegado.
- Ventas y Devoluciones/Garantias deben consumir esta trazabilidad antes de activar etiqueta masiva.

Aplicacion 2026-06-18:

- Respaldo externo: `artianilocal_panel_20260619_antes_etiquetas_series_schema.sql`.
- DDL aplicado para separar serie de fabricante, etiqueta de trazabilidad interna y escaneo en venta.
- `erp_inventario_unidades` ahora conserva `id_sku_erp`, `serie_fabricante`, `codigo_etiqueta_interna`, estado de etiqueta y origen.
- FK aplicada: `fk_inv_unidad_sku`.
- Auditores `AlmacenEsquema` y `CatalogoErpEsquema` regresan `success`.
- No se activo etiqueta interna masiva en SKUs.
- Consulta tecnica de `REC-OC-20`: carga 13 partidas correctamente; primera partida `SAP-300` mantiene `requiere_codigo_unico=0`, `generar_etiqueta_individual=0`.
- Conteos posteriores: reglas con etiqueta/serie/escaneo nuevas `0`, unidades individuales `0`, lotes `5`, movimientos `5`.

Cierre Almacen 2026-06-18:

- `SCF-800` genero unidad individual `ART-00001-23-0001` desde recepcion.
- Pantalla `Almacen > Etiquetado` permite consultar y operar etiquetas.
- Estado de etiqueta validado hasta `pegada`.
- Bloqueo de duplicado validado con `ERROR 1062`.
- Queda fuera de este cierre: asociar unidad a venta, devolucion o garantia.

## Tareas siguientes

| ID | Tarea | Folio sugerido | Estado | Notas |
| --- | --- | --- | --- | --- |
| ALM-T001 | Generar respaldo externo antes de primera recepcion real | REC-OC-24 | Hecho | `artianilocal_panel_20260618_081947_antes_uat_alm_rec_oc_24.sql` |
| ALM-T002 | Ejecutar recepcion parcial menor a 5 unidades | REC-OC-24 | Hecho | Recibidas 2/5; cierra UAT-ALM-003 y UAT-COM-008 |
| ALM-T003 | Verificar lotes, movimiento, existencia y detalle de orden despues de parcial | REC-OC-24 | Hecho | Lote 18, movimiento 18, orden parcial |
| ALM-T004 | Completar unidades restantes | REC-OC-24 | Hecho | Recibidas 5/5; cierra UAT-ALM-004 y UAT-COM-009 |
| ALM-T005 | Verificar cierre de notificacion de almacen | REC-OC-24 | Hecho | Notificacion 13 quedo `resuelta`; cierra UAT-ALM-008 y UAT-NOT-006 |
| ALM-T006 | Probar no duplicidad al repetir guardado o reintento controlado | REC-OC-24 | Hecho | Reintento bloqueado sin lote/movimiento adicional |
| ALM-T007 | Reservar REC-OC-20 para prueba extendida de 13 partidas | REC-OC-20 | En uso | UAT-ALM-006 recibio 1/105 unidades; folio queda parcial |
| ALM-T008 | Validar listado de recepciones por evidencia tecnica | REC-OC-20 / REC-OC-24 | Hecho | Cierra UAT-ALM-001 como aprobado tecnico |
| ALM-T009 | Validar carga de detalle por evidencia tecnica | REC-OC-20 / REC-OC-24 | Hecho | Cierra UAT-ALM-002 como aprobado tecnico |
| ALM-T010 | Recorrer UX visual en navegador con usuario | REC-OC-20 o nuevo folio corto | Pendiente | Confirmar filtros, mensajes, colores, boton cerrado y flujo manual |
| ALM-T011 | Validar existencias/kardex desde modelo de Inventario ERP | REC-OC-24 | Hecho | `listarExistencias`, `listarMovimientos` y `buscarSkus` reflejan 5 disponibles |
| ALM-T012 | Revisar codificacion de nombres producto/SKU | REC-OC-24 | Hecho | Auditoria ampliada en ALM-H002; no bloquea UAT base |
| ALM-T013 | Auditar esquema Almacen/Inventario y tablas legacy | Almacen/Inventario | Hecho | Documento `docs/erp_almacen_inventario_auditoria_esquema.md` |
| ALM-T014 | Corregir auditor `AlmacenEsquema` para declarar `id_sku_erp` | Almacen/Inventario | Hecho | Codigo actualizado; auditor vuelve a `success`; sin DDL |
| ALM-T015 | Proponer plan de FKs/indices robustos sin ejecutar | Almacen/Inventario | Hecho | Propuesta documentada; no ejecutar sin respaldo/autorizacion |
| ALM-T016 | Auditar codificacion de textos base oficiales | Almacen/Catalogo | Hecho | Solo SELECT; 1500 productos, 418 SKUs, 1 almacen, 3 snapshots |
| ALM-T017 | Preparar plan de correccion de codificacion | Almacen/Catalogo | Hecho | Conversion candidata documentada; falta autorizacion para respaldo + preview completo |
| ALM-T018 | Generar preview completo de correccion de codificacion | Almacen/Catalogo | Hecho | `docs/erp_almacen_codificacion_preview_20260618.csv`, 2225 pares candidatos |
| ALM-T019 | Respaldar BD antes de corregir codificacion | Almacen/Catalogo | Hecho | `artianilocal_panel_20260618_almacen_codificacion_erp_almacenes.sql` |
| ALM-T020 | Corregir codificacion por bloques pequenos | Almacen/Catalogo | Hecho | `erp_almacenes`, nombres Catalogo/SKU/snapshots y descripciones HTML corregidos |
| ALM-T021 | Normalizar catalogo `erp_almacenes` | Almacen | Hecho | 3 almacenes como `activo/sucursal` con respaldo externo |
| ALM-T022 | Clasificar tablas legacy/residuales en codigo/docs | Almacen/Inventario | Hecho | Oficiales, legacy historico y residuales documentadas |
| ALM-T023 | Crear auditoria extendida de integridad | Almacen/Inventario | Hecho | Orfandades 0; tipos compatibles; indices faltantes documentados |
| ALM-T024 | Preparar DDL de FKs por bloques | Almacen/Inventario | Hecho sin ejecutar | Ver `docs/erp_almacen_inventario_ddl_indices_fks_propuesto.sql`; no hay choques de nombres detectados |
| ALM-T025 | Corregir descripciones HTML de Catalogo ERP | Catalogo | Hecho | 1497 campos actualizados con respaldo externo |
| ALM-T026 | Aplicar normalizacion de `erp_almacenes` | Almacen | Hecho | Respaldo `artianilocal_panel_20260618_almacen_normalizacion_almacenes.sql`; 3 filas actualizadas |
| ALM-T027 | Aplicar indices/FKs de integridad | Almacen/Inventario | Hecho | 5 indices y 16 FKs aplicadas con respaldo externo |
| ALM-T028 | Ejecutar pruebas visuales de Recepciones | Almacen | Hecho parcial | Listado, recepcion pendiente y recepcion cerrada validadas; guardados reales se probaron por backend/modelo |
| ALM-T029 | Auditar reglas de lote/caducidad/serie para UAT-ALM-006 | REC-OC-20 | Hecho | Hallazgo `ALM-H004`; no hay reglas activas actualmente |
| ALM-T030 | Preparar piloto de reglas para alimentos de REC-OC-20 | REC-OC-20 | Hecho | Respaldo externo y `UPDATE` controlado de 5 SKUs |
| ALM-T031 | Ejecutar UAT-ALM-006 con reglas piloto | REC-OC-20 | Hecho | Backend bloquea sin lote/caducidad y permite guardado real con lote/caducidad |
| ALM-T032 | Respaldar antes de recepcion real UAT-ALM-006 | REC-OC-20 | Hecho | `artianilocal_panel_20260619_antes_uat_alm_006_rec_oc_20.sql` |
| ALM-T033 | Verificar impacto de recepcion real UAT-ALM-006 | REC-OC-20 | Hecho | 1 lote, 1 movimiento, existencia disponible 1.0000, orden parcial |
| ALM-T034 | Validar existencias/kardex para lote con caducidad | REC-OC-20 / TP-7838 | Hecho | Cierra UAT-ALM-007 para piloto lote/caducidad |
| ALM-T035 | Cerrar partida piloto TP-7838 acumulando misma existencia | REC-OC-20 / TP-7838 | Hecho | Recibidas 4 unidades restantes; existencia 20 acumula 5.0000 |
| ALM-T036 | Recibir segundo SKU controlado con lote/caducidad | REC-OC-20 / TP-7840 | Hecho | 5 unidades recibidas; lote 22, movimiento 22, existencia 21 |
| ALM-T037 | Diseñar politica de etiqueta interna vs serie fabricante | Catalogo/Almacen | Hecho propuesta | Hallazgo `ALM-H005`; ver `docs/erp_etiquetas_series_trazabilidad_diseno.md` |
| ALM-T038 | Preparar auditoria/DDL para separar serie fabricante y etiqueta de trazabilidad | Catalogo/Almacen | Hecho | Respaldo externo y DDL aplicado; auditores en success |
| ALM-T039 | UAT piloto de etiqueta de trazabilidad | Catalogo/Almacen | Hecho | SKU `SCF-800`; unidad `ART-00001-23-0001`; flujo validado hasta `pegada` |
| ALM-T040 | Diseñar bandeja/accion de impresion de etiquetas | Almacen/Inventario | Hecho | Pantalla `Almacen > Etiquetado`; validada visualmente con unidad `Pegada` |
| ALM-T041 | Quitar decision manual de etiquetas en Recepcion | Almacen | Hecho | Recepcion muestra regla de Catalogo; no checkbox libre de etiqueta |
| ALM-T042 | Normalizar termino de etiqueta de trazabilidad | Catalogo/Almacen | Hecho | UI y docs ya no usan la marca como nombre del proceso; respaldo `artianilocal_panel_20260619_antes_terminologia_etiqueta_trazabilidad.sql` |
| ALM-T043 | Ejecutar UAT de estado de etiqueta piloto | Almacen | Hecho | `ART-00001-23-0001`: `pendiente_impresion -> impresa -> pegada`; validado visualmente como `Pegada` |
| ALM-T044 | Validar bloqueo de reimpresion/duplicado de etiqueta | Almacen/Inventario | Hecho | Modelo bloquea `pegada -> impresa`; BD bloquea codigo duplicado con `ERROR 1062` |
| ALM-T045 | Evitar auditoria doble en acciones de etiquetado | Seguridad/Almacen | Hecho | `Core` excluye acciones con auditoria explicita |
| ALM-T046 | Agregar salida imprimible de etiqueta | Almacen | Hecho tecnico | Boton `Imprimir` abre etiqueta imprimible con producto, SKU, Code128 y codigo interno |
| ALM-T047 | Agregar seleccion e impresion masiva de etiquetas | Almacen | Hecho tecnico | Seleccion multiple e impresion de tanda; marcado `impresa` solo con confirmacion |
| ALM-T048 | Conectar Recepcion con Etiquetado al guardar | Almacen | Hecho tecnico | Si se generan etiquetas, ofrece ir a `/almacen/etiquetado` filtrado por folio |
| ALM-T049 | Hacer transaccional el marcado masivo de etiquetas impresas | Almacen/Inventario | Hecho tecnico | Endpoint por lote valida todas las unidades y actualiza todo junto o nada |
| ALM-T050 | Ejecutar recepcion real controlada para etiquetas multiples | REC-OC-20 / SHF-600 | Hecho | 3 unidades generadas, impresas y pegadas por UAT visual |
| ALM-T051 | Agregar marcado masivo de etiquetas pegadas | Almacen/Inventario | Hecho tecnico | Endpoint por lote y boton `Marcar pegadas`; pendiente UAT visual con nuevas etiquetas impresas |
| ALM-T052 | Probar recepcion normal sin lote ni etiqueta | REC-OC-20 / SP-2823 | Hecho tecnico | 1 unidad recibida; 0 unidades individuales; orden sigue parcial |

## Decisiones pendientes

### ALM-D001 - Normalizacion de `erp_almacenes`

Estado: aplicado el 2026-06-18.

Los 3 almacenes tienen `estatus`, `tipo_almacen`, contacto, telefono y email en `NULL`.

Tipos recomendados para un ERP robusto:

- `principal`: bodega central o matriz; normalmente recibe compras grandes, concentra stock y puede surtir a sucursales.
- `sucursal`: punto fisico de venta/operacion con inventario propio; recibe, vende, ajusta y traspasa.
- `transito`: mercancia en camino entre almacenes o pendiente de confirmar llegada; no debe mezclarse con existencia disponible.
- `devoluciones`: mercancia devuelta por cliente/proveedor mientras se decide si vuelve a venta, se repara o se manda a merma.
- `merma`: producto danado, caducado, perdido o no vendible; debe existir para trazabilidad, pero no como stock disponible.
- `cuarentena`: producto recibido que requiere revision de calidad, caducidad, lote, conteo o autorizacion antes de estar disponible.

Recomendacion para este proyecto:

- `estatus='activo'` para los 3, porque el codigo actual ya los trata como activos con `COALESCE(estatus,'activo')`; hacerlo explicito evita ambiguedad en filtros/reportes.
- `tipo_almacen='sucursal'` para los 3, porque los nombres parecen direcciones de ubicaciones fisicas operativas. No conviene declarar uno como `principal` sin confirmar que realmente funciona como bodega central/matriz.
- Crear despues almacenes especiales separados si el flujo los necesita: `TRANSITO`, `DEVOLUCIONES`, `MERMA`, `CUARENTENA`. No mezclarlos con sucursales reales.

No aplicar automaticamente: afecta filtros, permisos futuros y reportes por tipo de almacen.

Artefacto preparado:

- `docs/erp_almacen_normalizacion_almacenes_propuesta.sql`

Valores propuestos:

| id_almacen | almacen | estatus | tipo_almacen |
| ---: | --- | --- | --- |
| 1 | Francisco Javier Mina 1105 | `activo` | `sucursal` |
| 2 | San José 1727 | `activo` | `sucursal` |
| 3 | Francisco Javier Mina 971 | `activo` | `sucursal` |

Por que no usar otros tipos ahora:

- `principal`: requiere confirmar que una direccion sea bodega central; si no, distorsiona reportes de abastecimiento.
- `transito`: no es una ubicacion fisica de venta, sino un estado/almacen tecnico para traspasos.
- `devoluciones`, `merma`, `cuarentena`: deben ser almacenes tecnicos separados, no las sucursales existentes.

Aplicacion 2026-06-18:

- Respaldo externo: `artianilocal_panel_20260618_almacen_normalizacion_almacenes.sql`.
- Filas actualizadas: 3.
- Resultado:
  - `id_almacen=1`: `activo/sucursal`.
  - `id_almacen=2`: `activo/sucursal`.
  - `id_almacen=3`: `activo/sucursal`.
- Validacion:
  - Almacenes activos: 3.
  - Conteo por tipo: `sucursal=3`.
  - Solicitudes con almacen destino invalido: 0.
  - Ordenes con almacen destino invalido: 0.

### ALM-D002 - Aplicacion de indices y llaves foraneas

Estado: aplicado el 2026-06-18.

Artefacto preparado:

- `docs/erp_almacen_inventario_ddl_indices_fks_propuesto.sql`

Incluye:

- Auditoria previa de orfandades.
- Indices de soporte faltantes.
- FKs con `ON UPDATE RESTRICT ON DELETE RESTRICT`.

Validacion previa:

- No hay constraints existentes con los nombres propuestos.
- No hay indices existentes con los nombres propuestos.

No aplicar automaticamente: el DDL hace autocommit en MySQL/MariaDB y requiere respaldo externo inmediatamente previo.

Aplicacion 2026-06-18:

- Respaldo externo: `artianilocal_panel_20260618_almacen_indices_fks.sql`.
- Auditoria inmediata previa: 0 orfandades en las relaciones criticas.
- Indices aplicados: 5.
  - `idx_recepcion_detalle_oc_detalle`.
  - `idx_recepcion_lote_almacen`.
  - `idx_existencia_almacen_clave`.
  - `idx_inventario_mov_existencia`.
  - `idx_inventario_mov_recepcion_lote`.
- FKs aplicadas: 16.
  - Recepciones: 2.
  - Detalle de recepcion: 3.
  - Lotes de recepcion: 4.
  - Existencias: 2.
  - Movimientos: 4.
  - Ubicaciones: 1.
- Politica: `ON UPDATE RESTRICT ON DELETE RESTRICT`.
- Verificacion posterior:
  - Constraints detectadas: 16.
  - Orfandades criticas revisadas: 0.
  - Conteos intactos: existencias 2, movimientos 2, lotes 2, ubicaciones 8.

Ajuste de auditor 2026-06-18:

- `AlmacenEsquema::auditarAlmacenInventario()` ahora tambien valida FKs esperadas.
- Se agregaron al contrato los 5 indices de soporte aplicados.
- Verificacion: auditor devuelve `success`, `tiene_pendientes=false`.
- Verificacion: `information_schema.TABLE_CONSTRAINTS` reporta 16 FKs esperadas.

### ALM-D003 - Piloto de reglas de inventario para lote/caducidad

Estado: aplicado y probado con recepcion real controlada.

Recomendacion:

- Activar `requiere_lote=1` y `requiere_caducidad=1` solo para los SKUs de alimento de `REC-OC-20`:
  - `TP-7838`
  - `TP-7840`
  - `SFF-03`
  - `SFF-303`
  - `TP-40372`
- Usar `dias_alerta_caducidad=90` y `dias_minimos_recepcion=30`.
- Usar `estrategia_salida='FEFO'` como objetivo ERP robusto. Si salidas aun no consumen FEFO, documentarlo como deuda tecnica y mantener `FIFO` temporal hasta completar ventas/salidas.

Por que:

- Son alimentos o producto a granel, por lo tanto requieren trazabilidad por lote y vencimiento.
- Permite aprobar `UAT-ALM-006` con un caso real y acotado, sin cambiar 593 SKUs de golpe.
- Reduce riesgo operativo: si la regla molesta o falta dato del proveedor, solo afecta 5 SKUs piloto.

No aplicar hasta tener:

- Preview antes/despues.
- Respaldo externo.
- Autorizacion explicita.
- Prueba visual posterior en `REC-OC-20`.

Artefacto preparado:

- `docs/erp_almacen_reglas_inventario_piloto_rec_oc_20.sql`

Aplicacion:

- Respaldo externo: `artianilocal_panel_20260619_almacen_reglas_piloto_rec_oc_20.sql`.
- Alcance: 5 SKUs de alimentos presentes en `REC-OC-20`.
- Resultado verificado por modelo y prueba negativa backend.
- No se recibio mercancia ni se crearon lotes/movimientos en `REC-OC-20`.

## Evidencia tecnica actual

### 2026-06-18 - Prueba visual inicial

Servidor local:

- Apache/XAMPP activo en `http://localhost/panel/`.

Ruta probada:

- `http://localhost/panel/almacen/mostrar_recepciones`

Resultado:

- La ruta protegida redirige correctamente a login.
- No se pudo inspeccionar visualmente la pantalla interna de Recepciones sin sesion autenticada.

Evidencia:

- `storage/uat/visual/almacen_login_bloqueo_20260618.png`

Siguiente requisito:

- Iniciar sesion con usuario real con permisos de almacen/inventario, o autorizar crear usuario temporal UAT.

### 2026-06-18 - Prueba visual autenticada

Servidor local:

- Apache/XAMPP activo en `http://dashboard.com.local/`.

Rutas probadas:

- `http://dashboard.com.local/almacen/mostrar_recepciones`
- `http://dashboard.com.local/almacen/recibir/1` (`REC-OC-20`)
- `http://dashboard.com.local/almacen/recibir/2` (`REC-OC-24`)

Resultado:

- Listado carga 2 recepciones:
  - `REC-OC-24`: estatus `recibida`, pendiente `0.0000`.
  - `REC-OC-20`: estatus `pendiente`, 13 partidas, pendiente `105.0000`.
- `REC-OC-20` carga pantalla de recepcion con 13 partidas y boton `Guardar recepcion`.
- `REC-OC-20` muestra `Pendiente al guardar` y etiqueta `listo por guardar`, sin implicar que ya este recibido.
- `REC-OC-24` carga como cerrada con boton `Recepcion cerrada` y etiqueta de partida `recibida`.

Evidencia:

- `storage/uat/visual/almacen_recepciones_listado_ajuste_20260618.png`
- `storage/uat/visual/almacen_recibir_rec_oc_20_pendiente_final_20260618.png`
- `storage/uat/visual/almacen_recibir_rec_oc_24_cerrada_ajuste_20260618.png`

### 2026-06-18 - UAT-ALM-006 tecnico con recepcion real

Folio:

- Recepcion: `REC-OC-20`.
- Orden: `OC-2026-000020`.

Respaldo externo:

- `artianilocal_panel_20260619_antes_uat_alm_006_rec_oc_20.sql`.

Precondicion:

- `REC-OC-20`: estatus `pendiente`.
- `OC-2026-000020`: estatus `enviada`.
- Lotes de `REC-OC-20`: 0.
- Movimientos de `REC-OC-20`: 0.
- Detalle `TP-7838`: ordenado `5.0000`, recibido `0.0000`, pendiente `5.0000`.

Pruebas negativas previas:

- Sin lote ni caducidad: backend respondio `requiere lote`; lotes antes/despues `0/0`.
- Con lote pero sin caducidad: backend respondio `requiere caducidad`; lotes antes/despues `0/0`.

Recepcion real aplicada:

- SKU: `TP-7838`.
- Cantidad: `1.0000`.
- Lote: `UAT-ALM-006-TP7838`.
- Caducidad: `2027-12-31`.
- Ubicacion: `UAT-ALM-006`.

Resultado:

- Respuesta: `Recepcion guardada correctamente`.
- Lotes creados: 1 (`id_recepcion_lote=20`).
- Movimientos creados: 1 (`id_movimiento_inventario=20`).
- Existencia creada/actualizada: `id_existencia_inventario=20`, disponible `1.0000`.
- Ubicacion creada: `id_ubicacion=9`, codigo `UAT-ALM-006`, estatus `activa`.
- Incidencias: 0.
- `REC-OC-20`: estatus `parcial`, fecha_inicio_recepcion `2026-06-18 21:01:41`.
- Detalle `TP-7838`: recibido `1.0000`, pendiente `4.0000`, estatus `parcial`.
- `OC-2026-000020`: estatus `parcial`.
- Detalle de orden `TP-7838`: cantidad_recibida `1.000000`.
- Notificacion `compra_orden_enviada_recepcion_pendiente` de orden 20: `resuelta` segun regla documentada de notificaciones para recepcion iniciada/parcial.

Decision:

- `UAT-ALM-006` queda aprobado tecnicamente para validacion backend y persistencia real de lote/caducidad.
- Evidencia visual opcional en navegador queda como mejora, no bloqueante, porque la regla critica ya fue validada por modelo y BD.
- No completar `REC-OC-20` sin nueva autorizacion, porque moveria el resto de inventario.

### 2026-06-18 - UAT-ALM-007 existencias/kardex con lote y caducidad

Folio:

- Recepcion: `REC-OC-20`.
- SKU: `TP-7838`.

Resultado:

- Existencia: `id_existencia_inventario=20`.
- Almacen: `id_almacen=3`.
- Lote: `UAT-ALM-006-TP7838`.
- Caducidad: `2027-12-31`.
- Ubicacion: `UAT-ALM-006`.
- Cantidad: `1.0000`.
- Cantidad disponible: `1.0000`.
- Estatus existencia: `disponible`.
- Movimiento: `id_movimiento_inventario=20`.
- Movimiento referencia: `REC-OC-20`.
- Movimiento cantidad: `1.0000`.
- Detalle de orden `TP-7838`: cantidad_recibida `1.000000`.

Decision:

- `UAT-ALM-007` queda aprobado tecnicamente para existencia/kardex con lote y caducidad.

### 2026-06-18 - Cierre de partida piloto TP-7838

Folio:

- Recepcion: `REC-OC-20`.
- SKU: `TP-7838`.

Respaldo externo:

- `artianilocal_panel_20260619_antes_cierre_tp7838_rec_oc_20.sql`.

Precondicion:

- Detalle `TP-7838`: recibido `1.0000`, pendiente `4.0000`, estatus `parcial`.
- Existencia `id_existencia_inventario=20`: cantidad `1.0000`, disponible `1.0000`.

Recepcion aplicada:

- Cantidad: `4.0000`.
- Lote: `UAT-ALM-006-TP7838`.
- Caducidad: `2027-12-31`.
- Ubicacion: `UAT-ALM-006`.

Resultado:

- Detalle `TP-7838`: recibido `5.0000`, pendiente `0.0000`, estatus `recibida`.
- Lote nuevo: `id_recepcion_lote=21`, cantidad `4.0000`.
- Movimiento nuevo: `id_movimiento_inventario=21`, cantidad `4.0000`.
- Existencia acumulada: `id_existencia_inventario=20`, cantidad/disponible `5.0000`.
- `REC-OC-20`: sigue `parcial`.
- `OC-2026-000020`: sigue `parcial`.
- Resumen `REC-OC-20`: 13 partidas, 105 ordenadas, 5 recibidas, 100 pendientes, 1 partida recibida, 12 pendientes.

Decision:

- El ERP acumula correctamente varias recepciones del mismo SKU/lote/caducidad/ubicacion en una sola existencia.
- No completar mas partidas sin continuar con respaldos por bloque.

### 2026-06-18 - Segundo SKU controlado TP-7840

Folio:

- Recepcion: `REC-OC-20`.
- SKU: `TP-7840`.

Respaldo externo:

- `artianilocal_panel_20260619_antes_tp7840_rec_oc_20.sql`.

Precondicion:

- Detalle `TP-7840`: recibido `0.0000`, pendiente `5.0000`, estatus `pendiente`.
- Sin lotes, movimientos ni existencias previas para `TP-7840`.

Recepcion aplicada:

- Cantidad: `5.0000`.
- Lote: `UAT-ALM-006-TP7840`.
- Caducidad: `2027-12-31`.
- Ubicacion: `UAT-ALM-006`.

Resultado:

- Detalle `TP-7840`: recibido `5.0000`, pendiente `0.0000`, estatus `recibida`.
- Lote creado: `id_recepcion_lote=22`, cantidad `5.0000`.
- Movimiento creado: `id_movimiento_inventario=22`, cantidad `5.0000`.
- Existencia creada: `id_existencia_inventario=21`, cantidad/disponible `5.0000`.
- Incidencias: 0.
- `REC-OC-20`: sigue `parcial`.
- `OC-2026-000020`: sigue `parcial`.
- Resumen `REC-OC-20`: 13 partidas, 105 ordenadas, 10 recibidas, 95 pendientes, 2 partidas recibidas, 11 pendientes.

Decision:

- Segundo SKU con lote/caducidad validado correctamente.
- Las notificaciones de recepcion pendiente ya estaban resueltas desde el inicio parcial, conforme a la regla documentada.

### 2026-06-18 - UAT-ALM-009 etiqueta de trazabilidad

Folio:

- Recepcion: `REC-OC-20`.
- Orden: `OC-2026-000020`.
- SKU: `SCF-800`.

Respaldo externo:

- `artianilocal_panel_20260619_antes_uat_alm_009_etiqueta_scf800.sql`.

Decision de piloto:

- Se eligio `SCF-800` porque solo tenia 1 unidad pendiente y es equipo/filtro, buen candidato para etiqueta de trazabilidad.
- No se uso `requiere_serie_fabricante` porque no hay serie real de proveedor.

Regla aplicada:

- `generar_etiqueta_interna=1`.
- `requiere_escaneo_venta=1`.
- `prefijo_etiqueta_interna='ART'`.
- `plantilla_etiqueta='estandar_qr'`.
- `tipo_etiqueta_seguridad='void'`.

Recepcion aplicada:

- Cantidad: `1.0000`.
- Ubicacion: `UAT-ALM-009`.
- Sin lote/caducidad.

Resultado:

- Detalle `SCF-800`: recibido `1.0000`, pendiente `0.0000`, estatus `recibida`.
- Lote/entrada: `id_recepcion_lote=23`, codigo `LOT-1-23`.
- Movimiento: `id_movimiento_inventario=23`.
- Existencia: `id_existencia_inventario=22`, disponible `1.0000`.
- Unidad individual: `id_inventario_unidad=1`.
- Codigo interno/etiqueta: `ART-00001-23-0001`.
- Estado etiqueta: `pendiente_impresion`.
- Incidencias: 0.
- `REC-OC-20`: sigue parcial, 11/105 recibidas, 94 pendientes.

Decision:

- `UAT-ALM-009` aprobado tecnicamente.
- Queda pendiente la accion visual/operativa para imprimir o marcar etiqueta como pegada.
- El checkbox libre de `Etiquetas` en Recepcion se retiro como decision operativa; la etiqueta individual se determina por Catalogo.

### 2026-06-18 - ALM-T042 normalizacion de termino

Decision:

- Termino operativo en UI: `Etiqueta de trazabilidad`.
- Termino tecnico/documental: `identificacion unitaria interna` o `etiqueta de trazabilidad interna`.
- `Artiani` no debe usarse como nombre del proceso; solo puede vivir como marca/prefijo/contenido de plantilla fisica.

Evidencia:

- Respaldo externo: `artianilocal_panel_20260619_antes_terminologia_etiqueta_trazabilidad.sql`.
- SKU piloto `SCF-800`: se mantuvo `prefijo_etiqueta_interna='ART'`.
- Instruccion actualizada a: `Pegar etiqueta de trazabilidad en zona visible antes de venta`.

### 2026-06-18 - ALM-T040 bandeja de etiquetas de trazabilidad

Objetivo:

- Tener una vista operativa para las unidades creadas en `erp_inventario_unidades`.
- Separar la recepcion de mercancia del control fisico de impresion/pegado.
- No mezclar el flujo con Ventas todavia.

Decision de ubicacion:

- Almacen opera el trabajo fisico: imprimir, pegar y confirmar etiquetas.
- Inventario conserva el dato maestro de la unidad, su existencia y trazabilidad.
- Catalogo decide si el SKU requiere etiqueta de trazabilidad.
- Ventas/Garantias consumiran el codigo despues, pero no participan en esta tarea.

Implementacion tecnica:

- Vista operativa: `Almacen > Etiquetado`.
- Ruta vista: `/almacen/etiquetado`.
- Endpoint lectura: `/almacen/etiquetas_erp`.
- Endpoint operativo: `/almacen/etiqueta_marcar_impresa_erp`.
- Endpoint operativo: `/almacen/etiqueta_marcar_pegada_erp`.
- Tabla de datos: `erp_inventario_unidades`.
- Permisos:
  - Consultar: `almacen.ver`.
  - Marcar impresa/pegada: `almacen.recibir`.
- Estados permitidos:
  - `pendiente_impresion` -> `impresa`.
  - `impresa` o `reimpresa` -> `pegada`.

Evidencia tecnica sin escritura:

- `erp_inventario_unidades`: 1 unidad en `pendiente_impresion`.
- Unidad piloto: `ART-00001-23-0001`.
- No se marco ninguna etiqueta como impresa/pegada durante esta tarea.

Verificaciones:

- `C:\xampp\php\php.exe -l app\controladores\Almacen.php`: OK.
- `C:\xampp\php\php.exe -l app\controladores\Inventario.php`: OK.
- `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\almacen\etiquetado.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\inventarios\existencias.php`: OK.
- `node --check public\assets\js\custom\apps\erp\almacen\etiquetado\etiquetado.js`: OK.
- `node --check public\assets\js\custom\apps\erp\inventarios\existencias_erp.js`: OK.

UAT visual:

- Usuario valido que `ART-00001-23-0001` aparece como `Pegada` en la pantalla de Almacen.

### 2026-06-18 - ALM-T043 UAT tecnico de estado de etiqueta

Folio/codigo:

- Recepcion: `REC-OC-20`.
- SKU: `SCF-800`.
- Unidad: `ART-00001-23-0001`.

Respaldo externo:

- `artianilocal_panel_20260619_antes_uat_alm_010_etiqueta_impresa_pegada.sql`.

Precondicion:

- `id_inventario_unidad=1`.
- `estado_etiqueta='pendiente_impresion'`.
- `fecha_impresion=NULL`.
- `fecha_etiquetado=NULL`.

Ejecucion:

- Se uso `InventarioErp::marcarEtiquetaImpresa()` para validar la transicion `pendiente_impresion -> impresa`.
- Se uso `InventarioErp::marcarEtiquetaPegada()` para validar la transicion `impresa -> pegada`.
- No se uso `UPDATE` directo para evitar saltar reglas de negocio.

Resultado:

- `estado_etiqueta='pegada'`.
- `fecha_impresion='2026-06-18 22:53:14'`.
- `fecha_etiquetado='2026-06-18 22:53:14'`.
- Conteo posterior de unidades: `pegada=1`.

Evidencia visual:

- Usuario confirma que `/almacen/etiquetado` muestra la unidad `ART-00001-23-0001` como `Pegada`.

Nota:

- UAT aprobado tecnicamente y validado visualmente en navegador.
- `impreso_por` y `etiquetado_por` quedaron `NULL` porque esta ejecucion tecnica no uso sesion web; el UAT visual con usuario real debe validar que se registre el usuario.

### 2026-06-18 - ALM-T044 bloqueo de reimpresion/duplicado

Folio/codigo:

- Recepcion: `REC-OC-20`.
- SKU: `SCF-800`.
- Unidad: `ART-00001-23-0001`.

Respaldo externo:

- `artianilocal_panel_20260619_antes_uat_alm_011_bloqueo_duplicado_etiqueta.sql`.

Prueba 1 - retroceso de estado:

- Precondicion: `estado_etiqueta='pegada'`.
- Accion: ejecutar `InventarioErp::marcarEtiquetaImpresa(id_inventario_unidad=1)`.
- Resultado: bloqueado con mensaje `Solo se puede marcar como impresa una etiqueta pendiente de impresion`.
- Estado final: sigue `pegada`.

Prueba 2 - codigo duplicado:

- Accion: intento de insertar otra unidad con `codigo_unico='ART-00001-23-0001'` y `codigo_etiqueta_interna='ART-00001-23-0001'`.
- Resultado: MySQL bloqueo con `ERROR 1062 Duplicate entry 'ART-00001-23-0001' for key 'idx_inventario_unidad_codigo'`.
- Conteo posterior:
  - Unidades con codigo `ART-00001-23-0001`: `1`.
  - Total `erp_inventario_unidades`: `1`.

Decision:

- UAT de bloqueo aprobado tecnicamente.
- La unicidad existe por `codigo_unico` y tambien hay indice unico para `codigo_etiqueta_interna`; el primer bloqueo observado fue por `idx_inventario_unidad_codigo`.

### 2026-06-18 - ALM-T045 auditoria explicita de etiquetado

Hallazgo:

- Las acciones `/almacen/etiqueta_marcar_impresa_erp` y `/almacen/etiqueta_marcar_pegada_erp` registran auditoria explicita en `Almacen.php`.
- Si no se agregan a `Core::$auditoriaExplicita`, una llamada POST autenticada tambien genera auditoria generica.

Accion:

- Se agregaron a la lista de auditoria explicita:
  - `Almacen.etiqueta_marcar_impresa_erp`.
  - `Almacen.etiqueta_marcar_pegada_erp`.

Verificacion:

- `C:\xampp\php\php.exe -l app\core\Core.php`: OK.
- `C:\xampp\php\php.exe -l app\controladores\Almacen.php`: OK.

### 2026-06-18 - ALM-T046 salida imprimible de etiqueta

Objetivo:

- No llamar `impresa` a una etiqueta si no existe una salida imprimible.
- Permitir que Almacen genere una etiqueta fisica desde `Almacen > Etiquetado`.

Implementacion:

- En cada fila de `/almacen/etiquetado` aparece accion `Imprimir`.
- La impresion abre una ventana con formato aproximado de `50mm x 30mm`.
- Contenido de etiqueta:
  - Producto.
  - SKU.
  - Codigo Code128 generado en SVG.
  - Codigo legible.
- Regla de privacidad operativa:
  - La etiqueta impresa no debe mostrar folio de recepcion ni orden de compra, porque se pega al producto y puede llegar al cliente.
  - Recepcion y orden se conservan en la pantalla operativa y en BD para trazabilidad interna.
- Si la unidad esta `pendiente_impresion`, despues de abrir impresion pregunta si debe marcarse como `impresa`.
- Si la unidad ya esta `impresa`, `reimpresa` o `pegada`, permite imprimir copia sin cambiar estado automaticamente.

Varias etiquetas de un producto:

- Cada unidad vive como una fila separada en `erp_inventario_unidades`.
- Si se reciben 5 piezas de un SKU con etiqueta, deben verse 5 filas con el mismo SKU/producto y 5 codigos distintos.
- La seleccion multiple permite imprimir varias unidades del mismo SKU/recepcion en una tanda.

Verificaciones:

- `node --check public\assets\js\custom\apps\erp\almacen\etiquetado\etiquetado.js`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\almacen\etiquetado.php`: OK.

### 2026-06-18 - ALM-T047 seleccion e impresion masiva

Objetivo:

- Evitar que Almacen imprima una por una cuando un producto llega con varias piezas etiquetables.
- Mantener la visibilidad por unidad: cada codigo sigue siendo una fila independiente.

Implementacion:

- Se agrego checkbox por fila.
- Se agrego selector de todo lo visible.
- Se agrego boton `Imprimir seleccion`.
- El resumen muestra `Seleccionadas N`.
- La ventana imprimible genera una hoja con varias etiquetas.
- Si la seleccion contiene etiquetas en `pendiente_impresion`, despues de imprimir pregunta si se marcan como `impresa`.

Regla operativa:

- Imprimir no cambia estado por si solo.
- El cambio a `impresa` requiere confirmacion del usuario.
- El cambio a `pegada` sigue separado porque pegar es una accion fisica posterior.

Verificaciones:

- `node --check public\assets\js\custom\apps\erp\almacen\etiquetado\etiquetado.js`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\almacen\etiquetado.php`: OK.

### 2026-06-18 - ALM-T048 enlace Recepcion -> Etiquetado

Objetivo:

- Evitar que Almacen termine de recibir y no sepa donde imprimir/pegar etiquetas.
- Mantener el flujo completo dentro de Almacen antes de pasar a Ventas/Garantias.

Implementacion:

- `Almacenes::guardar_recepcion_almacen()` ya devuelve `depurar.unidades`.
- Si `unidades > 0`, la UI de Recepcion muestra mensaje de exito con accion `Ir a etiquetado`.
- La accion abre `/almacen/etiquetado?q=FOLIO&estado_etiqueta=pendiente_impresion`.
- La pantalla de Etiquetado lee parametros de URL y aplica filtros iniciales.

Verificaciones:

- `node --check public\assets\js\custom\apps\erp\almacen\recibir\recibir.js`: OK.
- `node --check public\assets\js\custom\apps\erp\almacen\etiquetado\etiquetado.js`: OK.

### 2026-06-18 - ALM-T049 marcado masivo transaccional

Objetivo:

- Evitar estados parciales cuando Almacen imprime una tanda de etiquetas y confirma marcado masivo.
- Mantener una sola accion operativa auditable para la tanda.

Implementacion:

- `InventarioErp::marcarEtiquetasImpresas()` recibe una lista de unidades, bloquea registros con `FOR UPDATE`, valida que todas esten en `pendiente_impresion` o `reimpresa` y actualiza todo en una sola transaccion.
- `Almacen::etiquetas_marcar_impresas_erp()` expone la accion bajo permiso `almacen.recibir`.
- `Core` registra la accion como auditoria explicita para evitar duplicado con auditoria generica.
- La pantalla de Etiquetado envia una sola solicitud por lote.
- La ventana de impresion espera brevemente antes de llamar `print()` para reducir riesgo de imprimir antes de renderizar el codigo de barras.

Regla operativa:

- Imprimir sigue sin cambiar estado por si solo.
- El marcado masivo a `impresa` solo ocurre despues de confirmacion del usuario.
- Si una etiqueta seleccionada ya no esta pendiente, no se actualiza ninguna de la tanda.

Verificaciones:

- `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php`: OK.
- `C:\xampp\php\php.exe -l app\controladores\Almacen.php`: OK.
- `C:\xampp\php\php.exe -l app\core\Core.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\almacen\etiquetado.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\almacen\recibir.php`: OK.
- `node --check public\assets\js\custom\apps\erp\almacen\etiquetado\etiquetado.js`: OK.
- `node --check public\assets\js\custom\apps\erp\almacen\recibir\recibir.js`: OK.

### 2026-06-18 - ALM-T050 recepcion real controlada para etiquetas multiples

Folio:

- Recepcion: `REC-OC-20`.
- Orden: `OC-2026-000020`.
- SKU: `SHF-600`.

Objetivo:

- Tener varias etiquetas pendientes de un mismo producto para validar seleccion multiple, impresion por tanda y marcado masivo desde `Almacen > Etiquetado`.
- Mantener la prueba dentro de Almacen antes de pasar a Ventas/Garantias.

Respaldo externo:

- `artianilocal_panel_20260619_antes_uat_alm_012_etiquetas_shf600.sql`.

Decision de piloto:

- Se eligio `SHF-600 Filtro Cascada 600` porque tenia 3 piezas pendientes y no requiere lote/caducidad.
- Se activo etiqueta de trazabilidad en Catalogo para ese SKU:
  - `generar_etiqueta_interna=1`.
  - `requiere_escaneo_venta=1`.
  - `prefijo_etiqueta_interna='ART'`.
  - `plantilla_etiqueta='estandar_code128'`.
  - `tipo_etiqueta_seguridad='void'`.
  - `instrucciones_etiquetado='Pegar etiqueta de trazabilidad en zona visible antes de venta'`.

Recepcion real aplicada:

- Script de evidencia: `storage/uat/uat_alm_012_recibir_shf600.php`.
- Cantidad recibida: `3.0000`.
- Ubicacion: `UAT-ALM-012`.
- Observacion: `UAT-ALM-012 etiquetas multiples SHF-600`.

Resultado:

- Respuesta del modelo: `Recepcion guardada correctamente`.
- Lotes creados: 1.
- Movimientos creados: 1.
- Unidades generadas: 3.
- Incidencias: 0.
- Detalle `SHF-600`: recibido `3.0000`, pendiente `0.0000`, estatus `recibida`.
- Movimiento: `id_movimiento_inventario=24`, referencia `REC-OC-20`, cantidad `3.0000`.
- Existencia: `id_existencia_inventario=23`, codigo `EXI-290-23`, cantidad/disponible `3.0000`.
- `REC-OC-20`: sigue `parcial`, recibido `14.0000`, pendiente `91.0000`.

Etiquetas generadas:

- `ART-00001-24-0001`, estado `pendiente_impresion`.
- `ART-00001-24-0002`, estado `pendiente_impresion`.
- `ART-00001-24-0003`, estado `pendiente_impresion`.

Verificaciones:

- `C:\xampp\php\php.exe -l storage\uat\uat_alm_012_recibir_shf600.php`: OK.
- Consultas de detalle, existencia, movimiento y unidades confirmadas por BD.

UAT visual:

- Usuario abrio `/almacen/etiquetado?q=REC-OC-20&estado_etiqueta=pendiente_impresion`.
- Usuario valido visualmente la impresion por seleccion y marco las tres etiquetas como `impresa`.

Evidencia posterior:

- `ART-00001-24-0001`: `impresa`, `fecha_impresion=2026-06-18 23:48:09`, `impreso_por=1`.
- `ART-00001-24-0002`: `impresa`, `fecha_impresion=2026-06-18 23:48:09`, `impreso_por=1`.
- `ART-00001-24-0003`: `impresa`, `fecha_impresion=2026-06-18 23:48:09`, `impreso_por=1`.
- Conteo por lote de recepcion `24`: `impresa=3`.
- Duplicados para esos tres codigos: 0.
- Auditoria: `sys_auditoria_eventos.id_auditoria_evento=1405`, accion `etiquetas_marcar_impresas_erp`, resultado `ok`.
- `REC-OC-20`: sigue `parcial`, recibido `14.0000`, pendiente `91.0000`.
- `OC-2026-000020`: sigue `parcial`; detalle `SHF-600` recibido `3.000000` de `3.000000`.

UAT visual de pegado:

- Usuario marco las tres etiquetas como `pegada` desde `Almacen > Etiquetado`.
- `ART-00001-24-0001`: `pegada`, `fecha_etiquetado=2026-06-18 23:53:10`, `etiquetado_por=1`.
- `ART-00001-24-0002`: `pegada`, `fecha_etiquetado=2026-06-18 23:53:19`, `etiquetado_por=1`.
- `ART-00001-24-0003`: `pegada`, `fecha_etiquetado=2026-06-18 23:53:25`, `etiquetado_por=1`.
- Conteo por lote de recepcion `24`: `pegada=3`.
- Auditorias:
  - `sys_auditoria_eventos.id_auditoria_evento=1406`, unidad `3`, resultado `ok`.
  - `sys_auditoria_eventos.id_auditoria_evento=1407`, unidad `4`, resultado `ok`.
  - `sys_auditoria_eventos.id_auditoria_evento=1408`, unidad `5`, resultado `ok`.
- `REC-OC-20`: sigue `parcial`, recibido `14.0000`, pendiente `91.0000`.
- `OC-2026-000020`: sigue `parcial`; detalle `SHF-600` recibido `3.000000` de `3.000000`.

Decision:

- UAT de recepcion + impresion + pegado de multiples etiquetas queda aprobado para Almacen.
- Integracion posterior con Ventas/Garantias queda fuera de este cierre de Almacen.

### 2026-06-19 - ALM-T051 marcado masivo de etiquetas pegadas

Objetivo:

- Evitar que Almacen tenga que marcar una por una cuando ya pego fisicamente una tanda de etiquetas.
- Mantener la misma regla transaccional usada para marcado masivo de `impresa`.

Implementacion:

- `InventarioErp::marcarEtiquetasPegadas()` expone marcado masivo a `pegada`.
- `InventarioErp::actualizarEstadoEtiquetas()` valida toda la tanda con `FOR UPDATE` y actualiza todo junto o nada.
- `Almacen::etiquetas_marcar_pegadas_erp()` expone la accion con permiso `almacen.recibir`.
- `Core` registra la accion como auditoria explicita para evitar auditoria doble.
- La pantalla `Almacen > Etiquetado` agrega boton `Marcar pegadas` para la seleccion.

Regla operativa:

- Solo se pueden marcar masivamente como `pegada` etiquetas en `impresa` o `reimpresa`.
- Si una etiqueta seleccionada no esta lista, no se actualiza ninguna de la tanda.

Verificaciones:

- `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php`: OK.
- `C:\xampp\php\php.exe -l app\controladores\Almacen.php`: OK.
- `C:\xampp\php\php.exe -l app\core\Core.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\almacen\etiquetado.php`: OK.
- `node --check public\assets\js\custom\apps\erp\almacen\etiquetado\etiquetado.js`: OK.

Pendiente:

- UAT visual con una nueva tanda en estado `impresa`. No se forzo con datos actuales porque todas las etiquetas reales ya estan `pegada`.

### 2026-06-19 - ALM-T052 recepcion normal sin lote ni etiqueta

Folio:

- Recepcion: `REC-OC-20`.
- Orden: `OC-2026-000020`.
- SKU: `SP-2823`.

Objetivo:

- Confirmar que las nuevas reglas de lote, caducidad y etiquetas no rompen la recepcion simple de un SKU sin controles especiales.

Respaldo externo:

- `artianilocal_panel_20260619_antes_uat_alm_013_recepcion_normal_sp2823.sql`.

Precondicion:

- `SP-2823`: pendiente `5.0000`.
- Reglas del SKU: `requiere_lote=0`, `requiere_caducidad=0`, `generar_etiqueta_interna=0`.

Recepcion real aplicada:

- Script de evidencia: `storage/uat/uat_alm_013_recibir_sp2823_normal.php`.
- Cantidad recibida: `1.0000`.
- Ubicacion: `UAT-ALM-013`.
- Observacion: `UAT-ALM-013 recepcion normal sin lote ni etiqueta`.

Resultado:

- Respuesta del modelo: `Recepcion guardada correctamente`.
- Lotes/entradas creadas: 1.
- Movimientos creados: 1.
- Unidades individuales generadas: 0.
- Incidencias: 0.
- Detalle `SP-2823`: recibido `1.0000`, pendiente `4.0000`, estatus `parcial`.
- Movimiento: `id_movimiento_inventario=25`, referencia `REC-OC-20`, cantidad `1.0000`.
- Existencia: `id_existencia_inventario=24`, codigo `EXI-1042-24`, cantidad/disponible `1.0000`, sin lote ni caducidad.
- Unidades en `erp_inventario_unidades` para `SP-2823`: 0.
- `REC-OC-20`: sigue `parcial`, recibido `15.0000`, pendiente `90.0000`.
- `OC-2026-000020`: sigue `parcial`; detalle `SP-2823` recibido `1.000000` de `5.000000`.

Verificaciones:

- `C:\xampp\php\php.exe -l storage\uat\uat_alm_013_recibir_sp2823_normal.php`: OK.
- Consultas de detalle, existencia, movimiento, unidades y orden confirmadas por BD.

### 2026-06-19 - Auditoria de pendientes de Almacen

Resumen actual de `REC-OC-20`:

- Total ordenado: `105.0000`.
- Recibido: `15.0000`.
- Pendiente: `90.0000`.
- Partidas recibidas: 4.
- Partidas parciales: 1.
- Partidas pendientes: 8.

Conteos operativos:

- `erp_almacen_recepciones`: 2.
- `erp_almacen_recepciones_detalle`: 14.
- `erp_almacen_recepciones_lotes`: 8.
- Movimientos de recepcion: 8.
- `erp_inventario_existencias`: 7.
- `erp_inventario_unidades`: 4.
- Etiquetas por estado: `pegada=4`.

Pendientes reales antes de cerrar Almacen:

- UAT visual opcional de `Marcar pegadas` masivo cuando exista una tanda en `impresa`.
- Decidir si `REC-OC-20` seguira como folio de pruebas o se deja congelado para no mover mas inventario.
- Doble submit simultaneo queda como riesgo bajo/futuro de concurrencia, no bloqueante del cierre funcional.

Pendientes fuera del cierre de Almacen:

- Integrar escaneo/validacion de etiqueta en Ventas.
- Integrar etiqueta con Devoluciones/Garantias.
- Definir activacion masiva de reglas de lote/caducidad/etiqueta por familias/categorias desde Catalogo.

### 2026-06-18 - Estado posterior a saneamiento de codificacion

Conteos operativos:

- `erp_almacen_recepciones`: 2.
- `erp_almacen_recepciones_detalle`: 14.
- `erp_almacen_recepciones_lotes`: 8 al 2026-06-19 despues de UAT adicionales.
- `erp_inventario_existencias`: 7 al 2026-06-19 despues de UAT adicionales.
- Movimientos de recepcion: 8 al 2026-06-19 despues de UAT adicionales.

Orfandades criticas revisadas:

- Recepciones sin orden: 0.
- Detalle sin recepcion: 0.
- Lotes sin detalle: 0.
- Existencias con SKU inexistente: 0.
- Movimientos sin existencia: 0.

Verificacion de codigo:

- `php -l app\modelos\AlmacenEsquema.php`: OK.

## Mejoras aplicadas

### 2026-06-27 - ALM-REC-VAR-001 recepcion con cantidad real variable

Objetivo:

- Preparar `Almacen > Recepciones` para SKUs donde Compras indica unidades fisicas esperadas, pero Almacen debe capturar la cantidad real recibida en unidad base de inventario.
- Evitar interpretar casos operativos como `paca`, `costal`, `saco` o `bolsa` como unidades formales obligatorias del ERP.

Decision operativa:

- La unidad formal sigue viniendo de Catalogo (`KG`, `L`, `PZA`, `M`, etc.).
- Catalogo define si el SKU requiere `cantidad real variable en recepcion`.
- Recepcion captura:
  - `cantidad`: unidades fisicas/compra recibidas para cerrar pendiente contra la orden.
  - `cantidad_base_real`: cantidad real recibida en unidad base para inventario.
- Inventario recibe siempre la cantidad base real cuando el SKU es variable.
- El factor de proveedor queda como referencia/contrato normal para SKUs no variables, pero no debe sustituir el peso/contenido real capturado cuando el SKU exige recepcion variable.

Auditoria tecnica:

- `erp_almacen_recepciones_detalle` ya conserva `cantidad_recibida_base`, `cantidad_pendiente_base`, `unidad_base` y `factor_conversion`.
- `erp_almacen_recepciones_lotes` ya conserva `cantidad_compra`, `cantidad_base`, `unidad_compra`, `unidad_base` y `factor_conversion`.
- `erp_inventario_movimientos` e `erp_inventario_existencias` ya usan la cantidad base generada desde Recepcion.
- `erp_inventario_unidades` ya puede guardar `cantidad_base_original`, `cantidad_base_disponible` y `unidad_base`.
- La BD local aun no tiene las columnas de reglas variables en `erp_catalogo_sku_reglas_inventario`; consulta read-only confirmada el 2026-06-27: 0 columnas presentes.
- `CatalogoErpEsquema` ya declara las columnas y el indice `idx_catalogo_regla_recepcion_variable`; no hace falta inventar otro mecanismo de esquema.
- Precision disponible:
  - `erp_almacen_recepciones_detalle.cantidad_*_base`: `DECIMAL(18,6)`.
  - `erp_almacen_recepciones_lotes.cantidad_base`: `DECIMAL(18,6)`.
  - `erp_inventario_unidades.cantidad_base_original/disponible`: `DECIMAL(18,6)`.
- Pendientes actuales de `REC-OC-20` no son buen piloto para recepcion variable porque son principalmente `pza`; se recomienda generar o usar una recepcion controlada con SKU en `kg` o `L`.

Cambios aplicados sin migracion:

- `app/modelos/Almacenes.php`
  - Lee reglas variables solo si las columnas existen.
  - Si no existen, devuelve `requiere_cantidad_variable_recepcion=0` y el flujo normal sigue igual.
  - Para SKUs variables, `partida_recepcion_con_conversion()` exige `cantidad_base_real > 0` y usa ese valor para lote, existencia, kardex y unidades.
  - Para SKUs con unidades fisicas requeridas, bloquea cantidades fisicas decimales.
  - Para SKUs variables, no bloquea por factor de compra faltante, porque la entrada real se captura en unidad base.
- `public/assets/js/custom/apps/erp/almacen/recibir/recibir.js`
  - Muestra badge `Cantidad real` cuando el SKU venga configurado como variable.
  - Muestra campo `Cantidad real {unidad_base}` solo para esos SKUs.
  - Envia `cantidad_base_real` al backend.
  - Valida que la cantidad real sea mayor a 0.
- `app/vistas/paginas/apps/erp/almacen/recibir.php`
  - Versiona el JS a `20260627-var1`.

DDL pendiente de autorizacion:

```sql
ALTER TABLE erp_catalogo_sku_reglas_inventario
  ADD COLUMN requiere_cantidad_variable_recepcion TINYINT(1) NOT NULL DEFAULT 0 AFTER dias_minimos_recepcion,
  ADD COLUMN requiere_unidades_fisicas_recepcion TINYINT(1) NOT NULL DEFAULT 0 AFTER requiere_cantidad_variable_recepcion,
  ADD COLUMN tolerancia_recepcion_porcentaje DECIMAL(9,4) NULL AFTER requiere_unidades_fisicas_recepcion,
  ADD COLUMN nota_recepcion_variable VARCHAR(255) NULL AFTER tolerancia_recepcion_porcentaje;

ALTER TABLE erp_catalogo_sku_reglas_inventario
  ADD KEY idx_catalogo_regla_recepcion_variable (requiere_cantidad_variable_recepcion);
```

Reglas de validacion esperadas despues del DDL:

- Si `requiere_cantidad_variable_recepcion=1`, Recepcion no permite guardar sin `cantidad_base_real`.
- `cantidad_base_real` debe ser mayor a 0.
- `unidad_base` debe existir.
- Si `requiere_unidades_fisicas_recepcion=1`, `cantidad` debe ser entera.
- La diferencia entre cantidad esperada y cantidad real se conserva como dato operativo, no como error automatico.
- Si el SKU genera etiqueta interna, las unidades generadas reparten el contenido base entre las unidades fisicas capturadas; el desglose por unidad fisica queda como mejora futura si se necesita pesar una por una.
- Si se necesita capturar peso por unidad fisica desde esta primera fase, el operador puede capturar varias lineas/lotes del mismo SKU con `cantidad=1` y `cantidad_base_real` distinto en cada linea; asi cada unidad etiquetada queda con su contenido real. Una captura total unica reparte el contenido entre las unidades.

Verificaciones:

- `C:\xampp\php\php.exe -l app\modelos\Almacenes.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\almacen\recibir.php`: OK.
- `node --check public\assets\js\custom\apps\erp\almacen\recibir\recibir.js`: OK.
- Consulta read-only de `REC-OC-25` por modelo: `error=false`, `detalle=1`, `requiere_cantidad_variable_recepcion=0`.

Pendiente:

- Antes de UAT real de recepcion variable: respaldo externo, aplicar DDL autorizado, configurar un SKU piloto en Catalogo y crear/usar una recepcion controlada.
- Si se requiere capturar peso por cada unidad fisica individual y no solo total real, proponer estructura adicional o payload JSON para desglose; no implementado todavia para evitar inventar esquema sin autorizacion.

Aplicacion de esquema 2026-06-27:

- Autorizacion recibida en conversacion: continuar.
- Respaldo externo previo:
  - `storage/backups/artianilocal_pre_alm_rec_var_ddl_20260627_210626.sql`.
- DDL aplicado:
  - `requiere_cantidad_variable_recepcion`.
  - `requiere_unidades_fisicas_recepcion`.
  - `tolerancia_recepcion_porcentaje`.
  - `nota_recepcion_variable`.
  - indice `idx_catalogo_regla_recepcion_variable`.
- Verificacion posterior:
  - Las 4 columnas existen con tipos esperados.
  - El indice existe.
  - `erp_catalogo_sku_reglas_inventario`: `1750` reglas siguen en `0/0`, por lo que no cambio el comportamiento de ningun SKU.
  - `REC-OC-25` consulta correctamente por modelo y devuelve `requiere_cantidad_variable_recepcion=0`.

Candidatos read-only para UAT piloto:

| SKU | Unidad base | Proveedor activo | Lote/caducidad | Etiqueta | Nota |
| --- | --- | ---: | --- | --- | --- |
| `TP-40311` | `kg` | 1 | Si | Si | Mejor candidato tecnico por tener proveedor, kg y etiqueta; requiere autorizacion antes de activar regla. |
| `TP-40372` | `kg` | 1 | Si | No | Ya fue usado en pruebas de granel/preparacion; evitar moverlo sin definir folio controlado. |
| `TP-40352` | `kg` | 0 | No | No | No ideal para UAT de recepcion desde proveedor porque no tiene relacion activa. |

Revision de candidato operativo 2026-06-27:

- SKU indicado por el usuario: `ALIHA-01`.
- Nombre: `Heno de alfalfa natural por paca`.
- Unidad base: `kg`.
- `factor_unidad_base=18.000000` como referencia operativa actual.
- Reglas actuales:
  - `controla_inventario=1`.
  - `requiere_cantidad_variable_recepcion=1`.
  - `requiere_unidades_fisicas_recepcion=1`.
  - `generar_etiqueta_interna=1`.
  - `requiere_lote=0`.
  - `requiere_caducidad=0`.
- No tiene relacion proveedor activa, recepciones ni existencias al momento de la auditoria.

Pruebas dry-run sin escritura:

- Caso positivo:
  - Entrada simulada: `cantidad=5` unidades fisicas, `cantidad_base_real=102.75 kg`.
  - Resultado esperado: inventario recibiria `102.75 kg`.
  - Resultado observado en `Almacenes::partida_recepcion_con_conversion()`: `_cantidad_compra=5`, `_cantidad_base=102.75`, `_unidad_base=kg`.
- Caso negativo sin cantidad real:
  - Entrada simulada: `cantidad=5`, `cantidad_base_real=0`.
  - Resultado: bloqueado con `Captura la cantidad real recibida de Heno de alfalfa natural por paca`.
- Caso negativo con unidades fisicas decimales:
  - Entrada simulada: `cantidad=1.5`, `cantidad_base_real=30`.
  - Resultado: bloqueado con `requiere capturar unidades fisicas enteras`.

Conclusion:

- `ALIHA-01` es buen candidato operativo para UAT de recepcion variable.
- Para una prueba real falta una OC/recepcion controlada o crear una relacion proveedor/orden si se quiere probar desde el flujo completo de Compras -> Almacen.

Siguiente autorizacion requerida:

- Crear o usar una relacion proveedor/OC/recepcion controlada para `ALIHA-01`, porque no hay folio existente para probar desde la pantalla completa.
- Respaldar antes de cualquier escritura de UAT.
- Ejecutar recepcion controlada:
  - Compra/recepcion: unidades fisicas esperadas.
  - Recepcion: cantidad real total en `kg`.
  - Verificar existencia, movimiento, lote/entrada y etiquetas generadas.

### 2026-06-18 - UX de recepcion cerrada

Archivo:

- `public/assets/js/custom/apps/erp/almacen/recibir/recibir.js`

Cambio:

- El badge de estatus en la pantalla de recepcion ahora cambia color segun `pendiente`, `parcial`, `recibida` o `cancelada`.
- Cuando la recepcion esta `recibida` o `cancelada`, el boton deshabilitado muestra `Recepcion cerrada` en lugar de sugerir que todavia se puede guardar.

Verificacion:

- `node --check public\assets\js\custom\apps\erp\almacen\recibir\recibir.js`

## Consultas base para proxima evidencia

```sql
SELECT id_orden_compra, folio, estatus
FROM erp_compras_ordenes
WHERE folio IN ('OC-2026-000024','OC-2026-000020');
```

```sql
SELECT id_recepcion_almacen, folio, id_orden_compra, estatus,
       fecha_inicio_recepcion, fecha_cierre_recepcion
FROM erp_almacen_recepciones
WHERE folio IN ('REC-OC-24','REC-OC-20');
```

```sql
SELECT rd.id_recepcion_detalle, rd.id_orden_compra_detalle, rd.id_sku_erp,
       rd.sku, rd.nombre_producto, rd.cantidad_ordenada,
       rd.cantidad_recibida, rd.cantidad_pendiente, rd.estatus
FROM erp_almacen_recepciones_detalle rd
INNER JOIN erp_almacen_recepciones r
  ON r.id_recepcion_almacen = rd.id_recepcion_almacen
WHERE r.folio = 'REC-OC-24'
ORDER BY rd.id_recepcion_detalle;
```

```sql
SELECT r.folio, COUNT(l.id_recepcion_lote) lotes, COALESCE(SUM(l.cantidad),0) cantidad_lotes
FROM erp_almacen_recepciones r
LEFT JOIN erp_almacen_recepciones_detalle rd
  ON rd.id_recepcion_almacen=r.id_recepcion_almacen
LEFT JOIN erp_almacen_recepciones_lotes l
  ON l.id_recepcion_detalle=rd.id_recepcion_detalle
WHERE r.folio='REC-OC-24'
GROUP BY r.folio;
```

```sql
SELECT r.folio, COUNT(m.id_movimiento_inventario) movimientos,
       COALESCE(SUM(m.cantidad),0) cantidad_movimientos
FROM erp_almacen_recepciones r
LEFT JOIN erp_almacen_recepciones_detalle rd
  ON rd.id_recepcion_almacen=r.id_recepcion_almacen
LEFT JOIN erp_inventario_movimientos m
  ON m.origen_tipo='recepcion_compra'
 AND m.origen_id=r.id_recepcion_almacen
 AND m.origen_detalle_id=rd.id_recepcion_detalle
WHERE r.folio='REC-OC-24'
GROUP BY r.folio;
```

```sql
SELECT id_notificacion, tipo, area_responsable, permiso_requerido,
       prioridad, estatus, id_entidad_origen, fecha_resolucion
FROM erp_notificaciones
WHERE modulo_origen='compras'
  AND entidad_origen='erp_compras_ordenes'
  AND id_entidad_origen IN (20,24)
ORDER BY id_notificacion;
```
