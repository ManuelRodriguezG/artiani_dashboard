# ERP Comercial - Listas de precios

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-16  
Estado: decision viva de arquitectura; no implica DDL ni escrituras en BD.

## Principio rector

Listas de precios no debe depender de asignar clientes uno por uno. Esa opcion debe existir, pero como excepcion comercial o trato negociado. El camino escalable para miles de clientes es asignar listas a segmentos/tipos de cliente y dejar que el resolutor backend elija el precio por prioridad, vigencia, canal, almacen y elegibilidad.

## Modelo comercial recomendado

La administracion de listas debe soportar estos niveles:

1. Lista general ERP.
2. Lista por canal y, cuando aplique, almacen.
3. Lista por segmento/tipo de cliente CRM.
4. Lista default del cliente CRM.
5. Lista asignada directamente a cliente CRM.
6. Precio manual autorizado en venta como excepcion.

El cliente individual no debe usarse para resolver todos los casos recurrentes, porque no escala cuando existan miles de clientes. Debe reservarse para convenios especiales, mayoristas concretos, cuentas clave o excepciones autorizadas.

## Segmentos/tipos de cliente

CRM ya contempla segmentos mediante:

- `crm_clientes_segmentos`
- `crm_clientes_segmentos_rel`
- `crm_clientes_maestro.id_segmento_default`

Listas de precios debe apoyarse en ese modelo para casos como:

- publico general;
- frecuente;
- recurrente;
- mayoreo;
- instalador;
- acuarista frecuente;
- cuenta comercial;
- cliente VIP autorizado;
- ecommerce registrado;
- convenio especial.

Los nombres finales deben definirse operativamente, pero la arquitectura debe permitir crearlos, pausarlos, auditarlos y asignarlos sin tocar ventas pasadas.

## Tabla futura sugerida

Cuando se autorice DDL, crear o validar una tabla equivalente a:

- `erp_segmentos_listas_precios`

Campos sugeridos:

- `id_segmento_lista_precio`
- `id_segmento_crm`
- `id_lista_precio`
- `canal`
- `id_almacen`
- `prioridad`
- `fecha_inicio`
- `fecha_fin`
- `estatus`
- `motivo`
- `creado_por`
- `fecha_registro`
- `actualizado_por`
- `fecha_actualizacion`

Debe tener auditoria comercial similar a cliente-lista, porque cambiar una lista por segmento puede afectar a muchos clientes.

## Plan tecnico preparado

Estado 2026-07-16:

- Auditoria read-only: `/comercial/esquema_auditar_segmentos_listas_precios`
- Plan DDL protegido: `/comercial/esquema_actualizar_segmentos_listas_precios`
- Token requerido para ejecutar: `VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL`
- Requiere respaldo externo valido antes de ejecutar.
- Auditoria CLI read-only ejecutada: CRM segmentos existe, CRM segmentos relacion existe, `erp_listas_precios` existe, `erp_segmentos_listas_precios` no existe.
- Plan DDL generado sin ejecutar; crea solo `erp_segmentos_listas_precios` con indices por segmento, lista, alcance y vigencia.
- Resolutor backend preparado de forma defensiva: si la tabla puente existe y el cliente tiene `id_segmento_default`, puede devolver `regla_precio_origen=lista_segmento_cliente`; si la tabla no existe, conserva el flujo actual.
- UAT read-only `storage/uat/uat_listas_precios_segmentos_readonly.php 2` ejecutado: no hay segmentos CRM activos visibles; dry-run de asignacion queda bloqueado por falta de tabla puente y segmento activo.
- Catalogo inicial sugerido documentado en `docs/erp_listas_precios_segmentos_catalogo_inicial.md`.
- Segmentos base quedan como catalogo CRM configurable, no hardcodeado. Endpoints preparados: listar, dry-run y guardado autorizado con token `CRM_CLIENTES_SEGMENTO_CATALOGO`.
- UAT read-only `storage/uat/uat_crm_segmentos_catalogo_readonly.php` ejecutado: no hay segmentos actuales y los 7 segmentos base sugeridos pasan dry-run sin bloqueos.
- Comercial/Listas ya expone seleccion y validacion de segmentos CRM desde la vista operativa. El guardado de vinculo segmento/lista queda preparado en backend y UI, pero bloquea mientras falte `erp_segmentos_listas_precios`.
- Endpoint preparado para guardado futuro de vinculo segmento/lista: `/comercial/listas_precios_segmento_guardar_operativo_erp`. No crea segmentos, no asigna clientes y no modifica ventas pasadas.
- La consulta de lista ya devuelve `asignaciones_segmentos` y `schema.segmentos_listas`; antes del DDL la UI muestra el estado pendiente sin romper el flujo.
- La UI mantiene deshabilitado el boton de guardar vinculo segmento/lista cuando `schema.segmentos_listas=false`; permite dry-run, pero no guardado real hasta aplicar el DDL autorizado.
- La UI muestra una preparacion segura de segmentos con tres estados: catalogo CRM, relacion cliente/segmento y puente segmento/lista. El puente debe aparecer pendiente hasta aplicar el DDL.
- Revision de lista ya contempla conteo de segmentos activos y advierte que listas de `mayoreo`/`ecommerce` deben vincularse por segmento cuando exista el puente.
- UAT read-only `storage/uat/uat_listas_precios_segmentos_readonly.php 2` valida auditoria, SQL planeado, consulta de lista, segmentos candidatos y dry-run sin ejecutar DDL.
- Respaldo externo generado para la fase: `C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql`.
- Scripts protegidos preparados:
  - `storage/uat/uat_crm_segmentos_catalogo_apply_authorized.php` para sembrar segmentos base con token `CRM_CLIENTES_SEGMENTO_CATALOGO`.
  - `storage/uat/uat_listas_precios_segmentos_schema_apply_authorized.php` para crear `erp_segmentos_listas_precios` con token `VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL`.
  - `storage/uat/uat_listas_precios_segmento_vinculo_apply_authorized.php` para vincular lista/segmento con token `VENTAS_LISTAS_PRECIOS_SEGMENTO_ASIGNAR_REAL`.
