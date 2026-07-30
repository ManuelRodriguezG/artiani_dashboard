# Solicitud de autorizacion - Alineacion BD TMS logistica pura

Fecha: 2026-07-29

## Objetivo

Alinear la base de datos TMS con la decision vigente: TMS solo administra compromiso logistico y no debe conservar valores base ligados a venta, postventa, garantia o revision de producto.

Esta solicitud no autoriza ejecucion por si misma; deja preparado el alcance para una autorizacion futura.

## Token propuesto

```text
TMS_LOGISTICA_PURA_BASE
```

## Respaldo requerido

Antes de cualquier escritura o DDL:

```text
C:\xampp\panel_db_backups\artianilocal_panel_YYYYMMDD_antes_tms_logistica_pura.sql
```

## Frase de autorizacion futura

```text
AUTORIZO ALINEAR BD TMS LOGISTICA PURA usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_YYYYMMDD_antes_tms_logistica_pura.sql con token TMS_LOGISTICA_PURA_BASE.
```

## Alcance permitido futuro

- Cambiar default de `erp_tms_servicios.motivo_logistico` a `servicio_inicial` si todavia conserva valor anterior.
- Actualizar filas existentes con `motivo_logistico=venta_inicial` a `servicio_inicial`.
- Si existieran filas con origenes/tipos deprecados, dejarlas reportadas para decision manual antes de migrarlas.

## Fuera de alcance

- No tocar POS/Ventas.
- No tocar caja.
- No tocar inventario.
- No tocar postventa/garantias.
- No borrar servicios TMS.
- No cambiar folios TMS.
- No reescribir evidencias.

## Preflight requerido

Ejecutar antes de pedir autorizacion:

```text
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_logistica_pura_readonly.php
```

Debe indicar si la BD ya esta alineada o si requiere token `TMS_LOGISTICA_PURA_BASE`.

## Resultado preflight 2026-07-29

- Estado: `logistica_pura_codigo_listo_bd_pendiente`.
- Checks codigo/contrato: 6/6.
- Pendiente: default `erp_tms_servicios.motivo_logistico` conserva `venta_inicial`.
- Pendiente: 1 fila TMS conserva `motivo_logistico=venta_inicial`.
- Script autorizado probado sin token: bloqueado correctamente.

## Resultado aplicacion autorizada 2026-07-29

Autorizacion recibida:

```text
AUTORIZO ALINEAR BD TMS LOGISTICA PURA usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_YYYYMMDD_antes_tms_logistica_pura.sql con token TMS_LOGISTICA_PURA_BASE.
```

Respaldo real generado:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260729_antes_tms_logistica_pura.sql
```

Resultado:

- Estado final: `tms_logistica_pura_bd_alineada`.
- Default final: `servicio_inicial`.
- Filas `venta_inicial` despues: 0.
- Postcheck read-only: `logistica_pura_completa`.
- No toca POS/Ventas, caja, inventario ni postventa.
