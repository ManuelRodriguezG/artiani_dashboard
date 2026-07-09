# ERP Ventas/POS - Uso de saldo CRM como pago

Fecha: 2026-07-06

## Objetivo

Permitir que un cliente use saldo monetario CRM en una venta POS sin tratarlo como efectivo, sin mover caja y sin mezclarlo con recompensas.

## Regla central

El saldo CRM es dinero previamente reconocido a favor del cliente. Al usarlo en POS:

- se registra como pago de venta;
- no genera movimiento de caja;
- descuenta `crm_clientes_saldos_cuentas.saldo_disponible`;
- crea movimiento `crm_clientes_saldos_movimientos` tipo `uso_saldo_pos`;
- queda enlazado al folio de venta POS;
- no puede generar cambio.

## Contrato de pago POS

En `erp_ventas_pagos`:

- `id_movimiento_caja=NULL`;
- `id_metodo_pago=NULL` o metodo virtual controlado;
- `metodo_pago='saldo_crm'`;
- `tipo_pago='saldo_cliente'`;
- `monto` igual al saldo usado;
- `referencia` igual al folio del movimiento CRM.

En CRM:

- cuenta: `crm_clientes_saldos_cuentas`;
- movimiento: `crm_clientes_saldos_movimientos`;
- tipo: `uso_saldo_pos`;
- naturaleza: `cargo`;
- origen: `ventas_pos/venta_pos_pago_saldo_crm/FOLIO_POS`.

## Dry-run preparado

Script:

- `storage/uat/uat_ventas_pos_saldo_crm_uso_dryrun.php`.

Caso UAT:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_saldo_crm_uso_dryrun.php --id_usuario=1 --id_cliente_crm=157 --monto=100 --compact=1
```

Resultado:

- `ok=true`;
- saldo disponible `100`;
- monto `100`;
- saldo resultante `0`;
- no escribe BD.

## Bloqueos validados

- Cliente inexistente.
- Cliente sin cuenta de saldos.
- Saldo insuficiente.
- Monto mayor al total de venta porque saldo CRM no genera cambio.

## Siguiente paso

Preparar apply real solo cuando haya una venta POS real o una UAT de venta completa con:

- cliente CRM;
- carrito validado;
- turno abierto;
- stock disponible;
- saldo CRM suficiente;
- pago total o mixto.

El apply real debe ejecutarse dentro de la misma transaccion que la venta POS para que inventario, venta, pago y saldo CRM queden consistentes.

## Preparacion apply real

Fecha: 2026-07-06

Script preparado:

- `storage/uat/uat_ventas_pos_saldo_crm_apply_prep_authorized.php`.

Comando UAT de preparacion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_saldo_crm_apply_prep_authorized.php --autorizar=VENTAS_POS_SALDO_CRM_APPLY_PREP --respaldo="UAT POS vigente" --id_usuario=1 --id_cliente_crm=157 --monto_saldo_crm=100 --id_sku=1760 --cantidad=1 --precio=295 --pago_caja=195 --compact=1
```

Resultado:

- `read_only=true`;
- cliente CRM `157`, saldo disponible `100`;
- monto saldo CRM `100`, saldo resultante estimado `0`;
- total venta estimado `295`;
- monto caja estimado `195`;
- bloqueo restante: no hay turno abierto para la caja asignada.

Ajuste de modelo:

- `VentasErp::prevalidarPagosPos` reconoce `saldo_crm` como pago virtual sin caja.
- `VentasErp::registrarPagosPosReal` bloquea `saldo_crm` en la ruta real actual para evitar que se registre como movimiento de caja.

Siguiente autorizacion fuerte:

```text
AUTORIZO EJECUTAR VENTA POS UAT REAL CON SALDO CRM usando respaldo UAT POS vigente con token VENTAS_POS_SALDO_CRM_REAL id_usuario=1 id_cliente_crm=157 id_sku=1760 cantidad=1 precio=295 monto_saldo_crm=100 pago_caja=195
```

Antes de ejecutarla debe existir turno abierto y stock suficiente para el SKU.

## Ruta real preparada en modelo

Fecha: 2026-07-07

Cambios:

- `VentasErp::registrarPagosPosReal` ahora soporta pago mixto:
  - pagos de caja siguen creando `erp_pos_movimientos_caja`;
  - `saldo_crm` crea pago POS sin caja;
  - el `monto_esperado` del turno solo suma pagos que mueven caja.
- Nuevo helper `VentasErp::registrarPagoSaldoCrmPosReal`:
  - bloquea cuenta CRM con `FOR UPDATE`;
  - valida saldo disponible;
  - descuenta saldo;
  - inserta `crm_clientes_saldos_movimientos` tipo `uso_saldo_pos`;
  - inserta `erp_ventas_pagos` con `id_movimiento_caja=NULL`;
  - registra eventos en ventas y CRM.
