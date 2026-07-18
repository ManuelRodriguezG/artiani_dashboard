# ERP Listas de precios - Runbook de activacion por segmentos CRM

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-17  
Estado: runbook operativo; no ejecuta BD.

## Objetivo

Activar listas de precios por segmento/tipo de cliente CRM de forma controlada, con respaldo externo, tokens por paso, UAT read-only y posibilidad de detenerse sin afectar ventas pasadas.

## Alcance

Este runbook cubre:

- sembrar segmentos CRM base configurables;
- crear la tabla puente `erp_segmentos_listas_precios`;
- vincular una lista de precios con un segmento CRM;
- asignar un cliente UAT al segmento;
- validar que el resolutor backend pueda devolver `lista_segmento_cliente`.

No cubre promociones, recompensas, ecommerce activo ni descuentos manuales.

## Estado actual validado

Respaldo externo validado:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql
```

Estado read-only actual:

- Respaldo existe, es legible y pesa `28578042` bytes.
- Lista UAT `2` existe y esta activa.
- Cliente CRM UAT `1` existe.
- Cliente CRM `2` fue identificado como candidato limpio para UAT de segmento: activo, sin lista directa y sin lista default.
- Segmento `RECURRENTE` no existe todavia.
- Tabla `erp_segmentos_listas_precios` no existe todavia.
- El resolutor POS sigue funcionando por asignacion directa de cliente (`lista_cliente`).
- Con cliente CRM `2`, SKU `1760`, almacen `5`, canal `pos`, el resolutor cae actualmente a `lista_canal_sucursal`; esto es correcto antes de asignar segmento.

## Preflight obligatorio

Validar primero que el runbook, scripts, tokens y respaldo esten completos:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_runbook_readiness_readonly.php
```

Debe devolver `ok=true`, sin archivos faltantes ni tokens faltantes.

Validar semaforo go/no-go:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_go_nogo_readonly.php
```

Debe devolver `decision=GO_PARA_PEDIR_AUTORIZACION`. Es normal que `segmento_crm` y `tabla_puente` aparezcan pendientes antes del apply; esos son los pasos autorizados 1 y 2.

Tomar linea base read-only de ventas/snapshots:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_ventas_impacto_readonly.php
```

Despues del apply de segmentos, los conteos de `erp_ventas`, `erp_ventas_detalle` y los maximos de id no deben cambiar por esta activacion.

Comparar baseline despues del apply:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_ventas_baseline_compare_readonly.php --ventas_total=22 --ventas_max_id=25 --detalle_total=23 --detalle_max_id=26
```

Debe devolver `resultado=BASELINE_VENTAS_INTACTA`.

Evidencia de bloqueo por defecto:

```text
docs/erp_listas_precios_segmentos_guardrails_apply.md
```

Ejecutar primero:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_autorizacion_paquete_readonly.php
```

Debe devolver:

- `modo=read-only`;
- `validacion_respaldo.ok=true`;
- `cliente_existe=true`;
- `lista_consultada=true`;
- comandos en orden;
- verificaciones post-apply;
- `guardrails.no_ejecuta_comandos=true`.
- advertencia si el cliente UAT conserva lista directa activa y por eso no puede demostrar segmento puro.

Detenerse si:

- el respaldo no existe o no es legible;
- la lista UAT no existe;
- el cliente UAT no existe;
- algun script read-only marca error.

## Autorizacion explicita requerida

Antes de ejecutar cualquier `apply_authorized`, el dueno debe autorizar por escrito el alcance completo. Frase sugerida:

```text
Autorizo ejecutar los 4 pasos apply_authorized de listas por segmento CRM usando el respaldo C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql y detenerse si algun paso falla.
```

Esta autorizacion cubre:

- sembrar segmentos CRM base;
- crear `erp_segmentos_listas_precios`;
- vincular la lista `2` con el segmento `RECURRENTE`;
- asignar el cliente CRM `2` al segmento `RECURRENTE`.

No cubre:

- modificar ventas pasadas;
- crear promociones;
- activar ecommerce;
- asignar listas directas masivas a clientes.

## Orden autorizado

Ejecutar solo con autorizacion explicita y detenerse si un paso falla.

### 1. Sembrar segmentos CRM base

```powershell
C:\xampp\php\php.exe storage\uat\uat_crm_segmentos_catalogo_apply_authorized.php --autorizar=CRM_CLIENTES_SEGMENTO_CATALOGO --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql
```

Resultado esperado:

- crea o actualiza segmentos base sin duplicar codigos;
- no asigna clientes;
- no asigna listas;
- no modifica ventas.

### 2. Crear tabla puente segmento/lista

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_schema_apply_authorized.php --autorizar=VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql
```

Resultado esperado:

- crea `erp_segmentos_listas_precios`;
- crea indices por segmento, lista, alcance y vigencia;
- no crea segmentos;
- no crea listas;
- no cambia precios.

### 3. Vincular lista UAT con segmento `RECURRENTE`

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmento_vinculo_apply_authorized.php --autorizar=VENTAS_LISTAS_PRECIOS_SEGMENTO_ASIGNAR_REAL --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql --id_lista_precio=2 --codigo_segmento=RECURRENTE --canal=pos --id_almacen=5 --prioridad=100
```

