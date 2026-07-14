# ERP Almacen - Handoff pre-DDL Resurtido / Traspasos

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-13  
Modulo: ERP > Almacen > Resurtido / Traspasos entre tiendas  
Estado: listo para revision pre-DDL; sin escrituras de BD ejecutadas.

## Objetivo

Dejar consolidado el punto exacto del modulo antes de aplicar esquema real:

- Ya existe auditoria, propuesta de esquema, pantalla read-only, endpoints GET y UAT sin escritura.
- Ya existen arneses bloqueados para aplicar DDL, crear primer folio UAT, preparar/enviar y recibir.
- Aun no se aplico DDL.
- Aun no se creo ningun folio real `RES-*`.
- Aun no hay movimientos de inventario por resurtido.
- Aun no hay integracion con POS/ecommerce.

## Decision operativa vigente

Se eligio flujo robusto de solicitud de resurtido + traspaso documental, no traspaso directo simple.

Motivo:

- Permite que tienda solicite y almacen central autorice.
- Separa solicitado, autorizado, preparado, enviado, recibido, cerrado y cancelado.
- Conserva lote, caducidad, ubicacion, unidad fisica y trazabilidad.
- Permite registrar faltantes, dano, lote distinto o caducidad distinta al recibir.
- Permite transito real entre salida de origen y confirmacion de tienda.
- Deja espacio para alertas por stock bajo tienda/SKU sin mezclar POS/ecommerce.

## Hallazgos consolidados

| ID | Hallazgo | Estado |
| --- | --- | --- |
| `RES-H001` | El traspaso actual de Inventario es directo y no modela solicitud/autorizacion/preparacion/transito/recepcion. | Documentado |
| `RES-H002` | No existian tablas de solicitud de resurtido multi-tienda. | Propuesta DDL lista |
| `RES-H003` | Los almacenes/tiendas ya existen en `erp_almacenes`; Acuario y Mascotas pueden operar como tiendas destino. | Validado read-only |
| `RES-H004` | No hay politicas min/max/reorden por tienda/SKU; solo se usa fallback global de catalogo. | Contrato listo |
| `RES-H005` | El flujo actual no registra diferencias de recepcion por faltante, dano, lote o caducidad. | Contrato listo |
| `RES-H006` | El flujo robusto requiere tabla de transito y recepcion para no afectar tienda hasta confirmar llegada. | Propuesta DDL lista |

## Archivos principales creados o actualizados

- `docs/erp_almacen_resurtido_traspasos_arranque.md`
- `docs/erp_almacen_resurtido_traspasos_tareas.md`
- `docs/erp_almacen_resurtido_traspasos_schema_propuesta.sql`
- `docs/erp_almacen_resurtido_schema_solicitud_autorizacion.md`
- `docs/erp_almacen_resurtido_schema_runbook_aplicacion.md`
- `docs/erp_almacen_resurtido_schema_plan_reversa.md`
- `docs/erp_almacen_resurtido_estados_permisos.md`
- `docs/erp_almacen_resurtido_paquete_autorizacion_uat.md`
- `app/controladores/Almacen.php`
- `app/modelos/Almacenes.php`
- `app/modelos/AlmacenEsquema.php`
- `app/vistas/paginas/apps/erp/almacen/resurtido.php`
- `public/assets/js/custom/apps/erp/almacen/resurtido/resurtido.js`

## Tablas propuestas

- `erp_inventario_politicas_almacen_sku`
- `erp_almacen_resurtidos`
- `erp_almacen_resurtido_detalle`
- `erp_almacen_resurtido_preparacion`
- `erp_almacen_resurtido_envios`
- `erp_almacen_resurtido_recepciones`
- `erp_almacen_resurtido_diferencias`

## Endpoints actuales

Read-only:

- `Almacen::resurtido()`
- `Almacen::resurtido_stock_bajo_preflight_erp()`
- `Almacen::resurtido_listar_erp()`
- `Almacen::resurtido_consultar_erp()`
- `Almacen::resurtido_simular_solicitud_erp()`
- `Almacen::resurtido_resumen_tiendas_erp()`
- `Almacen::resurtido_validar_solicitud_erp()`
- `Almacen::resurtido_payload_solicitud_erp()`
- `Almacen::resurtido_estados_erp()`
- `Almacen::resurtido_preparacion_envio_contrato_erp()`
- `Almacen::resurtido_plan_preparacion_erp()`
- `Almacen::resurtido_recepcion_diferencias_contrato_erp()`
- `Almacen::resurtido_politicas_alertas_contrato_erp()`

Guardado bloqueado por esquema/autorizacion operativa:

- `Almacen::resurtido_guardar_erp()`
- `Almacen::resurtido_autorizar_erp()`
- `Almacen::resurtido_cancelar_erp()`
- `Almacen::resurtido_preparar_enviar_erp()`
- `Almacen::resurtido_recibir_erp()`
- `Almacen::resurtido_politica_guardar_erp()`

Nota: no invocar los POST desde UI mientras no exista DDL aplicado y respaldo externo autorizado. Los endpoints de autorizar, cancelar y guardar politicas quedan listos para UAT real post-DDL; antes del DDL devuelven `schema_pendiente`. Preparar/enviar y recibir siguen devolviendo `implementacion_pendiente` y no mueven inventario.

## UAT sin escritura