- Nuevo script protegido:
  - `storage/uat/uat_ventas_pos_saldo_crm_apply_authorized.php`.

Validaciones:

- `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores.
- `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_saldo_crm_apply_authorized.php`: sin errores.
- Script real sin token: bloqueado, no escribio BD.

Bloqueo operativo actual:

- La prevalidacion posterior no pudo consultar BD porque la conexion esta no disponible y no hay proceso MySQL activo.
- No se recupero MySQL ni se ejecuto venta real sin autorizacion.

## Verificador posterior UAT

Fecha: 2026-07-07

Script:

- `storage/uat/uat_ventas_pos_saldo_crm_post_readonly.php`.

Uso esperado despues de la venta real:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_saldo_crm_post_readonly.php --folio=POS-YYYYMMDD-###### --compact=1
```

Valida:

- pago `saldo_crm` existe;
- pago `saldo_crm` no tiene `id_movimiento_caja`;
- movimiento CRM `uso_saldo_pos` existe y coincide con el monto pagado;
- los pagos de caja coinciden con movimientos de caja;
- existen trazas de inventario;
- hay eventos de venta.

Validaciones tecnicas:

- `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_saldo_crm_post_readonly.php`: sin errores.
- Sin `folio` o `id_venta`: bloquea en modo read-only.

## Runbook UAT saldo CRM

Fecha: 2026-07-07

Script:

- `storage/uat/uat_ventas_pos_saldo_crm_runbook_readonly.php`.

Resultado compacto:

- total venta `295`;
- saldo CRM `100`;
- pago caja `195`;
- monto inicial `500`;
- monto contado esperado para cierre `695`.

Nota contable:

- El cierre esperado no es `795` porque el saldo CRM no entra a caja.
- Caja debe cuadrar con `monto_inicial + pago_caja = 500 + 195 = 695`.

Autorizaciones preparadas:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS saldo CRM"

AUTORIZO EJECUTAR VENTA POS UAT REAL CON SALDO CRM usando respaldo UAT POS vigente con token VENTAS_POS_SALDO_CRM_REAL id_usuario=1 id_cliente_crm=157 id_sku=1760 cantidad=1 precio=295 monto_saldo_crm=100 pago_caja=195

AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=695 observaciones="Cierre UAT POS venta con saldo CRM"
```

Stock opcional solo si readiness indica insuficiencia:

```text
AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-UAT-SALDO-CRM-01
```

## Integracion UI POS

Fecha: 2026-07-07

Cambios:

- Vista POS:
  - boton rapido `Saldo cliente` junto a efectivo, tarjeta y transferencia.
- JS POS:
  - `saldo_crm` se renderiza como pago virtual, sin selector de metodo de caja;
  - exige cliente CRM seleccionado antes de agregarlo;
  - muestra que no entra a caja;
  - el mensaje de cobro separa caja recibida y saldo cliente usado;
  - bloquea antes de cobrar si saldo cliente generaria cambio.
- Backend:
  - `VentasErp::prevalidarPagosPos` bloquea `Saldo CRM no puede generar cambio`.

Validaciones:

- `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: sin errores.
- Readiness valido con caja `195` + saldo CRM `100`: solo bloquea por falta de turno.
- Readiness negativo con caja `250` + saldo CRM `100`: bloquea `Pago 2: Saldo CRM no puede generar cambio`.

## Ticket, detalle y corte

Fecha: 2026-07-07

Cambios:

- Ticket formal:
  - muestra `Saldo cliente no caja` cuando la venta usa saldo CRM.
- Corte de turno:
  - separa pagos normales de `Pagos sin caja`;
  - saldo CRM queda visible, pero fuera de movimientos de caja.
- Detalle web de venta:
  - traduce `saldo_crm` a `Saldo cliente`;
  - muestra tipo `No entra a caja`.
- Corte embebido en POS:
  - traduce `saldo_crm` a `Saldo cliente`;
  - muestra tipo `Sin caja`.
- Caja/Turnos:
  - el arqueo ya no usa la etiqueta ambigua `Vales/saldo`;
  - muestra nota operativa: saldo cliente CRM no se captura en arqueo porque no entra a caja.

Validaciones:

