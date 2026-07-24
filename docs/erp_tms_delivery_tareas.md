# ERP TMS Delivery - Tareas vivas

Documentacion IA: Codex GPT-5  
Fecha base: 2026-07-24  
Estado: plan de trabajo inicial; no implica cambios de esquema, codigo ni BD.  
Documento rector: `docs/erp_tms_delivery_plan.md`

## Objetivo operativo

Construir un modulo TMS/Delivery independiente para servicios de entrega, recoleccion y traslados, sin que el estatus logistico gobierne Ventas, productos, garantias o devoluciones.

Regla central:

- POS, ecommerce, postventa o un operador pueden solicitar un servicio TMS.
- TMS opera su propio folio y solo decide sobre el servicio logistico.
- Si no se entrega, TMS registra evidencia y deja el servicio reprogramado, pendiente de cliente o cancelado.
- La venta, garantia, devolucion o decision comercial se atiende en su propio modulo.

## Alcance fase 1

Fase 1 debe ser documental-operativa, sin rutas avanzadas.

Debe permitir:

- crear folio TMS desde solicitud manual o desde POS/pedido como origen opcional;
- capturar cliente/contacto/direccion por snapshot;
- registrar tipo de servicio, prioridad, ventana y cobro logistico;
- operar estados basicos: solicitada, programada, lista para salida, en ruta, entregada, no entregada, pendiente cliente, cancelada;
- registrar resultado logistico independiente: completa, parcial, sin entrega, cliente recogera, nuevo intento requerido, cerrada sin entrega;
- registrar eventos y evidencia minima;
- listar servicios en bandeja TMS;
- consultar reportes basicos.

No debe:

- cancelar ventas;
- confirmar ventas;
- mover inventario por si mismo;
- decidir garantias;
- cobrar productos;
- crear SKUs o productos de envio.

## TMS-T001 - Cerrar contrato de dominio

Estado: completado documentalmente.

Archivos:

- `docs/erp_tms_delivery_plan.md`
- `docs/erp_tms_delivery_tareas.md`

Criterio de cierre:

- queda documentado que TMS no es submodulo de Ventas;
- queda documentado que TMS no decide venta, garantia ni producto;
- queda documentado que POS solo solicita un servicio logistico y puede informar estatus/cobro del envio.

Resultado:

- El plan rector fue ajustado para eliminar la dependencia fuerte con Ventas.
- Se corrigio el lenguaje de postventa para evitar sugerir que garantia incluye entrega gratis.

## TMS-T002 - Propuesta DDL inicial

Estado: completado como propuesta documental.

Objetivo:

Definir tablas propias de TMS sin ejecutar migraciones.

Archivo esperado:

- `docs/erp_tms_delivery_schema_propuesta.sql`

Tablas propuestas:

- `erp_tms_servicios`
- `erp_tms_servicios_detalle`
- `erp_tms_servicios_costos`
- `erp_tms_eventos`
- `erp_tms_evidencias`

Criterio de cierre:

- [x] DDL propuesto existe como SQL documental.
- [x] No modifica BD.
- [x] No agrega FKs obligatorias hacia Ventas.
- [x] Las relaciones a POS/Ventas/Postventa quedan como origen opcional mediante `solicitado_por_*`.
- [x] Incluye indices por folio, estatus, fecha, cliente, responsable y origen.

Resultado:

- Se creo `docs/erp_tms_delivery_schema_propuesta.sql`.
- La tabla principal `erp_tms_servicios` no contiene `id_venta` obligatorio.
- Los detalles logisticos no dependen de `erp_ventas_detalle`; pueden guardar referencias opcionales y snapshots.
- Se separan costos/cobros, eventos y evidencias.
- El DDL queda pendiente de revision antes de crear `TmsEsquema.php`.

## TMS-T003 - Esquema PHP audit/plan

Estado: completado en dry-run; no se ejecuto DDL.

Objetivo:

Crear `app/modelos/TmsEsquema.php` con auditoria y plan de actualizacion, siguiendo patron `*Esquema.php`.

Archivos esperados:

- `app/modelos/TmsEsquema.php`
- endpoints futuros en `app/controladores/Tms.php`

Reglas:

- con `ejecutar=false` solo genera plan;
- con `ejecutar=true` requerira respaldo externo y autorizacion;
- no se ejecuta DDL en esta tarea sin autorizacion explicita;
- todo metodo nuevo debe documentarse segun `docs/erp_estandar_documentacion_codigo.md`.

