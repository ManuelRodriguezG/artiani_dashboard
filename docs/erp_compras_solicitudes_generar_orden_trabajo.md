# ERP Compras - Solicitudes: generar orden

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-09  
Estado: Subdocumento puntual de `erp_compras_solicitudes_trabajo.md`  
Aplica a: convertir solicitud aprobada en orden de compra

## Proposito

Definir como una solicitud aprobada genera una orden sin duplicados y con trazabilidad.

## Archivos de trabajo

- `app/controladores/Compra.php`
- `app/modelos/SolicitudesCompraErp.php`
- `app/modelos/OrdenesCompraErp.php`
- `app/modelos/ComprasEsquema.php`

## Reglas

- Solo solicitud `aprobada` genera orden.
- Solicitud `pendiente`, `rechazada` o `cancelada` no genera orden.
- Si ya existe orden activa relacionada, no generar duplicado.
- La orden conserva `id_solicitud`.
- Cada partida conserva `id_solicitud_detalle`.
- La solicitud pasa a `orden_generada`.
- Productos propuestos pasan como pendientes en orden.

## Permisos

- Generar orden: `compras.crear`.
- Opcional: requerir `compras.aprobar` si el negocio decide que generar orden tambien es autorizacion operativa.
- Ver solicitud/orden generada: `compras.ver`.

## Criterios de terminado

- Una solicitud aprobada genera una sola orden activa.
- La orden aparece como borrador editable segun permisos.
- La solicitud queda relacionada a la orden.
- Backend impide duplicados.

## Prompt puntual

```text
Trabaja solo en Compras > Solicitudes > Generar orden.
Objetivo: generar una orden desde solicitud aprobada, conservar id_solicitud/id_solicitud_detalle, evitar duplicados y cambiar solicitud a orden_generada.
No permitir generar desde rechazada, cancelada o pendiente.
Criterio: una solicitud aprobada genera una sola orden activa trazable.
```
