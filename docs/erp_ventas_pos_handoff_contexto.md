# ERP Ventas/POS/Pedidos - Handoff de contexto vivo

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-27  
Estado: documento de continuidad para evitar perdida de contexto.

## Proposito

Mantener en un solo lugar el contexto operativo del modulo Ventas/POS/Pedidos conforme avance el trabajo. Si la conversacion se compacta, este archivo debe leerse junto con:

- `docs/erp_ventas_pos_pedidos_arranque.md`
- `docs/erp_ventas_pos_clientes_plan.md`
- `docs/erp_ventas_pos_autorizacion_pendiente.md`
- `docs/erp_ventas_pos_base_runbook_aplicacion.md`
- `docs/erp_ventas_pos_base_plan_reversa.md`
- `docs/erp_ventas_pos_base_solicitud_autorizacion.md`
- `docs/erp_ventas_pos_uat_manual.md`
- `docs/erp_ventas_pos_evidencia_uat.md`

## Principios no negociables

- No tomar sugerencias operativas del usuario como especificacion literal si afectan arquitectura ERP.
- Interpretar la necesidad de fondo y proponer una solucion robusta.
- No escribir BD sin respaldo externo y autorizacion textual explicita.
- POS debe operar por usuario, sucursal, almacen, caja y turno.
- POS no debe seleccionar libremente tienda/caja en operacion real; debe cargar asignacion oficial.
- Venta real debe generar folio, pagos, kardex, trazabilidad y afectar inventario en una sola transaccion.
- Ecommerce queda fuera de cambios productivos por ahora; solo se documenta impacto.
- El cliente rapido en POS es una entrada express, no el modelo final de clientes.

## Estado actual del modulo

### Implementado sin escribir BD

- UI POS nueva en `app/vistas/paginas/apps/erp/ventas/pos.php`.
- JS POS nuevo en `public/assets/js/custom/apps/erp/ventas/pos.js`.
- Tablero/listado ERP de ventas en `app/vistas/paginas/apps/erp/ventas/listado.php`.
- Modelo `app/modelos/VentasErp.php` con prevalidaciones, diagnosticos, disponibilidad, dry-runs y contratos.
- Modelo `app/modelos/VentasErpEsquema.php` con plan DDL para `erp_pos*` y `erp_ventas*`.
- Scripts UAT/read-only y aplicadores autorizados con guardrails en `storage/uat`.
- Manual de cajero, UAT manual, plantilla de evidencia y autorizacion pendiente.
- Dry-run de cliente/precio y abono de apartado:
  - `/ventas/pos_cliente_precio_dryrun_erp`
  - `/ventas/apartado_abono_dryrun_erp`
  - `storage/uat/uat_ventas_pos_cliente_precio_apartado_readonly.php`

### UX POS actual

- Productos arriba con scroll horizontal compacto.
- Carrito abajo a pantalla completa en tabla.
- Cuentas en atencion: varias pestanas/carritos locales para atender diferentes clientes al mismo tiempo.
- Modo de salida con botones `Stock`, `Pieza`, `Granel`.
- Granel usa input de peso directo.
- Pieza usa stepper `-` / input / `+`.
- Pagos con botones rapidos: `Efectivo`, `Tarjeta`, `Transferencia`.
- Sin boton generico duplicado `Pago`.
- Playwright contra ruta real redirige a login sin sesion; se valido maqueta local con estilos POS.
- Modal `Caja` simula movimientos no venta.
- Modal `Corte` simula cierre de turno.
- Modal `Atenciones` simula bandeja persistente y compartir cuenta actual.

Limitacion de cuentas en atencion:

- Son locales al navegador/usuario.
- No escriben BD.
- No reservan inventario.
- No son pedidos ni apartados hasta que se confirme con flujo autorizado.
- Futuro robusto: persistir como borradores de atencion por terminal/turno para continuidad entre usuarios, dispositivos y caja.

### Estado tecnico 2026-06-26

- Se agrego DDL propuesto para `erp_pos_atenciones`, `erp_pos_atenciones_detalle` y `erp_pos_atenciones_pagos_temporales`.
- Estas tablas pertenecen al alcance expandido; no son necesarias para las cuentas locales actuales.
- Con MySQL activo, `uat_ventas_pos_paquete_autorizacion_dryrun.php --id_usuario=1 --alcance=base` devuelve `ok=true`, `ddl_total=11`, `seed_cajas_total=2`, `seed_terminales_total=2` y `seed_asignaciones_total=2`.
- Con `--alcance=expandido` devuelve `ddl_total=21`.
- Cajas propuestas: `CJ-ACUARIO967-01` para almacen `4` y `CJ-MASCOTAS971-01` para almacen `5`.
- Terminales propuestas: `TERM-ACUARIO967-01` y `TERM-MASCOTAS971-01`.
- Asignacion UAT propuesta: `id_usuario=1` para ambas sucursales.
- El aplicador de DDL acepta `VENTAS_POS_DDL_BASE` para POS/caja/ventas/devoluciones.
- El aplicador de DDL acepta `VENTAS_POS_DDL_EXPANDIDO` solo si se autoriza incluir clientes, listas, atenciones POS, eventos y politicas de apartado.
- El token anterior `VENTAS_POS_DDL` queda bloqueado aunque el respaldo sea valido.
- Recomendacion vigente: aplicar primero alcance base, probar caja/turno/venta/kardex, y despues pasar a clientes/listas/apartados.
- `storage/uat/uat_ventas_pos_base_autorizacion_preflight_readonly.php --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1` devuelve `ok=true`, `base_total=11`, `expandido_total=21`, `cajas_total=2`, `terminales_total=2`, `asignaciones_total=2` y `bloqueos=[]`.
- `Ventas::esquema_actualizar_ventas_pos()` tambien bloquea DDL web si falta token exacto y respaldo valido.
- `VentasErpEsquema::planActualizarVentasPos()` y `auditarVentasPos()` usan alcance `base` por defecto.
- `storage/uat/uat_ventas_pos_base_compatibilidad_readonly.php` devuelve `ok=true`; Catalogo/Inventario tienen las tablas y columnas requeridas por venta POS base, kardex y trazabilidad.
- `storage/uat/uat_ventas_pos_base_readiness_suite_readonly.php --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1` devuelve `ok=true`; consolida respaldo, paquete base, preflight base, compatibilidad y post-config pre-DDL.
- `storage/uat/uat_ventas_pos_guardrails_readonly.php` devuelve `ok=true`; DDL, semillas, turno y venta real quedan bloqueados sin autorizacion valida.

### BD UAT aplicado con autorizacion

- DDL base POS/Ventas/Pedidos aplicado con respaldo externo autorizado.
- Tablas base creadas:
  - `erp_pos_cajas`
  - `erp_pos_terminales`
  - `erp_pos_usuarios_cajas`
  - `erp_pos_turnos`
  - `erp_pos_movimientos_caja`
  - `erp_ventas`
  - `erp_ventas_detalle`
  - `erp_ventas_detalle_inventario`
  - `erp_ventas_pagos`
  - `erp_ventas_devoluciones`
  - `erp_ventas_devoluciones_detalle`
- Semillas POS base aplicadas: 2 cajas, 2 terminales y 2 asignaciones para `id_usuario=1`.
- Turno UAT abierto: `TUR-20260626-002-001`, `id_turno_caja=1`, `id_caja=2`, `id_almacen=5`, fondo inicial `500`.
- Stock UAT cargado para SKU `1760` en almacen `5`: existencia `EXI-1016-34`, cantidad inicial `2`.
- Primera venta real UAT ejecutada: folio `POS-20260626-000001`, `id_venta=1`, total `295`, pago efectivo `295`, movimiento caja `2`, movimiento kardex `53`, existencia final `1`.
- Validacion post-venta por folio: `ok=true`, `hallazgos=[]`.
- Cierre de turno dry-run ejecutado con contado `795`:
  - esperado por turno `795`
  - esperado por movimientos `795`
  - diferencia `0`
  - sin bloqueos
  - no escribe BD.
- UI POS tiene modal `Corte` para simular cierre con monto contado y resumen del turno.
- UI POS tiene modal `Cliente/precios` para simular resolucion de cliente/lista/precio desde backend.
- Dry-run cliente/precio UAT con `id_almacen=5`, `id_sku=1760`, identificador `5550000000`:
  - cliente `publico_general`;
  - requiere alta rapida futura;
  - SKU `TP-40352-500GR`;
  - precio base/aplicado `295`;
  - lista `general`;
  - no escribe BD.
- Auditoria expandida read-only previa con `id_usuario=1`:
  - asignacion POS activa;
  - modo UI `asignacion_oficial`;
  - faltaban 10 tablas: eventos, politicas de apartado, clientes, identificadores, listas, detalles, relacion cliente-lista y atenciones persistentes.
- DDL expandido aplicado el 2026-06-27 con autorizacion del dueno:
  - tablas pendientes posteriores: ninguna;
  - `schema_clientes_pendiente=false`;
  - `schema_listas_precios_pendiente=false`;
  - `schema_pendiente=false` en atenciones;
  - bandeja de atenciones queda vacia y lista para siguiente implementacion;
  - cliente/precio sigue sin crear clientes reales hasta semilla/flujo autorizado.
- Semillas expandidas preparadas en modo read-only:
  - cliente `CL-UAT-POS-001`;
  - telefono `5550000000`;
  - lista `LP-UAT-POS`;
  - politica `POS_APARTADO_UAT`;
  - SKU `1760` precio `295`;
  - aplicador bloqueado por token `VENTAS_POS_SEED_EXPANDIDO`.
- Semillas expandidas ejecutadas el 2026-06-27 con autorizacion:
  - `id_cliente=1`;
  - `id_lista_precio=1`;
  - conteos de clientes/listas/politicas en `1`;
  - telefono `5550000000` resuelve `Cliente UAT POS`;
  - SKU `1760` resuelve `regla_precio_origen=lista_cliente`;
  - `requiere_alta_rapida=false`.
- Atencion persistente real preparada pero no ejecutada:
  - metodo `VentasErp::crearAtencionPersistente()`;
  - script `storage/uat/uat_ventas_pos_atencion_persistente_apply_authorized.php`;
  - token requerido `VENTAS_POS_ATENCION_REAL`;
  - dry-run UAT con SKU `1760`, cantidad `1`, precio `295`, sin bloqueos;
  - guardrail validado sin token;
  - no crea venta, pagos, reservas ni movimientos de inventario.
- Atencion persistente real ejecutada el 2026-06-27 con autorizacion:
  - `id_atencion_pos=1`;
  - folio temporal `ATN-20260627-094828-088`;
  - estatus `lista_para_cobro`;
  - cliente `Cliente UAT POS`;
  - SKU `1760`;
  - total `295`;
  - visible en bandeja;
  - ventas/pagos/caja/inventario sin nuevos movimientos.
- Conversion/cobro de atencion preparado:
  - script read-only `storage/uat/uat_ventas_pos_atencion_convertir_preflight_readonly.php`;
  - `storage/uat/uat_ventas_pos_venta_apply_authorized.php` acepta `--id_atencion=1`;
  - preflight `ok=true`, sin bloqueos;
  - al autorizar consumira el ultimo disponible del SKU `1760` y marcara la atencion como `convertida`;
  - guardrail validado sin `VENTAS_POS_VENTA_REAL`.
- Conversion/cobro ejecutado el 2026-06-27:
  - `id_venta=2`;
  - folio `POS-20260627-000001`;
  - atencion `1` quedo `convertida`;
  - salida inventario `id_movimiento_inventario=54`;
  - existencia `34` quedo en `0`;
  - pago `id_venta_pago=2`;
  - movimiento caja `id_movimiento_caja=3`;
  - corte dry-run esperado `1090`, diferencia `0`.
- Hallazgo `VENTAS-POS-UAT-001`:
  - venta cobrada correctamente con kardex/caja, pero el script UAT previo no lleno snapshot de lista/precio en detalle;
  - el aplicador fue corregido para futuras ventas;
  - no ajustar la venta historica sin autorizacion especifica.
- Cierre de turno preparado post-atencion:
  - dry-run con monto contado `1090`;
  - esperado `1090`;
  - diferencia `0`;
  - ventas `2`;
  - total vendido/pagado `590`;
  - movimientos: monto inicial `500` + ingresos POS `590`;
  - guardrail validado sin `VENTAS_POS_TURNO_CIERRE`.
- Script de cierre real preparado y bloqueado por defecto:
  - `storage/uat/uat_ventas_pos_turno_cierre_apply_authorized.php`
  - requiere `--autorizar=VENTAS_POS_TURNO_CIERRE`, respaldo externo, `id_usuario` y `monto_contado`.
- Caja completa preparada en modo dry-run:
  - endpoint `/ventas/caja_movimiento_dryrun_erp`
  - UI POS con boton/modal `Caja`
  - script `storage/uat/uat_ventas_pos_caja_movimiento_dryrun_readonly.php`
  - gasto simulado `50` con impacto esperado `-50`
  - sin escritura BD.
- DDL Caja POS completa preparado pero no aplicado:
  - `storage/uat/uat_ventas_pos_caja_schema_readonly.php`
  - `storage/uat/uat_ventas_pos_caja_schema_apply_authorized.php`
  - faltan 17 columnas y 3 indices en `erp_pos_movimientos_caja`.
- Movimiento real de caja preparado pero no ejecutado:
  - `storage/uat/uat_ventas_pos_caja_movimiento_apply_authorized.php`
  - requiere primero DDL caja completa;
  - despues requiere `VENTAS_POS_CAJA_MOVIMIENTO_REAL` con respaldo y datos del movimiento.

### Pendiente de autorizacion BD o sesion

- Cierre de turno real UAT.
- Movimientos de caja no venta: gasto, retiro, entrada, vale, reembolso.
- DDL Caja POS completa con token `VENTAS_POS_CAJA_DDL`.
- Movimiento real de caja con token `VENTAS_POS_CAJA_MOVIMIENTO_REAL`, solo despues del DDL de caja completa.
- Reponer stock UAT o elegir otro SKU antes de seguir probando ventas del SKU `1760`, porque quedo en `0`.
- Cerrar turno real UAT con autorizacion o aplicar caja completa, segun prioridad.
- Flujo real para alta rapida de cliente y abonos, cada uno con autorizacion separada.
- Politicas reales de precio manual/descuento general con permisos/autorizacion y snapshot de margen.
- Sesion de prueba o credenciales autorizadas para Playwright real contra `/ventas/pos`.

### Avance POS precio/descuento 2026-06-28

- Se agrego dry-run de excepcion comercial:
  - endpoint `/ventas/pos_excepcion_comercial_dryrun_erp`;
  - metodo `VentasErp::excepcionComercialDryRun`;
  - UI en modal `Cliente y precios`;
  - script `storage/uat/uat_ventas_pos_excepcion_comercial_dryrun_readonly.php`.
- Alcance:
  - precio manual;
  - descuento por partida;
  - descuento general.
- Reglas actuales:
  - el backend resuelve precio base/lista antes de simular;
  - el JS no aplica ni guarda precio final;
  - motivo obligatorio;
  - codigo/autorizacion de supervisor obligatorio;
  - si el precio queda debajo del precio base, se emite aviso de margen minimo pendiente.
- Pendiente para venta real:
  - permisos `pos.precio_manual`, `pos.descuento_partida`, `pos.descuento_general`, `pos.autorizar_excepcion_comercial`;
  - politica de margen minimo;
  - persistir snapshot de precio base, lista, aplicado, descuento, motivo, supervisor y autorizacion;
  - bloquear cualquier venta real cuyo precio recibido no coincida con backend y no tenga excepcion autorizada.
- Guardrail aplicado:
  - `prevalidarCarritoPos` ya resuelve cliente/canal/lista y recalcula precio por backend;
  - `prevalidarPartida` expone `precio_enviado_pos`, `precio_base`, `precio_aplicado`, `id_lista_precio`, `lista_precio_snapshot` y `regla_precio_origen`;
  - si el navegador manda un precio distinto al backend, se agrega bloqueo `Precio enviado por POS no coincide...`;
  - el subtotal se calcula con precio backend;
  - script `storage/uat/uat_ventas_pos_precio_guardrail_readonly.php` valida el caso.
- Permisos preparados en codigo, no sembrados en BD:
  - `ventas.precio_manual`;
  - `ventas.descuento_partida`;
  - `ventas.descuento_general`;
  - `ventas.autorizar_excepcion_comercial`.
- Roles base con esos permisos en codigo:
  - `direccion`;
  - `administrador_erp`.
- Auditoria read-only:
  - `storage/uat/uat_ventas_pos_excepcion_permisos_readonly.php`;
  - resultado: los 4 permisos faltan en BD actual;
  - requiere autorizacion de siembra de seguridad con respaldo externo.
- Aplicador autorizado preparado:
  - `storage/uat/uat_ventas_pos_excepcion_permisos_apply_authorized.php`;
  - token `VENTAS_POS_EXCEPCION_PERMISOS`;
  - guardrail validado sin token; no escribio BD;
  - autorizacion sugerida: `AUTORIZO SEMBRAR PERMISOS POS EXCEPCION COMERCIAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`.
- Permisos sembrados con autorizacion el 2026-06-28:
  - `ventas.precio_manual` = `id_permiso 291`;
  - `ventas.descuento_partida` = `id_permiso 292`;
  - `ventas.descuento_general` = `id_permiso 293`;
  - `ventas.autorizar_excepcion_comercial` = `id_permiso 294`;
  - asignados a roles `direccion` y `administrador_erp`;
  - auditoria posterior `faltantes_bd=[]`.
- DDL de excepcion comercial preparado el 2026-06-29:
  - metodo `VentasErpEsquema::planActualizarExcepcionesComerciales`;
  - auditoria `VentasErpEsquema::auditarExcepcionesComerciales`;
  - read-only `storage/uat/uat_ventas_pos_excepcion_schema_readonly.php`;
  - aplicador bloqueado `storage/uat/uat_ventas_pos_excepcion_schema_apply_authorized.php`;
  - token requerido `VENTAS_POS_EXCEPCION_DDL`;
  - auditoria actual: faltan 2 tablas, 9 columnas y 2 indices;
  - guardrail del aplicador validado sin token, no escribio BD.
  - autorizacion sugerida: `AUTORIZO APLICAR DDL POS EXCEPCION COMERCIAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`.
- DDL de excepcion comercial aplicado el 2026-06-29:
  - tablas existentes:
    - `erp_ventas_politicas_comerciales`;
    - `erp_ventas_excepciones_comerciales`.
  - columnas existentes en `erp_ventas`: `descuento_motivo`, `id_excepcion_comercial_general`, `autorizado_comercial_por`, `fecha_autorizacion_comercial`;
  - columnas existentes en `erp_ventas_detalle`: `id_excepcion_comercial`, `tipo_excepcion_comercial`, `motivo_excepcion_comercial`, `autorizado_comercial_por`, `fecha_autorizacion_comercial`;
  - indices existentes: `idx_ventas_excepcion_general`, `idx_ventas_detalle_excepcion`;
  - verificacion posterior read-only sin faltantes.
- Politicas UAT de excepcion comercial preparadas, no sembradas:
  - read-only `storage/uat/uat_ventas_pos_excepcion_politicas_readonly.php`;
  - aplicador bloqueado `storage/uat/uat_ventas_pos_excepcion_politicas_apply_authorized.php`;
  - token requerido `VENTAS_POS_EXCEPCION_POLITICAS`;
  - politicas propuestas:
    - `POS_PRECIO_MANUAL_UAT`;
    - `POS_DESCUENTO_PARTIDA_UAT`;
    - `POS_DESCUENTO_GENERAL_UAT`;
  - auditoria actual: `politicas_existentes=[]`, `bloqueos=[]`;
  - autorizacion sugerida: `AUTORIZO SEMBRAR POLITICAS POS EXCEPCION COMERCIAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 para UAT POS`.
- Politicas UAT de excepcion comercial sembradas el 2026-06-29:
  - `id_politica_comercial=1`, `POS_PRECIO_MANUAL_UAT`, estatus `activa`;
  - `id_politica_comercial=2`, `POS_DESCUENTO_PARTIDA_UAT`, estatus `activa`;
  - `id_politica_comercial=3`, `POS_DESCUENTO_GENERAL_UAT`, estatus `activa`;
  - verificacion posterior `bloqueos=[]`.
- Registro real de excepcion comercial preparado y ejecutado:
  - metodo `VentasErp::registrarExcepcionComercialAutorizada`;
  - preflight `storage/uat/uat_ventas_pos_excepcion_registro_preflight_readonly.php`;
  - aplicador bloqueado `storage/uat/uat_ventas_pos_excepcion_registro_apply_authorized.php`;
  - verificador `storage/uat/uat_ventas_pos_excepcion_registro_readonly.php`;
  - token requerido `VENTAS_POS_EXCEPCION_REAL`;
  - preflight UAT `ok=true` para SKU `1760`, cliente `5550000000`, precio manual `285`;
  - politica `POS_PRECIO_MANUAL_UAT`;
  - autorizador `id_usuario=1` tiene permiso;
  - excepcion creada `id_excepcion_comercial=1`, folio `EXC-20260628-000001`;
  - conteo actual excepciones `1`;
  - guardrail validado sin token, no escribio BD;
  - la excepcion no esta ligada todavia a venta/detalle y queda pendiente su consumo transaccional.

### UAT manual vigente

- Leer `docs/erp_ventas_pos_uat_manual.md`.
- Pruebas clave actuales:
  - POS abre con operador, caja y turno.
  - Carrito/cuentas/pagos siguen funcionando.
  - `Caja` simula gasto/retiro/entrada/vale/reembolso.
  - `Corte` con contado `795` debe devolver diferencia `0`.
  - `Corte` con contado `790` debe mostrar diferencia `-5`.
- `Atenciones` debe mostrar esquema pendiente y simular compartir cuenta actual sin crear registros.
- `Cliente/precios` debe resolver el carrito actual y mostrar que backend decide precio/lista, no el JS.
- `Cliente/precios` debe simular excepcion comercial y bloquear si falta motivo o autorizacion.
- La prevalidacion normal debe bloquear precio alterado desde POS.
- Auditoria de permisos de excepcion comercial debe mostrar permisos en codigo y faltantes en BD hasta autorizacion.

## Decisiones de arquitectura recientes

### Cliente en POS

El POS puede tener opcion de crear cliente rapido, pero no debe convertirse en un formulario largo ni en un campo suelto.

Modelo esperado:

- venta anonima/publico general;
- busqueda express por telefono, correo, codigo o nombre;
- alta rapida con datos minimos;
- ficha completa fuera del cobro;
- soporte futuro de listas de precios, promociones y recompensas.

### Listas de precios

El POS debe poder mostrar y aplicar precios especiales, pero el calculo debe vivir en backend.

Prioridad propuesta:

1. Precio manual autorizado.
2. Promocion especifica vigente.
3. Lista asignada al cliente.
4. Lista asignada al segmento.
5. Lista por canal/sucursal.
6. Lista general.

Toda venta debe guardar snapshot del precio/lista/regla aplicada.

### Apartados y pagos parciales

El modulo debe soportar pedidos/apartados donde el cliente aparta producto y paga en parcialidades.

Reglas esperadas:

- `pedido` o `apartado` puede reservar inventario sin liquidar totalmente.
- Cada abono queda ligado al folio, caja, turno y metodo de pago.
- La entrega final consume inventario si no se consumio antes.
- Cancelacion debe liberar reserva o generar penalizacion/devolucion segun politica futura.
- El saldo pendiente debe ser visible en POS y tablero.

### Recompensas

No implementar todavia. Preparar contratos para que no choque despues:

- puntos/monedero/cupones se calculan sobre ventas confirmadas;
- devoluciones revierten beneficios;
- redencion debe quedar separada de descuentos manuales;
- incentivos a vendedor/cajero son un submodulo separado de recompensas al cliente.

## Archivos principales

- `app/controladores/Ventas.php`
- `app/modelos/VentasErp.php`
- `app/modelos/VentasErpEsquema.php`
- `app/vistas/paginas/apps/erp/ventas/pos.php`
- `app/vistas/paginas/apps/erp/ventas/listado.php`
- `public/assets/js/custom/apps/erp/ventas/pos.js`
- `public/assets/js/custom/apps/erp/ventas/listado.js`
- `docs/erp_ventas_pos_pedidos_arranque.md`
- `docs/erp_ventas_pos_clientes_plan.md`

## Scripts UAT relevantes

- `storage/uat/uat_ventas_pos_dryrun_readonly.php`
- `storage/uat/uat_ventas_pos_paquete_autorizacion_dryrun.php`
- `storage/uat/uat_ventas_pos_respaldo_preflight_readonly.php`
- `storage/uat/uat_ventas_pos_runbook_readonly.php`
- `storage/uat/uat_ventas_pos_schema_apply_authorized.php`
- `storage/uat/uat_ventas_pos_seed_apply_authorized.php`
- `storage/uat/uat_ventas_pos_turno_preflight_readonly.php`
- `storage/uat/uat_ventas_pos_turno_apertura_apply_authorized.php`
- `storage/uat/uat_ventas_pos_venta_preflight_readonly.php`
- `storage/uat/uat_ventas_pos_venta_apply_authorized.php`
- `storage/uat/uat_ventas_pos_post_venta_readonly.php`

## Estado de respaldo/autorizacion

Respaldo validado:

- `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`

Usuario UAT sugerido:

- `id_usuario=1`

Ya se autorizo y ejecuto:

- DDL base.
- Semillas POS base.
- Apertura de turno real.
- Stock UAT POS.
- Venta POS UAT real.
- DDL expandido de clientes/listas/atenciones/apartados.
- Semillas expandidas POS.
- Atencion POS persistente real.
- Cobro/conversion de atencion POS real.
- Cierre de turno real `TUR-20260626-002-001`.
- DDL de caja completa sobre `erp_pos_movimientos_caja`.
- Nuevo turno POS UAT `TUR-20260627-002-001` con monto inicial `500`.
- Gasto caja POS UAT real `GASTO-UAT-001` por `50`, movimiento `5`.
- Cierre del turno `TUR-20260627-002-001` con monto contado `450` y diferencia `0`.
- Recarga stock UAT SKU `1760` en almacen `5` por cantidad `2`, referencia `INV-INICIAL-POS-UAT-20260627-A5-S1760-R2`.
- Nuevo turno POS UAT `TUR-20260627-002-002` abierto para venta/ticket con monto inicial `500`.
- Venta POS UAT real `POS-20260627-000002`, SKU `1760`, total `295`, pago efectivo `295`, kardex `56`.
- Cierre del turno `TUR-20260627-002-002` con monto contado `795` y diferencia `0`.
- Ticket formal read-only generado para `POS-20260627-000002`.
- Preparado guardado transaccional de snapshot de garantia para la proxima venta POS autorizada.
- Dry-run de Garantias para SKU `1760`: sin bloqueos, politica no configurada, snapshot sugerido `Sin garantia`.
- Preparada reimpresion UI desde tablero Ventas con modal de ticket formal.
- Inspector post-venta ampliado para validar `erp_ventas_detalle_garantias`.
- Turno `TUR-20260627-002-003` abierto para validar snapshot de garantia, `id_turno_caja=4`, monto inicial `500`.
- Preflight SKU `1760`, cantidad `1`, precio/pago `295`: `puede_vender_real=true`, stock suficiente y plan salida existencia `34`.
- Venta POS UAT real `POS-20260627-000003`, `id_venta=4`, total/pago `295`, garantia snapshot `Sin garantia`, kardex `57`, existencia `34` agotada.
- Ticket formal `POS-20260627-000003` validado sin hallazgos; muestra `Garantia: Sin garantia`.
- Cierre real del turno `TUR-20260627-002-003`: monto esperado/contado `795`, diferencia `0`, hallazgos `[]`.
- No hay turno abierto posterior al cierre.
- Alta rapida de cliente POS preparada en dry-run:
  - telefono nuevo `5551112222` puede crear propuesta `CL-POS-20260628-0001`;
  - telefono existente `5550000000` bloquea por duplicado y sugiere `CL-UAT-POS-001`;
  - UI POS ya tiene bloque `Alta rapida dry-run`.
