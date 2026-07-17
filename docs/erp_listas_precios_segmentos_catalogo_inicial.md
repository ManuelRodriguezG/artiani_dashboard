# Catalogo inicial sugerido - Segmentos CRM para Listas de precios

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-16  
Estado: propuesta operativa; no sembrada en BD.

## Objetivo

Definir segmentos comerciales iniciales para que Listas de precios escale por tipo de cliente, sin asignar listas cliente por cliente.

Estos segmentos base no deben quedar hardcodeados. Son un arranque sugerido de un catalogo configurable en CRM, donde se puedan crear, editar, pausar o cancelar segmentos conforme cambie la operacion.

## Principios

- CRM es dueno de los segmentos.
- Comercial/Listas solo vincula segmentos con listas autorizadas.
- POS no decide el segmento ni el precio; backend resuelve.
- Cambiar un segmento o una lista no debe modificar ventas pasadas.
- Una lista directa a cliente sigue siendo excepcion, no operacion masiva.

## Segmentos sugeridos fase 1

| Codigo | Nombre | Tipo | Uso sugerido |
| --- | --- | --- | --- |
| `PUBLICO_GENERAL` | Publico general | comercial | Cliente sin relacion recurrente o venta anonima. |
| `RECURRENTE` | Cliente recurrente | comercial | Compra frecuente con beneficio moderado. |
| `MAYOREO` | Mayoreo | comercial | Compra por volumen o cuenta comercial. |
| `VIP` | VIP autorizado | comercial | Cliente con mejores condiciones por autorizacion. |
| `INSTALADOR` | Instalador / tecnico | comercial | Cliente que compra para instalaciones o mantenimiento. |
| `CONVENIO` | Convenio especial | comercial | Acuerdo negociado con vigencia y motivo. |
| `ECOMMERCE_REG` | Ecommerce registrado | comercial | Cliente registrado de ecommerce futuro. |

## Reglas sugeridas

- `PUBLICO_GENERAL` no debe tener mejores precios que la lista general.
- `RECURRENTE` puede tener una lista moderada, con margen revisado.
- `MAYOREO`, `VIP` y `CONVENIO` requieren permiso/motivo para asignar listas.
- `ECOMMERCE_REG` no debe consumir listas internas POS salvo autorizacion explicita.
- Segmentos con prioridad alta deben tener vigencia o revision periodica.

## UAT minimo futuro

1. Crear segmento `RECURRENTE`.
2. Asignar cliente CRM UAT al segmento `RECURRENTE`.
3. Crear tabla puente `erp_segmentos_listas_precios` con respaldo y token.
4. Vincular una lista activa POS al segmento.
5. Resolver precio en POS dry-run con SKU `1760`.
6. Confirmar `regla_precio_origen=lista_segmento_cliente`.
7. Asignar lista directa al mismo cliente.
8. Confirmar que gana `lista_cliente`.
9. Pausar vinculo segmento/lista.
10. Confirmar caida a canal/almacen o general.

## Pendientes antes de sembrar

- Confirmar nombres comerciales reales.
- Confirmar si `PUBLICO_GENERAL` debe ser segmento CRM o solo ausencia de cliente.
- Definir quien puede mover clientes a `VIP`, `MAYOREO` o `CONVENIO`.
- Definir auditoria/evento CRM al cambiar segmento.

## Contrato tecnico preparado

- Pantalla CRM: `CRM > Clientes > Comercial > Segmentos configurables`.
- Listar segmentos: `/crm/segmentos_catalogo_listar_erp`
- Validar segmento sin escribir: `/crm/segmento_catalogo_dryrun_erp`
- Guardar segmento autorizado: `/crm/segmento_catalogo_guardar_autorizado_erp`
- Token de guardado: `CRM_CLIENTES_SEGMENTO_CATALOGO`
- UAT read-only: `storage/uat/uat_crm_segmentos_catalogo_readonly.php`
- Siembra CLI protegida: `storage/uat/uat_crm_segmentos_catalogo_apply_authorized.php`

La pantalla muestra los segmentos base como botones sugeridos para capturar mas rapido, pero no los siembra automaticamente. El guardado autorizado permite crear, editar, pausar o cancelar segmentos, pero no asigna clientes, no asigna listas y no modifica ventas pasadas.

## Siembra autorizada opcional

Con respaldo externo generado:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql
```

Comando protegido:

```powershell
C:\xampp\php\php.exe storage\uat\uat_crm_segmentos_catalogo_apply_authorized.php --autorizar=CRM_CLIENTES_SEGMENTO_CATALOGO --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql
```

Orden recomendado:

1. Ejecutar UAT read-only de segmentos CRM.
2. Sembrar segmentos base con token si se confirma que no existen.
3. Crear tabla puente `erp_segmentos_listas_precios` con token de DDL.
4. Vincular lista activa a segmento.
5. Validar resolutor backend con POS dry-run.

Comando protegido para vincular lista/segmento:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmento_vinculo_apply_authorized.php --autorizar=VENTAS_LISTAS_PRECIOS_SEGMENTO_ASIGNAR_REAL --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql --id_lista_precio=2 --codigo_segmento=RECURRENTE --canal=pos --id_almacen=5 --prioridad=100
```

Verificador read-only posterior:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmento_resolutor_readonly.php 2 RECURRENTE 1760 5
```

La siembra protegida de segmentos es idempotente: si el codigo ya existe, se actualiza ese segmento y no se duplica.

Cliente segmentado para UAT:

```powershell
C:\xampp\php\php.exe storage\uat\uat_crm_cliente_segmento_candidatos_readonly.php RECURRENTE 10
```

```powershell
C:\xampp\php\php.exe storage\uat\uat_crm_cliente_segmento_apply_authorized.php --autorizar=CRM_CLIENTES_SEGMENTO --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql --id_cliente_crm=1 --codigo_segmento=RECURRENTE --principal=1 --actualizar_default=1
```

Suite completa read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_suite_readonly.php 2 RECURRENTE 1760 5
```

Paquete de autorizacion read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_autorizacion_paquete_readonly.php
```

Este paquete no ejecuta comandos; solo valida respaldo, lista, cliente, segmento, tabla puente y devuelve el orden de apply recomendado.
