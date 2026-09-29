# ERP Ecommerce Videos - Plan de reversa DDL

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-28  
Estado: plan preventivo; no ejecutar sin autorizacion explicita.

## Contexto

El DDL de Videos crea tablas nuevas para metadatos de videos TikTok y relaciones comerciales. No altera tablas existentes y no toca inventario, ventas, POS ni CRM.

## Reversa tecnica posible

Si la aplicacion se hizo por error y aun no hay datos reales capturados, la reversa tecnica seria eliminar las tablas nuevas en orden dependiente:

```sql
DROP TABLE IF EXISTS `erp_ecommerce_video_categoria`;
DROP TABLE IF EXISTS `erp_ecommerce_video_producto`;
DROP TABLE IF EXISTS `erp_ecommerce_videos`;
```

## Condiciones para permitir reversa

Antes de revertir:

- confirmar respaldo externo previo;
- confirmar que no hay videos reales cargados;
- confirmar que ningun contenido, producto o categoria depende de esos videos;
- confirmar que el frontend no esta consumiendo videos reales desde `fuente=bd_videos`.

## Si ya hay datos reales

No usar `DROP TABLE` como primera opcion.

En ese caso:

1. Exportar tablas `erp_ecommerce_videos*`.
2. Pausar publicacion de videos desde CMS.
3. Corregir esquema con migracion incremental si el problema es estructural.
4. Conservar miniaturas y metadatos para no perder trabajo editorial.

## Guardrail

No hay script automatico de reversa por diseno. El borrado de tablas debe ser manual, revisado y autorizado, porque podria destruir contenido comercial real.
