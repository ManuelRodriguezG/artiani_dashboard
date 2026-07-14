# ERP Almacen - Resurtido y traspasos tareas vivas

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-11  
Modulo: ERP > Almacen > Resurtido / Traspasos entre tiendas  
Estado: primeras tareas definidas; sin escrituras de BD.

## Regla de trabajo

- Tareas pequenas.
- UAT por folio/SKU/almacen.
- Hallazgos con ID `RES-H###`.
- No tocar Ventas/POS/ecommerce.
- No ejecutar migraciones ni escrituras de BD sin respaldo externo y autorizacion.
- No mezclar ERP nuevo con legacy.
- Documentar handoff futuro cuando algo afecte POS/ecommerce.

## Documentos relacionados

- `docs/erp_almacen_resurtido_traspasos_arranque.md`
- `docs/erp_almacen_resurtido_traspasos_schema_propuesta.sql`
- `docs/erp_almacen_resurtido_paquete_autorizacion_uat.md`
- `docs/erp_almacen_resurtido_handoff_pre_ddl.md`
- `docs/erp_almacen_sucursales_almacenes_arranque.md`
- `docs/erp_inventario_existencias_arranque.md`
- `docs/erp_almacen_unidades_fisicas_arranque.md`
- `docs/erp_inventario_existencias_unidades_abiertas_handoff.md`
- `docs/erp_notificaciones_alertas_trabajo.md`

## Bloques de implementacion

| ID | Tarea | Resultado esperado | Autorizacion |
| --- | --- | --- | --- |
| `RES-T001` | Documentar auditoria inicial | Arranque con hallazgos, decision y UAT minimo | No |
| `RES-T002` | Preparar DDL propuesto | SQL revisable sin ejecutar | No |
| `RES-T003` | Definir estados/permisos | Contrato operativo antes de codigo | No |
| `RES-T004` | Preflight stock bajo por tienda/SKU | Consulta read-only de necesidades | No |
| `RES-T005` | Diseno UX de listado/captura | Pantallas y acciones esperadas | No |
| `RES-T006` | Integrar auditoria de esquema en plan | `AlmacenEsquema` detecta faltantes sin ejecutar DDL | No para codigo; si se ejecuta plan write, si |
| `RES-T007` | Aplicar DDL | Tablas reales de resurtido | Si, con respaldo externo |
| `RES-T007A` | Pantalla read-only antes de DDL | Menu, vista y endpoints GET sin escritura | No |
| `RES-T007B` | Detalle read-only por folio | Consulta documental completa sin escritura | No |
| `RES-T007C` | UAT read-only del modulo | Prueba repetible antes/despues de DDL | No |
| `RES-T007D` | Script apply autorizado especifico | DDL resurtido bloqueado por token/respaldo | No para crear script; si se ejecuta, si |
| `RES-T007E` | Simulacion read-only de solicitud | Preview de solicitud desde stock bajo | No |
| `RES-T007F` | Cobertura origen read-only | Valida si bodega puede surtir preview | No |
| `RES-T007G` | Origen manual en simulacion | Comparar bodegas/origen sin guardar | No |
| `RES-T007H` | Resumen multi-tienda read-only | Comparar necesidades Acuario/Mascotas | No |
| `RES-T007I` | Validacion solicitud read-only | Bloqueos/advertencias previos a guardado | No |
| `RES-T007J` | Payload RES-T008 read-only | Contrato de POST futuro sin guardar | No |
| `RES-T007K` | UAT contrato payload | Valida campos obligatorios del POST futuro | No |
| `RES-T007L` | UAT estatico SQL DDL | Valida tablas/columnas/constraints y ausencia de SQL destructivo | No |
| `RES-T008` | Backend solicitud/resurtido | Crear/listar/consultar/autorizacion sin mover inventario | Si para UAT con escritura |
| `RES-T008B` | Backend autorizar/rechazar post-DDL | Endpoint/modelo listo post-DDL, bloqueado si falta esquema | No |
| `RES-T008C` | Backend cancelar post-DDL | Endpoint/modelo listo post-DDL, bloqueado si falta esquema | No |
| `RES-T008D` | UAT read-only robusto sin conexion | Reporta entorno caido sin fatal | No |
| `RES-T008E` | Contrato acciones read-only | UI/backend lista acciones futuras sin POST | No |
| `RES-T008F` | Arnes autorizar/cancelar autorizado | Token/respaldo; modifica folios solo post-DDL | No |
| `RES-T009A` | Contrato estados read-only | Endpoint/UI/UAT de estados y transiciones | No |
| `RES-T009B` | Contrato preparacion/envio read-only | Endpoint/UI/UAT de trazabilidad y movimientos esperados | No |
| `RES-T009C` | Preflight folio preparacion/envio | Validar folio antes de preparar/enviar sin escritura | No |
| `RES-T009D` | Arnes preparar/enviar autorizado | Token/respaldo sin implementar movimientos aun | No |
| `RES-T009E` | Backend pendiente preparar/enviar | Endpoint/modelo bloqueado por esquema/implementacion | No |
| `RES-T009F` | Plan preparacion read-only | FEFO por existencia/lote/unidad sin apartar stock | No |
| `RES-T009G` | Payload preparacion/envio read-only | Contrato POST futuro desde plan FEFO | No |
| `RES-T009` | Backend preparacion/envio | Salida origen + transito con trazabilidad | Si, con respaldo externo |
| `RES-T010A` | Contrato recepcion/diferencias read-only | Endpoint/UI/UAT de comparacion enviado vs recibido | No |
| `RES-T010B` | Preflight folio recepcion/diferencias | Validar folio enviado antes de recibir sin escritura | No |
| `RES-T010C` | Arnes recibir autorizado | Token/respaldo sin implementar recepcion aun | No |
| `RES-T010D` | Backend pendiente recibir | Endpoint/modelo bloqueado por esquema/implementacion | No |
| `RES-T010` | Backend recepcion/diferencias | Recepcion tienda y diferencias persistentes | Si, con respaldo externo |
| `RES-T011A` | Contrato politicas tienda/SKU read-only | Min/max/reorden sin crear politicas | No |
| `RES-T011B` | Backend politicas tienda/SKU post-DDL | Upsert listo post-DDL, bloqueado si falta esquema | No |
| `RES-T011C` | Arnes politica autorizado | Token/respaldo; guarda reglas solo post-DDL | No |
| `RES-T012A` | Contrato alertas stock bajo read-only | Eventos futuros sin crear notificaciones | No |
| `RES-T013A` | Paquete autorizacion/UAT | Preflight y secuencia para DDL + primer folio | No |
| `RES-T013B` | Handoff pre-DDL | Estado consolidado antes de autorizacion de esquema | No |
| `RES-T011` | UI operativa | Pantalla Almacen > Resurtido | No para codigo; si UAT real mueve stock, si |
| `RES-T012` | Alertas stock bajo | Integracion con Notificaciones | Si hay semillas o datos |
| `RES-T013` | UAT cierre modulo | Casos `RES-UAT-*` documentados | Si para movimientos reales |

## Estado actual

- `RES-T001`: completada.
- `RES-T002`: completada como propuesta documental.
- `RES-T003`: completada en contrato inicial de estados, transiciones, permisos candidatos y auditoria.
- `RES-T004`: completada primera version read-only con reglas globales de Catalogo como fallback.
- `RES-T005`: completada en diseno UX operativo inicial.
- `RES-T006`: completada en auditoria/plan dry-run, sin ejecutar DDL.
- `RES-T007`: paquete de autorizacion preparado; aplicacion bloqueada hasta respaldo externo y autorizacion textual.
- `RES-T007A`: completada pantalla inicial read-only de Almacen > Resurtido.
- `RES-T007B`: completada consulta read-only de folio y trazabilidad documental.
- `RES-T007C`: completada prueba UAT read-only del modulo.
- `RES-T007D`: completada preparacion de script apply autorizado; no ejecutado.
- `RES-T007E`: completada simulacion read-only de solicitud desde stock bajo.
- `RES-T007F`: completada cobertura read-only del almacen origen.
- `RES-T007G`: completada seleccion manual de origen en simulacion.
- `RES-T007H`: completado resumen multi-tienda read-only.
- `RES-T007I`: completada validacion read-only de solicitud.
- `RES-T007J`: completado payload read-only para POST futuro.
- `RES-T007K`: completada validacion de contrato payload en UAT.
- `RES-T007L`: completada validacion estatica del SQL propuesto sin conectar BD.
- `RES-T008`: iniciado como endpoint POST bloqueado por esquema pendiente; escritura real bloqueada hasta DDL/respaldo.
- `RES-T008B`: completado backend para autorizar/rechazar; listo post-DDL, sin movimientos.
- `RES-T008C`: completado backend para cancelar; listo post-DDL, sin movimientos.
- `RES-T008D`: completado UAT read-only robusto ante MySQL no disponible.
- `RES-T008E`: completado contrato read-only de acciones futuras en backend/UI/UAT.
- `RES-T008F`: completado arnes autorizado para autorizar/cancelar; modifica folios solo post-DDL y con respaldo.
- `RES-T009A`: completado contrato read-only de estados/transiciones para backend, UI y UAT.
- `RES-T009B`: completado contrato read-only de preparacion/envio para backend, UI y UAT.
- `RES-T009C`: completado preflight read-only por folio antes de preparacion/envio.
- `RES-T009D`: completado arnes autorizado bloqueado para futura preparacion/envio.
- `RES-T009E`: completado backend pendiente para preparar/enviar, sin movimientos.
- `RES-T009F`: completado plan read-only de preparacion FEFO por existencia/lote/unidad.
- `RES-T009G`: completado payload read-only de preparacion/envio desde plan FEFO.
- `RES-T010A`: completado contrato read-only de recepcion/diferencias para backend, UI y UAT.
- `RES-T010B`: completado preflight read-only por folio antes de recepcion/diferencias.
- `RES-T010C`: completado arnes autorizado bloqueado para futura recepcion.
- `RES-T010D`: completado backend pendiente para recibir, sin movimientos.
- `RES-T011A`: completado contrato read-only de politicas tienda/SKU.
- `RES-T011B`: completado backend para politicas tienda/SKU; upsert listo post-DDL, sin alertas ni movimientos.
- `RES-T011C`: completado arnes autorizado para politicas tienda/SKU; guarda reglas solo post-DDL y con respaldo.
- `RES-T012A`: completado contrato read-only de alertas futuras de stock bajo.
- `RES-T013A`: completado paquete de autorizacion/UAT read-only para DDL y primer folio controlado.
- `RES-T013B`: completado handoff pre-DDL consolidado.
- `RES-T009` en adelante: bloqueadas hasta autorizacion de DDL/respaldo y cierre de solicitud real.