- `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: sin errores.
- `node --check public\assets\js\custom\apps\erp\ventas\venta_detalle.js`: sin errores.
- `node --check public\assets\js\custom\apps\erp\ventas\caja_turnos.js`: sin errores.

## Indicador saldo cliente en POS

Fecha: 2026-07-07

Cambios:

- Endpoint read-only:
  - `/ventas/cliente_saldo_crm_readonly_erp`;
  - modelo `VentasErp::clienteSaldoCrmReadOnly`.
- POS:
  - al seleccionar cliente CRM consulta saldo MXN activo;
  - muestra saldo disponible debajo de los metodos de pago;
  - si el saldo conocido es `0`, muestra `Sin saldo cliente disponible` y deshabilita `Saldo cliente`;
  - si el saldo conocido es menor al saldo de venta, propone solo el monto disponible.

Contrato:

- No crea cuenta CRM.
- No crea movimientos CRM.
- No mueve caja.
- No cambia precios ni descuentos.
- El cobro real sigue revalidando saldo dentro de la transaccion.

Validaciones:

- `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores.
- `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: sin errores.
- Consulta read-only cliente `157`: saldo disponible `$0.00`, sin escritura BD.
- `node --check storage\uat\uat_ventas_pos_saldo_crm_playwright_uat.js`: sin errores.
- UAT visual POS:
  - indicador de saldo `$0.00` visible;
  - boton `Saldo cliente` deshabilitado;
  - no agrega pago y no cobra.

## Reversas de ventas con saldo cliente

Fecha: 2026-07-07

Hallazgo:

- Una venta POS puede tener mezcla financiera:
  - pago de caja;
  - pago `saldo_crm` que no entra a caja.
- La reversa actual acepta `reembolso_caja`, `saldo_favor`, `cambio_producto` y `sin_reembolso`, pero aun no separa automaticamente el origen del pago.
- Regla requerida:
  - caja solo debe reembolsar el monto que originalmente entro a caja;
  - saldo CRM debe reintegrarse a ledger CRM o quedar como saldo favor CRM trazable;
  - el corte no debe mostrar como salida de caja lo que nunca entro a caja.

Herramienta read-only:

- `storage/uat/uat_ventas_pos_reversa_saldo_crm_readiness_readonly.php`.
- Audita venta, dry-run de devolucion, mezcla de pagos, reversas previas y capacidad de reembolso.
- No escribe BD, no mueve caja, no mueve inventario y no mueve saldo CRM.

UAT read-only con `POS-20260707-000001`:

- Total venta: `$295.00`.
- Caja pagada: `$195.00`.
- Saldo CRM pagado: `$100.00`.
- Reembolso completo desde caja:
  - bloqueado;
  - excede caja pagada disponible por `$100.00`.
- Saldo favor:
  - valido como dry-run;
  - avisa que el saldo favor actual de reversas POS todavia no crea movimiento CRM automatico.

Decision pendiente:

- Preparar DDL/modelo para decision financiera mixta o reintegro CRM:
  - `reembolso_caja` hasta caja pagada disponible;
  - `reintegro_saldo_crm` con movimiento `abono` en `crm_clientes_saldos_movimientos`;
  - evento de venta/devolucion ligado al folio de reversa;
  - ticket/corte mostrando caja y no caja por separado.

## DDL preparado para reversas con saldo cliente

Fecha: 2026-07-07

Cambios preparados sin aplicar BD:

- `VentasErpEsquema::planActualizarReversasSaldoCrmPos`.
- `VentasErpEsquema::auditarReversasSaldoCrmPos`.
- Script read-only:
  - `storage/uat/uat_ventas_pos_reversa_saldo_crm_schema_readonly.php`.
- Script apply protegido:
  - `storage/uat/uat_ventas_pos_reversa_saldo_crm_schema_apply_authorized.php`;
  - token `VENTAS_POS_REVERSA_SALDO_CRM_DDL`.

Estructura propuesta:

- Columnas resumen en `erp_ventas_devoluciones`:
  - `id_cliente_crm`;
  - `monto_reintegro_saldo_crm`;
  - `monto_no_caja`.
- Tabla nueva `erp_ventas_devoluciones_finanzas`:
  - componente caja;
  - componente reintegro saldo CRM;
  - componente saldo favor CRM;
  - liga a movimiento de caja cuando aplique;
  - liga a cuenta/movimiento CRM cuando aplique;
  - snapshot de trazabilidad.

Read-only ejecutado:

- `ok=true`.
- Pendiente:
  - tabla `erp_ventas_devoluciones_finanzas`;
  - columnas resumen;
  - indices de devolucion/venta/cliente/caja/saldo.
- Apply sin token:
  - bloqueado correctamente;
  - no escribio BD.

Autorizacion fuerte siguiente:

```text
AUTORIZO APLICAR DDL REVERSAS POS CON SALDO CRM usando respaldo UAT POS vigente con token VENTAS_POS_REVERSA_SALDO_CRM_DDL para UAT POS/CRM
```

## DDL reversas con saldo cliente aplicado

Fecha: 2026-07-07

Autorizacion ejecutada:

- `VENTAS_POS_REVERSA_SALDO_CRM_DDL`.

Resultado:

- Primer intento bloqueado por MySQL no disponible; no ejecuto DDL.
- Se levanto `mysqld` y se revalido conexion desde la app.
- Segundo intento `ok=true`.
- Auditoria posterior:
  - `erp_ventas_devoluciones_finanzas` existe;
  - `id_cliente_crm`, `monto_reintegro_saldo_crm`, `monto_no_caja` existen en `erp_ventas_devoluciones`;
  - indices requeridos existen.

Validacion de negocio posterior:

- Reembolso completo desde caja de `POS-20260707-000001` sigue bloqueado:
  - caja pagada `$195.00`;
  - saldo CRM pagado `$100.00`;
  - reembolso estimado `$295.00`;
  - excede caja por `$100.00`.

Siguiente frente:

- Implementar reversa real con componentes financieros:
  - registrar componente caja solo hasta caja disponible;
  - registrar componente `reintegro_saldo_crm`;
  - crear movimiento CRM `abono`;
  - ligar componente financiero a devolucion y venta;
  - actualizar ticket/corte/post-readonly.

## Modelo real preparado para reversas con saldo cliente

Fecha: 2026-07-07

Autorizacion ejecutada:

- `VENTAS_POS_REVERSA_SALDO_CRM_MODELO`.

Cambios preparados:

- `VentasErp::confirmarReversaPosReal` ahora soporta decisiones:
  - `mixta_saldo_crm`;
  - `reintegro_saldo_crm`.
- Nuevos helpers:
  - resumen de pagos originales por caja/saldo CRM;
  - resumen de reversas financieras previas;
  - calculo de componentes financieros;
  - registro de componente financiero en `erp_ventas_devoluciones_finanzas`;
  - reintegro de saldo CRM con movimiento `abono`;
  - eventos de venta y CRM para reintegro;
  - folio `DFIN-YYYYMMDD-######`.
