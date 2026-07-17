# ERP Listas de precios - Plan de reversa segmentos CRM

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-17  
Estado: plan de reversa documentado; no ejecuta cambios.

## Alcance

Este plan aplica solo a la tabla puente:

```sql
erp_segmentos_listas_precios
```

No aplica a:

- segmentos CRM existentes;
- clientes CRM;
- listas de precios;
- detalles de precio por SKU;
- ventas pasadas;
- snapshots POS.

## Preferencia de reversa

La reversa preferida es restaurar el respaldo externo tomado antes del apply:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql
```

No usar `DROP TABLE` como primera opcion si hubo operacion real.

## Reversa tecnica considerada

Solo podria considerarse una reversa tecnica manual si:

- `erp_segmentos_listas_precios` existe;
- la tabla esta vacia;
- no hay vinculos segmento/lista activos;
- no se hizo UAT real con clientes segmentados;
- existe respaldo externo verificable;
- hay autorizacion explicita posterior.

SQL de reversa tecnica, no ejecutado:

```sql
DROP TABLE IF EXISTS `erp_segmentos_listas_precios`;
```

## Preflight read-only

Ejecutar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_reversa_preflight_readonly.php
```

Resultado esperado antes del DDL:

- `etapa=reversa_no_aplica_sin_tabla`
- no hay nada que revertir.

Resultado aceptable para considerar reversa tecnica:

- `etapa=reversa_tecnica_considerable`
- `puede_considerar_reversa_tecnica=true`
- tabla existente y vacia.

Resultado bloqueante:

- `tabla_con_datos_erp_segmentos_listas_precios`
- `existen_vinculos_segmento_lista_activos`

## Regla operativa

Si ya hay vinculos segmento/lista reales, la reversa no debe ser borrado manual. En ese caso:

- pausar o cancelar vinculos con auditoria;
- validar resolutor POS;
- preservar historial;
- restaurar respaldo completo solo en ambiente controlado si la situacion lo amerita.

No hay script automatico de reversa destructiva por diseno.
