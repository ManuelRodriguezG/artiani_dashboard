# Runbook - Reversas POS

Fecha: 2026-06-30

## Proposito

Aplicar de forma controlada el DDL necesario para reversas POS reales y preparar la ejecucion UAT con guardrails. La reversa real solo se ejecuta con autorizacion fuerte especifica.

## Precondiciones

- Tener respaldo externo:
  - `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`.
- Tener autorizacion textual exacta:

```text
AUTORIZO APLICAR DDL REVERSAS POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_REVERSA_DDL para UAT POS
```

- Confirmar que no se esta ejecutando una venta o cierre de turno en paralelo.

## Paso 1 - Auditoria read-only previa

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_schema_readonly.php
```

Resultado esperado antes de aplicar:

- tablas base existentes;
- columnas/indices de reversa faltantes;
- plan generado con `ejecutado=false`.

## Paso 2 - Readiness operativo read-only

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_readiness_readonly.php --folio=POS-20260629-000003 --id_usuario=1 --id_venta_detalle=8 --cantidad=1 --tipo=devolucion --motivo="UAT readiness reversa POS" --decision_inventario=cuarentena --decision_financiera=reembolso_caja
```

Resultado esperado antes de aplicar:

- dry-run de devolucion valido;
- bloqueo por DDL pendiente;
- bloqueo de caja si el turno esta cerrado.

## Paso 3 - Aplicar DDL autorizado

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_schema_apply_authorized.php --autorizar=VENTAS_POS_REVERSA_DDL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql
```

Resultado esperado:

- `ok=true`;
- auditoria antes con faltantes;
- plan con DDL ejecutado o columnas ya existentes;
- auditoria despues sin faltantes para reversas POS.

## Paso 4 - Auditoria read-only posterior

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_schema_readonly.php
```

Resultado esperado:

- columnas nuevas existentes;
- indices nuevos existentes;
- plan sin DDL pendiente o con mensajes `La columna ya existe` / `El indice ya existe`.

## Paso 5 - Readiness posterior

Con reembolso de caja:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_readiness_readonly.php --folio=POS-20260629-000003 --id_usuario=1 --id_venta_detalle=8 --cantidad=1 --tipo=devolucion --motivo="UAT readiness reversa POS" --decision_inventario=cuarentena --decision_financiera=reembolso_caja
```

Resultado esperado:

- no debe bloquear por DDL;
- puede bloquear por turno cerrado si no hay turno/caja abierto para reembolso.

Con saldo a favor:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_readiness_readonly.php --folio=POS-20260629-000003 --id_usuario=1 --id_venta_detalle=8 --cantidad=1 --tipo=devolucion --motivo="UAT readiness reversa POS saldo favor" --decision_inventario=cuarentena --decision_financiera=saldo_favor
```

Resultado esperado:

- no debe exigir movimiento de caja inmediato;
- debe seguir indicando que la reversa real requiere aplicador autorizado.

## Rollback conceptual

No se debe borrar columnas en caliente sin diagnostico. Si la aplicacion falla:

- detener flujo de reversas reales;
- conservar evidencia del `plan`;
- revisar `auditoria_despues`;
- corregir con DDL incremental, no con `DROP` improvisado.

## Fase 2 - Aplicador real protegido

Archivo preparado:

```text
storage/uat/uat_ventas_pos_reversa_apply_authorized.php
```

El aplicador queda bloqueado si falta alguno de estos datos:

- `--autorizar=VENTAS_POS_REVERSA_REAL`;
- respaldo externo existente;
- `id_usuario`;
- `folio` o `id_venta`;
- `id_venta_detalle`;
- `cantidad`;
- `motivo`.

Prueba segura del guardrail:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_apply_authorized.php
```

Resultado esperado:

- `ok=false`;
- `bloqueado=true`;
- no crea devolucion;
- no modifica venta;
- no mueve inventario;
- no registra caja.

## Fase 3 - Readiness antes de reversa real

Primera prueba recomendada sin caja:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_readiness_readonly.php --folio=POS-20260629-000003 --id_usuario=1 --id_venta_detalle=8 --cantidad=1 --tipo=devolucion --motivo="UAT devolucion POS saldo a favor" --decision_inventario=cuarentena --decision_financiera=saldo_favor --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql
```

Resultado esperado:

- `ok=true`;
- `bloqueos=[]`;
- dry-run de devolucion `success`;
- reembolso/saldo estimado `295`;
- no exige turno abierto por usar `saldo_favor`.

## Fase 4 - Ejecucion real con autorizacion fuerte

Autorizacion textual requerida:

```text
AUTORIZO EJECUTAR REVERSA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_REVERSA_REAL id_usuario=1 folio=POS-20260629-000003 id_venta_detalle=8 cantidad=1 tipo=devolucion decision_inventario=cuarentena decision_financiera=saldo_favor motivo="UAT devolucion POS saldo a favor"
```

Comando equivalente:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_apply_authorized.php --autorizar=VENTAS_POS_REVERSA_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --folio=POS-20260629-000003 --id_venta_detalle=8 --cantidad=1 --tipo=devolucion --decision_inventario=cuarentena --decision_financiera=saldo_favor --motivo="UAT devolucion POS saldo a favor"
```

Resultado esperado:

- crea `erp_ventas_devoluciones`;
- crea `erp_ventas_devoluciones_detalle`;
- actualiza estatus de venta a `devuelta` o `devolucion_parcial`;
- registra `monto_saldo_favor`;
- no registra movimiento de caja;
- no mueve inventario porque la decision es `cuarentena`;
- conserva snapshot y trazabilidad original.

## Fase 5 - Verificacion post-reversa read-only

Script:

```text
storage/uat/uat_ventas_pos_reversa_post_readonly.php
```

Comando por folio de venta:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_post_readonly.php --folio_venta=POS-20260629-000003
```

Comando por folio de devolucion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_post_readonly.php --folio_devolucion=DEV-YYYYMMDD-000001
```

Resultado esperado despues de la reversa real:

- `ok=true`;
- encabezado de devolucion existente;
- detalle ligado a venta original;
- venta con estatus `devuelta` o `devolucion_parcial`;
- si `decision_financiera=saldo_favor`, `monto_saldo_favor>0` y sin movimiento de caja;
- si `decision_financiera=reembolso_caja`, movimiento de caja ligado;
- si `decision_inventario=cuarentena`, sin kardex de entrada;
- si `decision_inventario=reintegrar`, kardex de entrada ligado.

## Siguiente fase

Despues de DDL:

1. Ejecutar primera reversa real a saldo a favor con autorizacion.
2. Validar devolucion a cuarentena sin reembolso de caja.
3. Validar reembolso de caja con turno abierto.
4. Validar cancelacion completa.
5. Validar ticket de devolucion.
