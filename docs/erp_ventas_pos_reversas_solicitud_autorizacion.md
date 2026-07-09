# Solicitud de autorizacion - DDL reversas POS

Fecha: 2026-06-30

## Objetivo

Aplicar la evolucion de esquema requerida para que Ventas/POS pueda registrar devoluciones y cancelaciones reales sin borrar historial, con trazabilidad de caja, decision financiera, reembolso, saldo a favor e inventario destino.

## Alcance autorizado propuesto

- Agregar columnas e indices a:
  - `erp_ventas_devoluciones`;
  - `erp_ventas_devoluciones_detalle`.
- Preparar soporte para:
  - caja, almacen y turno de la reversa;
  - movimiento de caja asociado al reembolso;
  - decision financiera: `reembolso_caja`, `saldo_favor`, `cambio_producto`, `sin_reembolso`;
  - monto reembolsado;
  - monto en saldo a favor;
  - usuario autorizador y usuario aplicador;
  - snapshot historico;
  - existencia, almacen destino e importe por partida.

## Fuera de alcance

- No crear devoluciones reales.
- No reembolsar dinero.
- No mover inventario.
- No cambiar estatus de ventas.
- No tocar ecommerce.
- No ejecutar garantias.
- No crear saldos a favor reales todavia.

## Evidencia previa

- Dry-run de devolucion POS valido para `POS-20260629-000003`.
- Reembolso estimado `295`.
- Readiness DDL bloqueado por columnas/indices faltantes.
- Readiness de caja bloqueado porque el turno original ya esta cerrado para reembolso de caja.

## Script preparado

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_schema_apply_authorized.php --autorizar=VENTAS_POS_REVERSA_DDL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql
```

## Texto de autorizacion requerido

```text
AUTORIZO APLICAR DDL REVERSAS POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_REVERSA_DDL para UAT POS
```

## Validacion posterior esperada

- `storage/uat/uat_ventas_pos_reversa_schema_readonly.php` debe reportar columnas/indices sin faltantes.
- `storage/uat/uat_ventas_pos_reversa_readiness_readonly.php` debe dejar de bloquear por DDL.
- Si se prueba `decision_financiera=reembolso_caja`, seguira requiriendo turno/caja abierto.
- Si se prueba `decision_financiera=saldo_favor`, no debe exigir movimiento de caja inmediato, pero la reversa real seguira pendiente de aplicador autorizado.