- Aplicador autorizado `storage/uat/uat_ventas_pos_cliente_alta_rapida_apply_authorized.php` preparado y guardrail validado; no ejecutado.
- Permisos POS para excepcion comercial sembrados:
  - `ventas.precio_manual`;
  - `ventas.descuento_partida`;
  - `ventas.descuento_general`;
  - `ventas.autorizar_excepcion_comercial`.
- DDL POS excepcion comercial aplicado:
  - `erp_ventas_politicas_comerciales`;
  - `erp_ventas_excepciones_comerciales`;
  - columnas de referencia/autorizacion en `erp_ventas` y `erp_ventas_detalle`.
- Politicas UAT activas:
  - `POS_PRECIO_MANUAL_UAT`;
  - `POS_DESCUENTO_PARTIDA_UAT`;
  - `POS_DESCUENTO_GENERAL_UAT`.
- Excepcion comercial real registrada y autorizada:
  - folio `EXC-20260628-000001`;
  - `id_excepcion_comercial=1`;
  - tipo `precio_manual`;
  - cliente `CL-UAT-POS-001`;
  - SKU `TP-40352-500GR`;
  - precio lista `295`;
  - precio aplicado `285`;
  - descuento `10`;
  - no esta consumida por venta ni detalle.
- Consumo dry-run de excepcion comercial preparado y validado:
  - metodo `VentasErp::excepcionComercialConsumoDryRun`;
  - script `storage/uat/uat_ventas_pos_excepcion_consumo_dryrun_readonly.php`;
  - folio `EXC-20260628-000001` simula total `285`, descuento `10`, pago `285`, saldo `0`;
  - el backend recalcula precio lista `295` y aplica excepcion autorizada; no confia en precio JS;
  - despues de abrir turno y cargar stock, `bloqueos=[]`.
- Prerrequisitos UAT de venta con excepcion listos:
  - turno abierto `TUR-20260628-002-001`, `id_turno_caja=5`, almacen `5`, caja `2`, monto inicial `500`;
  - stock SKU `1760` cargado por referencia `INV-INICIAL-POS-UAT-20260628-A5-S1760`, cantidad `1`;
  - plan salida inventario usa `id_existencia_inventario=34`;
  - excepcion `EXC-20260628-000001` sigue `autorizada` y sin venta/detalle.
- Aplicador real preparado, no ejecutado:
  - `storage/uat/uat_ventas_pos_venta_apply_authorized.php` acepta `--folio_excepcion`;
  - guardrail sin `--autorizar=VENTAS_POS_VENTA_REAL` validado;
  - debe actualizar excepcion a `aplicada` y ligar venta/detalle si se autoriza.
- Venta real con excepcion comercial aplicada:
  - venta `POS-20260628-000001`, `id_venta=5`, estatus `pagada`;
  - subtotal `295`, descuento `10`, total/pago `285`, saldo `0`;
  - excepcion `EXC-20260628-000001` quedo `aplicada`, ligada a `id_venta=5`, `id_venta_detalle=5`;
  - kardex `id_movimiento_inventario=59`, existencia `34` de `1` a `0`;
  - caja movimiento `id_movimiento_caja=11`, ingreso `285`;
  - ticket formal sin hallazgos;
  - turno `TUR-20260628-002-001` sigue abierto, esperado `785`.
- Hallazgo POS-EXC-CLI-001:
  - la venta real quedo como `Cliente mostrador UAT` y `id_cliente=NULL`, aunque la excepcion tenia cliente `CL-UAT-POS-001`;
  - no se modifico la venta ya escrita;
  - el aplicador UAT fue ajustado para que futuras ventas con excepcion hereden cliente desde la excepcion.
- Cierre turno con excepcion comercial:
  - turno `TUR-20260628-002-001`, `id_turno_caja=5`, cerrado;
  - monto esperado `785`, contado `785`, diferencia `0`;
  - `hallazgos=[]`;
  - no queda turno abierto para caja `2`, almacen `5`.
- Hardening UI read-only de excepcion comercial:
  - endpoint `pos_excepcion_consumo_dryrun_erp`;
  - POS permite capturar folio `EXC-*` y validar consumo contra carrito/pagos/stock;
  - si el folio ya fue consumido, bloquea reutilizacion;
  - asset POS `20260629-excepcion1`.
- Preflight UAT robusta de excepcion comercial listo:
  - script `storage/uat/uat_ventas_pos_excepcion_uat_robusta_preflight_readonly.php`;
  - `ok=true`, `bloqueos=[]`;
  - no hay turno abierto;
  - referencia stock propuesta `INV-INICIAL-POS-UAT-20260629-A5-S1760-EXC2`;
  - precio lista `295`, precio manual `285`, descuento `10`, venta esperada `285`;
  - monto inicial `500`, cierre esperado `785`;
  - codigo autorizacion `SUP-UAT-002`.
- UAT robusta POS excepcion comercial SUP-UAT-002 ejecutada:
  - turno abierto y cerrado `TUR-20260629-002-001`, `id_turno_caja=6`;
  - stock UAT cargado con referencia `INV-INICIAL-POS-UAT-20260629-A5-S1760-EXC2`;
  - excepcion `EXC-20260629-000001`, `id_excepcion_comercial=2`, precio manual `285`, descuento `10`;
  - venta `POS-20260629-000001`, `id_venta=6`, total/pago `285`, saldo `0`;
  - excepcion quedo `aplicada` y ligada a `id_venta=6`, `id_venta_detalle=6`;
  - kardex `id_movimiento_inventario=61`, existencia `34` de `1` a `0`;
  - caja movimiento venta `13`, cierre esperado/contado `785`, diferencia `0`;
  - ticket formal validado sin hallazgos y con garantia snapshot `Sin garantia`;
  - intento posterior de reutilizar folio bloquea por estatus no autorizado y excepcion ya consumida;
  - no queda turno abierto para caja `2`, almacen `5`.
- Hallazgo vigente POS-CRM-EXC-001:
  - en la UAT robusta, la excepcion `EXC-20260629-000001` quedo con `id_cliente=NULL` aunque se envio identificador `5550000000`;
  - la venta se completo como venta mostrador, pero cliente CRM/POS no esta cerrado para excepciones comerciales;
  - no hacer reglas definitivas de listas, recompensas, garantias personalizadas o apartados por cliente hasta cerrar el contrato CRM canonico.
- Diagnostico CRM/POS read-only 2026-06-29:
  - script `storage/uat/uat_ventas_pos_crm_contrato_readonly.php`;
  - telefono `5550000000`: existe en `erp_clientes` POS/UAT como `CL-UAT-POS-001`, pero no existe en CRM canonico;
  - telefono `3312345678`: existe en CRM canonico como `CRM-POSUAT-20260628-0001`, `id_cliente_crm=1`;
  - dry-run de excepcion comercial con `3312345678` resuelve `id_cliente_crm=1` y no tiene bloqueos;
  - modelo `VentasErp` ya queda preparado para guardar CRM en excepciones si existen columnas autorizadas.
- DDL contrato CRM/POS preparado, no aplicado:
  - script read-only `storage/uat/uat_ventas_pos_crm_contrato_schema_readonly.php`;
  - aplicador bloqueado `storage/uat/uat_ventas_pos_crm_contrato_schema_apply_authorized.php`;
  - token requerido `VENTAS_POS_CRM_CONTRATO_DDL`;
  - columnas faltantes: 9;
  - indices faltantes: 2;
  - guardrail sin token validado, no escribio BD.
- DDL contrato CRM/POS aplicado:
  - autorizacion recibida el 2026-06-29;
  - script ejecutado `storage/uat/uat_ventas_pos_crm_contrato_schema_apply_authorized.php`;
  - columnas existentes en `erp_ventas`: `id_cliente_crm`, `cliente_codigo_snapshot`, `cliente_origen_snapshot`, `cliente_snapshot`;
  - columnas existentes en `erp_ventas_excepciones_comerciales`: `id_cliente_crm`, `cliente_codigo_snapshot`, `cliente_nombre_snapshot`, `cliente_identificador_snapshot`, `cliente_origen_snapshot`;
  - indices existentes: `idx_ventas_cliente_crm_fecha`, `idx_ventas_excepcion_cliente_crm`;
  - verificacion posterior read-only sin faltantes;
  - dry-run de excepcion con `3312345678` resuelve `id_cliente_crm=1` sin bloqueos.
- Preflight UAT robusta post-DDL CRM/POS listo:
  - script `storage/uat/uat_ventas_pos_excepcion_uat_robusta_preflight_readonly.php`;
  - se agrego `--referencia_sufijo` para evitar choque con referencias UAT anteriores;
  - telefono `3312345678`, cliente CRM `id_cliente_crm=1`;
  - referencia stock propuesta `INV-INICIAL-POS-UAT-20260629-A5-S1760-CRM1`;
  - precio lista `295`, precio manual `285`, descuento `10`;
  - venta esperada `285`, monto inicial `500`, cierre esperado `785`;
  - codigo autorizacion `SUP-CRM-003`;
  - `bloqueos=[]`.
- UAT robusta POS CRM excepcion comercial SUP-CRM-003 ejecutada:
  - turno `TUR-20260629-002-002`, `id_turno_caja=7`, cerrado;
  - stock UAT referencia `INV-INICIAL-POS-UAT-20260629-A5-S1760-CRM1`;
  - excepcion `EXC-20260629-000002`, `id_excepcion_comercial=3`, `id_cliente_crm=1`, estatus `aplicada`;
  - venta `POS-20260629-000002`, `id_venta=7`, `id_cliente_crm=1`, cliente `Cliente Express UAT`;
  - snapshot cliente en venta: `CRM-POSUAT-20260628-0001`, identificador `3312345678`, origen `crm`;
  - subtotal `295`, descuento `10`, total/pago `285`, saldo `0`;
  - kardex `id_movimiento_inventario=63`, existencia `34` de `1` a `0`;
  - caja movimiento venta `id_movimiento_caja=15`;
  - garantia snapshot `Sin garantia`;
  - ticket formal sin hallazgos;
  - cierre esperado/contado `785`, diferencia `0`;
  - reutilizacion del folio bloquea por excepcion aplicada/consumida, sin turno abierto y stock agotado.
- UI POS excepcion comercial activa:
  - se agrego panel visible `pos_excepcion_activa`;
  - cada cuenta local conserva su excepcion validada;
  - los totales/pagos rapidos usan `total_con_excepcion` mientras el folio este activo;
  - cualquier cambio de carrito, cantidad, modo de salida o pagos limpia el folio y exige revalidacion;
  - modal reorganizado en tabs `Cliente`, `Autorizacion` y `Folio`;
  - se agrego boton lateral `Autorizacion`;
  - la excepcion comercial ya permite seleccionar partida objetivo en vez de tomar siempre el primer SKU;
  - campos comerciales se habilitan/deshabilitan segun tipo de excepcion;
  - asset POS actualizado a `20260629-excepcion3`;
  - validaciones: `node --check` y `C:\xampp\php\php.exe -l` sin errores.
- Registro real de excepcion comercial POS UI expuesto:
  - autorizacion recibida el 2026-06-29;
  - endpoint POST `/ventas/pos_excepcion_comercial_registrar_erp`;
  - requiere `ventas.operar` y `ventas.autorizar_excepcion_comercial`;
  - CSRF obligatorio por `Core`;
  - auditoria explicita `ventas.excepcion_comercial_registrar`;
  - boton `Registrar folio autorizado` conectado en POS;
  - al registrar, llena `pos_excepcion_folio` y mueve al tab `Folio`;
  - escribe solo excepcion comercial, no venta/caja/inventario;
  - confirmacion antes de crear folio real;
  - boton protegido contra doble clic mientras registra;
  - backend deduplica registros equivalentes autorizados de los ultimos 2 minutos y devuelve el folio existente con `duplicado_reciente=true`;
  - asset POS actualizado a `20260629-excepcion5`;
  - no se ejecuto escritura desde CLI en esta tarea.
- Readiness read-only registro POS UI:
  - script `storage/uat/uat_ventas_pos_excepcion_ui_readiness_readonly.php`;
  - resultado `ok=true`;
  - endpoint existe;
  - respaldo externo valido;
  - permisos de usuario `1` OK;
  - politica `POS_PRECIO_MANUAL_UAT` activa;
  - cliente CRM `id_cliente_crm=1`;
  - SKU `TP-40352-500GR`, precio lista `295`, precio manual `285`, total `285`;
  - `bloqueos=[]`;
  - conteo excepciones antes `3`.
- Readiness read-only cobro real POS UI:
  - script `storage/uat/uat_ventas_pos_cobro_ui_readiness_readonly.php`;
  - valida respaldo, endpoint real propuesto, endpoints soporte, permisos, asignacion, turno, prevalidacion, confirmacion dry-run, ticket preview, excepcion comercial opcional y conteos read-only;
  - resultado actual `ok=false`;
  - respaldo externo valido;
  - usuario `1` con `ventas.operar=true`;
  - asignacion POS activa `id_almacen=5`, `id_caja=2`;
  - sin turno abierto;
  - SKU 1760 sin existencia suficiente;
  - endpoint real `/ventas/pos_confirmar_erp` aun no estaba expuesto en esta primera medicion;
  - endpoints soporte existentes;
  - conteos antes/despues sin cambios.
- Cobro real POS UI expuesto:
  - autorizacion recibida el 2026-06-29;
  - endpoint POST `/ventas/pos_confirmar_erp`;
  - modelo `VentasErp::confirmarVentaPosReal`;
  - requiere sesion, CSRF y `ventas.operar`;
  - auditoria explicita `ventas.pos_cobro_confirmar`;
  - revalida asignacion POS, turno, carrito, pagos, precio backend, inventario y excepcion comercial opcional;
  - transaccion con rollback completo;
  - crea venta, detalle, pagos, caja, kardex, garantia snapshot y trazabilidad detalle-inventario;
  - UI boton `Cobrar` conectado, con confirmacion y bloqueo de doble clic;
  - asset POS `20260629-cobro1`;
  - readiness posterior detecta endpoint existente, pero sigue bloqueado por falta de turno abierto y stock.
- Cobro real POS UI UAT ejecutado:
  - autorizacion recibida el 2026-06-29;
  - script `storage/uat/uat_ventas_pos_cobro_ui_apply_authorized.php`;
  - invoca `VentasErp::confirmarVentaPosReal`;
  - folio `POS-20260629-000003`;
  - `id_venta=8`;
  - total/pago `295`;
  - turno `TUR-20260629-002-003`, `id_turno_caja=8`;
  - caja movimiento venta `id_movimiento_caja=17`;
  - kardex `id_movimiento_inventario=65`;
  - existencia `34` de `1` a `0`;
  - garantia snapshot `id_venta_detalle_garantia=5`, `Sin garantia`;
  - post-venta read-only `ok=true`, `hallazgos=[]`;
  - ticket formal `ok=true`, `hallazgos=[]`;
  - cierre dry-run con contado `795`, diferencia `0`;
  - readiness posterior bloquea por `Existencia insuficiente`, confirmando guardrail de stock.
- Cierre turno POS UI UAT ejecutado:
  - autorizacion recibida el 2026-06-29;
  - turno `TUR-20260629-002-003`, `id_turno_caja=8`;
  - monto esperado `795`;
  - monto contado `795`;
  - diferencia `0`;
  - post-cierre read-only `ok=true`, `hallazgos=[]`;
  - turno queda `cerrado`;
  - preflight posterior permite abrir nuevo turno;
  - readiness cobro posterior bloquea por falta de turno abierto y stock agotado.
- Devolucion/cancelacion POS dry-run robustecido:
  - `VentasErp::devolucionDryRun`;
  - script `storage/uat/uat_ventas_pos_devolucion_dryrun_readonly.php`;
  - valida venta POS original, detalle, cantidad disponible, motivo, decision de inventario y reembolso estimado;
  - folio probado `POS-20260629-000003`, `id_venta=8`, detalle `8`;
  - devolucion por cantidad `1` a `cuarentena`: `ok=true`, reembolso estimado `295`;
  - cantidad excedente `2`: bloqueo esperado;
  - sin motivo: bloqueo esperado;
  - cancelacion completa sin partidas: auto-selecciona detalle y queda `ok=true`;
  - no escribe BD;
  - reversa real sigue pendiente de autorizacion fuerte.
- Readiness DDL reversas POS preparado:
  - `VentasErpEsquema::planActualizarReversasPos`;
  - `VentasErpEsquema::auditarReversasPos`;
  - endpoints:
    - `/ventas/esquema_auditar_reversas_pos`;
    - `/ventas/esquema_actualizar_reversas_pos`;
  - scripts:
    - `storage/uat/uat_ventas_pos_reversa_schema_readonly.php`;
    - `storage/uat/uat_ventas_pos_reversa_readiness_readonly.php`;
    - `storage/uat/uat_ventas_pos_reversa_schema_apply_authorized.php`;
  - auditoria actual: tablas base existen, faltan columnas/indices para caja, turno, decision financiera, reembolso, saldo a favor, autorizacion, snapshot e inventario destino;
  - readiness con folio `POS-20260629-000003`: devolucion dry-run valida, reembolso estimado `295`;
  - readiness final `ok=false` por DDL pendiente y porque el turno de la venta esta cerrado para reembolso de caja;
  - readiness con `decision_financiera=saldo_favor`: no exige movimiento de caja, solo queda bloqueado por DDL pendiente;
  - siguiente autorizacion propuesta: `VENTAS_POS_REVERSA_DDL`.
- Documentos nuevos para el siguiente paso:
  - `docs/erp_ventas_pos_reversas_solicitud_autorizacion.md`;
  - `docs/erp_ventas_pos_reversas_runbook_aplicacion.md`.
- DDL reversas POS aplicado el 2026-06-30:
  - autorizacion `VENTAS_POS_REVERSA_DDL`;
  - script `storage/uat/uat_ventas_pos_reversa_schema_apply_authorized.php`;
  - resultado `ok=true`;
  - agrego 16 columnas y 4 indices;
  - auditoria posterior sin faltantes;
  - no creo devoluciones, no reembolso caja, no movio inventario;
  - readiness posterior con `decision_financiera=saldo_favor`: `ok=true`, `bloqueos=[]`;
  - readiness posterior con `decision_financiera=reembolso_caja`: bloquea por turno cerrado, esperado.
- Aplicador real de reversa POS preparado el 2026-06-30:
  - script `storage/uat/uat_ventas_pos_reversa_apply_authorized.php`;
  - verificador `storage/uat/uat_ventas_pos_reversa_post_readonly.php`;
  - metodo `VentasErp::confirmarReversaPosReal`;
  - guardrail probado sin parametros: bloquea y no escribe BD;
  - sintaxis PHP valida en modelo, aplicador y verificador;
  - readiness read-only con `folio=POS-20260629-000003`, `id_venta_detalle=8`, `cantidad=1`, `decision_inventario=cuarentena`, `decision_financiera=saldo_favor`: `ok=true`, `bloqueos=[]`, estimado `295`;
  - verificador post-reversa antes de ejecutar: reporta que no existe devolucion real todavia, esperado;
  - no se ha ejecutado devolucion real, saldo a favor real, reembolso ni kardex reversa.
- Reversa POS real UAT ejecutada el 2026-06-30:
  - autorizacion `VENTAS_POS_REVERSA_REAL`;
  - respaldo externo validado;
  - venta `POS-20260629-000003`, `id_venta=8`;
  - detalle `id_venta_detalle=8`;
  - folio devolucion `DEV-20260630-000001`;
  - `id_devolucion=1`;
  - cantidad `1`, importe `295`;
  - decision inventario `cuarentena`;
  - decision financiera `saldo_favor`;
  - `monto_saldo_favor=295`;
  - sin reembolso caja, sin pago reembolso y sin kardex entrada;
  - venta queda en estatus `devuelta`;
  - post read-only `ok=true`, `hallazgos=[]`;
  - readiness posterior sobre la misma venta bloquea por `La venta ya esta cancelada/devuelta`, confirmando guardrail anti-reversa repetida.
- Ticket formal de devolucion POS read-only:
  - metodo `VentasErp::ticketDevolucionFormalReadOnly`;
  - endpoint `/ventas/pos_ticket_devolucion_erp`;
  - script `storage/uat/uat_ventas_pos_ticket_devolucion_readonly.php`;
  - UAT con `DEV-20260630-000001`: `ok=true`, `hallazgos=[]`;
  - muestra venta original `POS-20260629-000003`, turno `TUR-20260629-002-003`, saldo favor `295`, reembolso `0`, decision inventario `cuarentena`;
  - se corrigio consulta para usar turno/caja/almacen de devolucion o fallback de venta original cuando la devolucion no tiene turno por ser saldo a favor.
- Preparacion UAT reembolso de caja POS:
  - documento `docs/erp_ventas_pos_reembolso_caja_solicitud_autorizacion.md`;
  - preflight turno `ok=true`, sin turno abierto y puede abrir;
  - preflight stock `ok=true`, SKU 1760, referencia recomendada `INV-INICIAL-POS-UAT-20260630-A5-S1760`;
  - venta anterior no reutilizable porque ya esta `devuelta`;
  - autorizacion recomendada:
    `AUTORIZO UAT REEMBOLSO CAJA POS REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 precio=295 pago=295 monto_inicial=500 monto_contado=500 referencia_stock=INV-INICIAL-POS-UAT-20260630-A5-S1760 motivo="UAT devolucion POS con reembolso de caja"`.
- UAT reembolso de caja POS real ejecutada el 2026-06-30:
  - turno `TUR-20260630-002-001`, `id_turno_caja=9`, monto inicial `500`;
  - stock UAT cargado con referencia `INV-INICIAL-POS-UAT-20260630-A5-S1760`;
  - venta `POS-20260630-000001`, `id_venta=9`, detalle `9`, total/pago `295`;
  - movimiento caja venta `19`;
  - kardex salida `67`;
  - devolucion `DEV-20260630-000002`, `id_devolucion=2`;
  - decision inventario `cuarentena`;
  - decision financiera `reembolso_caja`;
  - movimiento caja reembolso `20`;
  - pago reembolso `id_venta_pago=10`;
  - venta queda `devuelta`;
  - ticket devolucion read-only `ok=true`, `hallazgos=[]`, muestra `REEMBOLSO_CAJA`;
  - cierre turno esperado/contado `500`, diferencia `0`;
  - post-cierre `ok=true`, `hallazgos=[]`, sin turno abierto;
  - readiness posterior bloquea reversa repetida por `La venta ya esta cancelada/devuelta`.
- Bandeja read-only evidencias de caja:
  - metodo `VentasErp::evidenciasCajaPendientesReadOnly`;
  - endpoint `/ventas/caja_evidencias_pendientes_erp`;
  - script `storage/uat/uat_ventas_pos_caja_evidencias_readonly.php`;
  - documento `docs/erp_ventas_pos_evidencias_caja_plan.md`;
  - UAT sobre turno `9`: `ok=true`, `total_registros=1`, monto `295`;
  - pendiente detectado: movimiento caja `20`, referencia `DEV-20260630-000002`, venta `POS-20260630-000001`, turno cerrado `TUR-20260630-002-001`, estado `pendiente`;
  - falta DDL formal de evidencias/adjuntos y flujo de aprobacion/rechazo.
- Readiness DDL evidencias de caja POS:
  - metodo `VentasErpEsquema::planActualizarEvidenciasCajaPos`;
  - metodo `VentasErpEsquema::auditarEvidenciasCajaPos`;
  - endpoints:
    - `/ventas/esquema_auditar_evidencias_caja_pos`;
    - `/ventas/esquema_actualizar_evidencias_caja_pos`;
  - scripts:
    - `storage/uat/uat_ventas_pos_caja_evidencias_schema_readonly.php`;
    - `storage/uat/uat_ventas_pos_caja_evidencias_schema_apply_authorized.php`;
  - auditoria actual: falta tabla `erp_pos_movimientos_caja_evidencias` e indice `idx_pos_movimiento_evidencia`;
  - solicitud `docs/erp_ventas_pos_evidencias_caja_schema_solicitud_autorizacion.md`;
  - siguiente autorizacion requerida:
    `AUTORIZO APLICAR DDL EVIDENCIAS CAJA POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIAS_DDL para UAT POS`.
- DDL evidencias de caja POS aplicado el 2026-06-30:
  - autorizacion `VENTAS_POS_CAJA_EVIDENCIAS_DDL`;
  - script `storage/uat/uat_ventas_pos_caja_evidencias_schema_apply_authorized.php`;
  - tabla creada `erp_pos_movimientos_caja_evidencias`;
  - indice creado `idx_pos_movimiento_evidencia`;
  - auditoria posterior sin faltantes;
  - no adjunto archivos, no aprobo evidencias, no modifico movimientos de caja;
  - bandeja posterior mantiene pendiente el movimiento `20`, referencia `DEV-20260630-000002`, monto `295`.
- Aplicador evidencia caja POS preparado:
  - metodo `VentasErp::registrarEvidenciaCajaPosReal`;
  - script `storage/uat/uat_ventas_pos_caja_evidencia_apply_authorized.php`;
  - guardrail probado sin parametros: bloquea y no escribe BD;
  - siguiente autorizacion requerida:
    `AUTORIZO REGISTRAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_REAL id_usuario=1 id_movimiento_caja=20 tipo_evidencia=ticket_firmado referencia_externa=DEV-20260630-000002 descripcion="Comprobante UAT de reembolso de caja firmado por cliente"`.
- Evidencia caja POS registrada el 2026-06-30:
  - autorizacion `VENTAS_POS_CAJA_EVIDENCIA_REAL`;
  - movimiento caja `20`, referencia `DEV-20260630-000002`;
  - evidencia `id_evidencia_caja=1`;
  - tipo `ticket_firmado`;
  - estado movimiento/evidencia `recibida`;
  - requiere revision posterior;
  - consulta de pendientes turno `9`: `total_registros=0`;
  - consulta de recibidas turno `9`: `total_registros=1`, monto `295`;
  - no aprobo evidencia, no movio dinero, no modifico inventario.
- Revision evidencia caja POS preparada:
  - metodo `VentasErp::evidenciasCajaDetalleReadOnly`;
  - metodo `VentasErp::revisarEvidenciaCajaPosReal`;
  - endpoints `/ventas/caja_evidencias_detalle_erp` y `/ventas/caja_evidencia_revisar_erp`;
  - scripts `storage/uat/uat_ventas_pos_caja_evidencias_detalle_readonly.php` y `storage/uat/uat_ventas_pos_caja_evidencia_revision_apply_authorized.php`;
  - detalle evidencia `1`: `ok=true`, estado `recibida`, movimiento `20`, venta `POS-20260630-000001`, devolucion `DEV-20260630-000002`;
  - script de revision sin parametros bloqueado correctamente;
  - regla formal: aprobar/rechazar no mueve dinero ni inventario, solo cambia estado de evidencia/movimiento;
  - por defecto exige revisor distinto al creador;
  - siguiente autorizacion recomendada:
    `AUTORIZO REVISAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_REVISION_REAL id_usuario=ID_REVISOR id_evidencia_caja=1 decision=aprobada`.
