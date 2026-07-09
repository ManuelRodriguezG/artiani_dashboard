# ERP - Notificaciones y alertas operativas

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-16  
Estado: Documento rector transversal  
Aplica a: todos los modulos ERP

## Proposito

Definir como debe funcionar el sistema de notificaciones y alertas del ERP para que cada area pueda atender pendientes sin depender de llamadas, mensajes externos o memoria del usuario.

La regla central:

El ERP debe avisar automaticamente cuando una accion de un modulo genera trabajo para otra area o cuando un usuario tiene algo que atender segun sus permisos, rol o responsabilidades.

## Alcance inicial

Por ahora el alcance es solo:

- Notificaciones.
- Alertas.
- Indicadores visuales en navbar o zona global del sistema.
- Bandeja de pendientes por usuario/rol/area.

No incluye por ahora:

- Chat interno.
- Mensajeria entre usuarios.
- Activity logs como flujo de trabajo.
- Notificaciones por correo, WhatsApp o push externas.

## Diferencia entre alerta, notificacion y auditoria

### Alerta

Una alerta es algo que puede bloquear, afectar o requerir atencion operativa.

Ejemplos:

- Producto fisico sin SKU ERP.
- Orden lista para recepcion.
- Pago pendiente de conciliacion.
- Solicitud pendiente de aprobacion.
- XML con conceptos no identificados.
- SKU proveedor sin relacion activa.

### Notificacion

Una notificacion es el aviso visible para el usuario o area responsable.

Ejemplos:

- "Catalogo tiene 3 productos pendientes de alta".
- "Almacen tiene 2 recepciones pendientes".
- "Compras puede continuar una orden porque Catalogo resolvio el SKU".

### Auditoria

La auditoria es historial. Sirve para saber quien hizo que, cuando y sobre que entidad.

No debe usarse como bandeja de trabajo.

## Principio de trabajo entre departamentos

Cuando un modulo detecta un pendiente que pertenece a otra area:

1. El modulo origen registra la incidencia o pendiente.
2. El sistema lo asigna logicamente al area responsable.
3. La notificacion aparece a usuarios con permisos/rol de esa area.
4. El modulo origen puede ver el estatus del pendiente.
5. Cuando el area responsable resuelve, el modulo origen recibe una notificacion de desbloqueo o seguimiento.

Ejemplo:

Compras detecta producto fisico sin SKU ERP.

- Origen: Compras.
- Responsable: Catalogo.
- Notificacion para Catalogo: "Producto pendiente de alta desde orden de compra".
- Seguimiento para Compras: "Catalogo ya tiene el pendiente".
- Desbloqueo para Compras: "Producto vinculado; ya puedes continuar".

## Reglas de permisos

Las notificaciones deben respetar permisos.

Un usuario ve una notificacion si:

- Tiene permiso del modulo responsable.
- Tiene permiso de consulta del origen si necesita abrir la entidad origen.
- Tiene rol operativo relacionado con el area.
- Es usuario asignado directamente como responsable.
- Es administrador o soporte con permisos suficientes.

Ejemplos:

- `catalogo.ver` puede ver pendientes de Catalogo.
- `catalogo.editar` puede resolver pendientes de Catalogo.
- `compras.ver` puede ver pendientes relacionados con sus ordenes/solicitudes.
- `almacen.recibir` puede ver recepciones pendientes.
- `finanzas.operar` puede ver pagos/notas pendientes de accion.

## Navbar y experiencia global

El navbar debe tener un indicador visual de pendientes.

Comportamiento recomendado:

- Mostrar contador total de pendientes visibles para el usuario.
- Mostrar indicador llamativo si hay pendientes criticos o vencidos.
- Al abrir, mostrar tabs simples:
  - Alertas: requieren accion.
  - Notificaciones: cambios relevantes o desbloqueos.
- Cada item debe abrir la entidad relacionada o la bandeja del modulo responsable.

No usar el chat como fuente primaria de trabajo. El chat puede existir despues para conversacion, pero la responsabilidad debe vivir en pendientes/notificaciones con estatus.

## Datos minimos de una alerta/notificacion

Cada alerta operativa debe tener:

- id.
- tipo.
- modulo_origen.
- entidad_origen.
- id_entidad_origen.
- area_responsable.
- permiso_requerido.
- titulo.
- descripcion.
- prioridad.
- estatus.
- url_accion o ruta destino.
- payload_json.
- creado_por.
- asignado_a, si aplica.
- fecha_registro.
- fecha_vencimiento, si aplica.
- fecha_resolucion.