Resultado esperado:

- crea vinculo activo segmento/lista;
- respeta canal `pos`;
- respeta almacen `5`;
- deja trazabilidad comercial.

### 4. Asignar cliente UAT al segmento

```powershell
C:\xampp\php\php.exe storage\uat\uat_crm_cliente_segmento_apply_authorized.php --autorizar=CRM_CLIENTES_SEGMENTO --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql --id_cliente_crm=2 --codigo_segmento=RECURRENTE --principal=1 --actualizar_default=1
```

Resultado esperado:

- cliente CRM `2` queda relacionado a `RECURRENTE`;
- `id_segmento_default` puede quedar actualizado;
- no asigna lista directa al cliente;
- no modifica ventas pasadas.

## UAT posterior

Para prueba pura de segmento usar cliente CRM `2`. Este es el caso principal porque no tiene lista directa ni lista default:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_suite_readonly.php 2 RECURRENTE 1760 5 2
```

Para comprobar que la prioridad sigue correcta cuando un cliente tiene lista directa, usar cliente CRM `1`. Debe ganar `lista_cliente`, no segmento:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_suite_readonly.php 2 RECURRENTE 1760 5 1
```

Para explicar la prioridad real del resolutor:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_prioridad_resolutor_readonly.php 2 1760 5 pos
```

Para cierre de aceptacion post-apply:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_post_apply_acceptance_readonly.php 2 RECURRENTE 2 1760 5 pos
```

Para cierre consolidado post-apply:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_post_apply_suite_readonly.php --id_lista_precio=2 --codigo_segmento=RECURRENTE --id_cliente_crm=2 --id_sku=1760 --id_almacen=5 --canal=pos --ventas_total=23 --ventas_max_id=26 --detalle_total=24 --detalle_max_id=27
```

Esperado despues de activar:

- `segmento_existe=true`;
- `tabla_puente_existe=true`;
- `dryrun_vinculo_sin_bloqueos=true`;
- resolutor ejecutado sin bloqueos;
- con cliente CRM `2`, una prueba sin lista directa debe resolver `regla_precio_origen=lista_segmento_cliente`.
- aceptacion post-apply debe devolver `resultado=PASS_POST_APPLY`.
- suite consolidada debe devolver `resultado=PASS_SUITE_POST_APPLY`.

Nota: si el cliente conserva una lista directa activa, debe ganar `lista_cliente`, porque tiene mayor prioridad que segmento. Estado validado actual: cliente UAT `1` tiene una lista directa activa a lista `2`, por lo que no sirve como prueba pura de segmento mientras esa asignacion exista.

## Evidencia de cierre 2026-07-17

Apply autorizado ejecutado:

- segmentos CRM base sembrados/actualizados;
- tabla `erp_segmentos_listas_precios` creada;
- vinculo activo creado: lista `2` + segmento `RECURRENTE` + canal `pos` + almacen `5`;
- cliente CRM `2` asignado a `RECURRENTE` y `id_segmento_default=2`.

Suite final:

```text
resultado=PASS_SUITE_POST_APPLY
acceptance_resultado=PASS_POST_APPLY
baseline_resultado=BASELINE_VENTAS_INTACTA
origen_resolutor=lista_segmento_cliente
```

Resolutor validado con cliente CRM `2`, SKU `1760`, almacen `5`, canal `pos`:

- `regla_precio_origen=lista_segmento_cliente`;
- `id_lista_precio=2`;
- `lista_precio_snapshot=Lista UAT borrador`;
- `precio_aplicado=315`.

Baseline vigente para cierre:

- `erp_ventas.total=23`;
- `erp_ventas.max_id=26`;
- `erp_ventas_detalle.total=24`;
- `erp_ventas_detalle.max_id=27`.

Nota operativa: la venta `POS-20260717-000002` fue registrada a las `22:20:20`, antes del primer apply autorizado de segmentos (`22:23:40`), por eso el baseline de cierre usa `23/26/24/27`.

## Revision visual

En `ERP > Comercial > Listas de precios`:

- el panel de preparacion debe mostrar catalogo CRM listo;
- relacion cliente/segmento lista;
- puente segmento/lista listo despues del DDL;
- el boton `Guardar vinculo` debe habilitarse solo cuando `schema.segmentos_listas=true`.

## Reversa

Antes del DDL, el preflight debe decir que no aplica reversa:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_reversa_preflight_readonly.php
```

Si ya hay datos reales, no usar borrado manual. La reversa recomendada es:

- pausar/cancelar vinculos con auditoria;
- validar resolutor POS;
- restaurar respaldo completo solo en ambiente controlado si es necesario.

Documento de reversa:

```text
docs/erp_listas_precios_segmentos_plan_reversa.md
```

## Criterio de cierre

La fase queda lista cuando:

- CRM tiene segmentos configurables;
- `erp_segmentos_listas_precios` existe;
- lista UAT se vincula a `RECURRENTE`;
- cliente UAT se asigna al segmento;
- POS dry-run resuelve precio desde backend;
- ventas pasadas permanecen sin cambios.
