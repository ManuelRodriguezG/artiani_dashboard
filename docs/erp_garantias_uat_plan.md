# ERP Garantias - Plan UAT inicial

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-27  
Estado: plan de pruebas; ejecutar despues de DDL/permisos.

## Objetivo

Validar que Garantias ERP resuelve politicas, genera snapshots de venta, consulta elegibilidad y prepara reclamos sin romper Ventas, Almacen ni Inventario.

## Condiciones previas

- DDL base de Garantias aplicado.
- Permisos de Garantias aplicados.
- Auditoria de esquema sin pendientes.
- Al menos un SKU ERP activo.
- Al menos una politica de garantia activa.
- Al menos una regla de garantia activa.

## Casos UAT

### GAR-UAT-001 - Auditoria de esquema completa

Accion:

- Ejecutar `Garantias/esquema_auditar_garantias_erp`.

Esperado:

- `tipo=success`.
- Sin tablas faltantes.
- Sin columnas faltantes.

### GAR-UAT-002 - Resolver SKU sin politica

Accion:

- Consultar un SKU ERP sin regla de garantia.

Esperado:

- Respuesta sin error tecnico.
- Politica `SIN_GARANTIA`.
- Alerta `politica_no_configurada`.

### GAR-UAT-003 - Resolver SKU con politica directa

Accion:

- Crear politica activa.
- Crear regla con `ambito=sku`.
- Consultar el SKU.

Esperado:

- Devuelve politica configurada.
- Origen `sku`.
- Snapshot sugerido con fecha de inicio y vencimiento.

### GAR-UAT-004 - Precedencia SKU sobre categoria

Accion:

- Crear politica A por categoria.
- Crear politica B por SKU.
- Consultar SKU.

Esperado:

- Gana politica B por SKU.
- No usa categoria si existe regla especifica vigente.

### GAR-UAT-005 - Reglas empatadas

Accion:

- Configurar dos reglas activas del mismo ambito y prioridad para el mismo SKU.

Esperado:

- Resolver responde `warning`.
- Alerta `reglas_duplicadas_misma_prioridad`.
- No debe seleccionar silenciosamente sin advertir.

### GAR-UAT-006 - Snapshot dry-run de venta

Accion:

- Enviar `venta_snapshot_dryrun_erp` con una partida SKU configurada.

Esperado:

- `dry_run=true`.
- No crea venta.
- No guarda snapshot.
- Devuelve snapshot sugerido por partida.

### GAR-UAT-007 - Snapshot de venta real futuro

Accion:

- Confirmar venta real cuando Ventas integre Garantias.

Esperado:

- Se guarda `erp_ventas_detalle_garantias`.
- El ticket usa resumen del snapshot.
- Cambiar la politica despues no altera la venta historica.

Estado actual:

- Pendiente hasta integrar con Ventas/POS.

### GAR-UAT-008 - Consulta elegibilidad sin snapshot

Accion:

- Consultar elegibilidad sin `id_venta_detalle_garantia`, `id_venta_detalle` ni folio.

Esperado:

- Bloqueo `referencia_no_indicada`.
- No crea reclamo.

### GAR-UAT-009 - Reclamo dry-run incompleto

Accion:

- Ejecutar `reclamo_dryrun_erp` sin motivo o sin SKU.

Esperado:

- `tipo=warning`.
- Bloqueos claros.
- No crea reclamo.

### GAR-UAT-010 - Reclamo dry-run valido

Accion:

- Ejecutar `reclamo_dryrun_erp` con venta/detalle, SKU y motivo.

Esperado:

- `dry_run=true`.
- Sin bloqueos de esquema.
- No crea reclamo.
- No crea devolucion.
- No mueve inventario.

### GAR-UAT-011 - Producto con serie/etiqueta

Accion:

- Resolver o consultar elegibilidad de SKU que requiere serie/etiqueta.

Esperado:

- El flujo debe exigir unidad vendida cuando aplique.
- Si no hay unidad ligada a venta, debe generar bloqueo o alerta.

Estado actual:

- Pendiente de integracion con Ventas e Inventario.

### GAR-UAT-012 - Garantia con destino Almacen

Accion:

- Resolver reclamo que requiere recepcion fisica.

Esperado futuro:

- Garantias no mueve inventario.
- Genera solicitud/contrato para Almacen.
- Almacen decide cuarentena, devoluciones, merma o reingreso.

Estado actual:

- Pendiente de contrato endpoint en Almacen.

## Evidencia por prueba

Guardar:

- usuario;
- fecha/hora;
- endpoint;
- parametros relevantes;
- respuesta JSON;
- folio/SKU usado;
- hallazgo o captura.

## Criterio de cierre UAT inicial

- Resolver SKU funciona para sin politica, politica directa y precedencia.
- Snapshot dry-run genera contratos por partida.
- Elegibilidad bloquea sin referencia.
- Reclamo dry-run valida datos minimos.
- Ninguna prueba mueve inventario ni crea devoluciones.
