# CRM Clientes - Solicitud de autorizacion migracion legacy

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-29  
Estado: autorizacion usada para lote `offset=20`, `limite=5`; nuevas migraciones requieren nuevo lote autorizado.

## Objetivo

Migrar clientes legacy hacia el CRM canonico de forma controlada, conservando vinculo externo auditable y bloqueando registros con duplicados no resueltos.

## Token requerido

```text
CRM_CLIENTES_MIGRACION_LEGACY
```

## Alcance propuesto

Este token autorizaria migrar solo registros marcados por el borrador como `migrable_borrador`.

Por cada cliente migrado:

- crear registro en `crm_clientes_maestro`;
- crear identificadores normalizados en `crm_clientes_identificadores`;
- crear vinculo auditable en `crm_clientes_vinculos_externos`;
- crear evento `migracion_legacy` en `crm_clientes_eventos`.

## Bloqueos obligatorios

No debe migrar registros cuando:

- el borrador indique `bloqueado_duplicado`;
- el registro no tenga identificador util;
- el identificador ya exista en CRM canonico;
- ya exista vinculo externo para la misma fuente legacy;
- falte respaldo externo verificable.

## Fuera de alcance

Este token no autoriza:

- fusionar clientes;
- resolver duplicados automaticamente;
- borrar o modificar `crm_clientes` legacy;
- tocar ventas, POS, ecommerce, apartados, garantias o devoluciones;
- corregir codificacion historica de nombres;
- asignar listas de precios o recompensas.

## Evidencia read-only disponible

```text
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_migracion_borrador_readonly.php --offset=0 --limite=5
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_migracion_borrador_readonly.php --offset=20 --limite=5
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_duplicados_readonly.php
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_migracion_apply_authorized.php --offset=20 --limite=5
```

Resultados observados:

- lote inicial: 5 de 5 bloqueados por duplicado;
- lote posterior: 5 de 5 migrables en borrador;
- existen grupos duplicados legacy que requieren revision manual.
- apply sin token/respaldo queda bloqueado y no escribe BD.

## Comando autorizado propuesto

Ejemplo para migrar un lote previamente validado como 100% `migrable_borrador`:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_migracion_apply_authorized.php --autorizar=CRM_CLIENTES_MIGRACION_LEGACY --respaldo="[RUTA_RESPALDO]" --offset=[OFFSET] --limite=[LIMITE]
```

## Frase sugerida de autorizacion

```text
AUTORIZO MIGRAR CLIENTES LEGACY A CRM usando respaldo [RUTA_RESPALDO] con token CRM_CLIENTES_MIGRACION_LEGACY para migrar solo registros con estado migrable_borrador desde offset [OFFSET] y limite [LIMITE]. Entiendo que no fusiona duplicados, no modifica legacy, no toca ventas, POS ni ecommerce, y bloquea registros duplicados o ya vinculados.
```

## Verificacion posterior esperada

Despues de un apply autorizado:

- `crm_clientes_maestro` debe aumentar solo por migrables aplicados;
- `crm_clientes_identificadores` debe aumentar segun identificadores insertados;
- `crm_clientes_vinculos_externos` debe tener una relacion por registro migrado;
- `crm_clientes_eventos` debe registrar `migracion_legacy`;
- `crm_clientes` legacy debe conservar el mismo conteo;
- los grupos duplicados deben seguir sin fusionarse.

## Resultado lote autorizado 2026-06-29

Autorizacion recibida para:

```text
offset=20
limite=5
```

Resultado aplicado:

- migrados: 5;
- `crm_clientes_maestro=6`;
- `crm_clientes_identificadores=6`;
- `crm_clientes_eventos=6`;
- `crm_clientes_vinculos_externos=5`;
- `crm_clientes` legacy conserva 244 registros;
- `erp_clientes` conserva 1 registro;
- el mismo lote queda ahora en borrador como `bloqueado_vinculado`.