- Revision evidencia caja POS aprobada el 2026-06-30:
  - autorizacion `VENTAS_POS_CAJA_EVIDENCIA_REVISION_REAL`;
  - evidencia `1`, movimiento caja `20`;
  - decision `aprobada`;
  - `revisado_por=1`;
  - se uso `permitir_mismo_usuario=1` solo para UAT porque el unico candidato con permiso era tambien el creador;
  - detalle evidencia `1`: `estatus=aprobada`, `fecha_revision=2026-06-30 08:42:25`;
  - movimiento caja `20`: `evidencia_estado=aprobada`;
  - turno `9`, aprobadas: `total_registros=1`, monto `295`;
  - turno `9`, pendientes: `total_registros=0`;
  - no movio dinero, no modifico inventario, no modifico importes ni turno.
- Bandeja visual evidencias de caja POS preparada:
  - integrada en modal `Caja` del POS;
  - archivos `app/vistas/paginas/apps/erp/ventas/pos.php` y `public/assets/js/custom/apps/erp/ventas/pos.js`;
  - version asset `20260630-evidencias1`;
  - filtro por estado y detalle read-only por movimiento;
  - endpoints `/ventas/caja_evidencias_pendientes_erp` y `/ventas/caja_evidencias_detalle_erp`;
  - validaciones sintaxis OK para JS/PHP;
  - no aprueba, no rechaza, no adjunta, no escribe BD;
  - pendiente validacion visual en navegador autenticado.
- Permiso fino evidencias caja POS preparado:
  - permiso `ventas.caja_evidencias.revisar`;
  - agregado en `SeguridadEsquema` para roles base `direccion` y `administrador_erp`;
  - `VentasErp::revisarEvidenciaCajaPosReal` acepta permiso fino y conserva compatibilidad temporal con `ventas.autorizar_excepcion_comercial`;
  - scripts `storage/uat/uat_ventas_pos_caja_evidencias_permiso_readonly.php` y `storage/uat/uat_ventas_pos_caja_evidencias_permiso_apply_authorized.php`;
  - read-only actual: permiso no existe en BD y requiere autorizacion;
  - aplicador sin parametros bloqueado correctamente;
  - solicitud `docs/erp_ventas_pos_caja_evidencias_permiso_solicitud_autorizacion.md`;
  - siguiente autorizacion requerida:
    `AUTORIZO SEMBRAR PERMISO EVIDENCIAS CAJA POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIAS_PERMISO id_usuario=1 para UAT POS`.
- Permiso fino evidencias caja POS sembrado el 2026-06-30:
  - autorizacion `VENTAS_POS_CAJA_EVIDENCIAS_PERMISO`;
  - permiso `ventas.caja_evidencias.revisar`;
  - `id_permiso=300`;
  - roles asignados `direccion` y `administrador_erp`;
  - auditoria posterior `faltantes=[]`, `requiere_autorizacion=false`;
  - no aprobo evidencias, no modifico caja, no movio dinero ni inventario;
  - siguiente paso: refrescar sesion/permisos y validar visualmente bandeja de evidencias en POS.
- Validacion previsual bandeja evidencias:
  - navegador integrado `iab` no disponible en la sesion;
  - validacion por sintaxis y endpoints read-only OK;
  - `node --check public/assets/js/custom/apps/erp/ventas/pos.js`: OK;
  - `php -l app/vistas/paginas/apps/erp/ventas/pos.php`: OK;
  - consulta turno `9`, estado `aprobada`: `total_registros=1`, monto `295`, movimiento `20`;
  - detalle movimiento `20`: evidencia `1`, estatus `aprobada`, revisado por `1`;
  - UAT manual pendiente: abrir `/ventas/pos`, boton `Caja`, filtro `Aprobada`, consultar y revisar detalle.
- Revision UI evidencias caja POS preparada:
  - version asset `20260630-evidencias2`;
  - botones `Aprobar` y `Rechazar` aparecen solo para evidencias `recibida`;
  - aprobar pide confirmacion;
  - rechazar exige motivo;
  - endpoint POST `/ventas/caja_evidencia_revisar_erp`;
  - permiso fino `ventas.caja_evidencias.revisar` sembrado y vigente;
  - validaciones JS/PHP OK;
  - evidencia UAT `1` ya esta `aprobada`, por lo que para probar botones se requiere otra evidencia `recibida`.
- Ticket devolucion desde evidencia caja POS:
  - version asset `20260630-evidencias3`;
  - boton `Ticket devolucion` en detalle de evidencia cuando existe `folio_devolucion`;
  - usa `/ventas/pos_ticket_devolucion_erp`;
  - validacion read-only `DEV-20260630-000002`: `ok=true`, `hallazgos=[]`, reembolso caja `295`, movimiento caja `20`;
  - no escribe BD.
- Guardrail evidencia duplicada aprobada:
  - `VentasErp::registrarEvidenciaCajaPosReal` bloquea nueva evidencia sobre movimiento con `evidencia_estado=aprobada`;
  - intento duplicado sobre movimiento `20` fue bloqueado con `ok=false`;
  - posterior: movimiento `20` sigue `aprobada`, total evidencias `1`;
  - pendiente futuro: flujo formal de correccion de evidencia aprobada con permiso superior y motivo.
- Readiness DDL correcciones evidencias caja POS:
  - objetivo: corregir evidencias aprobadas sin editar/borrar historia;
  - metodo `VentasErpEsquema::planActualizarCorreccionesEvidenciasCajaPos`;
  - metodo `VentasErpEsquema::auditarCorreccionesEvidenciasCajaPos`;
  - endpoints `/ventas/esquema_auditar_correcciones_evidencias_caja_pos` y `/ventas/esquema_actualizar_correcciones_evidencias_caja_pos`;
  - scripts `storage/uat/uat_ventas_pos_caja_evidencias_correccion_schema_readonly.php` y `storage/uat/uat_ventas_pos_caja_evidencias_correccion_schema_apply_authorized.php`;
  - auditoria read-only: falta `erp_pos_movimientos_caja_evidencias_correcciones`;
  - aplicador sin parametros bloqueado correctamente;
  - solicitud `docs/erp_ventas_pos_caja_evidencias_correccion_schema_solicitud_autorizacion.md`;
  - siguiente autorizacion requerida:
    `AUTORIZO APLICAR DDL CORRECCIONES EVIDENCIAS CAJA POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIAS_CORRECCION_DDL para UAT POS`.
- DDL correcciones evidencias caja POS aplicado el 2026-06-30:
  - tabla creada `erp_pos_movimientos_caja_evidencias_correcciones`;
  - auditoria posterior sin faltantes;
  - movimiento caja `20` y evidencia `1` siguen `aprobada`;
  - no modifico evidencias, caja, dinero ni inventario.
- Solicitud correccion evidencia caja POS preparada:
  - metodo `VentasErp::solicitarCorreccionEvidenciaCajaPosReal`;
  - endpoint `/ventas/caja_evidencia_correccion_solicitar_erp`;
  - script `storage/uat/uat_ventas_pos_caja_evidencia_correccion_apply_authorized.php`;
  - crea folio `COR-EVC-YYYYMMDD-######`;
  - requiere evidencia aprobada, motivo y permiso `ventas.caja_evidencias.revisar`;
  - bloquea si ya hay correccion abierta para la evidencia;
  - aplicador sin parametros bloqueado correctamente;
  - siguiente autorizacion requerida:
    `AUTORIZO SOLICITAR CORRECCION EVIDENCIA CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_REAL id_usuario=1 id_evidencia_caja=1 tipo_correccion=reemplazo_evidencia motivo="UAT solicitud de correccion de evidencia aprobada"`.
- Solicitud correccion evidencia caja POS creada:
  - folio `COR-EVC-20260630-000001`;
  - `id_correccion_evidencia_caja=1`;
  - evidencia original `1`, movimiento caja `20`;
  - estatus `solicitada`;
  - evidencia original y movimiento siguen `aprobada`;
  - no movio dinero ni inventario.
- Evidencia correctiva caja POS preparada:
  - metodo `VentasErp::registrarEvidenciaCorrectivaCajaPosReal`;
  - endpoint `/ventas/caja_evidencia_correccion_evidencia_erp`;
  - script `storage/uat/uat_ventas_pos_caja_evidencia_correctiva_apply_authorized.php`;
  - inserta nueva evidencia con estatus `recibida_correccion` y cambia correccion a `en_revision`;
  - no cambia evidencia original ni movimiento de caja;
  - aplicador sin parametros bloqueado correctamente;
  - siguiente autorizacion requerida:
    `AUTORIZO REGISTRAR EVIDENCIA CORRECTIVA CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_CORRECTIVA_REAL id_usuario=1 folio=COR-EVC-20260630-000001 tipo_evidencia=ticket_firmado_correccion referencia_externa=DEV-20260630-000002-CORR descripcion="Comprobante correctivo UAT para evidencia aprobada"`.
- Evidencia correctiva caja POS registrada:
  - correccion `COR-EVC-20260630-000001` paso a `en_revision`;
  - evidencia correctiva nueva `id_evidencia_caja=2`;
  - evidencia `2` en estado `recibida_correccion`;
  - evidencia original `1` sigue `aprobada`;
  - movimiento caja `20` sigue `evidencia_estado=aprobada`;
  - no movio dinero ni inventario.
- Resolucion correccion evidencia caja POS preparada:
  - metodo `VentasErp::resolverCorreccionEvidenciaCajaPosReal`;
  - endpoint `/ventas/caja_evidencia_correccion_resolver_erp`;
  - script `storage/uat/uat_ventas_pos_caja_evidencia_correccion_resolver_apply_authorized.php`;
  - aprueba/rechaza correccion en revision;
  - si aprueba: correccion `resuelta`, evidencia nueva `aprobada_correccion`;
  - si rechaza: correccion `rechazada`, evidencia nueva `rechazada_correccion`;
  - no cambia evidencia original ni movimiento caja;
  - aplicador sin parametros bloqueado correctamente;
  - siguiente autorizacion requerida:
    `AUTORIZO RESOLVER CORRECCION EVIDENCIA CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_RESOLVER_REAL id_usuario=1 folio=COR-EVC-20260630-000001 decision=aprobada motivo="UAT correccion validada y aceptada"`.
- Correccion evidencia caja POS resuelta:
  - folio `COR-EVC-20260630-000001`;
  - decision `aprobada`;
  - correccion `estatus=resuelta`;
  - evidencia original `1` sigue `aprobada`;
  - evidencia correctiva `2` quedo `aprobada_correccion`;
  - `resuelto_por=1`, `fecha_resolucion=2026-06-30 10:00:45`;
  - movimiento caja `20` sigue `evidencia_estado=aprobada`;
  - turno `9`, estado `aprobada`: `total_registros=1`, monto `295`;
  - no movio dinero ni inventario.
- UI detalle evidencias caja POS enriquecida:
  - `VentasErp::evidenciasCajaDetalleReadOnly` ahora incluye folio/estatus/decision de correccion cuando existe `erp_pos_movimientos_caja_evidencias_correcciones`;
  - la pantalla POS muestra la correccion ligada a cada evidencia como historial operativo;
  - asset POS actualizado a `20260630-evidencias4`;
  - UAT read-only sobre movimiento `20` devolvio evidencia original `1` y correctiva `2` con folio `COR-EVC-20260630-000001`, `estatus=resuelta`, `decision=aprobada`;
  - no escribe BD, no aprueba evidencia, no mueve dinero ni inventario.
- UI correcciones evidencia caja POS expuesta:
  - autorizacion recibida: `VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_UI`;
  - asset POS actualizado a `20260630-evidencias5`;
  - acciones en detalle de evidencia:
    - solicitar correccion sobre evidencia `aprobada`;
    - registrar evidencia correctiva por folio `solicitada` o `en_revision`;
    - aprobar/rechazar correccion en revision con evidencia `recibida_correccion`;
  - usa endpoints existentes con CSRF/sesion/permisos:
    - `/ventas/caja_evidencia_correccion_solicitar_erp`;
    - `/ventas/caja_evidencia_correccion_evidencia_erp`;
    - `/ventas/caja_evidencia_correccion_resolver_erp`;
  - no cambia reglas de caja, dinero, venta, kardex ni inventario;
  - UAT manual agregado como `Prueba 47`.
- Bandeja read-only de devoluciones fisicas:
  - metodo `VentasErp::devolucionesInventarioPendientesReadOnly`;
  - endpoint `/ventas/devoluciones_inventario_pendientes_erp`;
  - script `storage/uat/uat_ventas_pos_devoluciones_inventario_pendientes_readonly.php`;
  - UI POS en modal `Caja`, seccion `Devoluciones fisicas`;
  - asset POS actualizado a `20260630-devoluciones-fisicas1`;
  - resultado UAT actual: 2 partidas pendientes, cantidad `2`, importe `590`, decision `cuarentena`;
  - folios: `DEV-20260630-000002` y `DEV-20260630-000001`;
  - contrato: no escribe BD, no crea kardex, no reintegra inventario;
  - siguiente modulo a cerrar: Almacen/Inventario para inspeccion fisica, cuarentena, merma, reintegro o garantia/proveedor.
- DDL inspeccion fisica devoluciones POS aplicado:
  - autorizacion `VENTAS_POS_DEVOLUCIONES_FISICAS_DDL`;
  - tabla creada `erp_ventas_devoluciones_inspecciones`;
  - columnas agregadas a `erp_ventas_devoluciones_detalle`: `inspeccion_estado`, `id_inspeccion_fisica`, `fecha_inspeccion_fisica`;
  - auditoria posterior OK;
  - endpoint dry-run `/ventas/devolucion_inspeccion_fisica_dryrun_erp`;
  - script `storage/uat/uat_ventas_pos_devolucion_inspeccion_fisica_dryrun_readonly.php`;
  - dry-run `id_devolucion_detalle=2`:
    - `mantener_cuarentena`: valido, no requiere kardex;
    - `reintegrar_disponible`: valido, requiere kardex en ejecucion futura;
  - no se registraron inspecciones reales ni movimientos de inventario.
- Primera inspeccion fisica real registrada:
  - autorizacion `VENTAS_POS_DEVOLUCION_FISICA_REAL`;
  - `id_inspeccion_fisica=1`;
  - folio `IFD-20260630-000001`;
  - `id_devolucion_detalle=2`, devolucion `DEV-20260630-000002`;
  - decision `mantener_cuarentena`;
  - `inspeccion_estado=cuarentena_confirmada`;
  - pendientes fisicos ahora: 1 partida (`DEV-20260630-000001`);
  - no creo kardex, no movio inventario, no creo garantia.
- POS cliente/UX actualizado:
  - `docs/erp_ventas_pos_ux_operativa_plan.md` creado;
  - scripts POS de alta rapida cliente corregidos para usar CRM canonico:
    - `storage/uat/uat_ventas_pos_cliente_alta_rapida_dryrun_readonly.php`;
    - `storage/uat/uat_ventas_pos_cliente_alta_rapida_apply_authorized.php`;
  - acciones inferiores del carrito reubicadas como barra horizontal de modulos: Prevalidar, Simular, Pedido, Ticket, Cliente, Autorizar, Atenciones, Caja y Corte;
  - parte baja del POS queda enfocada en pagos, totales y `Cobrar`;
  - modal de cliente ahora separa `Buscar` CRM read-only, `Resolver lista` con carrito y `Validar alta` dry-run;
  - atajos iniciales agregados: `F2`, `Ctrl+K`, `Alt+1`, `Alt+2`, `Alt+3`, `F4`, `F6`, `F8`, `F9`, `Ctrl+Enter`;
  - se evita usar `F5`, `Ctrl+R`, `Ctrl+L` y `Ctrl+P` para no chocar con navegador;
  - asset POS actualizado a `20260630-pos-ux-cliente3`;
  - no se creo cliente real ni se escribio BD.
- Validaciones read-only posteriores:
  - `node --check public/assets/js/custom/apps/erp/ventas/pos.js`: OK;
  - `php -l app/vistas/paginas/apps/erp/ventas/pos.php`: OK;
  - alta rapida POS dry-run propone `CRM-POSUAT-20260630-0001` sobre CRM canonico;
  - listado CRM read-only encuentra `id_cliente_crm=1`, telefono `3312345678`;
  - Playwright headless local con `localhost` redirige a otro proyecto; host correcto para POS local: `http://dashboard.com.local`;
  - Playwright autenticado en `dashboard.com.local` carga POS con buscador, barra de modulos, cliente y cobrar;
  - evidencia visual:
    - `public/storage/uat/pos_ux_cliente2_authenticated_dashboard.png`;
    - `public/storage/uat/pos_ux_cliente_busqueda_crm.png`;
    - `public/storage/uat/pos_ux_cliente_seleccionado_v3.png`;
    - `public/storage/uat/pos_ux_busqueda_producto_1760.png`;
  - busqueda CRM POS read-only con `3312345678` encuentra cliente `CRM-POSUAT-20260628-0001`;
  - seleccionar cliente actualiza campos POS y cuenta activa; boton cambia a `Seleccionado`;
  - correccion UX posterior por prueba manual:
    - `Buscar cliente` ya no se confunde con `Precios/lista`;
    - si hay una coincidencia exacta activa por telefono/codigo, el cliente se selecciona automaticamente;
    - `Validar alta` queda deshabilitado y cambia a `Cliente seleccionado`;
    - evidencia `public/storage/uat/pos_ux_cliente_autoseleccionado_v5.png`;
    - asset POS actualizado a `20260630-pos-ux-cliente5`;
  - busqueda producto `1760` devuelve 3 resultados sin stock y sin errores JS;
  - asignacion oficial POS reforzada:
    - almacen, caja y turno quedan deshabilitados en UI cuando existe asignacion oficial;
    - tooltip de almacen: `Sucursal fijada por asignacion oficial POS`;
    - tooltip de caja: `Caja fijada por asignacion oficial POS`;
    - tooltip de turno sin apertura: `Sin turno abierto para la caja oficial`;
    - evidencia `public/storage/uat/pos_ux_asignacion_oficial_bloqueada.png`;
    - asset POS actualizado a `20260630-pos-ux-oficial1`;
  - cobro UI sin turno reforzado:
    - si no hay turno abierto, boton `Cobrar` queda deshabilitado;
    - texto visible: `Abrir turno para cobrar`;
    - tooltip: `Abre turno de caja antes de cobrar`;
    - evidencia `public/storage/uat/pos_ux_cobro_bloqueado_sin_turno.png`;
    - asset POS actualizado a `20260630-pos-ux-turno1`;
  - `uat_ventas_pos_post_config_readonly.php --id_usuario=1 --alcance=expandido`: 2 cajas, 2 terminales, 2 asignaciones activas, 0 turnos abiertos, asignacion oficial activa y unico bloqueo `No hay turno abierto`;
  - `uat_ventas_pos_acceso_readonly.php --id_usuario=1`: usuario con `ventas.ver` y `ventas.operar`.
- Preflight para siguiente UAT real UI:
  - `uat_ventas_pos_turno_preflight_readonly.php --id_usuario=1`: puede abrir turno, asignacion oficial `MASCOTAS971 / CJ-MASCOTAS971-01 / TERM-MASCOTAS971-01`;
  - `uat_ventas_pos_stock_uat_preflight_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1`: SKU `TP-40352-500GR`, precio `295`, permite fraccionaria, sin bloqueos para cargar stock UAT;
  - `uat_ventas_pos_venta_preflight_readonly.php --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295`: bloqueos actuales `No hay turno abierto`, `Selecciona turno abierto de caja`, `Existencia insuficiente`;
  - siguiente autorizacion robusta recomendada: abrir turno + cargar stock UAT; despues validar carrito/cobro desde UI.
- Preparacion UAT venta POS UI real ejecutada:
  - autorizacion recibida: preparar UAT venta POS UI real con respaldo `artianilocal_respaldo_completo_20260625_post_repair.sql`;
  - turno abierto `TUR-20260630-002-002`, `id_turno_caja=10`, caja `2`, almacen `5`, fondo inicial `500`, movimiento caja `21`;
  - stock UAT cargado para SKU `1760`, cantidad `1`, referencia `INV-INICIAL-POS-UAT-UX`;
  - preflight venta read-only: `puede_vender_real=true`, turno abierto `true`, saldo `0`, faltante `0`, salida por existencia `34`;
  - candidatos stock almacen `5`: `TP-40352-500GR`, disponible `1`;
  - Playwright POS autenticado muestra turno abierto, boton `Cobrar` habilitado y producto con `Disp. 1 kg`;
  - evidencia `public/storage/uat/pos_ux_turno_stock_listo.png`.
- Venta POS UI real confirmada por usuario:
  - folio `POS-20260701-000001`, `id_venta=10`;
  - estatus `pagada`, canal `pos`, tipo `venta`;
  - turno `TUR-20260630-002-002`, caja `CJ-MASCOTAS971-01`, almacen `5`;
  - cliente CRM `id_cliente_crm=1`, snapshot `CRM-POSUAT-20260628-0001`, `Cliente Express UAT`, identificador `3312345678`;
  - detalle `id_venta_detalle=10`, SKU `1760`, `TP-40352-500GR`, cantidad `1 kg`, total `295`;
  - lista aplicada `Lista UAT POS`, regla `lista_canal_sucursal`;
  - pago efectivo `id_venta_pago=11`, movimiento caja `22`, monto `295`;
  - garantia snapshot `Sin garantia`, `id_venta_detalle_garantia=7`;
  - trazabilidad inventario `id_venta_detalle_inventario=10`, movimiento kardex `69`, existencia `34`, referencia `POS-20260701-000001`;
  - existencia anterior `1`, nueva `0`; stock candidatos almacen `5` queda `total=0`;
  - postventa read-only sin hallazgos;
  - ticket formal read-only generado con 28 lineas, sin hallazgos;
  - se corrigio copy de prevalidacion: ya no dice que falta autorizacion para cobrar; asset `20260701-pos-ux-prevalidar1`.

No se ha autorizado todavia:

- Alta/edicion real completa de clientes/listas/apartados desde UI.
- Ejecucion real de alta rapida cliente POS.
- Validacion visual con sesion web real de la reimpresion UI.
- UAT visual del registro real de excepcion comercial desde POS UI.
- Validar visualmente cobro real desde navegador con Playwright/sesion.
- Alta/edicion real de cliente POS contra CRM desde UI productiva.
- Apartados/abonos con reserva formal y cliente obligatorio.
- Ejecucion real de reembolso de caja POS y kardex de reintegro despues de inspeccion.

## Proximos pasos recomendados

1. Validar visualmente bandeja de evidencias de caja en POS autenticado.
2. Crear permiso fino de supervisor de caja/evidencias para no depender de `ventas.autorizar_excepcion_comercial`.
3. Integrar reimpresion visual de ticket de devolucion en UI.
4. Disenar flujo de cuarentena/inspeccion: reintegrar, merma, garantia o proveedor.
5. Validar UX real del POS con sesion de prueba o credenciales autorizadas.
6. Registrar un folio real de excepcion comercial desde POS UI con usuario autorizado y validarlo contra carrito/pagos.
7. Validar visualmente la reimpresion UI del ticket formal en navegador autenticado.
8. Implementar aplicador autorizado de alta rapida cliente POS solo contra CRM canonico.
9. Avanzar apartados/abonos con reserva formal de inventario y cliente obligatorio.

## Riesgos vigentes

- Si se activa venta real sin caja/turno, se pierde control de corte.
- Si se activa cliente rapido sin modelo robusto, se crean duplicados y deuda tecnica.
- Si se aplican precios/descuentos en JS, se rompe auditoria y control de margen.
- Si se mezclan clientes ecommerce sin auditoria, se pueden fusionar identidades incorrectas.
- Si apartados no reservan correctamente, se puede prometer stock inexistente.

## Decision UX/POS 2026-07-01 - separar caja/configuracion del cobro

- El POS principal debe quedar enfocado en venta rapida: buscar, escanear, carrito, cliente, pagos, cobrar y ticket.
- Caja/turnos no deben quedar mezclados como funciones administrativas dentro de la pantalla de cobro:
  - `Caja > Turnos`: apertura, cierre, corte y arqueo;
  - `Caja > Movimientos`: gastos, retiros, entradas, vales, reembolsos;
  - `Caja > Evidencias`: comprobantes, revisiones y correcciones.
- La configuracion operativa debe vivir separada:
  - `Configuracion POS > Tiendas y cajas`;
  - `Configuracion POS > Terminales`;
  - `Configuracion POS > Asignaciones usuario/caja`;
  - `Configuracion POS > Politicas`.
- El tablero `/ventas/mostrar` debe quedar para ventas/folios/tickets/pagos/trazabilidad y acciones por venta.
- Devoluciones deben iniciar desde folio/venta, pero operar en modulo propio conectado a caja, inventario, garantia y CRM.
- Estado actual: existen endpoints/tablas base y modales UAT en POS; falta crear pantallas productivas dedicadas y CRUD visual para tiendas/cajas/terminales/asignaciones.

## Avance tecnico 2026-07-01 - pantallas base separadas Caja/Configuracion POS

- Rutas/vistas nuevas sin escrituras:
  - `/ventas/caja_turnos`;
  - `/ventas/pos_configuracion`.
- Endpoint read-only nuevo:
  - `/ventas/pos_configuracion_resumen_erp`;
  - modelo `VentasErp::configuracionPosReadOnly`.
- JS nuevos:
  - `public/assets/js/custom/apps/erp/ventas/caja_turnos.js`;
  - `public/assets/js/custom/apps/erp/ventas/pos_configuracion.js`.
- Menu POS actualizado:
  - `Caja y turnos`;
  - `Configuracion POS`.
- Consulta read-only validada por CLI:
  - schema POS completo: cajas, terminales, asignaciones, turnos y movimientos;
  - cajas `2`;
  - terminales `2`;
  - asignaciones `2`;
  - turnos abiertos `1`;
  - movimientos recientes `22`;
  - turno abierto actual `TUR-20260630-002-002`;
  - movimientos del turno actual: apertura `500` y venta `295`.
- Contrato:
  - las nuevas vistas no crean cajas;
  - no crean terminales;
  - no cambian asignaciones;
  - no cierran turno real;
  - el cierre real sigue requiriendo autorizacion explicita con respaldo.
- Pendiente de UX:
  - retirar gradualmente de `/ventas/mostrar` los bloques de preparacion/caja dry-run para dejarlo solo como tablero de ventas;
  - mover acciones reales de caja a las pantallas nuevas cuando se autoricen aplicadores productivos.

## Avance tecnico 2026-07-01 - tablero de ventas limpio y modulo devoluciones

- `/ventas/mostrar` queda enfocado en KPIs, filtros, folios, ticket y acciones de venta.
- Se retiraron de `/ventas/mostrar` los bloques de:
  - preparacion POS/cajas sugeridas;
  - turno de caja dry-run;
  - cancelacion/devolucion dry-run.
- Nuevo modulo separado:
  - ruta `/ventas/devoluciones`;
  - vista `app/vistas/paginas/apps/erp/ventas/devoluciones.php`;
  - JS `public/assets/js/custom/apps/erp/ventas/devoluciones.js`.
- Menu POS actualizado con `Devoluciones`.
- Accesos rapidos en tablero de ventas:
  - `Devoluciones`;
  - `Caja`;
  - `Pedidos`;
  - `POS`.
- Contrato de devoluciones:
  - simula reversa via `/ventas/devolucion_dryrun_erp`;
  - consulta pendientes fisicos via `/ventas/devoluciones_inventario_pendientes_erp`;
  - consulta ticket de devolucion via `/ventas/pos_ticket_devolucion_erp`;
  - no ejecuta devolucion real;
  - no reembolsa;
  - no mueve inventario;
  - no crea kardex.
- Validaciones:
  - `php -l` OK en controlador, sidebar, listado y devoluciones;
  - `node --check` OK en `listado.js` y `devoluciones.js`.

## Avance tecnico 2026-07-01 - dry-run CRUD Configuracion POS

- Endpoints dry-run agregados:
  - `/ventas/pos_configuracion_caja_dryrun_erp`;
  - `/ventas/pos_configuracion_terminal_dryrun_erp`;
  - `/ventas/pos_configuracion_asignacion_dryrun_erp`.