## RES-T007A - Pantalla read-only antes de DDL

Fecha: 2026-07-11

Objetivo:

- Permitir revisar el modulo desde el menu sin crear datos.
- Exponer preflight de stock bajo por tienda/SKU usando el endpoint read-only ya validado.
- Dejar visible que la creacion de solicitudes queda bloqueada hasta aplicar esquema con respaldo externo.

Implementado:

- Vista: `app/vistas/paginas/apps/erp/almacen/resurtido.php`.
- JS: `public/assets/js/custom/apps/erp/almacen/resurtido/resurtido.js`.
- Menu: `Almacen > Resurtido`.
- Controlador:
  - `Almacen::resurtido()`
  - `Almacen::resurtido_listar_erp()`
- Modelo:
  - `Almacenes::consultar_resurtidos_readonly()`
  - `Almacenes::tablaExisteAlmacen()`

Contrato:

- Solo usa `GET`.
- No crea solicitudes.
- No mueve inventario.
- No crea alertas persistentes.
- Si faltan tablas, devuelve `schema_pendiente=1`, lista vacia y mensaje informativo.

Validaciones:

- `php -l app/controladores/Almacen.php`: OK.
- `php -l app/modelos/Almacenes.php`: OK.
- `php -l app/vistas/paginas/apps/erp/almacen/resurtido.php`: OK.
- `node --check public/assets/js/custom/apps/erp/almacen/resurtido/resurtido.js`: OK.
- Prueba CLI read-only de `consultar_resurtidos_readonly()`: `schema_pendiente=1`, `total=0`.

## RES-T007B - Detalle read-only por folio

Fecha: 2026-07-12

Objetivo:

- Preparar la consulta completa por folio antes de habilitar escrituras.
- Validar que el backend use los nombres reales de la propuesta DDL (`id_resurtido_almacen`).
- Permitir UAT documental por folio/SKU cuando el esquema este aplicado.

Implementado:

- Controlador: `Almacen::resurtido_consultar_erp()`.
- Modelo: `Almacenes::consultar_resurtido_readonly()`.
- UI: folio clicable en `Almacen > Resurtido` y panel lateral `Detalle folio`.

Alcance de la consulta:

- Encabezado del resurtido.
- Detalle por SKU.
- Preparacion por existencia, lote, caducidad, ubicacion y unidad fisica.
- Envios y referencias a movimientos/transito.
- Recepciones en tienda.
- Diferencias por faltante, dano, lote o caducidad.

Contrato:

- Solo usa `GET`.
- No crea ni modifica datos.
- No mueve inventario.
- Si faltan tablas, devuelve `schema_pendiente=1`.

Correccion aplicada:

- El listado read-only quedo alineado con el DDL propuesto:
  - llave primaria `id_resurtido_almacen`;
  - destino operativo como `id_almacen_solicitante`;
  - sin uso de columnas no propuestas como `fecha_requerida`.

Validaciones:

- `php -l app/controladores/Almacen.php`: OK.
- `php -l app/modelos/Almacenes.php`: OK.
- `php -l app/vistas/paginas/apps/erp/almacen/resurtido.php`: OK.
- `node --check public/assets/js/custom/apps/erp/almacen/resurtido/resurtido.js`: OK.
- Prueba CLI read-only de listado: `schema_pendiente=1`, `total=0`.
- Prueba CLI read-only de detalle: `schema_pendiente=1`, `detalle=0`.

## RES-T007C - UAT read-only del modulo

Fecha: 2026-07-12

Archivo:

- `storage/uat/uat_almacen_resurtido_readonly.php`

Objetivo:

- Validar el modulo sin ejecutar DDL ni escribir datos.
- Confirmar que vista/JS existen.
- Confirmar que listado y detalle responden en modo `schema_pendiente`.
- Confirmar que el preflight de stock bajo sigue funcionando con datos reales.
- Reportar guardrails para DDL y UAT posterior.

Resultado ejecutado:

- `ok=true`.
- `read_only=true`.
- Tablas pendientes detectadas: 7.
- Listado read-only: `schema_pendiente=1`, `total=0`.
- Detalle read-only: `schema_pendiente=1`.
- Stock bajo: `success`, `total=2`.
- Almacen usado por preflight: `Francisco Javier Mina 967 - Acuario` (`id_almacen=4`).

Guardrails confirmados:

- Requiere respaldo externo para DDL.
- Requiere autorizacion textual:
  - `AUTORIZO DDL RESURTIDO ALMACEN usando respaldo RUTA_O_REFERENCIA`
- Token tecnico de script:
  - `ALMACEN_RESURTIDO_DDL`
- No escribe BD.
- No mueve kardex.
- No modifica etiquetas.
- No toca POS/ecommerce.

## RES-T007D - Script apply autorizado especifico

Fecha: 2026-07-12

Archivo:

- `storage/uat/uat_almacen_resurtido_schema_apply_authorized.php`

Decision tecnica:

- No usar `AlmacenEsquema::planActualizarAlmacenInventario(true)` para aplicar este modulo, porque el plan general puede incluir pendientes ajenos a Resurtido.
- Usar el SQL especifico `docs/erp_almacen_resurtido_traspasos_schema_propuesta.sql`.
- Bloquear ejecucion por defecto con token y respaldo.

Token requerido:

```text
--autorizar=ALMACEN_RESURTIDO_DDL
```

Confirmacion textual requerida:

```text
--confirmacion="AUTORIZO DDL RESURTIDO ALMACEN usando respaldo RUTA_O_REFERENCIA"
```

Respaldo requerido:

```text
--respaldo=RUTA_O_REFERENCIA_RESPALDO
```

Contrato:

- Si falta token o respaldo valido, responde `modo=bloqueado`.
- Si falta confirmacion textual, responde `modo=bloqueado`.
- Si se autoriza, ejecuta solo las sentencias del SQL de Resurtido.
- No crea solicitudes reales.
- No mueve inventario.
- No toca POS/ecommerce.
- No usa el actualizador general de Almacen.

Validaciones:

- `php -l storage/uat/uat_almacen_resurtido_schema_apply_authorized.php`: OK.
- Ejecucion sin parametros: bloqueada correctamente; no ejecuto DDL.

## RES-T007E - Simulacion read-only de solicitud

Fecha: 2026-07-12

Objetivo:

- Preparar el contrato de `RES-T008` sin crear folios reales.
- Convertir el preflight de stock bajo en una vista previa de solicitud.
- Validar origen/destino antes de habilitar guardado.

Implementado:

- Controlador: `Almacen::resurtido_simular_solicitud_erp()`.
- Modelo: `Almacenes::simular_solicitud_resurtido_readonly()`.
- UI: boton `Simular solicitud` en `Almacen > Resurtido`.
- UAT: `storage/uat/uat_almacen_resurtido_readonly.php` ahora valida simulacion.

Contrato:

- Solo `GET`.
- No crea encabezado.
- No crea detalle.
- No aparta stock.
- No mueve inventario.
- Si no se pasa origen, propone un almacen activo tipo `bodega`/`principal` o con `permite_preparacion=1`.

Resultado UAT:

- `ok=true`.
- Folio preview: `SIM-RES-20260712-ACUARIO967`.
- Origen sugerido: `Francisco Javier Mina 971 - Bodega trasera`.
- Destino: `Francisco Javier Mina 967 - Acuario`.
- Partidas sugeridas: 2.
- `schema_pendiente=1`.

Decision:

- La simulacion usa reglas globales de Catalogo mientras no exista politica local por tienda/SKU.
- La solicitud real seguira bloqueada hasta DDL autorizado y respaldo externo.

## RES-T007F - Cobertura origen read-only

Fecha: 2026-07-12

Objetivo:

- Verificar desde la simulacion si el almacen origen sugerido puede surtir lo solicitado.
- Evitar que `RES-T008` cree solicitudes aparentemente validas pero imposibles de preparar.
- Mantener el calculo sin apartados ni movimientos.

Implementado:

- `Almacenes::simular_solicitud_resurtido_readonly()` ahora agrega por partida:
  - `cantidad_disponible_origen`
  - `cantidad_surtible_origen`
  - `puede_surtir_origen`
  - `estatus_cobertura_origen`
- Agrega resumen:
  - `partidas_con_origen_insuficiente`
  - `cantidad_total_surtible_origen`
- UI muestra badges `Origen suficiente` / `Origen insuficiente`.
- UAT read-only reporta cobertura del origen.

Resultado UAT:

- Acuario (`id_almacen=4`) mantiene 2 partidas sugeridas.
- Cantidad total sugerida: 51.
- Cantidad total surtible desde Bodega trasera: 3.
- Partidas con origen insuficiente: 1.
- `schema_pendiente=1`.

Hallazgo:

- `RES-H010`: el resurtido robusto debe distinguir necesidad de tienda contra capacidad real del origen. Una solicitud puede nacer desde stock bajo, pero antes de autorizar/preparar debe marcar lineas insuficientes para compra, ajuste de origen o autorizacion parcial.