- Los scripts protegidos fueron validados en modo bloqueado; con token incorrecto no ejecutan escritura.
- La siembra de segmentos base es idempotente: si el codigo ya existe, carga `id_segmento_crm` y actualiza en vez de duplicar.
- Verificador read-only preparado: `storage/uat/uat_listas_precios_segmento_resolutor_readonly.php 2 RECURRENTE 1760 5`.
- Candidatos cliente/segmento read-only preparado: `storage/uat/uat_crm_cliente_segmento_candidatos_readonly.php RECURRENTE 10`.
- Apply protegido para asignar cliente a segmento preparado: `storage/uat/uat_crm_cliente_segmento_apply_authorized.php` con token `CRM_CLIENTES_SEGMENTO`; actualiza `id_segmento_default` si se solicita y no toca listas ni ventas.
- Suite read-only de preparacion completa: `storage/uat/uat_listas_precios_segmentos_suite_readonly.php 2 RECURRENTE 1760 5`.
- Estado actual de la suite: lista UAT 2 existe con SKU 1760 y cliente candidato 2 existe; faltan segmento `RECURRENTE` y tabla puente `erp_segmentos_listas_precios`.
- Paquete read-only de autorizacion preparado: `storage/uat/uat_listas_precios_segmentos_autorizacion_paquete_readonly.php`. Valida respaldo, confirma estado actual y devuelve los comandos en orden sin ejecutarlos.
- Paquete read-only validado con respaldo `C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql`: respaldo existe, cliente UAT 1 existe, lista 2 existe, faltan segmento `RECURRENTE` y tabla puente.
- El paquete read-only de autorizacion ahora incluye `verificaciones_post_apply`: acceptance, baseline de ventas y suite consolidada.
- Plan de reversa documentado: `docs/erp_listas_precios_segmentos_plan_reversa.md`.
- Preflight de reversa read-only preparado: `storage/uat/uat_listas_precios_segmentos_reversa_preflight_readonly.php`; estado actual `reversa_no_aplica_sin_tabla`.
- Runbook operativo de activacion documentado: `docs/erp_listas_precios_segmentos_runbook_activacion.md`.
- Readiness read-only del runbook preparado y validado: `storage/uat/uat_listas_precios_segmentos_runbook_readiness_readonly.php`; estado `ok=true`, sin archivos ni tokens faltantes.
- UAT read-only de prioridad del resolutor preparado: `storage/uat/uat_listas_precios_prioridad_resolutor_readonly.php`. Estado actual con cliente UAT 1, SKU 1760, almacen 5, canal POS: gana `lista_cliente` por asignacion directa activa a lista 2; esto es correcto y tiene prioridad sobre segmento.
- UAT read-only de cliente puro para segmento preparado: `storage/uat/uat_listas_precios_segmento_cliente_puro_readonly.php`. Estado actual: cliente CRM `2` es candidato recomendado para probar `lista_segmento_cliente` porque no tiene lista directa activa ni lista default.
- Semaforo go/no-go read-only preparado: `storage/uat/uat_listas_precios_segmentos_go_nogo_readonly.php`. Estado actual: `GO_PARA_PEDIR_AUTORIZACION`; respaldo, lista 2, cliente puro 2 y resolutor previo estan correctos. Pendientes esperados: sembrar segmento `RECURRENTE` y crear `erp_segmentos_listas_precios`.
- Acceptance post-apply read-only preparado: `storage/uat/uat_listas_precios_segmentos_post_apply_acceptance_readonly.php`. Estado actual antes del apply: `PENDIENTE_O_FAIL` esperado por falta de segmento, tabla puente, vinculo y cliente segmentado. Despues del apply debe devolver `PASS_POST_APPLY`.
- Guardrails negativos de apply documentados: `docs/erp_listas_precios_segmentos_guardrails_apply.md`. Los 4 scripts protegidos fueron probados sin argumentos y quedaron en `modo=bloqueado`.
- Baseline read-only de impacto en ventas preparada: `storage/uat/uat_listas_precios_segmentos_ventas_impacto_readonly.php`. Estado actual: `erp_ventas.total=22`, `erp_ventas.max_id=25`, `erp_ventas_detalle.total=23`, `erp_ventas_detalle.max_id=26`; el apply de segmentos no debe cambiar esos valores ni snapshots historicos.
- Comparador read-only de baseline de ventas preparado: `storage/uat/uat_listas_precios_segmentos_ventas_baseline_compare_readonly.php`. Estado actual con baseline `22/25/23/26`: `BASELINE_VENTAS_INTACTA`; debe repetirse despues del apply.
- Suite consolidada post-apply read-only preparada: `storage/uat/uat_listas_precios_segmentos_post_apply_suite_readonly.php`. Estado actual antes del apply: `PENDIENTE_O_FAIL_SUITE_POST_APPLY`, con `BASELINE_VENTAS_INTACTA` y acceptance pendiente por falta de segmento/tabla/vinculo. Despues del apply debe devolver `PASS_SUITE_POST_APPLY`.
- Validacion final preautorizacion 2026-07-17: `uat_listas_precios_segmentos_go_nogo_readonly.php` devuelve `GO_PARA_PEDIR_AUTORIZACION`; `uat_listas_precios_segmentos_runbook_readiness_readonly.php` devuelve `ok=true`; el paquete de autorizacion devuelve los 4 comandos en orden y las 3 verificaciones post-apply sin ejecutar escritura.
- Apply autorizado ejecutado 2026-07-17:
  - segmentos CRM base sembrados/actualizados: `PUBLICO_GENERAL`, `RECURRENTE`, `MAYOREO`, `VIP`, `INSTALADOR`, `CONVENIO`, `ECOMMERCE_REG`;
  - tabla `erp_segmentos_listas_precios` creada con indices de segmento, lista, alcance y vigencia;
  - lista `2` vinculada al segmento `RECURRENTE` para canal `pos`, almacen `5`, prioridad `100`;
  - cliente CRM `2` asignado a `RECURRENTE` y `id_segmento_default=2`.
