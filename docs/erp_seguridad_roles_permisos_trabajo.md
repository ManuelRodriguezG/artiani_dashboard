# ERP Seguridad - Asignacion rol-permiso

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-10  
Estado: Cerrado como matriz base conservadora; sincronizacion de base existente pendiente de ejecucion controlada  
Aplica a: permisos asignados por rol

## Proposito

Revisar que cada rol tenga permisos adecuados para operar sin tener poderes innecesarios.

## Archivos

- `app/modelos/SeguridadEsquema.php`
- `app/modelos/SeguridadPermisos.php`
- `app/controladores/Sistema.php`

## Reglas

- El rol `administrador_erp` debe conservar permisos de seguridad.
- Debe existir al menos un administrador activo.
- Auditor debe consultar sin modificar.
- Almacen no debe operar compras.
- Compras no debe recibir inventario.
- Finanzas no debe depender de Compras para pagos/notas.
- Catalogo debe poder resolver productos/fiscales/costos.

## Decision tomada

Se aplica matriz conservadora:

- `compras` conserva `finanzas.ver`, pero no `finanzas.operar`.
- `finanzas_contabilidad` conserva `finanzas.operar`, pero no `compras.aprobar`.
- `direccion` conserva aprobacion/consulta/reportes, pero no `configuracion.administrar`.
- `soporte_sistema` conserva `sistema.soporte`, auditoria y reportes, pero no permisos operativos de compras/finanzas.

## Criterios de terminado

- Cada rol tiene permisos justificados.
- No hay rol operativo con permisos de administrador.
- Separacion Compras/Finanzas/Almacen/Catalogo queda clara.
- Decisiones sensibles quedan aplicadas en `SeguridadEsquema::permisosPorRolBaseERP()`.

## Resultado de auditoria 2026-06-10

Estado: cerrado en semilla/base de codigo con matriz conservadora.

Validaciones correctas:

- `administrador_erp` conserva `seguridad.ver` y `seguridad.administrar`.
- Existe al menos un usuario activo con `administrador_erp` en la base local.
- `auditor` solo tiene permisos de consulta: catalogo, compras, almacen, inventario, ventas, ecommerce, finanzas, auditoria y reportes.
- `almacen` no puede crear/aprobar/cancelar compras ni operar finanzas.
- `inventario` no puede operar compras ni finanzas.
- `solo_lectura` no tiene permisos operativos.
- `catalogo_productos` puede editar catalogo y costos, pero no operar compras ni finanzas.

Cambios aplicados en `SeguridadEsquema::permisosPorRolBaseERP()`:

- `compras`: queda sin `finanzas.operar`; puede consultar saldos/reportes financieros mediante `finanzas.ver`.
- `finanzas_contabilidad`: queda sin `compras.aprobar`; su alcance es financiero, pagos/notas y conciliacion.
- `direccion`: queda sin `configuracion.administrar`; conserva aprobacion de compras y reportes.
- `soporte_sistema`: queda sin `configuracion.administrar`, compras operativas ni `finanzas.operar`; conserva `sistema.soporte`, `seguridad.ver`, `auditoria.ver` y `reportes.ver`.

Nota operativa:

- La semilla actual usa `INSERT IGNORE`, por lo que estos cambios no eliminan automaticamente permisos ya asignados en `sys_roles_permisos`. Para reflejarlo en una base existente se requiere sincronizacion controlada desde UI o script/migracion puntual.

## Prompt puntual

```text
Trabaja solo en ERP > Seguridad > Rol-permiso.
Objetivo: auditar permisosPorRolBaseERP en SeguridadEsquema.php contra el plan maestro.
Detecta permisos excesivos, faltantes y decisiones de negocio pendientes.
No implementes cambios sin confirmar decisiones sensibles.
Criterio: dejar propuesta de asignacion por rol y dudas para el dueno.
```
