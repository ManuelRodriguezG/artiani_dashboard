# ERP Almacen - Paquete autorizacion y UAT Resurtido

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-13  
Modulo: ERP > Almacen > Resurtido / Traspasos entre tiendas  
Estado: paquete preparado; no ejecuta DDL ni escrituras por si mismo.

## Objetivo

Pasar de la fase read-only al primer folio UAT `RES-*` de forma controlada, con respaldo externo, autorizacion textual y evidencia por folio/SKU.

## Guardrails

- No ejecutar DDL sin respaldo externo.
- No crear folio UAT sin respaldo externo.
- No mover inventario en `RES-T008`.
- No tocar POS/ecommerce.
- No usar el actualizador general de Almacen para este DDL.
- No usar auditoria como bandeja de trabajo.

## Archivos del paquete

- `docs/erp_almacen_resurtido_traspasos_schema_propuesta.sql`
- `docs/erp_almacen_resurtido_schema_solicitud_autorizacion.md`
- `docs/erp_almacen_resurtido_schema_runbook_aplicacion.md`
- `docs/erp_almacen_resurtido_schema_plan_reversa.md`
- `storage/uat/uat_almacen_resurtido_readonly.php`
- `storage/uat/uat_almacen_resurtido_sql_static.php`
- `storage/uat/uat_almacen_resurtido_autorizacion_preflight.php`
- `storage/uat/uat_almacen_resurtido_schema_apply_authorized.php`
- `storage/uat/uat_almacen_resurtido_guardar_authorized.php`
- `storage/uat/uat_almacen_resurtido_folio_readonly.php`
- `storage/uat/uat_almacen_resurtido_preparacion_envio_preflight.php`
- `storage/uat/uat_almacen_resurtido_recepcion_diferencias_preflight.php`
- `storage/uat/uat_almacen_resurtido_preparar_enviar_authorized.php`
- `storage/uat/uat_almacen_resurtido_recibir_authorized.php`

## Preflight sin escritura

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_autorizacion_preflight.php
```

Resultado esperado:

- `ok=true`.
- Archivos requeridos presentes.
- Tablas pendientes listadas si aun no se aplico DDL.
- Secuencia autorizada visible.
- `no_escribe_bd=true`.

## Token DDL

```text
ALMACEN_RESURTIDO_DDL
```

Confirmacion textual:

```text
AUTORIZO DDL RESURTIDO ALMACEN usando respaldo RUTA_O_REFERENCIA
```

Comando autorizado, solo despues de respaldo externo:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_schema_apply_authorized.php --autorizar=ALMACEN_RESURTIDO_DDL --confirmacion="AUTORIZO DDL RESURTIDO ALMACEN usando respaldo RUTA_O_REFERENCIA" --respaldo=RUTA_O_REFERENCIA_RESPALDO
```

## Token folio UAT

```text
ALMACEN_RESURTIDO_GUARDAR_UAT
```

Confirmacion textual:

```text
AUTORIZO UAT GUARDAR RESURTIDO usando respaldo RUTA_O_REFERENCIA
```

Comando autorizado, solo despues de DDL y respaldo externo:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_guardar_authorized.php --autorizar=ALMACEN_RESURTIDO_GUARDAR_UAT --confirmacion="AUTORIZO UAT GUARDAR RESURTIDO usando respaldo RUTA_O_REFERENCIA" --respaldo=RUTA_O_REFERENCIA_RESPALDO --destino=4 --origen=3
```

## Secuencia esperada

1. Ejecutar UAT read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_readonly.php
```

2. Ejecutar UAT estatico del SQL:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_sql_static.php
```

3. Aplicar DDL con token, confirmacion y respaldo.
4. Repetir UAT read-only.
5. Crear folio UAT `RES-*` con token, confirmacion y respaldo.
6. Validar folio:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_folio_readonly.php --folio=RES-YYYYMMDD-####
```

7. Prevalidar folio antes de preparacion/envio:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_preparacion_envio_preflight.php --folio=RES-YYYYMMDD-####
```

8. Despues de `RES-T009`, prevalidar folio antes de recepcion/diferencias:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_recepcion_diferencias_preflight.php --folio=RES-YYYYMMDD-####
```

## Tokens futuros RES-T009/RES-T010

Preparar/enviar:

```text
ALMACEN_RESURTIDO_PREPARAR_ENVIAR_UAT
```

Confirmacion textual:

```text
AUTORIZO UAT PREPARAR ENVIAR RESURTIDO usando respaldo RUTA_O_REFERENCIA
```

Recibir:

```text
ALMACEN_RESURTIDO_RECIBIR_UAT
```

Confirmacion textual:

```text
AUTORIZO UAT RECIBIR RESURTIDO usando respaldo RUTA_O_REFERENCIA
```

Estado actual:

- Los arneses estan bloqueados por token/respaldo.
- Aun con token valido responden `implementacion_pendiente`.
- No mueven inventario hasta que se implemente y valide el backend real de `RES-T009` y `RES-T010`.

## Evidencia minima por folio/SKU

- Folio `RES-*`.
- Tienda destino.
- Almacen origen.
- SKU y cantidad solicitada.
- Estatus inicial `borrador` o `solicitado`.
- Sin preparacion.
- Sin envio.
- Sin recepcion.
- Sin diferencias.
- Sin movimientos de inventario.
- Sin afectacion POS/ecommerce.
- Preflight `RES-T009` ejecutado antes de cualquier preparacion/envio real.
- Preflight `RES-T010` ejecutado antes de cualquier recepcion real.

## Lo que queda despues del primer folio

- `RES-T009`: preparar y enviar con salida origen + entrada transito.
- `RES-T010`: recibir en tienda y registrar diferencias.
- `RES-T011`: politicas reales tienda/SKU.
- `RES-T012`: alertas persistentes de stock bajo.