Decision:

- En `RES-T008`, el guardado real debera permitir:
  - crear solicitud con linea marcada como insuficiente;
  - autorizar parcial;
  - o bloquear envio a preparacion si no hay cobertura.
- No se apartara stock hasta etapa de preparacion/envio definida en `RES-T009`.

## RES-T007G - Origen manual en simulacion

Fecha: 2026-07-12

Objetivo:

- Permitir comparar cobertura usando origen automatico o un origen elegido por el usuario.
- Validar que origen y destino no sean el mismo almacen.
- Mantener la simulacion sin persistencia.

Implementado:

- UI: selector `Origen sugerido` en `Almacen > Resurtido`.
- JS: `simularSolicitud()` envia `id_almacen_origen` cuando se elige manualmente.
- UAT: `uat_almacen_resurtido_readonly.php` valida simulacion con origen explicito.

Guardrails:

- Si origen = destino, el modelo responde `warning` con `Origen y destino no pueden ser el mismo almacen`.
- No crea folio.
- No aparta stock.
- No mueve inventario.

Resultado UAT:

- Destino: Acuario (`id_almacen=4`).
- Origen explicito: Bodega trasera (`id_almacen=3`).
- Simulacion con origen explicito: `success`, 2 partidas, `schema_pendiente=1`.

## RES-T007H - Resumen multi-tienda read-only

Fecha: 2026-07-12

Objetivo:

- Comparar necesidades de resurtido de todas las tiendas activas en una sola lectura.
- Detectar carga operativa antes de crear folios reales.
- Mantener el flujo sin alertas persistentes ni movimientos.

Implementado:

- Controlador: `Almacen::resurtido_resumen_tiendas_erp()`.
- Modelo: `Almacenes::resumen_resurtido_tiendas_readonly()`.
- UI: boton `Resumen tiendas`.
- UAT: `uat_almacen_resurtido_readonly.php` valida resumen multi-tienda.

Contrato:

- Solo `GET`.
- Solo tiendas activas con `permite_venta=1`.
- Usa simulacion read-only por tienda.
- No crea solicitudes.
- No crea alertas persistentes.
- No aparta stock.
- No mueve inventario.

Resultado UAT:

- Tiendas analizadas: 2.
- Partidas sugeridas: 4.
- Partidas con origen insuficiente: 2.
- Cantidad total sugerida: 102.
- Cantidad total surtible origen: 6.
- `schema_pendiente=1`.

Hallazgo:

- `RES-H011`: para operar resurtido multi-tienda no basta una alerta por tienda; se necesita tablero consolidado para priorizar tiendas y separar necesidad real de capacidad de origen.

Decision:

- El tablero read-only puede quedarse como preoperativo.
- En `RES-T012`, cuando existan alertas persistentes, este resumen debe alimentar pendientes por tienda/SKU sin sustituir el folio documental.

## RES-T007I - Validacion solicitud read-only

Fecha: 2026-07-13

Objetivo:

- Preparar el contrato previo a `RES-T008` sin habilitar POST ni escrituras.
- Indicar si una simulacion puede convertirse en solicitud real.
- Separar bloqueos de advertencias operativas.

Implementado:

- Controlador: `Almacen::resurtido_validar_solicitud_erp()`.
- Modelo: `Almacenes::validar_solicitud_resurtido_readonly()`.
- UI: boton `Validar solicitud`.
- UAT: `uat_almacen_resurtido_readonly.php` valida resultado.

Bloqueos actuales:

- `RES-VAL-001`: esquema pendiente.
- `RES-VAL-002`: sin partidas sugeridas.
- `RES-VAL-004`: cantidad solicitada invalida.

Advertencias actuales:

- `RES-VAL-003`: partidas con origen insuficiente.

Resultado UAT:

- `puede_guardar=0`.
- Bloqueos: 1 (`RES-VAL-001`, esquema pendiente).
- Advertencias: 1 (`RES-VAL-003`, origen insuficiente).
- No se creo folio, detalle, apartado ni movimiento.

Decision:

- Cuando el DDL este aplicado, `RES-T008` podra usar esta validacion como preflight antes del POST real.
- Las advertencias no deben bloquear necesariamente el borrador, pero si deben bloquear envio a preparacion salvo autorizacion parcial o resolucion operativa.

## RES-T007J - Payload RES-T008 read-only

Fecha: 2026-07-13

Objetivo:

- Definir el contrato del futuro POST de guardado sin habilitar escrituras.
- Mostrar encabezado y detalle que se enviarian a `RES-T008`.
- Mantener bloqueado el envio real mientras exista esquema pendiente.

Implementado:

- Controlador: `Almacen::resurtido_payload_solicitud_erp()`.
- Modelo: `Almacenes::payload_solicitud_resurtido_readonly()`.
- UI: boton `Payload RES-T008`.
- UAT: `uat_almacen_resurtido_readonly.php` valida payload.

Payload propuesto:

- Encabezado:
  - `tipo_documento=resurtido_tienda`
  - `estatus=borrador`
  - `prioridad=normal`
  - `origen_solicitud=stock_bajo_preflight`
  - `id_almacen_solicitante`
  - `id_almacen_origen`
  - `id_almacen_transito=null`
- Detalle:
  - `id_sku_erp`
  - `sku`
  - `nombre_producto`
  - `unidad_base`
  - `cantidad_solicitada`
  - `cantidad_autorizada=0`
  - `estatus=pendiente`
  - `cobertura_origen`

Resultado UAT:

- `payload_puede_enviar_post=0`.
- `payload_lineas=2`.
- `endpoint_futuro=/almacen/resurtido_guardar_erp`.
- Bloqueado por `RES-VAL-001` mientras el esquema este pendiente.

Decision:

- `RES-T008` debe reutilizar este contrato y volver a ejecutar validacion del lado servidor antes de escribir.
- El folio real se generara en backend al guardar; el folio preview no debe persistirse como folio definitivo.

## RES-T007K - UAT contrato payload

Fecha: 2026-07-13

Objetivo:

- Asegurar que el payload read-only tenga todos los campos minimos antes de convertirlo en POST real.
- Evitar que `RES-T008` nazca con contrato incompleto.

Implementado:

- `storage/uat/uat_almacen_resurtido_readonly.php` ahora valida:
  - encabezado obligatorio;
  - detalle no vacio;
  - campos obligatorios por linea;
  - cobertura de origen por linea;
  - cantidad solicitada mayor a cero.

Campos de encabezado obligatorios:

- `tipo_documento`
- `estatus`
- `prioridad`
- `origen_solicitud`
- `id_almacen_solicitante`
- `id_almacen_origen`

Campos de detalle obligatorios:

- `id_sku_erp`
- `sku`
- `nombre_producto`
- `unidad_base`
- `cantidad_solicitada`
- `cantidad_autorizada`
- `estatus`
- `cobertura_origen`

Resultado UAT:

- `payload_contrato.ok=true`.
- Fallos: 0.
- Campos de encabezado: 10.
- Lineas de detalle: 2.

Decision:

- `RES-T008` no debe aceptar payload sin revalidar este contrato en servidor.
- Este UAT debe ejecutarse antes y despues de aplicar DDL.

## RES-T007L - UAT estatico SQL DDL

Fecha: 2026-07-13

Archivo:

- `storage/uat/uat_almacen_resurtido_sql_static.php`

Objetivo:

- Validar el SQL propuesto antes de cualquier autorizacion de DDL.
- Confirmar que existan las 7 tablas esperadas.
- Confirmar columnas minimas usadas por backend/modelo.
- Confirmar indices y constraints criticos.
- Bloquear si aparecen operaciones destructivas o DML no esperado.

Contrato:

- No conecta BD.
- No ejecuta DDL.
- No escribe datos.
- No mueve inventario.
- No toca POS/ecommerce.

Validaciones:

- `C:\xampp\php\php.exe -l storage\uat\uat_almacen_resurtido_sql_static.php`: OK.
- `C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_sql_static.php`: `ok=true`.

Resultado observado:

- Tablas detectadas: 7.
- Bloqueos: 0.
- Avisos: 0.
- Guardrails read-only confirmados.

Decision:

- Este UAT debe ejecutarse antes del script autorizado `RES-T007D`.
- Que este UAT pase no autoriza DDL por si mismo; sigue requiriendo respaldo externo y autorizacion textual.

## RES-T008 - Inicio seguro backend solicitud/resurtido

Fecha: 2026-07-13

Objetivo:

- Dejar preparado el endpoint POST real sin permitir escrituras mientras falte DDL.
- Reutilizar el payload validado por `RES-T007J/K`.
- Confirmar por UAT que hoy el guardado queda bloqueado.

Implementado:

- Controlador: `Almacen::resurtido_guardar_erp()`.
- Modelo: `Almacenes::guardar_solicitud_resurtido()`.
- Helpers:
  - `tablasResurtidoFaltantes()`
  - `normalizarPayloadSolicitudResurtido()`
  - `generarFolioResurtido()`

Contrato actual:

- Endpoint futuro: `/almacen/resurtido_guardar_erp`.
- Metodo: `POST`.
- Permiso inicial conservador: `almacen.recibir`.
- Si falta esquema, retorna:
  - `schema_pendiente=1`
  - `guardado=0`
  - lista de tablas faltantes
- No valida/insertar payload si falta esquema.

Contrato cuando exista DDL:

- Inserta encabezado en `erp_almacen_resurtidos`.
- Inserta detalle en `erp_almacen_resurtido_detalle`.
- Estados iniciales permitidos:
  - `borrador`
  - `solicitado`
- Genera folio backend `RES-YYYYMMDD-####`.
- No mueve inventario.
- No aparta stock.
- No crea preparacion/envio/recepcion.

Resultado UAT actual:

- `guardado_bloqueado.tipo=info`.
- `guardado_schema_pendiente=1`.
- `guardado_realizado=0`.
- UAT completo `ok=true`.

