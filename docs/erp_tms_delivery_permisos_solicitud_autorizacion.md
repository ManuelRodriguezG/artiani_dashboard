# ERP TMS Delivery - Solicitud de autorizacion de permisos

Documentacion IA: Codex GPT-5  
Fecha base: 2026-07-24  
Estado: solicitud preparada; no ejecutada.

## Objetivo

Sincronizar en BD los permisos `tms.*` definidos en `app/modelos/SeguridadEsquema.php`, sin aplicar DDL TMS, sin crear servicios logisticos y sin modificar ventas, productos, garantias ni inventario.

## Permisos incluidos

- `tms.ver`
- `tms.crear`
- `tms.programar`
- `tms.operar`
- `tms.evidencias`
- `tms.costos`
- `tms.autorizar`
- `tms.reportes`

## Roles afectados

Se usan roles existentes. No se crea rol `delivery` en esta fase.

- `direccion`
- `administrador_erp`
- `ventas`
- `almacen`
- `crm`
- `finanzas_contabilidad`
- `auditor`
- `solo_lectura`

## Alcance permitido

La sincronizacion de seguridad puede:

- insertar permisos `tms.*` faltantes en `sys_permisos`;
- actualizar descripcion/estatus de esos permisos si ya existieran;
- vincular permisos TMS a roles base segun `SeguridadEsquema::permisosPorRolBaseERP()`;
- conservar roles existentes.

## Alcance prohibido

Esta autorizacion no permite:

- aplicar DDL TMS;
- crear tablas `erp_tms_*`;
- crear servicios logisticos reales;
- modificar ventas;
- cancelar ventas;
- mover inventario;
- resolver garantias;
- crear rol `delivery`;
- asignar usuarios a roles;
- modificar contrasenas o sesiones.

## Validacion previa recomendada

```powershell
C:\xampp\php\php.exe -l app\modelos\SeguridadEsquema.php
```

Debe responder sin errores.

## Texto de autorizacion futura

Usar este texto sustituyendo la ruta de respaldo real:

```text
AUTORIZO SINCRONIZAR PERMISOS TMS DELIVERY usando respaldo [RUTA_RESPALDO] con token TMS_PERMISOS_BASE. Entiendo que solo sincroniza permisos tms.* y sus relaciones con roles base existentes, no crea tablas erp_tms_*, no crea servicios logisticos, no modifica ventas, productos, garantias, inventario, caja ni usuarios.
```

## Verificacion posterior esperada

- `sys_permisos` contiene los 8 permisos `tms.*`.
- Los roles base definidos tienen las relaciones esperadas.
- No existen tablas `erp_tms_*` si no se autorizo DDL por separado.
- No se crearon servicios TMS.

## Handoff

Antes de ejecutar, generar respaldo externo real fuera del proyecto y validar que el token sea exactamente `TMS_PERMISOS_BASE`. La aplicacion debe hacerse mediante el flujo de seguridad ya existente, no con SQL manual suelto.
