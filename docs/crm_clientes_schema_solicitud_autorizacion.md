# CRM Clientes - Solicitud de autorizacion DDL base

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-29  
Estado: pendiente de autorizacion del dueno.

## Objetivo

Crear el esquema canonico CRM Clientes sin migrar datos legacy, sin tocar POS, sin tocar ventas y sin vincular ecommerce.

## Alcance autorizado por este token

Token requerido:

```text
CRM_CLIENTES_DDL_BASE
```

Incluye crear, si no existen:

- `crm_clientes_maestro`
- `crm_clientes_identificadores`
- `crm_clientes_contactos`
- `crm_clientes_direcciones`
- `crm_clientes_fiscales`
- `crm_clientes_segmentos`
- `crm_clientes_segmentos_rel`
- `crm_clientes_consentimientos`
- `crm_clientes_notas`
- `crm_clientes_eventos`
- `crm_clientes_vinculos_externos`
- `crm_clientes_fusiones`

## Fuera de alcance

Este token no autoriza:

- migrar `crm_clientes` legacy;
- copiar `erp_clientes`;
- vincular clientes POS/ecommerce;
- fusionar duplicados;
- modificar `erp_ventas`;
- borrar tablas antiguas;
- crear datos semilla.

## Evidencia previa

Scripts read-only:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_auditoria_readonly.php
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_migracion_plan_readonly.php
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_duplicados_readonly.php --limite=10
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_schema_readonly.php
```

Estado observado:

- `crm_clientes` legacy: 244 registros.
- `erp_clientes`: 1 registro POS/UAT.
- `erp_ventas`: 4 registros, todos publico general con snapshot.
- DDL CRM pendiente: 12 tablas.
- Duplicados legacy detectados: 2 grupos por identificador.

## Comando autorizado propuesto

Reemplazar la ruta de respaldo por un respaldo externo real y reciente:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_schema_apply_authorized.php --autorizar=CRM_CLIENTES_DDL_BASE --respaldo="C:\ruta\externa\respaldo.sql"
```

## Frase sugerida de autorizacion

```text
AUTORIZO CREAR ESQUEMA CRM CLIENTES BASE usando respaldo [RUTA_RESPALDO] con token CRM_CLIENTES_DDL_BASE. Entiendo que solo crea tablas crm_clientes_* y no migra clientes legacy, POS ni ecommerce.
```

## Verificacion posterior

Despues del apply:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_schema_readonly.php
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_migracion_plan_readonly.php
```

Resultado esperado:

- `ddl_pendientes=0`.
- El plan de migracion ya no debe bloquear por falta de esquema canonico.
- Puede seguir bloqueando por duplicados legacy, lo cual es correcto.
