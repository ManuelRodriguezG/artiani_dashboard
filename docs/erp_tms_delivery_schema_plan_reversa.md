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

## Preflight ejecutable read-only

Se preparo el diagnostico:

```powershell
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_reversa_preflight_readonly.php
```

Contrato:

- no ejecuta `DROP`;
- no borra datos;
- no toca ventas, garantias ni inventario;
- solo cuenta filas si las tablas existen;
- si MySQL no esta disponible, reporta `sin_conexion_mysql`.

## Resultado de validacion

2026-07-25:

- Validacion inicial: `sin_conexion_mysql`.
- Validacion posterior con MySQL levantado: `reversa_no_aplica_schema_pendiente`.
- Conexion MySQL: activa.
- Tablas TMS existentes: 0/5.
- Total filas TMS: 0.
- Reversa tecnica: no aplica porque el esquema TMS aun no existe.

Despues de aplicar DDL TMS:

- Validacion: `reversa_tecnicamente_viable_solo_con_autorizacion_futura`.
- Tablas TMS existentes: 5/5.
- Total filas TMS: 0.
- No hay token activo para reversa.
- Cualquier reversa futura requiere solicitud separada y respaldo nuevo.

Despues de ejecutar UAT manual TMS:

- Validacion: `reversa_bloqueada_hay_datos_tms`.
- Total filas TMS: 9.
- Servicios: 1.
- Detalle: 1.
- Costos: 1.
- Eventos: 5.
- Evidencias: 1.
- Regla: no borrar esquema TMS con datos existentes; usar baja logica o migracion controlada.

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