## Estados recomendados

- `pendiente`: existe y requiere atencion.
- `en_revision`: alguien ya la esta atendiendo.
- `bloqueada`: no puede resolverse sin informacion externa.
- `resuelta`: se atendio correctamente.
- `descartada`: no aplica o fue descartada con motivo.
- `cancelada`: el origen dejo de existir o fue cancelado.

## Prioridades recomendadas

- `info`: aviso de seguimiento.
- `normal`: requiere atencion, no bloquea.
- `alta`: bloquea o puede afectar operacion cercana.
- `critica`: bloquea operacion importante o tiene riesgo alto.

## Fuente inicial recomendada

Mientras se consolida una tabla transversal de notificaciones, se puede usar como fuente:

- `erp_catalogo_incidencias_calidad` para incidencias de Catalogo/Proveedores detectadas desde Compras.
- Tablas propias de Almacen para recepciones pendientes.
- Tablas propias de Finanzas para pagos/notas pendientes.
- Tablas propias de Compras para solicitudes/ordenes pendientes.

Despues, si el flujo crece, conviene crear una tabla transversal tipo:

- `erp_notificaciones`
- `erp_alertas_operativas`
- `erp_pendientes_operativos`

La tabla transversal no debe reemplazar la entidad original. Solo debe indexar, notificar y dar seguimiento global.

## Integracion por modulo

Cada modulo nuevo debe responder:

1. Que eventos generan alertas.
2. Que permiso necesita verlas.
3. Que permiso necesita resolverlas.
4. Cual es la entidad origen.
5. Cual es la ruta para atenderlas.
6. Cuando se consideran resueltas.

## Compras como primer caso

Compras debe generar alertas cuando:

- Una orden tiene producto fisico sin SKU ERP.
- Una orden tiene SKU ERP sin relacion proveedor-SKU.
- XML trae conceptos no identificados.
- Hay productos solicitados no incluidos en XML.
- Una orden cambia a enviada y Almacen debe recibir.
- Hay pagos/notas pendientes de Finanzas.

Compras debe ver seguimiento de:

- Pendientes enviados a Catalogo.
- Pendientes enviados a Proveedores.
- Pendientes enviados a Almacen.
- Pendientes enviados a Finanzas.

## Criterio de implementacion

No implementar notificaciones como texto estatico en vistas.

Cada notificacion debe provenir de datos persistentes y respetar permisos. La UI solo debe consultar y renderizar lo que el backend permita.

## Pendientes de implementacion

- [x] Definir si la primera etapa usa solo fuentes existentes o crea tabla `erp_notificaciones`.
- [x] Preparar esquema transversal `erp_notificaciones` y `erp_notificaciones_lecturas` sin ejecutar migracion.
- [x] Preparar endpoints de soporte para auditar/actualizar esquema de notificaciones.
- [x] Definir permiso base `notificaciones.ver`; la resolucion debe seguir usando permisos del modulo responsable.
- [x] Ejecutar esquema de notificaciones con autorizacion (`ejecutar=1`) y respaldo externo previo.
- [x] Sincronizar permisos base con autorizacion.
- [x] Definir endpoints globales iniciales de notificaciones.
- [x] Adaptar navbar para consumir endpoint global.
- [x] Crear bandeja general de alertas.
- [x] Agregar contador por modulo.
- [x] Registrar UAT transversal de notificaciones.

## Avance tecnico 2026-06-16

Documentacion IA: Codex GPT-5

Archivos preparados:

- `app/modelos/NotificacionesEsquema.php`
- `app/controladores/Sistema.php`
- `app/modelos/SeguridadEsquema.php`

Endpoints de soporte preparados:

- `/sistema/esquema_auditar_notificaciones_erp`
- `/sistema/esquema_actualizar_notificaciones_erp`

Reglas:

- Ambos endpoints requieren `sistema.soporte`.
- `esquema_actualizar_notificaciones_erp` solo ejecuta cambios si recibe `ejecutar=1`.
- No se ejecuto migracion de base de datos en este avance.

## Avance tecnico 2026-06-16 - ejecucion autorizada

Documentacion IA: Codex GPT-5

Respaldo previo:

- Base local: `artianilocal`.
- Carpeta externa al proyecto: `C:\Users\aleja\Documents\Respaldos_panel_bd`.
- Archivo: `panel_bd_20260616_212205.sql`.
- Tamano: 52,717,126 bytes.

Ejecucion:

- Se ejecuto `NotificacionesEsquema::planActualizarNotificacionesErp(true)`.
- Se crearon `erp_notificaciones` y `erp_notificaciones_lecturas`.
- Auditoria posterior: esquema de notificaciones completo, sin pendientes.
- Se ejecuto `SeguridadEsquema::planActualizarSeguridad(true)`.
- Se creo/sincronizo permiso `notificaciones.ver`.
- Roles base con `notificaciones.ver`: administrador_erp, almacen, auditor, catalogo_productos, compras, direccion, ecommerce, finanzas_contabilidad, inventario, solo_lectura, soporte_sistema, ventas.

Backend inicial:

- `app/modelos/NotificacionesErp.php`
- `/sistema/notificaciones_resumen_erp`
- `/sistema/notificaciones_listar_erp`
- `/sistema/notificacion_marcar_leida_erp`

Regla aplicada:

- El endpoint solo devuelve notificaciones asignadas directamente al usuario o cuyo `permiso_requerido` exista en sus permisos.
- La resolucion operativa queda para el modulo responsable; este backend solo cubre consulta y lectura inicial.

## Avance tecnico 2026-06-16 - navbar global

Documentacion IA: Codex GPT-5

Archivos:

- `app/vistas/includes/header/header.php`
- `app/vistas/includes/header/sidebar.php`
- `public/assets/js/custom/apps/erp/notificaciones/notificaciones.js`

Implementacion:

- El icono de notificaciones del navbar muestra contador de pendientes visibles por permiso.
- El dropdown consulta `/sistema/notificaciones_resumen_erp` y `/sistema/notificaciones_listar_erp`.
- Las notificaciones se refrescan cada 60 segundos sin bloquear la pantalla actual.
- El boton `Marcar` registra lectura con `/sistema/notificacion_marcar_leida_erp`.
- La lectura no resuelve el pendiente; solo indica que el usuario ya lo vio.
- La resolucion real debe ocurrir en el modulo responsable del pendiente.

Verificacion:

- `node --check public/assets/js/custom/apps/erp/notificaciones/notificaciones.js`
- `C:\xampp\php\php.exe -l app\vistas\includes\header\header.php`
- `C:\xampp\php\php.exe -l app\vistas\includes\header\sidebar.php`

## Avance tecnico 2026-06-16 - bandeja general

Documentacion IA: Codex GPT-5

Archivos:

- `app/controladores/Sistema.php`
- `app/vistas/paginas/apps/erp/notificaciones/listado.php`
- `public/assets/js/custom/apps/erp/notificaciones/listado.js`
- `public/assets/js/custom/apps/erp/notificaciones/notificaciones.js`

Ruta:

- `/sistema/notificaciones`

Implementacion:

- Vista general de notificaciones visibles por permisos.
- Resumen de pendientes, criticas, altas y areas con pendientes.
- Filtros por estatus y area responsable.
- Accion para abrir la URL operativa de la notificacion cuando exista.
- Accion para marcar como leida sin resolver el pendiente.
- Acceso desde el dropdown del navbar mediante `Ver bandeja`.

Verificacion:

- `C:\xampp\php\php.exe -l app\controladores\Sistema.php`
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\notificaciones\listado.php`
- `node --check public/assets/js/custom/apps/erp/notificaciones/listado.js`
- `node --check public/assets/js/custom/apps/erp/notificaciones/notificaciones.js`

## Avance tecnico 2026-06-16 - contador por modulo

Documentacion IA: Codex GPT-5

Archivos:

- `app/modelos/NotificacionesErp.php`
- `app/vistas/paginas/apps/erp/notificaciones/listado.php`
- `public/assets/js/custom/apps/erp/notificaciones/listado.js`

Implementacion:

- El resumen global ahora incluye `por_modulo`.
- La bandeja muestra chips con contador por `modulo_origen`.
- No requiere cambios de esquema; utiliza la tabla `erp_notificaciones` existente.

Verificacion:

- `C:\xampp\php\php.exe -l app\modelos\NotificacionesErp.php`
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\notificaciones\listado.php`
- `node --check public/assets/js/custom/apps/erp/notificaciones/listado.js`

## UAT transversal inicial

Documentacion IA: Codex GPT-5

UAT-NOT-001 - Visibilidad por permisos:

- Usuario con `notificaciones.ver` puede abrir `/sistema/notificaciones`.
- El backend solo debe devolver notificaciones asignadas al usuario o vinculadas a permisos que tenga en sesion.
- Usuario sin `notificaciones.ver` debe recibir 403 por `requerirPermiso`.