- Cierre post-apply 2026-07-17: `uat_listas_precios_segmentos_post_apply_suite_readonly.php --id_lista_precio=2 --codigo_segmento=RECURRENTE --id_cliente_crm=2 --id_sku=1760 --id_almacen=5 --canal=pos --ventas_total=23 --ventas_max_id=26 --detalle_total=24 --detalle_max_id=27` devuelve `PASS_SUITE_POST_APPLY`.
- Resolutor validado:
  - cliente CRM `2`, SKU `1760`, almacen `5`, canal `pos`: `regla_precio_origen=lista_segmento_cliente`, `id_lista_precio=2`, snapshot `Lista UAT borrador`, precio aplicado `315`;
  - cliente CRM `1` conserva prioridad `lista_cliente` por asignacion directa activa a lista `2`.
- Nota de baseline: antes del primer apply autorizado (`22:23:40`) ya existia la venta `POS-20260717-000002` a las `22:20:20`; por eso el baseline vigente para cierre quedo en `erp_ventas.total=23`, `erp_ventas.max_id=26`, `erp_ventas_detalle.total=24`, `erp_ventas_detalle.max_id=27`.
- UI operativa 2026-07-18: `Comercial > Listas de precios` ya muestra segmentos como activos, oculta el ID tecnico en la captura principal, permite seleccionar segmento por tarjeta, limpiar el formulario de vinculo y operar vinculos existentes con cargar/pausar/activar/cancelar. El boton guardar solo queda bloqueado si el schema de segmentos/listas no esta disponible.
- UAT UI read-only 2026-07-18:
  - `storage/uat/uat_listas_precios_segmentos_ui_readiness_readonly.php` devuelve `PASS_UI_SEGMENTOS_OPERATIVA`;
  - `storage/uat/uat_listas_precios_segmentos_estatus_ui_dryrun_readonly.php` devuelve `PASS_ESTATUS_UI_DRYRUN` para pausar, activar y cancelar el vinculo `RECURRENTE/lista 2` sin escribir BD.
