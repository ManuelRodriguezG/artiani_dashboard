# ERP Compras - Solicitudes: listado

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-09  
Estado: Subdocumento puntual de `erp_compras_solicitudes_trabajo.md`  
Aplica a: listado, filtros y acciones de solicitudes

## Proposito

Definir el listado operativo de solicitudes para priorizar trabajo, aprobaciones y solicitudes listas para generar orden.

## Archivos de trabajo

- `app/vistas/paginas/apps/erp/compras/solicitudes/listado.php`
- `public/assets/js/custom/apps/erp/compras/solicitudes/listado.js`
- `app/controladores/Compra.php`
- `app/modelos/SolicitudesCompraErp.php`

## Filtros esperados

- Estatus.
- Fecha.
- Solicitante.
- Prioridad.
- Proveedor sugerido.
- Almacen destino.
- Con/sin orden generada.
- Productos pendientes/nuevos.

## Columnas esperadas

- Folio/id.
- Solicitante.
- Prioridad.
- Estatus.
- Fecha requerida.
- Proveedor sugerido.
- Almacen destino.
- Total estimado si aplica.
- Orden relacionada.
- Acciones por permiso.

## Acciones por permiso

- Ver: `compras.ver`.
- Crear: `compras.crear`.
- Editar: `compras.editar` y estatus editable.
- Aprobar/rechazar: `compras.aprobar`.
- Cancelar: `compras.cancelar`.
- Generar orden: `compras.crear` y solicitud aprobada sin orden activa.
- Descargar documento formal: `compras.ver`.

## Criterios de terminado

- El listado carga rapido y permite filtrar.
- Las acciones respetan permisos y estatus.
- Solicitudes listas para aprobacion/orden son faciles de detectar.

## Prompt puntual

```text
Trabaja solo en Compras > Solicitudes > Listado.
Objetivo: implementar/mejorar listado con filtros por estatus, fecha, solicitante, prioridad, proveedor, almacen y con/sin orden, mostrando acciones por permiso.
Backend debe filtrar y validar acciones.
Criterio: el listado permite priorizar aprobaciones, pendientes y solicitudes listas para orden.
```