Criterio de cierre:

- [x] `C:\xampp\php\php.exe -l app/modelos/TmsEsquema.php` sin errores.
- [x] Auditoria preparada para devolver faltantes sin escribir BD.
- [x] Plan preparado para devolver SQL de creacion sin escribir BD.
- [ ] Endpoints en controlador futuro pendientes para exponer auditoria/plan.

Resultado:

- Se creo `app/modelos/TmsEsquema.php`.
- Metodos disponibles:
  - `tablasTms()`;
  - `auditarTmsDelivery()`;
  - `planActualizarTmsDelivery($ejecutar = false)`.
- El modelo no ejecuta DDL por defecto.
- El contrato documenta que TMS no es submodulo de Ventas, no cancela ventas, no decide garantias y no mueve inventario por si mismo.

Continuacion:

- La parte de endpoints se hara en `TMS-T005`, junto con `Tms.php`, para no abrir controlador antes de definir permisos/base de navegacion.

## TMS-T004 - Permisos base

Estado: completado en propuesta y codigo; no se sincronizo BD.

Objetivo:

Proponer permisos TMS en seguridad sin aplicarlos todavia.

Permisos:

- `tms.ver`
- `tms.crear`
- `tms.programar`
- `tms.operar`
- `tms.evidencias`
- `tms.costos`
- `tms.autorizar`
- `tms.reportes`

Roles sugeridos:

- `direccion`: ver, autorizar, reportes, costos.
- `administrador_erp`: todos.
- `ventas`: ver, crear.
- `almacen`: ver.
- `crm`: ver, crear cuando sea solicitud manual de cliente.
- `auditor`: ver, reportes.
- `solo_lectura`: ver.

Criterio de cierre:

- [x] Permisos propuestos en documento.
- [x] Permisos agregados a `SeguridadEsquema.php`.
- [x] Se definio no crear rol `delivery` todavia como requisito obligatorio.

Resultado:

- Se creo `docs/erp_tms_delivery_permisos_plan.md`.
- Se agregaron permisos `tms.*` a `app/modelos/SeguridadEsquema.php`.
- Se asignaron a roles base existentes sin crear rol `delivery`.
- Permisos propuestos:
  - `tms.ver`;
  - `tms.crear`;
  - `tms.programar`;
  - `tms.operar`;
  - `tms.evidencias`;
  - `tms.costos`;
  - `tms.autorizar`;
  - `tms.reportes`.
- Decision: fase 1 puede usar roles existentes; rol futuro `delivery` solo si hay usuarios/repartidores que entren al sistema.
- Validacion: `C:\xampp\php\php.exe -l app\modelos\SeguridadEsquema.php` sin errores.

Pendiente:

- Ejecutar sincronizacion de seguridad en BD con autorizacion cuando el dueno lo permita.

## TMS-T005 - Controlador y modelo read-only/dry-run

Estado: completado base read-only/dry-run; permisos `tms.*` definidos en codigo, pendientes de sincronizacion en BD.

Objetivo:

Crear modulo base sin escritura real.

Archivos esperados:

- `app/controladores/Tms.php`
- `app/modelos/TmsDelivery.php`
- `app/vistas/paginas/apps/tms/servicios.php`

Endpoints iniciales:

- [x] `/tms/servicios`
- [x] `/tms/esquema_auditar_tms`
- [x] `/tms/esquema_plan_tms`
- [x] `/tms/servicio_dryrun_erp`
- [x] `/tms/servicios_listar_erp`
- [x] `/tms/catalogos_erp`
- [x] `/tms/acciones_contrato_erp`

Reglas:

- [x] El controlador se agrego a protegidos en `Core.php`.
- [x] Endpoints POST pasan por sesion/CSRF del `Core.php` y permisos del controlador.
- [x] No existe guardado real.
- [x] `esquema_plan_tms` bloquea `ejecutar=1` en esta fase.

Criterio de cierre:

- [x] Controlador/modelo cargan sin errores de sintaxis.
- [x] Endpoints read-only/dry-run responden contrato JSON del proyecto.
- [x] No escribe BD.

Resultado:

