# ERP Ventas/POS - Decisiones financieras de pedidos/apartados

Fecha: 2026-07-06

## Objetivo

Resolver formalmente pagos de pedidos/apartados cancelados, sin borrar pagos y sin usar movimientos sueltos de caja.

Caso UAT base:

- Folio: `APT-20260706-000002`.
- Estatus: `cancelado`.
- Pago previo: `$100.00`.
- Reserva liberada: `1`.
- Kardex salida: `0`.
- Monto pendiente de decision: `$100.00`.

## Problema operativo

`pedidoCancelarReal` hace bien la parte operativa:

- cancela el documento;
- libera reservas;
- no reembolsa automaticamente;
- no genera kardex de salida;
- registra evento `pedido_cancelado` con `decision_financiera_pendiente`.

Lo pendiente es decidir que hacer con el dinero ya recibido.

## Regla de arquitectura

No resolver el anticipo con un movimiento manual de caja.

Debe existir un expediente financiero ligado al folio cancelado, con decision, autorizacion, evidencia y trazabilidad.

## Decisiones permitidas

### `saldo_favor`

Usar cuando el cliente seguira comprando o cuando CRM ya pueda cargar saldos.

Requiere:

- cliente CRM o identificador estable;
- ledger formal de saldo a favor;
- referencia contra el folio cancelado;
- uso posterior trazable en una venta/pedido.

### `reembolso_caja`

Usar cuando se devuelve efectivo desde caja.

Requiere:

- turno abierto;
- supervisor/autorizacion;
- movimiento `erp_pos_movimientos_caja` tipo `reembolso`;
- pago tipo `reembolso`;
- evidencia de caja si politica lo exige.

### `penalizacion`

Usar cuando la politica de apartado permite retener parcial o totalmente el anticipo.

Requiere:

- politica vigente;
- monto penalizado;
- motivo;
- autorizacion;
- registro contable/gerencial futuro.

### `sin_reembolso`

Solo aplica cuando no hay pago previo o el monto neto a resolver es cero.

## Esquema propuesto

Tabla: `erp_ventas_pedidos_decisiones_financieras`

Columnas:

- `id_decision_financiera` BIGINT PK.
- `folio` VARCHAR(50) unico.
- `id_venta` BIGINT.
- `folio_venta` VARCHAR(40).
- `tipo_documento` VARCHAR(30).
- `id_cliente_crm` BIGINT NULL.
- `cliente_snapshot` TEXT NULL.
- `decision` VARCHAR(40): `saldo_favor`, `reembolso_caja`, `penalizacion`, `sin_reembolso`.
- `monto_base` DECIMAL(18,6).
- `monto_saldo_favor` DECIMAL(18,6).
- `monto_reembolso` DECIMAL(18,6).
- `monto_penalizacion` DECIMAL(18,6).
- `id_turno_caja` BIGINT NULL.
- `id_caja` INT NULL.
- `id_almacen` INT NULL.
- `id_movimiento_caja` BIGINT NULL.
- `id_venta_pago` BIGINT NULL.
- `id_saldo_cliente_movimiento` BIGINT NULL.
- `estatus` VARCHAR(40): `pendiente`, `autorizada`, `aplicada`, `rechazada`, `cancelada`.
- `motivo` TEXT.
- `evidencia_referencia` VARCHAR(250) NULL.
- `datos_snapshot` TEXT.
- `solicitado_por` INT NULL.
- `autorizado_por` INT NULL.
- `aplicado_por` INT NULL.
- `fecha_solicitud` DATETIME.
- `fecha_autorizacion` DATETIME NULL.
- `fecha_aplicacion` DATETIME NULL.
- `fecha_actualizacion` DATETIME NULL.

Indices:

- unique `folio`.
- unique por `id_venta` para impedir doble resolucion financiera de un mismo cancelado.
- `estatus`, `decision`, `fecha_solicitud`.
- `id_turno_caja`, `id_caja`, `estatus`.
- `id_cliente_crm`, `estatus`.
- `id_movimiento_caja`.

## Contrato de negocio

- Solo se puede crear decision si `erp_ventas.estatus='cancelado'`.
- Solo aplica a `tipo_documento in ('pedido','apartado')`.
- `monto_base` debe coincidir con pagos registrados no resueltos.
- No se permite decision si hubo entrega/kardex de salida; eso pertenece a reversas/devoluciones.
- Una venta cancelada solo puede tener una decision financiera activa/aplicada.
- `reembolso_caja` requiere turno abierto de la caja/almacen del POS.
- `saldo_favor` requiere cliente CRM o identificador estable.
- `penalizacion` requiere politica y motivo.
- La aplicacion debe registrar evento en `erp_ventas_eventos`.