UAT-NOT-002 - Navbar:

- El contador del navbar muestra el total de pendientes visibles.
- Si no hay pendientes, el contador permanece oculto.
- Si hay prioridad critica, el indicador usa color de alerta.
- El dropdown muestra maximo 8 pendientes y liga a la bandeja.

UAT-NOT-003 - Bandeja:

- La bandeja lista hasta 50 notificaciones visibles.
- Filtro por estatus y area responsable no debe romper la consulta.
- Los chips por modulo deben coincidir con `modulo_origen`.
- `Marcar leida` solo registra lectura; no resuelve el pendiente.

UAT-NOT-004 - Integracion futura:

- Compras, Catalogo, Proveedores, Almacen y Finanzas deben crear notificaciones operativas por eventos reales.
- Cada notificacion debe incluir `modulo_origen`, `area_responsable`, `permiso_requerido`, `titulo`, `descripcion` y `url_accion` cuando aplique.
- No se debe usar auditoria ni chat como reemplazo de estas alertas.

## Avance tecnico 2026-06-16 - Compras genera alertas reales

Documentacion IA: Codex GPT-5

Archivo:

- `app/modelos/OrdenesCompraErp.php`

Eventos integrados al guardar orden:

- `compra_producto_pendiente_alta`
  - Origen: orden de compra con producto fisico sin SKU ERP.
  - Responsable: `catalogo`.
  - Permiso visible: `catalogo.editar`.
  - URL: `/catalogoerp/configuracion`.
- `compra_sku_sin_relacion_proveedor`
  - Origen: orden de compra con SKU ERP pero sin relacion proveedor-SKU.
  - Responsable: `proveedores`.
  - Permiso visible: `proveedores.matching`.
  - URL: `/proveedor/mostrar_proveedores_erp`.
- `compra_orden_enviada_recepcion_pendiente`
  - Origen: orden cambia/queda en estatus `enviada`.
  - Responsable: `almacen`.
  - Permiso visible: `almacen.recibir`.
  - URL: `/almacen/mostrar_recepciones`.

Reglas:

- Las alertas se guardan en `erp_notificaciones`.
- Se generan dentro del guardado transaccional de la orden.
- Si ya existe una notificacion activa con la misma huella, se actualiza en vez de duplicarse.
- Si una partida pendiente se corrige en una edicion posterior, la notificacion activa correspondiente pasa a `resuelta`.
- Marcar como leida no resuelve la notificacion; solo confirma que el usuario la vio.
- Si el esquema de notificaciones no estuviera disponible, el guardado de la orden no debe fallar por este bloque.

Verificacion:

- `C:\xampp\php\php.exe -l app\modelos\OrdenesCompraErp.php`

UAT-NOT-005 - Compras hacia areas responsables:

- Crear/guardar orden con producto fisico sin SKU ERP y confirmar alerta visible para usuario con `catalogo.editar`.
- Corregir la partida y confirmar que la alerta anterior pasa a `resuelta`.
- Guardar orden con SKU ERP sin relacion proveedor-SKU y confirmar alerta visible para usuario con `proveedores.matching`.
- Enviar orden y confirmar alerta visible para usuario con `almacen.recibir`.
- Reabrir/guardar la misma orden y confirmar que no duplica alertas activas.

## Avance tecnico 2026-06-16 - Almacen cierra alerta de recepcion

Documentacion IA: Codex GPT-5

Archivo:

- `app/modelos/Almacenes.php`

Implementacion:

- Al guardar una recepcion, si el estatus queda `parcial` o `recibida`, se cierra la notificacion `compra_orden_enviada_recepcion_pendiente`.
- El cierre cambia la notificacion a `resuelta` y registra `fecha_resolucion`.
- Si el esquema de notificaciones no estuviera disponible, la recepcion no debe fallar por este bloque.

Verificacion:

- `C:\xampp\php\php.exe -l app\modelos\Almacenes.php`

UAT-NOT-006 - Recepcion cierra alerta:

- Enviar una orden y confirmar alerta para `almacen.recibir`.
- Recibir parcialmente la orden y confirmar que la alerta queda `resuelta`.
- Repetir con recepcion completa y confirmar el mismo comportamiento.

Resultado 2026-06-18:

- Folio usado: `OC-2026-000024` / `REC-OC-24`.
- Antes de recibir: notificacion `13`, tipo `compra_orden_enviada_recepcion_pendiente`, estatus `pendiente`.
- Al guardar recepcion parcial de `2.0000` unidades: la notificacion `13` quedo `resuelta` con fecha resolucion `2026-06-18 00:20:16`.
- Al completar recepcion a `5.0000` unidades: la notificacion se mantuvo `resuelta`.
- Resultado: Aprobado.

