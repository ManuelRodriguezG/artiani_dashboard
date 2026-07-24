# ERP TMS Delivery - Solicitud de autorizacion DDL

Documentacion IA: Codex GPT-5  
Fecha base: 2026-07-24  
Estado: solicitud preparada; no ejecutada.

## Objetivo

Crear el esquema base de TMS Delivery para servicios logisticos independientes, usando tablas `erp_tms_*`.

## Tablas incluidas

- `erp_tms_servicios`
- `erp_tms_servicios_detalle`
- `erp_tms_servicios_costos`
- `erp_tms_eventos`
- `erp_tms_evidencias`

## Alcance permitido

La aplicacion DDL puede:

- crear tablas TMS si no existen;
- crear indices de consulta por folio, estado, cobro, fecha, cliente, responsable y origen solicitante;
- crear FKs internas entre tablas TMS.

## Alcance prohibido

Esta autorizacion no permite:

- crear servicios logisticos reales;
- modificar ventas;
- cancelar ventas;
- mover inventario;
- resolver garantias;
- crear productos/SKUs de envio;
- sincronizar permisos `tms.*`;
- asignar usuarios a roles;
- integrar POS/ecommerce/postventa con guardado real.

## Dependencias recomendadas

Antes de aplicar DDL, conviene tener permisos `tms.*` sincronizados con autorizacion separada:

- `docs/erp_tms_delivery_permisos_solicitud_autorizacion.md`

No es una dependencia tecnica estricta para crear tablas, pero si para probar UI desde navegador con usuario normal.

## Validacion previa recomendada

```powershell
C:\xampp\php\php.exe -l app\modelos\TmsEsquema.php
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_schema_readonly.php
```

Debe confirmar que el plan es read-only y que los pendientes corresponden a tablas `erp_tms_*`.

## Texto de autorizacion futura

Usar este texto sustituyendo la ruta de respaldo real:

```text
AUTORIZO CREAR ESQUEMA TMS DELIVERY usando respaldo [RUTA_RESPALDO] con token TMS_DELIVERY_DDL_BASE. Entiendo que solo crea tablas erp_tms_* para servicios logisticos independientes, no crea servicios reales, no modifica ventas, productos, garantias, inventario, caja, clientes ni permisos.
```

## Verificacion posterior esperada

```powershell
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_schema_readonly.php
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_dryrun_readonly.php
```

Resultados esperados:

- `ddl_pendientes=0`.
- `servicios_listar_erp` o modelo equivalente devuelve lista vacia si no hay servicios.
- dry-run de solicitud valida contrato sin crear servicio.
- No existen ventas, movimientos de inventario ni garantias nuevas por esta aplicacion.

## Handoff

El DDL TMS debe aplicarse separado de permisos y separado del primer servicio real. Despues del DDL, el siguiente avance seguro es habilitar guardado real `TMS-T007` con dry-run previo y auditoria, todavia sin integrar POS.
