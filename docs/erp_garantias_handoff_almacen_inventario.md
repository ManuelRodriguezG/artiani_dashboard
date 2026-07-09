# ERP Garantias - Handoff para Almacen e Inventario

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-27  
Estado: contrato de integracion; no implementar movimientos sin DDL/permisos autorizados.

## Contexto

Garantias gestiona politica, elegibilidad, reclamo, evidencia, diagnostico y resolucion.

Almacen/Inventario gestiona todo producto fisico:

- recepcion de producto reclamado;
- cuarentena;
- devoluciones;
- merma;
- reparacion;
- reingreso a disponible;
- trazabilidad por unidad, lote, caducidad y ubicacion.

## Regla principal

Garantias no debe escribir existencia disponible directamente.

Si un reclamo implica recibir producto fisico, Garantias debe solicitar o ligar un flujo de Almacen. Almacen decide el destino tecnico y registra movimientos.

## Destinos tecnicos esperados

- `cuarentena`: producto pendiente de revision.
- `devoluciones`: producto devuelto por cliente pendiente de decision.
- `merma`: producto no recuperable.
- `reintegrar`: solo cuando Almacen libera el producto.
- `sin_reingreso`: cuando la resolucion no devuelve producto fisico a inventario.

## Contrato futuro desde Garantias hacia Almacen

Entrada minima:

- `id_reclamo_garantia`
- `id_venta`
- `id_venta_detalle`
- `id_sku_erp`
- `id_inventario_unidad`, si aplica
- `cantidad_base`
- `unidad_base`
- `lote`, si aplica
- `fecha_caducidad`, si aplica
- `motivo`
- `decision_inventario_sugerida`

Almacen debe validar:

- que la unidad pertenece a la venta si hay `id_inventario_unidad`;
- que lote/caducidad coincidan cuando aplique;
- que no se reciba dos veces la misma unidad por el mismo reclamo;
- que el destino tecnico exista y sea valido;
- que el movimiento quede ligado al reclamo.

## Integracion con devoluciones de Ventas

Si la garantia termina en devolucion comercial:

- Ventas registra devolucion/reembolso/nota.
- Almacen registra recepcion/destino fisico.
- Garantias conserva el reclamo, evidencia y decision.

No se debe usar la devolucion de Ventas como unico historial de garantia.

## Dependencias

- Aplicar DDL base de Garantias.
- Validar almacenes tecnicos `devoluciones`, `cuarentena` y `merma`.
- Validar trazabilidad de `erp_inventario_unidades` para productos con serie/etiqueta.
- Definir endpoint futuro en Almacen para recepcion desde reclamo.

## Riesgos

- Reingresar inventario desde POS o Garantias sin revision fisica.
- No ligar unidad vendida con unidad devuelta.
- Mezclar productos devueltos con existencia disponible antes del diagnostico.
- Resolver reclamos sin evidencia de destino fisico.
