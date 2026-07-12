# ERP Ecommerce publico - Plan de reversa DDL Fase 1

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-11  
Estado: plan preventivo; no ejecutar sin autorizacion explicita.

## Contexto

El DDL Fase 1 crea tablas nuevas `erp_ecommerce_*`. No altera tablas existentes y no toca `ecom_*`, inventario, ventas, POS ni CRM.

## Reversa recomendada

Si la aplicacion se hizo por error y aun no hay datos reales capturados, la reversa tecnica seria eliminar las tablas nuevas en orden dependiente:

```sql
DROP TABLE IF EXISTS `erp_ecommerce_cotizaciones_eventos`;
DROP TABLE IF EXISTS `erp_ecommerce_cotizaciones_detalle`;
DROP TABLE IF EXISTS `erp_ecommerce_cotizaciones`;
DROP TABLE IF EXISTS `erp_ecommerce_configuracion`;
DROP TABLE IF EXISTS `erp_ecommerce_publicaciones`;
```

## Condiciones para permitir reversa

Antes de revertir:

- Confirmar respaldo externo previo.
- Confirmar que no existen publicaciones reales aprobadas.
- Confirmar que no existen cotizaciones reales recibidas por WhatsApp.
- Confirmar que no hay pantallas o endpoints productivos dependiendo de estas tablas.

## Si ya hay datos reales

No usar `DROP TABLE` como primera opcion. En ese caso:

1. Exportar tablas `erp_ecommerce_*`.
2. Pausar endpoints publicos.
3. Deshabilitar nuevas publicaciones/cotizaciones por configuracion.
4. Decidir si se corrige esquema con migracion incremental o si se archiva la data.

## Guardrail

No hay script automatico de reversa por diseno. El borrado de tablas debe ser manual, revisado y autorizado, porque podria destruir intencion comercial real.
