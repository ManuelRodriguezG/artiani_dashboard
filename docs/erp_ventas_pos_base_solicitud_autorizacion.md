# ERP Ventas/POS - Solicitud de autorizacion base

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-26  
Estado: listo para decision del dueno; no ejecuta cambios.

## Decision solicitada

Autorizar o no la aplicacion del esquema base de Ventas/POS/Pedidos.

Alcance recomendado:

- `VENTAS_POS_DDL_BASE`

Este alcance crea el minimo operativo para probar:

- POS por sucursal/caja/terminal;
- asignacion usuario/caja/terminal;
- turno de caja;
- venta POS;
- pago;
- salida de inventario;
- kardex;
- trazabilidad venta-detalle-inventario;
- devoluciones base.

No incluye:

- clientes ERP;
- listas de precios;
- atenciones POS persistentes;
- eventos de apartados/abonos;
- politicas de apartado.

## Evidencia previa

Respaldo validado:

- `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`
- archivo existe y es legible;
- tamano: `27216189` bytes.

Readiness suite:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_base_readiness_suite_readonly.php --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1
```

Resultado:

- `ok=true`
- `ddl_base_total=11`
- `seed_cajas_total=2`
- `seed_terminales_total=2`
- `seed_asignaciones_total=2`
- `preflight_bloqueos=[]`
- `compatibilidad_bloqueos=[]`
- `guardrails_fallas=[]`

## Tablas base a crear

- `erp_pos_cajas`
- `erp_pos_terminales`
- `erp_pos_usuarios_cajas`
- `erp_pos_turnos`
- `erp_pos_movimientos_caja`
- `erp_ventas`
- `erp_ventas_detalle`
- `erp_ventas_detalle_inventario`
- `erp_ventas_pagos`
- `erp_ventas_devoluciones`
- `erp_ventas_devoluciones_detalle`

## Semillas posteriores al DDL

Si el DDL base termina correctamente, el siguiente paso sera sembrar:

Cajas:

- `CJ-ACUARIO967-01`
- `CJ-MASCOTAS971-01`

Terminales:

- `TERM-ACUARIO967-01`
- `TERM-MASCOTAS971-01`

Asignaciones:

- `id_usuario=1` asignado a ambas terminales/cajas para UAT.

## Comando DDL base

No ejecutar sin autorizacion textual.

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_schema_apply_authorized.php --autorizar=VENTAS_POS_DDL_BASE --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql"
```

## Frase exacta para autorizar

Usar esta frase si se aprueba aplicar el esquema base:

`AUTORIZO CREAR ESQUEMA BASE ERP VENTAS POS PEDIDOS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`

## Despues del DDL

Si el DDL responde `ok=true`, ejecutar semillas:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_seed_apply_authorized.php --autorizar=VENTAS_POS_SEED --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1
```

Despues validar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_config_readonly.php --id_usuario=1 --alcance=base
```

## Puntos de alto

Detenerse y no ejecutar la siguiente fase si:

- DDL devuelve `ok=false`;
- falta cualquier tabla base;
- semillas devuelven `ok=false`;
- no queda asignacion activa para `id_usuario=1`;
- aparece cualquier inconsistencia en caja/terminal/turno.

## Plan de reversa

Leer antes de autorizar:

- `docs/erp_ventas_pos_base_plan_reversa.md`

Regla:

- preferir restaurar respaldo completo ante inconsistencia;
- no ejecutar limpieza manual ni `DROP TABLE` sin autorizacion posterior expresa.

## Decision

Marcar una:

- [ ] Autorizar DDL base ahora.
- [ ] No autorizar todavia; seguir revisando.
- [ ] Separar mas el alcance antes de aplicar.
