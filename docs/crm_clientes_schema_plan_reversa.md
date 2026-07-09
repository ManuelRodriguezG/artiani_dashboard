# CRM Clientes - Plan de reversa DDL base

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-29  
Estado: plan preventivo; no ejecutar sin autorizacion explicita.

## Principio

La reversa de DDL debe tratarse como accion destructiva. Solo se considera segura inmediatamente despues de aplicar el esquema, antes de migrar o capturar clientes en las nuevas tablas.

## Alcance de reversa

Tablas creadas por `CRM_CLIENTES_DDL_BASE`:

- `crm_clientes_fusiones`
- `crm_clientes_vinculos_externos`
- `crm_clientes_eventos`
- `crm_clientes_notas`
- `crm_clientes_consentimientos`
- `crm_clientes_segmentos_rel`
- `crm_clientes_segmentos`
- `crm_clientes_fiscales`
- `crm_clientes_direcciones`
- `crm_clientes_contactos`
- `crm_clientes_identificadores`
- `crm_clientes_maestro`

El orden anterior es el recomendado para eliminar si nunca se insertaron datos.

## No tocar

Nunca borrar dentro de esta reversa:

- `crm_clientes` legacy;
- `erp_clientes`;
- `erp_clientes_identificadores`;
- `erp_ventas`;
- `ecom_pedidos`;
- `sys_*`.

## Validacion antes de reversa

Antes de cualquier `DROP TABLE`, verificar que todas las tablas CRM nuevas esten vacias:

```sql
SELECT COUNT(*) FROM crm_clientes_maestro;
SELECT COUNT(*) FROM crm_clientes_identificadores;
SELECT COUNT(*) FROM crm_clientes_vinculos_externos;
```

Si alguna tabla tiene datos reales, no hacer reversa manual; preparar plan de migracion o restauracion desde respaldo.

## Recomendacion

Como el apply inicial solo crea tablas, la reversa preferida ante error serio es restaurar el respaldo externo completo en ambiente controlado, no borrar a mano en produccion.

## Estado actual

No se ha aplicado el DDL CRM base. Este plan queda preparado para cuando el dueno autorice el esquema.
