# ERP Seguridad - Roles base

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-10  
Estado: Cerrado para roles base; la matriz exacta de permisos se cierra en Paso 4  
Aplica a: roles reales del negocio

## Proposito

Definir roles que representen personas reales o futuras del negocio. Los roles no deben ser nombres tecnicos sin sentido operativo; deben permitir delegar trabajo con claridad.

## Archivo principal

- `app/modelos/SeguridadEsquema.php`

## Roles actuales a revisar

- `direccion`
- `administrador_erp`
- `compras`
- `almacen`
- `inventario`
- `ventas`
- `ecommerce`
- `catalogo_productos`
- `finanzas_contabilidad`
- `auditor`
- `solo_lectura`
- `soporte_sistema`

## Reglas por rol

- `direccion`: consulta amplia, aprobaciones y reportes; no necesariamente opera todo.
- `administrador_erp`: configura usuarios, roles, permisos y catalogos maestros.
- `compras`: solicitudes, ordenes, proveedores y seguimiento de compras.
- `almacen`: recepcion, ubicaciones, incidencias fisicas.
- `inventario`: existencias, ajustes, conteos y traspasos.
- `catalogo_productos`: productos, SKUs, fiscales, unidades, reglas y costos autorizados.
- `finanzas_contabilidad`: pagos, notas, saldos y conciliacion.
- `auditor`: consulta trazabilidad sin operar.
- `soporte_sistema`: diagnostico tecnico y soporte.
- `solo_lectura`: consulta limitada.

## Decisiones de alcance

- `compras` representa operacion de proveedores, solicitudes, ordenes y seguimiento; pagos/notas quedan como alcance natural de `finanzas_contabilidad` salvo permiso puntual definido en Paso 4.
- `direccion` representa consulta ejecutiva, aprobaciones y reportes; no administra usuarios por definicion del rol.
- `ecommerce` se conserva como rol separado para no mezclar sincronizacion/canal digital con Catalogo o Ventas.
- `soporte_sistema` representa diagnostico tecnico, auditoria de esquema y soporte; los permisos de ejecucion tecnica se validan por `sistema.soporte` en Paso 4 y Paso 5.

## Criterios de terminado

- Cada rol tiene descripcion clara.
- No hay roles redundantes.
- Cada rol se puede explicar a una persona del negocio.
- Roles base cerrados en este documento y alineados en `SeguridadEsquema::rolesBaseERP()`.

## Resultado de auditoria 2026-06-10

Estado: cerrado como lista base de roles.

Roles observados en base local:

- `administrador_erp`: activo, 26 permisos, asignado a usuario 1.
- `soporte_sistema`: activo, 13 permisos, asignado a usuario 1.
- `compras`: activo, 13 permisos, sin usuarios activos.
- `almacen`: activo, 8 permisos, sin usuarios activos.
- `inventario`: activo, 9 permisos, sin usuarios activos.
- `ventas`: activo, 6 permisos, sin usuarios activos.
- `catalogo_productos`: activo, 6 permisos, sin usuarios activos.
- `finanzas_contabilidad`: activo, 9 permisos, sin usuarios activos.
- `direccion`: activo, 13 permisos, sin usuarios activos.
- `auditor`: activo, 9 permisos, sin usuarios activos.
- `solo_lectura`: activo, 8 permisos, sin usuarios activos.
- `ecommerce`: activo, 7 permisos, sin usuarios activos.

Hallazgos:

- La lista base es coherente para delegar el ERP por areas reales.
- No se detectan nombres duplicados ni roles claramente repetidos.
- `compras` queda definido sin pagos/notas en la descripcion del rol; el alcance financiero real se decide en la matriz rol-permiso.
- `ecommerce` se mantiene como rol futuro/separado.
- `direccion` queda planteado como consulta/aprobacion/reportes, no como administrador de usuarios.
- `soporte_sistema` queda como rol tecnico; ejecucion de esquemas debe depender de `sistema.soporte`.

Decisiones trasladadas a Paso 4:

- Si `compras` conserva `finanzas.operar` o solo `finanzas.ver`.
- Si `finanzas_contabilidad` conserva `compras.aprobar` o solo opera pagos/notas.
- Si `soporte_sistema` conserva permisos operativos o queda limitado a soporte tecnico, auditoria y esquema.
- Si `direccion` conserva solo aprobaciones/reportes o tambien alguna administracion operativa excepcional.

## Prompt puntual

```text
Trabaja solo en ERP > Seguridad > Roles base.
Objetivo: revisar rolesBaseERP en SeguridadEsquema.php contra docs/erp_seguridad_roles_base_trabajo.md.
No implementes cambios si hay dudas de negocio; marca decisiones pendientes.
Criterio: dejar lista final de roles, descripcion y dudas que el dueno debe confirmar.
```