- Modelo:
  - `VentasErp::configuracionCajaDryRun`;
  - `VentasErp::configuracionTerminalDryRun`;
  - `VentasErp::configuracionAsignacionDryRun`.
- Pantalla `/ventas/pos_configuracion` ampliada con pestañas:
  - Caja;
  - Terminal;
  - Asignacion.
- Contrato:
  - no crea caja;
  - no crea terminal;
  - no crea asignacion;
  - valida campos, duplicados, pertenencia caja/almacen y consistencia terminal/caja.
- Pruebas CLI read-only:
  - caja nueva sugerida `CJ-UAT-DRY-01`: valida, sin bloqueos;
  - terminal nueva sugerida `TERM-UAT-DRY-01`: valida, sin bloqueos;
  - asignacion existente `id_usuario=1`, `id_almacen=5`, `id_caja=2`, `id_terminal_pos=2`: bloquea por duplicado activo.
- Validaciones:
  - `php -l` OK en `Ventas.php`, `VentasErp.php`, `pos_configuracion.php`;
  - `node --check` OK en `pos_configuracion.js`.
- Siguiente autorizacion robusta futura:
  - aplicar CRUD real Configuracion POS con respaldo;
  - separar permisos de administracion POS, por ejemplo `ventas.pos_configurar` o modulo `pos.configurar`.

## Avance UX 2026-07-02 - claridad de accion real/simulacion/consulta

- Se agregaron etiquetas visibles para evitar confusion entre prueba y operacion real:
  - POS: `Cobrar = accion real`, `Prevalidar/Simular = no escribe`, `Ticket preview = solo vista previa`;
  - Caja: `Consulta de caja = solo lectura`, `Simular corte = no cierra turno`, `Cerrar turno real requiere autorizacion`;
  - Configuracion POS: `Listados = solo consulta`, `Validar alta = no crea registros`, `CRUD real pendiente de autorizacion`;
  - Devoluciones: `Simular reversa = no aplica devolucion`, `Pendientes/ticket = solo consulta`, `Reembolso real requiere autorizacion`.
- Textos dinamicos actualizados:
  - corte simulado aclara que no cerro el turno real;
  - validacion de configuracion aclara que no crea ni modifica registros;
  - simulacion de devolucion aclara que no reembolso, no movio inventario y no creo kardex.
- Assets versionados:
  - `20260702-modos-accion1`.
- Validaciones:
  - `php -l` OK en `pos.php`, `caja_turnos.php`, `pos_configuracion.php`, `devoluciones.php`;
  - `node --check` OK en `caja_turnos.js`, `pos_configuracion.js`, `devoluciones.js`.

## Avance UX 2026-07-02 - guia de cierre real desde corte simulado

- En `/ventas/caja_turnos`, cuando `Simular corte sin cerrar` no tiene bloqueos:
  - se muestra el resumen de esperado/contado/diferencia;
  - se aclara que no se cerro el turno real;
  - se genera texto de autorizacion sugerida para copiar al chat.
- La autorizacion sugerida toma `id_usuario` desde la sesion actual.
- No se agrego boton de cierre real en UI.
- No se ejecuta escritura desde la pantalla.
- Asset actualizado:
  - `caja_turnos.js?v=20260702-cierre-guia1`.

## Avance UX 2026-07-02 - accion venta a devolucion

- En `/ventas/mostrar`, cada venta ahora muestra dos acciones:
  - ticket;
  - simular devolucion.
- La accion de devolucion abre `/ventas/devoluciones?folio=FOLIO`.
- `/ventas/devoluciones` precarga el folio de venta y muestra aviso de que solo simula.
- Tambien soporta `?folio_devolucion=DEV-...` para consultar ticket de devolucion.
- No aplica devolucion real, no reembolsa y no mueve inventario.
- Assets:
  - `listado.js?v=20260702-acciones-venta1`;
  - `devoluciones.js?v=20260702-acciones-venta1`.

## Avance UX 2026-07-02 - acciones post-cobro POS

- Despues de `Cobrar` real exitoso, el POS muestra acciones inmediatas:
  - `Ver ticket`: consulta ticket formal read-only y lo abre en modal POS;
  - `Ver venta`: abre `/ventas/mostrar?folio=FOLIO`;
  - `Caja/corte`: abre `/ventas/caja_turnos`;
  - `Simular devolucion`: abre `/ventas/devoluciones?folio=FOLIO`.
- `/ventas/mostrar` ahora lee `?folio=...` y precarga el filtro de busqueda.
- No se agregan escrituras nuevas.
- Assets:
  - `pos.js?v=20260702-post-cobro1`;
  - `listado.js?v=20260702-post-cobro1`.

## Pruebas read-only/dry-run 2026-07-02

- Sintaxis PHP OK:
  - `app/controladores/Ventas.php`;
  - `app/modelos/VentasErp.php`;
  - vistas POS/Ventas/Caja/Devoluciones/Configuracion;
  - sidebar;
  - scripts UAT de cierre/post-cierre/corte.
- Sintaxis JS OK:
  - `pos.js`;
  - `listado.js`;
  - `caja_turnos.js`;
  - `devoluciones.js`;
  - `pos_configuracion.js`.
- Corte simulado turno `TUR-20260630-002-002`:
  - `id_turno_caja=10`;
  - esperado `795`;
  - contado `795`;
  - diferencia `0`;
  - bloqueos `[]`;
  - no cerro turno real.
- Auditoria post-turno read-only:
  - turno sigue `abierto`;
  - venta `POS-20260701-000001` pagada por `295`;
  - pagos `295`;
  - movimientos de caja `795` (`500` apertura + `295` venta);
  - hallazgos esperados: turno no cerrado y contado real aun `0`.
- Postventa venta `POS-20260701-000001`:
  - detalle `295`;
  - pago `295`;
  - saldo `0`;
  - garantia snapshot `Sin garantia`;
  - kardex/trazabilidad movimiento `69`;
  - hallazgos `[]`.
- Ticket formal venta `POS-20260701-000001`:
  - generado read-only;
  - 28 lineas;
  - hallazgos `[]`;
  - incluye cliente CRM, caja, turno, garantia e inventario trazado.
- Devoluciones fisicas pendientes:
  - total `1`;
  - folio `DEV-20260630-000001`;
  - decision inventario `cuarentena`;
  - inspeccion fisica pendiente;
  - no movio inventario.
- Configuracion POS read-only:
  - schema completo;
  - cajas `2`;
  - terminales `2`;
  - asignaciones `2`;
  - turnos abiertos `1`.
- Dry-runs Configuracion POS:
  - caja nueva `CJ-UAT-DRY-02`: valida, sin bloqueos;
  - terminal nueva `TERM-UAT-DRY-02`: valida, sin bloqueos;
  - asignacion duplicada usuario `1`, almacen `5`, caja `2`, terminal `2`: bloqueada correctamente.
- Preflight venta SKU `1760`:
  - asignacion activa `true`;
  - turno abierto `true`;
  - venta real bloqueada correctamente por `Existencia insuficiente`;
  - stock candidatos almacen `5`: `0`.
- Listado ventas por folio:
  - `/ventas/mostrar?folio=POS-20260701-000001` tiene backend listo para devolver la venta filtrada.
- Devolucion simulada sobre `POS-20260701-000001` sin partidas:
  - bloquea correctamente con `Agrega partidas a devolver`.
- Ticket devolucion `DEV-20260630-000001`:
  - generado read-only;
  - decision financiera `saldo_favor`;
  - decision inventario `cuarentena`;
  - sin reembolso de caja;
  - sin movimiento de inventario.

## Avance UX 2026-07-02 - Caja movimientos y evidencias separados

- Nuevas rutas:
  - `/ventas/caja_movimientos`;
  - `/ventas/caja_evidencias`.
- Nuevas vistas:
  - `app/vistas/paginas/apps/erp/ventas/caja_movimientos.php`;
  - `app/vistas/paginas/apps/erp/ventas/caja_evidencias.php`.
- Nuevos assets:
  - `public/assets/js/custom/apps/erp/ventas/caja_movimientos.js`;
  - `public/assets/js/custom/apps/erp/ventas/caja_evidencias.js`.
- Menu POS actualizado:
  - `Movimientos caja`;
  - `Evidencias caja`.
- Contrato de movimientos:
  - consulta configuracion POS read-only;
  - simula gasto, retiro, entrada, vale interno o reembolso;
  - no registra dinero;
  - no cambia monto esperado del turno;
  - no adjunta evidencia;
  - indica si el movimiento requeriria autorizacion/evidencia en flujo real.
- Contrato de evidencias:
  - consulta movimientos que requieren evidencia;
  - consulta evidencias capturadas y correcciones;
  - no aprueba, rechaza, reemplaza ni corrige evidencias;
  - no mueve caja, dinero ni inventario.
- Pruebas:
  - `php -l` OK en controlador, sidebar y vistas nuevas;
  - `node --check` OK en JS nuevos;
  - dry-run gasto caja `50` valido, impacto esperado `-50`, requiere autorizacion/evidencia;
  - evidencias read-only: `2` movimientos, monto `345`, estados `aprobada` y `pendiente`;
  - detalle movimiento caja `20`: `2` evidencias, correccion `COR-EVC-20260630-000001` resuelta/aprobada.

## Avance UX 2026-07-02 - POS principal aligerado

- La barra de modulos del POS ya no abre Caja/Evidencias/Corte como modal principal.
- Nuevos accesos desde POS:
  - `Movimientos` -> `/ventas/caja_movimientos`;
  - `Evidencias` -> `/ventas/caja_evidencias`;
  - `Corte` -> `/ventas/caja_turnos`.
- El atajo `F8` ahora abre `/ventas/caja_movimientos`.
- Listeners legacy de modales Caja/Corte quedan protegidos como opcionales para evitar errores si se retiran los modales del HTML en una limpieza posterior.
- Asset POS versionado:
  - `pos.js?v=20260702-modulos-caja1`.
- Validaciones:
  - `php -l app/vistas/paginas/apps/erp/ventas/pos.php`: OK;
  - `node --check public/assets/js/custom/apps/erp/ventas/pos.js`: OK.

## Avance UX 2026-07-02 - Detalle read-only de venta

- Nueva ruta:
  - `/ventas/venta_detalle?folio=POS-...`.
- Nueva vista:
  - `app/vistas/paginas/apps/erp/ventas/venta_detalle.php`.
- Nuevo asset:
  - `public/assets/js/custom/apps/erp/ventas/venta_detalle.js`.
- Tablero `/ventas/mostrar` ahora incluye accion `Detalle`.
- Fuente de datos:
  - endpoint existente `/ventas/ticket_venta_readonly_erp`;
  - no se creo endpoint nuevo para evitar duplicar reglas.
- La pantalla muestra:
  - resumen de venta;
  - cliente CRM snapshot;
  - caja/turno/tienda;
  - partidas;
  - pagos;
  - garantia;
  - trazabilidad de inventario/kardex;
  - ticket formal e impresion.
- Contrato:
  - solo consulta;
  - no cobra;
  - no cancela;
  - no reembolsa;
  - no mueve inventario;
  - acciones reales se abren en modulos dedicados.
- Validaciones:
  - `php -l` OK en controlador, vista detalle y listado;
  - `node --check` OK en `venta_detalle.js` y `listado.js`;
  - ticket read-only `POS-20260701-000001` OK con 28 lineas y hallazgos `[]`.

## Avance UX 2026-07-02 - Limpieza final POS Caja/Corte

- El POS principal ya no incluye HTML de los modales legacy:
  - `pos_caja_modal`;
  - `pos_corte_modal`.
- El cobro real exitoso ahora muestra `Ver venta` apuntando a:
  - `/ventas/venta_detalle?folio=...`.
- El POS mantiene accesos a modulos dedicados:
  - Movimientos;
  - Evidencias;
  - Corte.
- Contrato:
  - no cambia cobro;
  - no cambia inventario;
  - no cambia caja real;
  - no cambia devoluciones;
  - solo reduce superficie UI del POS mostrador.
- Validaciones:
  - `php -l app/vistas/paginas/apps/erp/ventas/pos.php`: OK;
  - `node --check public/assets/js/custom/apps/erp/ventas/pos.js`: OK;
  - ticket read-only `POS-20260701-000001`: OK, hallazgos `[]`.

## Avance UX 2026-07-02 - Modulo Pedidos/Apartados dedicado

- La ruta `/ventas/pedidos` ya no reutiliza el listado general.
- Nueva vista:
  - `app/vistas/paginas/apps/erp/ventas/pedidos.php`.
- Nuevo asset:
  - `public/assets/js/custom/apps/erp/ventas/pedidos.js`.
- La pantalla permite:
  - consultar pedidos y apartados ERP;
  - filtrar por tipo, estatus y texto;
  - abrir detalle read-only por folio;
  - precargar folio para abono;
  - simular abono sin registrar pago ni caja.
- Contrato:
  - no crea pedido;
  - no reserva inventario;
  - no registra abono real;
  - no mueve caja;
  - no descuenta inventario.
- Validaciones:
  - `php -l app/controladores/Ventas.php`: OK;
  - `php -l app/vistas/paginas/apps/erp/ventas/pedidos.php`: OK;
  - `node --check public/assets/js/custom/apps/erp/ventas/pedidos.js`: OK;
  - `uat_ventas_pos_cliente_precio_apartado_readonly.php`: OK;
  - dry-run de abono bloquea correctamente si falta caja/turno.

## Avance tecnico 2026-07-02 - UAT y guardrail abono pedido/apartado

- Nuevo script UAT:
  - `storage/uat/uat_ventas_pos_pedidos_apartados_readonly.php`.
- El script valida:
  - contexto POS usuario/caja/turno;
  - conteo de pedidos;
  - conteo de apartados;
  - dry-run de abono;
  - contrato sin escrituras.
- Refuerzo en `VentasErp::apartadoAbonoDryRun`:
  - valida que el folio/id exista;
  - valida que sea `pedido` o `apartado`;
  - bloquea estatus no abonables;
  - bloquea saldo pendiente cero;
  - avisa si el monto excede el saldo.
- UAT actual:
  - no hay pedidos/apartados en muestra UAT;
  - abono a `APT-UAT-000001` queda bloqueado correctamente por `Pedido/apartado no encontrado`;
  - no hubo escrituras de BD.

## Avance documentacion 2026-07-03 - Checklist de cierre UAT

- Se creo una guia corta para terminar el modulo sin perder foco:
  - `docs/erp_ventas_pos_cierre_checklist.md`.
- La guia concentra:
  - estado actual del POS;
  - pruebas manuales disponibles;
  - pruebas tecnicas sin escritura;
  - pendientes para considerar POS cerrado en UAT;
  - autorizaciones recomendadas en orden;
  - criterios de cierre funcional.
- Proximo paso operativo recomendado:
  - cerrar turno UAT abierto con monto contado `795`;
  - despues avanzar flujo real de pedidos/apartados o CRUD de configuracion POS.

## Avance tecnico 2026-07-03 - Dry-run robusto Pedidos/Apartados

- Se reforzo `VentasErp::pedidoReservaDryRun` sin agregar escrituras:
  - valida cliente por nombre, id o identificador publico;
  - valida fecha compromiso con formato `Y-m-d`;
  - bloquea fechas anteriores a hoy;
  - resuelve politica activa de apartado;
  - calcula anticipo minimo por porcentaje/monto;
  - valida vigencia maxima del apartado;
  - valida si la politica permite abonos;
  - genera propuesta de reservas desde el plan de salida de inventario;
  - deja contrato explicito para eventos, reserva, consumo/liberacion y kardex al entregar.
- Se amplio `storage/uat/uat_ventas_pos_pedidos_apartados_readonly.php`:
  - ahora simula reserva de pedido/apartado;
  - conserva simulacion de abono;
  - sigue sin crear pedido, pago, caja, reserva ni kardex.
- UAT 2026-07-03:
  - contexto POS: almacen `5`, caja `2`, turno `10`;
  - politica `POS_APARTADO_UAT`;
  - anticipo minimo `59`;
  - pago simulado `100`;
  - saldo estimado `195`;
  - reserva bloqueada por `Existencia insuficiente`;
  - abono fake bloqueado por `Pedido/apartado no encontrado`;
  - no hubo escrituras de BD.
- Se preparo runbook del flujo real:
  - `docs/erp_ventas_pos_pedidos_apartados_real_runbook.md`.
  - Incluye endpoints objetivo, transacciones, estados, UAT minima y autorizacion requerida.

## Avance UX 2026-07-03 - Simulador de reserva en Pedidos/Apartados

- Se amplio `/ventas/pedidos` con panel `Simular pedido/apartado`.
- Se agrego acceso directo `Pedidos` en la barra de modulos del POS principal.
- La UI captura:
  - tipo `pedido` o `apartado`;
  - fecha compromiso;
  - almacen/caja/turno;
  - metodo de anticipo;
  - cliente e identificador;
  - SKU, cantidad, precio;
  - anticipo y referencia.
- El panel llama `/ventas/pedido_reserva_dryrun_erp`.
- Renderiza:
  - politica de apartado;
  - anticipo minimo;
  - pagado simulado;
  - saldo estimado;
  - fecha maxima;
  - bloqueos/avisos;
  - reservas que se crearian.
- Contrato:
  - no crea pedido;
  - no aparta inventario;
  - no mueve caja;
  - no genera kardex.
- Validaciones:
  - `php -l app/vistas/paginas/apps/erp/ventas/pos.php`: OK;
  - `php -l app/vistas/paginas/apps/erp/ventas/pedidos.php`: OK;
  - `node --check public/assets/js/custom/apps/erp/ventas/pedidos.js`: OK;
  - UAT read-only de pedidos/apartados: OK, bloqueo esperado por `Existencia insuficiente`.

## Avance UX 2026-07-03 - sincronizacion almacenes/cajas/turnos en Pedidos

- En `/ventas/pedidos`, los formularios de reserva y abono sincronizan:
  - turno -> almacen/caja;
  - almacen -> caja/turno compatible;
  - caja -> almacen/turno compatible.
- La fecha compromiso por defecto se calcula en fecha local, no UTC.
- Contrato:
  - solo mejora captura;
  - no agrega escrituras;
  - no cambia reglas de backend.
- Validaciones:
  - `php -l app/vistas/paginas/apps/erp/ventas/pedidos.php`: OK;
  - `node --check public/assets/js/custom/apps/erp/ventas/pedidos.js`: OK;
  - UAT read-only de pedidos/apartados: OK.

## Avance UX 2026-07-03 - claridad POS vs modulo Pedidos

- En `/ventas/pos`, el boton `Pedido` se renombro a `Simular pedido`.
- En `/ventas/pos`, el boton `Pedidos` abre `/ventas/pedidos`.
- Se agrego atajo `F10` para abrir `/ventas/pedidos`.
- Se conserva `F9` para prevalidar.
- Contrato:
  - `Simular pedido` no crea folio ni reserva;
  - `Pedidos` abre seguimiento/dry-run dedicado;
  - no se agregaron escrituras.
- Validaciones:
  - `php -l app/vistas/paginas/apps/erp/ventas/pos.php`: OK;
  - `node --check public/assets/js/custom/apps/erp/ventas/pos.js`: OK.

## Avance tecnico 2026-07-03 - readiness consolidado POS

- Se creo script read-only:
  - `storage/uat/uat_ventas_pos_readiness_readonly.php`.
- Se agrego endpoint read-only:
  - `/ventas/pos_readiness_readonly_erp`.
- Se agrego panel en UI:
  - `/ventas/caja_turnos`, seccion `Readiness POS`.
- Si readiness detecta turno abierto y diferencia `0`, muestra una autorizacion sugerida para cierre real.
- La autorizacion sugerida no ejecuta nada desde UI; solo genera texto para copiar al chat.
- La autorizacion sugerida ahora incluye boton `Copiar autorizacion`.
- El script CLI acepta `--compact=1` o `--compacto=1` para evidencia resumida sin exponer todo el detalle del ticket.
- El script consolida:
  - asignacion POS;
  - turno abierto;
  - cierre dry-run;
  - ticket formal read-only;
  - reserva dry-run de apartado;
  - abono dry-run;
  - devoluciones fisicas pendientes.
- UAT actual:
  - asignacion activa `true`;
  - turno abierto `true`;
  - cierre diferencia `0`;
  - ticket `28` lineas;
  - reserva bloqueada por `Existencia insuficiente`;
  - abono bloqueado por `Pedido/apartado no encontrado`;
  - devoluciones fisicas pendientes `1`.
- Contrato:
  - no escribe BD;
  - no cierra turno;
  - no crea pedido;
  - no registra abono;
  - no reserva inventario;
  - no mueve kardex.
- Validaciones:
  - `php -l app/modelos/VentasErp.php`: OK;
  - `php -l app/controladores/Ventas.php`: OK;
  - `php -l app/vistas/paginas/apps/erp/ventas/caja_turnos.php`: OK;
  - `node --check public/assets/js/custom/apps/erp/ventas/caja_turnos.js`: OK;
  - `php -l storage/uat/uat_ventas_pos_readiness_readonly.php`: OK;
  - ejecucion read-only compacta: OK.

## Avance UX 2026-07-03 - navegacion caja/configuracion POS

- Se agrego script read-only:
  - `storage/uat/uat_ventas_pos_configuracion_readonly.php`.
- Se normalizo la navegacion superior entre:
  - `/ventas/caja_turnos`;
  - `/ventas/caja_movimientos`;
  - `/ventas/caja_evidencias`;
  - `/ventas/pos_configuracion`;
  - `/ventas/pos`;
  - `/ventas/mostrar`.
- En Configuracion POS se sincronizan selectores para evitar combinaciones incoherentes:
  - tienda -> cajas compatibles;
  - asignacion tienda/caja -> terminal compatible;
  - terminal -> caja de la misma tienda.
- Contrato:
  - no escribe BD;
  - no crea caja;
  - no crea terminal;
  - no modifica asignaciones;
  - solo mejora navegacion y captura dry-run.
- UAT actual:
  - usuario `1` asignado a almacen `5`, caja `2`, terminal `2`;
  - asignacion activa `true`;
  - turno abierto `true`;
  - esquema pendiente `false`;
  - `2` cajas, `2` terminales y `2` asignaciones;
  - hallazgos `[]`.

## Avance tecnico 2026-07-03 - preflight cierre turno POS

- Se agrego script read-only:
  - `storage/uat/uat_ventas_pos_turno_cierre_preflight_readonly.php`.
- Proposito:
  - validar cierre real antes de usar el aplicador autorizado;
  - generar autorizacion sugerida y comando aplicador;
  - evitar confundir simulacion con cierre real.
- UAT actual:
  - turno `TUR-20260630-002-002`;
  - `id_turno_caja=10`;
  - almacen `5`, caja `2`;
  - esperado `795`;
  - contado `795`;
  - diferencia `0`;
  - bloqueos `[]`.
- Contrato:
  - no escribe BD;
  - no cierra turno;
  - no modifica caja;
  - no mueve dinero.

## Avance tecnico 2026-07-03 - post-cierre y stock siguiente UAT

- `storage/uat/uat_ventas_pos_turno_post_cierre_readonly.php` acepta `--compact=1`.
- Estado actual antes del cierre real:
  - turno `TUR-20260630-002-002`;
  - estatus `abierto`;
  - hallazgos esperados porque falta autorizacion de cierre real.
- `storage/uat/uat_ventas_pos_stock_uat_preflight_readonly.php` acepta:
  - `--id_usuario`;
  - `--respaldo`;
  - `--referencia`.
- Preflight stock listo para siguiente UAT:
  - almacen `5`;
  - SKU `1760`;
  - cantidad `1`;
  - referencia `INV-INICIAL-POS-UAT-20260703-A5-S1760-CIERRE1`;
  - bloqueos `[]`.
- Contrato:
  - no escribe BD;
  - no carga stock;
  - no mueve kardex.

## Avance tecnico 2026-07-03 - secuencia UAT siguiente

- `storage/uat/uat_ventas_pos_turno_preflight_readonly.php` ahora genera autorizacion sugerida y comando aplicador cuando no hay bloqueos.
- `storage/uat/uat_ventas_pos_venta_preflight_readonly.php` ahora acepta `--respaldo` y `--cliente`, y genera autorizacion sugerida/comando aplicador cuando puede vender.
- `storage/uat/uat_ventas_pos_pedidos_apartados_readonly.php` acepta `--compact=1`.
- Estado actual de la secuencia:
  - apertura siguiente turno bloqueada por turno abierto actual;
  - venta siguiente bloqueada por stock insuficiente;
  - pedido/apartado bloqueado por stock insuficiente y sin folio real de apartado;
  - estos bloqueos son esperados hasta cerrar turno, cargar stock y autorizar flujo real de pedidos/apartados.
- Contrato:
  - no escribe BD;
  - no abre turno;
  - no vende;
  - no crea pedido;
  - no reserva inventario.

## Bloqueo tecnico 2026-07-03 - MySQL UAT inestable

- Al ejecutar readiness/preflight consolidado, MariaDB local dejo de responder.
- `mysqladmin ping` marco `Can't connect to MySQL server on '127.0.0.1' (10061)`.
- Se intento iniciar `mysqld`; respondio temporalmente, pero despues las consultas devolvieron `MySQL server has gone away`.
- Log `C:\xampp\mysql\data\mysql_error.log` contiene historial InnoDB:
  - `InnoDB: corruption in the InnoDB tablespace`;
  - `InnoDB: Assertion failure`.
- Respaldo externo verificado:
  - `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`;
  - tamano `27216189` bytes.
- Documento creado:
  - `docs/erp_ventas_pos_bloqueo_mysql_uat.md`.
- Script read-only creado:
  - `storage/uat/uat_mysql_pos_recovery_preflight_readonly.php`.
- Preflight recuperacion:
  - respaldo existe;
  - binarios `mysqld.exe`, `mysql.exe` y `my.ini` existen;
  - bloqueos `[]`;
  - comandos de recuperacion quedan propuestos, no ejecutados.
- Contrato:
  - no se modifico `my.ini`;
  - no se ejecuto restauracion;
  - no se ejecuto recovery mode;
  - no se escribio BD.
- Antes de cerrar turno POS real, recuperar MySQL con autorizacion robusta.
- Revalidacion posterior:
  - se creo aplicador protegido `storage/uat/uat_mysql_pos_recovery_apply_authorized.php`;
  - sin token bloquea correctamente;
  - fase `diagnostico` con token solo valida entradas y ping;
  - `mysqladmin ping`: `mysqld is alive`;
  - readiness POS compacto posterior: OK, turno `10`, diferencia `0`.
- Estado actualizado: MySQL volvio a responder para lectura read-only, pero queda antecedente de inestabilidad. Revalidar ping/readiness justo antes de cualquier escritura real.

## Evidencia UAT 2026-07-03 - turno POS cerrado

- Se ejecuto cierre real autorizado del turno `TUR-20260630-002-002`.
- Comando:
  - `storage/uat/uat_ventas_pos_turno_cierre_apply_authorized.php --autorizar=VENTAS_POS_TURNO_CIERRE ...`
- Resultado:
  - `ok=true`;
  - `id_turno_caja=10`;
  - almacen `5`;
  - caja `2`;
  - esperado `795`;
  - contado `795`;
  - diferencia `0`;
  - estatus post-cierre `cerrado`;
  - fecha cierre `2026-07-03 21:03:05`;
  - hallazgos post-cierre `[]`;
  - turnos abiertos actuales `0`.
- Preflight posterior:
  - apertura siguiente turno `puede_abrir_turno=true`;
  - monto inicial sugerido `500`.
- Siguiente paso con autorizacion:
  - abrir turno UAT siguiente;
  - cargar stock UAT para SKU `1760`.