Pendiente para completar `RES-T008`:

- Aplicar DDL con respaldo externo y autorizacion.
- Ejecutar UAT read-only despues del DDL.
- Ejecutar UAT controlado de guardado real por folio/SKU.
- Revisar si se crean permisos finos `almacen.resurtido.*` antes de operacion real.

### UAT autorizado de guardado RES-T008

Fecha: 2026-07-13

Archivo:

- `storage/uat/uat_almacen_resurtido_guardar_authorized.php`

Objetivo:

- Crear una solicitud UAT real solo despues de DDL, respaldo y autorizacion explicita.
- Probar encabezado/detalle sin mover inventario.

Token requerido:

```text
--autorizar=ALMACEN_RESURTIDO_GUARDAR_UAT
```

Confirmacion textual requerida:

```text
--confirmacion="AUTORIZO UAT GUARDAR RESURTIDO usando respaldo RUTA_O_REFERENCIA"
```

Parametros:

```text
--respaldo=RUTA_O_REFERENCIA_RESPALDO
--destino=ID_ALMACEN_TIENDA
--origen=ID_ALMACEN_ORIGEN
```

Alcance:

- Crea encabezado.
- Crea detalle.
- No mueve inventario.
- No aparta stock.
- No crea preparacion.
- No crea envio.
- No crea recepcion.
- No toca POS/ecommerce.

Validacion ejecutada:

- `php -l storage/uat/uat_almacen_resurtido_guardar_authorized.php`: OK.
- Ejecucion sin parametros: `modo=bloqueado`; no creo solicitud.

### UAT read-only por folio RES-T008

Fecha: 2026-07-13

Archivo:

- `storage/uat/uat_almacen_resurtido_folio_readonly.php`

Objetivo:

- Validar una solicitud creada por `RES-T008` sin modificarla.
- Confirmar que existe encabezado y detalle.
- Confirmar que aun no hay preparacion, envio ni recepcion antes de `RES-T009`.

Uso esperado despues de guardar UAT:

```text
php storage/uat/uat_almacen_resurtido_folio_readonly.php --folio=RES-YYYYMMDD-####
```

Contrato:

- Read-only.
- No mueve kardex.
- No aparta stock.
- Espera solo encabezado/detalle para cierre de `RES-T008`.

Validacion ejecutada:

- `php -l storage/uat/uat_almacen_resurtido_folio_readonly.php`: OK.
- Ejecucion con folio de ejemplo y esquema pendiente: `ok=true`, aviso de `schema_pendiente`; no escribio datos.

## Contrato preliminar de estados

Estados del encabezado:

- `borrador`: captura interna no enviada.
- `solicitado`: tienda envio solicitud.
- `autorizado`: responsable aprueba total/parcial.
- `rechazado`: responsable rechaza antes de preparar.
- `preparando`: bodega esta seleccionando existencias/unidades.
- `preparado`: surtido listo para enviar.
- `enviado`: mercancia salio de origen y esta en transito.
- `recibido_parcial`: tienda recibio con faltantes o diferencias pendientes.
- `recibido`: tienda recibio completo.
- `cerrado`: diferencias resueltas y documento concluido.
- `cancelado`: documento cancelado sin movimiento pendiente.

Estados de detalle:

- `pendiente`
- `autorizado`
- `rechazado`
- `preparado`
- `enviado`
- `recibido`
- `diferencia`
- `cancelado`

Estados de diferencia:

- `abierta`
- `en_revision`
- `resuelta`
- `cancelada`

## Permisos sugeridos

No crear permisos sin revisar `SeguridadPermisos.php`, `SeguridadEsquema.php`, `Core.php` y convenciones existentes.

Permisos candidatos:

- `almacen.resurtido.ver`
- `almacen.resurtido.solicitar`
- `almacen.resurtido.autorizar`
- `almacen.resurtido.preparar`
- `almacen.resurtido.enviar`
- `almacen.resurtido.recibir`
- `almacen.resurtido.diferencias`
- `almacen.resurtido.configurar`

Alternativa conservadora inicial:

- usar temporalmente `almacen.ver` para consultas;
- usar `almacen.recibir` para acciones operativas;
- usar `inventario.traspasar` para envio;
- crear permisos finos cuando el flujo este estable.

Recomendacion:

- Crear permisos finos antes de usar en operacion real, porque tiendas y bodega no deben tener las mismas acciones.

## RES-T003 - Contrato inicial de estados, permisos y auditoria

Fecha: 2026-07-11

Auditoria de seguridad revisada:

- `SeguridadEsquema::permisosBaseERP()` ya define permisos base para Almacen e Inventario.
- Permisos actuales relacionados:
  - `almacen.ver`
  - `almacen.recibir`
  - `almacen.ubicaciones`
  - `inventario.ver`
  - `inventario.traspasar`
  - `inventario.ajustar`
  - `inventario.conteo`
- Roles base actuales:
  - `almacen` tiene `almacen.ver`, `almacen.recibir`, `almacen.ubicaciones`, `inventario.ver`, `inventario.traspasar`.
  - `inventario` tiene `inventario.ver`, `inventario.ajustar`, `inventario.traspasar`, `inventario.conteo`.
  - `auditor` tiene consulta sin operacion transaccional.
- `Core.php` ya contempla auditoria explicita para `Inventario.traspasar_erp` y `Almacen.guardar_recepcion`.

Decision:

- Para codigo read-only/preflight se puede usar `almacen.ver` o `inventario.ver`.
- Para operacion real del modulo conviene crear permisos finos de resurtido.
- No conviene reutilizar solo `inventario.traspasar` para todo, porque autorizar, preparar, enviar y recibir son responsabilidades distintas.

Permisos finos recomendados:

| Permiso | Uso |
| --- | --- |
| `almacen.resurtido.ver` | Consultar solicitudes, envios, recepciones y diferencias |
| `almacen.resurtido.solicitar` | Crear solicitud desde tienda |
| `almacen.resurtido.autorizar` | Aprobar, rechazar o ajustar cantidades |
| `almacen.resurtido.preparar` | Seleccionar existencias/unidades origen |
| `almacen.resurtido.enviar` | Confirmar salida y transito |
| `almacen.resurtido.recibir` | Confirmar recepcion en tienda |
| `almacen.resurtido.diferencias` | Resolver diferencias de recepcion |
| `almacen.resurtido.configurar` | Configurar politicas por tienda/SKU |

Asignacion inicial sugerida por rol:

| Rol | Permisos sugeridos |
| --- | --- |
| `direccion` | ver, autorizar, diferencias |
| `administrador_erp` | todos |
| `almacen` | ver, preparar, enviar, recibir |
| `inventario` | ver, configurar, diferencias |
| `ventas` | ver limitado en fase futura, no operar |
| `auditor` | ver |
| `solo_lectura` | ver si negocio lo permite |

### Transiciones permitidas

| Estado actual | Accion | Estado destino | Permiso recomendado |
| --- | --- | --- | --- |
| `borrador` | enviar solicitud | `solicitado` | `almacen.resurtido.solicitar` |
| `solicitado` | autorizar total/parcial | `autorizado` | `almacen.resurtido.autorizar` |
| `solicitado` | rechazar | `rechazado` | `almacen.resurtido.autorizar` |
| `autorizado` | iniciar preparacion | `preparando` | `almacen.resurtido.preparar` |
| `preparando` | confirmar preparacion | `preparado` | `almacen.resurtido.preparar` |
| `preparado` | enviar | `enviado` | `almacen.resurtido.enviar` |
| `enviado` | recibir parcial | `recibido_parcial` | `almacen.resurtido.recibir` |
| `enviado` | recibir completo | `recibido` | `almacen.resurtido.recibir` |
| `recibido_parcial` | resolver diferencias | `cerrado` o `recibido` | `almacen.resurtido.diferencias` |
| `recibido` | cerrar | `cerrado` | `almacen.resurtido.recibir` |
| `borrador`/`solicitado`/`autorizado` | cancelar | `cancelado` | segun estado y permiso responsable |

Reglas:

- No permitir volver de `enviado` a `preparado` si ya hubo movimientos.
- No permitir cancelar `enviado` sin recepcion, diferencia o reversa documentada.
- No permitir editar cantidades autorizadas despues de `preparado`; usar ajuste documental o cancelar antes.
- Autorizacion parcial, rechazo, diferencia y cancelacion requieren comentario.
- Si una linea preparada mezcla lotes, debe dividirse por lote/caducidad/unidad.

### Auditoria explicita recomendada

Acciones que deben registrar auditoria explicita:

- `resurtido_solicitar_erp`
- `resurtido_autorizar_erp`
- `resurtido_rechazar_erp`
- `resurtido_preparar_erp`
- `resurtido_enviar_erp`
- `resurtido_recibir_erp`
- `resurtido_diferencia_resolver_erp`
- `resurtido_cancelar_erp`
- `resurtido_politica_guardar_erp`

Entidad principal:

```text
erp_almacen_resurtidos
```

Entidades secundarias:

```text
erp_almacen_resurtido_detalle
erp_almacen_resurtido_preparacion
erp_almacen_resurtido_envios
erp_almacen_resurtido_recepciones
erp_almacen_resurtido_diferencias
erp_inventario_politicas_almacen_sku
```

Resultado:

- `RES-T003` queda documentada.
- No se modifico codigo ni BD.

## RES-T004 - Preflight stock bajo por tienda/SKU

Fecha: 2026-07-11

Objetivo:

- Consultar necesidades potenciales de resurtido por almacen/SKU sin crear documentos, alertas ni movimientos.
- Usar reglas globales de Catalogo mientras no exista politica local por tienda/SKU.

Endpoint agregado:

```text
GET /almacen/resurtido_stock_bajo_preflight_erp
```

Permiso:

```text
almacen.ver
```

Metodo de dominio:

