# Solicitud de autorizacion - Listas de precios por segmento CRM

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-16  
Estado: solicitud preparada; respaldo generado; DDL no ejecutado.

## Objetivo

Crear la tabla puente `erp_segmentos_listas_precios` para asignar listas de precios a segmentos/tipos de cliente CRM, sin asignar listas reales todavía y sin modificar ventas pasadas.

## Respaldo requerido

Antes de ejecutar DDL se requiere respaldo externo valido fuera del proyecto.

Ejemplo:

`C:\xampp\panel_db_backups\panel_artianilocal_YYYYMMDD_HHmmss_antes_listas_precios_segmentos.sql`

Respaldo generado para esta fase:

`C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql`

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=28578042
```

## Token requerido

`VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL`

## Endpoint preparado

Auditoria read-only:

`/comercial/esquema_auditar_segmentos_listas_precios`

Apply protegido:

`/comercial/esquema_actualizar_segmentos_listas_precios`

Script CLI protegido:

`storage/uat/uat_listas_precios_segmentos_schema_apply_authorized.php`

Payload autorizado:

```json
{
  "ejecutar": 1,
  "autorizar": "VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL",
  "respaldo": "C:\\xampp\\panel_db_backups\\panel_artianilocal_YYYYMMDD_HHmmss_antes_listas_precios_segmentos.sql"
}
```

Comando CLI autorizado equivalente:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_schema_apply_authorized.php --autorizar=VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql
```

## Alcance del DDL

El apply solamente puede crear `erp_segmentos_listas_precios` con:

- `id_segmento_crm`
- `id_lista_precio`
- `canal`
- `id_almacen`
- `prioridad`
- vigencia
- estatus
- motivo
- trazabilidad basica

## Guardrails

- No crea segmentos CRM.
- No asigna listas a segmentos.
- No activa listas.
- No cambia precios.
- No modifica ventas pasadas.
- No toca POS, ecommerce ni recompensas.

## UAT read-only previo

Ejecutar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_readonly.php 2
```

Esperado antes del DDL:

- CRM segmentos existe.
- CRM segmentos relacion existe.
- ERP listas de precios existe.
- `erp_segmentos_listas_precios` no existe.
- El plan SQL se genera sin ejecutar.
- El dry-run de asignacion segmento/lista queda bloqueado por falta de tabla puente.

## UAT posterior al DDL

- Auditoria debe mostrar `erp_segmentos_listas_precios` existente con columnas e indices.
- Dry-run de asignacion segmento/lista debe validar segmento, lista, canal, almacen, prioridad y vigencia.
- Resolver precio con cliente que tenga `id_segmento_default` debe poder devolver `regla_precio_origen=lista_segmento_cliente` cuando exista asignacion activa.
