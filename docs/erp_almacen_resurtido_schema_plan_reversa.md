# ERP Almacen - Plan de reversa DDL Resurtido

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-11  
Estado: plan preventivo; no ejecutar sin autorizacion explicita.

## Contexto

El DDL de Resurtido crea tablas nuevas para solicitudes, preparacion, envio, recepcion, diferencias y politicas por tienda/SKU.

No debe mover inventario ni tocar POS/ecommerce al aplicarse.

## Reversa recomendada si no hay datos reales

Si la aplicacion se hizo por error y aun no hay documentos reales, la reversa tecnica seria eliminar tablas en orden dependiente:

```sql
DROP TABLE IF EXISTS `erp_almacen_resurtido_diferencias`;
DROP TABLE IF EXISTS `erp_almacen_resurtido_recepciones`;
DROP TABLE IF EXISTS `erp_almacen_resurtido_envios`;
DROP TABLE IF EXISTS `erp_almacen_resurtido_preparacion`;
DROP TABLE IF EXISTS `erp_almacen_resurtido_detalle`;
DROP TABLE IF EXISTS `erp_almacen_resurtidos`;
DROP TABLE IF EXISTS `erp_inventario_politicas_almacen_sku`;
```

## Condiciones antes de revertir

Confirmar:

- existe respaldo externo previo;
- no hay folios reales `RES-*`;
- no hay politicas reales por tienda/SKU;
- no hay UAT pendiente que dependa de estas tablas;
- no hay pantallas o endpoints productivos usando Resurtido.

## Si ya hay datos reales

No usar `DROP TABLE` como primera opcion.

Pasos recomendados:

1. Exportar tablas de resurtido.
2. Pausar endpoints de resurtido.
3. Revisar si la falla se corrige con migracion incremental.
4. Si se debe desactivar, ocultar menu/acciones antes de borrar.
5. Decidir con el dueno si se archiva o conserva la evidencia.

## Guardrail

No preparar script automatico de reversa destructiva.

El borrado de tablas debe ser manual, revisado y autorizado porque podria destruir solicitudes, diferencias o politicas operativas reales.

