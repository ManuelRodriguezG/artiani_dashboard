# ERP TMS Delivery - Runbook aplicacion DDL

Documentacion IA: Codex GPT-5  
Fecha base: 2026-07-24  
Estado: runbook preparado; no ejecutado.

## Paso 1 - Preflight read-only

Validar sintaxis y plan:

```powershell
C:\xampp\php\php.exe -l app\modelos\TmsEsquema.php
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_schema_readonly.php
```

Validar que:

- el script responde `modo=read-only`;
- no hay escrituras;
- `token_apply=TMS_DELIVERY_DDL_BASE`;
- los pendientes son solo tablas/columnas `erp_tms_*`.

## Paso 2 - Respaldo externo

Crear respaldo fuera del proyecto, siguiendo la ruta estandar documentada en:

```text
docs/erp_respaldo_bd_estandar.md
```

Ejemplo de ruta esperada:

```text
C:\xampp\panel_db_backups\panel_artianilocal_YYYYMMDD_antes_tms_delivery.sql
```

## Paso 3 - Autorizacion humana

Usar texto exacto:

```text
AUTORIZO CREAR ESQUEMA TMS DELIVERY usando respaldo [RUTA_RESPALDO] con token TMS_DELIVERY_DDL_BASE. Entiendo que solo crea tablas erp_tms_* para servicios logisticos independientes, no crea servicios reales, no modifica ventas, productos, garantias, inventario, caja, clientes ni permisos.
```

## Paso 4 - Aplicacion

La aplicacion debe ejecutarse mediante flujo autorizado preparado para TMS, no con SQL manual suelto.

Comando futuro sugerido cuando exista script apply:

```powershell
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_schema_apply_authorized.php --autorizar=TMS_DELIVERY_DDL_BASE --respaldo="[RUTA_RESPALDO]"
```

## Paso 5 - Verificacion posterior

```powershell
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_schema_readonly.php
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_dryrun_readonly.php
```

Criterios:

- `ddl_pendientes=0`;
- no se crearon servicios TMS;
- dry-run de solicitud sigue sin escritura;
- no se modificaron ventas, productos, garantias, inventario ni caja.

## Paso 6 - Siguiente avance

Despues del DDL:

1. Preparar `TMS-T007` guardado real de servicio manual.
2. Mantener integracion POS fuera hasta validar guardado manual y eventos.
3. Preparar notificaciones TMS solo despues de tener servicios reales.
