# ERP TMS Delivery - Plan de reversa DDL

Documentacion IA: Codex GPT-5  
Fecha base: 2026-07-24  
Estado: plan preventivo; no ejecutar sin autorizacion explicita.

## Criterio

La reversa de DDL es destructiva y solo debe considerarse inmediatamente despues de aplicar el esquema, antes de crear servicios logisticos reales.

Si ya existen servicios TMS reales, eventos, costos o evidencias, no se debe borrar el esquema. Debe usarse baja logica o migracion controlada.

## Tablas en orden de borrado

Si no existen servicios reales o se confirma que todo sigue vacio:

1. `erp_tms_evidencias`
2. `erp_tms_eventos`
3. `erp_tms_servicios_costos`
4. `erp_tms_servicios_detalle`
5. `erp_tms_servicios`

## Preflight obligatorio

Antes de considerar reversa, consultar conteos:

```sql
SELECT COUNT(*) FROM erp_tms_servicios;
SELECT COUNT(*) FROM erp_tms_servicios_detalle;
SELECT COUNT(*) FROM erp_tms_servicios_costos;
SELECT COUNT(*) FROM erp_tms_eventos;
SELECT COUNT(*) FROM erp_tms_evidencias;
```

Todos deben ser `0`.

## Alcance prohibido

La reversa no debe tocar:

- ventas;
- productos;
- inventario;
- garantias;
- CRM;
- caja;
- permisos `tms.*`;
- notificaciones.

## Autorizacion futura requerida

No hay token activo para reversa. Si llegara a requerirse, preparar una solicitud separada con respaldo externo reciente y evidencia de que las tablas estan vacias.