```text
Almacenes::preflight_stock_bajo_resurtido($filtros)
```

Filtros soportados:

- `id_almacen`: obligatorio.
- `id_sku`: opcional.
- `q`: busqueda por SKU/producto.
- `solo_bajos`: default `1`.

Contrato:

- Read-only.
- No usa tablas nuevas.
- No crea solicitudes.
- No crea notificaciones.
- No mueve inventario.
- Devuelve `politica_fuente='catalogo_global'` hasta que exista `erp_inventario_politicas_almacen_sku`.

Formula aplicada:

```text
umbral_usado = punto_reorden > 0 ? punto_reorden : stock_minimo
requiere_resurtido = umbral_usado > 0 AND disponible <= umbral_usado
si stock_maximo existe:
  cantidad_sugerida = stock_maximo - disponible
si no:
  cantidad_sugerida = punto_reorden - disponible o stock_minimo - disponible
```

Archivos modificados:

- `app/controladores/Almacen.php`
- `app/modelos/Almacenes.php`

Validaciones tecnicas:

| Comando | Resultado |
| --- | --- |
| `C:\xampp\php\php.exe -l app/controladores/Almacen.php` | OK |
| `C:\xampp\php\php.exe -l app/modelos/Almacenes.php` | OK |

Prueba read-only CLI:

```text
Almacenes::preflight_stock_bajo_resurtido(id_almacen=3, solo_bajos=1)
```

Resultado:

```json
{"error":false,"tipo":"success","mensaje":"Preflight de stock bajo consultado","total":1}
```

Hallazgo derivado:

- `RES-H009`: el preflight ya puede detectar necesidades con politica global, pero falta politica local por tienda/SKU para que Acuario y Mascotas tengan minimos/maximos propios.

## RES-T005 - Diseno UX operativo inicial

Fecha: 2026-07-11

Objetivo:

- Definir la primera pantalla `Almacen > Resurtido` antes de crear vistas/JS.
- Mantener la experiencia separada de Inventario > Traspaso directo.
- Evitar una pantalla "bonita" pero inutil para bodega/tienda.

Ruta sugerida:

```text
/almacen/resurtido
```

Vistas sugeridas:

```text
app/vistas/paginas/apps/erp/almacen/resurtido/listado.php
app/vistas/paginas/apps/erp/almacen/resurtido/formulario.php
```

JS sugerido:

```text
public/assets/js/custom/apps/erp/almacen/resurtido/listado.js
public/assets/js/custom/apps/erp/almacen/resurtido/formulario.js
```

Primera pantalla recomendada:

- Encabezado compacto con filtros:
  - tienda solicitante;
  - almacen origen;
  - estatus;
  - prioridad;
  - busqueda por folio/SKU.
- Tabla operativa:
  - folio;
  - tienda;
  - origen;
  - estatus;
  - partidas;
  - pendientes;
  - diferencias;
  - fecha;
  - acciones.
- Panel lateral o modal para `Stock bajo`:
  - llama al preflight read-only;
  - muestra SKU, disponible, punto de reorden, maximo y cantidad sugerida;
  - no crea solicitud hasta que exista flujo de solicitud.

Formulario recomendado por fases:

1. Solicitud:
   - tienda solicitante;
   - prioridad;
   - partidas con SKU, disponible en tienda, sugerido y solicitado;
   - comentario.
2. Autorizacion:
   - solicitado vs autorizado;
   - comentario obligatorio si autoriza parcial o rechaza.
3. Preparacion:
   - existencia origen exacta;
   - lote/caducidad;
   - ubicacion;
   - unidad fisica si aplica;
   - cantidad preparada.
4. Envio:
   - confirmar salida;
   - mostrar transito;
   - no permitir editar cantidades.
5. Recepcion:
   - confirmar recibido;
   - capturar diferencia si falta o llega danado/lote distinto;
   - ubicacion destino.

Reglas UX:

- Las acciones visibles dependen del estatus y permiso.
- No mezclar lotes en una misma linea visual de preparacion.
- Cantidades importantes deben usar stepper o input decimal con unidad visible, segun `docs/erp_ux_operativa.md`.
- La pantalla debe mostrar diferencia entre solicitado, autorizado, preparado, enviado y recibido.
- No mostrar stock en transito como disponible en tienda.

Resultado:

- `RES-T005` queda lista para convertir a vista/JS cuando exista backend minimo.

## RES-T006 - Auditoria de esquema dry-run

Fecha: 2026-07-11

Objetivo:

- Que `AlmacenEsquema` detecte estructuras faltantes de Resurtido sin ejecutar DDL.
- Mantener el DDL real bloqueado hasta respaldo externo y autorizacion.

Archivo modificado:

```text
app/modelos/AlmacenEsquema.php
```

Tablas agregadas a auditoria/plan:

- `erp_inventario_politicas_almacen_sku`
- `erp_almacen_resurtidos`
- `erp_almacen_resurtido_detalle`
- `erp_almacen_resurtido_preparacion`
- `erp_almacen_resurtido_envios`
- `erp_almacen_resurtido_recepciones`
- `erp_almacen_resurtido_diferencias`

Validaciones tecnicas:

| Comando | Resultado |
| --- | --- |
| `C:\xampp\php\php.exe -l app/modelos/AlmacenEsquema.php` | OK |
| `AlmacenEsquema::auditarAlmacenInventario()` | `tipo=warning`, `pendientes=true` |

Resultado especifico de resurtido:

| Tabla | Existe | Columnas faltantes | Indices faltantes |
| --- | --- | ---: | ---: |
| `erp_inventario_politicas_almacen_sku` | no | 15 | 3 |
| `erp_almacen_resurtidos` | no | 25 | 6 |
| `erp_almacen_resurtido_detalle` | no | 18 | 3 |
| `erp_almacen_resurtido_preparacion` | no | 18 | 5 |
| `erp_almacen_resurtido_envios` | no | 12 | 5 |
| `erp_almacen_resurtido_recepciones` | no | 16 | 5 |
| `erp_almacen_resurtido_diferencias` | no | 24 | 8 |

Decision:

- Se agregaron FKs minimas al plan dry-run y al SQL propuesto para que el esquema nazca con integridad referencial.
- El siguiente paso tecnico antes de migrar es solicitar respaldo externo y autorizacion formal.

## RES-T007 - Paquete de autorizacion DDL preparado

Fecha: 2026-07-11

Objetivo:

- Dejar lista la autorizacion formal para crear tablas de Resurtido.
- Documentar aplicacion y reversa antes de tocar BD.

Documentos creados:

- `docs/erp_almacen_resurtido_schema_solicitud_autorizacion.md`
- `docs/erp_almacen_resurtido_schema_runbook_aplicacion.md`
- `docs/erp_almacen_resurtido_schema_plan_reversa.md`

Cambios de integridad:

- `app/modelos/AlmacenEsquema.php` ahora contempla FKs esperadas de resurtido.
- `docs/erp_almacen_resurtido_traspasos_schema_propuesta.sql` queda sincronizado con esas FKs.

Token de autorizacion sugerido:

```text
AUTORIZO DDL RESURTIDO ALMACEN usando respaldo RUTA_O_REFERENCIA
```

Guardrails:

- No ejecutar DDL sin respaldo externo.
- No insertar datos reales en esta fase.
- No crear solicitudes `RES-*` todavia.
- No mover inventario.
- No tocar Ventas/POS/ecommerce.

Estado:

- Paquete preparado.
- Aplicacion no ejecutada.

## RES-T008A - Validacion interna de payload antes de guardar

Fecha: 2026-07-13

Objetivo:

- Reforzar el endpoint futuro de guardado de solicitudes de resurtido para que no dependa solo del payload generado por UI.
- Mantener el guardado real bloqueado mientras el esquema no exista.

Cambios:

- `Almacenes::guardar_solicitud_resurtido()` valida internamente encabezado, origen, destino, estatus inicial y detalle antes de insertar.
- Se agregaron bloqueos con IDs `RES-GUA-001` a `RES-GUA-009`.
- Se agrego advertencia `RES-GUA-010` cuando el origen no alcanza para surtir completo.
- El flujo sigue sin apartar stock, sin mover kardex y sin tocar etiquetas.

Validaciones ejecutadas:

- `php -l app/modelos/Almacenes.php`: OK.
- `storage/uat/uat_almacen_resurtido_readonly.php`: OK.

Resultado UAT read-only:

- Stock bajo Acuario: 2 partidas.
- Simulacion Acuario: folio preview `SIM-RES-20260712-ACUARIO967`, 2 partidas.
- Resumen tiendas: 2 tiendas, 4 partidas sugeridas, 2 con origen insuficiente.
- Payload RES-T008: contrato OK, 2 lineas.
- Guardado real: bloqueado por esquema pendiente, `guardado=0`.

Guardrails:

- No se ejecuto DDL.
- No se escribio BD.
- No se movio inventario.
- No se toco POS/ecommerce.

## RES-T009 - Contrato de estados y permisos documentado

Fecha: 2026-07-13

Objetivo:

- Cerrar el contrato operativo antes de implementar acciones reales de autorizacion, preparacion, envio, recepcion y cierre.
- Evitar saltos de estado como solicitado directo a enviado o preparado directo a recibido.

Documento creado:

- `docs/erp_almacen_resurtido_estados_permisos.md`

Decision:

- Estados de encabezado definidos: `borrador`, `solicitado`, `autorizado`, `rechazado`, `preparando`, `preparado`, `enviado`, `recibido_parcial`, `recibido`, `cerrado`, `cancelado`.
- Estados de detalle definidos para conservar solicitado/autorizado/preparado/enviado/recibido.
- Permisos finos quedan como candidatos, no se crean todavia.
- Read-only sigue con `almacen.ver`.
- Guardado futuro sigue protegido temporalmente por `almacen.recibir`.

Guardrails:

