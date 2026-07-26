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
- Se crearon JS read-only para pantallas internas:
  - `public/assets/js/custom/apps/tms/operacion.js`;
  - `public/assets/js/custom/apps/tms/costos.js`;
  - `public/assets/js/custom/apps/tms/reportes.js`;
  - `public/assets/js/custom/apps/tms/configuracion.js`.
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
  - `node --check public\assets\js\custom\apps\tms\operacion.js`: sin errores.
  - `node --check public\assets\js\custom\apps\tms\costos.js`: sin errores.
  - `node --check public\assets\js\custom\apps\tms\configuracion.js`: sin errores.
  - `C:\xampp\php\php.exe -l app\vistas\includes\header\sidebar.php`: sin errores.
  - `C:\xampp\php\php.exe storage\uat\uat_tms_delivery_sidebar_readonly.php`: `ok=true`.

Resultado ampliado 2026-07-24:

- `Operacion y rutas` consulta cola TMS y KPIs operativos en modo read-only.
- `Costos logisticos` consulta resumen financiero logistico en modo read-only.
- `Configuracion delivery` muestra catalogos, contrato de dominio y acciones disponibles en modo read-only.
- Ninguna pantalla interna modifica ventas, garantias, inventario o servicios mientras falta esquema.

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

Estado: preparado en codigo; pendiente de esquema aplicado para uso real.

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

- [x] Metodo de modelo preparado: `aplicarAccionServicio($datos, $idUsuario = 0)`.
- [x] Endpoint POST preparado: `/tms/servicio_accion_erp`.
- [x] Permiso dinamico por accion:
  - `programar`, `reprogramar`, `asignar_responsable`: `tms.programar`;
  - resto de acciones operativas: `tms.operar`.
- [x] Si falta esquema, devuelve bloqueo controlado sin escribir BD.
- [x] UAT confirma bloqueo por esquema pendiente.
- [x] No hay cambios automaticos sobre ventas/productos/garantias.
- [x] No entrega requiere motivo.
- [x] Cancelar servicio requiere motivo.
- [ ] Cada accion registra evento persistido despues de aplicar DDL.
- [ ] Acciones operativas validadas con servicios reales despues de aplicar DDL.

Resultado:

- Acciones preparadas:
  - `programar`;
  - `asignar_responsable`;
  - `marcar_lista_salida`;
  - `iniciar_ruta`;
  - `entregar`;
  - `no_entregada`;
  - `pendiente_cliente`;
  - `reprogramar`;
  - `cancelar_servicio`.
- El modelo actualiza solo columnas de `erp_tms_servicios` y registra evento en `erp_tms_eventos` cuando el esquema exista.
- En la BD actual, el UAT devuelve `Esquema TMS pendiente; no se puede operar servicio`.

## TMS-T009 - Evidencias

Estado: preparado en codigo; pendiente de esquema aplicado para uso real.

Objetivo:

Adjuntar evidencia operativa a un folio TMS.

Tipos:

- foto;
- nota;
- comprobante;
- ubicacion;
- chat_snapshot.

Criterio de cierre:

- [x] Metodo read-only preparado: `listarEvidencias($idServicio)`.
- [x] Metodo de registro preparado: `registrarEvidencia($datos, $idUsuario = 0)`.
- [x] Metodo de baja logica preparado: `cancelarEvidencia($datos, $idUsuario = 0)`.
- [x] Endpoints preparados:
  - `/tms/evidencias_listar_erp`;
  - `/tms/evidencia_registrar_erp`;
  - `/tms/evidencia_cancelar_erp`.
- [x] Si falta esquema, listar devuelve estado vacio controlado.
- [x] Si falta esquema, registrar/cancelar devuelve bloqueo controlado sin escribir BD.
- [x] Cancelar evidencia queda como baja logica.
- [x] No se borra historial operativo.
- [ ] Evidencia queda ligada a folio TMS despues de aplicar DDL.
- [ ] Cancelacion queda persistida despues de aplicar DDL.

Resultado:

- Tipos soportados en contrato inicial:
  - `foto`;
  - `firma`;
  - `nota`;
  - `comprobante`;
  - `ubicacion`;
  - `chat_snapshot`.