- CRM operativo 2026-07-18: la pantalla `CRM > Clientes` expone la seccion `Tipos de cliente` como duena del catalogo de segmentos. Comercial/Listas no crea tipos de cliente; solo vincula listas a segmentos ya existentes. POS sigue consumiendo el resolutor backend.
- UI CRM 2026-07-18:
  - permite crear/limpiar captura de tipo de cliente;
  - permite cargar un tipo existente;
  - permite preparar pausar/activar/cancelar como dry-run antes del guardado autorizado;
  - envia CSRF en los POST de catalogo de segmentos.
- UAT CRM read-only 2026-07-18:
  - `storage/uat/uat_crm_segmentos_catalogo_ui_readiness_readonly.php` devuelve `PASS_CRM_SEGMENTOS_UI`;
  - `storage/uat/uat_crm_segmentos_catalogo_estatus_dryrun_readonly.php` devuelve `PASS_CRM_SEGMENTOS_ESTATUS_DRYRUN` para `RECURRENTE` sin escribir BD.
- Mesa de productos 2026-07-18: `Comercial > Listas de precios` ya permite seleccionar SKUs visibles, ver conteo de seleccionados y elegir si las acciones masivas aplican a `seleccionados` o `visibles`.
- Acciones masivas protegidas en UI:
  - calcular precios sugeridos por margen objetivo y redondeo sin tocar inputs;
  - aplicar precios sugeridos como cambios pendientes solo cuando el usuario lo confirma;
  - aplicar margen objetivo;
  - copiar precio general;
  - copiar precios desde otra lista;
  - redondear precios.