- No se modifico Seguridad.
- No se ejecuto DDL.
- No se escribio BD.
- No se movio inventario.

## RES-T008B - Backend autorizar/rechazar post-DDL

Fecha: 2026-07-13

Objetivo:

- Dejar listo el carril backend para autorizar o rechazar un folio `RES-*` despues del DDL.
- Mantener la accion bloqueada mientras el esquema este pendiente.
- Evitar movimientos de inventario en la etapa documental.

Implementado:

- Controlador: `Almacen::resurtido_autorizar_erp()`.
- Modelo: `Almacenes::autorizar_resurtido_pendiente()`.
- Validador interno: `validarFolioResurtidoParaAutorizar()`.

Contrato actual:

- Si falta esquema, devuelve `schema_pendiente=1`.
- Si existe esquema, valida que el folio este en `borrador` o `solicitado`.
- Si autoriza, actualiza encabezado, detalle autorizado y fecha/usuario de autorizacion.
- Si rechaza, actualiza encabezado/detalle como rechazado y exige motivo.
- Siempre devuelve `movimientos_generados=0`.
- No aparta stock.
- No toca POS/ecommerce.

Validacion:

- `storage/uat/uat_almacen_resurtido_readonly.php` valida que, sin DDL, la accion quede bloqueada y sin movimientos.
- `storage/uat/uat_almacen_resurtido_autorizar_authorized.php` queda listo para UAT real post-DDL con token/respaldo.

## RES-T008C - Backend cancelar post-DDL

Fecha: 2026-07-13

Objetivo:

- Dejar listo el carril backend para cancelar folios antes de envio/recepcion.
- Separar cancelacion simple de reversa controlada cuando ya existan envios o recepciones.

Implementado:

- Controlador: `Almacen::resurtido_cancelar_erp()`.
- Modelo: `Almacenes::cancelar_resurtido_pendiente()`.
- Validador interno: `validarFolioResurtidoParaCancelar()`.

Contrato actual:

- Si falta esquema, devuelve `schema_pendiente=1`.
- Si existe esquema, exige motivo y bloquea folios cerrados/cancelados/enviados/recibidos.
- Si el folio es cancelable, actualiza encabezado, partidas, motivo y cierre documental.
- Siempre devuelve `movimientos_generados=0`.
- No revierte movimientos.
- No toca POS/ecommerce.

Validacion:

- `storage/uat/uat_almacen_resurtido_readonly.php` valida que, sin DDL, la accion quede bloqueada y sin movimientos.
- `storage/uat/uat_almacen_resurtido_cancelar_authorized.php` queda listo para UAT real post-DDL con token/respaldo.

## RES-T008D - UAT read-only robusto sin conexion

Fecha: 2026-07-13

Objetivo:

- Evitar errores fatales cuando MySQL/MariaDB local no este disponible.
- Reportar bloqueo de entorno en JSON controlado.
- Mantener guardrails visibles incluso cuando no se pueda consultar BD.

Implementado:

- Modelo: `Almacenes::conexion_disponible_readonly()`.
- UAT: `storage/uat/uat_almacen_resurtido_readonly.php`.

Contrato:

- No ejecuta DDL.
- No escribe BD.
- No mueve inventario.
- Si no hay conexion, responde `ok=false` con `error_entorno`.

Resultado observado:

- Con MySQL local caido, el UAT responde bloqueo controlado:
  - `error_entorno=Conexion de BD no disponible`.
  - `no_ejecuta_ddl=true`.
  - `no_escribe_bd=true`.
  - `no_mueve_kardex=true`.

## RES-T008E - Contrato acciones read-only

Fecha: 2026-07-13

Objetivo:

- Mostrar en backend/UI las acciones futuras sin ejecutar POST.
- Concentrar guardrails de autorizar, cancelar, preparar/enviar, recibir y politicas.
- Marcar cuales acciones son documentales y cuales afectaran inventario.

Implementado:

- Controlador: `Almacen::resurtido_acciones_contrato_erp()`.
- Modelo: `Almacenes::acciones_resurtido_contrato_readonly()`.
- UI: panel `Acciones` en `Almacen > Resurtido`.
- JS: `cargarContratoAcciones()`.

Contrato:

- Solo GET.
- No ejecuta POST.
- No escribe BD.
- No mueve inventario.
- No toca POS/ecommerce.

Validacion:

- `storage/uat/uat_almacen_resurtido_readonly.php` valida minimo 6 acciones.
- El UAT exige que preparar/enviar y recibir queden marcadas como acciones que afectan inventario.

## RES-T008F - Arnes autorizar/cancelar autorizado

Fecha: 2026-07-13

Objetivo:

- Dejar listos scripts UAT bloqueados por token/respaldo para autorizar/rechazar y cancelar.
- Conectar los scripts al backend documental post-DDL de `Almacenes`.
- Evitar que una prueba posterior use comandos sueltos sin guardrails.

Implementado:

- `storage/uat/uat_almacen_resurtido_autorizar_authorized.php`.
- `storage/uat/uat_almacen_resurtido_cancelar_authorized.php`.

Contrato actual:

- Bloqueados por defecto.
- Requieren token, confirmacion textual y respaldo.
- Si falta DDL, responden `schema_pendiente` y no modifican folios.
- Con DDL aplicado y datos validos, autorizan/rechazan o cancelan documentos de resurtido.
- No mueven inventario.
- No tocan POS/ecommerce.

Tokens:

- `ALMACEN_RESURTIDO_AUTORIZAR_UAT`.
- `ALMACEN_RESURTIDO_CANCELAR_UAT`.

## RES-T009A - Contrato estados read-only en backend/UI/UAT

Fecha: 2026-07-13

Objetivo:

- Exponer la matriz de estados y transiciones como contrato consultable por el modulo.
- Validar desde UAT que los saltos peligrosos no aparezcan como permitidos.
- Preparar `RES-T009` sin habilitar acciones reales.

Implementado:

- Controlador: `Almacen::resurtido_estados_erp()`.
- Modelo: `Almacenes::estados_resurtido_readonly()`.
- UI: panel `Estados` en `Almacen > Resurtido`.
- UAT: `storage/uat/uat_almacen_resurtido_readonly.php` valida estados/transiciones.

Contrato:

- Solo `GET`.
- No consulta folios reales.
- No cambia estados.
- No crea permisos.
- No escribe BD.
- No mueve inventario.

Validaciones UAT:

- Estados de encabezado: 11.
- Estados de detalle: 8.
- Transiciones permitidas: 12.
- `preparado -> enviado`: permitido.
- `solicitado -> enviado`: bloqueado.
- UAT maestro: `ok=true`.

Decision:

- Los permisos finos siguen como candidatos.
- Autorizar/cancelar ya tienen backend documental listo post-DDL.
- Preparar, enviar, recibir y cerrar con movimientos quedan pendientes hasta DDL autorizado, respaldo externo y UAT por folio/SKU.

## RES-T009B - Contrato preparacion/envio read-only

Fecha: 2026-07-13

Objetivo:

- Definir el contrato tecnico de preparacion y envio antes de escribir BD o mover inventario.
- Alinear el resurtido con existencias, movimientos, lotes, caducidad y unidades fisicas.
- Preparar el backend real de `RES-T009` con guardrails comprobables.

Implementado:

- Controlador: `Almacen::resurtido_preparacion_envio_contrato_erp()`.
- Modelo: `Almacenes::preparacion_envio_resurtido_contrato_readonly()`.
- UI: panel `Preparacion/envio` en `Almacen > Resurtido`.
- UAT: `storage/uat/uat_almacen_resurtido_readonly.php` valida contrato.
- Documento vivo: `docs/erp_almacen_resurtido_estados_permisos.md`.

Contrato:

- Preparacion requiere folio `autorizado` y detalle `autorizada`.
- Preparacion fija existencia, ubicacion, lote, caducidad y unidad fisica si aplica.
- Preparacion no afecta inventario.
- Envio requiere folio `preparado`.
- Envio genera salida de origen y entrada a transito.
- Envio conserva SKU, lote, caducidad, unidad fisica y referencia `RES-*`.
- Envio es el primer punto que afecta inventario.

Validaciones UAT:

- Contrato consultado en modo read-only.
- Preparacion declara `no_afecta_inventario=1`.
- Envio declara `afecta_inventario=1`.
- Envio define 2 movimientos esperados: salida origen y entrada transito.
- UAT maestro: `ok=true`.

Decision:

- Recomendacion vigente: usar almacen tecnico `TRANSITO` real.
- La implementacion real de preparacion/envio queda bloqueada hasta DDL autorizado, respaldo externo y UAT por folio/SKU.

## RES-T009C - Preflight folio preparacion/envio

Fecha: 2026-07-13

Objetivo:

- Validar un folio `RES-*` antes de ejecutar acciones reales de preparacion/envio.
- Detectar estado no preparable, detalle sin autorizacion o recepciones previas.
- Mantener la verificacion sin escritura y sin movimiento de inventario.

Implementado:

- UAT: `storage/uat/uat_almacen_resurtido_preparacion_envio_preflight.php`.

Contrato:

- Acepta `--folio=RES-*` o `--id=ID_RESURTIDO`.
- Consulta el folio en modo read-only.
- Consulta contrato `preparacion_envio_resurtido_contrato_readonly()`.
- No autoriza.
- No prepara.
- No envia.
- No aparta stock.
- No mueve inventario.

Validaciones:

- Con esquema pendiente devuelve advertencia `RES-PRE-002`.
- Cuando exista esquema, exige estatus `autorizado`, `preparando` o `preparado`.
- Exige lineas con `cantidad_autorizada > 0`.
- Bloquea folios con recepciones antes de `RES-T009`.

Decision:

- Este preflight debe ejecutarse despues de crear el primer folio UAT y antes de cualquier script real de preparacion/envio.

## RES-T009D - Arnes preparar/enviar autorizado

Fecha: 2026-07-13