- En esta fase no se implementa subida fisica de archivos; se registra metadata/ruta si existe.
- El registro de evidencia crea evento `evidencia_registrada` cuando el esquema exista.
- La cancelacion crea evento `evidencia_cancelada` cuando el esquema exista.
- UAT confirma:
  - `evidencias_listado`: lista vacia controlada por esquema pendiente;
  - `evidencia_registro_bloqueado_por_schema`;
  - `evidencia_cancelacion_bloqueada_por_schema`.

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

Estado: preparado en codigo; pendiente de esquema aplicado para datos reales.

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

- [x] Metodo read-only preparado: `resumenReportes($filtros = array())`.
- [x] Endpoint preparado: `/tms/reportes_resumen_erp`.
- [x] Vista `app/vistas/paginas/apps/tms/reportes.php` conectada al endpoint.
- [x] JS creado: `public/assets/js/custom/apps/tms/reportes.js`.
- [x] Si falta esquema, devuelve KPIs en cero y `schema_pendiente=true`.
- [x] Reporte read-only.
- [x] No recalcula ventas.
- [x] No modifica servicios.
- [ ] Reporte devuelve datos reales despues de aplicar DDL.

Resultado:

- KPIs preparados:
  - servicios totales;
  - completas;
  - express;
  - no entregadas;
  - pendiente cliente;
  - bonificadas;
  - ingresos logisticos;
  - costo real;
  - monto bonificado;
  - tiempo promedio en minutos.
- Agrupaciones preparadas:
  - por tipo;
  - por resultado logistico;
  - por zona.
- UAT confirma `reportes_resumen` con ceros y `schema_pendiente=true`.

## TMS-T013 - Go/No-Go preactivacion

Estado: completado read-only.

Objetivo:

Consolidar en un solo semaforo si TMS Delivery esta listo a nivel codigo antes de solicitar activaciones en BD.

Criterio de cierre:

- [x] Script read-only creado: `storage/uat/uat_tms_delivery_go_nogo_readonly.php`.
- [x] No aplica permisos.
- [x] No ejecuta DDL.
- [x] No crea servicios.
- [x] Valida controlador, modelo, esquema, vistas, JS, sidebar, permisos declarados, plan DDL, dry-run y reportes.
- [x] Salida resumida por defecto.
- [x] Salida detallada disponible con `--detalle=1`.

Resultado 2026-07-24:

- `ok=true`.
- Estado: `go_con_activaciones_pendientes`.
- Checks: 45/45 correctos.
- Permisos TMS pendientes en BD: 8.
- Esquema TMS pendiente: si.
- Siguiente paso: generar respaldo externo y aplicar primero permisos TMS con autorizacion `TMS_PERMISOS_BASE`; DDL TMS queda en autorizacion separada `TMS_DELIVERY_DDL_BASE`.

## TMS-T014 - Preflight de activacion controlada

Estado: completado read-only.

Objetivo:

Preparar una salida operativa que indique orden, comandos y respaldos esperados para activar TMS sin mezclar permisos y DDL.

Criterio de cierre:

- [x] Script read-only creado: `storage/uat/uat_tms_delivery_preactivacion_readonly.php`.
- [x] No crea respaldo.
- [x] No sincroniza permisos.
- [x] No ejecuta DDL.
- [x] No crea servicios.
- [x] Propone comandos separados para permisos, esquema y UAT manual.
- [x] Reafirma reglas de dominio: no ventas, no garantias, no inventario.

Resultado 2026-07-24:

- `ok=true`.
- Estado: `preactivacion_preparada`.
- Checks: 9/9 correctos.
- Orden recomendado:
  - respaldo permisos;
  - aplicar permisos TMS;
  - validar menu/acceso TMS;
  - respaldo schema;
  - aplicar DDL TMS;
  - validar schema TMS;
  - respaldo UAT manual;
  - ejecutar UAT manual TMS;
  - validar UI TMS con datos de prueba.

## TMS-T015 - Verificacion post-permisos

Estado: completado read-only; pendiente de que se aplique `TMS_PERMISOS_BASE` para pasar a verde.

Objetivo:

Validar que despues de sincronizar permisos TMS existan los ocho permisos, relaciones esperadas por rol y sidebar listo.

Criterio de cierre:

- [x] Script read-only creado: `storage/uat/uat_tms_delivery_permisos_postapply_readonly.php`.
- [x] No crea permisos.
- [x] No asigna roles.
- [x] No ejecuta DDL.
- [x] Salida resumida por defecto.
- [x] Salida detallada disponible con `--detalle=1`.
- [x] Detecta menu TMS listo aunque BD de permisos siga pendiente.

