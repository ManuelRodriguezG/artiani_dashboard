# ERP Compras - Solicitudes: editar solicitud

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-09  
Estado: Subdocumento puntual de `erp_compras_solicitudes_trabajo.md`  
Aplica a: correccion o complemento de una solicitud existente

## Proposito

Definir cuando y como se puede editar una solicitud. Editar debe respetar estatus y no debe alterar solicitudes ya cerradas o con orden generada.

## Archivos de trabajo

- `app/vistas/paginas/apps/erp/compras/solicitudes/formulario.php`
- `public/assets/js/custom/apps/erp/compras/solicitudes/formulario.js`
- `app/controladores/Compra.php`
- `app/modelos/SolicitudesCompraErp.php`

## Edicion por estatus

### Borrador

Permite:

- Editar cabecera.
- Agregar/quitar partidas.
- Cambiar cantidades.
- Cambiar proveedor/almacen sugeridos.
- Agregar productos nuevos/propuestos.
- Guardar o enviar a aprobacion.

### Pendiente

Regla recomendada:

- No editar libremente.
- Aprobador puede aprobar/rechazar.
- Si se requiere correccion, regresar a `borrador` con motivo o crear flujo especifico.

### Aprobada

Permite:

- Ver.
- Generar orden si no existe orden activa.

No permite:

- Editar libremente.

### Rechazada, cancelada, orden_generada

- Solo lectura.
- Acciones administrativas solo si se definen.

## Permisos

- Ver: `compras.ver`.
- Editar borrador: `compras.editar`.
- Enviar a aprobacion: `compras.editar` o `compras.crear`.
- Aprobar/rechazar: `compras.aprobar`.
- Cancelar: `compras.cancelar`.

## Reglas tecnicas

- Backend debe validar estatus antes de guardar.
- No borrar partidas sin auditoria si la solicitud ya salio de borrador.
- Cambios relevantes deben auditarse.

## Criterios de terminado

- Borrador editable funciona.
- Pendiente no se edita libremente.
- Aprobada solo permite generar orden.
- Rechazada/cancelada/orden_generada quedan en solo lectura.

## Prompt puntual

```text
Trabaja solo en Compras > Solicitudes > Editar solicitud.
Objetivo: permitir editar solicitudes en borrador y bloquear edicion libre en pendiente, aprobada, rechazada, cancelada u orden_generada.
Backend debe validar estatus y permisos.
Criterio: ninguna solicitud cerrada o con orden generada puede modificarse como captura normal.
```