- Se creo `app/controladores/Tms.php`.
- Se creo `app/modelos/TmsDelivery.php`.
- Se creo vista minima `app/vistas/paginas/apps/tms/servicios.php`.
- Se agrego `Tms` a controladores protegidos en `app/core/Core.php`.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\TmsDelivery.php`: sin errores.
  - `C:\xampp\php\php.exe -l app\modelos\TmsEsquema.php`: sin errores.
  - `C:\xampp\php\php.exe -l app\controladores\Tms.php`: sin errores.
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\tms\servicios.php`: sin errores.
  - `C:\xampp\php\php.exe -l app\core\Core.php`: sin errores.

Nota operativa:

- Los permisos `tms.*` ya existen en `SeguridadEsquema.php`, pero no se ha ejecutado sincronizacion de seguridad en BD; por tanto la pantalla/endpoints TMS pueden quedar bloqueados por permisos hasta aplicar esa sincronizacion autorizada.

## TMS-T006 - Bandeja TMS inicial

Estado: completado como UI inicial con validacion y creacion protegida; depende de permisos/esquema para uso real.

Objetivo:

Crear UI operativa para listar servicios y validar un servicio en dry-run.

Archivos esperados:

- `app/vistas/paginas/apps/tms/servicios.php`
- `public/assets/js/custom/apps/tms/servicios.js`

UX:

- [x] Filtros por estatus, tipo y cobro.
- [x] KPIs iniciales: servicios, en ruta, no entregadas y pendiente cliente.
- [x] Tabla de folio, tipo, cliente, ventana, cobro y resultado.
- [x] Formulario compacto para validar solicitud de servicio sin guardar.
- [x] Boton de crear servicio conectado a `/tms/servicio_guardar_erp`.
- [x] Estados de no entrega visibles en contrato: reprogramar, pendiente cliente, cancelar servicio.
- [x] Acceso en sidebar `TMS > Delivery` condicionado por permisos TMS.

Criterio de cierre:

- [x] `php -l` vista sin errores.
- [x] `node --check` JS sin errores.
- [x] No requiere datos reales para renderizar estado vacio.
- [x] Si falta esquema, muestra aviso controlado.
- [x] Si se intenta crear sin esquema, muestra bloqueo controlado desde backend.

Resultado:

- La pantalla `/tms/servicios` carga layout del sistema.
- El sidebar queda como modulo padre `TMS` y dentro el grupo `Delivery`:
  - `Bandeja TMS`: `/tms/servicios`, permiso `tms.ver`;
  - `Operacion y rutas`: `/tms/operacion`, permiso `tms.operar`;
  - `Costos logisticos`: `/tms/costos`, permiso `tms.costos`;
  - `Reportes delivery`: `/tms/reportes`, permiso `tms.reportes`;
  - `Configuracion delivery`: `/tms/configuracion`, permiso `tms.autorizar`.
- Se crearon pantallas base para los enlaces nuevos:
  - `app/vistas/paginas/apps/tms/operacion.php`;
  - `app/vistas/paginas/apps/tms/costos.php`;
  - `app/vistas/paginas/apps/tms/reportes.php`;
  - `app/vistas/paginas/apps/tms/configuracion.php`.
- Se creo UAT de navegacion:
  - `storage/uat/uat_tms_delivery_sidebar_readonly.php`.
- El JS consume:
  - `/tms/catalogos_erp`;
  - `/tms/servicios_listar_erp`;
  - `/tms/servicio_dryrun_erp`;
  - `/tms/servicio_guardar_erp`.
