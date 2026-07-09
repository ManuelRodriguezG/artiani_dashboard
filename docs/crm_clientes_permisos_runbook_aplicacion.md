# CRM Clientes - Runbook aplicacion permisos base

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-29  
Estado: pendiente de autorizacion del dueno.

## Proposito

Aplicar permisos minimos para operar `CRM > Clientes` sin tocar datos de clientes ni esquema CRM.

## Precondiciones

- Respaldo externo reciente disponible y verificable.
- Autorizacion textual del dueno con token `CRM_PERMISOS_BASE`.
- Revision previa de:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_permisos_readonly.php
C:\xampp\php\php.exe storage\uat\uat_crm_permisos_plan_readonly.php
```

## Ejecucion

```text
C:\xampp\php\php.exe storage\uat\uat_crm_permisos_apply_authorized.php --autorizar=CRM_PERMISOS_BASE --respaldo="C:\ruta\externa\respaldo.sql"
```

## Verificacion inmediata

```text
C:\xampp\php\php.exe storage\uat\uat_crm_permisos_readonly.php
```

Validar:

- `crm.ver`, `crm.crear`, `crm.editar`, `crm.fusionar`, `crm.auditoria` existen.
- El rol `crm` existe.
- Los roles base tienen los permisos CRM correspondientes.
- La pantalla `/crm/clientes` puede aparecer en sidebar para roles con `crm.ver`.

## Criterio de exito

- El modulo queda visible por permisos, pero sin habilitar escrituras CRM.
- No se crean clientes, no se migra legacy y no se modifica POS.

## Criterio de pausa

Pausar si:

- falta respaldo externo;
- el script reporta error en alguna sentencia;
- faltan roles base esperados;
- aparece cualquier modificacion fuera de permisos/roles CRM.