## Evidencia UAT 2026-07-03 - turno nuevo y stock listos

- Se abrio turno UAT posterior al cierre:
  - `id_turno_caja=11`;
  - folio `TUR-20260703-002-001`;
  - caja `2`, almacen `5`;
  - monto inicial `500`;
  - movimiento caja apertura `23`.
- Se cargo stock UAT:
  - SKU `1760`;
  - cantidad `1`;
  - referencia `INV-INICIAL-POS-UAT-20260703-A5-S1760-CIERRE1`;
  - movimiento inventario generado por `InventarioErp`;
  - etiquetas generadas `0`.
- Verificacion posterior:
  - turno abierto `true`;
  - venta preflight SKU `1760` puede vender real `true`;
  - total `295`, pago `295`, bloqueos `[]`;
  - plan salida existencia `34`, antes `1`, despues `0`;
  - apartado dry-run ya no bloquea por stock;
  - abono fake bloquea por `Pedido/apartado no encontrado`.
- Siguiente autorizacion posible:
  - ejecutar venta POS real de SKU `1760`;
  - o preparar flujo real de pedidos/apartados.

## Evidencia UAT 2026-07-03 - venta POS real post-cierre

- Venta real ejecutada con respaldo UAT POS vigente.
- Resultado:
  - folio `POS-20260703-000001`;
  - `id_venta=11`;
  - estatus `pagada`;
  - total `295`;
  - pago efectivo `295`;
  - saldo `0`;
  - turno `11`, folio `TUR-20260703-002-001`;
  - movimiento caja `24`;
  - movimiento inventario/kardex `71`;
  - existencia `34` de `1` a `0`;
  - garantia snapshot `Sin garantia`;
  - ticket formal `28` lineas, hallazgos `[]`.
- Readiness posterior:
  - cierre diferencia `0`;
  - nueva venta SKU `1760` bloquea por `Existencia insuficiente`;
  - apartado dry-run bloquea por stock nuevamente;
  - devoluciones fisicas pendientes `1`.
- Siguiente paso:
  - cerrar turno `TUR-20260703-002-001` con monto contado `795`, o cargar stock si se desea probar apartado real antes del cierre.

## Decision operativa 2026-07-03 - respaldo por ciclo UAT

- El respaldo externo sigue siendo obligatorio antes de escrituras sensibles, pero no se pedira en cada peticion menor.
- Para el ciclo UAT POS actual queda como referencia vigente:
  - `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`.
- Repetir solicitud de respaldo solo cuando:
  - se aplique DDL o migracion;
  - se recupere/restaure MySQL;
  - se ejecute un bloque grande de escrituras;
  - cambie el alcance de modulo;
  - haya inestabilidad de BD;
  - se acumulen suficientes cambios como para cerrar un nuevo punto de respaldo.
- En autorizaciones chicas del mismo ciclo, pedir la accion concreta y registrar en evidencia que usa el respaldo UAT POS vigente.
- Si el usuario autoriza con `respaldo UAT POS vigente`, resolver internamente al path ya validado; no pedir que repita la ruta salvo nuevo ciclo/cambio fuerte.
- Scripts preflight ajustados para sugerir autorizaciones humanas con `respaldo UAT POS vigente`:
  - `uat_ventas_pos_turno_cierre_preflight_readonly.php`;
  - `uat_ventas_pos_turno_preflight_readonly.php`;
  - `uat_ventas_pos_venta_preflight_readonly.php`;
  - `uat_ventas_pos_stock_uat_preflight_readonly.php`.
- Los comandos aplicadores siguen usando la ruta tecnica validada.

## Revalidacion read-only 2026-07-03 - turno 11

- Turno actual:
  - `id_turno_caja=11`;
  - folio `TUR-20260703-002-001`;
  - caja `2`, almacen `5`;
  - estatus abierto.
- Cierre preflight:
  - monto esperado `795`;
  - monto contado simulado `795`;
  - diferencia `0`;
  - bloqueos `[]`.
- Ticket formal:
  - folio `POS-20260703-000001`;
  - `28` lineas;
  - hallazgos `[]`;
  - kardex/movimiento inventario `71`;
  - existencia `34` de `1` a `0`;
  - garantia snapshot `Sin garantia`.
- Venta nueva SKU `1760`:
  - bloqueada por `Existencia insuficiente`;
  - estado correcto porque el stock UAT quedo en `0`.
- Readiness compacto:
  - reserva/apartado bloqueada por stock;
  - abono fake bloqueado por `Pedido/apartado no encontrado`;
  - devoluciones fisicas pendientes `1`.
- Configuracion/acceso:
  - usuario `1` tiene `ventas.ver` y `ventas.operar`;
  - asignacion activa a almacen `5`, caja `2`, terminal `2`;
  - esquema POS completo sin hallazgos;
  - `turnos_abiertos=1`, esperado por el turno actual;
  - siguiente recomendado de configuracion: preparar CRUD real con autorizacion.
- Proxima autorizacion si se desea cerrar turno:
  - `AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS post venta POS-20260703-000001"`.

## Avance sin escritura 2026-07-03 - Configuracion POS real preparada

- Se preparo runbook de CRUD real de configuracion:
  - `docs/erp_ventas_pos_configuracion_real_runbook.md`.
- Alcance:
  - cajas;
  - terminales;
  - asignaciones usuario/caja/terminal;
  - desactivaciones seguras;
  - permisos finos futuros;
  - UAT minima.
- Decision UX documentada:
  - configuracion POS es administracion separada;
  - el POS mostrador no debe permitir selector libre de tienda/caja;
  - el contexto operativo se resuelve por usuario, asignacion, caja, terminal y turno.
- Manual UAT actualizado:
  - `Prueba 55 - Configuracion POS separada del mostrador`.
- Autorizaciones humanas normalizadas:
  - `respaldo UAT POS vigente` para configuracion y pedidos/apartados.
- Validaciones:
  - `node --check public\assets\js\custom\apps\erp\ventas\pos_configuracion.js`: OK;
  - `php -l app\controladores\Ventas.php`: OK;
  - `php -l app\modelos\VentasErp.php`: OK.
- Preflight actualizado:
  - `storage/uat/uat_ventas_pos_configuracion_readonly.php` ahora devuelve `autorizacion_sugerida` cuando no hay hallazgos;
  - salida actual: `ok=true`, `hallazgos=[]`, almacen `5`, caja `2`, terminal `2`, `turno_abierto=true`.
- Ajuste UX:
  - `/ventas/pos_configuracion` cambia `Validar alta = no crea registros` por `Validar sin crear = no guarda registros`;
  - asset `20260703-validar-sin-crear1`.
- No hubo escrituras de BD.

## Escritura autorizada 2026-07-03 - cierre turno 11 y CRUD config preparado

- Cierre real ejecutado:
  - folio `TUR-20260703-002-001`;
  - `id_turno_caja=11`;
  - estatus `cerrado`;
  - fecha cierre `2026-07-03 21:32:47`;
  - esperado `795`;
  - contado `795`;
  - diferencia `0`;
  - hallazgos post-cierre `[]`.
- Estado posterior:
  - turnos abiertos `0`;
  - SKU `1760` sigue sin stock disponible para venta/apartado;
  - readiness bloquea operaciones reales por falta de turno abierto, esperado despues del cierre.
- CRUD real Configuracion POS preparado bajo autorizacion `VENTAS_POS_CONFIG_CRUD`:
  - modelo:
    - `configuracionCajaGuardarReal`;
    - `configuracionTerminalGuardarReal`;
    - `configuracionAsignacionGuardarReal`;
    - `configuracionPosDesactivarReal`;
  - controlador:
    - `pos_configuracion_caja_guardar_erp`;
    - `pos_configuracion_terminal_guardar_erp`;
    - `pos_configuracion_asignacion_guardar_erp`;
    - `pos_configuracion_desactivar_erp`;
  - script UAT:
    - `storage/uat/uat_ventas_pos_configuracion_apply_authorized.php`.
- Pruebas:
  - script sin parametros bloquea;
  - script con token pero sin datos obligatorios devuelve warning y no inserta;
  - conteos quedan `2` cajas, `2` terminales, `2` asignaciones, `0` turnos abiertos.
- Siguiente autorizacion util:
  - alta real controlada de caja/terminal/asignacion UAT, o abrir turno + cargar stock para pedidos/apartados.

## Decision operativa 2026-07-03 - cierre con diferencias y reportes

- Regla confirmada por negocio:
  - una caja puede cerrarse aunque no cuadre en cero;
  - diferencia positiva = sobrante;
  - diferencia negativa = faltante;
  - la diferencia no debe bloquear el cierre;
  - la diferencia debe quedar registrada para supervision, reportes y seguimiento por empleado/caja/sucursal.
- Backend:
  - `VentasErp::cierreTurnoDryRun` conserva cierre valido con diferencia distinta de cero;
  - agrega aviso de sobrante/faltante;
  - contrato ahora declara `permite_cerrar_con_diferencia=true` y `diferencia_alimenta_reportes=true`;
  - `readinessPosReadOnly` expone `cierre_bloqueos` y `cierre_requiere_revision`.
- UI:
  - `caja_turnos.js` muestra advertencia si hay sobrante/faltante;
  - sigue generando autorizacion sugerida aunque la diferencia no sea cero, siempre que no existan bloqueos reales;
  - autorizaciones sugeridas usan `respaldo UAT POS vigente`;
  - asset `20260703-diferencias-caja1`.
- Vista:
  - `/ventas/caja_turnos` agrega badge `Diferencias quedan en reportes`.
- Plan creado:
  - `docs/erp_ventas_pos_reportes_caja_plan.md`.
- Siguiente fase recomendada:
  - reporte read-only de caja/diferencias por turno, empleado, caja y sucursal;
  - despues flujo formal de revision de diferencias con evidencia y resolucion.

## Avance 2026-07-03 - area inicial de Reportes POS

- Nueva vista:
  - `/ventas/reportes`.
- Nuevo endpoint:
  - `/ventas/reportes_caja_erp`.
- Nuevo modelo read-only:
  - `VentasErp::reporteCajaPosReadOnly`.
- Nuevo JS:
  - `public/assets/js/custom/apps/erp/ventas/reportes.js`.
- Nuevo script UAT:
  - `storage/uat/uat_ventas_pos_reportes_caja_readonly.php`.
- Reporte inicial:
  - filtros por fecha y solo diferencias;
  - KPIs de turnos, turnos con diferencia, faltantes y sobrantes;
  - tabla de turnos con esperado, contado, diferencia, estado, usuarios y sucursal/caja.
- Agregado por empleado:
  - `por_usuario` en backend;
  - tabla `Diferencias por empleado` en UI;
  - turnos, turnos con diferencia, porcentaje, faltantes, sobrantes y neto.
- UAT read-only:
  - `11` turnos consultados;
  - `0` turnos con diferencia;
  - primer usuario `Usuario 1`, `11` turnos, `0` diferencias;
  - primer resumen por caja: almacen `MASCOTAS971`, caja `CJ-MASCOTAS971-01`, `11` turnos, `0` diferencias;
  - filtro solo diferencias devuelve `0`;
  - sin escrituras de BD.

## Avance 2026-07-03 - UAT diferencias preparada

- Navegacion:
  - `/ventas/mostrar` ahora enlaza a Reportes;
  - `/ventas/pos` ahora enlaza a Reportes en la barra de modulos.
- Script read-only:
  - `storage/uat/uat_ventas_pos_cierre_diferencias_readonly.php`.
- Contrato:
  - no escribe BD;
  - no cierra turno;
  - simula cuadrado/faltante/sobrante cuando hay turno abierto.
- Resultado actual:
  - bloquea correctamente porque no hay turno abierto;
  - asignacion usuario `1` sigue activa a almacen `5`, caja `2`, terminal `2`.
- Para probar diferencia real despues:
  - abrir turno;
  - registrar movimiento/venta o usar monto inicial;
  - simular diferencias;
  - autorizar cierre con contado distinto;
  - validar `/ventas/reportes`.

## Avance 2026-07-03 - preflight consolidado para diferencia real

- Script revisado:
  - `storage/uat/uat_ventas_pos_ciclo_uat_preflight_readonly.php`.
- Ajustes:
  - las autorizaciones humanas usan `respaldo UAT POS vigente`;
  - si no hay turno abierto, no sugiere cierre real;
  - devuelve `siguiente_paso` operativo.
- Resultado actual:
  - asignacion usuario `1` activa;
  - almacen `5`;
  - caja `2`;
  - sin turno abierto;
  - stock SKU `1760` insuficiente para venta/apartado;
  - siguiente paso: abrir turno y cargar stock.
- Autorizaciones preparadas:
  - abrir turno con monto inicial `500`;
  - cargar stock UAT SKU `1760` cantidad `1` con referencia `INV-INICIAL-POS-UAT-DIFERENCIA-01`.
- Contrato:
  - no escribe BD;
  - no abre turno;
  - no carga stock;
  - no vende;
  - no cierra turno.

## Escritura autorizada 2026-07-03 - turno y stock para diferencia caja

- Se abrio turno UAT:
  - `id_turno_caja=12`;
  - folio `TUR-20260703-002-002`;
  - almacen `5`;
  - caja `2`;
  - movimiento caja `25`;
  - monto inicial `500`.
- Se cargo stock UAT:
  - SKU `1760`;
  - cantidad `1`;
  - referencia `INV-INICIAL-POS-UAT-DIFERENCIA-01`;
  - movimientos inventario `1`.
- Preflight de venta posterior:
  - `puede_vender_real=true`;
  - total `295`;
  - pago `295`;
  - existencia `34` de `1` a `0` estimado al vender.
- Simulacion cierre antes de venta:
  - esperado `500`;
  - contado `500`, diferencia `0`;
  - contado `490`, diferencia `-10`;
  - contado `510`, diferencia `10`;
  - sin bloqueos.
- Siguiente autorizacion recomendada:
  - ejecutar venta real POS de SKU `1760`;
  - despues cerrar turno con contado `785` para generar faltante `-10` contra esperado `795`.

## Escritura autorizada 2026-07-03 - venta y cierre con faltante

- Venta real:
  - folio `POS-20260703-000002`;
  - `id_venta=12`;
  - turno `12`;
  - total `295`;
  - pago efectivo `295`;
  - movimiento caja `26`;
  - movimiento inventario `73`;
  - existencia `34` de `1` a `0`;
  - garantia snapshot `Sin garantia`.
- Cierre real:
  - turno `TUR-20260703-002-002`;
  - `id_turno_caja=12`;
  - esperado `795`;
  - contado `785`;
  - diferencia `-10`;
  - clasificacion esperada `faltante`.
- Reporte caja read-only:
  - con `solo_diferencias=1` devuelve `1` turno;
  - faltantes total `10`;
  - sobrantes total `0`;
  - diferencia neta `-10`;
  - `por_usuario`: Usuario `1`, `1` turno con diferencia;
  - `por_caja`: `MASCOTAS971` / `CJ-MASCOTAS971-01`, `1` turno con diferencia.
- Estado posterior:
  - sin turno abierto;
  - SKU `1760` queda sin stock disponible;
  - ticket formal `POS-20260703-000002` genera `28` lineas sin hallazgos;
  - post-venta sin hallazgos.
- Siguiente recomendado:
  - revisar en UI `/ventas/reportes` con filtro `Solo con diferencia`;
  - despues avanzar flujo de revision/resolucion de diferencias de caja, para que faltantes/sobrantes tengan responsable, evidencia y cierre administrativo.

## Avance 2026-07-03 - DDL revision diferencias preparado

- Autorizacion recibida: preparar DDL, no aplicar.
- Preparado:
  - `VentasErpEsquema::planActualizarRevisionDiferenciasCajaPos`;
  - `VentasErpEsquema::auditarRevisionDiferenciasCajaPos`;
  - endpoints de auditoria/actualizacion de esquema;
  - scripts UAT read-only/apply protegido.
- Auditoria actual:
  - tabla `erp_pos_turnos_diferencias_revision` no existe;
  - plan genera `CREATE TABLE` sin ejecutar;
  - aplicador sin parametros bloquea correctamente.
- Siguiente autorizacion requerida para crear tabla:
  - `AUTORIZO APLICAR DDL REVISION DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_REVISION_DDL para UAT POS`.

## Escritura autorizada 2026-07-03 - DDL revision diferencias aplicado

- Tabla creada:
  - `erp_pos_turnos_diferencias_revision`.
- Auditoria posterior:
  - columnas esperadas existen;
  - indices esperados existen.
- Verificacion de negocio:
  - turno `TUR-20260703-002-002` conserva diferencia `-10`;
  - reporte caja sigue mostrando faltante `10`;
  - bandeja de diferencias cambia a `schema_revision_pendiente=false`;
  - aun no hay expediente formal (`id_diferencia_revision=null`).
- Siguiente paso:
  - preparar/aplicar flujo para crear expediente de revision del turno `12`;
  - despues resolverlo como `explicada`, `aceptada`, `ajustada` o `escalada`.

## Escritura autorizada 2026-07-03 - expediente diferencia caja

- Se registro expediente formal:
  - `id_diferencia_revision=1`;
  - folio `DIF-CAJ-20260703-000001`;
  - turno `TUR-20260703-002-002`;
  - `id_turno_caja=12`;
  - tipo `faltante`;
  - monto `-10`;
  - estatus `pendiente_revision`;
  - motivo `UAT faltante de caja controlado`;
  - responsable `Supervisor UAT`;
  - evidencia `FALTANTE-UAT-001`.
- Verificacion:
  - bandeja de diferencias read-only devuelve `1` registro;
  - resumen: faltantes total `10`, diferencia neta `-10`;
  - reporte caja sigue mostrando `1` turno con diferencia;
  - no se modifico el turno;
  - no se movio caja;
  - no se movio inventario.
- Siguiente paso recomendado:
  - preparar resolucion formal del expediente;
  - mantener la diferencia historica visible aunque quede explicada o escalada.

## Avance 2026-07-03 - resolucion diferencia caja preparada

- Preparado:
  - `VentasErp::resolverRevisionDiferenciaCajaPosReal`;
  - `storage/uat/uat_ventas_pos_diferencia_revision_resolver_apply_authorized.php`.
- Prueba negativa:
  - sin token/respaldo/usuario/folio/decision/motivo queda bloqueado;
  - no escribe BD.
- Estado actual:
  - expediente `DIF-CAJ-20260703-000001` sigue `pendiente_revision`;
  - faltante historico sigue `-10`;
  - reportes siguen mostrando faltante total `10`.
- Siguiente autorizacion fuerte:
  - resolver el expediente como `explicada` para UAT sin ajuste de caja.

## Escritura autorizada 2026-07-03 - diferencia caja resuelta

- Expediente resuelto:
  - folio `DIF-CAJ-20260703-000001`;
  - `id_diferencia_revision=1`;
  - decision `explicada`;
  - estatus `explicada`;
  - motivo `UAT faltante explicado sin ajuste de caja`;
  - evidencia `FALTANTE-UAT-001`;
  - fecha resolucion `2026-07-03 22:50:26`.
- Validacion posterior:
  - pendientes de revision `0`;
  - bandeja todos: `1` registro explicado;
  - reporte caja conserva faltante historico `-10`;
  - faltantes total `10`;
  - diferencia neta `-10`.
- Contrato cumplido:
  - no se modifico `erp_pos_turnos`;
  - no se movio caja;
  - no se movio inventario.
- Siguiente recomendado:
  - exponer acciones de resolver diferencias en UI con permiso fino;
  - despues avanzar reporte ejecutivo y/o CRUD de configuracion POS.

## Avance 2026-07-03 - resolucion diferencias expuesta en UI

- Endpoint nuevo:
  - `/ventas/reportes_diferencia_caja_resolver_erp`.
- Seguridad:
  - POST con CSRF;
  - permiso `ventas.caja_diferencias.resolver`;
  - auditoria explicita `pos_diferencia_caja_resolver`.
- UI:
  - `/ventas/reportes` permite filtrar seguimiento por estado;
  - `Pendientes`, `Todos`, `En revision`, `Explicadas`, `Aceptadas`, `Ajustadas`, `Escaladas`, `Canceladas`;
  - boton `Resolver` solo para expedientes abiertos;
  - modal pide decision, motivo y evidencia.
- Validacion:
  - sintaxis PHP/JS correcta;
  - consulta read-only devuelve folio de revision `DIF-CAJ-20260703-000001`;
  - expediente actual queda `explicada`.
- Pendiente:
  - UAT visual en navegador de `/ventas/reportes`;
  - confirmar con roles productivos que solo supervisores/finanzas tengan `ventas.caja_diferencias.resolver`.

## UAT visual 2026-07-03 - reportes y diferencias

- Script:
  - `storage/uat/uat_ventas_pos_reportes_playwright_uat.js`.
- Resultado:
  - `ok=true`;
  - login local correcto;
  - URL final `/ventas/reportes`;
  - filtro estado revision existe;
  - estado seleccionado `todos`;
  - diferencia explicada visible;
  - filas seguimiento `1`;
  - botones resolver visibles `0`, esperado porque el expediente ya esta cerrado;
  - no ejecuta resolucion;
  - no mueve caja;
  - no mueve inventario.
- Captura:
  - `public/storage/uat/pos_reportes_diferencias_uat.png`.
- Ajuste UX aplicado despues de primera captura:
  - copy cambia de `no se corrigen desde aqui` a `Las diferencias pueden cerrarse administrativamente sin mover caja ni inventario`.
- Pendiente siguiente:
  - preparar permisos finos de diferencias de caja;
  - despues avanzar CRUD real de configuracion POS o flujo real de pedidos/apartados, segun prioridad.

## Avance 2026-07-03 - permisos finos diferencias caja preparados

- Permisos:
  - `ventas.caja_diferencias.ver`;
  - `ventas.caja_diferencias.revisar`;
  - `ventas.caja_diferencias.resolver`.
- Backend:
  - `SeguridadEsquema.php` declara permisos y roles base;
  - endpoint resolver requiere `ventas.caja_diferencias.resolver`;
  - modelo valida `ventas.caja_diferencias.resolver`.
- Scripts:
  - `storage/uat/uat_ventas_pos_diferencias_permisos_readonly.php`;
  - `storage/uat/uat_ventas_pos_diferencias_permisos_apply_authorized.php`.
- Auditoria read-only:
  - tablas seguridad existen;
  - permisos aun no existen en BD;
  - aplicador sin token bloqueado.
- Siguiente autorizacion:
  - sembrar permisos y relaciones por rol sin tocar turnos/caja/inventario.

## Escritura autorizada 2026-07-03 - permisos diferencias caja

- Permisos sembrados:
  - `ventas.caja_diferencias.ver`;
  - `ventas.caja_diferencias.revisar`;
  - `ventas.caja_diferencias.resolver`.
- Resultado:
  - permisos total `3`;
  - relaciones intentadas `11`;
  - faltantes posteriores `[]`.
- Roles:
  - `administrador_erp`, `direccion`, `finanzas_contabilidad`: ver/revisar/resolver;
  - `auditor`: ver;
  - `ventas`: ver.
- Contrato:
  - no asigna usuarios directo;
  - no toca turnos;
  - no mueve caja;
  - no mueve inventario.
- Nota:
  - recargar permisos/cerrar sesion para que la UI refleje permisos nuevos en la sesion.

## UAT visual post-permisos 2026-07-03

- `/ventas/reportes` carga correctamente con login nuevo;
- filtro de estado revision disponible;
- estado `todos`;
- diferencia explicada visible;
- no hay boton resolver para expediente cerrado;
- auditoria permisos: faltantes `[]`;
- no se ejecuto ninguna resolucion;
- no se movio caja ni inventario.
- Siguiente recomendado:
  - validar roles productivos de supervision/finanzas;
  - continuar con CRUD real de Configuracion POS o flujo de Pedidos/Apartados.

## Cierre 2026-07-03 - resolver diferencias solo con permiso fino

- Se retiro compatibilidad temporal con `ventas.operar` para resolver diferencias.
- Controlador y modelo requieren `ventas.caja_diferencias.resolver`.
- Usuario UAT `1` ya tiene ver/revisar/resolver.
- UAT visual de reportes sigue `ok=true`.
- Pendiente operativo:
  - confirmar roles reales de usuarios antes de productivo;
  - si un supervisor no ve/puede resolver, asignar rol adecuado desde Seguridad.

## Avance 2026-07-04 - Configuracion POS final preparada

- Se inicio cierre de Configuracion POS como modulo separado del mostrador.
- Cambios preparados sin escritura de BD:
  - `SeguridadEsquema.php` declara permisos finos `ventas.pos_config.*`;
  - `Ventas::pos_configuracion` y resumen requieren `ventas.pos_config.ver`;
  - guardar caja/terminal requiere `ventas.pos_config.crear` o `ventas.pos_config.editar`;
  - guardar asignacion requiere `ventas.pos_config.asignar_usuario`;
  - desactivar requiere `ventas.pos_config.desactivar`;
  - Core marca endpoints de Configuracion POS como auditoria explicita para evitar auditoria generica duplicada;
  - sidebar usa `ventas.pos_config.ver`;
  - `/ventas/pos_configuracion` queda con UI CRUD preparada: editar, guardar, desactivar con motivo y limpiar captura.
- Scripts nuevos:
  - `storage/uat/uat_ventas_pos_configuracion_permisos_readonly.php`;
  - `storage/uat/uat_ventas_pos_configuracion_permisos_apply_authorized.php`.
- Validaciones:
  - `php -l` OK en `Ventas.php`, `SeguridadEsquema.php`, `pos_configuracion.php` y scripts nuevos;
  - `node --check` OK en `pos_configuracion.js`;
  - auditor read-only confirma configuracion base sana: 2 cajas, 2 terminales, 2 asignaciones, 0 turnos abiertos;
  - auditor read-only confirma permisos `ventas.pos_config.*` faltantes en BD.
- Efecto operativo esperado:
  - hasta sembrar permisos, la pantalla de Configuracion POS queda protegida.
- Proxima autorizacion robusta:

```text
AUTORIZO SEMBRAR PERMISOS CONFIGURACION POS usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_PERMISOS para UAT POS
```

## Escritura autorizada 2026-07-04 - permisos Configuracion POS

- Permisos sembrados:
  - `ventas.pos_config.ver`;
  - `ventas.pos_config.crear`;
  - `ventas.pos_config.editar`;
  - `ventas.pos_config.desactivar`;
  - `ventas.pos_config.asignar_usuario`.
- Resultado:
  - permisos total `5`;
  - relaciones intentadas `11`;
  - faltantes posteriores `[]`.
- Roles:
  - `administrador_erp`, `direccion`: ver/crear/editar/desactivar/asignar usuario;
  - `auditor`: ver.
- Usuario UAT `1`:
  - tiene los 5 permisos.
- Configuracion POS posterior:
  - almacén `5`, caja `2`, terminal `2`;
  - asignacion activa `true`;
  - turno abierto `false`;
  - 2 cajas, 2 terminales, 2 asignaciones.
- Contrato:
  - no asigna usuarios directo;
  - no abre turno;
  - no mueve caja;
  - no mueve inventario.
- Siguiente recomendado:
  - UAT visual de `/ventas/pos_configuracion`;
  - luego CRUD real controlado para editar/crear/desactivar configuracion POS.

## UAT visual 2026-07-04 - Configuracion POS

- Playwright local valido `/ventas/pos_configuracion`.
- Resultado `ok=true`.
- Validaciones:
  - pagina carga con permisos nuevos;
  - muestra botones Guardar;
  - 2 cajas, 2 terminales, 2 asignaciones;
  - 6 botones editar y 6 botones desactivar;
  - editar caja llena formulario;
  - editar terminal llena formulario;
  - editar asignacion llena formulario.
- Evidencia:
  - `public/storage/uat/pos_configuracion_crud_uat.png`.