## Permisos propuestos

- `ventas.pedidos.finanzas.ver`.
- `ventas.pedidos.finanzas.solicitar`.
- `ventas.pedidos.finanzas.autorizar`.
- `ventas.pedidos.finanzas.aplicar`.
- `ventas.pedidos.finanzas.reembolso_caja`.
- `ventas.pedidos.finanzas.penalizacion`.

## Endpoints propuestos

- `pedido_finanzas_decision_dryrun_erp`.
- `pedido_finanzas_decision_solicitar_erp`.
- `pedido_finanzas_decision_autorizar_erp`.
- `pedido_finanzas_decision_aplicar_erp`.
- `pedido_finanzas_decision_consultar_erp`.

## UAT propuesta

### UAT-PED-FIN-001 Saldo a favor

- Cancelar apartado con anticipo.
- Resolver decision `saldo_favor`.
- Verificar que no mueva caja.
- Verificar ledger/saldo cliente cuando CRM este listo.

### UAT-PED-FIN-002 Reembolso caja

- Abrir turno.
- Resolver decision `reembolso_caja`.
- Verificar salida de caja.
- Verificar evidencia requerida.
- Cerrar turno con esperado reducido.

### UAT-PED-FIN-003 Penalizacion parcial

- Cancelar apartado con anticipo.
- Retener parte del anticipo por politica.
- Generar saldo/reembolso por remanente si aplica.
- Verificar auditoria gerencial.

### UAT-PED-FIN-004 Bloqueos

- Rechazar folio no cancelado.
- Rechazar folio entregado.
- Rechazar decision duplicada.
- Rechazar reembolso sin turno abierto.

## Estado actual

- Auditor read-only disponible:
  - `storage/uat/uat_ventas_pos_pedido_cancelado_finanzas_readonly.php`.
- Folio UAT validado:
  - `APT-20260706-000002`;
  - `monto_pendiente_decision=100`;
  - hallazgos `[]`.

## Hallazgo UAT 2026-07-06 - Saldo favor pendiente de CRM

- Decision POS registrada:
  - folio decision `PFIN-20260706-000001`;
  - folio apartado `APT-20260706-000002`;
  - decision `saldo_favor`;
  - monto `100`;
  - estatus `pendiente_ledger_crm`.
- Auditor read-only creado:
  - `storage/uat/uat_ventas_pos_pedidos_crm_ledger_readonly.php`.
- Preparacion CRM iniciada:
  - metodo `ClientesCrmEsquema::planActualizarSaldosClientesCrm`;
  - endpoint `/crm/esquema_plan_clientes_saldos_crm`;
  - endpoint protegido `/crm/esquema_actualizar_clientes_saldos_crm`;
  - scripts `storage/uat/uat_crm_clientes_saldos_schema_readonly.php` y `storage/uat/uat_crm_clientes_saldos_schema_apply_authorized.php`.
- Resultado:
  - no se puede convertir a saldo real todavia;
  - falta `id_cliente_crm` en el apartado cancelado;
  - falta esquema CRM de saldos/cuenta corriente (`crm_clientes_saldos_cuentas`, `crm_clientes_saldos_movimientos`);
  - existen tablas de recompensas/monedero, pero no deben usarse para este saldo porque recompensas son beneficios y el saldo favor es dinero/pasivo con cliente.
- Decision:
  - mantener `PFIN-20260706-000001` como expediente pendiente;
  - no crear saldo anonimo;
  - no cargarlo a recompensas;
  - integrar despues de que CRM exponga ledger de saldos y el apartado este ligado a cliente CRM.
- Estado preparacion DDL:
  - `ddl_total=2`;
  - `ddl_pendientes=2`;
  - apply sin token/respaldo bloqueado correctamente;
  - autorizacion de aplicacion documentada en `docs/crm_clientes_saldos_solicitud_autorizacion.md`.

## Siguiente paso

Preparar contrato CRM de saldos/cuenta corriente y liga obligatoria de cliente CRM en apartados con saldo favor. No aplicar DDL ni mover saldos hasta tener autorizacion fuerte del modulo CRM.