- La opcion recomendada para operacion diaria es trabajar con `seleccionados`, dejando `visibles` para cambios controlados despues de filtrar por busqueda/estatus.
- Exportacion operativa 2026-07-18: la mesa puede generar CSV de productos visibles con SKU, producto, unidad, costo, precio general, precio de lista, sugerido y margen estimado. Es solo salida local de navegador; no importa datos ni escribe BD.
- Importacion operativa 2026-07-18: la mesa puede cargar un CSV con columnas `id_sku` o `sku` y `precio_lista`/`precio`, prevalidarlo contra productos visibles y aplicar los precios solo como cambios pendientes en pantalla. El guardado real sigue usando `/comercial/listas_precios_detalles_lote_guardar_operativo_erp`, permisos, validaciones y auditoria por partida.
- Prevalidacion backend de lote 2026-07-18: antes de guardar cambios masivos, la UI llama `/comercial/listas_precios_detalles_lote_dryrun_erp`. El endpoint valida el mismo payload que se guardara, detecta errores de detalle, duplicados dentro del lote, precios invalidos y resume margen/perdida/sin costo sin escribir BD.
- Revision de activacion 2026-07-18: el backend bloquea listas con vigencia vencida, ecommerce sin segmento/cliente explicito y productos con perdida. La UI tambien bloquea activacion si hay cambios sin guardar y advierte si existen sugeridos/importaciones CSV pendientes de aplicar.
- Auditoria operativa 2026-07-18: la bitacora visible de `Comercial > Listas de precios` filtra por lista/precio/segmento/cliente, muestra eventos con etiqueta, tipo, usuario, motivo y resumen corto de campos modificados. El backend enriquece eventos y oculta JSON crudo de `datos_antes/datos_despues` para la UI operativa.
- Historial por SKU 2026-07-18: cada producto con precio guardado puede abrir su historial de auditoria filtrado por `id_lista_precio_detalle`. Esto permite ver cambios de precio, usuario, motivo y campos modificados sin buscar manualmente en la bitacora general.
- Comparador de listas 2026-07-19: la mesa permite capturar una lista origen, comparar sus precios contra los productos visibles de la lista actual y revisar iguales, diferentes, faltantes, diferencia monetaria, porcentaje y margen estimado del precio origen antes de copiar. La accion `Usar diferencias` solo modifica inputs como cambios pendientes; el guardado real sigue pasando por prevalidacion backend, permisos y auditoria.
- Filtros operativos de margen 2026-07-19: productos permite separar `sin precio`, `sin costo`, `perdida` y `margen bajo` con umbral configurable desde la mesa. El endpoint `/comercial/listas_precios_productos_erp` recibe `margen_minimo` y etiqueta el riesgo con ese umbral, para que la revision visual coincida con la consulta backend.
- Revision comercial local 2026-07-19: el panel de revision combina la revision backend de activacion con un semaforo de los productos visibles en pantalla: con precio, sin precio, sin costo, perdida y margen bajo segun el umbral configurado. Los bloqueos detienen activacion; los avisos quedan visibles para decision operativa.
- Revision accionable 2026-07-19: cuando la revision comercial local detecta pendientes, muestra acciones rapidas para saltar a filtros `sin_precio`, `sin_costo`, `perdida` o `margen_bajo`. La accion solo cambia el filtro de la mesa y consulta productos; no guarda ni modifica precios.
- Sugeridos por pendientes 2026-07-19: la mesa permite calcular y aplicar precios sugeridos solo a productos visibles con pendiente comercial (`sin precio`, `perdida` o `margen bajo`). No aplica a productos ya correctos y no guarda BD; deja cambios pendientes para prevalidacion backend y guardado auditado.
- Edicion asistida por fila 2026-07-19: cada SKU visible muestra el motivo del pendiente comercial y permite aplicar un sugerido solo a esa fila. Si el sugerido no existe, se calcula con el margen objetivo y redondeo activos. La accion solo cambia la pantalla y conserva el guardado auditado posterior.
- Guardado masivo seguro 2026-07-19: antes de guardar lote, la UI muestra la prevalidacion backend con total, validos, errores, perdida, margen bajo, sin costo y OK margen. Los errores y precios con perdida bloquean el guardado; los avisos comerciales requieren confirmacion del usuario.
- Prevalidacion manual 2026-07-19: la mesa permite ejecutar `Prevalidar cambios` sin guardar BD ni abrir confirmaciones de persistencia. El usuario revisa el resumen y luego `Guardar cambios` vuelve a ejecutar el dry-run antes de persistir.
- Flujo guiado 2026-07-19: la pantalla muestra un mapa operativo de cinco pasos (`Encabezado`, `Productos`, `Alcance`, `Asignacion`, `Revision`) con estado real y accesos rapidos. Es guia de trabajo, no regla de precios; las reglas siguen en backend.
- Asignacion clara 2026-07-19: la UI separa `Segmentos CRM` como camino recomendado y `Excepcion por cliente` como acuerdo puntual. Esto evita operar cliente por cliente cuando existan miles de clientes y mantiene la prioridad por segmento como ruta normal.
- Alcance y prioridad 2026-07-19: la UI explica que el alcance solo define donde compite la lista, mientras que cliente/segmento se asignan aparte. Tambien muestra la guia del resolutor: cliente directo, lista default CRM, segmento CRM, canal/almacen y general ERP; dentro del mismo nivel gana la prioridad menor.
- Mesa de productos 2026-07-19: la tabla mantiene encabezado visible durante scroll y agrega una barra accionable de cambios pendientes con acceso a modificados, prevalidacion y limpieza. El resumen muestra riesgos de perdida, margen bajo y sin costo sin guardar BD.
- Resultado de guardado por lote 2026-07-20: despues de guardar precios por lote, la UI muestra resumen operativo con primeros SKUs guardados y errores por fila/SKU. El backend devuelve `guardados_detalle` acotado para la vista sin reemplazar auditoria formal.
- Manual operativo 2026-07-20: se agrego `/comercial/listas_precios_manual` y acceso en ERP > Comercial. El manual declara fase 1 como lista para piloto POS controlado, con ecommerce sujeto a contrato de exposicion y granel/presentaciones como fase posterior.
- Semaforo de entrega 2026-07-20: se agrego `/comercial/listas_precios_fase1_readiness_erp` y un panel de arranque en Comercial/Listas. Evalua esquema base, auditoria comercial, asignacion CRM, snapshot en venta, resolutor backend, POS visible, mesa operativa y manual. Ecommerce y granel/presentaciones quedan como fase 2 y no bloquean piloto POS controlado.
- UAT previo a venta POS 2026-07-20: se agrego `storage/uat/uat_listas_precios_piloto_pos_readonly.php`. Permite probar lista/SKU/cliente/almacen/canal contra el resolutor, validar origen esperado, precio mayor a cero, snapshot y que el dry-run no cree ventas ni detalles. UAT actual con lista 2, SKU 1760, cliente CRM 2, almacen 5 y canal POS devuelve `PASS_PILOTO_POS_LISTAS_PRECIOS`.
- UAT post-venta POS 2026-07-20: se agrego `storage/uat/uat_listas_precios_pos_venta_snapshot_readonly.php`. Despues de una venta piloto real, valida por folio o id_venta que `erp_ventas_detalle` conserve `id_lista_precio`, `lista_precio_snapshot`, `regla_precio_origen` y precio persistido. La pantalla Detalle venta POS tambien muestra origen y snapshot de precio por partida.
- Estructura UI 2026-07-20: `/comercial/listas_precios` queda como listado/portada read-only con KPIs, semaforo, filtros y acciones claras. Crear usa `/comercial/listas_precios_nueva`; editar usa `/comercial/listas_precios_editar?id_lista_precio=ID`. La mesa grande se mantiene solo como editor de una lista concreta.
- Editor por secciones 2026-07-20: el editor se dividio en pestanas internas `Encabezado`, `Productos`, `Alcance`, `Clientes/Segmentos` y `Revision`. El flujo guiado cambia a la pestana correspondiente, y solo se muestra una seccion principal a la vez para evitar que el operador pierda contexto.

