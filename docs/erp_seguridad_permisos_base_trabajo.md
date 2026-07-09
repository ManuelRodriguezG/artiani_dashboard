# ERP Seguridad - Permisos base

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-10  
Estado: Subdocumento puntual de `erp_seguridad_roles_avance.md`  
Aplica a: permisos por modulo y accion

## Proposito

Definir permisos base por modulo, evitando permisos demasiado amplios que luego impidan delegar tareas con seguridad.

## Archivo principal

- `app/modelos/SeguridadEsquema.php`

## Modulos actuales

- seguridad
- configuracion
- catalogo
- compras
- almacen
- inventario
- ventas
- ecommerce
- finanzas
- auditoria
- reportes
- sistema

## Reglas de permisos

- Cada permiso debe tener `modulo`, `accion`, `permiso` y `descripcion`.
- Permisos sensibles deben estar separados: ver, crear, editar, aprobar, cancelar, operar, administrar.
- No usar un solo permiso para todo un modulo si hay acciones criticas.
- Cada endpoint sensible debe mapearse a un permiso.

## Permisos criticos esperados

Seguridad:

- `seguridad.ver`
- `seguridad.administrar`

Catalogo:

- `catalogo.ver`
- `catalogo.editar`
- `catalogo.costos`

Compras:

- `compras.ver`
- `compras.crear`
- `compras.editar`
- `compras.aprobar`
- `compras.cancelar`
- `compras.adjuntos`

Almacen:

- `almacen.ver`
- `almacen.recibir`
- `almacen.ubicaciones`

Inventario:

- `inventario.ver`
- `inventario.ajustar`
- `inventario.traspasar`
- `inventario.conteo`

Finanzas:

- `finanzas.ver`
- `finanzas.operar`

Auditoria:

- `auditoria.ver`

Sistema:

- `sistema.soporte`

## Criterios de terminado

- Permisos base cubren cada modulo del plan maestro.
- Acciones sensibles estan separadas.
- No faltan permisos para Compras/Catalogo/Almacen/Finanzas.
- No hay permisos duplicados o confusos.

## Resultado de auditoria 2026-06-10

Estado: terminado para permisos base.

Evidencia local:

- Total de permisos activos: 26.
- Modulos cubiertos: `seguridad`, `configuracion`, `catalogo`, `compras`, `almacen`, `inventario`, `ventas`, `ecommerce`, `finanzas`, `auditoria`, `reportes`, `sistema`.
- Compras tiene permisos finos para consultar, crear, editar, aprobar, cancelar y adjuntar.
- Finanzas tiene permisos separados para consultar y operar.
- Almacen separa consulta, recepcion y ubicaciones.
- Inventario separa consulta, ajuste, traspaso y conteo.
- Auditoria y sistema tienen permisos propios: `auditoria.ver` y `sistema.soporte`.
- No se detectaron permisos duplicados.
- Los permisos observados en llamadas `requerirPermiso()` existen en `sys_permisos`.

Riesgos y decisiones:

- `catalogo.editar` cubre varias acciones de configuracion/organizacion del catalogo. Si el negocio quiere separar alta/edicion de productos contra configuracion maestra, crear y mapear `catalogo.configurar`.
- La asignacion de `finanzas.operar` al rol `compras` no es un problema de permiso base, sino decision de rol-permiso del Paso 4.
- La revision de endpoints sin permiso puntual queda para Paso 5.

## Prompt puntual

```text
Trabaja solo en ERP > Seguridad > Permisos base.
Objetivo: auditar permisosBaseERP en SeguridadEsquema.php y compararlo contra endpoints reales.
No cambies controladores todavia.
Criterio: listar permisos suficientes, faltantes, redundantes y riesgos de permisos demasiado amplios.
```