- Contrato:
  - no se presiono Guardar;
  - no se presiono Desactivar;
  - no se abrio turno;
  - no se movio caja ni inventario.
- Auditoria posterior:
  - permisos `ventas.pos_config.*` vigentes;
  - configuracion base sin cambios operativos.
- Siguiente paso:

```text
AUTORIZO EJECUTAR CRUD REAL CONFIGURACION POS UAT usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_CRUD id_usuario=1 para UAT POS
```

## Escritura autorizada 2026-07-04 - CRUD real Configuracion POS

- Se ejecuto CRUD real controlado:
  - crear caja UAT `CJ-UAT-20260704-01`, `id_caja=3`;
  - crear terminal UAT `TERM-UAT-20260704-01`, `id_terminal_pos=3`;
  - crear asignacion UAT `id_usuario_caja=3` para usuario `1`, almacen `5`, caja `3`, terminal `3`;
  - desactivar asignacion `3`;
  - desactivar terminal `3`;
  - desactivar caja `3`.
- Auditoria read-only posterior:
  - caja `3`: `inactiva`;
  - terminal `3`: `inactiva`;
  - asignacion `3`: `inactivo`;
  - turnos abiertos `0`;
  - configuracion base de usuario `1` sigue en almacen `5`, caja `2`, terminal `2`;
  - hallazgos `[]`.
- UX ajustada:
  - registros inactivos aparecen como `Historico`;
  - registros inactivos no muestran boton Desactivar.
- UAT visual posterior:
  - `ok=true`;
  - cajas `2`;
  - terminales `3`;
  - asignaciones `3`;
  - botones editar `8`;
  - botones desactivar `6`.
- Contrato:
  - no abrio turno;
  - no movio caja;
  - no creo venta;
  - no movio inventario.
- Siguiente recomendado:
  - definir si Configuracion POS debe tener filtro `Activos/Historial/Todos`;
  - continuar con separacion fina de caja/turnos o Pedidos/Apartados segun prioridad.

## Avance 2026-07-04 - filtros historicos Configuracion POS

- Se agregaron filtros por tabla:
  - `Activos`;
  - `Historial`;
  - `Todos`.
- KPIs:
  - muestran activos;
  - agregan `+N hist.` cuando hay registros historicos.
- UX:
  - registros inactivos siguen visibles bajo filtro historial/todos;
  - registros historicos no muestran boton Desactivar;
  - editar sigue disponible para inspeccion/correccion administrativa.
- UAT visual:
  - `ok=true`;
  - activos: 2 cajas, 2 terminales, 2 asignaciones;
  - todos: 3 terminales, 3 asignaciones;
  - historial: 1 terminal, 1 asignacion;
  - no se presiono Guardar ni Desactivar.
- Contrato:
  - sin escritura BD;
  - sin abrir turno;
  - sin mover caja ni inventario.

## Avance 2026-07-04 - Caja/Turnos apertura dry-run

- Se agrego bloque `Apertura de turno` en `/ventas/caja_turnos`.
- La UI valida apertura con `/ventas/turno_apertura_dryrun_erp`.
- La pantalla muestra autorizacion sugerida para abrir turno real, pero no ejecuta escrituras.
- UAT visual:
  - `ok=true`;
  - opciones caja apertura `2`;
  - KPI turnos abiertos `0`;
  - autorizacion visible.
- Auditoria read-only:
  - usuario `1` puede abrir turno;
  - asignacion activa: almacen `5`, caja `2`, terminal `2`;
  - sin turno abierto actual;
  - bloqueos `[]`.
- Autorizacion sugerida:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS desde Caja/Turnos"
```

- Contrato:
  - no abrio turno;
  - no cerro turno;
  - no movio caja;
  - no movio inventario.

## Escritura autorizada 2026-07-04 - apertura turno Caja/Turnos

- Autorizacion ejecutada:
  - `AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS desde Caja/Turnos"`.
- Resultado:
  - turno abierto `id_turno_caja=13`;
  - folio `TUR-20260704-002-001`;
  - movimiento caja inicial `id_movimiento_caja=27`;
  - usuario `1`;
  - almacen `5`;
  - caja `2`;
  - monto inicial `500`.
- Incidencia durante ejecucion:
  - primer intento bloqueado porque MySQL estaba apagado;
  - se levanto `mysqld`;
  - auditoria posterior mostro asignacion activa y se ejecuto correctamente.
- Correcciones posteriores:
  - apertura dry-run ahora valida doble turno abierto;
  - Caja/Turnos consulta `terminal_asignacion_actual_erp` y amarra apertura a caja asignada;
  - selectores de apertura quedan deshabilitados visualmente.
- Estado actual:
  - turno abierto `true`;
  - preflight apertura bloquea doble apertura;
  - KPI Caja/Turnos muestra `1` turno abierto.
- Siguiente recomendado:
  - ejecutar venta UAT con turno `TUR-20260704-002-001`, o
  - cerrar turno con monto contado cuando se decida cerrar el ciclo.

## Bloqueo 2026-07-04 - venta POS por falta de stock

- Autorizacion de venta recibida para SKU `1760`, cantidad `1`, precio/pago `295`.
- Preflight venta:
  - turno abierto `13` / `TUR-20260704-002-001`;
  - almacen `5`;
  - caja `2`;
  - pago completo;
  - bloqueo `Existencia insuficiente`.
- Venta real no ejecutada.
- Stock UAT preflight:
  - SKU `TP-40352-500GR`;
  - cantidad sugerida `1`;
  - referencia `INV-INICIAL-POS-UAT-20260704-A5-S1760-CAJA-TURNOS`;
  - bloqueos `[]`.
- Siguiente autorizacion:

```text
AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-UAT-20260704-A5-S1760-CAJA-TURNOS
```

## Escritura autorizada 2026-07-04 - stock y venta real Caja/Turnos

- Stock UAT aplicado:
  - almacen `5`;
  - SKU `1760`;
  - cantidad `1`;
  - referencia `INV-INICIAL-POS-UAT-20260704-A5-S1760-CAJA-TURNOS`;
  - inventario inicial aplicado;
  - movimientos kardex `1`.
- Preflight venta posterior:
  - `puede_vender_real=true`;
  - turno `13` / `TUR-20260704-002-001`;
  - almacen `5`;
  - caja `2`;
  - total `295`;
  - pago `295`;
  - saldo `0`;
  - existencia asignada `34`, antes `1`, despues `0`;
  - bloqueos `[]`.
- Venta real ejecutada con autorizacion ya recibida:
  - folio `POS-20260704-000001`;
  - `id_venta=13`;
  - estatus `pagada`;
  - cliente `Cliente UAT POS Caja Turnos`;
  - total `295`;
  - pago `295`.
- Trazabilidad generada:
  - inventario `id_movimiento_inventario=75`;
  - existencia `34`;
  - movimiento caja venta `id_movimiento_caja=28`;
  - pago `id_venta_pago=14`;
  - garantia snapshot `id_venta_detalle_garantia=10`, resumen `Sin garantia`.
- Ticket formal read-only:
  - generado correctamente;
  - 28 lineas;
  - sin hallazgos;
  - incluye tienda, caja, turno, cliente, pago, garantia e inventario.
- Estado del turno:
  - sigue abierto;
  - monto inicial `500`;
  - venta `295`;
  - monto esperado `795`.
- Preflight cierre:
  - monto contado `795`;
  - diferencia `0`;
  - bloqueos `[]`.
- Siguiente autorizacion recomendada:

```text
AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS Caja Turnos con venta POS-20260704-000001"
```

## Escritura autorizada 2026-07-04 - cierre turno Caja/Turnos

- Autorizacion ejecutada:
  - `AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS Caja Turnos con venta POS-20260704-000001"`.
- Resultado:
  - turno `id_turno_caja=13`;
  - folio `TUR-20260704-002-001`;
  - estatus `cerrado`;
  - monto esperado `795`;
  - monto contado `795`;
  - diferencia `0`;
  - fecha cierre registrada.
- Auditoria posterior:
  - ventas `1`;
  - total ventas `295`;
  - pagos `295`;
  - movimientos caja `795`;
  - movimientos caja count `2`;
  - hallazgos `[]`.
- Configuracion POS posterior:
  - usuario `1` sigue asignado a almacen `5`, caja `2`, terminal `2`;
  - `turno_abierto=false`;
  - `turnos_abiertos=0`.
- Ticket formal posterior:
  - `POS-20260704-000001`;
  - sin hallazgos;
  - mantiene trazabilidad de turno/caja/pago/inventario/garantia.
- Estado actual:
  - ciclo UAT Caja/Turnos con venta real cerrado correctamente;
  - no hay turno abierto.
- Siguiente recomendado:
  - continuar con pantalla productiva de cierre/arqueo en UI, o
  - avanzar Pedidos/Apartados POS si se prioriza atencion multi-cliente.

## Avance 2026-07-04 - arqueo visual Caja/Turnos

- Se preparo `/ventas/caja_turnos` para un cierre mas operativo sin escritura directa:
  - efectivo por denominaciones;
  - tarjeta;
  - transferencia;
  - vales/saldo a favor;
  - total contado calculado automaticamente;
  - campo monto contado readonly;
  - simulacion de corte usa el total calculado.
- El resultado del dry-run ahora muestra desglose del arqueo usado para el cierre.
- Si hay diferencia, la UI mantiene el criterio definido:
  - el cierre puede permitirse;
  - la diferencia queda para reportes/seguimiento;
  - el cierre real requiere autorizacion escrita.
- Validaciones ejecutadas:
  - PHP lint de `caja_turnos.php`: sin errores;
  - JS check de `caja_turnos.js`: sin errores;
  - JS check de UAT Playwright: sin errores.
- Auditoria posterior:
  - MySQL local se levanto para lectura;
  - tablas POS presentes;
  - turnos abiertos `0`;
  - turno `TUR-20260704-002-001` sigue cerrado con esperado `795`, contado `795`, diferencia `0`;
  - configuracion POS read-only: schema completo, asignacion activa usuario `1` a almacen `5`, caja `2`, terminal `2`.
- Incidencia pendiente:
  - Playwright headless local sigue agotando timeout al ejecutar la prueba visual completa;
  - el script se ajusto para timeouts cortos y cierre en `finally`;
  - falta volver a correr UAT visual cuando el runtime headless responda estable.
- Contrato:
  - sin apertura real de turno;
  - sin cierre real de turno;
  - sin movimientos de caja;
  - sin movimientos de inventario.
- Siguiente recomendado:
  - exponer cierre real desde UI con permiso y confirmacion fuerte, o
  - avanzar flujo real Pedidos/Apartados POS.

## Avance 2026-07-04 - cierre real Caja/Turnos POS UI

- Se expuso cierre real controlado en `/ventas/caja_turnos`.
- Backend:
  - `VentasErp::cerrarTurnoRealPos`;
  - `/ventas/turno_cierre_real_erp`;
  - auditoria explicita `pos_turno_cerrar`.
- Seguridad/reglas:
  - requiere `ventas.operar`;
  - usa usuario de sesion;
  - requiere caja asignada y turno abierto;
  - requiere confirmacion exacta `CERRAR TURNO`;
  - bloquea turno que no coincida con asignacion actual;
  - bloqueo transaccional `FOR UPDATE`;
  - reusa `cierreTurnoDryRun`;
  - solo actualiza `erp_pos_turnos`.
- UI:
  - despues de simulacion valida muestra panel `Cierre real de turno`;
  - captura observaciones;
  - solicita confirmacion;
  - envia monto contado calculado por arqueo;
  - recarga estado al terminar.
- Validacion segura:
  - script `uat_ventas_pos_cierre_real_ui_guard_readonly.php` bloquea con confirmacion incorrecta;
  - resultado `ok=true`;
  - no hay turno abierto;
  - turno `TUR-20260704-002-001` sigue cerrado con diferencia `0`.
- Contrato:
  - no se ejecuto cierre real adicional;
  - no se movio caja;
  - no se movio inventario;
  - no se creo venta ni pago.
- Siguiente recomendado:
  - UAT real desde UI con turno nuevo;
  - despues, corte imprimible y reporte ejecutivo de caja.

## Escritura autorizada 2026-07-04 - cierre turno 14 tras bloqueo UI

- Contexto:
  - se autorizo cierre real UI automatizado;
  - Playwright no pudo llegar a UI por `ERR_CONNECTION_REFUSED` y luego `ERR_TOO_MANY_REDIRECTS`;
  - no se cerro por UI.
- Autorizacion posterior ejecutada:
  - `AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=500 observaciones="Cierre UAT POS turno TUR-20260704-002-002 tras bloqueo UI local"`.
- Resultado:
  - turno `id_turno_caja=14`;
  - folio `TUR-20260704-002-002`;
  - estatus `cerrado`;
  - monto esperado `500`;
  - monto contado `500`;
  - diferencia `0`.
- Auditoria posterior:
  - ventas `0`;
  - pagos `0`;
  - movimientos caja `1`;
  - movimiento inicial `500`;
  - hallazgos `[]`;
  - turnos abiertos `0`.
- Estado actual:
  - no hay turno abierto;
  - cierre real UI queda implementado pero falta UAT visual exitosa en entorno web estable.
- Siguiente recomendado:
  - estabilizar acceso web local para Playwright/UI;
  - luego repetir UAT cierre real desde UI;
  - despues crear corte imprimible y reportes de caja.

## Avance 2026-07-04 - corte imprimible Caja/Turnos

- Se agrego corte formal read-only de turno POS:
  - modelo `VentasErp::corteTurnoFormalReadOnly`;
  - endpoint `/ventas/corte_turno_readonly_erp`;
  - panel `Corte imprimible` en `/ventas/caja_turnos`;
  - boton imprimir.
- El corte incluye:
  - turno/estatus;
  - tienda/caja;
  - apertura/cierre;
  - usuario apertura/cierre;
  - inicial/esperado/contado/diferencia;
  - ventas;
  - pagos por metodo;
  - movimientos de caja;
  - observaciones de cierre.
- UAT read-only:
  - turno `TUR-20260704-002-002`;
  - `ok=true`;
  - 34 lineas;
  - hallazgos `[]`;
  - diferencia `0`.
- Contrato:
  - no escribe BD;
  - no cierra turnos;
  - no ajusta caja;
  - no mueve inventario.
- Siguiente recomendado:
  - reportes ejecutivos de caja/turnos/diferencias, o
  - flujo real Pedidos/Apartados POS.

## Avance 2026-07-04 - reportes ejecutivos caja con corte

- `/ventas/reportes` ahora muestra KPIs adicionales:
  - ventas acumuladas;
  - movimientos de caja;
  - faltante promedio;
  - sobrante promedio.
- Cada turno en el reporte tiene accion para abrir corte imprimible.
- El modal de corte usa `/ventas/corte_turno_readonly_erp`.
- `VentasErp::reporteCajaPosReadOnly` agrega campos de resumen para ventas/movimientos/promedios.
- UAT read-only:
  - almacen `5`, caja `2`;
  - turnos `14`;
  - turnos con diferencia `1`;
  - faltantes total `10`;
  - ventas total `3805`;
  - movimientos caja `29`;
  - corte `TUR-20260704-002-002` con `34` lineas y hallazgos `[]`.
- Contrato:
  - no escribe BD;
  - no cierra turnos;
  - no resuelve diferencias;
  - no mueve inventario.
- Siguiente recomendado:
  - flujo real Pedidos/Apartados POS;
  - o estabilizar acceso web para UAT visual de cierre/corte.

## Avance 2026-07-05 - Pedidos/Apartados POS read-only UX

- Se retomo el modulo `/ventas/pedidos` como flujo separado del POS inmediato y del legacy ecommerce.
- Estado actual:
  - listado de pedidos/apartados ERP en consulta;
  - simulacion de reserva/apartado sin crear folio;
  - simulacion de abono sin registrar caja;
  - bloqueo correcto cuando no hay turno abierto o existencia suficiente.
- Ajustes UI:
  - se agrego resumen de contexto POS:
    - tienda/almacen;
    - caja asignada;
    - turno;
    - modo operativo.
  - se agrego resumen rapido de total, anticipo y saldo antes de simular apartado.
  - el mensaje superior ahora comunica que el modulo consulta/simula sin escribir caja ni inventario.
- UAT read-only:
  - `uat_ventas_pos_pedidos_apartados_readonly.php --compact=1`;
  - `ok=true`;
  - asignacion activa usuario `1`;
  - almacen `5`;
  - caja `2`;
  - turno abierto `false`;
  - pedidos `0`;
  - apartados `0`;
  - reserva bloqueada por:
    - `Selecciona turno abierto de caja`;
    - `Existencia insuficiente`.
  - abono bloqueado por:
    - `Selecciona turno abierto de caja`;
    - `Pedido/apartado no encontrado`.
- Contrato:
  - no escribe BD;
  - no crea pedido;
  - no registra abono;
  - no mueve caja;
  - no reserva inventario;
  - no mueve kardex.
- Siguiente recomendado:
  - abrir turno/cargar stock solo si se quiere probar dry-run positivo;
  - preparar autorizacion fuerte para flujo real de pedidos/apartados con reservas y abonos.

## Avance 2026-07-05 - Preflight flujo real Pedidos/Apartados POS

- Se agrego auditoria read-only:
  - `storage/uat/uat_ventas_pos_pedidos_real_preflight_readonly.php`.
- Objetivo:
  - verificar tablas/columnas requeridas;
  - verificar politica activa de apartado;
  - verificar contexto POS usuario/turno;
  - verificar stock UAT;
  - verificar si existen metodos reales transaccionales.
- Resultado compacto:
  - `ok=false`;
  - asignacion activa `true`;
  - turno abierto `false`;
  - politica activa de apartado `1`;
  - stock suficiente SKU `1760` almacen `5`: `false`.
- Bloqueos de implementacion real:
  - falta `VentasErp::pedidoGuardarReal`;
  - falta `VentasErp::apartadoAbonoReal`;
  - falta `VentasErp::pedidoEntregarReal`;
  - falta `VentasErp::pedidoCancelarReal`;
  - falta `InventarioErp::consumirReserva`.
- Hallazgos operativos:
  - sin turno abierto no se pueden registrar anticipos/abonos reales;
  - sin stock suficiente no se puede crear reserva UAT positiva.
- Decision tecnica:
  - mantener `/ventas/pedidos` en consulta/simulacion;
  - no exponer botones reales todavia;
  - siguiente fase debe implementar metodos transaccionales con endpoints y scripts UAT autorizados.
- Autorizacion fuerte sugerida:
  - `AUTORIZO PREPARAR FLUJO REAL PEDIDOS APARTADOS POS usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDOS_APARTADOS_REAL para UAT POS`.

## Avance 2026-07-05 - Flujo real Pedidos/Apartados POS preparado

- Autorizacion recibida:
  - `AUTORIZO PREPARAR FLUJO REAL PEDIDOS APARTADOS POS usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDOS_APARTADOS_REAL para UAT POS`.
- Backend implementado:
  - `VentasErp::pedidoGuardarReal`;
  - `VentasErp::apartadoAbonoReal`;
  - `VentasErp::pedidoEntregarReal`;
  - `VentasErp::pedidoCancelarReal`;
  - `InventarioErp::consumirReserva`.
- Endpoints agregados:
  - `/ventas/pedido_guardar_erp`;
  - `/ventas/apartado_abono_erp`;
  - `/ventas/pedido_entregar_erp`;
  - `/ventas/pedido_cancelar_erp`.
- Scripts UAT bloqueados por token:
  - `storage/uat/uat_ventas_pos_pedido_apartado_apply_authorized.php`;
  - `storage/uat/uat_ventas_pos_apartado_abono_apply_authorized.php`;
  - `storage/uat/uat_ventas_pos_pedido_entrega_apply_authorized.php`;
  - `storage/uat/uat_ventas_pos_pedido_cancelar_apply_authorized.php`.
- Reglas implementadas:
  - crear pedido/apartado usa prevalidacion backend;
  - requiere asignacion POS y turno abierto;
  - crea encabezado `erp_ventas`;
  - crea detalle `erp_ventas_detalle`;
  - crea reservas `erp_inventario_reservas`;
  - sube `cantidad_apartada` y baja `cantidad_disponible`;
  - registra anticipo en `erp_ventas_pagos` y `erp_pos_movimientos_caja`;
  - actualiza `monto_esperado` del turno;
  - registra eventos en `erp_ventas_eventos`;
  - abono/liquidacion no mueve inventario;
  - entrega consume reservas, baja existencia total, genera kardex y trazabilidad;
  - cancelacion libera reservas y deja pagos para decision financiera posterior.
- Validaciones:
  - PHP lint `VentasErp.php`: sin errores;
  - PHP lint `InventarioErp.php`: sin errores;
  - PHP lint `Ventas.php`: sin errores;
  - scripts UAT: sin errores;
  - scripts UAT sin token: bloqueados, no escriben BD;
  - preflight read-only: `ok=true`.
- Hallazgos operativos actuales:
  - no hay turno abierto;
  - no hay stock suficiente SKU `1760` en almacen `5`.
- Siguiente para UAT real:
  - abrir turno POS;
  - cargar stock UAT;
  - autorizar creacion de apartado real;
  - despues autorizar abono/liquidacion;
  - despues autorizar entrega o cancelacion segun prueba elegida.

## Escritura autorizada 2026-07-05 - turno y stock UAT Pedidos/Apartados

- Autorizacion recibida:
  - abrir turno POS UAT con usuario `1`, monto inicial `500`;
  - cargar stock UAT SKU `1760`, almacen `5`, cantidad `1`, referencia `INV-INICIAL-POS-UAT-PEDIDOS-01`.
- Resultado turno:
  - `id_turno_caja=15`;
  - folio `TUR-20260705-002-001`;
  - caja `2`;
  - almacen `5`;
  - movimiento caja inicial `30`;
  - estatus `abierto`.
- Resultado stock:
  - stock cargado correctamente;
  - movimientos inventario `1`;
  - referencia `INV-INICIAL-POS-UAT-PEDIDOS-01`.
- Preflight posterior:
  - `ok=true`;
  - asignacion activa `true`;
  - turno abierto `true`;
  - stock suficiente `true`;
  - hallazgos `[]`.
- Dry-run apartado:
  - anticipo minimo `59`;
  - anticipo simulado `100`;
  - saldo estimado `195`;
  - bloqueos `[]`.
- Siguiente autorizacion:
  - crear apartado real con token `VENTAS_POS_PEDIDO_REAL`.

## Escritura autorizada 2026-07-05 - apartado POS real

- Autorizacion ejecutada:
  - crear apartado POS UAT real con token `VENTAS_POS_PEDIDO_REAL`;
  - usuario `1`;
  - SKU `1760`;
  - cantidad `1`;
  - precio `295`;
  - anticipo `100`;
  - telefono `3312345678`;
  - cliente `Cliente UAT Apartado POS`.
- Resultado:
  - folio `APT-20260705-000001`;
  - `id_venta=14`;
  - estatus `pendiente_pago`;
  - total `295`;
  - pagado `100`;
  - saldo `195`.
- Reserva:
  - `id_reserva_inventario=2`;
  - folio `RES-PED-20260705-000001`;
  - existencia `34`;
  - cantidad `1`;
  - estatus `activa`.
- Caja:
  - anticipo `100`;
  - `id_venta_pago=15`;
  - `id_movimiento_caja=31`;
  - turno `15`;
  - monto esperado turno `600`.
- Inventario:
  - existencia `34`;
  - cantidad `1`;
  - apartada `1`;
  - disponible `0`.
- Preflight posterior:
  - abono por `195` para `APT-20260705-000001` sin bloqueos;
  - nueva reserva de otro apartado bloqueada por existencia insuficiente, esperado.
- Siguiente recomendado:
  - autorizar abono/liquidacion de `195`;
  - despues autorizar entrega para consumir reserva y generar kardex, o cancelar para liberar reserva.

## Escritura autorizada 2026-07-05 - liquidacion apartado POS real

- Autorizacion ejecutada:
  - registrar abono/liquidacion por `195`;
  - folio `APT-20260705-000001`;
  - referencia `UAT-LIQ-APT-20260705-000001`.
- Resultado:
  - `id_venta=14`;
  - estatus `pagado`;
  - pagado total `295`;
  - saldo `0`.
- Pagos:
  - anticipo `100`, `id_venta_pago=15`, movimiento caja `31`;
  - liquidacion `195`, `id_venta_pago=16`, movimiento caja `32`.
- Caja:
  - turno `15`;
  - monto esperado `795`;
  - estatus `abierto`.
- Inventario:
  - reserva `RES-PED-20260705-000001` sigue `activa`;
  - existencia `34`: cantidad `1`, apartada `1`, disponible `0`;
  - no hay salida/kardex de entrega todavia.
- Siguiente recomendado:
  - autorizar entrega real para consumir reserva y generar kardex;
  - despues cerrar turno con monto contado `795`.

## Escritura autorizada 2026-07-05 - entrega apartado POS real

- Autorizacion ejecutada:
  - entregar apartado `APT-20260705-000001`;
  - token `VENTAS_POS_PEDIDO_ENTREGA_REAL`.
- Resultado:
  - venta `14` estatus `entregado`;
  - detalle `14` estatus `entregada`.
- Reserva:
  - `RES-PED-20260705-000001`;
  - `id_reserva_inventario=2`;
  - estatus `consumida`;
  - consumida `1`.
- Inventario:
  - existencia `34`;
  - cantidad `0`;
  - apartada `0`;
  - disponible `0`;
  - estatus `agotada`.
- Kardex/trazabilidad:
  - movimiento inventario `77`;
  - tipo `salida`;
  - origen `pedido_pos_entrega`;
  - referencia `APT-20260705-000001`;
  - traza `erp_ventas_detalle_inventario=14`.
- Caja:
  - turno `15`;
  - monto esperado `795`;
  - sin movimientos nuevos por entrega.
- Siguiente recomendado:
  - cerrar turno `15` con monto contado `795`;
  - validar corte y reportes.

## Escritura autorizada 2026-07-05 - cierre turno ciclo apartado POS

- Autorizacion ejecutada:
  - cerrar turno POS UAT real;
  - usuario `1`;
  - monto contado `795`;
  - observaciones `Cierre UAT POS apartado entregado APT-20260705-000001`.
- Resultado:
  - turno `15`;
  - folio `TUR-20260705-002-001`;
  - estatus `cerrado`;
  - esperado `795`;
  - contado `795`;
  - diferencia `0`.
- Resumen operativo:
  - venta/apartado `APT-20260705-000001`;
  - venta `14` entregada;
  - pagos `2`, total `295`;
  - movimientos caja `3`, total `795`;
  - reserva `2` consumida;
  - kardex salida `77`;
  - existencia `34` agotada.
- Configuracion posterior:
  - turnos abiertos `0`;
  - asignacion usuario `1` sigue activa para almacen `5`, caja `2`, terminal `2`.
- Corte:
  - `TUR-20260705-002-001`;
  - lineas `37`;
  - hallazgos `[]`.
- Estado del ciclo:
  - flujo completo pedido/apartado validado: apertura, stock, apartado, reserva, anticipo, liquidacion, entrega, kardex, cierre.

## Avance 2026-07-05 - UI operativa Pedidos/Apartados real

- Se conecto `/ventas/pedidos` con acciones reales controladas.
- Cambios UI:
  - la pantalla conserva simulacion/prevalidacion antes de guardar;
  - si la reserva simulada no tiene bloqueos, muestra accion para crear pedido/apartado real;
  - si el abono simulado no tiene bloqueos, muestra accion para registrar abono real;
  - en listado, acciones por estado:
    - abonar cuando hay saldo y el estatus lo permite;
    - entregar cuando esta pagado;
    - cancelar cuando no esta entregado/cancelado.
