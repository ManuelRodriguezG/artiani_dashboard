# CRM Clientes - Runbook aplicacion DDL base

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-29  
Estado: runbook preparado; no ejecutar sin autorizacion.

## Precondiciones

- MySQL operativo.
- Respaldo externo reciente y legible.
- Autorizacion textual del dueno.
- Token exacto `CRM_CLIENTES_DDL_BASE`.

## Paso 1 - Auditoria previa

```text
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_auditoria_readonly.php
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_duplicados_readonly.php --limite=20
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_schema_readonly.php
```

Validar:

- El script de schema muestra `ddl_total=12`.
- Si ya hubiera tablas creadas, revisar que sean del mismo contrato.
- Duplicados legacy quedan documentados, pero no bloquean crear esquema.

## Paso 2 - Apply autorizado

```text
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_schema_apply_authorized.php --autorizar=CRM_CLIENTES_DDL_BASE --respaldo="C:\ruta\externa\respaldo.sql"
```

## Paso 3 - Verificacion posterior

```text
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_schema_readonly.php
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_migracion_plan_readonly.php
```

Validar:

- Las 12 tablas existen.
- No se crearon clientes CRM automaticamente.
- No cambio el conteo de `crm_clientes`, `erp_clientes` ni `erp_ventas`.

## Paso 4 - Siguiente fase

No migrar todavia. La fase posterior debe preparar un migrador separado que:

- respete duplicados;
- cree vinculos externos;
- deje clientes ambiguos en calidad `revisar`;
- genere cola de fusion;
- conserve snapshot de ventas.

## Señales para detenerse

Detener si:

- el respaldo no valida;
- MySQL reporta error DDL;
- alguna tabla CRM ya existe con estructura distinta;
- aparece una tabla antigua con el mismo nombre esperado;
- el conteo de tablas legacy cambia durante el apply.
