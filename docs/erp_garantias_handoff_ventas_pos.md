# ERP Garantias - Handoff para Ventas/POS

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-27  
Estado: contrato preparado; no aplicar operacion sin DDL/permisos autorizados.

## Contexto

Garantias queda como dominio propio. Ventas/POS no debe calcular politicas de garantia por su cuenta.

Ventas debe:

- pedir a Garantias la politica vigente por SKU;
- guardar snapshot por partida al confirmar venta;
- imprimir/resumir garantia en ticket desde snapshot;
- consultar elegibilidad desde snapshot para postventa;
- crear devolucion/reembolso/nota solo si la resolucion de garantia lo requiere.

Ventas no debe:

- editar politicas de garantia;
- recalcular ventas historicas con politicas nuevas;
- mover inventario por reclamo;
- decidir reingreso a disponible.

## Endpoints preparados

### Resolver garantia por SKU

Ruta futura:

- `GET /Garantias/resolver_sku_erp`

Entrada minima:

- `id_sku_erp`
- `canal`
- `id_almacen`
- `fecha`

Salida:

- politica vigente;
- regla/origen;
- snapshot sugerido;
- alertas.

Si falta esquema:

- devuelve `sin_garantia`;
- alerta `esquema_pendiente`.

### Snapshot dry-run

Ruta futura:

- `POST /Garantias/venta_snapshot_dryrun_erp`

Entrada:

```json
{
  "fecha": "AAAA-MM-DD",
  "canal": "pos",
  "id_almacen": 0,
  "items": [
    {
      "id_sku_erp": 0
    }
  ]
}
```

Contrato:

- no crea venta;
- no guarda snapshot;
- no mueve inventario;
- valida si Garantias puede resolver las partidas.

## Integracion futura en Venta confirmada

Cuando Ventas confirme una venta real:

1. Validar stock/precio/caja como ya hace el flujo POS.
2. Por cada partida con SKU ERP, pedir snapshot a Garantias.
3. Guardar snapshot en `erp_ventas_detalle_garantias`.
4. Imprimir resumen en ticket.
5. No recalcular snapshots al cambiar politicas de Catalogo.

## Dependencias

- Aplicar DDL base de Garantias.
- Aplicar permisos:
  - `garantias.ver`
  - `garantias.reclamos.crear`
- Validar que Ventas tenga tabla `erp_ventas_detalle`.
- Validar que POS conserve `id_sku_erp` por partida.

## Riesgos

- Si POS imprime garantia desde politica viva y no desde snapshot, ventas historicas pueden quedar inconsistentes.
- Si una garantia termina en devolucion, debe referenciar `erp_ventas_devoluciones`, no duplicar devoluciones dentro de Garantias.
- Si el producto requiere unidad/serie, la elegibilidad debe validar la unidad vendida.
