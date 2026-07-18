# ERP Listas de precios - Guardrails de scripts apply

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-17  
Estado: evidencia read-only; no ejecuta escrituras.

## Objetivo

Comprobar que los scripts de escritura controlada para listas por segmento CRM quedan bloqueados si no reciben token explicito y respaldo externo valido.

## Scripts verificados

### 1. Sembrar segmentos CRM base

Comando probado sin argumentos:

```powershell
C:\xampp\php\php.exe storage\uat\uat_crm_segmentos_catalogo_apply_authorized.php
```

Resultado:

- `ok=false`
- `modo=bloqueado`
- mensaje: falta token o respaldo valido
- token requerido: `CRM_CLIENTES_SEGMENTO_CATALOGO`
- no asigna clientes, listas ni modifica ventas pasadas

### 2. Crear tabla puente segmento/lista

Comando probado sin argumentos:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_schema_apply_authorized.php
```

Resultado:

- `ok=false`
- `modo=bloqueado`
- mensaje: falta token o respaldo valido
- token requerido: `VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL`
- no crea segmentos, no asigna clientes, no modifica precios ni ventas pasadas

### 3. Vincular lista con segmento

Comando probado sin argumentos:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmento_vinculo_apply_authorized.php
```

Resultado:

- `ok=false`
- `modo=bloqueado`
- mensaje: falta token o respaldo valido
- token requerido: `VENTAS_LISTAS_PRECIOS_SEGMENTO_ASIGNAR_REAL`
- no crea segmentos, no modifica clientes, precios ni ventas pasadas

### 4. Asignar cliente a segmento

Comando probado sin argumentos:

```powershell
C:\xampp\php\php.exe storage\uat\uat_crm_cliente_segmento_apply_authorized.php
```

Resultado:

- `ok=false`
- `modo=bloqueado`
- mensaje: falta token o respaldo valido
- token requerido: `CRM_CLIENTES_SEGMENTO`
- no modifica listas, precios, POS ni ventas pasadas

## Conclusiones

- Todos los apply quedan bloqueados por defecto.
- Todos requieren token especifico por paso.
- Todos requieren respaldo externo valido.
- Los placeholders de respaldo quedan bloqueados.
- El flujo permite ejecutar preflights read-only antes de pedir autorizacion real.

## Comando de readiness relacionado

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_runbook_readiness_readonly.php
```

## Semaforo relacionado

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_go_nogo_readonly.php
```
