# ERP Compras - Solicitudes: diferencias contra orden

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-09  
Estado: Subdocumento puntual de `erp_compras_solicitudes_trabajo.md`  
Aplica a: comparar solicitud aprobada/generada contra orden de compra

## Proposito

Mostrar y conservar diferencias entre lo solicitado y lo comprado, para que la solicitud no muera al generar una orden.

## Diferencias esperadas

- Producto solicitado comprado igual.
- Producto solicitado no surtido.
- Cantidad menor.
- Cantidad mayor.
- Producto sustituido.
- Producto adicional no solicitado.
- Costo diferente al estimado.

## Datos por diferencia

- Tipo de diferencia.
- Partida de solicitud.
- Partida de orden.
- Cantidad solicitada.
- Cantidad comprada.
- Costo estimado.
- Costo comprado.
- Justificacion opcional.
- Estado: pendiente, aceptada, resuelta.

## Permisos

- Ver diferencias: `compras.ver`.
- Resolver/aceptar diferencias: recomendado `compras.aprobar` o permiso de direccion si son sensibles.
- Ver costos: segun regla de costos del negocio.

## Reglas

- No borrar partida solicitada sin evidencia.
- Si se compra algo distinto, marcar sustitucion o adicional.
- Si no se compra, marcar no surtido.
- Diferencias sensibles pueden requerir autorizacion.

## Criterios de terminado

- Solicitud y orden explican necesidad vs compra real.
- Diferencias se ven desde solicitud y desde orden.
- Cambios quedan trazables.

## Estado actual

- Implementado: comparación y visualización de diferencias en modo consulta desde ambas vistas.
- Endpoint usado: `/compra/orden_diferencias_solicitud_erp`.
- Modal con columnas: faltantes, adicionales y cambios.
- Pendiente: validar escenarios reales con orden en estado aprobado/enviada y orden sin relación.

## Prompt puntual

```text
Trabaja solo en Compras > Solicitudes > Diferencias contra orden.
Objetivo: mostrar y guardar diferencias entre solicitado y comprado: no surtido, menor/mayor cantidad, sustituido, adicional y costo diferente.
No borrar partidas solicitadas sin evidencia.
Criterio: solicitud y orden permiten explicar que se pidio vs que se compro.
```