Reglas del apply:

- No crea segmentos CRM.
- No asigna listas a segmentos.
- No modifica ventas pasadas.
- Solo prepara la tabla puente `erp_segmentos_listas_precios`.

## Prioridad v1 ajustada

Orden recomendado del resolutor backend:

1. Precio manual autorizado en POS, con permiso, motivo y auditoria.
2. Lista asignada directamente al cliente CRM.
3. Lista default del cliente CRM.
4. Lista por segmento/tipo de cliente CRM.
5. Lista por canal/almacen.
6. Lista general ERP.
7. Fallback temporal a `erp_catalogo_sku_precios` lista `general`.

Promociones, cupones y recompensas quedan fuera de esta fase.

## Reglas de autorizacion

Asignar una lista mejor a un segmento debe requerir permiso y motivo cuando:

- baja margen esperado;
- aplica a muchos clientes;
- aplica a ecommerce;
- tiene prioridad alta;
- sustituye una lista vigente;
- usa vigencia abierta sin fecha fin.

El sistema debe permitir que un cliente suba de segmento por autorizacion futura, por ejemplo de `recurrente` a `vip`, sin cambiar ventas pasadas. Las ventas confirmadas deben conservar snapshot de lista, origen y precio aplicado.

## UI recomendada

La pantalla de Listas de precios debe evolucionar a tres formas de alcance:

- Alcance por canal/almacen.
- Alcance por segmento/tipo de cliente.
- Excepcion por cliente especifico.

La asignacion por cliente debe presentarse visualmente como excepcion, no como el mecanismo principal.

## UAT futuro

Casos minimos:

- Cliente sin segmento usa lista canal/almacen o general.
- Cliente con segmento `recurrente` usa lista del segmento.
- Cliente con lista directa gana sobre segmento.
- Cliente con `id_lista_precio_default` gana sobre segmento si la prioridad definida lo indica.
- Cambio posterior de segmento no modifica ventas pasadas.
- Pausar lista por segmento hace caer a canal/almacen o general.
- Ecommerce no consume listas internas POS si no hay regla explicita.
