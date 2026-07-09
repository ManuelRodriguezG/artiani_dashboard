# Solicitud autorizacion - Permiso evidencias caja POS

Fecha: 2026-06-30

## Objetivo

Separar la revision de evidencias de caja POS del permiso generico `ventas.autorizar_excepcion_comercial`.

## Permiso propuesto

- Modulo: `ventas`
- Accion: `caja_evidencias_revisar`
- Permiso: `ventas.caja_evidencias.revisar`
- Descripcion: `Aprobar o rechazar evidencias de movimientos sensibles de caja POS`

## Roles base propuestos

- `direccion`
- `administrador_erp`

## Evidencia read-only

Script:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencias_permiso_readonly.php
```

Resultado:

- `permiso_existe=false`;
- faltante `permiso`;
- faltante `rol:administrador_erp`;
- faltante `rol:direccion`;
- requiere autorizacion.

## Guardrail

Script autorizado sin parametros:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencias_permiso_apply_authorized.php
```

Resultado:

- `ok=false`;
- `bloqueado=true`;
- no escribio BD.

## Autorizacion requerida

```text
AUTORIZO SEMBRAR PERMISO EVIDENCIAS CAJA POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIAS_PERMISO id_usuario=1 para UAT POS
```

## Impacto esperado

- Crea o actualiza `sys_permisos.ventas.caja_evidencias.revisar`.
- Asigna el permiso a roles `direccion` y `administrador_erp`.
- No aprueba evidencias.
- No modifica caja.
- No mueve dinero.
- No mueve inventario.
