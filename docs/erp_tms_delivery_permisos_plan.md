# ERP TMS Delivery - Permisos base propuestos

Documentacion IA: Codex GPT-5  
Fecha base: 2026-07-24  
Estado: propuesta documental aplicada en codigo; no sincronizada en BD.

## Proposito

Definir permisos iniciales para operar TMS/Delivery sin mezclar responsabilidades de Ventas, CRM, Almacen, Garantias o Finanzas.

## Regla de dominio

Los permisos TMS autorizan acciones sobre servicios logisticos, no sobre ventas ni productos.

- `tms.crear` no permite vender.
- `tms.operar` no permite cobrar productos.
- `tms.cancelar` cancela el servicio logistico, no la venta.
- `tms.autorizar` autoriza excepciones logisticas, no garantias ni devoluciones comerciales.

## Permisos propuestos

| Permiso | Accion | Descripcion |
| --- | --- | --- |
| `tms.ver` | `ver` | Consultar servicios logisticos, estados, eventos y evidencias visibles. |
| `tms.crear` | `crear` | Crear solicitudes de entrega/recoleccion/traslado desde POS, manual o postventa. |
| `tms.programar` | `programar` | Programar fecha, ventana y responsable de un servicio. |
| `tms.operar` | `operar` | Cambiar estados operativos: lista para salida, en ruta, entregada, no entregada, pendiente cliente o reprogramada. |
| `tms.evidencias` | `evidencias` | Adjuntar o cancelar evidencias logisticas con baja logica. |
| `tms.costos` | `costos` | Ver y registrar costo estimado/real del servicio logistico. |
| `tms.autorizar` | `autorizar` | Autorizar bonificaciones, cortesias o excepciones logisticas sensibles. |
| `tms.reportes` | `reportes` | Consultar reportes e indicadores de servicios logisticos. |

## Roles actuales sugeridos

### `direccion`

Permisos:

- `tms.ver`
- `tms.autorizar`
- `tms.costos`
- `tms.reportes`

Razon:

- Direccion debe ver rentabilidad, bonificaciones, no entregados y excepciones.

### `administrador_erp`

Permisos:

- todos los permisos TMS.

Razon:

- Administra configuracion, soporte operativo y pruebas controladas.

### `ventas`

Permisos:

- `tms.ver`
- `tms.crear`

Razon:

- Ventas puede solicitar entrega y consultar estado, pero no operar ruta ni cancelar logistica por su cuenta.

### `almacen`

Permisos:

- `tms.ver`

Razon:

- Almacen necesita ver servicios para preparar/salida fisica cuando aplique, pero no debe decidir ruta ni cobro logistico.

Pendiente:

- Si Almacen tambien entrega paquetes, se puede agregar `tms.operar` a usuarios concretos o a un rol delivery separado.

### `crm`

Permisos:

- `tms.ver`
- `tms.crear`

Razon:

- CRM puede solicitar servicio manual ligado a cliente/direccion, sin cobrar productos ni operar ruta.

### `finanzas_contabilidad`

Permisos:

- `tms.ver`
- `tms.costos`
- `tms.reportes`

Razon:

- Finanzas necesita medir cobros, costos, bonificaciones y diferencias logisticas.

### `auditor`

Permisos:

- `tms.ver`
- `tms.reportes`

Razon:

- Auditoria consulta historial y reportes, sin operar servicios.

### `solo_lectura`

Permisos:

- `tms.ver`

Razon:

- Consulta general sin escritura.

## Decision sobre rol `delivery`

No crear rol `delivery` todavia como requisito obligatorio.

Recomendacion:

- Fase 1 puede operar con permisos asignados a roles existentes.
- Crear rol `delivery` cuando el negocio tenga usuarios/repartidores que entren al sistema y necesiten operar rutas sin acceso a ventas, CRM amplio, caja ni garantias.

Rol futuro sugerido:

- `delivery`: `tms.ver`, `tms.operar`, `tms.evidencias`.

No deberia tener por defecto:

- `ventas.operar`;
- `ventas.ver` amplio;
- `crm.editar`;
- `garantias.reclamos.resolver`;
- `finanzas.operar`.

## Aplicacion futura

Avance en codigo 2026-07-24:

- Permisos `tms.*` agregados a `SeguridadEsquema::permisosBaseERP()`.
- Asignaciones TMS agregadas a roles base existentes en `SeguridadEsquema::permisosPorRolBaseERP()`.
- No se creo rol `delivery`.
- No se ejecuto sincronizacion de seguridad en BD.

Cuando se autorice sincronizar BD:

1. Ejecutar plan de seguridad con respaldo/autorizacion segun metodologia del proyecto.
2. Verificar que existan permisos `tms.*` en `sys_permisos`.
3. Verificar asignaciones de roles en `sys_roles_permisos`.
4. Si despues se decide crear rol `delivery`, agregarlo a `rolesBaseERP()` en otra tarea.

## Criterio de cierre

- Esta propuesta queda lista para revision.
- Codigo de seguridad actualizado sin errores de sintaxis.
- No se ejecuta sincronizacion de permisos en BD.
- La siguiente tarea tecnica debe preparar autorizacion/sincronizacion de permisos o continuar UI sabiendo que el modulo aun puede quedar bloqueado por permisos no aplicados.