Comandos seguros:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_readonly.php
```

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_autorizacion_preflight.php
```

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_sql_static.php
```

Resultado esperado actual:

- `ok=true`.
- `read_only=true` cuando aplique.
- Tablas pendientes: 7 antes del DDL.
- Stock bajo Acuario: datos consultables.
- Simulacion de solicitud: disponible sin guardar.
- Validacion/payload: disponibles sin POST.
- Contratos de estados, preparacion/envio, recepcion/diferencias y politicas: disponibles.
- Plan read-only de preparacion FEFO: disponible sin apartar stock ni mover unidades.
- Payload read-only de preparacion/envio: disponible como contrato de POST futuro sin ejecutar acciones.
- Backend pendiente de preparar/enviar y recibir: bloqueado y sin movimientos.
- Backend de autorizar/cancelar y politicas tienda/SKU: listo post-DDL, bloqueado por `schema_pendiente` mientras falten tablas.
- Contrato read-only de acciones: UI/backend/UAT listan acciones futuras sin ejecutar POST.
- Arneses autorizados de autorizar/cancelar/politicas: bloqueados por token/respaldo e incluidos en preflight.
- SQL propuesto: validacion estatica disponible antes de cualquier DDL.

## Arneses bloqueados

Aplicar DDL:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_schema_apply_authorized.php --autorizar=ALMACEN_RESURTIDO_DDL --confirmacion="AUTORIZO DDL RESURTIDO ALMACEN usando respaldo RUTA_O_REFERENCIA" --respaldo=RUTA_O_REFERENCIA_RESPALDO
```

Crear primer folio UAT:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_guardar_authorized.php --autorizar=ALMACEN_RESURTIDO_GUARDAR_UAT --confirmacion="AUTORIZO UAT GUARDAR RESURTIDO usando respaldo RUTA_O_REFERENCIA" --respaldo=RUTA_O_REFERENCIA_RESPALDO --destino=4 --origen=3
```

Autorizar folio UAT:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_autorizar_authorized.php --autorizar=ALMACEN_RESURTIDO_AUTORIZAR_UAT --confirmacion="AUTORIZO UAT AUTORIZAR RESURTIDO usando respaldo RUTA_O_REFERENCIA" --respaldo=RUTA_O_REFERENCIA_RESPALDO --folio=RES-YYYYMMDD-#### --accion=autorizar
```

Cancelar folio UAT:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_cancelar_authorized.php --autorizar=ALMACEN_RESURTIDO_CANCELAR_UAT --confirmacion="AUTORIZO UAT CANCELAR RESURTIDO usando respaldo RUTA_O_REFERENCIA" --respaldo=RUTA_O_REFERENCIA_RESPALDO --folio=RES-YYYYMMDD-#### --motivo="Cancelacion UAT"
```

Politica tienda/SKU:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_politica_authorized.php --autorizar=ALMACEN_RESURTIDO_POLITICA_UAT --confirmacion="AUTORIZO UAT POLITICA RESURTIDO usando respaldo RUTA_O_REFERENCIA" --respaldo=RUTA_O_REFERENCIA_RESPALDO --almacen=4 --sku=1
```

Preparar/enviar futuro:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_preparar_enviar_authorized.php --autorizar=ALMACEN_RESURTIDO_PREPARAR_ENVIAR_UAT --confirmacion="AUTORIZO UAT PREPARAR ENVIAR RESURTIDO usando respaldo RUTA_O_REFERENCIA" --respaldo=RUTA_O_REFERENCIA_RESPALDO --folio=RES-YYYYMMDD-####
```

Recibir futuro:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_recibir_authorized.php --autorizar=ALMACEN_RESURTIDO_RECIBIR_UAT --confirmacion="AUTORIZO UAT RECIBIR RESURTIDO usando respaldo RUTA_O_REFERENCIA" --respaldo=RUTA_O_REFERENCIA_RESPALDO --folio=RES-YYYYMMDD-####
```

Los dos ultimos arneses aun responden `implementacion_pendiente`; existen para fijar contrato y evitar escrituras accidentales.

## Secuencia recomendada

1. Ejecutar UAT read-only.
2. Ejecutar UAT estatico del SQL.
3. Confirmar respaldo externo fuera de esta carpeta de proyecto.
4. Aplicar DDL especifico de resurtido con token y confirmacion textual.
5. Repetir UAT read-only.
6. Crear un folio UAT controlado con destino Acuario o Mascotas.
7. Validar el folio con:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_folio_readonly.php --folio=RES-YYYYMMDD-####
```

8. Revisar plan de preparacion FEFO por folio/tienda antes de confirmar movimientos.

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_plan_preparacion_readonly.php --folio=RES-YYYYMMDD-####
```

9. Revisar payload de preparacion/envio antes de confirmar movimientos.

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_resurtido_payload_preparacion_envio_readonly.php --folio=RES-YYYYMMDD-####
```

10. Implementar `RES-T009`: preparacion/envio con salida de origen y entrada a transito.
11. Implementar `RES-T010`: recepcion tienda y diferencias.
12. Ejecutar UAT de politicas tienda/SKU post-DDL si se requiere min/max/reorden local antes de movimientos.
13. Implementar `RES-T012`: alertas persistentes de stock bajo.

## Guardrails vivos

- No ejecutar DDL sin respaldo externo y autorizacion textual.
- No ejecutar UAT con escritura sin respaldo externo.
- No mover inventario antes de `RES-T009`.
- No tocar POS/ecommerce.
- No mezclar endpoints legacy de Inventario.
- No usar auditoria como bandeja de pendientes.
- Documentar cada cierre por folio/SKU/almacen.