Resultado 2026-07-24:

- Estado actual: `permisos_tms_pendientes`.
- Permisos TMS en BD: 0/8.
- Roles esperados con permisos TMS: 0/8.
- Menu TMS: listo.
- Este resultado es esperado antes de aplicar `TMS_PERMISOS_BASE`.

## TMS-T016 - Verificacion post-DDL

Estado: completado read-only; pendiente de que se aplique `TMS_DELIVERY_DDL_BASE` para pasar a verde.

Objetivo:

Validar que despues de crear tablas TMS existan las cinco tablas requeridas, no queden pendientes de columnas y los endpoints read-only cambien a modo con esquema disponible.

Criterio de cierre:

- [x] Script read-only creado: `storage/uat/uat_tms_delivery_schema_postapply_readonly.php`.
- [x] No crea servicios.
- [x] No inserta, actualiza ni borra registros.
- [x] No toca Ventas.
- [x] No decide garantias.
- [x] No mueve inventario.
- [x] Ejecuta `servicioDryRun` como validacion sin escritura.
- [x] Revisa listado y reportes sin modificar datos.

Resultado 2026-07-24:

- Estado actual: `schema_tms_pendiente`.
- Tablas esperadas: 5.
- Pendientes schema actuales: 5.
- Listado/reportes indican `schema_pendiente=true`.
- Dry-run valido: si.
- Este resultado es esperado antes de aplicar `TMS_DELIVERY_DDL_BASE`.

## TMS-T017 - UAT autorizado de servicio manual

Estado: preparado; bloqueado por defecto.

Objetivo:

Ejecutar, despues de permisos y DDL, una prueba controlada de ciclo manual TMS sin integrar POS/Ventas.

Criterio de cierre:

- [x] Script creado: `storage/uat/uat_tms_delivery_servicio_manual_apply_authorized.php`.
- [x] Bloqueado sin token `TMS_UAT_SERVICIO_MANUAL`.
- [x] Requiere respaldo externo valido.
- [x] No sincroniza permisos.
- [x] No ejecuta DDL.
- [x] No toca Ventas.
- [x] No decide garantias.
- [x] No mueve inventario.
- [x] En ejecucion autorizada futura crea solo servicio TMS de prueba, eventos y evidencia textual.

Resultado 2026-07-24:

- `php -l`: sin errores.
- Ejecucion sin token/respaldo: bloqueada correctamente.
- Solicitud de autorizacion creada:
  - `docs/erp_tms_delivery_uat_manual_solicitud_autorizacion.md`.
- Alcance declarado:
  - crea servicio TMS de prueba;
  - crea eventos TMS;
  - crea evidencia TMS;
  - no toca ventas/POS/inventario/garantias.

## TMS-T018 - Preflight read-only de reversa DDL

Estado: completado read-only.

Objetivo:

Tener un diagnostico seguro antes de cualquier reversa futura de DDL TMS, verificando si existen tablas y si tienen filas.

Criterio de cierre:

- [x] Script read-only creado: `storage/uat/uat_tms_delivery_reversa_preflight_readonly.php`.
- [x] No ejecuta `DROP`.
- [x] No borra datos.
- [x] No toca Ventas.
- [x] No decide garantias.
- [x] No mueve inventario.
- [x] Define orden tecnico de borrado solo como informacion.
- [x] Si MySQL no esta disponible, reporta `sin_conexion_mysql`.

Resultado inicial 2026-07-25:

- `php -l`: sin errores.
- Ejecucion read-only: `sin_conexion_mysql`.
- Log local MariaDB reporta `Aria recovery failed` y `Could not open mysql.plugin table`.
- No se ejecuto reparacion de MariaDB ni cambios sobre BD.
- No existe token activo para reversa DDL.

Resultado actualizado 2026-07-25:

- MySQL/MariaDB levantado.
- Ejecucion read-only: `reversa_no_aplica_schema_pendiente`.
- Conexion MySQL: si.
- Tablas TMS existentes: 0/5.
- Total filas TMS: 0.
- Reversa tecnicamente viable: no aplica porque el esquema TMS aun no existe.

## TMS-T019 - Checklist de activacion

Estado: completado read-only.

Objetivo:

Consolidar el estado de permisos, esquema y UAT manual para definir el siguiente token permitido sin saltarse validaciones.

Criterio de cierre:

