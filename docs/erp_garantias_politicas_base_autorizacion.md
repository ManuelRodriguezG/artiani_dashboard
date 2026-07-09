# ERP Garantias - Autorizacion de politicas base

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-27  
Estado: propuesta; no ejecutar sin autorizacion.

## Objetivo

Crear las primeras politicas operativas de Garantias ERP para poder probar resolver SKU, snapshot y reclamo con reglas reales.

## Alcance propuesto

Crear politicas base:

- `SIN_GARANTIA`
- `GAR_TIENDA_7_DIAS_CAMBIO`
- `GAR_TIENDA_30_DIAS_DIAGNOSTICO`
- `GAR_PROVEEDOR_SEGUN_POLITICA`
- `GAR_FABRICANTE_SERIE`
- `CADUCIDAD_CALIDAD_LIMITADA`

No se propone crear reglas masivas todavia.

## Regla de arranque recomendada

Para UAT inicial, crear solo una regla directa por SKU de prueba:

- Politica: `GAR_TIENDA_7_DIAS_CAMBIO`
- Ambito: `sku`
- Referencia sugerida: SKU `7` (`ART.10198`)
- Prioridad: `10`
- Canal: `pos`

Motivo:

- Permite probar precedencia por SKU.
- No afecta categorias completas.
- No cambia operacion masivamente.
- Es facil desactivar la regla si no corresponde.

## Fuera de alcance

Esta autorizacion no incluye:

- Asignar politicas por categoria completa.
- Asignar politicas por marca completa.
- Asignar politicas por proveedor completo.
- Integrar UI de Catalogo.
- Guardar snapshots en ventas reales.
- Crear reclamos reales.
- Mover inventario.

## Token requerido

```text
GARANTIAS_POLITICAS_BASE
```

## Respaldo requerido

Se requiere respaldo externo fuera del proyecto antes de guardar politicas reales.

## Criterio de exito

- `erp_garantias_politicas` contiene politicas base.
- `erp_garantias_politicas_reglas` contiene solo la regla UAT autorizada.
- Resolver SKU `7` devuelve politica `GAR_TIENDA_7_DIAS_CAMBIO`.
- Snapshot dry-run para SKU `7` devuelve vencimiento a 7 dias.
- No se crean reclamos ni movimientos de inventario.

## Criterio de no ejecucion

No ejecutar si:

- no hay respaldo externo;
- falta token exacto;
- no se confirma SKU UAT;
- se pretende asignar por categoria/marca/proveedor sin revision;
- hay duda sobre si la politica aplica al producto real.
