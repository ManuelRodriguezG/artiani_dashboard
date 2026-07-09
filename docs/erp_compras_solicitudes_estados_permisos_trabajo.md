# ERP Compras - Solicitudes: estados y permisos

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-09  
Estado: Subdocumento puntual de `erp_compras_solicitudes_trabajo.md`  
Aplica a: transiciones, permisos y validacion backend

## Proposito

Definir como cambia de estado una solicitud y que permiso requiere cada accion. Esta pieza evita que una solicitud rechazada, cancelada o pendiente genere orden indebidamente.

## Estados

- `borrador`: captura editable.
- `pendiente`: enviada para aprobacion.
- `aprobada`: autorizada para generar orden.
- `rechazada`: no genera orden.
- `orden_generada`: ya tiene orden activa relacionada.
- `cancelada`: cerrada sin compra.

## Transiciones permitidas

- `borrador -> pendiente`: enviar a aprobacion.
- `pendiente -> aprobada`: aprobar.
- `pendiente -> rechazada`: rechazar con motivo.
- `borrador/pendiente/aprobada -> cancelada`: cancelar con motivo si no rompe reglas.
- `aprobada -> orden_generada`: generar orden.

## Transiciones prohibidas

- `rechazada -> orden_generada`.
- `cancelada -> orden_generada`.
- `pendiente -> orden_generada`.
- `orden_generada -> borrador`.
- Editar libremente `rechazada`, `cancelada` u `orden_generada`.

## Permisos por accion

- Ver: `compras.ver`.
- Crear: `compras.crear`.
- Editar borrador: `compras.editar`.
- Enviar a aprobacion: `compras.crear` o `compras.editar`, segun regla final.
- Aprobar/rechazar: `compras.aprobar`.
- Cancelar: `compras.cancelar`.
- Generar orden: `compras.crear`; opcionalmente `compras.aprobar` si el negocio decide que tambien es autorizacion.
- Auditoria completa: permiso auditor/soporte si se crea.

## Reglas tecnicas

- Frontend oculta acciones, backend valida siempre.
- Toda transicion sensible debe auditarse.
- Rechazo y cancelacion requieren motivo.
- Generar orden debe verificar que no exista orden activa relacionada.

## Criterios de terminado

- Ninguna transicion invalida se ejecuta desde backend.
- Cada boton visible corresponde a permiso y estatus.
- Auditoria registra aprobacion, rechazo, cancelacion y generacion de orden.

## Prompt puntual

```text
Trabaja solo en Compras > Solicitudes > Estados y permisos.
Objetivo: implementar/validar transiciones borrador, pendiente, aprobada, rechazada, orden_generada y cancelada con permisos por accion.
Backend debe rechazar transiciones invalidas aunque frontend sea manipulado.
Criterio: solicitudes rechazadas/canceladas/pendientes no generan orden y todo cambio sensible queda auditado.
```