- Guardas UI:
  - crear requiere escribir `CREAR`;
  - abonar requiere escribir `ABONAR`;
  - entregar requiere escribir `ENTREGAR`;
  - cancelar requiere motivo y escribir `CANCELAR`.
- Endpoints usados:
  - `/ventas/pedido_guardar_erp`;
  - `/ventas/apartado_abono_erp`;
  - `/ventas/pedido_entregar_erp`;
  - `/ventas/pedido_cancelar_erp`.
- Validaciones:
  - `php -l app/vistas/paginas/apps/erp/ventas/pedidos.php`: sin errores;
  - `node --check public/assets/js/custom/apps/erp/ventas/pedidos.js`: sin errores;
  - UAT read-only posterior: `ok=true`, sin turno abierto, apartado entregado bloquea nuevos abonos como esperado.
- Contrato:
  - no se ejecuto escritura nueva al integrar UI;
  - cualquier accion real desde navegador pasa por backend, CSRF, sesion, permiso y confirmacion.

## Avance 2026-07-05 - UX Pedidos/Apartados para operacion de tienda

- Se retiro la precarga de datos UAT en la vista `/ventas/pedidos`:
  - cliente;
  - telefono/identificador;
  - SKU;
  - precio;
  - referencia.
- Se agrego buscador de producto en el alta de pedido/apartado:
  - usa `/ventas/pos_buscar_skus_erp`;
  - respeta almacen seleccionado;
  - muestra imagen, SKU, nombre, precio, existencia disponible y badges de granel/unidad cerrada/sin stock;
  - al seleccionar producto llena `id_sku` y precio para simular reserva.
- Se actualizaron textos operativos:
  - modo con turno: `Operativo con turno`;
  - sin turno: `Requiere abrir turno`;
  - alerta: prevalidar primero y despues ejecutar accion real confirmada por backend.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pedidos.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
  - `node --check public\assets\js\custom\apps\erp\ventas\pedidos.js`: sin errores.
- No hubo escritura de base de datos.

### UAT manual sugerida Pedidos/Apartados UI

1. Abrir turno POS y cargar stock UAT con autorizacion vigente.
2. Entrar a `/ventas/pedidos`.
3. Confirmar que el contexto muestre almacen/caja y `Operativo con turno`.
4. Capturar cliente e identificador.
5. Buscar producto por SKU, codigo o nombre; seleccionar una tarjeta de resultado.
6. Confirmar que se llenen `SKU ID`, precio, total, anticipo y saldo.
7. Presionar `Simular reserva`.
8. Si no hay bloqueos, presionar `Crear apartado/pedido real` y confirmar con `CREAR`.
9. En listado, usar accion de abono si queda saldo; simular abono y confirmar con `ABONAR`.
10. Cuando quede pagado, usar accion de entrega y confirmar con `ENTREGAR`.
11. Validar en detalle/corte que caja, reserva, kardex y estatus queden consistentes.

## Avance 2026-07-05 - Pedidos/Apartados multipartida en UI

- Se cambio el alta de pedido/apartado para operar con varias partidas.
- Comportamiento:
  - buscar producto;
  - seleccionar SKU;
  - capturar cantidad/precio;
  - presionar `Agregar partida`;
  - repetir con otros productos;
  - simular reserva con todas las partidas.
- La tabla permite:
  - editar cantidad por renglon;
  - editar precio por renglon;
  - quitar partida;
  - vaciar partidas.
- El total, anticipo y saldo se recalculan con todas las partidas.
- El payload `items` ahora se construye desde la tabla de partidas.
- Guardrail UX:
  - si no se agrego ninguna partida, se conserva fallback con el SKU/cantidad/precio capturado en los campos visibles para no romper pruebas manuales.
- Alcance:
  - no hubo escritura de BD;
  - no se modifico backend;
  - aprovecha que `VentasErp::pedidoGuardarReal` y `pedidoReservaDryRun` ya aceptan arreglo `items`.
- Validaciones:
  - `node --check public\assets\js\custom\apps\erp\ventas\pedidos.js`: sin errores;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pedidos.php`: sin errores.

## Avance 2026-07-06 - UAT multipartida preparada y guardrail conexion

- Se amplio `storage/uat/uat_ventas_pos_pedido_apartado_apply_authorized.php`:
  - acepta `--items_json=[...]`;
  - acepta `--item=id_sku,cantidad,precio` repetible;
  - conserva compatibilidad con `--id_sku --cantidad --precio`;
  - sigue bloqueado sin token `VENTAS_POS_PEDIDO_REAL` y respaldo vigente.
- Se amplio `storage/uat/uat_ventas_pos_pedidos_apartados_readonly.php`:
  - acepta las mismas entradas multipartida;
  - reporta `partidas_enviadas` y `reservas_propuestas` en modo compacto.
- Se agrego guardrail en `VentasErp`:
  - `prevalidarCarritoPos` devuelve respuesta controlada si no hay conexion PDO;
  - `pedidoReservaDryRun` devuelve bloqueo `conexion_bd_no_disponible` en vez de fatal.
- Evidencia:
  - el script autorizado sin parametros sigue bloqueado y no escribe BD;
  - el read-only con dos partidas devolvio JSON controlado;
  - en esa ejecucion la BD no estaba disponible, por eso no hubo reservas propuestas.
- Reintento con MySQL levantado:
  - PHP conecto correctamente;
  - asignacion POS activa para usuario `1`;
  - almacen `5`;
  - caja `2`;
  - turno abierto `false`;
  - apartados en muestra `1`;
  - `partidas_enviadas=2`;
  - `anticipo_minimo=88.5`;
  - `pagado_total=100`;
  - `saldo_estimado=342.5`;
  - bloqueos esperados: sin turno abierto, existencia insuficiente e incremento minimo invalido en una partida fraccionaria.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedido_apartado_apply_authorized.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedidos_apartados_readonly.php`: sin errores.
- No hubo escritura de base de datos.

## Avance 2026-07-06 - Guardrail dry-run contra sobre-reserva multipartida

- Hallazgo:
  - el flujo real bloquea cada existencia con `FOR UPDATE` al crear reservas;
  - pero el dry-run podia validar partidas duplicadas del mismo SKU/existencia de forma optimista, porque cada renglon se comparaba contra disponibilidad actual y no contra el acumulado del carrito.
- Cambio:
  - se agrego `VentasErp::validarPlanSalidaAcumuladoPos`;
  - `prevalidarCarritoPos` ahora suma asignaciones por `id_existencia_inventario` y `id_inventario_unidad`;
  - bloquea si la reserva acumulada supera disponibilidad propuesta.
- Alcance:
  - solo memoria/read-only;
  - no bloquea filas;
  - no mueve inventario;
  - alinea mejor la simulacion con el comportamiento transaccional real.
- Validacion:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - read-only multipartida siguio devolviendo JSON controlado con bloqueos esperados por falta de turno/stock/anticipo.
- Ajuste de escenario UAT:
  - read-only con anticipo `120` para total `590` elimino bloqueo por anticipo minimo;
  - anticipo minimo calculado: `118`;
  - saldo estimado: `470`;
  - bloqueos restantes: turno abierto pendiente y existencia insuficiente.

## Avance 2026-07-06 - Verificador post-UAT apartado multipartida

- Se agrego `storage/uat/uat_ventas_pos_pedido_multipartida_post_readonly.php`.
- Proposito:
  - verificar por folio un apartado/pedido multipartida despues de UAT real;
  - consultar venta, detalles, reservas, pagos, movimientos de caja asociados y trazas/kardex;
  - generar hallazgos de consistencia sin escribir BD.
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedido_multipartida_post_readonly.php`: sin errores;
  - ejecucion sin `--folio`: bloqueada, no escribe;
  - ejecucion con folio inexistente: `no_encontrado`, no escribe.
- Uso posterior sugerido:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pedido_multipartida_post_readonly.php --folio=FOLIO_APT --compact=1`.

## Avance 2026-07-06 - Runbook read-only ciclo apartado multipartida

- Se agrego `storage/uat/uat_ventas_pos_pedido_multipartida_runbook_readonly.php`.
- Proposito:
  - calcular datos esperados de la UAT multipartida;
  - emitir frases de autorizacion sugeridas;
  - listar comandos de ejecucion/verificacion sin escribir BD.
- Valores default:
  - usuario `1`;
  - almacen `5`;
  - SKU `1760`;
  - 2 partidas de cantidad `1`;
  - precio `295`;
  - total `590`;
  - anticipo `120`;
  - saldo `470`;
  - monto inicial `500`;
  - monto contado final sugerido `1090`.
- Secuencia cubierta:
  - abrir turno y cargar stock;
  - crear apartado multipartida;
  - verificar post-creacion;
  - abonar saldo;
  - entregar;
  - verificar post-entrega;
  - cerrar turno.
- Validacion:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedido_multipartida_runbook_readonly.php`: sin errores;
  - ejecucion `--compact=1`: JSON read-only correcto.

## Avance 2026-07-06 - Readiness actual UAT apartado multipartida

- Se agrego `storage/uat/uat_ventas_pos_pedido_multipartida_readiness_readonly.php`.
- Proposito:
  - validar estado actual antes de pedir autorizacion real;
  - revisar asignacion POS, turno abierto, stock suficiente, politica de apartado, metodo de pago y dry-run.
- Resultado actual:
  - asignacion activa: `true`;
  - caja: `2`;
  - turno abierto: `false`;
  - stock disponible SKU `1760` almacen `5`: `0`;
  - stock necesario: `2`;
  - total esperado: `590`;
  - anticipo: `120`;
  - saldo: `470`;
  - bloqueos reales: `Sin turno abierto`, `Stock insuficiente para multipartida`.
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedido_multipartida_readiness_readonly.php`: sin errores;
  - ejecucion `--compact=1`: JSON read-only correcto.

## Avance 2026-07-06 - Verificador post-UAT parametrizable

- Se ajusto `storage/uat/uat_ventas_pos_pedido_multipartida_post_readonly.php`.
- Nuevos parametros:
  - `--esperar_partidas=N`;
  - `--esperar_total=MONTO`;
  - `--esperar_pagado=MONTO`;
  - `--esperar_saldo=MONTO`.
- Se actualizo el runbook para incluir expectativas en comandos post-creacion, post-abono y post-entrega.
- Validacion:
  - folio previo `APT-20260705-000001` con `--esperar_partidas=1 --esperar_total=295 --esperar_pagado=295 --esperar_saldo=0` devolvio `hallazgos=[]`.
- Impacto:
  - permite usar el mismo verificador para UAT vieja, nueva multipartida y futuros pedidos con otros totales.

## Avance 2026-07-06 - UX pedidos/apartados con partidas explicitas

- Se ajusto `/ventas/pedidos` para que la captura de producto sea una zona de preparacion y la tabla sea la fuente visible del pedido.
- Regla UI:
  - solo las partidas agregadas a la tabla se envian al backend en `items`;
  - si el operador captura SKU/cantidad/precio pero no presiona `Agregar partida`, la simulacion y la accion real muestran bloqueo local;
  - el total, anticipo y saldo se calculan desde la tabla, no desde campos temporales.
- Archivos:
  - `app/vistas/paginas/apps/erp/ventas/pedidos.php`;
  - `public/assets/js/custom/apps/erp/ventas/pedidos.js`.
- Impacto:
  - evita pedidos invisibles o ambiguos;
  - mejora UAT para apartados multipartida;
  - mantiene backend y BD sin cambios.

## Avance 2026-07-06 - Runbook read-only cancelacion de apartado

- Se agrego `storage/uat/uat_ventas_pos_pedido_cancelacion_runbook_readonly.php`.
- Proposito:
  - preparar UAT de cancelacion de apartado antes de entrega;
  - emitir autorizaciones sugeridas sin escribir BD;
  - documentar expectativas: libera reserva, no genera kardex de salida y no reembolsa caja en este flujo.
- Valores default:
  - usuario `1`;
  - almacen `5`;
  - SKU `1760`;
  - cantidad `1`;
  - precio `295`;
  - anticipo `100`;
  - saldo previo a cancelacion `195`;
  - monto inicial `500`;
  - monto contado final sugerido `600`.
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedido_cancelacion_runbook_readonly.php`: sin errores;
  - ejecucion `--compact=1`: JSON read-only correcto;
  - aplicador real de cancelacion sin token: bloqueado correctamente.

## Avance 2026-07-06 - Auditor read-only decision financiera de apartado cancelado

- Se agrego `storage/uat/uat_ventas_pos_pedido_cancelado_finanzas_readonly.php`.
- Proposito:
  - detectar anticipos/pagos en pedidos o apartados cancelados;
  - confirmar reserva liberada y sin consumo;
  - exponer opciones financieras sin mover caja ni inventario.
- Opciones reportadas:
  - `saldo_favor`;
  - `reembolso_caja`;
  - `penalizacion`;
  - `sin_reembolso`.
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedido_cancelado_finanzas_readonly.php`: sin errores;
  - `APT-20260706-000002`: `monto_pendiente_decision=100`, hallazgos `[]`;
  - `APT-20260706-000001`: marca correctamente que no corresponde porque esta `entregado`.
- Hallazgo de esquema:
  - tabla `erp_ventas_eventos` usa `id_venta_evento` y `fecha_registro`, no `id_evento_venta` ni `fecha_evento`.
- Impacto:
  - queda visible el anticipo pendiente tras cancelacion;
  - aun falta implementar decision financiera real para pedido/apartado cancelado.

## Avance 2026-07-06 - Plan y auditoria esquema decisiones financieras pedidos

- Se agrego `docs/erp_ventas_pos_pedidos_finanzas_plan.md`.
- Se agrego `storage/uat/uat_ventas_pos_pedidos_finanzas_schema_readonly.php`.
- Proposito:
  - proponer tabla formal `erp_ventas_pedidos_decisiones_financieras`;
  - separar decisiones financieras de apartados cancelados de reversas/devoluciones de venta;
  - auditar si el esquema existe sin aplicar DDL.
- Resultado read-only:
  - tabla existe: `false`;
  - columnas faltantes: `29`;
  - indices faltantes: `7`;
  - hallazgo: `Falta tabla erp_ventas_pedidos_decisiones_financieras.`
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedidos_finanzas_schema_readonly.php`: sin errores;
  - ejecucion `--compact=1`: JSON read-only correcto.
- Decision:
  - no usar movimiento suelto de caja;
  - resolver por expediente financiero ligado al folio cancelado.

## Avance 2026-07-06 - Dry-run decision financiera pedidos

- Se agrego `storage/uat/uat_ventas_pos_pedidos_finanzas_decision_dryrun.php`.
- Proposito:
  - simular decision financiera de pedido/apartado cancelado;
  - validar folio, estatus, pagos, reserva liberada, duplicados y requisitos por decision;
  - no insertar expediente ni mover caja.
- Validaciones:
  - `APT-20260706-000002`, `saldo_favor`, monto `100`: `ok=true`, sin bloqueos;
  - `APT-20260706-000002`, `reembolso_caja`: bloquea por falta de turno abierto;
  - `APT-20260706-000001`, `saldo_favor`: bloquea por no estar cancelado y por reserva consumida;
  - decision invalida: bloqueada;
  - monto mayor al pagado: bloqueado;
  - `sin_reembolso` con pago registrado: bloqueado.
- Contrato:
  - no crea decision;
  - no reembolsa;
  - no genera saldo favor;
  - no penaliza;
  - no mueve caja ni inventario.

## Avance 2026-07-06 - Decision financiera saldo a favor registrada

- Se agrego `storage/uat/uat_ventas_pos_pedidos_finanzas_decision_apply_authorized.php`.
- Autorizacion usada:
  - token `VENTAS_POS_PEDIDOS_FINANZAS_REAL`;
  - folio `APT-20260706-000002`;
  - decision `saldo_favor`;
  - monto `100`.
- Resultado:
  - `id_decision_financiera=1`;
  - folio decision `PFIN-20260706-000001`;
  - estatus `pendiente_ledger_crm`;
  - evento `pedido_decision_financiera_registrada`.
- Verificacion posterior:
  - monto pendiente de decision `0`;
  - monto con decision `100`;
  - duplicado bloqueado por dry-run;
  - sin movimiento de caja;
  - sin saldo CRM real;
  - sin movimiento de inventario.
- Siguiente integracion natural:
  - conectar `PFIN-20260706-000001` con ledger/saldos del CRM cuando ese modulo confirme contrato.

## Avance 2026-07-06 - Auditor POS -> CRM ledger saldo favor

- Se agrego `storage/uat/uat_ventas_pos_pedidos_crm_ledger_readonly.php`.
- Proposito:
  - auditar si una decision financiera POS puede convertirse en saldo real de cliente CRM;
  - impedir que saldo favor de cancelacion se mezcle con recompensas/monedero;
  - preparar contrato de cuenta corriente CRM.
- Resultado para `PFIN-20260706-000001`:
  - `ok=false`;
  - folio venta `APT-20260706-000002`;
  - monto saldo favor `100`;
  - estatus `pendiente_ledger_crm`;
  - bloqueo por falta de `id_cliente_crm`;
  - bloqueo por falta de tablas `crm_clientes_saldos_cuentas` y `crm_clientes_saldos_movimientos`.
- Decision:
  - no crear saldo anonimo;
  - no usar recompensas como saldo favor;
  - requerir contrato CRM de saldos antes de aplicar dinero de cliente.

## Avance 2026-07-06 - Preparacion DDL CRM saldos cliente

- Se agrego en CRM:
  - `ClientesCrmEsquema::planActualizarSaldosClientesCrm`;
  - endpoint read-only `/crm/esquema_plan_clientes_saldos_crm`;
  - endpoint apply protegido `/crm/esquema_actualizar_clientes_saldos_crm`.
- Se agregaron scripts:
  - `storage/uat/uat_crm_clientes_saldos_schema_readonly.php`;
  - `storage/uat/uat_crm_clientes_saldos_schema_apply_authorized.php`.
- Tablas propuestas:
  - `crm_clientes_saldos_cuentas`;
  - `crm_clientes_saldos_movimientos`.
- Validacion:
  - `ddl_total=2`;
  - `ddl_pendientes=2`;
  - apply sin token/respaldo bloqueado.
- Documento creado:
  - `docs/crm_clientes_saldos_solicitud_autorizacion.md`.
- Siguiente paso con autorizacion fuerte:
  - aplicar DDL CRM saldos;
  - despues ligar cliente CRM al apartado o repetir caso UAT con cliente CRM real;
  - finalmente convertir `PFIN-20260706-000001` o nuevo folio equivalente a ledger CRM.

## Avance 2026-07-06 - DDL CRM saldos cliente aplicado

- Autorizacion usada:
  - token `CRM_CLIENTES_SALDOS_DDL`;
  - respaldo/referencia `UAT POS vigente`.
- Resultado:
  - `crm_clientes_saldos_cuentas` existe;
  - `crm_clientes_saldos_movimientos` existe;
  - ambas tablas con `0` filas;
  - auditor read-only posterior `ddl_pendientes=0`.
- Alcance respetado:
  - no creo cuentas reales;
  - no creo movimientos;
  - no convirtio decisiones POS;
  - no movio caja ni inventario;
  - no uso recompensas.
- Auditor POS -> CRM posterior:
  - `PFIN-20260706-000001` sigue bloqueado solo por falta de `id_cliente_crm`;
  - `requiere_ddl_crm_saldos=false`.
- Siguiente paso:
  - ligar cliente CRM al apartado/decision o repetir UAT de apartado con cliente CRM real antes de crear movimiento de saldo favor.

## Avance 2026-07-06 - Dry-run liga cliente CRM y saldo favor POS

- Se agrego `storage/uat/uat_ventas_pos_pedidos_cliente_crm_link_dryrun.php`.
- Proposito:
  - validar cliente CRM candidato;
  - validar decision `PFIN-20260706-000001`;
  - simular actualizacion de venta/decision;
  - simular cuenta CRM saldos y movimiento `abono_saldo_favor`.
- Caso UAT validado:
  - folio decision `PFIN-20260706-000001`;
  - folio venta `APT-20260706-000002`;
  - cliente CRM candidato `157`;
  - monto saldo favor `100`;
  - `ok=true`.
- Propuesta dry-run:
  - actualizar `erp_ventas.id_cliente_crm=157`;
  - actualizar `erp_ventas_pedidos_decisiones_financieras.id_cliente_crm=157`;
  - crear cuenta MXN si falta;
  - crear movimiento CRM saldos por `$100`;
  - marcar decision como aplicada despues de crear movimiento;
  - registrar eventos en ventas y CRM.
- Avisos:
  - no existe cuenta MXN para cliente `157`, el apply real la crearia con saldo cero antes del movimiento;
  - cliente `157` proviene de legacy migrado, se debe confirmar que corresponde al cliente del apartado.
- Bloqueos comprobados:
  - cliente CRM inexistente;
  - folio decision inexistente.
- Se agrego apply protegido:
  - `storage/uat/uat_ventas_pos_pedidos_cliente_crm_link_apply_authorized.php`.
- Guardrail:
  - apply sin token/respaldo bloqueado;
  - no escribio BD.

## Avance 2026-07-06 - Saldo favor POS aplicado a CRM

- Autorizacion usada:
  - token `VENTAS_POS_PEDIDOS_CLIENTE_CRM_LINK_REAL`;
  - folio decision `PFIN-20260706-000001`;
  - cliente CRM `157`.
- Resultado:
  - venta `APT-20260706-000002` ligada a `id_cliente_crm=157`;
  - decision financiera `PFIN-20260706-000001` en estatus `aplicada`;
  - `id_saldo_cliente_movimiento=1`;
  - cuenta CRM saldos `1`;
  - movimiento `CRM-SAL-20260706-000001`;
  - saldo disponible MXN del cliente `157`: `100`.
- Trazabilidad:
  - evento venta `pedido_saldo_favor_crm_aplicado`;
  - evento CRM `saldo_favor_abonado`;
  - referencia externa `APT-20260706-000002`;
  - origen `PFIN-20260706-000001`.
- Se agrego lector read-only:
  - `storage/uat/uat_crm_clientes_saldos_cliente_readonly.php`.
- Validaciones:
  - auditor de decision muestra `estatus=aplicada`;
  - dry-run posterior bloquea duplicado;
  - sin caja, sin inventario, sin recompensas.
- Siguiente paso:
  - preparar consumo/redencion de saldo CRM en POS como metodo de pago controlado, primero dry-run.

## Avance 2026-07-06 - Dry-run uso saldo CRM en POS

- Se agrego `storage/uat/uat_ventas_pos_saldo_crm_uso_dryrun.php`.
- Se agrego plan `docs/erp_ventas_pos_saldo_crm_plan.md`.
- Proposito:
  - validar saldo disponible CRM antes de permitir pago POS con saldo;
  - definir que saldo CRM no mueve caja;
  - bloquear cambio desde saldo CRM;
  - separar saldo monetario de recompensas.
- Caso UAT validado:
  - cliente CRM `157`;
  - saldo disponible MXN `100`;
  - monto a usar `100`;
  - resultado `ok=true`;
  - saldo resultante simulado `0`.
- Bloqueos comprobados:
  - saldo insuficiente (`monto=150`);
  - cliente inexistente;
  - monto mayor al total de venta (`total_venta=80`, `monto=100`).
- Contrato futuro:
  - en `erp_ventas_pagos`: `metodo_pago='saldo_crm'`, `tipo_pago='saldo_cliente'`, `id_movimiento_caja=NULL`;
  - en CRM: movimiento `uso_saldo_pos`, naturaleza `cargo`;
  - el apply real debe vivir en la misma transaccion de la venta POS.

## Avance 2026-07-06 - Preparacion apply saldo CRM en POS

- Autorizacion recibida:
  - `AUTORIZO PREPARAR APPLY REAL USO SALDO CRM EN POS usando respaldo UAT POS vigente con token VENTAS_POS_SALDO_CRM_APPLY_PREP para UAT POS`.
- Se agrego script:
  - `storage/uat/uat_ventas_pos_saldo_crm_apply_prep_authorized.php`.
- Se ajusto `VentasErp::prevalidarPagosPos`:
  - acepta `metodo_pago='saldo_crm'` o `tipo_pago='saldo_cliente'` como pago virtual;
  - exige `id_metodo_pago=NULL`;
  - marca `mueve_caja=false`;
  - marca `requiere_ledger_crm=true`.
- Se agrego guardrail en `VentasErp::registrarPagosPosReal`:
  - si llega `saldo_crm` a la ruta real actual, lanza excepcion;
  - evita registrar saldo CRM como ingreso de caja.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_saldo_crm_apply_prep_authorized.php`: sin errores;
  - script sin token: bloqueado, no escribio BD;
  - script con token de preparacion: `read_only=true`.
- Resultado UAT preparacion:
  - cliente CRM `157`;
  - saldo disponible `100`;
  - monto saldo CRM `100`;
  - total venta estimado `295`;
  - caja estimada `195`;
  - bloqueo restante: no hay turno abierto para la caja asignada.
- Siguiente paso con autorizacion fuerte:
  - abrir turno y cargar stock si hace falta;
  - ejecutar venta POS real con saldo CRM usando token `VENTAS_POS_SALDO_CRM_REAL`.

## Avance 2026-07-07 - Ruta real saldo CRM preparada en codigo

- Se modifico `VentasErp::registrarPagosPosReal` para aceptar pagos mixtos:
  - pagos normales siguen moviendo caja;
  - `saldo_crm` descuenta ledger CRM y crea pago POS sin caja;
  - `monto_esperado` del turno suma solo pagos con `mueve_caja=true`.
- Se agregaron helpers:
  - `registrarPagoSaldoCrmPosReal`;
  - `registrarEventosSaldoCrmPosReal`;
  - `generarFolioSaldoCrmPosReal`.
- Reglas implementadas:
  - saldo CRM requiere cliente CRM ligado;
  - cuenta CRM MXN activa;
  - bloqueo `FOR UPDATE` de la cuenta;
  - saldo suficiente;
  - saldo CRM no genera cambio;
  - `erp_ventas_pagos.id_movimiento_caja=NULL`;
  - movimiento CRM `uso_saldo_pos`, naturaleza `cargo`;
  - eventos `pago_saldo_crm_aplicado` y `saldo_crm_usado_pos`.
- Se agrego script protegido:
  - `storage/uat/uat_ventas_pos_saldo_crm_apply_authorized.php`;
  - token requerido `VENTAS_POS_SALDO_CRM_REAL`.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_saldo_crm_apply_authorized.php`: sin errores;
  - script real sin token: bloqueado, no escribio BD.
- Bloqueo operativo actual:
  - prevalidacion posterior devolvio `Conexion BD no disponible`;
  - no hay proceso MySQL activo;
  - no se recupero MySQL ni se ejecuto venta real sin autorizacion.
- Siguiente paso:
  - levantar/recuperar MySQL si sigue caido;
  - abrir turno, cargar stock y ejecutar UAT real con token `VENTAS_POS_SALDO_CRM_REAL`.

## Avance 2026-07-07 - Verificador posterior saldo CRM

- Se agrego `storage/uat/uat_ventas_pos_saldo_crm_post_readonly.php`.
- Proposito:
  - auditar una venta POS con saldo CRM despues de ejecutarla;
  - confirmar que saldo CRM no genero movimiento de caja;
  - validar coincidencia entre pago POS y movimiento CRM;
  - validar que pagos de caja coinciden con movimientos de caja.
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_saldo_crm_post_readonly.php`: sin errores;
  - sin `--folio` o `--id_venta`: bloquea en modo read-only.
- Uso posterior esperado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_saldo_crm_post_readonly.php --folio=POS-YYYYMMDD-###### --compact=1`.

## Avance 2026-07-07 - Runbook UAT saldo CRM

