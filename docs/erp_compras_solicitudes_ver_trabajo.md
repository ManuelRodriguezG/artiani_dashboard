# ERP Compras - Solicitudes: ver solicitud

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-09  
Estado: Subdocumento puntual de `erp_compras_solicitudes_trabajo.md`  
Aplica a: consulta segura de una solicitud

## Proposito

Definir la vista de consulta de solicitud. Ver solicitud debe explicar la necesidad, estado, aprobacion y relacion con orden, sin permitir edicion directa salvo acciones autorizadas.

## Archivos de trabajo

- `app/vistas/paginas/apps/erp/compras/solicitudes/formulario.php`
- `public/assets/js/custom/apps/erp/compras/solicitudes/formulario.js`
- `app/controladores/Compra.php`
- `app/modelos/SolicitudesCompraErp.php`
- `app/modelos/OrdenesCompraErp.php`

## Secciones visibles

- Cabecera.
- Partidas solicitadas.
- Estatus.
- Solicitante/area.
- Aprobacion/rechazo/cancelacion.
- Orden relacionada si existe.
- Diferencias contra orden si ya se genero.
- Productos nuevos o pendientes de Catalogo.

## Acciones posibles

- Editar si esta en `borrador` y tiene `compras.editar`.
- Aprobar/rechazar si esta en `pendiente` y tiene `compras.aprobar`.
- Cancelar si reglas y `compras.cancelar` lo permiten.
- Generar orden si esta `aprobada`, no tiene orden activa y tiene permiso.
- Generar documento formal si tiene `compras.ver`.

## Permisos

- Ver solicitud: `compras.ver`.
- Ver diferencias: `compras.ver`.
- Acciones: permisos especificos por accion.
- Auditoria completa: permiso auditor/soporte si se crea.

## Criterios de terminado

- Ver solicitud es seguro y no editable por defecto.
- Las acciones aparecen solo si permiso y estatus lo permiten.
- Muestra orden relacionada y diferencias.
- Backend valida cualquier accion sensible.

## Prompt puntual

```text
Trabaja solo en Compras > Solicitudes > Ver solicitud.
Objetivo: crear/ajustar vista segura de consulta con cabecera, partidas, estatus, orden relacionada, diferencias y acciones por permiso.
No permitir edicion directa salvo redireccion a editar en estatus permitido.
Criterio: ver solicitud explica el estado completo y respeta permisos.
```