- Script real protegido:
  - `storage/uat/uat_ventas_pos_reversa_saldo_crm_apply_authorized.php`;
  - token futuro `VENTAS_POS_REVERSA_SALDO_CRM_REAL`.
- Post-readonly ampliado:
  - `storage/uat/uat_ventas_pos_reversa_post_readonly.php`;
  - valida componentes financieros y movimientos CRM ligados.

Validaciones:

- `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores.
- `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_reversa_saldo_crm_apply_authorized.php`: sin errores.
- `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_reversa_post_readonly.php`: sin errores.
- Aplicador real sin token:
  - bloqueado correctamente;
  - no creo devolucion;
  - no movio caja;
  - no movio CRM;
  - no movio inventario.
- Readiness `mixta_saldo_crm` sobre `POS-20260707-000001`:
  - `ok=true`;
  - propone caja `$195.00`;
  - propone saldo CRM `$100.00`.
- Readiness `reintegro_saldo_crm` completo:
  - `ok=false`;
  - bloquea porque excede saldo CRM pagado por `$195.00`.

Siguiente autorizacion real:

```text
AUTORIZO EJECUTAR REVERSA POS UAT REAL CON SALDO CRM usando respaldo UAT POS vigente con token VENTAS_POS_REVERSA_SALDO_CRM_REAL id_usuario=1 folio=POS-20260707-000001 id_venta_detalle=18 cantidad=1 tipo=devolucion decision_inventario=cuarentena decision_financiera=mixta_saldo_crm motivo="UAT devolucion mixta caja y saldo CRM"
```

Nota:

- Antes de ejecutar la UAT real debe existir turno abierto en la misma caja/almacen si el componente caja es mayor a `$0.00`.

## Avance 2026-07-07 - Reversa mixta y evidencia

- Se ejecuto reversa mixta real `DEV-20260707-000001` contra venta `POS-20260707-000001`.
- Componentes financieros:
  - caja `$195.00`;
  - saldo CRM reintegrado `$100.00`;
  - monto no caja `$100.00`.
- Se registro evidencia `id_evidencia_caja=3` para el reembolso caja `41`.
- Evidencia fue revisada y queda `aprobada`.
- Movimiento caja `41` queda con `evidencia_estado=aprobada`.

Siguiente autorizacion recomendada:

```text
AUTORIZO REVISAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_EVIDENCIA_REVISION_REAL id_usuario=1 id_evidencia_caja=3 decision=aprobada permitir_mismo_usuario=1
```

Guardrail:

- `permitir_mismo_usuario=1` solo debe usarse en UAT controlada cuando no hay segundo usuario revisor.

Siguiente frente:

- Registrar inspeccion fisica documental para `id_devolucion_detalle=3`;
- mantener cuarentena;
- no mover inventario;
- no crear kardex.