- El dry-run envia CSRF desde `window.ERP_CSRF_TOKEN`.
- Existe guardado real preparado en backend, pero queda bloqueado hasta aplicar esquema.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\tms\servicios.php`: sin errores.
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\tms\operacion.php`: sin errores.
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\tms\costos.php`: sin errores.
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\tms\reportes.php`: sin errores.
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\tms\configuracion.php`: sin errores.
  - `node --check public\assets\js\custom\apps\tms\servicios.js`: sin errores.
  - `C:\xampp\php\php.exe -l app\vistas\includes\header\sidebar.php`: sin errores.
  - `C:\xampp\php\php.exe storage\uat\uat_tms_delivery_sidebar_readonly.php`: `ok=true`.

## TMS-T006A - UAT read-only previo a permisos y DDL

Estado: completado; no escribio BD.

Objetivo:

Preparar y ejecutar pruebas UAT de lectura para validar el estado real antes de autorizar sincronizacion de permisos o creacion de tablas.

Archivos:

- `storage/uat/uat_tms_delivery_permisos_readonly.php`
- `storage/uat/uat_tms_delivery_schema_readonly.php`
- `storage/uat/uat_tms_delivery_dryrun_readonly.php`

Criterio de cierre:

- [x] Scripts UAT existen en `storage/uat`.
- [x] `C:\xampp\php\php.exe -l` sin errores en los tres scripts.
- [x] UAT permisos confirma tablas de seguridad existentes y permisos `tms.*` pendientes en BD.
- [x] UAT esquema confirma cinco tablas `erp_tms_*` pendientes y genera plan en dry-run.
- [x] UAT dominio confirma catalogos, listado controlado sin esquema y validacion dry-run sin escritura.

Resultado:

- Permisos pendientes en BD:
  - `tms.ver`;
  - `tms.crear`;
  - `tms.programar`;
  - `tms.operar`;
  - `tms.evidencias`;
  - `tms.costos`;
  - `tms.autorizar`;
  - `tms.reportes`.
- Tablas pendientes:
  - `erp_tms_servicios`;
  - `erp_tms_servicios_detalle`;
  - `erp_tms_servicios_costos`;
  - `erp_tms_eventos`;
  - `erp_tms_evidencias`.
- El dry-run de solicitud TMS valida una entrega express por cobrar y bloquea bonificacion sin motivo.
- Tokens separados:
  - permisos: `TMS_PERMISOS_BASE`;
  - DDL TMS: `TMS_DELIVERY_DDL_BASE`.

## TMS-T006B - Scripts apply_authorized para permisos y DDL

Estado: completado como preparacion; no se ejecuto autorizacion ni escritura.

Objetivo:

Dejar preparados scripts operativos con candado para aplicar permisos o DDL TMS solo cuando exista respaldo externo y autorizacion explicita.

Archivos:

- `storage/uat/uat_tms_delivery_permisos_apply_authorized.php`
- `storage/uat/uat_tms_delivery_schema_apply_authorized.php`

Criterio de cierre:

- [x] Script de permisos exige `--autorizar=TMS_PERMISOS_BASE`.
- [x] Script de DDL exige `--autorizar=TMS_DELIVERY_DDL_BASE`.
- [x] Ambos scripts exigen `--respaldo`.
- [x] Ambos scripts bloquean placeholders como `RUTA_O_REFERENCIA`.
- [x] Ambos scripts corren sin token en modo bloqueado y no escriben BD.
- [x] `C:\xampp\php\php.exe -l` sin errores en ambos scripts.

Resultado:

- El script de permisos solo puede crear/actualizar permisos `tms.*` y vincular roles base existentes.
- El script de permisos no crea tablas `erp_tms_*`, no crea servicios, no asigna usuarios y no toca Ventas, Inventario ni Garantias.
- El script de DDL solo puede ejecutar `TmsEsquema::planActualizarTmsDelivery(true)`.
- El script de DDL no sincroniza permisos, no crea servicios y no toca Ventas/POS, Inventario ni Garantias.
- Prueba sin autorizacion:
  - permisos: `modo=bloqueado`;
  - DDL: `modo=bloqueado`.

## TMS-T007 - Guardado real de servicio TMS

Estado: preparado en codigo; pendiente de esquema aplicado para uso real.

Objetivo:

Crear servicio TMS real una vez aplicado el esquema.

Reglas:

- no confirma ventas;
- no cancela ventas;
- no mueve inventario;
- puede recibir referencia de POS/pedido/manual como snapshot;
- registra evento inicial;
- registra costo/cobro logistico si viene informado.

Criterio de cierre:

- [x] Metodo de modelo preparado para crear servicio con folio TMS.
- [x] Endpoint POST preparado: `/tms/servicio_guardar_erp`.
- [x] Guardado bloquea con respuesta controlada si falta esquema.
- [x] UAT confirma bloqueo por esquema pendiente sin escritura.
- [ ] Servicio se crea con folio TMS despues de aplicar DDL.
- [ ] Evento `servicio_creado` queda persistido despues de aplicar DDL.
- [ ] Listado muestra servicios reales despues de aplicar DDL.
- [ ] Auditoria explicita validada contra BD despues de aplicar DDL.

Resultado:

- `app/modelos/TmsDelivery.php` incluye `guardarServicio($datos, $idUsuario = 0)`.
- `app/controladores/Tms.php` incluye `servicio_guardar_erp()`.
- El guardado real crea encabezado, detalle, costo logistico y evento inicial dentro de una transaccion.
- El contrato mantiene separacion: no confirma ventas, no cancela ventas, no decide garantias y no mueve inventario.
- En la BD actual, el UAT devuelve `Esquema TMS pendiente; no se puede crear servicio`.

## TMS-T008 - Operacion basica de estados

Estado: pendiente.

Objetivo:

Permitir cambios de estado logisticos controlados.

Acciones:

- programar;
- asignar responsable;
- marcar lista para salida;
- iniciar ruta;
- entregar;
- marcar no entregada;
- marcar pendiente cliente;
- reprogramar;
- cancelar servicio.

Criterio de cierre:

- cada accion registra evento;
- no hay cambios automaticos sobre ventas/productos/garantias;
- no entrega requiere motivo;
- cancelar servicio requiere motivo.

## TMS-T009 - Evidencias

Estado: pendiente.

Objetivo:

Adjuntar evidencia operativa a un folio TMS.

Tipos:

- foto;
- nota;
- comprobante;
- ubicacion;
- chat_snapshot.

Criterio de cierre:

- evidencia queda ligada a folio TMS;
- cancelar evidencia es baja logica;
- no borrar historial operativo.

## TMS-T010 - Integracion POS como solicitante

Estado: pendiente.

Objetivo:

Permitir que POS cree solicitud TMS, sin que TMS dependa de Ventas para existir ni cambie estatus de venta.

Reglas:

- POS decide cobros de producto y envio en su propio flujo;
- TMS recibe snapshot logistico;
- TMS puede reportar si envio esta pagado, por cobrar o bonificado;
- si TMS no entrega, POS/Ventas no cambia automaticamente.

Criterio de cierre:

- venta puede solicitar entrega;
- folio TMS queda visible como referencia;
- fallo de entrega no cancela venta automaticamente;
- se puede crear nueva solicitud TMS posterior si el negocio decide intentar otra entrega.

## TMS-T011 - Notificaciones

Estado: pendiente.

Objetivo:

Crear notificaciones operativas TMS con `erp_notificaciones`.

Eventos:

- servicio express solicitado;
- servicio sin responsable;
- ventana proxima a vencer;
- entrega no completada;
- paquete pendiente de recoleccion por cliente;
- evidencia faltante;
- servicio bonificado pendiente de autorizacion.

Criterio de cierre:

- notificaciones visibles por permiso;
- marcar leida no resuelve servicio;
- resolver/cancelar servicio cierra notificacion correspondiente.

## TMS-T012 - Reportes basicos

Estado: pendiente.

Objetivo:

Medir el valor real del delivery como fortaleza del negocio.

Reportes:

- servicios por tipo;
- servicios express;
- entregas completas/no entregadas;
- ingresos logisticos;
- bonificaciones;
- servicios pendientes de cliente;
- tiempos de respuesta;
- zonas con mas demanda.

Criterio de cierre:

- reporte read-only;
- no recalcula ventas;
- no modifica servicios.

## Handoff / continuidad

Fecha: 2026-07-24

- Contexto actual: TMS ya tiene documentos, DDL propuesto, modelo de esquema dry-run, modelo de dominio, controlador base y vista minima; no existe esquema aplicado y el guardado real queda bloqueado hasta crear tablas.
- Decision: TMS es modulo independiente, no submodulo de Ventas.
- Cambios recientes: se creo plan rector, plan de tareas, DDL propuesto inicial, `TmsEsquema.php`, `TmsDelivery.php`, `Tms.php`, UI inicial, proteccion en `Core.php`, modulo padre `TMS` en sidebar con grupo `Delivery`, permisos `tms.*` en `SeguridadEsquema.php`, UAT read-only en `storage/uat`, scripts `apply_authorized` bloqueados por token/respaldo y endpoint de guardado protegido por esquema.
- Validacion reciente: UAT read-only confirma permisos y tablas pendientes en BD; dry-run de dominio TMS funciona sin escritura; guardado real queda bloqueado mientras falta esquema.
- Pendiente inmediato: sincronizar permisos `tms.*` en BD con autorizacion o preparar `TMS-T007` solo despues de aplicar DDL TMS.
- No tocar todavia: BD, vistas POS o integraciones reales.
- Siguiente paso recomendado: pedir autorizacion para `TMS_PERMISOS_BASE`; despues probar acceso `/tms/servicios`. El DDL de tablas `erp_tms_*` debe ser otra autorizacion separada.