## Avance tecnico 2026-06-16 - XML genera alerta de conciliacion

Documentacion IA: Codex GPT-5

Archivo:

- `app/modelos/ComprasXmlErp.php`

Implementacion:

- Al importar XML, si el documento queda con conceptos `sin_coincidencia` o `ambigua`, se crea/actualiza una notificacion `compra_xml_conceptos_revision`.
- La alerta se crea por documento fiscal, no por renglon, para evitar ruido operativo.
- Responsable: `catalogo`.
- Permiso visible: `catalogo.editar`.
- URL: `/catalogoerp/configuracion`.
- Al mover, resolver o descartar conceptos, se reevalua el documento fiscal.
- Si el documento ya no tiene conceptos pendientes, la notificacion queda `resuelta`.

Reglas:

- La alerta contiene `id_orden_compra`, folio de orden, `id_documento_fiscal`, UUID y conteo de conceptos pendientes en `payload_json`.
- Si ya existe una notificacion activa para el mismo documento, se actualiza en vez de duplicarse.
- Si el esquema de notificaciones no estuviera disponible, la importacion/conciliacion XML no debe fallar por este bloque.

Verificacion:

- `C:\xampp\php\php.exe -l app\modelos\ComprasXmlErp.php`

UAT-NOT-007 - XML pendiente de conciliacion:

- Importar XML con conceptos no reconocidos y confirmar una alerta para `catalogo.editar`.
- Resolver o descartar todos los conceptos pendientes y confirmar que la alerta queda `resuelta`.
- Reimportar/operar sobre la misma orden sin duplicar alertas activas del mismo documento.

## Avance tecnico 2026-06-16 - Proveedores conecta incidencias con Catalogo

Documentacion IA: Codex GPT-5

Archivos:

- `app/modelos/NotificacionesErp.php`
- `app/modelos/Proveedores.php`
- `app/modelos/CatalogoErpDatos.php`

Implementacion:

- Se agrego helper reutilizable en `NotificacionesErp` para crear/actualizar notificaciones operativas por huella y resolverlas desde modelos de dominio.
- Al crear una incidencia desde Proveedores para un renglon sin SKU ERP confiable, se crea/actualiza la notificacion `proveedor_producto_pendiente_alta_catalogo`.
- Responsable: `catalogo`.
- Permiso visible: `catalogo.editar`.
- URL: `/catalogoerp`.
- Cuando Catalogo crea el SKU temporal desde la incidencia, la notificacion de Catalogo se marca como `resuelta`.
- En ese mismo flujo se crea/actualiza la notificacion `catalogo_sku_temporal_creado_proveedor_matching`.
- Responsable: `proveedores`.
- Permiso visible: `proveedores.matching`.
- URL: `/proveedor/mostrar_proveedores_erp`.
- Cuando Proveedores aplica la relacion proveedor-SKU, individual o en lote, la notificacion `catalogo_sku_temporal_creado_proveedor_matching` queda `resuelta`.

Reglas:

- Proveedores no crea producto/SKU automaticamente; solo escala la incidencia accionable.
- Catalogo crea producto/SKU en borrador desde la incidencia existente.
- La creacion del SKU temporal no sustituye el matching; Proveedores debe vincular el renglon/proveedor con el SKU ERP.
- El cierre operativo para Compras ocurre despues de vincular proveedor-SKU, no solo por crear el borrador en Catalogo.
- Si falla el registro de notificaciones, la incidencia o el SKU temporal no deben fallar por ese bloque.

Verificacion:

- `C:\xampp\php\php.exe -l app\modelos\NotificacionesErp.php`
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`
- `C:\xampp\php\php.exe -l app\modelos\CatalogoErpDatos.php`

UAT-NOT-008 - Proveedores -> Catalogo -> Proveedores:

- Desde Proveedores, crear incidencia de un renglon sin SKU ERP confiable.
- Confirmar alerta visible para usuario con `catalogo.editar`.
- En Catalogo, crear SKU temporal desde esa incidencia.
- Confirmar que la alerta original queda `resuelta`.
- Confirmar nueva alerta visible para usuario con `proveedores.matching`.
- En Proveedores, hacer matching/vinculacion del renglon contra el SKU temporal.
- Confirmar que el seguimiento de Proveedores queda `resuelta`.
