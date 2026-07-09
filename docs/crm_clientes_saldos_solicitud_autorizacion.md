# CRM Clientes - Solicitud de autorizacion saldos/cuenta corriente

Fecha: 2026-07-06

## Objetivo

Crear el esquema CRM de saldos monetarios de cliente para soportar saldo a favor, consumos, cargos, ajustes y trazabilidad contable/operativa sin mezclarlo con recompensas.

## Token

`CRM_CLIENTES_SALDOS_DDL`

## Alcance del DDL

- Crea `crm_clientes_saldos_cuentas`.
- Crea `crm_clientes_saldos_movimientos`.
- No crea cuentas reales.
- No crea movimientos.
- No convierte decisiones POS a saldo.
- No mueve caja.
- No mueve inventario.
- No toca recompensas, ecommerce, garantias ni legacy.

## Contrato operativo

- Recompensas/monedero no son saldo favor de cliente.
- Saldo favor de cancelaciones/apartados es dinero/pasivo con cliente.
- Toda cuenta queda ligada a `id_cliente_crm`.
- No se permiten saldos anonimos.
- Todo movimiento debe tener folio unico, naturaleza, monto, saldo anterior, saldo resultante y origen.
- POS solo podra consumir este ledger despues de:
  - cliente CRM ligado al documento POS;
  - decision financiera POS autorizada;
  - dry-run exitoso;
  - autorizacion real separada.

## Scripts

Read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_saldos_schema_readonly.php
```

Apply protegido:

```powershell
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_saldos_schema_apply_authorized.php --autorizar=CRM_CLIENTES_SALDOS_DDL --respaldo="UAT POS vigente"
```

## Autorizacion requerida para aplicar

```text
AUTORIZO APLICAR DDL CRM SALDOS CLIENTE usando respaldo UAT POS vigente con token CRM_CLIENTES_SALDOS_DDL para UAT CRM/POS
```

## Verificacion esperada

- `crm_clientes_saldos_cuentas` existe.
- `crm_clientes_saldos_movimientos` existe.
- Ambas tablas inician sin filas.
- `PFIN-20260706-000001` sigue `pendiente_ledger_crm` hasta una autorizacion posterior de integracion POS -> CRM ledger.

## Resultado aplicado 2026-07-06

- Autorizacion recibida:

```text
AUTORIZO APLICAR DDL CRM SALDOS CLIENTE usando respaldo UAT POS vigente con token CRM_CLIENTES_SALDOS_DDL para UAT CRM/POS
```

- Script ejecutado:
  - `storage/uat/uat_crm_clientes_saldos_schema_apply_authorized.php`.
- Resultado:
  - `crm_clientes_saldos_cuentas` creada;
  - `crm_clientes_saldos_movimientos` creada;
  - ambas tablas con `0` filas;
  - `ddl_pendientes=0` en auditor read-only posterior.
- Alcance respetado:
  - no creo cuentas reales;
  - no creo movimientos;
  - no convirtio decisiones POS;
  - no movio caja;
  - no movio inventario;
  - no uso recompensas.
- Bloqueo restante:
  - `PFIN-20260706-000001` no puede integrarse todavia porque el apartado `APT-20260706-000002` no tiene `id_cliente_crm`.