- [x] Script read-only creado: `storage/uat/uat_tms_delivery_activacion_checklist_readonly.php`.
- [x] No crea respaldos.
- [x] No sincroniza permisos.
- [x] No ejecuta DDL.
- [x] No crea servicios.
- [x] Bloquea saltar a DDL si permisos no estan aplicados.
- [x] Bloquea UAT manual si permisos y esquema no estan listos.

Resultado 2026-07-25:

- Estado: `listo_para_permisos_tms`.
- Conexion MySQL: si.
- Permisos pendientes: 8.
- Schema pendiente: si.
- Tablas TMS existentes: 0.
- Siguiente token permitido: `TMS_PERMISOS_BASE`.
- DDL y UAT manual quedan bloqueados por dependencias.

## TMS-T020 - Respaldo previo a permisos TMS

Estado: completado; no aplica permisos ni DDL.

Objetivo:

Generar respaldo externo antes de sincronizar permisos `tms.*` en BD.

Resultado 2026-07-25:

- Respaldo creado:
  - `C:\xampp\panel_db_backups\artianilocal_panel_20260725_antes_tms_permisos.sql`.
- Tamano: 32809505 bytes.
- Checklist posterior sigue en `listo_para_permisos_tms`.
- Siguiente token permitido: `TMS_PERMISOS_BASE`.
- No se sincronizaron permisos.
- No se ejecuto DDL.
- No se crearon servicios TMS.

## TMS-T021 - Aplicacion autorizada de permisos TMS

Estado: completado.

Objetivo:

Sincronizar los permisos `tms.*` y relaciones esperadas sobre roles base, usando respaldo externo y token autorizado.

Autorizacion usada:

```text
AUTORIZO SINCRONIZAR PERMISOS TMS DELIVERY usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_20260725_antes_tms_permisos.sql con token TMS_PERMISOS_BASE.
```

Resultado 2026-07-25:

- Script ejecutado: `storage/uat/uat_tms_delivery_permisos_apply_authorized.php`.
- Permisos sincronizados: 8.
- Roles detectados: 8.
- Relaciones intentadas: 23.
- No asigna usuarios directo.
- No crea tablas TMS.
- No crea servicios TMS.
- No toca Ventas, Inventario ni Garantias.

Verificacion posterior:

- `storage/uat/uat_tms_delivery_permisos_postapply_readonly.php`: `permisos_tms_listos`.
- Permisos: 8/8.
- Roles: 8/8.
- Menu: listo.
- Checklist de activacion: `listo_para_schema_tms`.
- Siguiente token permitido: `TMS_DELIVERY_DDL_BASE`.

## TMS-T022 - Respaldo previo a DDL TMS

Estado: completado; no aplica DDL.

Objetivo:

Generar respaldo externo antes de crear tablas `erp_tms_*`.

Resultado 2026-07-25:

- Respaldo creado:
  - `C:\xampp\panel_db_backups\artianilocal_panel_20260725_antes_tms_delivery_schema.sql`.
- Tamano: 32811490 bytes.
- Script DDL probado con respaldo real pero sin token: bloqueado correctamente.
- Validacion de respaldo en script DDL: `ok=true`.
- No se ejecuto DDL.
- No se crearon servicios TMS.
- No se tocaron Ventas, POS, Inventario ni Garantias.

## TMS-T023 - Aplicacion autorizada DDL TMS

Estado: completado.

Objetivo:

Crear las tablas base `erp_tms_*` para operar servicios logisticos independientes.

Autorizacion usada:

```text
AUTORIZO CREAR ESQUEMA TMS DELIVERY usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_20260725_antes_tms_delivery_schema.sql con token TMS_DELIVERY_DDL_BASE.
```

Resultado 2026-07-25:

- Script ejecutado: `storage/uat/uat_tms_delivery_schema_apply_authorized.php`.
- Tablas creadas: 5.
- Tablas:
  - `erp_tms_servicios`;
  - `erp_tms_servicios_detalle`;
  - `erp_tms_servicios_costos`;
  - `erp_tms_eventos`;
  - `erp_tms_evidencias`.
- No crea servicios TMS.
- No toca Ventas.
- No toca POS.
- No toca Inventario.
- No toca Garantias.
- No sincroniza permisos.

Verificacion posterior:

