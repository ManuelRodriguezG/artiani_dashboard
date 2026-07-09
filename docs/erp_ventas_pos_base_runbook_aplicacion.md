# ERP Ventas/POS - Runbook de aplicacion base

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-26  
Estado: guia operativa previa a autorizacion; no implica ejecucion.

## Objetivo

Aplicar de forma controlada el alcance base de Ventas/POS/Pedidos:

- cajas POS;
- terminales POS;
- asignacion usuario/caja/terminal;
- turnos;
- ventas;
- detalle;
- pagos;
- trazabilidad venta-inventario;
- devoluciones base.

Este alcance no incluye clientes ERP, listas de precios, atenciones POS persistentes, eventos ni politicas de apartado. Esos quedan para alcance expandido.

## Precondiciones

- MySQL local activo.
- Respaldo externo validado:
  - `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`
- Plan de reversa leido:
  - `docs/erp_ventas_pos_base_plan_reversa.md`
- Usuario UAT confirmado:
  - `id_usuario=1`
- Preflight consolidado base en verde.
- Compatibilidad Catalogo/Inventario en verde.
- Autorizacion textual explicita del dueno antes de cualquier escritura.

## Preflights read-only

Validar respaldo:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_respaldo_preflight_readonly.php --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql"
```

Paquete base:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_paquete_autorizacion_dryrun.php --id_usuario=1 --alcance=base
```

Preflight consolidado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_base_autorizacion_preflight_readonly.php --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1
```

Compatibilidad Catalogo/Inventario:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_base_compatibilidad_readonly.php
```

## Autorizacion requerida

Frase sugerida:

`AUTORIZO CREAR ESQUEMA BASE ERP VENTAS POS PEDIDOS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`

## Aplicacion DDL base

No ejecutar sin la autorizacion anterior.

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_schema_apply_authorized.php --autorizar=VENTAS_POS_DDL_BASE --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql"
```

Criterio de exito:

- `ok=true`
- `modo=ddl_ejecutado`
- `alcance=base`

Punto de alto:

- Si falla cualquier tabla, no sembrar cajas.
- Revisar mensaje de error y conservar salida completa.

## Semillas POS base

No ejecutar si el DDL base no termino correctamente.

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_seed_apply_authorized.php --autorizar=VENTAS_POS_SEED --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1
```

Criterio de exito:

- `ok=true`
- `modo=semillas_ejecutadas`
- cajas esperadas:
  - `CJ-ACUARIO967-01`
  - `CJ-MASCOTAS971-01`
- terminales esperadas:
  - `TERM-ACUARIO967-01`
  - `TERM-MASCOTAS971-01`

Punto de alto:

- Si no existe asignacion activa para `id_usuario=1`, no abrir turno.

## Post-config read-only

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_config_readonly.php --id_usuario=1 --alcance=base
```

Criterio de exito:

- sin tablas base pendientes;
- cajas `>=2`;
- terminales `>=2`;
- asignaciones activas `>=2`;
- `asignacion_activa=true` para el usuario UAT.

## Turno UAT

Preflight:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_preflight_readonly.php --id_usuario=1 --monto_inicial=500
```

Abrir turno requiere autorizacion posterior separada:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_apertura_apply_authorized.php --autorizar=VENTAS_POS_TURNO_APERTURA --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --monto_inicial=500
```

## Venta UAT

Preflight:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_preflight_readonly.php --id_usuario=1
```

Venta real requiere autorizacion posterior separada:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_apply_authorized.php --autorizar=VENTAS_POS_VENTA_REAL --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1
```

## Evidencia minima

Registrar en `docs/erp_ventas_pos_evidencia_uat.md`:

- fecha/hora;
- usuario;
- sucursal/caja/terminal;
- folio de turno;
- folio de venta;
- SKU;
- cantidad;
- modo de salida;
- pago;
- movimiento de caja;
- movimiento de inventario;
- existencia anterior/nueva;
- unidad fisica anterior/nueva si aplica.

## Criterio para pasar a alcance expandido

No avanzar a clientes/listas/atenciones/apartados hasta validar:

- DDL base sin errores;
- semillas correctas;
- turno abierto/cerrable;
- venta UAT con kardex;
- post-venta por folio sin diferencias;
- UI POS operable por cajero.
