# ERP - Handoff Inventario/Existencias: unidades fisicas abiertas

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-25  
Estado: listo para iniciar modulo Inventario/Existencias

## Contexto

Almacen/Preparacion-Empaque ya puede:

- recibir unidades fisicas con contenido base;
- generar etiquetas desde Recepcion;
- preparar presentaciones desde existencia agregada;
- preparar presentaciones desde una unidad fisica exacta;
- dejar una unidad fisica como `abierta` cuando se consume parcialmente;
- generar etiquetas para presentaciones preparadas;
- imprimir/pegar etiquetas de preparacion.

## Evidencia cerrada

- `REC-OC-25`: recepcion con 3 unidades fisicas de `4 kg`.
- `PREP-20260625-0001`: preparacion por existencia sin unidad fisica origen.
- `PREP-20260625-0002`: preparacion desde unidad fisica exacta.
- Unidad abierta validada:
  - `UAT-EXI-26-20260625-001`
  - `14.950000 kg`
  - `estado_fisico = abierta`
  - `estatus = disponible`

## Decision operativa

Unidad abierta:

- conserva inventario disponible;
- no es unidad cerrada vendible;
- puede usarse en Preparacion/Empaque;
- puede venderse a granel en POS si el SKU lo permite;
- no debe venderse en ecommerce como unidad cerrada.

Regla corta:

> Unidad abierta = stock disponible, no unidad cerrada vendible. POS puede venderla a granel; Preparacion puede consumirla; ecommerce no debe tomarla como unidad cerrada.

## Objetivo del siguiente modulo

Trabajar en `ERP > Inventario > Existencias` para que Inventario muestre saldos robustos considerando:

- existencia agregada por SKU/lote/caducidad/ubicacion;
- unidades fisicas cerradas;
- unidades fisicas abiertas;
- unidades consumidas;
- etiquetas pendientes de impresion/pegado;
- diferencias entre stock disponible contable y unidades fisicas trazables.

## Archivos a leer primero

- `AGENTS.md`
- `docs/erp_plan_maestro_fundamentos.md`
- `docs/erp_ux_operativa.md`
- `docs/erp_inventario_existencias_arranque.md`
- `docs/erp_almacen_unidades_fisicas_arranque.md`
- `docs/erp_inventario_existencias_unidades_abiertas_handoff.md`
- `app/controladores/Inventario.php`
- `app/modelos/InventarioErp.php`
- `app/vistas/paginas/apps/erp/inventarios/existencias.php`
- `public/assets/js/custom/apps/erp/inventarios/existencias_erp.js`

## Auditoria inicial sugerida

1. Como `InventarioErp::existencias_erp` lista saldos.
2. Si el listado muestra unidades fisicas relacionadas.
3. Si puede distinguir:
   - cerrado disponible;
   - abierto disponible;
   - consumido;
   - pendiente impresion/pegado.
4. Si el diagnostico de unidades vs existencia ya usa `SUM(cantidad_base_disponible)`.
5. Si la UI necesita filtros:
   - con unidades abiertas;
   - con etiquetas pendientes;
   - con diferencia saldo vs unidades.
6. Si hace falta endpoint de detalle por existencia para ver etiquetas/unidades.

## Criterio de cierre del siguiente modulo

- Inventario muestra existencia agregada sin ocultar unidades abiertas.
- Inventario permite rastrear unidades fisicas por existencia.
- Unidad abierta se resalta como stock disponible con condicion operativa.
- No se toca Ventas ni ecommerce salvo documentar impacto.
- No se aplica migracion sin respaldo externo y autorizacion.

## Pendientes conocidos no bloqueantes

- Definir comportamiento final de POS para venta a granel desde unidad abierta.
- Definir exclusiones de ecommerce para unidades abiertas.
- Evaluar si se requiere tabla historica `erp_inventario_unidad_movimientos` para auditoria fina de movimientos por unidad.