- Se agrego `storage/uat/uat_ventas_pos_saldo_crm_runbook_readonly.php`.
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_saldo_crm_runbook_readonly.php`: sin errores;
  - `--compact=1`: genera autorizaciones y scripts sin escribir BD.
- Readiness actual:
  - MySQL preflight `ok=true`, sin bloqueos;
  - asignacion POS activa para usuario `1`;
  - almacen `5`, caja `2`;
  - cliente CRM `157` con saldo `100`;
  - venta estimada `295`;
  - bloqueo restante: no hay turno abierto.
- Cierre esperado despues de venta:
  - monto inicial `500`;
  - pago caja `195`;
  - saldo CRM `100` no mueve caja;
  - monto contado esperado `695`.
- Autorizaciones preparadas:
  - abrir turno;
  - ejecutar venta con token `VENTAS_POS_SALDO_CRM_REAL`;
  - cerrar turno con `monto_contado=695`;
  - cargar stock solo si readiness posterior marca insuficiencia.

## Avance 2026-07-07 - UX pago saldo cliente en POS

- Vista `app/vistas/paginas/apps/erp/ventas/pos.php`:
  - se agrego boton rapido `Saldo cliente`.
- JS `public/assets/js/custom/apps/erp/ventas/pos.js`:
  - `saldo_crm` se muestra como pago virtual;
  - no usa selector de metodo de caja;
  - exige cliente CRM seleccionado;
  - informa que no entra a caja;
  - el mensaje de cobro separa caja y saldo CRM;
  - bloquea si saldo CRM generaria cambio.
- Modelo `VentasErp::prevalidarPagosPos`:
  - bloquea `Saldo CRM no puede generar cambio`.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`: sin errores;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: sin errores;
  - readiness valido caja `195` + saldo CRM `100`: solo falta turno;
  - readiness negativo caja `250` + saldo CRM `100`: bloquea cambio por saldo CRM.

## Avance 2026-07-07 - Ticket/corte saldo cliente

- Modelo `app/modelos/VentasErp.php`:
  - ticket formal etiqueta saldo CRM como `Saldo cliente (sin caja)`;
  - corte de turno separa pagos normales de `Pagos sin caja`.
- JS `public/assets/js/custom/apps/erp/ventas/venta_detalle.js`:
  - detalle de venta muestra `Saldo cliente` y `No entra a caja`.
- JS `public/assets/js/custom/apps/erp/ventas/pos.js`:
  - corte embebido traduce saldo CRM a `Saldo cliente` y `Sin caja`.
- JS `public/assets/js/custom/apps/erp/ventas/caja_turnos.js`:
  - arqueo cambia `Vales/saldo` a `Vales/otros`;
  - aclara que saldo cliente CRM no se captura en arqueo.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: sin errores;
  - `node --check public\assets\js\custom\apps\erp\ventas\venta_detalle.js`: sin errores;
  - `node --check public\assets\js\custom\apps\erp\ventas\caja_turnos.js`: sin errores.
- Siguiente autorizacion fuerte pendiente:
  - ya fue ejecutada el 2026-07-07 con folios `POS-20260707-000001` y `TUR-20260707-002-001`;
  - saldo CRM quedo en `$0.00`;
  - turno cerro con diferencia `$0.00`.

## Avance 2026-07-07 - UAT real saldo cliente CRM

- Turno:
  - `TUR-20260707-002-001`;
  - `id_turno_caja=18`;
  - monto inicial `$500.00`;
  - monto esperado `$695.00`;
  - monto contado `$695.00`;
  - diferencia `$0.00`.
- Venta:
  - `POS-20260707-000001`;
  - `id_venta=17`;
  - cliente CRM `157`;
  - SKU `1760`;
  - total `$295.00`;
  - pago caja `$195.00`;
  - pago saldo CRM `$100.00`.
- Trazabilidad:
  - movimiento caja venta `39`;
  - pago saldo CRM sin movimiento de caja;
  - movimiento CRM `CRM-SAL-20260707-000001`;
  - saldo CRM final `$0.00`;
  - kardex inventario `82`;
  - existencia `34`: `1 -> 0`.
- Verificaciones:
  - post venta saldo CRM `ok=true`, hallazgos `[]`;
  - post cierre turno `ok=true`, hallazgos `[]`;
  - ticket formal `ok=true`, muestra `Saldo cliente no caja`;
  - corte formal `ok=true`, hallazgos `[]`.
- Siguiente trabajo recomendado:
  - revisar UX/UI en navegador para confirmar que el operador pueda hacer el mismo flujo desde POS;
  - conectar reporte/corte visual con pagos sin caja de forma mas explicita;
  - definir siguiente frente POS: devolucion de venta pagada con saldo cliente, o alta/uso de cliente CRM desde POS.

## Avance 2026-07-07 - Saldo cliente visible en POS

- Endpoint:
  - `/ventas/cliente_saldo_crm_readonly_erp`;
  - usa `VentasErp::clienteSaldoCrmReadOnly`.
- POS:
  - muestra saldo CRM disponible debajo de metodos de pago;
  - consulta saldo al seleccionar o activar cliente CRM;
  - bloquea `Saldo cliente` si el saldo conocido es `$0.00`;
  - propone como maximo el saldo disponible conocido.
- Validacion:
  - cliente CRM `157` consulta saldo disponible `$0.00`;
  - movimientos recientes incluyen `CRM-SAL-20260707-000001`;
  - no escribe BD.
- Siguiente revision UX:
  - completada con `storage/uat/uat_ventas_pos_saldo_crm_playwright_uat.js`;
  - evidencia visual `public/storage/uat/pos_saldo_cliente_uat.png`.

## Avance 2026-07-07 - UAT visual saldo cliente POS

- Resultado:
  - `ok=true`;
  - cliente CRM `157` seleccionado visualmente;
  - indicador `Sin saldo cliente disponible`;
  - saldo `$0.00`;
  - boton `Saldo cliente` deshabilitado;
  - no agrego pago;
  - no cobro;
  - no movio caja ni inventario.
- Observacion:
  - consola con `ERR_NETWORK_ACCESS_DENIED` por recursos externos, sin afectar flujo POS.
- Siguiente frente recomendado:
  - preparar devolucion/cancelacion de venta que uso saldo cliente, definiendo si el reembolso vuelve a saldo CRM, caja o decision financiera;
  - o cerrar tablero/reporte de pagos sin caja en corte/reportes.

## Avance 2026-07-07 - Readiness reversa con saldo cliente

- Script nuevo:
  - `storage/uat/uat_ventas_pos_reversa_saldo_crm_readiness_readonly.php`.
- Contrato:
  - solo lectura;
  - usa dry-run de devolucion;
  - compara reembolso estimado contra pagos originales;
  - no mueve caja;
  - no mueve inventario;
  - no toca saldo CRM.
- UAT sobre `POS-20260707-000001`:
  - total `$295.00`;
  - caja pagada `$195.00`;
  - saldo CRM pagado `$100.00`;
  - reembolso completo desde caja queda bloqueado porque excede caja disponible por `$100.00`;
  - `saldo_favor` queda como dry-run posible, pero falta liga automatica a ledger CRM.
- Decision de arquitectura:
  - no aplicar reversa real de ventas con saldo CRM hasta preparar decision financiera mixta o reintegro a saldo CRM.
- Siguiente autorizacion fuerte recomendada:
  - preparar DDL/modelo de reversas POS con componentes financieros:
    - componente caja;
    - componente saldo CRM;
    - referencia a movimiento CRM de abono;
    - ticket/corte separado por caja y no caja.

## Avance 2026-07-07 - DDL preparado reversas con saldo CRM

- Modelo:
  - `VentasErpEsquema::planActualizarReversasSaldoCrmPos`;
  - `VentasErpEsquema::auditarReversasSaldoCrmPos`.
- Scripts:
  - `storage/uat/uat_ventas_pos_reversa_saldo_crm_schema_readonly.php`;
  - `storage/uat/uat_ventas_pos_reversa_saldo_crm_schema_apply_authorized.php`.
- Estructura:
  - agrega `id_cliente_crm`, `monto_reintegro_saldo_crm`, `monto_no_caja` a `erp_ventas_devoluciones`;
  - crea `erp_ventas_devoluciones_finanzas` para componentes financieros de reversa.
- Read-only:
  - `ok=true`;
  - tabla financiera aun no existe;
  - columnas resumen aun no existen;
  - SQL generado sin ejecutar.
- Seguridad:
  - apply sin token queda bloqueado;
  - no escribio BD.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErpEsquema.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_reversa_saldo_crm_schema_readonly.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_reversa_saldo_crm_schema_apply_authorized.php`: sin errores.
- Proxima autorizacion fuerte:
  - `AUTORIZO APLICAR DDL REVERSAS POS CON SALDO CRM usando respaldo UAT POS vigente con token VENTAS_POS_REVERSA_SALDO_CRM_DDL para UAT POS/CRM`.

## Avance 2026-07-07 - DDL reversas saldo CRM aplicado

- Autorizacion recibida:
  - `VENTAS_POS_REVERSA_SALDO_CRM_DDL`.
- Primer intento:
  - `ok=false`;
  - MySQL no disponible;
  - no ejecuto DDL.
- Se levanto `mysqld` con XAMPP y se revalido conexion de la app.
- Segundo intento:
  - `ok=true`;
  - agrego columnas resumen a `erp_ventas_devoluciones`;
  - creo `erp_ventas_devoluciones_finanzas`;
  - auditoria posterior con tablas, columnas e indices `existe=true`.
- Readiness posterior:
  - reembolso completo desde caja de `POS-20260707-000001` sigue bloqueado por `$100.00`;
  - `saldo_favor` sigue en read-only valido, pero falta ruta real ligada a CRM.
- Siguiente frente sin aplicar todavia:
  - preparar modelo real para reversa mixta:
    - componente caja;
    - componente reintegro saldo CRM;
    - movimiento CRM de abono;
    - post-readonly que valide componentes.

## Avance 2026-07-07 - Modelo real reversas saldo CRM preparado

- Autorizacion recibida:
  - `VENTAS_POS_REVERSA_SALDO_CRM_MODELO`.
- Modelo:
  - `VentasErp::confirmarReversaPosReal` acepta `mixta_saldo_crm` y `reintegro_saldo_crm`;
  - calcula componentes financieros desde pagos originales y reversas previas;
  - bloquea caja si el monto excede lo realmente pagado en caja;
  - reintegra saldo CRM con movimiento `abono`;
  - registra componentes en `erp_ventas_devoluciones_finanzas`;
  - recalcula componentes dentro de la transaccion despues de bloquear venta.
- Scripts:
  - `storage/uat/uat_ventas_pos_reversa_saldo_crm_apply_authorized.php`;
  - `storage/uat/uat_ventas_pos_reversa_post_readonly.php` ampliado.
- Guardrail:
  - script real sin token queda bloqueado;
  - no escribio BD.
- Readiness UAT:
  - venta `POS-20260707-000001`;
  - `id_venta=17`;
  - `id_venta_detalle=18`;
  - `mixta_saldo_crm`: caja `$195.00`, saldo CRM `$100.00`, `ok=true`;
  - `reintegro_saldo_crm` completo: bloqueado por exceder saldo CRM pagado.
- Proxima autorizacion fuerte:
  - `AUTORIZO EJECUTAR REVERSA POS UAT REAL CON SALDO CRM usando respaldo UAT POS vigente con token VENTAS_POS_REVERSA_SALDO_CRM_REAL id_usuario=1 folio=POS-20260707-000001 id_venta_detalle=18 cantidad=1 tipo=devolucion decision_inventario=cuarentena decision_financiera=mixta_saldo_crm motivo="UAT devolucion mixta caja y saldo CRM"`.

## Avance 2026-07-07 - Reversa saldo CRM bloqueada sin turno

- Autorizacion recibida para reversa real:
  - `VENTAS_POS_REVERSA_SALDO_CRM_REAL`.
- Resultado:
  - `ok=false`;
  - bloqueo `turno_caja_reembolso_pendiente`;
  - venta requiere caja `2`, almacen `5`;
  - no habia turno abierto.
- Verificacion posterior:
  - `uat_ventas_pos_reversa_post_readonly.php --folio_venta=POS-20260707-000001` no encontro devolucion;
  - no se crearon componentes financieros;
  - no se movio caja/CRM/inventario.
- Correccion menor:
  - `storage/uat/uat_ventas_pos_reversa_saldo_crm_apply_authorized.php` ahora usa `$ok` para `siguiente_paso`, evitando mensaje confuso en respuestas `warning`.
- Siguiente autorizacion necesaria:
  - abrir turno POS en caja/almacen asignados;
  - repetir reversa real;
  - cerrar turno con monto esperado ajustado por reembolso de caja.

## Avance 2026-07-07 - Reversa saldo CRM real aplicada

- Turno:
  - `TUR-20260707-002-002`;
  - `id_turno_caja=19`;
  - monto inicial `$500.00`;
  - movimiento apertura `40`.
- Reversa:
  - `DEV-20260707-000001`;
  - `id_devolucion=3`;
  - venta `POS-20260707-000001`;
  - detalle `18`;
  - estatus venta `devuelta`;
  - decision inventario `cuarentena`.
- Componentes:
  - caja `$195.00`, componente `DFIN-20260707-000001`, movimiento caja `41`;
  - saldo CRM `$100.00`, componente `DFIN-20260707-000002`, movimiento CRM `3`;
  - movimiento CRM `CRM-SAL-20260707-000002`, saldo `$0.00 -> $100.00`.
- Cierre:
  - monto esperado `$305.00`;
  - monto contado `$305.00`;
  - diferencia `$0.00`;
  - no queda turno abierto.
- Validaciones:
  - post-readonly reversa `ok=true`, hallazgos `[]`;
  - post-cierre turno `ok=true`, hallazgos `[]`;
  - readiness posterior bloquea segunda reversa porque la venta ya esta `devuelta`.
- Pendientes inmediatos:
  - registrar evidencia del reembolso caja `id_movimiento_caja=41`;
  - avanzar inspeccion fisica de la devolucion detalle `id_devolucion_detalle=3`;
  - revisar UI/ticket/corte para componentes financieros de devolucion mixta.

## Avance 2026-07-07 - Ticket devolucion mixta preparado

- Ticket `DEV-20260707-000001`:
  - `ok=true`;
  - hallazgos `[]`;
  - muestra `Reembolso $195.00`;
  - muestra `Reint saldo cliente $100.00`;
  - muestra `No caja $100.00`;
  - lista componentes `Caja` y `Saldo cliente`.
- Evidencia caja:
  - bandeja pendiente incluye `id_movimiento_caja=41`;
  - no hay evidencia registrada para movimiento `41`;
  - aplicador de evidencia acepta ahora `UAT_POS_VIGENTE` con `--respaldo_uat_vigente=1`;
  - sin token queda bloqueado correctamente.
- Proxima autorizacion fuerte:
  - `AUTORIZO REGISTRAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_EVIDENCIA_REAL id_usuario=1 id_movimiento_caja=41 tipo_evidencia=ticket_firmado referencia_externa=DEV-20260707-000001 descripcion="Comprobante UAT de reembolso mixto caja y saldo CRM firmado por cliente"`.

## Avance 2026-07-07 - Evidencia reembolso mixto registrada

- Evidencia:
  - `id_evidencia_caja=3`;
  - movimiento caja `41`;
  - tipo `ticket_firmado`;
  - estatus `recibida`;
  - referencia externa `DEV-20260707-000001`.
- Movimiento asociado:
  - tipo `reembolso`;
  - categoria `reembolso_cliente`;
  - monto `$195.00`;
  - turno `TUR-20260707-002-002`;
  - caja `2`, almacen `5`;
  - venta `POS-20260707-000001`;
  - devolucion `DEV-20260707-000001`.
- Validaciones:
  - detalle evidencia `ok=true`, `total_registros=1`;
  - revisores readonly `ok=true`, candidato `id_usuario=1`;
  - regla: preferir revisor distinto al capturista; `permitir_mismo_usuario=1` solo para UAT controlada;
  - `php -l storage/uat/uat_ventas_pos_caja_evidencia_apply_authorized.php`: sin errores;
  - `php -l app/modelos/VentasErp.php`: sin errores.
- Impacto:
  - no movio caja;
  - no movio saldo CRM;
  - no movio inventario;
  - queda pendiente revision/aprobacion de evidencia.

## Avance 2026-07-07 - Inspeccion fisica devolucion mixta preparada

- Bandeja pendiente:
  - `total_registros=2`;
  - devolucion nueva `DEV-20260707-000001`;
  - `id_devolucion_detalle=3`;
  - venta `POS-20260707-000001`;
  - SKU `TP-40352-500GR`;
  - decision inventario `cuarentena`;
  - inspeccion `pendiente`.
- Dry-run:
  - `id_usuario=1`;
  - decision `mantener_cuarentena`;
  - condicion `pendiente_revision`;
  - `ok=true`;
  - bloqueos `[]`.
- Aplicador:
  - `storage/uat/uat_ventas_pos_devolucion_inspeccion_fisica_apply_authorized.php`;
  - acepta `UAT_POS_VIGENTE`;
  - sin token queda bloqueado.
- Siguiente paso:
  - primero revisar/aprobar evidencia caja `id_evidencia_caja=3`;
  - despues registrar inspeccion fisica documental para `id_devolucion_detalle=3`.

## Avance 2026-07-07 - Evidencia reembolso mixto aprobada

- Autorizacion ejecutada:
  - token `VENTAS_POS_CAJA_EVIDENCIA_REVISION_REAL`;
  - `id_evidencia_caja=3`;
  - decision `aprobada`;
  - `permitir_mismo_usuario=1` por UAT controlada.
- Resultado:
  - evidencia `3`: `aprobada`;
  - movimiento caja `41`: `evidencia_estado=aprobada`;
  - reembolso caja `$195.00`;
  - devolucion `DEV-20260707-000001`;
  - venta `POS-20260707-000001`.
- Validacion:
  - post-readonly reversa `hallazgos=[]`;
  - componentes financieros intactos:
    - caja `$195.00`;
    - saldo CRM `$100.00`;
  - saldo CRM reintegrado sigue ligado a movimiento `CRM-SAL-20260707-000002`.
- Pendiente siguiente:
  - registrar inspeccion fisica documental de `id_devolucion_detalle=3`, manteniendo cuarentena y sin mover inventario.

## Avance 2026-07-07 - Inspeccion fisica devolucion mixta registrada

- Autorizacion ejecutada:
  - token `VENTAS_POS_DEVOLUCION_FISICA_REAL`;
  - `id_devolucion_detalle=3`;
  - decision `mantener_cuarentena`.
- Resultado:
  - inspeccion `IFD-20260707-000001`;
  - `id_inspeccion_fisica=2`;
  - devolucion `DEV-20260707-000001`;
  - estado `cuarentena_confirmada`;
  - sin kardex;
  - sin movimiento inventario;
  - sin garantia.
- Validacion:
  - `DEV-20260707-000001` ya no aparece en pendientes fisicos;
  - pendientes fisicos bajan a `1`, solo queda historico `DEV-20260630-000001`;
  - post-readonly reversa sin hallazgos;
  - dry-run posterior bloquea segunda inspeccion por estado `cuarentena_confirmada`.
- Siguiente frente sugerido:
  - decidir si cerramos pendiente historico `DEV-20260630-000001`;
  - o avanzar UI de devoluciones/inspeccion para operar este flujo desde POS sin scripts UAT.

## Avance 2026-07-08 - UI inspeccion fisica devoluciones POS

- Ruta de trabajo nueva:
  - `C:\xampp\htdocs\panel_de_control`.
- Estado read-only inicial:
  - readiness POS `ok=true`;
  - sin turno abierto;
  - devoluciones fisicas pendientes `1`;
  - pendiente historico `DEV-20260630-000001`, `id_devolucion_detalle=1`.
- Cambios preparados:
  - endpoint `/ventas/devolucion_inspeccion_fisica_registrar_erp`;
  - vista `apps/erp/ventas/devoluciones.php` con panel de inspeccion fisica;
  - JS `devoluciones.js` con seleccion de partida, prevalidacion y confirmacion de cuarentena.
- Guardrails:
  - POST con CSRF;
  - requiere `ventas.operar`;
  - modelo solo permite `mantener_cuarentena` en esta fase;
  - no reintegra disponible;
  - no crea merma;
  - no crea garantia;
  - no crea kardex ni mueve inventario.
- Validaciones:
  - `php -l app/controladores/Ventas.php`: sin errores;
  - `node --check public/assets/js/custom/apps/erp/ventas/devoluciones.js`: sin errores.
- Ajuste posterior:
  - version de asset actualizada a `20260709-inspeccion-ui1` para evitar cache viejo del navegador.
- Verificacion navegador:
  - navegador integrado no disponible en esta sesion;
  - queda pendiente UAT manual en `/ventas/devoluciones`.
- Pendiente:
  - probar desde navegador con usuario autorizado;
  - si se confirma el pendiente historico desde UI, validar que la bandeja quede en cero.

## Avance 2026-07-09 - Bandeja fisica POS sin pendientes

- Observacion:
  - el pendiente historico `DEV-20260630-000001` ya aparece con inspeccion registrada;
  - no fue ejecutado por comandos de esta sesion, se toma como cambio existente/UAT externa.
- Validacion read-only:
  - `DEV-20260630-000001`;
  - `id_devolucion_detalle=1`;
  - `id_inspeccion_fisica=3`;
  - `inspeccion_estado=cuarentena_confirmada`;
  - `fecha_inspeccion_fisica=2026-07-09 09:07:14`;
  - post-readonly reversa `hallazgos=[]`.
- Readiness POS:
  - devoluciones fisicas pendientes `0`;
  - sin turno abierto;
  - pedidos/apartados siguen bloqueados por turno/stock en readiness.
- Impacto observado:
  - no hay movimiento inventario de devolucion;
  - no hay kardex de reintegro;
  - no hay reembolso caja asociado a esa devolucion historica.
- Siguiente frente:
  - validar visualmente `/ventas/devoluciones` con la bandeja vacia;
  - avanzar decisiones fisicas siguientes: reintegrar disponible, merma, garantia/proveedor o reparacion, todas con DDL/UAT separada.

## Avance 2026-07-09 - Plan inspeccion fisica avanzada

- Documento creado:
  - `docs/erp_ventas_pos_inspeccion_fisica_plan.md`.
- Alcance:
  - define decisiones fisicas posteriores a cuarentena;
  - separa devolucion comercial de destino fisico;
  - marca riesgos de inventario, kardex, garantia/proveedor y reparacion.
- Decision:
  - no implementar reintegro/merma/garantia como botones directos hasta tener DDL, modelo transaccional, UAT y reportes.
- Siguiente recomendado:
  - preparar Fase 2 `reintegrar_disponible` en modo read-only.

## Avance 2026-07-09 - Filtro destino final en devoluciones

- Cambios:
  - `VentasErp::devolucionesInventarioPendientesReadOnly` acepta `inspeccion_estado`;
  - UI `Ventas > Devoluciones` agrega filtro de estado de inspeccion;
  - JS evita mostrar boton `Inspeccionar` en partidas ya inspeccionadas;
  - script UAT read-only acepta `--inspeccion_estado=...`.
- Validaciones:
  - `php -l app/modelos/VentasErp.php`: sin errores;
  - `php -l app/vistas/paginas/apps/erp/ventas/devoluciones.php`: sin errores;
  - `php -l storage/uat/uat_ventas_pos_devoluciones_inventario_pendientes_readonly.php`: sin errores;
  - `node --check public/assets/js/custom/apps/erp/ventas/devoluciones.js`: sin errores.
- Estado read-only:
  - `cuarentena + pendiente`: `0`;
  - `cuarentena + cuarentena_confirmada`: `3`.
- Siguiente frente:
  - preparar dry-run de destino final `reintegrar_disponible` sobre una de las 3 partidas, sin escribir BD.

## Avance 2026-07-09 - Devoluciones listas para prueba real controlada

- Vista ajustada:
  - `app/vistas/paginas/apps/erp/ventas/devoluciones.php`.
- Cambios:
  - expone `window.ERP_CSRF_TOKEN` con `SesionSeguridad::csrfToken()`;
  - actualiza textos para diferenciar simulacion de reversa vs inspeccion real de cuarentena;
  - cache-bust de JS a `20260709-inspeccion-ui2`.
- Alcance listo para navegador:
  - `/ventas/devoluciones`;
  - consultar devoluciones fisicas;
  - prevalidar inspeccion;
  - confirmar cuarentena documental real.
- Guardrail:
  - no mueve inventario;
  - no crea kardex;
  - no aplica destino final.
- Documento operativo:
  - `docs/erp_ventas_pos_inspeccion_fisica_plan.md`, seccion `Pieza lista para prueba real controlada`.

## Avance 2026-07-09 - Dry-run destino final cuarentena POS

- Cambios:
  - modelo `VentasErp::destinoFinalCuarentenaDevolucionDryRun`;
  - endpoint `/ventas/devolucion_destino_final_dryrun_erp`;
  - panel `Destino final de cuarentena` en `/ventas/devoluciones`;
  - script `storage/uat/uat_ventas_pos_destino_final_cuarentena_dryrun.php`.
- Contrato:
  - read-only/dry-run;
  - no actualiza devolucion;
  - no crea kardex;
  - no mueve inventario;
  - no crea garantia/reparacion/merma.
- UAT read-only:
  - `id_devolucion_detalle=3`;
  - `reintegrar_disponible`: sin bloqueos, plan entrada existencia `34`, `0 -> 1`;
  - `merma`: sin bloqueos, exige causa/evidencia/autorizador;
  - `garantia_proveedor`: sin bloqueos, requiere folio operativo;
  - `reparacion`: sin bloqueos, requiere folio operativo.
- DDL requerido para apply real:
  - `erp_ventas_devoluciones_detalle.destino_final`;
  - `erp_ventas_devoluciones_detalle.fecha_destino_final`;
  - `erp_ventas_devoluciones_detalle.resuelto_por`.
- Siguiente autorizacion robusta:
  - `AUTORIZO PREPARAR DDL DESTINO FINAL CUARENTENA POS usando respaldo UAT POS vigente con token VENTAS_POS_DESTINO_FINAL_CUARENTENA_DDL para UAT POS`.

## Avance 2026-07-09 - Prepare DDL destino final cuarentena POS

- Autorizacion recibida:
  - `AUTORIZO PREPARAR DDL DESTINO FINAL CUARENTENA POS usando respaldo UAT POS vigente con token VENTAS_POS_DESTINO_FINAL_CUARENTENA_DDL para UAT POS`.
- Cambios:
  - `VentasErpEsquema::planActualizarDestinoFinalCuarentenaPos`;
  - `VentasErpEsquema::auditarDestinoFinalCuarentenaPos`;
  - `/ventas/esquema_auditar_destino_final_cuarentena_pos`;
  - `/ventas/esquema_actualizar_destino_final_cuarentena_pos`;
  - `storage/uat/uat_ventas_pos_destino_final_cuarentena_ddl_prepare.php`.
- Resultado prepare:
  - `ok=true`;
  - `read_only=true`;
  - SQL generado sin ejecutar.
- Faltantes detectados:
  - columnas de destino final en `erp_ventas_devoluciones_detalle`;
  - columnas de destino final en `erp_ventas_devoluciones_inspecciones`;
  - indices por destino final y movimiento destino.
- Validaciones:
  - `php -l app/modelos/VentasErpEsquema.php`: sin errores;
  - `php -l app/controladores/Ventas.php`: sin errores;
  - `php -l storage/uat/uat_ventas_pos_destino_final_cuarentena_ddl_prepare.php`: sin errores.
- Siguiente autorizacion:
  - `AUTORIZO APLICAR DDL DESTINO FINAL CUARENTENA POS usando respaldo UAT POS vigente con token VENTAS_POS_DESTINO_FINAL_CUARENTENA_DDL para UAT POS`.