Objetivo:

- Dejar preparado el contrato de autorizacion para futura prueba real de preparacion/envio.
- Evitar ejecuciones accidentales sin token, confirmacion y respaldo.
- Hacer explicito que la implementacion de movimientos sigue pendiente.

Implementado:

- UAT: `storage/uat/uat_almacen_resurtido_preparar_enviar_authorized.php`.

Contrato actual:

- Bloqueado por defecto.
- Requiere token `ALMACEN_RESURTIDO_PREPARAR_ENVIAR_UAT`.
- Requiere confirmacion textual `AUTORIZO UAT PREPARAR ENVIAR RESURTIDO usando respaldo RUTA_O_REFERENCIA`.
- Requiere respaldo.
- Aun con autorizacion responde `implementacion_pendiente`.
- No mueve inventario ni modifica unidades.

Decision:

- El arnes no sustituye el backend real `RES-T009`.
- Implementar movimientos solo despues de DDL, respaldo y folio UAT validado.

## RES-T009E - Backend pendiente preparar/enviar

Fecha: 2026-07-13

Objetivo:

- Exponer un endpoint/modelo real para la accion futura de preparar/enviar.
- Mantenerlo bloqueado por esquema pendiente o implementacion pendiente.
- Evitar que el arnes autorizado quede desconectado del dominio `Almacenes`.

Implementado:

- Controlador: `Almacen::resurtido_preparar_enviar_erp()`.
- Modelo: `Almacenes::preparar_enviar_resurtido_pendiente()`.
- Validadores internos:
  - `validarFolioResurtidoParaPrepararEnviar()`.

Contrato actual:

- Si falta esquema, devuelve `schema_pendiente=1`.
- Si existe esquema pero el folio no es valido, devuelve bloqueos `RES-ENV-VAL-*`.
- Si el folio es candidato, devuelve `implementacion_pendiente=1`.
- Siempre devuelve `preparado=0`, `enviado=0`, `movimientos_generados=0`.
- No inserta preparacion.
- No inserta envio.
- No descuenta origen.
- No crea transito.
- No modifica unidades fisicas.
- No toca POS/ecommerce.

Validacion:

- `storage/uat/uat_almacen_resurtido_readonly.php` valida que el contrato quede bloqueado y sin movimientos.
- `storage/uat/uat_almacen_resurtido_preparar_enviar_authorized.php` consulta este backend despues del token/respaldo.

Decision:

- La implementacion real `RES-T009` debe reemplazar este metodo o evolucionarlo solo despues de DDL aplicado, respaldo externo, folio UAT creado y preflight validado.

## RES-T009F - Plan preparacion read-only

Fecha: 2026-07-13

Objetivo:

- Calcular que existencias del origen cubririan un resurtido antes de apartar o mover inventario.
- Usar orden FEFO por lote/caducidad/fecha de registro.
- Respetar unidades fisicas trazables cuando existan, incluyendo unidades abiertas o cerradas con saldo.
- Mantener el flujo en modo read-only antes de `RES-T009` real.

Implementado:

- Controlador: `Almacen::resurtido_plan_preparacion_erp()`.
- Modelo: `Almacenes::plan_preparacion_resurtido_readonly()`.
- UI: panel `Plan preparacion` en `Almacen > Resurtido`.
- UAT: `storage/uat/uat_almacen_resurtido_readonly.php` valida el plan.

Contrato actual:

- Acepta folio/id real cuando exista DDL.
- Antes de DDL puede operar sobre simulacion de stock bajo con tienda/origen seleccionados.
- No inserta preparacion.
- No aparta stock.
- No genera envios.
- No descuenta origen.
- No crea transito.
- No modifica unidades fisicas.
- No toca POS/ecommerce.

Resultado UAT read-only:

- Partidas planeadas: 2.
- Cantidad requerida: 51.
- Cantidad planeada desde origen: 3.
- Cantidad faltante: 48.
- Selecciones de existencia: 1.
- Selecciones con unidad fisica: 1.
- Movimientos generados: 0.

Decision:

- La preparacion real debe reutilizar este criterio FEFO como previsualizacion, pero al confirmar debera bloquear filas, insertar `erp_almacen_resurtido_preparacion` y validar nuevamente saldos/unidades.
- Si una existencia tiene unidades fisicas, no se debe sugerir saldo agregado sin unidad; el operador debe elegir unidad trazable o resolver la diferencia.

## RES-T009G - Payload preparacion/envio read-only

Fecha: 2026-07-13

Objetivo:

- Convertir el plan FEFO en el JSON futuro para `resurtido_preparar_enviar_erp`.
- Revisar selecciones de existencia, lote, caducidad, ubicacion y unidad fisica antes de habilitar POST.
- Mantener bloqueado el POST si falta DDL, si hay cobertura incompleta o si no hay selecciones.

Implementado:

- Controlador: `Almacen::resurtido_payload_preparacion_envio_erp()`.
- Modelo: `Almacenes::payload_preparacion_envio_resurtido_readonly()`.
- UI: panel `Payload RES-T009`.
- UAT: `storage/uat/uat_almacen_resurtido_payload_preparacion_envio_readonly.php`.

Contrato actual:

- Solo GET.
- No inserta preparacion.
- No aparta stock.
- No genera envio.
- No mueve inventario.
- No modifica unidades fisicas.
- `puede_enviar_post=0` mientras falte DDL o existan advertencias de cobertura.

Resultado UAT read-only:

- Preparaciones candidatas en payload: 1.
- POST bloqueado.
- Movimientos generados: 0.
- Preparaciones generadas: 0.

## RES-T010A - Contrato recepcion/diferencias read-only

Fecha: 2026-07-13

Objetivo:

- Definir el contrato tecnico de recepcion antes de escribir BD o mover inventario.
- Separar recepcion completa de recepcion parcial con diferencias.
- Alinear diferencias por folio/SKU/lote/caducidad/unidad fisica.

Implementado:

- Controlador: `Almacen::resurtido_recepcion_diferencias_contrato_erp()`.
- Modelo: `Almacenes::recepcion_diferencias_resurtido_contrato_readonly()`.
- UI: panel `Recepcion/diferencias` en `Almacen > Resurtido`.
- UAT: `storage/uat/uat_almacen_resurtido_readonly.php` valida contrato.
- Documento vivo: `docs/erp_almacen_resurtido_estados_permisos.md`.

Contrato:

- Recepcion requiere folio `enviado`.
- Recepcion compara enviado contra recibido.
- Recepcion genera salida de transito y entrada a tienda.
- Recepcion puede dejar folio en `recibido` o `recibido_parcial`.
- Diferencias minimas: faltante, sobrante, danado, lote_distinto, caducidad_distinta, unidad_no_recibida, unidad_no_enviada.
- Cierre solo con diferencias resueltas o aceptadas con responsable y observacion.

Validaciones UAT:

- Contrato consultado en modo read-only.
- Recepcion declara `afecta_inventario=1`.
- Recepcion define 2 movimientos esperados: salida transito y entrada tienda.
- Recepcion define al menos 6 diferencias minimas.
- UAT maestro: `ok=true`.

Decision:

- Las diferencias abiertas bloquean cierre operativo.
- La implementacion real de recepcion/diferencias queda bloqueada hasta DDL autorizado, respaldo externo y UAT por folio/SKU.

## RES-T010B - Preflight folio recepcion/diferencias

Fecha: 2026-07-13

Objetivo:

- Validar un folio `RES-*` antes de ejecutar recepcion real.
- Detectar folios no enviados, sin envios, ya recibidos o con diferencias previas.
- Mantener la verificacion sin escritura y sin movimiento de inventario.

Implementado:

- UAT: `storage/uat/uat_almacen_resurtido_recepcion_diferencias_preflight.php`.

Contrato:

- Acepta `--folio=RES-*` o `--id=ID_RESURTIDO`.
- Consulta el folio en modo read-only.
- Consulta contrato `recepcion_diferencias_resurtido_contrato_readonly()`.
- No recibe.
- No registra diferencias.
- No mueve inventario.
- No modifica unidades.

Validaciones:

- Con esquema pendiente devuelve advertencia `RES-REC-PRE-002`.
- Cuando exista esquema, exige estatus `enviado`.
- Exige envios con cantidad enviada mayor a cero.
- Advierte si ya hay recepciones o diferencias.

Decision:

- Este preflight debe ejecutarse despues de `RES-T009` y antes de cualquier recepcion real `RES-T010`.

## RES-T010C - Arnes recibir autorizado

Fecha: 2026-07-13

Objetivo:

- Dejar preparado el contrato de autorizacion para futura prueba real de recepcion/diferencias.
- Evitar ejecuciones accidentales sin token, confirmacion y respaldo.
- Hacer explicito que la implementacion de recepcion sigue pendiente.

Implementado:

- UAT: `storage/uat/uat_almacen_resurtido_recibir_authorized.php`.

Contrato actual:

- Bloqueado por defecto.
- Requiere token `ALMACEN_RESURTIDO_RECIBIR_UAT`.
- Requiere confirmacion textual `AUTORIZO UAT RECIBIR RESURTIDO usando respaldo RUTA_O_REFERENCIA`.
- Requiere respaldo.
- Aun con autorizacion responde `implementacion_pendiente`.
- No recibe, no registra diferencias, no mueve inventario ni modifica unidades.

Decision:

- El arnes no sustituye el backend real `RES-T010`.
- Implementar recepcion solo despues de DDL, respaldo, envio UAT y preflight de recepcion validado.

## RES-T010D - Backend pendiente recibir

Fecha: 2026-07-13

Objetivo:

- Exponer un endpoint/modelo real para la accion futura de recibir en tienda y registrar diferencias.
- Mantenerlo bloqueado por esquema pendiente o implementacion pendiente.
- Evitar que el arnes autorizado quede desconectado del dominio `Almacenes`.

Implementado:

- Controlador: `Almacen::resurtido_recibir_erp()`.
- Modelo: `Almacenes::recibir_resurtido_pendiente()`.
- Validadores internos:
  - `validarFolioResurtidoParaRecibir()`.

Contrato actual:

- Si falta esquema, devuelve `schema_pendiente=1`.
- Si existe esquema pero el folio no es valido, devuelve bloqueos `RES-REC-VAL-*`.
- Si el folio es candidato, devuelve `implementacion_pendiente=1`.
- Siempre devuelve `recibido=0`, `diferencias_registradas=0`, `movimientos_generados=0`.
- No inserta recepcion.
- No inserta diferencias.
- No descuenta transito.
- No entra tienda.
- No modifica unidades fisicas.
- No toca POS/ecommerce.

Validacion:

- `storage/uat/uat_almacen_resurtido_readonly.php` valida que el contrato quede bloqueado y sin movimientos.
- `storage/uat/uat_almacen_resurtido_recibir_authorized.php` consulta este backend despues del token/respaldo.

Decision:

- La implementacion real `RES-T010` debe reemplazar este metodo o evolucionarlo solo despues de DDL aplicado, folio enviado con `RES-T009`, respaldo externo y preflight de recepcion validado.

## RES-T011B - Backend politicas tienda/SKU post-DDL

Fecha: 2026-07-13

Objetivo:

- Dejar listo el carril backend para guardar minimos, maximos, punto de reorden y cantidad sugerida por tienda/SKU.
- Mantenerlo bloqueado antes de DDL y UAT real.
- Separar reglas de resurtido de POS/ecommerce.

Implementado:

- Controlador: `Almacen::resurtido_politica_guardar_erp()`.
- Modelo: `Almacenes::guardar_politica_resurtido_pendiente()`.
- Validador interno: `validarPayloadPoliticaResurtido()`.

Contrato actual:

- Si falta tabla `erp_inventario_politicas_almacen_sku`, devuelve `schema_pendiente=1`.
- Si existe esquema, valida almacen, SKU y cantidades no negativas.
- Si existe esquema, inserta o actualiza la politica por clave `id_almacen + id_sku_erp`.
- Devuelve `guardado=1` solo cuando el upsert se ejecuta correctamente.
- Siempre devuelve `alertas_generadas=0`.
- No genera alertas persistentes.
- No toca POS/ecommerce.

Validacion:

- `storage/uat/uat_almacen_resurtido_readonly.php` valida que, sin DDL, la politica quede bloqueada y sin guardado real.
- `storage/uat/uat_almacen_resurtido_politica_authorized.php` queda listo para UAT real post-DDL con token/respaldo.

## RES-T011C - Arnes politica autorizado

Fecha: 2026-07-13

Objetivo:

- Dejar listo script UAT bloqueado por token/respaldo para guardar politicas tienda/SKU.
- Conectar el script al backend real post-DDL de `Almacenes`.
- Evitar semillas manuales sin guardrails.

Implementado:

- `storage/uat/uat_almacen_resurtido_politica_authorized.php`.

Contrato actual:

- Bloqueado por defecto.
- Requiere token `ALMACEN_RESURTIDO_POLITICA_UAT`.
- Requiere confirmacion textual y respaldo.
- Si falta DDL, el backend responde `schema_pendiente` y no guarda reglas.
- Con DDL aplicado y datos validos, guarda o actualiza politica tienda/SKU.
- No genera alertas persistentes.
- No mueve inventario.
- No toca POS/ecommerce.

## RES-T011A/RES-T012A - Contrato politicas y alertas read-only

Fecha: 2026-07-13

Objetivo:

- Definir politicas tienda/SKU antes de crear datos.
- Definir eventos futuros de alerta sin crear notificaciones persistentes.
- Mantener fallback actual a reglas globales de Catalogo mientras no exista politica local.

Implementado:

- Controlador: `Almacen::resurtido_politicas_alertas_contrato_erp()`.
- Modelo: `Almacenes::politicas_alertas_resurtido_contrato_readonly()`.
- UI: panel `Politicas/alertas` en `Almacen > Resurtido`.
- UAT: `storage/uat/uat_almacen_resurtido_readonly.php` valida contrato.
- Documento vivo: `docs/erp_almacen_resurtido_estados_permisos.md`.

Contrato:

- Tabla futura: `erp_inventario_politicas_almacen_sku`.
- Clave unica: `id_almacen + id_sku_erp`.
- Campos minimos: `stock_minimo`, `punto_reorden`, `prioridad`, `estatus`.
- Campos opcionales: `stock_maximo`, `cantidad_sugerida`, `dias_cobertura_objetivo`, `observaciones`.
- Alertas futuras: stock bajo, stock critico, origen insuficiente y politica faltante.

Validaciones UAT:

- Contrato consultado en modo read-only.
- Politica local reporta `schema_pendiente=1` mientras no exista tabla.
- Campos obligatorios: 6.
- Reglas de formula: 5.
- Eventos de alerta: 4.
- UAT maestro: `ok=true`.

Decision:

- No crear politicas ni alertas hasta DDL/respaldo/autorizacion.
- Cuando se implemente, las alertas deben ser persistentes y respetar permisos; auditoria no sera bandeja de trabajo.

## RES-T013A - Paquete autorizacion/UAT read-only

Fecha: 2026-07-13

Objetivo:

- Preparar la secuencia final para aplicar DDL y crear el primer folio UAT `RES-*`.
- Validar archivos, tokens, comandos y alcance sin ejecutar DDL ni escrituras.
- Documentar evidencia minima por folio/SKU.

Implementado:

- Documento: `docs/erp_almacen_resurtido_paquete_autorizacion_uat.md`.
- UAT preflight: `storage/uat/uat_almacen_resurtido_autorizacion_preflight.php`.
- El preflight valida presencia de scripts read-only, DDL autorizado, folio UAT, preflights RES-T009/RES-T010 y arneses futuros.

Contrato:

- Solo read-only.
- No aplica DDL.
- No crea solicitudes.
- No mueve inventario.
- No toca POS/ecommerce.
- Devuelve tokens y secuencia autorizada para cuando exista respaldo externo.

Secuencia preparada:

- UAT read-only antes de DDL.
- DDL con token `ALMACEN_RESURTIDO_DDL`.
- UAT read-only despues de DDL.
- Folio UAT con token `ALMACEN_RESURTIDO_GUARDAR_UAT`.
- Validacion read-only por folio.
- Preflight read-only de preparacion/envio.
- Preflight read-only de recepcion/diferencias.
- Tokens futuros para arneses `RES-T009D` y `RES-T010C`.

Decision:

- El paquete queda listo para autorizacion humana.
- Cualquier ejecucion real sigue bloqueada hasta respaldo externo y confirmacion textual.

## UAT minimo de cierre

| ID | Caso | Datos requeridos | Resultado esperado |
| --- | --- | --- | --- |
| `RES-UAT-001` | Solicitud tienda | tienda, SKU, cantidad | folio `RES-*` solicitado |
| `RES-UAT-002` | Autorizacion parcial | folio, cantidad autorizada menor | detalle conserva solicitado/autorizado |
| `RES-UAT-003` | Preparacion con lote | existencia origen con lote/caducidad | linea preparada con origen fisico |
| `RES-UAT-004` | Envio a transito | folio preparado | movimientos salida/transito |
| `RES-UAT-005` | Recepcion completa | folio enviado | entrada a tienda y cierre recibido |
| `RES-UAT-006` | Recepcion con faltante | recibir menos | diferencia abierta |
| `RES-UAT-007` | Unidad cerrada | etiqueta/unidad disponible | unidad cambia de almacen conservando codigo |
| `RES-UAT-008` | Unidad abierta | contenido disponible | contenido y estado fisico conservados |
| `RES-UAT-009` | Stock bajo | politica tienda/SKU | necesidad read-only o alerta |
| `RES-UAT-010` | No afectacion POS/ecommerce | stock en transito | no aparece como vendible por canal |

## Primer sprint recomendado

### RES-S1-001 - Diseno de estados/permisos

Resultado:

- cerrar estados validos;
- cerrar transiciones permitidas;
- definir permisos candidatos;
- documentar que acciones quedan ocultas por estado.

### RES-S1-002 - Preflight stock bajo read-only

Resultado:

- consulta que compare existencia disponible por almacen/SKU contra politica local o default global;
- no genera solicitudes;
- no genera notificaciones;
- devuelve necesidad sugerida.

Formula inicial:

```text
si disponible <= punto_reorden:
  cantidad_sugerida = max(stock_maximo - disponible, 0)
si no hay stock_maximo:
  cantidad_sugerida = max(punto_reorden - disponible, 0)
```

### RES-S1-003 - DDL revisable

Resultado:

- revisar `docs/erp_almacen_resurtido_traspasos_schema_propuesta.sql`;
- ajustar nombres/indices antes de tocar `AlmacenEsquema`.

### RES-S1-004 - Auditoria de esquema modo plan

Resultado:

- `AlmacenEsquema` puede reportar faltantes de resurtido sin ejecutar DDL.
- No crear tablas todavia.

## Pendientes de decision

1. Confirmar codigos/nombres finales de almacenes:
   - Acuario.
   - Mascotas.
   - Bodega trasera.
   - Transito.
2. Decidir si `TRANSITO` sera almacen tecnico real o solo estado documental.
3. Definir si tienda puede solicitar cualquier SKU o solo SKUs habilitados para esa tienda.
4. Definir si autorizacion parcial requiere comentario obligatorio.
5. Definir si envio puede mezclar lotes en una misma linea o debe partir detalle por lote.

Recomendacion inicial:

- usar almacen tecnico `TRANSITO` real;
- partir detalle por lote/caducidad/unidad cuando se prepare;
- no mezclar lotes en una misma linea recibida;
- comentario obligatorio en autorizacion parcial, rechazo y diferencia.
