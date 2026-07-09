# ERP Compras - Solicitudes: pendientes para Catalogo

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-09  
Estado: Subdocumento puntual de `erp_compras_solicitudes_trabajo.md`  
Aplica a: productos nuevos o datos incompletos detectados desde solicitud

## Proposito

Permitir que Compras solicite productos no registrados sin ensuciar Catalogo maestro. Catalogo debe recibir pendientes claros y accionables.

## Casos que generan pendiente

- Producto nuevo/propuesto.
- SKU proveedor sugerido no relacionado.
- Unidad faltante o no estandarizada.
- Datos fiscales faltantes si se capturan en solicitud.
- Proveedor sugerido sin relacion con producto.

## Datos del pendiente

- Tipo de pendiente.
- Descripcion del producto/concepto.
- SKU proveedor opcional.
- Proveedor sugerido.
- Unidad.
- Cantidad solicitada.
- Motivo.
- Solicitud relacionada.
- Partida relacionada.
- Datos fiscales propuestos si existen.
- Estado: pendiente, en revision, resuelto, descartado.

## Permisos

- Capturar pendiente desde solicitud: `compras.crear` / `compras.editar`.
- Ver resumen: `compras.ver`.
- Resolver maestro: `catalogo.editar`.
- Ver Catalogo: `catalogo.ver`.

## Reglas

- No crear producto maestro automaticamente.
- No actualizar fiscal maestro automaticamente.
- Pendiente debe conservar origen solicitud.
- Si se genera orden antes de resolver, la orden conserva producto pendiente.

## Criterios de terminado

- Compras puede registrar necesidad aunque producto no exista.
- Catalogo recibe datos suficientes para completar maestro.
- Solicitud y orden mantienen trazabilidad del pendiente.

## Prompt puntual

```text
Trabaja solo en Compras > Solicitudes > Pendientes Catalogo.
Objetivo: cuando una solicitud incluya producto nuevo/propuesto o datos faltantes, generar pendiente para Catalogo sin crear maestro automaticamente.
Permisos: Compras captura; Catalogo resuelve.
Criterio: el pendiente conserva descripcion, cantidad, proveedor sugerido, motivo y datos propuestos.
```