- `storage/uat/uat_tms_delivery_schema_postapply_readonly.php`: `schema_tms_listo`.
- Pendientes schema: 0.
- Listado/reportes ya no indican `schema_pendiente`.
- Dry-run valido: si.
- Checklist de activacion: `listo_para_uat_manual_tms`.
- Siguiente token permitido: `TMS_UAT_SERVICIO_MANUAL`.
- Preflight reversa: tablas TMS 5/5, filas 0; reversa tecnicamente viable solo con autorizacion futura separada.

## TMS-T024 - Respaldo previo a UAT manual TMS

Estado: completado; no ejecuta UAT.

Objetivo:

Generar respaldo externo antes de crear el primer servicio TMS de prueba.

Resultado 2026-07-25:

- Respaldo creado:
  - `C:\xampp\panel_db_backups\artianilocal_panel_20260725_antes_tms_uat_manual.sql`.
- Tamano: 32820083 bytes.
- Script UAT manual probado con respaldo real pero sin token: bloqueado correctamente.
- Validacion de respaldo en script UAT: `ok=true`.
- No se creo servicio TMS.
- No se tocaron Ventas, POS, Inventario ni Garantias.
- Siguiente token permitido: `TMS_UAT_SERVICIO_MANUAL`.

## TMS-T025 - Ejecucion autorizada UAT manual TMS

Estado: completado.

Objetivo:

Crear y cerrar un servicio TMS de prueba, validando eventos, costos y evidencia sin integrar POS/Ventas.

Autorizacion usada:

```text
AUTORIZO EJECUTAR UAT MANUAL TMS DELIVERY usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_20260725_antes_tms_uat_manual.sql con token TMS_UAT_SERVICIO_MANUAL.
```

Resultado 2026-07-25:

- Script ejecutado: `storage/uat/uat_tms_delivery_servicio_manual_apply_authorized.php`.
- Servicio creado: `id_tms_servicio=1`.
- Folio: `TMS-20260725-212914-255`.
- Pasos ejecutados: 5.
- Estado final: `entregada`.
- Resultado logistico final: `completa`.
- Evidencia registrada: `id_tms_evidencia=1`, tipo `nota`.
- No toca Ventas.
- No toca POS.
- No toca Inventario.
- No toca Garantias.

Verificacion posterior:

- Checklist de activacion: `activacion_base_completa`.
- Servicios TMS: 1.
- Reportes TMS:
  - total: 1;
  - completas: 1;
  - express: 1;
  - ingresos logisticos: 75.
- Preflight reversa: `reversa_bloqueada_hay_datos_tms`.
- Filas TMS totales: 9.
- Reversa DDL ya no debe ejecutarse porque existen datos TMS.

## Handoff / continuidad

Fecha: 2026-07-24

- Contexto actual: TMS ya tiene documentos, permisos aplicados en BD, esquema TMS aplicado, modelo de dominio, controlador base, vistas base, JS, sidebar, reportes y un servicio TMS de prueba cerrado correctamente.
- Decision: TMS es modulo independiente, no submodulo de Ventas.
- Cambios recientes: se creo plan rector, plan de tareas, DDL propuesto inicial, `TmsEsquema.php`, `TmsDelivery.php`, `Tms.php`, UI inicial, proteccion en `Core.php`, modulo padre `TMS` en sidebar con grupo `Delivery`, permisos `tms.*` en `SeguridadEsquema.php`, permisos TMS aplicados en BD, esquema TMS aplicado en BD, UAT manual ejecutado, UAT read-only en `storage/uat`, endpoint de guardado real, endpoint de operacion de estados, endpoints de evidencias y reportes read-only.
- Validacion reciente: permisos TMS aplicados con respaldo externo y token `TMS_PERMISOS_BASE`; DDL TMS aplicado con respaldo externo y token `TMS_DELIVERY_DDL_BASE`; UAT manual ejecutado con token `TMS_UAT_SERVICIO_MANUAL`; post-permisos confirma 8/8 permisos, 8/8 roles y menu listo; post-DDL confirma `schema_tms_listo`; UAT go/no-go confirma 47/47 checks correctos; checklist de activacion confirma `activacion_base_completa`; preflight de reversa bloquea reversa porque ya hay datos TMS.
- Pendiente inmediato: validar UI TMS con el servicio de prueba y preparar integracion POS/Ventas en tarea separada.
- No tocar todavia: BD, vistas POS o integraciones reales.
- Siguiente paso recomendado: validar UI TMS en navegador y planear integracion POS/Ventas como solicitante, sin mezclar reglas de venta con servicio logistico.
