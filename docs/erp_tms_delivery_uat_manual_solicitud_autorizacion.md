# ERP TMS Delivery - Solicitud de autorizacion UAT manual

Documentacion IA: Codex GPT-5  
Fecha base: 2026-07-24  
Estado: solicitud preparada; no ejecutada.

## Objetivo

Ejecutar una prueba controlada de servicio manual TMS despues de tener permisos `tms.*` y tablas `erp_tms_*` aplicadas.

## Alcance permitido

La prueba puede:

- crear un servicio logistico TMS de prueba;
- crear detalle logistico de prueba;
- crear costo logistico de prueba;
- registrar eventos TMS del ciclo operativo;
- registrar una evidencia textual TMS;
- validar que el servicio se cierre como entrega completa.

## Alcance prohibido

Esta autorizacion no permite:

- modificar ventas;
- cancelar ventas;
- crear venta POS;
- crear pedido ecommerce;
- mover inventario;
- resolver garantias;
- crear productos o SKUs;
- sincronizar permisos;
- ejecutar DDL;
- asignar usuarios a roles.

## Dependencias obligatorias

Antes de ejecutar este UAT deben estar completas:

- permisos TMS aplicados con `TMS_PERMISOS_BASE`;
- esquema TMS aplicado con `TMS_DELIVERY_DDL_BASE`;
- verificacion post-permisos en estado `permisos_tms_listos`;
- verificacion post-DDL en estado `schema_tms_listo`;
- respaldo externo nuevo fuera del repo.

## Validacion previa recomendada

```powershell
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_go_nogo_readonly.php
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_permisos_postapply_readonly.php
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_schema_postapply_readonly.php
```

## Texto de autorizacion futura

Usar este texto sustituyendo la ruta de respaldo real:

```text
AUTORIZO EJECUTAR UAT MANUAL TMS DELIVERY usando respaldo [RUTA_RESPALDO] con token TMS_UAT_SERVICIO_MANUAL. Entiendo que creara solo un servicio logistico TMS de prueba con eventos y evidencia textual, no modifica ventas, POS, productos, garantias, inventario, caja, clientes, permisos ni esquema.
```

## Comando futuro

```powershell
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_servicio_manual_apply_authorized.php --autorizar=TMS_UAT_SERVICIO_MANUAL --respaldo="[RUTA_RESPALDO]"
```

## Verificacion posterior esperada

- El script responde `ok=true`.
- Existe un folio TMS de prueba.
- Existen eventos TMS para el ciclo operativo.
- Existe evidencia textual TMS.
- No existen ventas nuevas por esta prueba.
- No existen movimientos de inventario por esta prueba.
- No existen cambios de garantia por esta prueba.

## Handoff

Despues de este UAT, el siguiente avance seguro es validar la UI con datos reales de prueba en `/tms/servicios`, `/tms/operacion`, `/tms/costos` y `/tms/reportes`. La integracion POS debe seguir fuera hasta confirmar que el flujo manual TMS funciona de punta a punta.
