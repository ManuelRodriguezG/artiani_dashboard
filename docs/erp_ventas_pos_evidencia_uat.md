# ERP Ventas/POS - Evidencia UAT

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-26  
Estado: plantilla de evidencia; no implica autorizacion de escritura en BD.

## Datos de sesion

- Fecha de prueba:
- Ambiente:
- Usuario ERP:
- Sucursal:
- Almacen:
- Caja:
- Terminal:
- Turno:
- Respaldo validado:

## Alcance UAT base

Este bloque corresponde al alcance `VENTAS_POS_DDL_BASE`:

- cajas;
- terminales;
- asignacion usuario/caja/terminal;
- apertura de turno;
- venta POS;
- pago;
- kardex;
- trazabilidad venta-inventario;
- validacion post-venta por folio.

No incluye clientes ERP, listas de precios, atenciones persistentes, pedidos con reserva ni apartados con abonos.

## Caso VENTAS-UAT-BASE-001 - Post-config POS

- Comando:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_config_readonly.php --id_usuario=1 --alcance=base`
- Resultado esperado:
  - sin tablas base pendientes;
  - cajas `>=2`;
  - terminales `>=2`;
  - asignacion activa para `id_usuario=1`.
- Resultado real:
- Hallazgos:

## Caso VENTAS-UAT-BASE-002 - Apertura de POS

- Resultado esperado: el POS abre con operador, sucursal, caja y terminal correctos.
- Evidencia:
  - Operador mostrado:
  - Sucursal/caja mostrada:
  - Terminal mostrada:
  - Hallazgos:

## Caso VENTAS-UAT-BASE-003 - Apertura de turno

- Preflight:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_preflight_readonly.php --id_usuario=1 --monto_inicial=500`
- Comando autorizado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_apertura_apply_authorized.php --autorizar=VENTAS_POS_TURNO_APERTURA --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --monto_inicial=500`
- Folio turno:
- Caja:
- Almacen:
- Monto inicial:
- Movimiento caja inicial:
- Resultado esperado: turno abierto ligado a usuario, caja y almacen.
- Resultado real:
- Hallazgos:

## Caso VENTAS-UAT-BASE-004 - Venta POS real

- SKU: `TP-40352-500GR` (`id_sku=1760`)
- Cantidad: `1`
- Modo salida: `existencia_agregada`
- Existencia antes: `2.0000`
- Unidad fisica si aplica:
- Folio venta: `POS-20260626-000001`
- Existencia despues: `1.0000`
- Movimiento kardex: `53`
- Movimiento caja: `2`
- Pago: efectivo `295`
- Resultado esperado: la venta genera folio ERP, pago, salida de inventario, kardex y trazabilidad.
- Resultado real: aprobado; venta `pagada`, total `295`, saldo `0`.
- Hallazgos:

## Caso VENTAS-UAT-BASE-005 - Validacion post-venta por folio

- Comando:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_venta_readonly.php --folio=POS-20260626-000001`
- Folio venta: `POS-20260626-000001`
- Total: `295`
- Pagos: `1`, suma `295`
- Movimientos caja: `1`, movimiento `2`
- Movimientos inventario: `1`, movimiento `53`
- Trazabilidad detalle-inventario: `1`
- Resultado esperado: `ok=true`, sin hallazgos de trazabilidad.
- Resultado real: `ok=true`, `hallazgos=[]`.
- Hallazgos:

## Casos posteriores al UAT base

Estos casos quedan para la fase expandida o para reglas especificas despues de validar el POS base.

## Caso VENTAS-UAT-FUT-001 - Venta unidad cerrada

- SKU:
- Unidad fisica:
- Existencia antes:
- Folio venta:
- Existencia despues:
- Movimiento kardex:
- Resultado esperado: la unidad cerrada disponible queda vendida y trazada.
- Resultado real:
- Hallazgos:

## Caso VENTAS-UAT-FUT-002 - Bloqueo unidad abierta como cerrada

- SKU:
- Unidad fisica abierta:
- Folio intento:
- Resultado esperado: el POS bloquea venderla como pieza cerrada.
- Resultado real:
- Mensaje mostrado:
- Hallazgos:

## Caso VENTAS-UAT-FUT-003 - Venta granel desde unidad abierta

- SKU:
- Unidad fisica abierta:
- Cantidad solicitada:
- Existencia antes:
- Folio venta:
- Existencia despues:
- Movimiento kardex:
- Resultado esperado: solo permite granel si el SKU permite venta fraccionaria.
- Resultado real:
- Hallazgos:

## Caso VENTAS-UAT-FUT-004 - Stock insuficiente por sucursal

- SKU:
- Sucursal POS:
- Almacen POS:
- Existencia en tienda:
- Existencia en otra ubicacion:
- Resultado esperado: bloquea vender desde otra sucursal sin traspaso.
- Resultado real:
- Hallazgos:

## Caso VENTAS-UAT-FUT-005 - Pago efectivo

- Folio venta:
- Total:
- Pago recibido:
- Cambio:
- Movimiento caja:
- Resultado esperado: total y cambio cuadran, pago queda trazado.
- Resultado real:
- Hallazgos:

## Caso VENTAS-UAT-FUT-006 - Transferencia con referencia

- Folio venta:
- Total:
- Referencia:
- Movimiento caja:
- Resultado esperado: bloquea si falta referencia y acepta si esta capturada.
- Resultado real:
- Hallazgos:

## Caso VENTAS-UAT-FUT-007 - Pedido con reserva

- Folio pedido:
- SKU:
- Cantidad:
- Reserva:
- Disponible antes:
- Disponible despues:
- Resultado esperado: la reserva reduce disponible sin consumir venta final hasta confirmar.
- Resultado real:
- Hallazgos:

## Caso VENTAS-UAT-FUT-008 - Cancelacion o devolucion

- Folio venta original:
- Folio devolucion/cancelacion:
- SKU:
- Cantidad:
- Destino inventario:
- Movimiento kardex:
- Resultado esperado: no borra historial y deja trazabilidad de reversa.
- Resultado real:
- Hallazgos:

## Hallazgos

| ID | Severidad | Caso | SKU/Folio | Descripcion | Estado | Responsable |
| --- | --- | --- | --- | --- | --- | --- |
| VENTAS-H-UAT-001 |  |  |  |  | Abierto |  |

## Evidencia tecnica - DDL base autorizado

- Fecha: 2026-06-26
- Autorizacion recibida: `AUTORIZO CREAR ESQUEMA BASE ERP VENTAS POS PEDIDOS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`
- Script ejecutado: `storage/uat/uat_ventas_pos_schema_apply_authorized.php --autorizar=VENTAS_POS_DDL_BASE`
- Respaldo externo usado como referencia: `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`
- Resultado: correcto; se crearon 11 tablas base POS/Ventas/Pedidos.
- Tablas creadas: `erp_pos_cajas`, `erp_pos_terminales`, `erp_pos_usuarios_cajas`, `erp_pos_turnos`, `erp_pos_movimientos_caja`, `erp_ventas`, `erp_ventas_detalle`, `erp_ventas_detalle_inventario`, `erp_ventas_pagos`, `erp_ventas_devoluciones`, `erp_ventas_devoluciones_detalle`.
- Verificacion posterior: `tablas_pendientes=[]`, `cajas=0`, `terminales=0`, `asignaciones_activas=0`, `turnos_abiertos=0`.
- Bloqueos esperados restantes: configurar caja/terminal/asignacion de usuario y abrir turno antes de operar venta real.
- Alcance no ejecutado: no se sembraron cajas/terminales, no se abrio turno, no se genero venta real.

## Evidencia tecnica - Semillas POS base autorizadas

- Fecha: 2026-06-26
- Autorizacion recibida: `AUTORIZO CREAR SEMILLAS POS BASE usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`
- Script ejecutado: `storage/uat/uat_ventas_pos_seed_apply_authorized.php --autorizar=VENTAS_POS_SEED --id_usuario=1`
- Respaldo externo usado como referencia: `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`
- Resultado: correcto; se ejecutaron 6 sentencias de semillas.
- Registros configurados: 2 cajas, 2 terminales y 2 asignaciones activas.
- Verificacion posterior: `tablas_pendientes=[]`, `cajas=2`, `terminales=2`, `asignaciones_activas=2`, `turnos_abiertos=0`.
- Asignacion POS: activa para `id_usuario=1`, modo UI `asignacion_oficial`.
- Bloqueo esperado restante: no hay turno abierto para la caja.
- Alcance no ejecutado: no se abrio turno, no se genero venta real.

## Evidencia tecnica - Turno POS UAT autorizado

- Fecha: 2026-06-26
- Autorizacion recibida: `AUTORIZO ABRIR TURNO POS UAT usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 y monto_inicial=500`
- Script ejecutado: `storage/uat/uat_ventas_pos_turno_apertura_apply_authorized.php --autorizar=VENTAS_POS_TURNO_APERTURA --id_usuario=1 --monto_inicial=500`
- Respaldo externo usado como referencia: `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`
- Resultado: correcto; turno abierto.
- Folio turno: `TUR-20260626-002-001`
- `id_turno_caja`: 1
- `id_movimiento_caja`: 1
- `id_almacen`: 5
- `id_caja`: 2
- Monto inicial: 500
- Verificacion posterior: `tablas_pendientes=[]`, `turnos_abiertos=1`, `diagnostico_hallazgos=[]`, `bloqueos_restantes=[]`.
- Preflight venta read-only con SKU de ejemplo: POS listo en asignacion/turno, pero venta bloqueada por `Existencia insuficiente` en almacen 5.
- Alcance no ejecutado: no se genero venta real, no se desconto inventario.

## Evidencia tecnica - Stock para primera venta UAT

- Fecha: 2026-06-26
- Scripts read-only agregados:
  - `storage/uat/uat_ventas_pos_stock_candidatos_readonly.php`
  - `storage/uat/uat_ventas_pos_stock_uat_preflight_readonly.php`
- Script autorizado preparado pero no ejecutado: `storage/uat/uat_ventas_pos_stock_uat_apply_authorized.php`
- Guardrail validado: el aplicador de stock bloquea sin `--autorizar=VENTAS_POS_STOCK_UAT` y respaldo valido.
- Resultado stock actual: almacen 5 sin SKU con `cantidad_disponible > 0`; almacen 4 tambien sin candidatos.
- Preflight stock UAT recomendado:
  - Almacen: `5` / `MASCOTAS971` / `Mascotas Mina 971`
  - SKU: `1760` / `TP-40352-500GR`
  - Producto: `Alimento churro rojo para peces agranel`
  - Precio general: `295.000000`
  - Cantidad sugerida: `2`
  - Ubicacion: sin ubicacion activa; se recomienda `ubicacion_id=0` para UAT controlado.
  - Referencia sugerida: `POS-UAT-STOCK-20260626-A5-S1760`
- Siguiente paso bloqueado por autorizacion: cargar inventario UAT por `InventarioErp::aplicarAjuste` para generar kardex antes de venta real.

## Evidencia tecnica - Stock UAT POS autorizado

- Fecha: 2026-06-26
- Autorizacion recibida: `AUTORIZO CARGAR STOCK UAT POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=2`
- Script ejecutado: `storage/uat/uat_ventas_pos_stock_uat_apply_authorized.php --autorizar=VENTAS_POS_STOCK_UAT --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=2`
- Primer intento: bloqueado por regla del modelo, referencia `POS-UAT-STOCK-20260626-A5-S1760` no iniciaba con `INV-INICIAL-`; no se cargo stock.
- Ejecucion correcta: referencia `INV-INICIAL-POS-UAT-20260626-A5-S1760`.
- Resultado: correcto; `InventarioErp::aplicarAjuste` aplico inventario inicial ERP.
- Kardex: `movimientos=1`, `origen_tipo=inventario_inicial`, `motivo_ajuste=inventario_inicial`.
- Etiquetas generadas: `0`.
- Existencia creada/actualizada: `id_existencia_inventario=34`, `codigo_existencia=EXI-1016-34`, `cantidad_disponible=2.0000`.
- Preflight venta POS read-only:
  - Comando: `storage/uat/uat_ventas_pos_venta_preflight_readonly.php --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295`
  - Resultado: `ok=true`, `puede_vender_real=true`, `bloqueos=[]`.
  - Contexto: `id_almacen=5`, `id_caja=2`, `id_turno_caja=1`.
  - Total estimado: `295`, pagado `295`, saldo `0`, cambio `0`.
  - Plan salida: existencia agregada `id_existencia_inventario=34`, antes `2`, despues `1`.
- Alcance no ejecutado: no se genero venta real, no se desconto inventario por venta.

## Evidencia tecnica - Venta POS UAT real autorizada

- Fecha: 2026-06-26
- Autorizacion recibida: `AUTORIZO EJECUTAR VENTA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_sku=1760 cantidad=1 precio=295 pago=295`
- Script ejecutado: `storage/uat/uat_ventas_pos_venta_apply_authorized.php --autorizar=VENTAS_POS_VENTA_REAL --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295`
- Respaldo externo usado como referencia: `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`
- Resultado: correcto; venta POS real UAT generada.
- Folio venta: `POS-20260626-000001`
- `id_venta`: 1
- Estatus: `pagada`
- Total: `295`, pagado `295`, saldo `0`
- Pago: `id_venta_pago=1`, metodo `Efectivo`, `id_movimiento_caja=2`, monto `295`
- Inventario: `id_existencia_inventario=34`, movimiento kardex `53`, origen `venta_pos`, referencia `POS-20260626-000001`
- Existencia: antes `2.0000`, salida `1.0000`, despues/disponible actual `1.0000`
- Trazabilidad: `erp_ventas_detalle_inventario.id_venta_detalle_inventario=1`
- Verificacion post-venta: `ok=true`, partidas `1`, pagos `1`, trazabilidades `1`, `hallazgos=[]`.
- Turno POS: sigue abierto, `turnos_abiertos=1`, `bloqueos_restantes=[]`.

## Evidencia tecnica - Cierre de turno dry-run

- Fecha: 2026-06-27
- Script creado: `storage/uat/uat_ventas_pos_cierre_turno_dryrun_readonly.php`
- UI POS agregada:
  - boton `Corte` en acciones del POS;
  - modal `Corte de caja`;
  - campo `Monto contado`;
  - boton `Simular corte`;
  - resumen de esperado, contado, diferencia, ventas, pagos por metodo y movimientos de caja.
- Comando ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=795`
- Resultado: `ok=true`, `Dry-run de cierre valido`.
- Turno: `TUR-20260626-002-001`, `id_turno_caja=1`, caja `2`, almacen `5`.
- Monto inicial: `500`.
- Venta/Pagos:
  - ventas: `1`
  - total venta: `295`
  - pagado: `295`
  - saldo: `0`
  - metodo: `Efectivo`, monto `295`.
- Movimientos de caja:
  - `entrada/monto_inicial`: `500`
  - `ingreso/venta_pos`: `295`
- Esperado por turno: `795`.
- Esperado por movimientos: `795`.
- Contado simulado: `795`.
- Diferencia: `0`.
- Bloqueos: `[]`.
- Alcance: read-only; no cerro turno, no actualizo montos contados y no creo movimientos.
- Paso UAT visual:
  - abrir POS;
  - presionar `Corte`;
  - capturar `795` en monto contado;
  - presionar `Simular corte`;
  - validar esperado `795`, contado `795`, diferencia `0`.
- Script de cierre real preparado pero no ejecutado:
  - `storage/uat/uat_ventas_pos_turno_cierre_apply_authorized.php`
- Guardrail validado: sin `--autorizar=VENTAS_POS_TURNO_CIERRE`, respaldo, usuario y monto contado, responde `modo=bloqueado`.
- Siguiente paso bloqueado por autorizacion: ejecutar cierre real de turno UAT con respaldo externo y token explicito.
- Comando esperado cuando se autorice:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_cierre_apply_authorized.php --autorizar=VENTAS_POS_TURNO_CIERRE --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --monto_contado=795`

## Evidencia tecnica - Caja completa dry-run

- Fecha: 2026-06-27
- Endpoint agregado:
  - `/ventas/caja_movimiento_dryrun_erp`
- UI POS agregada:
  - boton `Caja` en acciones del POS;
  - modal `Caja`;
  - campos: tipo, monto, motivo, referencia, responsable y observaciones;
  - boton `Simular movimiento`;
  - resultado con bloqueos, avisos, tipo caja e impacto esperado.
- Script creado:
  - `storage/uat/uat_ventas_pos_caja_movimiento_dryrun_readonly.php`
- Comando ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_movimiento_dryrun_readonly.php --id_usuario=1 --tipo=gasto_caja --motivo="Compra menor UAT" --monto=50 --observaciones="Simulacion sin escritura"`
- Resultado: `ok=true`, `Dry-run de movimiento de caja valido`.
- Contexto: almacen `5`, caja `2`, turno `1`.
- Movimiento simulado:
  - tipo funcional: `gasto_caja`
  - tipo caja: `gasto`
  - motivo caja: `gasto_caja`
  - monto: `50`
  - impacto esperado: `-50`
- Avisos esperados:
  - requiere autorizacion;
  - requiere evidencia/comprobante.
- Alcance: read-only; no inserto movimiento y no modifico esperado del turno.
- Validacion tecnica:
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`
  - ambos sin errores.

## Evidencia tecnica - DDL Caja POS completa preparado

- Fecha: 2026-06-27
- Scripts creados:
  - `storage/uat/uat_ventas_pos_caja_schema_readonly.php`
  - `storage/uat/uat_ventas_pos_caja_schema_apply_authorized.php`
- Comando read-only ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_schema_readonly.php`
- Resultado auditoria:
  - tabla `erp_pos_movimientos_caja`: existe.
  - columnas faltantes para caja completa: `17`.
  - indices faltantes para caja completa: `3`.
- Guardrail validado:
  - `storage/uat/uat_ventas_pos_caja_schema_apply_authorized.php` responde `modo=bloqueado` sin `--autorizar=VENTAS_POS_CAJA_DDL` y respaldo valido.
- Alcance propuesto:
  - estatus de movimiento;
  - categoria;
  - caja/almacen directo;
  - venta/proveedor relacionado;
  - responsable;
  - autorizacion;
  - evidencia;
  - cancelacion logica;
  - indices para corte y auditoria.
- Siguiente paso bloqueado por autorizacion: aplicar DDL de caja completa antes de registrar gastos/retiros reales.
- Comando esperado cuando se autorice:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_schema_apply_authorized.php --autorizar=VENTAS_POS_CAJA_DDL --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql"`

## Evidencia tecnica - Movimiento real de caja preparado

- Fecha: 2026-06-27
- Script creado:
  - `storage/uat/uat_ventas_pos_caja_movimiento_apply_authorized.php`
- Guardrail validado:
  - sin `--autorizar=VENTAS_POS_CAJA_MOVIMIENTO_REAL`, respaldo, usuario, tipo, motivo y monto responde `modo=bloqueado`.
- Candado adicional:
  - aunque exista autorizacion, el script bloquea si falta DDL de caja completa.
- Alcance futuro:
  - insertar movimiento de caja;
  - actualizar `monto_esperado` del turno segun impacto;
  - bloquear si el turno ya no esta abierto;
  - registrar autorizacion/evidencia pendiente segun tipo.
- No ejecutado:
  - no se registro gasto real;
  - no se actualizo turno;
  - no se modifico BD.
- Comando esperado despues de aplicar DDL de caja completa y solo si se autoriza un movimiento real:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_movimiento_apply_authorized.php --autorizar=VENTAS_POS_CAJA_MOVIMIENTO_REAL --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --tipo=gasto_caja --motivo="Compra menor UAT" --monto=50 --observaciones="UAT caja completa"`

## Evidencia tecnica - Atenciones compartidas dry-run

- Fecha: 2026-06-27
- Endpoints agregados:
  - `/ventas/atenciones_bandeja_dryrun_erp`
  - `/ventas/atencion_persistente_dryrun_erp`
  - `/ventas/esquema_auditar_atenciones_pos`
- UI POS agregada:
  - boton `Atenciones`;
  - modal `Atenciones`;
  - accion `Consultar bandeja`;
  - accion `Simular compartir cuenta actual`.
- Scripts creados:
  - `storage/uat/uat_ventas_pos_atenciones_readonly.php`
  - `storage/uat/uat_ventas_pos_atencion_persistente_dryrun_readonly.php`
- Comando read-only ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_atenciones_readonly.php --id_usuario=1`
- Resultado auditoria:
  - `erp_pos_atenciones`: no existe.
  - `erp_pos_atenciones_detalle`: no existe.
  - `erp_pos_atenciones_pagos_temporales`: no existe.
- Resultado bandeja:
  - `schema_pendiente=true`;
  - bloqueo esperado: aplicar DDL expandido antes de compartir cuentas entre dispositivos.
- Comando dry-run ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_atencion_persistente_dryrun_readonly.php --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295`
- Resultado:
  - calcula folio temporal sugerido `ATN-*`;
  - calcula partida SKU `1760`;
  - total estimado `295`;
  - detecta disponibilidad actual de SKU;
  - bloquea por esquema pendiente.
- Alcance:
  - no crea atencion;
  - no reserva inventario;
  - no descuenta inventario;
  - no genera venta.

## Evidencia tecnica - Cliente/precios dry-run

- Fecha: 2026-06-27
- Endpoint usado:
  - `/ventas/pos_cliente_precio_dryrun_erp`
- UI POS agregada:
  - boton `Cliente/precios`;
  - modal `Cliente y precios`;
  - accion `Resolver cliente/precio`.
- Script read-only existente:
  - `storage/uat/uat_ventas_pos_cliente_precio_apartado_readonly.php`
- Comando ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cliente_precio_apartado_readonly.php --id_almacen=5 --id_sku=1760 --identificador=5550000000`
- Resultado cliente/precio:
  - `ok=true`;
  - `schema_clientes_pendiente=true`;
  - `schema_listas_precios_pendiente=true`;
  - cliente como `publico_general`;
  - `requiere_alta_rapida=true`;
  - SKU `TP-40352-500GR`;
  - precio base `295`;
  - precio aplicado `295`;
  - regla `catalogo_general`;
  - lista snapshot `general`.
- Contrato validado:
  - backend resuelve precio;
  - venta guarda cliente si existe;
  - venta guarda lista/snapshot;
  - detalle guarda precio base/aplicado/origen;
  - JS no decide descuentos.
- Alcance:
  - no crea cliente;
  - no crea lista de precios;
  - no modifica precio real;
  - no escribe BD.

## Evidencia tecnica - Auditoria DDL expandido POS

- Fecha: 2026-06-27
- Comando read-only ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_config_readonly.php --alcance=expandido --id_usuario=1`
- Resultado:
  - `ok=true`;
  - cajas `2`;
  - terminales `2`;
  - asignaciones activas `2`;
  - turnos abiertos `1`;
  - asignacion usuario `id_usuario=1`: activa;
  - modo UI esperado: `asignacion_oficial`;
  - hallazgos diagnostico: ninguno.
- Tablas pendientes de DDL expandido:
  - `erp_ventas_eventos`;
  - `erp_ventas_politicas_apartado`;
  - `erp_clientes`;
  - `erp_clientes_identificadores`;
  - `erp_listas_precios`;
  - `erp_listas_precios_detalle`;
  - `erp_clientes_listas_precios`;
  - `erp_pos_atenciones`;
  - `erp_pos_atenciones_detalle`;
  - `erp_pos_atenciones_pagos_temporales`.
- Guardrail validado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_schema_apply_authorized.php`
  - responde `modo=bloqueado` sin `--autorizar=VENTAS_POS_DDL_EXPANDIDO` y respaldo valido.
- Alcance:
  - no aplica DDL;
  - no crea tablas;
  - no escribe BD.

## Evidencia tecnica - DDL expandido POS aplicado

- Fecha: 2026-06-27
- Autorizacion recibida:
  - `AUTORIZO APLICAR DDL EXPANDIDO ERP VENTAS POS PEDIDOS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`
- Comando ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_schema_apply_authorized.php --autorizar=VENTAS_POS_DDL_EXPANDIDO --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql"`
- Resultado:
  - `ok=true`;
  - alcance `expandido`;
  - tablas base POS/Ventas ya existian;
  - se crearon tablas expandidas.
- Tablas creadas:
  - `erp_ventas_eventos`;
  - `erp_ventas_politicas_apartado`;
  - `erp_clientes`;
  - `erp_clientes_identificadores`;
  - `erp_listas_precios`;
  - `erp_listas_precios_detalle`;
  - `erp_clientes_listas_precios`;
  - `erp_pos_atenciones`;
  - `erp_pos_atenciones_detalle`;
  - `erp_pos_atenciones_pagos_temporales`.
- Validacion read-only posterior:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_config_readonly.php --alcance=expandido --id_usuario=1`
- Resultado validacion:
  - tablas pendientes: ninguna;
  - asignacion POS usuario `1`: activa;
  - modo UI: `asignacion_oficial`;
  - cajas `2`;
  - terminales `2`;
  - turnos abiertos `1`;
  - bloqueos restantes: ninguno.
- Atenciones read-only posterior:
  - `erp_pos_atenciones`, `erp_pos_atenciones_detalle`, `erp_pos_atenciones_pagos_temporales` existen;
  - bandeja consultada sin atenciones abiertas;
  - `schema_pendiente=false`;
  - no crea atenciones.
- Cliente/precio read-only posterior:
  - `schema_clientes_pendiente=false`;
  - `schema_listas_precios_pendiente=false`;
  - SKU `TP-40352-500GR` resuelve precio general `295`;
  - no crea cliente/lista.

## Evidencia tecnica - Semillas expandidas POS preparadas

- Fecha: 2026-06-27
- Scripts creados:
  - `storage/uat/uat_ventas_pos_semillas_expandido_readonly.php`
  - `storage/uat/uat_ventas_pos_semillas_expandido_apply_authorized.php`
- Validacion de sintaxis:
  - ambos scripts sin errores PHP.
- Comando read-only ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_semillas_expandido_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --precio=295 --telefono=5550000000`
- Conteos actuales:
  - `erp_ventas_politicas_apartado`: `0`;
  - `erp_clientes`: `0`;
  - `erp_clientes_identificadores`: `0`;
  - `erp_listas_precios`: `0`;
  - `erp_listas_precios_detalle`: `0`;
  - `erp_clientes_listas_precios`: `0`.
- Semillas propuestas:
  - politica `POS_APARTADO_UAT`, anticipo minimo `20%`, vigencia `30` dias;
  - cliente `CL-UAT-POS-001`, nombre `Cliente UAT POS`;
  - identificador telefono `5550000000`;
  - lista `LP-UAT-POS`, canal `pos`, almacen `5`, prioridad `10`;
  - detalle SKU `1760`, precio `295`, moneda `MXN`;
  - relacion cliente-lista.
- Guardrail validado:
  - `storage/uat/uat_ventas_pos_semillas_expandido_apply_authorized.php` responde `modo=bloqueado` sin `--autorizar=VENTAS_POS_SEED_EXPANDIDO`, respaldo e `id_usuario`.
- Alcance:
  - no crea clientes;
  - no crea listas;
  - no crea politicas;
  - no escribe BD;
  - no toca ventas, pagos, caja, atenciones ni inventario.

## Evidencia tecnica - Semillas expandidas POS ejecutadas

- Fecha: 2026-06-27
- Autorizacion recibida:
  - `AUTORIZO CREAR SEMILLAS EXPANDIDAS POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 id_sku=1760 precio=295 telefono=5550000000 para UAT POS`
- Comando ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_semillas_expandido_apply_authorized.php --autorizar=VENTAS_POS_SEED_EXPANDIDO --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --id_almacen=5 --id_sku=1760 --precio=295 --telefono=5550000000`
- Resultado:
  - `ok=true`;
  - cliente creado/actualizado `id_cliente=1`;
  - lista creada/actualizada `id_lista_precio=1`;
  - politica de apartado creada/actualizada;
  - identificador de telefono creado;
  - detalle de lista creado para SKU `1760`;
  - relacion cliente-lista creada.
- Conteos posteriores:
  - `erp_ventas_politicas_apartado`: `1`;
  - `erp_clientes`: `1`;
  - `erp_clientes_identificadores`: `1`;
  - `erp_listas_precios`: `1`;
  - `erp_listas_precios_detalle`: `1`;
  - `erp_clientes_listas_precios`: `1`.
- Validacion cliente/precio posterior:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cliente_precio_apartado_readonly.php --id_almacen=5 --id_sku=1760 --identificador=5550000000`;
  - telefono `5550000000` resuelve cliente `Cliente UAT POS`;
  - `requiere_alta_rapida=false`;
  - precio aplicado `295`;
  - `regla_precio_origen=lista_cliente`;
  - `id_lista_precio=1`;
  - `lista_precio_snapshot=Lista UAT POS`.
- Abono de apartado sigue en dry-run:
  - `schema_pendiente=false`;
  - bloquea porque el script no manda caja/turno;
  - no registra pago real;
  - no mueve caja;
  - no mueve inventario.

## Evidencia tecnica - Atencion persistente real preparada

- Fecha: 2026-06-27
- Codigo preparado:
  - `VentasErp::crearAtencionPersistente()`;
  - `storage/uat/uat_ventas_pos_atencion_persistente_apply_authorized.php`.
- Contrato del metodo:
  - inserta encabezado en `erp_pos_atenciones`;
  - inserta partidas en `erp_pos_atenciones_detalle`;
  - usa transaccion;
  - prevalida antes de escribir;
  - no crea venta;
  - no registra pagos;
  - no reserva inventario;
  - no descuenta inventario;
  - caja debe revalidar stock/precio al cobrar.
- Dry-run ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_atencion_persistente_dryrun_readonly.php --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --cliente="Cliente UAT POS"`
- Resultado dry-run:
  - `ok=true`;
  - almacen `5`;
  - caja `2`;
  - turno `1`;
  - SKU `TP-40352-500GR`;
  - cantidad `1`;
  - total estimado `295`;
  - disponibilidad `1`;
  - faltante `0`;
  - sin bloqueos.
- Guardrail validado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_atencion_persistente_apply_authorized.php`
  - responde `modo=bloqueado` sin `--autorizar=VENTAS_POS_ATENCION_REAL`, respaldo, usuario y partida.
- Comando futuro autorizado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_atencion_persistente_apply_authorized.php --autorizar=VENTAS_POS_ATENCION_REAL --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --telefono=5550000000 --cliente="Cliente UAT POS"`

## Evidencia tecnica - Atencion persistente real ejecutada

- Fecha: 2026-06-27
- Autorizacion recibida:
  - `AUTORIZO CREAR ATENCION POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_sku=1760 cantidad=1 precio=295 telefono=5550000000 cliente="Cliente UAT POS"`
- Comando ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_atencion_persistente_apply_authorized.php --autorizar=VENTAS_POS_ATENCION_REAL --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --telefono=5550000000 --cliente="Cliente UAT POS"`
- Resultado:
  - `ok=true`;
  - `id_atencion_pos=1`;
  - folio temporal `ATN-20260627-094828-088`;
  - cliente `Cliente UAT POS`;
  - `id_cliente=1`;
  - almacen `5`;
  - caja `2`;
  - turno `1`;
  - terminal `2`;
  - partidas `1`;
  - total `295`.
- Detalle creado:
  - SKU `TP-40352-500GR`;
  - cantidad `1`;
  - precio unitario `295`;
  - subtotal/total `295`;
  - estatus `activa`.
- Validacion de bandeja:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_atencion_post_readonly.php --id_almacen=5 --id_atencion=1`;
  - bandeja muestra la atencion `ATN-20260627-094828-088`;
  - estatus `lista_para_cobro`;
  - partidas `1`.
- Validacion de no afectacion:
  - ventas totales siguen en `1` (venta UAT previa);
  - pagos siguen en `1`;
  - movimientos de caja siguen en `2`;
  - pagos temporales de atencion `0`;
  - dry-run posterior de atencion reporta disponibilidad SKU `1760` igual a `1`.
- Alcance:
  - no creo venta;
  - no registro pago;
  - no movio caja;
  - no reservo inventario;
  - no desconto inventario.

## Evidencia tecnica - Conversion/cobro de atencion preparado

- Fecha: 2026-06-27
- Script read-only creado:
  - `storage/uat/uat_ventas_pos_atencion_convertir_preflight_readonly.php`
- Script autorizado extendido:
  - `storage/uat/uat_ventas_pos_venta_apply_authorized.php`
  - ahora acepta `--id_atencion=ID`;
  - al ejecutarse con autorizacion, crea venta POS y marca la atencion como `convertida`.
- Preflight ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_atencion_convertir_preflight_readonly.php --id_atencion=1 --id_usuario=1 --pago=295`
- Resultado preflight:
  - `ok=true`;
  - atencion `ATN-20260627-094828-088`;
  - estatus `lista_para_cobro`;
  - cliente `Cliente UAT POS`;
  - total `295`;
  - almacen `5`;
  - caja `2`;
  - turno `1`;
  - pago efectivo `295`;
  - SKU `1760`;
  - disponibilidad `1`;
  - faltante `0`;
  - `regla_precio_origen=lista_cliente`;
  - bloqueos `[]`.
- Guardrail validado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_apply_authorized.php --id_atencion=1 --id_usuario=1`
  - responde `modo=guardrail` sin `--autorizar=VENTAS_POS_VENTA_REAL` y respaldo valido.
- Alcance si se autoriza:
  - crear venta POS real;
  - crear pago;
  - crear movimiento de caja;
  - descontar inventario/kardex;
  - crear trazabilidad detalle-inventario;
  - marcar atencion `convertida`.
- Riesgo controlado:
  - SKU `1760` tiene disponibilidad `1`; al convertir/cobrar la atencion quedaria en `0`.

## Evidencia tecnica - Atencion POS cobrada y convertida

- Fecha: 2026-06-27
- Autorizacion recibida:
  - `AUTORIZO COBRAR ATENCION POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_atencion=1 pago=295`
- Comando ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_apply_authorized.php --autorizar=VENTAS_POS_VENTA_REAL --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --id_atencion=1 --pago=295`
- Resultado:
  - `ok=true`;
  - venta `id_venta=2`;
  - folio `POS-20260627-000001`;
  - atencion convertida `id_atencion_pos=1`;
  - estatus venta `pagada`;
  - total `295`;
  - pagado `295`;
  - saldo `0`.
- Inventario:
  - SKU `1760`;
  - existencia `34`;
  - movimiento inventario `54`;
  - salida `1`;
  - existencia anterior `1`;
  - existencia nueva `0`;
  - disponible actual `0`.
- Caja/pago:
  - pago `id_venta_pago=2`;
  - movimiento caja `id_movimiento_caja=3`;
  - metodo `Efectivo`;
  - referencia `ATN-1`;
  - monto `295`.
- Atencion posterior:
  - folio temporal `ATN-20260627-094828-088`;
  - estatus `convertida`;
  - bandeja ya no muestra la atencion como abierta/lista para cobro.
- Corte posterior:
  - monto esperado `1090`;
  - contado simulado `1090`;
  - diferencia `0`;
  - ventas `2`;
  - total vendido `590`;
  - pagos efectivo `590`;
  - movimientos caja: monto inicial `500` + venta POS `590`.
- Validacion post-venta:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_venta_readonly.php --folio=POS-20260627-000001`;
  - hallazgos post-venta `[]`;
  - detalle `1`;
  - pago `1`;
  - trazabilidad inventario `1`.
- Reintento/preflight posterior:
  - atencion en estatus `convertida`;
  - disponibilidad SKU `1760` en `0`;
  - bloqueos esperados: atencion no cobrable y existencia insuficiente.
- Hallazgo documentado:
  - `VENTAS-POS-UAT-001`;
  - severidad media;
  - la venta UAT `POS-20260627-000001` se cobro correctamente, pero el script UAT previo no lleno `precio_base`, `precio_aplicado`, `id_lista_precio`, `lista_precio_snapshot` ni `regla_precio_origen` en `erp_ventas_detalle`;
  - el aplicador fue corregido despues para futuras ventas;
  - no se corrige la venta historica sin autorizacion especifica de ajuste de datos.

## Evidencia tecnica - Cierre de turno preparado post-atencion

- Fecha: 2026-06-27
- Turno:
  - `id_turno_caja=1`;
  - folio `TUR-20260626-002-001`;
  - almacen `5`;
  - caja `2`;
  - estatus actual `abierto`.
- Comando dry-run ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=1090`
- Resultado:
  - `ok=true`;
  - monto esperado `1090`;
  - monto contado simulado `1090`;
  - diferencia `0`;
  - ventas `2`;
  - total vendido `590`;
  - pagado `590`;
  - saldo `0`;
  - pagos efectivo `590`;
  - movimientos de caja:
    - monto inicial `500`;
    - ingreso venta POS `590`.
- Guardrail validado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_cierre_apply_authorized.php`
  - responde `modo=bloqueado` sin `--autorizar=VENTAS_POS_TURNO_CIERRE`, respaldo, usuario y monto contado.
- Alcance si se autoriza:
  - actualiza `erp_pos_turnos`;
  - guarda usuario de cierre;
  - guarda monto esperado;
  - guarda monto contado;
  - guarda diferencia;
  - marca turno como `cerrado`;
  - no crea ventas;
  - no mueve inventario.

## Evidencia tecnica - Cierre de turno real ejecutado

- Fecha: 2026-06-27
- Autorizacion recibida:
  - `AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 monto_contado=1090 observaciones="Cierre UAT POS con dos ventas"`
- Script ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_cierre_apply_authorized.php --autorizar=VENTAS_POS_TURNO_CIERRE --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --monto_contado=1090 --observaciones="Cierre UAT POS con dos ventas"`
- Inspector post-cierre creado:
  - `storage/uat/uat_ventas_pos_turno_post_cierre_readonly.php`
- Comando de verificacion:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_post_cierre_readonly.php --id_turno_caja=1`
- Resultado:
  - `ok=true`;
  - turno `TUR-20260626-002-001`;
  - `id_turno_caja=1`;
  - almacen `5`;
  - caja `2`;
  - estatus `cerrado`;
  - usuario apertura `1`;
  - usuario cierre `1`;
  - fecha apertura `2026-06-26 23:04:14`;
  - fecha cierre `2026-06-27 20:07:11`;
  - monto inicial `500`;
  - monto esperado `1090`;
  - monto contado `1090`;
  - diferencia `0`;
  - observaciones `Cierre UAT POS con dos ventas`.
- Resumen ligado al turno:
  - ventas `2`;
  - total vendido `590`;
  - pagos `2`;
  - total pagado `590`;
  - movimientos caja `3`;
  - monto inicial `500`;
  - ingresos por venta POS `590`;
  - total caja esperado `1090`.
- Ventas incluidas:
  - `POS-20260626-000001`, total `295`, pagada, saldo `0`;
  - `POS-20260627-000001`, total `295`, pagada, saldo `0`.
- Pagos incluidos:
  - pago `1`, efectivo `295`, referencia `UAT`;
  - pago `2`, efectivo `295`, referencia `ATN-1`.
- Validacion posterior:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=1090`;
  - resultado esperado post-cierre: bloqueado porque ya no hay turno abierto;
  - asignacion POS sigue activa;
  - `turno_abierto=null`;
  - bloqueo `No hay turno abierto para esta caja`.
- Hallazgos:
  - `[]`.
- Impacto:
  - no creo ventas nuevas;
  - no creo pagos nuevos;
  - no movio inventario;
  - solo actualizo el cierre del turno autorizado.

## Evidencia tecnica - Auditoria caja completa post-cierre

- Fecha: 2026-06-27
- Comando read-only:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_schema_readonly.php`
- Resultado:
  - `ok=true`;
  - tabla `erp_pos_movimientos_caja` existe;
  - falta DDL de caja completa.
- Columnas faltantes:
  - `categoria`;
  - `estatus`;
  - `id_caja`;
  - `id_almacen`;
  - `id_venta`;
  - `id_proveedor`;
  - `responsable`;
  - `requiere_autorizacion`;
  - `autorizado_por`;
  - `fecha_autorizacion`;
  - `requiere_evidencia`;
  - `evidencia_estado`;
  - `evidencia_ruta`;
  - `cancelado_por`;
  - `fecha_cancelacion`;
  - `motivo_cancelacion`;
  - `fecha_actualizacion`.
- Indices faltantes:
  - `idx_pos_movimiento_caja_estado`;
  - `idx_pos_movimiento_categoria`;
  - `idx_pos_movimiento_venta`.
- Impacto si se autoriza:
  - permite clasificar gastos, retiros, ingresos, vales y reembolsos;
  - agrega trazabilidad por caja, almacen, venta, responsable y proveedor;
  - prepara autorizacion/evidencia/cancelacion sin borrar historial;
  - no debe crear ventas ni mover inventario.
- Estado:
  - ejecutado despues con autorizacion explicita;
  - ver seccion siguiente.

## Evidencia tecnica - DDL caja completa aplicado

- Fecha: 2026-06-27
- Autorizacion recibida:
  - `AUTORIZO APLICAR DDL CAJA POS COMPLETA usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`
- Script ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_schema_apply_authorized.php --autorizar=VENTAS_POS_CAJA_DDL --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql"`
- Resultado:
  - `ok=true`;
  - `modo=caja_schema_aplicado`;
  - 17 columnas agregadas;
  - 3 indices agregados.
- Auditoria posterior read-only:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_schema_readonly.php`
- Resultado posterior:
  - tabla `erp_pos_movimientos_caja` existe;
  - columnas requeridas existen;
  - indices requeridos existen;
  - plan posterior solo reporta `La columna ya existe` y `El indice ya existe`.
- Columnas confirmadas:
  - `categoria`;
  - `estatus`;
  - `id_caja`;
  - `id_almacen`;
  - `id_venta`;
  - `id_proveedor`;
  - `responsable`;
  - `requiere_autorizacion`;
  - `autorizado_por`;
  - `fecha_autorizacion`;
  - `requiere_evidencia`;
  - `evidencia_estado`;
  - `evidencia_ruta`;
  - `cancelado_por`;
  - `fecha_cancelacion`;
  - `motivo_cancelacion`;
  - `fecha_actualizacion`.
- Indices confirmados:
  - `idx_pos_movimiento_caja_estado`;
  - `idx_pos_movimiento_categoria`;
  - `idx_pos_movimiento_venta`.
- Validacion de guardrail posterior:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_movimiento_dryrun_readonly.php --id_usuario=1 --tipo=gasto_caja --motivo="Gasto UAT caja" --monto=50 --referencia=GASTO-UAT-001 --responsable="Usuario UAT" --observaciones="Simulacion posterior a cierre"`;
  - resultado `ok=false`;
  - motivo esperado: no hay turno abierto;
  - asignacion POS activa;
  - `turno_abierto=null`.
- Impacto:
  - no creo movimientos de caja reales;
  - no creo ventas;
  - no movio inventario;
  - deja preparada caja completa para movimientos no venta con turno abierto y autorizacion separada.

## Evidencia tecnica - Nuevo turno UAT abierto para caja completa

- Fecha: 2026-06-27
- Autorizacion recibida:
  - `AUTORIZO ABRIR TURNO POS UAT usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 y monto_inicial=500`
- Ajuste tecnico previo:
  - `storage/uat/uat_ventas_pos_turno_apertura_apply_authorized.php` fue actualizado para que, si existe caja completa, el movimiento inicial guarde `id_caja`, `id_almacen`, `categoria`, `estatus`, autorizacion/evidencia y `fecha_actualizacion`;
  - conserva fallback para esquema base.
- Script ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_apertura_apply_authorized.php --autorizar=VENTAS_POS_TURNO_APERTURA --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --monto_inicial=500 --observaciones="UAT POS caja completa"`
- Resultado:
  - `ok=true`;
  - `modo=turno_abierto`;
  - turno `TUR-20260627-002-001`;
  - `id_turno_caja=2`;
  - `id_movimiento_caja=4`;
  - `id_usuario=1`;
  - almacen `5`;
  - caja `2`;
  - monto inicial `500`.
- Verificacion read-only:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_preflight_readonly.php --id_usuario=1`;
  - asignacion POS activa;
  - turno abierto actual `TUR-20260627-002-001`;
  - bloqueo esperado para nueva apertura: `Ya existe turno abierto para esta caja`.
- Movimiento inicial validado:
  - `id_movimiento_caja=4`;
  - `id_turno_caja=2`;
  - `id_caja=2`;
  - `id_almacen=5`;
  - tipo `entrada`;
  - categoria `apertura_turno`;
  - motivo `monto_inicial`;
  - monto `500`;
  - estatus `registrado`;
  - referencia `TUR-20260627-002-001`;
  - `requiere_autorizacion=0`;
  - `requiere_evidencia=0`;
  - creado por `1`.
- Dry-run de gasto post-apertura:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_movimiento_dryrun_readonly.php --id_usuario=1 --tipo=gasto_caja --motivo="Gasto UAT caja" --monto=50 --referencia=GASTO-UAT-001 --responsable="Usuario UAT" --observaciones="Simulacion con turno nuevo"`;
  - `ok=true`;
  - tipo caja `gasto`;
  - categoria `gasto_caja`;
  - impacto esperado `-50`;
  - bloqueos `[]`;
  - avisos: requiere autorizacion y evidencia en flujo real.
- Impacto:
  - se creo un nuevo turno real y su movimiento inicial;
  - no se creo gasto real;
  - no se creo venta;
  - no se movio inventario.

## Evidencia tecnica - Gasto caja POS UAT real

- Fecha: 2026-06-27
- Autorizacion recibida:
  - `AUTORIZO REGISTRAR GASTO CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 tipo=gasto_caja motivo="Gasto UAT caja" monto=50 referencia=GASTO-UAT-001 responsable="Usuario UAT"`
- Preflight read-only:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_movimiento_dryrun_readonly.php --id_usuario=1 --tipo=gasto_caja --motivo="Gasto UAT caja" --monto=50 --referencia=GASTO-UAT-001 --responsable="Usuario UAT" --observaciones="Gasto real UAT autorizado"`;
  - `ok=true`;
  - bloqueos `[]`;
  - impacto esperado `-50`;
  - avisos: requiere autorizacion y evidencia.
- Script ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_movimiento_apply_authorized.php --autorizar=VENTAS_POS_CAJA_MOVIMIENTO_REAL --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --tipo=gasto_caja --motivo="Gasto UAT caja" --monto=50 --referencia=GASTO-UAT-001 --responsable="Usuario UAT" --observaciones="Gasto real UAT autorizado"`
- Resultado:
  - `ok=true`;
  - `modo=movimiento_caja_real`;
  - `id_movimiento_caja=5`;
  - turno `TUR-20260627-002-001`;
  - `id_turno_caja=2`;
  - caja `2`;
  - almacen `5`;
  - tipo caja `gasto`;
  - categoria `gasto_caja`;
  - motivo `gasto_caja`;
  - monto `50`;
  - referencia `GASTO-UAT-001`;
  - responsable `Usuario UAT`;
  - impacto esperado `-50`.
- Verificacion SQL read-only:
  - turno `2` queda abierto;
  - monto inicial `500`;
  - monto esperado `450`;
  - movimiento inicial `id_movimiento_caja=4`, entrada `500`;
  - gasto `id_movimiento_caja=5`, gasto `50`;
  - `requiere_autorizacion=1`;
  - `autorizado_por=1`;
  - `requiere_evidencia=1`;
  - `evidencia_estado=pendiente`;
  - creado por `1`.
- Cierre dry-run con contado correcto:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=450`;
  - `ok=true`;
  - monto esperado `450`;
  - monto contado `450`;
  - diferencia `0`;
  - ventas `0`;
  - movimientos: entrada `500`, gasto `50`.
- Cierre dry-run con contado `500`:
  - `ok=true`;
  - monto esperado `450`;
  - monto contado `500`;
  - diferencia `50`;
  - valida que el corte detecta sobrante si el gasto no se resta fisicamente.
- Impacto:
  - creo movimiento de caja real;
  - actualizo `monto_esperado` del turno de `500` a `450`;
  - no creo ventas;
  - no movio inventario;
  - no cerro turno.

## Evidencia tecnica - Cierre turno caja completa con gasto

- Fecha: 2026-06-27
- Autorizacion recibida:
  - `AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 monto_contado=450 observaciones="Cierre UAT POS con gasto caja"`
- Dry-run previo:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=450`;
  - `ok=true`;
  - turno `TUR-20260627-002-001`;
  - monto esperado `450`;
  - monto contado `450`;
  - diferencia `0`;
  - ventas `0`;
  - movimientos: entrada `500`, gasto `50`.
- Script ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_cierre_apply_authorized.php --autorizar=VENTAS_POS_TURNO_CIERRE --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --monto_contado=450 --observaciones="Cierre UAT POS con gasto caja"`
- Resultado:
  - `ok=true`;
  - `modo=turno_cerrado`;
  - `id_turno_caja=2`;
  - folio `TUR-20260627-002-001`;
  - caja `2`;
  - almacen `5`;
  - monto esperado `450`;
  - monto contado `450`;
  - diferencia `0`.
- Inspector post-cierre:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_post_cierre_readonly.php --id_turno_caja=2`;
  - `ok=true`;
  - estatus `cerrado`;
  - fecha cierre `2026-06-27 20:31:01`;
  - observaciones `Cierre UAT POS con gasto caja`;
  - ventas `0`;
  - pagos `0`;
  - movimientos caja firmados `450`;
  - movimientos caja count `2`;
  - hallazgos `[]`.
- Movimientos incluidos:
  - `id_movimiento_caja=4`, entrada inicial `500`, referencia `TUR-20260627-002-001`;
  - `id_movimiento_caja=5`, gasto `50`, referencia `GASTO-UAT-001`.
- Validacion posterior:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=450`;
  - resultado esperado post-cierre: bloqueado porque ya no hay turno abierto;
  - asignacion POS activa;
  - `turno_abierto=null`.
- Ajuste de herramienta:
  - `storage/uat/uat_ventas_pos_turno_post_cierre_readonly.php` fue ajustado para sumar como salidas los tipos `gasto`, `retiro`, `vale` y `reembolso` en el total firmado de movimientos.
- Impacto:
  - cerro el turno autorizado;
  - no creo ventas;
  - no creo pagos;
  - no movio inventario;
  - dejo caja completa validada con gasto y corte cuadrado.

## Evidencia tecnica - Recarga stock UAT POS post-caja

- Fecha: 2026-06-27
- Autorizacion recibida:
  - `AUTORIZO CARGAR STOCK UAT POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=2`
- Preflight read-only:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_stock_uat_preflight_readonly.php --id_almacen=5 --id_sku=1760 --cantidad=2`;
  - `ok=true`;
  - almacen `5`, `MASCOTAS971`;
  - SKU `1760`, `TP-40352-500GR`;
  - producto `Alimento churro rojo para peces agranel`;
  - inventariable;
  - permite venta fraccionaria `1`;
  - precio general `295`;
  - bloqueos `[]`.
- Intento bloqueado por regla de InventarioErp:
  - referencia `POS-UAT-STOCK-20260627-A5-S1760-R2`;
  - resultado `ok=false`;
  - mensaje `La referencia de inventario inicial debe iniciar con INV-INICIAL-`;
  - no genero movimiento ni existencia.
- Script ejecutado con referencia valida:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_stock_uat_apply_authorized.php --autorizar=VENTAS_POS_STOCK_UAT --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=2 --referencia=INV-INICIAL-POS-UAT-20260627-A5-S1760-R2`
- Resultado:
  - `ok=true`;
  - `modo=stock_uat_cargado`;
  - referencia `INV-INICIAL-POS-UAT-20260627-A5-S1760-R2`;
  - movimientos `1`;
  - origen `inventario_inicial`;
  - motivo `inventario_inicial`.
- Verificacion existencia:
  - `id_existencia_inventario=34`;
  - codigo `EXI-1016-34`;
  - almacen `5`;
  - SKU `1760`;
  - cantidad `2`;
  - apartada `0`;
  - disponible `2`;
  - costo promedio `95`;
  - ultimo movimiento `55`;
  - estatus `disponible`.
- Verificacion kardex:
  - `id_movimiento_inventario=55`;
  - referencia `INV-INICIAL-POS-UAT-20260627-A5-S1760-R2`;
  - tipo `entrada`;
  - origen `inventario_inicial`;
  - cantidad `2`;
  - existencia anterior `0`;
  - existencia nueva `2`;
  - existencia `34`.
- Preflight venta posterior:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_preflight_readonly.php --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295`;
  - encuentra stock suficiente;
  - plan salida por existencia agregada `34`;
  - existencia antes `2`;
  - existencia despues estimada `1`;
  - faltante `0`;
  - bloqueos esperados: no hay turno abierto y falta seleccionar turno abierto de caja.
- Impacto:
  - cargo stock por modelo oficial de inventario;
  - genero kardex;
  - no creo venta;
  - no creo pago;
  - no abrio turno.

## Evidencia tecnica - Turno UAT abierto para venta/ticket

- Fecha: 2026-06-27
- Autorizacion recibida:
  - `AUTORIZO ABRIR TURNO POS UAT usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 y monto_inicial=500`
- Preflight previo:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_preflight_readonly.php --id_usuario=1`;
  - asignacion POS activa;
  - sin turno abierto previo.
- Script ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_apertura_apply_authorized.php --autorizar=VENTAS_POS_TURNO_APERTURA --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --monto_inicial=500 --observaciones="UAT POS venta ticket"`
- Resultado:
  - `ok=true`;
  - `modo=turno_abierto`;
  - turno `TUR-20260627-002-002`;
  - `id_turno_caja=3`;
  - `id_movimiento_caja=6`;
  - almacen `5`;
  - caja `2`;
  - monto inicial `500`.
- Movimiento inicial:
  - `id_movimiento_caja=6`;
  - `id_turno_caja=3`;
  - `id_caja=2`;
  - `id_almacen=5`;
  - tipo `entrada`;
  - categoria `apertura_turno`;
  - motivo `monto_inicial`;
  - monto `500`;
  - estatus `registrado`;
  - referencia `TUR-20260627-002-002`;
  - creado por `1`.
- Preflight venta posterior:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_preflight_readonly.php --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295`;
  - `puede_vender_real=true`;
  - asignacion activa `true`;
  - turno abierto `true`;
  - `id_turno_caja=3`;
  - total `295`;
  - pago `295`;
  - saldo `0`;
  - cambio `0`;
  - plan salida desde existencia agregada `34`;
  - existencia antes `2`;
  - existencia despues estimada `1`;
  - faltante `0`;
  - bloqueos `[]`.
- Impacto:
  - abrio turno real para venta/ticket;
  - no creo venta;
  - no creo pago;
  - no movio inventario.

## Evidencia tecnica - Venta POS UAT real post-caja

- Fecha: 2026-06-27
- Autorizacion recibida:
  - `AUTORIZO EJECUTAR VENTA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_sku=1760 cantidad=1 precio=295 pago=295`
- Preflight previo:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_preflight_readonly.php --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295`;
  - `puede_vender_real=true`;
  - asignacion activa `true`;
  - turno abierto `true`;
  - `id_turno_caja=3`;
  - total `295`;
  - pago `295`;
  - saldo `0`;
  - existencia `34`;
  - existencia antes `2`;
  - existencia despues estimada `1`;
  - bloqueos `[]`.
- Script ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_apply_authorized.php --autorizar=VENTAS_POS_VENTA_REAL --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295 --observaciones="UAT POS venta ticket"`
- Resultado:
  - `ok=true`;
  - folio `POS-20260627-000002`;
  - `id_venta=3`;
  - estatus `pagada`;
  - subtotal `295`;
  - total `295`;
  - pagado `295`;
  - saldo `0`.
- Detalle:
  - `id_venta_detalle=3`;
  - SKU `1760`;
  - codigo `TP-40352-500GR`;
  - descripcion `Alimento churro rojo para peces 500 gr`;
  - cantidad `1`;
  - unidad venta/base `kg`;
  - modo salida `existencia_agregada`;
  - precio unitario `295`;
  - precio base `295`;
  - precio aplicado `295`;
  - lista precio `1`;
  - snapshot `Lista UAT POS`;
  - regla precio `lista_canal_sucursal`;
  - descuento `0`;
  - total `295`.
- Pago/caja:
  - `id_venta_pago=3`;
  - metodo `Efectivo`;
  - monto `295`;
  - `id_movimiento_caja=7`;
  - movimiento tipo `ingreso`;
  - motivo `venta_pos`;
  - referencia `POS-20260627-000002`.
- Inventario:
  - `id_existencia_inventario=34`;
  - codigo existencia `EXI-1016-34`;
  - `id_movimiento_inventario=56`;
  - tipo movimiento `salida`;
  - origen `venta_pos`;
  - referencia `POS-20260627-000002`;
  - cantidad salida `1`;
  - existencia anterior `2`;
  - existencia nueva `1`;
  - disponible actual `1`;
  - faltante `0`.
- Post-venta read-only:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_venta_readonly.php --folio=POS-20260627-000002`;
  - `ok=true`;
  - suma detalle `295`;
  - pagos registrados `295`;
  - partidas `1`;
  - pagos `1`;
  - trazabilidades `1`;
  - hallazgos `[]`.
- Corte dry-run posterior:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=795`;
  - `ok=true`;
  - turno `TUR-20260627-002-002`;
  - monto esperado `795`;
  - monto contado `795`;
  - diferencia `0`;
  - ventas `1`;
  - total vendido `295`;
  - pagos efectivo `295`;
  - movimientos: entrada inicial `500`, ingreso venta POS `295`.
- Hallazgo `VENTAS-POS-UAT-002`:
  - severidad baja/media;
  - la venta `POS-20260627-000002` es funcionalmente correcta, con pago, kardex y trazabilidad;
  - el movimiento caja `7` fue insertado por el aplicador UAT con columnas de caja completa `id_caja`, `id_almacen` y `categoria` en `NULL`;
  - no se corrige el registro historico sin autorizacion especifica de ajuste de datos;
  - `storage/uat/uat_ventas_pos_venta_apply_authorized.php` fue corregido despues para que futuras ventas inserten `id_caja`, `id_almacen`, `categoria=venta_pos`, `estatus=registrado` y `fecha_actualizacion` cuando exista caja completa.
- Impacto:
  - creo venta real;
  - creo pago;
  - creo ingreso de caja;
  - genero kardex de salida;
  - desconto existencia de `2` a `1`;
  - no cerro turno.

## Evidencia tecnica - Cierre turno venta/ticket

- Fecha: 2026-06-27
- Autorizacion recibida:
  - `AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS con venta ticket"`
- Dry-run previo:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=795`;
  - `ok=true`;
  - turno `TUR-20260627-002-002`;
  - monto esperado `795`;
  - monto contado `795`;
  - diferencia `0`;
  - ventas `1`;
  - total vendido `295`;
  - pagos efectivo `295`;
  - movimientos: entrada inicial `500`, ingreso venta POS `295`.
- Script ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_cierre_apply_authorized.php --autorizar=VENTAS_POS_TURNO_CIERRE --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --monto_contado=795 --observaciones="Cierre UAT POS con venta ticket"`
- Resultado:
  - `ok=true`;
  - `modo=turno_cerrado`;
  - `id_turno_caja=3`;
  - folio `TUR-20260627-002-002`;
  - caja `2`;
  - almacen `5`;
  - monto esperado `795`;
  - monto contado `795`;
  - diferencia `0`.
- Inspector post-cierre:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_post_cierre_readonly.php --id_turno_caja=3`;
  - `ok=true`;
  - estatus `cerrado`;
  - fecha cierre `2026-06-27 20:56:17`;
  - observaciones `Cierre UAT POS con venta ticket`;
  - ventas `1`;
  - pagos `1`;
  - movimientos caja firmados `795`;
  - movimientos caja count `2`;
  - hallazgos `[]`.
- Venta incluida:
  - `POS-20260627-000002`;
  - total `295`;
  - pagado `295`;
  - saldo `0`.
- Movimientos incluidos:
  - `id_movimiento_caja=6`, entrada inicial `500`, referencia `TUR-20260627-002-002`;
  - `id_movimiento_caja=7`, ingreso venta POS `295`, referencia `POS-20260627-000002`.
- Validacion posterior:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=795`;
  - resultado esperado post-cierre: bloqueado porque ya no hay turno abierto;
  - asignacion POS activa;
  - `turno_abierto=null`.
- Existencia posterior:
  - existencia `34`;
  - SKU `1760`;
  - cantidad `1`;
  - disponible `1`;
  - ultimo movimiento `56`;
  - estatus `disponible`.
- Impacto:
  - cerro turno con venta/ticket UAT;
  - no creo nuevas ventas;
  - no creo nuevos pagos;
  - no movio inventario adicional;
  - deja venta, caja e inventario cuadrados.

## Evidencia tecnica - Ticket formal POS read-only

- Fecha: 2026-06-27
- Alcance:
  - ticket formal desde venta confirmada;
  - read-only;
  - no fiscal;
  - no reabre turno;
  - no recalcula venta historica;
  - no usa ecommerce legacy.
- Cambios aplicados:
  - `app/controladores/Ventas.php` agrega endpoint `ticket_venta_readonly_erp`;
  - `app/modelos/VentasErp.php` agrega `ticketVentaFormalReadOnly`;
  - `storage/uat/uat_ventas_pos_ticket_formal_readonly.php` permite UAT por CLI sin sesion web.
- Correccion tecnica:
  - se elimino un join incorrecto a tabla `usuarios`;
  - la BD actual usa `sys_usuarios`, pero el ticket formal no requiere datos de usuario por ahora;
  - se ajusto la linea de caja para no exceder el ancho del ticket.
- Comando:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260627-000002`
- Resultado:
  - `ok=true`;
  - `modo=ticket_formal_readonly`;
  - venta `POS-20260627-000002`;
  - lineas ticket `28`;
  - total `295`;
  - pagado `295`;
  - saldo `0`;
  - pago `Efectivo`;
  - precio snapshot `Lista UAT POS`;
  - inventario trazado `1` movimiento.
- Contenido clave del ticket:
  - encabezado `ARTIANI ERP`;
  - folio `POS-20260627-000002`;
  - fecha venta `2026-06-27 20:43:45`;
  - tienda `Mascotas Mina 971`;
  - caja `CJ-MASCOTAS971-01 Caja principal ...`;
  - turno `TUR-20260627-002-002`;
  - cliente `Cliente mostrador UAT`;
  - SKU `TP-40352-500GR`;
  - descripcion `Alimento churro rojo para peces 500 gr`;
  - cantidad `1.000 kg`;
  - precio `295`;
  - total `295`;
  - operacion `PAGADA`;
  - leyenda `No fiscal. Conserve este ticket.`
- Hallazgo `VENTAS-TICKET-001`:
  - severidad media;
  - la partida `3` no tiene snapshot de garantia guardado en `erp_ventas_detalle_garantias`;
  - el ticket muestra `Garantia: pendiente snapshot`;
  - no debe recalcular garantia viva para venta historica;
  - siguiente tarea tecnica: integrar guardado de snapshot de garantia al confirmar venta POS real.
- Impacto:
  - permite preview/reimpresion formal read-only por folio;
  - confirma precio/lista/caja/turno/pagos/inventario;
  - deja pendiente impresion UI y snapshot real de garantia.

## Evidencia tecnica - Acceso visual POS

- Fecha: 2026-06-27
- Hallazgo: el POS existe en ruta `/ventas/pos` y el menu lateral lo declara bajo Ventas, pero requiere permiso `ventas.operar`.
- Diagnostico read-only: `storage/uat/uat_ventas_pos_acceso_readonly.php --id_usuario=1`.
- Resultado usuario `id_usuario=1`: tiene `ventas.ver`, no tiene `ventas.operar`.
- Efecto visual: puede ver Ventas/Tablero, pero no ve el item POS y la ruta `/ventas/pos` responde permiso denegado.
- Correccion de codigo aplicada: `app/modelos/SeguridadEsquema.php` ahora incluye `ventas.operar` dentro del rol base `administrador_erp` para futuras sincronizaciones.
- Script autorizado preparado pero no ejecutado: `storage/uat/uat_ventas_pos_permiso_operar_apply_authorized.php`.
- Guardrail validado: el script bloquea sin `--autorizar=VENTAS_POS_PERMISO_OPERAR` y respaldo valido.
- Siguiente paso bloqueado por autorizacion: asignar `ventas.operar` al rol `administrador_erp` en BD actual, cerrar sesion o recargar para refrescar permisos, y validar UI con Playwright.

### Validacion Playwright de ruta real

- Fecha: 2026-06-27
- Script creado: `storage/uat/uat_ventas_pos_playwright_real_route.js`
- Comando ejecutado:
  - `C:\Users\aleja\AppData\Local\Programs\nodejs-portable-v24.16.0\node.exe storage\uat\uat_ventas_pos_playwright_real_route.js`
- URL solicitada: `http://dashboard.com.local/ventas/pos`
- URL final: `http://dashboard.com.local/autenticacion/login`
- Status HTTP: `200`
- Resultado: `ok=false`, porque Playwright abre contexto limpio sin sesion autenticada.
- Diagnostico:
  - `requiereLogin=true`
  - `posiblePermiso=false`
  - `errorNavegacion=null`
  - consola sin errores registrados.
- Evidencia visual generada:
  - `public/storage/uat/pos_playwright_real_route.png`
- Interpretacion:
  - No es una falla del POS ni de permisos actuales del usuario en su navegador.
  - Es una limitante normal de una prueba Playwright headless sin credenciales/sesion.
- Para validar UX real con Playwright se requiere usuario de prueba, credenciales autorizadas o una estrategia de sesion persistente autorizada.

## Evidencia tecnica - Preparacion snapshot garantia POS

- Fecha: 2026-06-27
- Alcance:
  - preparar guardado de snapshot de garantia al confirmar venta POS real;
  - no backfill de ventas historicas;
  - no venta nueva;
  - no movimiento de inventario;
  - no escritura BD durante la validacion read-only.
- Cambios aplicados:
  - `app/modelos/GarantiasErp.php` agrega `guardarSnapshotsVenta`;
  - `storage/uat/uat_ventas_pos_venta_apply_authorized.php` llama a Garantias dentro de la transaccion de venta UAT autorizada;
  - la proxima venta real autorizada guardara `erp_ventas_detalle_garantias` antes del commit;
  - si Garantias bloquea el snapshot, la venta completa hace rollback.
- Validacion tecnica:
  - `C:\xampp\php\php.exe -l app\modelos\GarantiasErp.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_venta_apply_authorized.php`;
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`.
- Dry-run ejecutado:
  - SKU `1760`;
  - almacen `5`;
  - canal `pos`;
  - fecha `2026-06-27`.
- Resultado:
  - `error=false`;
  - `tipo=success`;
  - sin bloqueos;
  - el SKU `1760` no tiene garantia configurada;
  - snapshot sugerido `Sin garantia`;
  - `fecha_vencimiento=null`;
  - alerta informativa `politica_no_configurada`.
- Decision:
  - una venta sin garantia configurada tambien debe guardar snapshot;
  - el ticket debe mostrar `Sin garantia`, no `pendiente snapshot`;
  - no se debe recalcular garantia viva en tickets historicos.
- Siguiente paso bloqueado por autorizacion:
  - abrir turno si no existe;
  - ejecutar una nueva venta POS real con SKU disponible;
  - validar que el ticket ya no emita `VENTAS-TICKET-001` para la venta nueva.

## Evidencia tecnica - Reimpresion ticket desde tablero Ventas

- Fecha: 2026-06-27
- Alcance:
  - habilitar consulta visual del ticket formal desde `Ventas ERP`;
  - read-only;
  - no crea venta;
  - no reabre turno;
  - no modifica pagos, caja, garantia ni inventario.
- Cambios aplicados:
  - `app/vistas/paginas/apps/erp/ventas/listado.php` agrega modal `Ticket POS`;
  - `public/assets/js/custom/apps/erp/ventas/listado.js` agrega accion por folio, carga `/ventas/ticket_venta_readonly_erp` y permite imprimir/reimprimir;
  - version de asset actualizada a `20260627-ticket1`.
- Validaciones:
  - `node --check public\assets\js\custom\apps\erp\ventas\listado.js`;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\listado.php`.
- Comportamiento esperado:
  - el listado muestra boton con icono de recibo por folio;
  - abre modal con ticket formal;
  - si hay hallazgos, los muestra antes de imprimir;
  - imprime solo el texto del ticket en formato monoespaciado.
- Pendiente:
  - validar visualmente con sesion web real;
  - para Playwright se requiere sesion/credenciales autorizadas.

## Evidencia tecnica - Post-venta con garantia read-only

- Fecha: 2026-06-27
- Alcance:
  - ampliar el inspector post-venta para validar snapshot de garantia;
  - read-only;
  - no corrige ventas historicas;
  - no crea snapshots;
  - no mueve inventario.
- Cambio aplicado:
  - `storage/uat/uat_ventas_pos_post_venta_readonly.php` ahora consulta `erp_ventas_detalle_garantias`.
- Validacion tecnica:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_post_venta_readonly.php`.
- Comando ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_venta_readonly.php --folio=POS-20260627-000002`
- Resultado:
  - `ok=false`;
  - venta `pagada`;
  - total `295`;
  - pagos `295`;
  - partidas `1`;
  - trazabilidades `1`;
  - garantias `0`;
  - hallazgo `VENTAS-POST-009`.
- Interpretacion:
  - el inspector detecta correctamente que la venta historica no tiene snapshot de garantia;
  - la proxima venta autorizada debe responder `ok=true`, `garantias=1` y sin `VENTAS-POST-009`;
  - no se debe hacer backfill de esta venta historica sin autorizacion especifica.

## Evidencia tecnica - Turno abierto para validar snapshot garantia

- Fecha: 2026-06-27
- Autorizacion recibida:
  - `AUTORIZO ABRIR TURNO POS UAT usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 y monto_inicial=500`
- Primer intento:
  - bloqueado correctamente por guardrail;
  - token usado no coincidia con el requerido por script;
  - no escribio BD.
- Ejecucion autorizada:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_apertura_apply_authorized.php --autorizar=VENTAS_POS_TURNO_APERTURA --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --monto_inicial=500`
- Resultado:
  - `ok=true`;
  - turno `TUR-20260627-002-003`;
  - `id_turno_caja=4`;
  - `id_movimiento_caja=8`;
  - usuario `1`;
  - almacen `5`;
  - caja `2`;
  - monto inicial `500`.
- Verificacion read-only:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_preflight_readonly.php --id_usuario=1 --monto_inicial=500`
  - turno abierto actual `TUR-20260627-002-003`;
  - bloqueo esperado para nueva apertura: `Ya existe turno abierto para esta caja`.
- Preflight venta:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_preflight_readonly.php --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295`
- Resultado preflight:
  - `puede_vender_real=true`;
  - `turno_abierto=true`;
  - `id_turno_caja=4`;
  - subtotal/total/pago `295`;
  - saldo `0`;
  - salida desde existencia `34`;
  - existencia antes `1`;
  - existencia despues estimada `0`;
  - faltante `0`;
  - bloqueos `[]`.
- Siguiente paso bloqueado por autorizacion:
  - ejecutar venta real UAT para validar snapshot de garantia guardado.

## Evidencia tecnica - Venta POS con snapshot garantia

- Fecha: 2026-06-27
- Autorizacion recibida:
  - `AUTORIZO EJECUTAR VENTA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_sku=1760 cantidad=1 precio=295 pago=295`
- Comando ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_apply_authorized.php --autorizar=VENTAS_POS_VENTA_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295 --observaciones="UAT POS venta snapshot garantia"`
- Resultado venta:
  - `ok=true`;
  - folio `POS-20260627-000003`;
  - `id_venta=4`;
  - estatus `pagada`;
  - subtotal/total/pagado `295`;
  - saldo `0`.
- Inventario:
  - SKU `1760`;
  - existencia `34`;
  - movimiento inventario `57`;
  - salida `1`;
  - existencia anterior `1`;
  - existencia nueva `0`;
  - disponible actual `0`;
  - referencia kardex `POS-20260627-000003`.
- Caja/pago:
  - turno `TUR-20260627-002-003`;
  - `id_turno_caja=4`;
  - pago efectivo `295`;
  - `id_venta_pago=4`;
  - movimiento caja `9`;
  - categoria movimiento `venta_pos`.
- Garantia:
  - `id_venta_detalle_garantia=1`;
  - detalle `4`;
  - SKU `1760`;
  - tipo snapshot `sin_garantia`;
  - resumen ticket `Sin garantia`;
  - vencimiento `null`;
  - estatus `vigente`.
- Post-venta read-only:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_venta_readonly.php --folio=POS-20260627-000003`;
  - `ok=true`;
  - partidas `1`;
  - pagos `1`;
  - garantias `1`;
  - trazabilidades `1`;
  - hallazgos `[]`.
- Ticket formal read-only:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260627-000003`;
  - `ok=true`;
  - lineas `28`;
  - hallazgos `[]`;
  - muestra `Garantia: Sin garantia`;
  - ya no emite `VENTAS-TICKET-001`.
- Cierre dry-run:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=795`;
  - monto esperado `795`;
  - monto contado `795`;
  - diferencia `0`;
  - ventas `1`;
  - pagos efectivo `295`;
  - movimientos: apertura `500` + venta `295`.
- Siguiente paso bloqueado por autorizacion:
  - cerrar turno real con monto contado `795`.

## Evidencia tecnica - Cierre turno con snapshot garantia

- Fecha: 2026-06-27
- Autorizacion recibida:
  - `AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS con snapshot garantia"`
- Primer intento:
  - bloqueado correctamente por guardrail;
  - token usado no coincidia con el requerido por script;
  - no cerro turno ni escribio cierre.
- Ejecucion autorizada:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_cierre_apply_authorized.php --autorizar=VENTAS_POS_TURNO_CIERRE --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --monto_contado=795 --observaciones="Cierre UAT POS con snapshot garantia"`
- Resultado:
  - `ok=true`;
  - turno `TUR-20260627-002-003`;
  - `id_turno_caja=4`;
  - usuario cierre `1`;
  - almacen `5`;
  - caja `2`;
  - monto esperado `795`;
  - monto contado `795`;
  - diferencia `0`;
  - estatus `cerrado`.
- Resumen operativo:
  - ventas `1`;
  - total vendido `295`;
  - pagado `295`;
  - saldo `0`;
  - pago efectivo `295`;
  - movimientos caja:
    - apertura `500`;
    - venta POS `295`;
  - esperado por movimientos `795`.
- Verificacion post-cierre:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_post_cierre_readonly.php --id_turno_caja=4`
  - `ok=true`;
  - hallazgos `[]`;
  - ventas `295`;
  - pagos `295`;
  - movimientos caja `795`;
  - ya no hay turno abierto para la caja.
- Verificacion post-venta:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_venta_readonly.php --folio=POS-20260627-000003`
  - `ok=true`;
  - garantias `1`;
  - trazabilidades `1`;
  - hallazgos `[]`.
- Estado final:
  - venta con snapshot de garantia validada end-to-end;
  - turno cerrado y cuadrado;
  - SKU `1760` queda sin disponibilidad UAT.

## Evidencia tecnica - Cliente alta rapida dry-run

- Fecha: 2026-06-27
- Alcance:
  - preparar alta rapida de cliente desde POS;
  - read-only/dry-run;
  - no crea cliente;
  - no crea identificador;
  - no mezcla clientes legacy/ecommerce;
  - no asigna lista ni modifica ventas.
- Cambios aplicados:
  - `app/controladores/Ventas.php` agrega endpoint `pos_cliente_alta_rapida_dryrun_erp`;
  - `app/modelos/VentasErp.php` agrega `clienteAltaRapidaDryRun`;
  - `app/vistas/paginas/apps/erp/ventas/pos.php` agrega bloque `Alta rapida dry-run`;
  - `public/assets/js/custom/apps/erp/ventas/pos.js` conecta validacion de alta rapida;
  - `storage/uat/uat_ventas_pos_cliente_alta_rapida_dryrun_readonly.php` agrega prueba CLI read-only.
- Validaciones tecnicas:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_cliente_alta_rapida_dryrun_readonly.php`;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`.
- Caso telefono nuevo:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cliente_alta_rapida_dryrun_readonly.php --id_usuario=1 --id_almacen=5 --nombre="Cliente Nuevo UAT POS" --identificador=5551112222 --consentimiento=1`;
  - `puede_crear=true`;
  - codigo sugerido `CL-POS-20260627-0001`;
  - tipo identificador `telefono`;
  - normalizado `5551112222`;
  - bloqueos `[]`.
- Caso duplicado:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cliente_alta_rapida_dryrun_readonly.php --id_usuario=1 --id_almacen=5 --nombre="Cliente Duplicado UAT" --identificador=5550000000 --consentimiento=1`;
  - `puede_crear=false`;
  - bloqueo `Ya existe cliente con ese identificador; selecciona coincidencia antes de crear`;
  - coincidencia `CL-UAT-POS-001`, `Cliente UAT POS`.
- Caso precio/lista existente:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cliente_precio_apartado_readonly.php --id_almacen=5 --id_sku=1760 --identificador=5550000000`;
  - cliente `Cliente UAT POS`;
  - regla precio `lista_cliente`;
  - lista `Lista UAT POS`;
  - precio aplicado `295`.
- Decision:
  - el POS puede validar alta rapida, pero la escritura real debe ser una autorizacion separada;
  - si hay duplicado, el cajero debe seleccionar cliente existente antes de crear;
  - venta mostrador puede continuar sin cliente.
- Siguiente paso bloqueado por autorizacion:
  - crear aplicador autorizado de alta rapida real o pasar a apartados/abonos dry-run.

## Evidencia tecnica - Aplicador alta rapida cliente preparado

- Fecha: 2026-06-28
- Alcance:
  - preparar escritura real autorizada de cliente express POS;
  - no ejecutada;
  - no crea cliente durante esta evidencia;
  - no mezcla clientes legacy/ecommerce;
  - no crea ventas, pagos ni movimientos de caja.
- Cambios aplicados:
  - `app/modelos/VentasErp.php` agrega `clienteAltaRapidaCrearAutorizado`;
  - `storage/uat/uat_ventas_pos_cliente_alta_rapida_apply_authorized.php` agrega aplicador CLI bloqueado por defecto;
  - el aplicador exige respaldo existente, `id_usuario` y token `VENTAS_POS_CLIENTE_ALTA_RAPIDA`.
- Guardrails:
  - ejecuta `clienteAltaRapidaDryRun` antes de escribir;
  - bloquea identificador duplicado;
  - usa lock MySQL por identificador normalizado;
  - escribe `erp_clientes` y `erp_clientes_identificadores` en transaccion;
  - libera lock y hace rollback si falla.
- Validaciones tecnicas:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_cliente_alta_rapida_apply_authorized.php`.
- Guardrail validado:
  - comando sin `--autorizar=VENTAS_POS_CLIENTE_ALTA_RAPIDA`;
  - resultado `ok=false`;
  - respaldo validado como existente y legible;
  - no escribio BD.
- Nota operativa:
  - durante la validacion MySQL estaba detenido y se levanto `mysqld` de XAMPP;
  - despues `mysqladmin ping` respondio `mysqld is alive`.
- Dry-run final telefono nuevo:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cliente_alta_rapida_dryrun_readonly.php --id_usuario=1 --id_almacen=5 --nombre="Cliente Nuevo UAT POS" --identificador=5551112222 --consentimiento=1`;
  - `puede_crear=true`;
  - codigo sugerido `CL-POS-20260628-0001`;
  - bloqueos `[]`.
- Dry-run final duplicado:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cliente_alta_rapida_dryrun_readonly.php --id_usuario=1 --id_almacen=5 --nombre="Cliente Duplicado UAT" --identificador=5550000000 --consentimiento=1`;
  - `puede_crear=false`;
  - coincidencia `CL-UAT-POS-001`;
  - bloqueo por identificador existente.
- Siguiente paso bloqueado por autorizacion:
  - ejecutar alta rapida real UAT con telefono nuevo.

## Cierre UAT

## Evidencia tecnica - Excepcion comercial POS dry-run

- Fecha: 2026-06-28
- Alcance:
  - simular precio manual, descuento por partida y descuento general;
  - no escribir BD;
  - no modificar carrito;
  - no crear venta, pago, caja ni kardex.
- Cambios aplicados:
  - `app/controladores/Ventas.php` agrega endpoint `pos_excepcion_comercial_dryrun_erp`;
  - `app/modelos/VentasErp.php` agrega `excepcionComercialDryRun`;
  - `app/vistas/paginas/apps/erp/ventas/pos.php` agrega bloque `Excepcion comercial dry-run`;
  - `public/assets/js/custom/apps/erp/ventas/pos.js` conecta simulacion y render;
  - `storage/uat/uat_ventas_pos_excepcion_comercial_dryrun_readonly.php` agrega prueba CLI read-only.
- Validaciones tecnicas:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_excepcion_comercial_dryrun_readonly.php`;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`.
- Prueba ejecutada:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_comercial_dryrun_readonly.php --id_almacen=5 --id_sku=1760 --identificador=5550000000 --precio_manual=285 --descuento_monto=20`;
  - `ok=true`;
  - cliente `CL-UAT-POS-001`, `Cliente UAT POS`;
  - SKU `TP-40352-500GR`;
  - precio lista `295`;
  - lista `Lista UAT POS`.
- Caso sin autorizacion:
  - tipo `warning`;
  - bloqueos:
    - `Captura motivo obligatorio de la excepcion comercial`;
    - `Falta codigo/autorizacion de supervisor`.
- Caso precio manual:
  - motivo `UAT precio manual`;
  - autorizacion `SUP-UAT-001`;
  - precio final estimado `285`;
  - descuento estimado `10`;
  - aviso de margen minimo pendiente porque queda por debajo del precio base.
- Caso descuento general:
  - motivo `UAT descuento general`;
  - autorizacion `SUP-UAT-002`;
  - descuento total estimado `20`;
  - total estimado `275`.
- Decision:
  - el POS puede simular excepciones comerciales, pero la venta real sigue bloqueada hasta crear permisos, politica de margen minimo y snapshot persistente.
- Siguiente paso:
  - definir/aplicar permisos de supervisor y preparar guardrail para que venta real rechace cualquier precio recibido distinto al backend sin excepcion autorizada.

## Evidencia tecnica - Guardrail precio POS backend

- Fecha: 2026-06-28
- Alcance:
  - fortalecer prevalidacion POS contra precio alterado en navegador;
  - no escribir BD;
  - no crear venta, pago, caja ni kardex.
- Cambios aplicados:
  - `VentasErp::prevalidarCarritoPos` ahora resuelve `canal`, cliente e identificador;
  - `VentasErp::prevalidarPartida` recalcula precio por backend/lista;
  - `prevalidarPartida` devuelve `precio_enviado_pos`, `precio_base`, `precio_aplicado`, `id_lista_precio`, `lista_precio_snapshot` y `regla_precio_origen`;
  - si `precio_enviado_pos` difiere de `precio_aplicado`, agrega bloqueo;
  - `public/assets/js/custom/apps/erp/ventas/pos.js` manda `identificador_cliente` desde la cuenta activa;
  - `storage/uat/uat_ventas_pos_venta_apply_authorized.php` conserva cliente/identificador al cobrar atencion;
  - `storage/uat/uat_ventas_pos_precio_guardrail_readonly.php` valida el candado.
- Validaciones tecnicas:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_venta_apply_authorized.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_precio_guardrail_readonly.php`;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`.
- Prueba ejecutada:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_precio_guardrail_readonly.php --id_almacen=5 --id_sku=1760 --identificador=5550000000 --precio_correcto=295 --precio_alterado=285`;
  - `ok=true`;
  - cliente `CL-UAT-POS-001`;
  - SKU `TP-40352-500GR`;
  - precio correcto:
    - `precio_enviado_pos=295`;
    - `precio_aplicado=295`;
    - lista `Lista UAT POS`.
  - precio alterado:
    - `precio_enviado_pos=285`;
    - `precio_unitario=295`;
    - `subtotal=295`;
    - bloqueo `Precio enviado por POS no coincide con el precio autorizado por backend; usa excepcion comercial autorizada`.
- Observacion:
  - tambien aparece `Existencia insuficiente` porque SKU `1760` quedo sin stock UAT; no afecta el resultado del guardrail de precio.
- Decision:
  - la venta real UAT que use `prevalidarCarritoPos` queda protegida contra precio manipulado por navegador.
- Siguiente paso:
  - crear permisos/politica persistente de excepciones comerciales para permitir precio manual real solo con autorizacion y snapshot.

## Evidencia tecnica - Permisos POS excepcion comercial preparados

- Fecha: 2026-06-28
- Alcance:
  - preparar permisos finos para precio manual/descuentos POS;
  - no sembrar permisos en BD;
  - no asignar roles reales;
  - no cambiar sesiones.
- Cambios aplicados:
  - `app/modelos/SeguridadEsquema.php` agrega permisos base:
    - `ventas.precio_manual`;
    - `ventas.descuento_partida`;
    - `ventas.descuento_general`;
    - `ventas.autorizar_excepcion_comercial`.
  - Roles base en codigo con esos permisos:
    - `direccion`;
    - `administrador_erp`.
  - `storage/uat/uat_ventas_pos_excepcion_permisos_readonly.php` audita codigo vs BD.
  - `storage/uat/uat_ventas_pos_excepcion_permisos_apply_authorized.php` prepara siembra autorizada bloqueada por token.
- Validaciones tecnicas:
  - `C:\xampp\php\php.exe -l app\modelos\SeguridadEsquema.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_excepcion_permisos_readonly.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_excepcion_permisos_apply_authorized.php`;
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_permisos_readonly.php`.
- Guardrail aplicador:
  - comando sin `--autorizar=VENTAS_POS_EXCEPCION_PERMISOS`;
  - resultado `ok=false`;
  - mensaje `No se sembraron permisos POS`;
  - no escribio BD.
- Resultado auditoria read-only:
  - `ok=true`;
  - permisos esperados en codigo `4`;
  - roles codigo `direccion` y `administrador_erp`;
  - permisos en BD actual `0`;
  - faltantes BD:
    - `ventas.precio_manual`;
    - `ventas.descuento_partida`;
    - `ventas.descuento_general`;
    - `ventas.autorizar_excepcion_comercial`.
- Decision:
  - no se permite aplicar excepcion comercial real hasta sembrar permisos y definir politica persistente.
- Siguiente paso bloqueado por autorizacion:
  - sembrar permisos de seguridad en BD con respaldo externo y refrescar sesion/roles.
  - texto sugerido: `AUTORIZO SEMBRAR PERMISOS POS EXCEPCION COMERCIAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`.

## Evidencia tecnica - Permisos POS excepcion comercial sembrados

- Fecha: 2026-06-28
- Autorizacion recibida:
  - `AUTORIZO SEMBRAR PERMISOS POS EXCEPCION COMERCIAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`.
- Script ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_permisos_apply_authorized.php --autorizar=VENTAS_POS_EXCEPCION_PERMISOS --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1`.
- Resultado aplicador:
  - `ok=true`;
  - permisos procesados:
    - `ventas.precio_manual`;
    - `ventas.descuento_partida`;
    - `ventas.descuento_general`;
    - `ventas.autorizar_excepcion_comercial`;
  - roles asignados:
    - `direccion`;
    - `administrador_erp`.
- Verificacion posterior:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_permisos_readonly.php`;
  - `faltantes_bd=[]`;
  - `requiere_autorizacion_siembra=false`;
  - permisos BD:
    - `291` = `ventas.precio_manual`;
    - `292` = `ventas.descuento_partida`;
    - `293` = `ventas.descuento_general`;
    - `294` = `ventas.autorizar_excepcion_comercial`.
- Decision:
  - la BD ya tiene permisos finos de excepcion comercial POS.
- Siguiente paso:
  - preparar persistencia de politica/autorizacion de excepciones comerciales antes de permitir precio manual o descuento en venta real.

## Evidencia tecnica - DDL excepcion comercial POS preparado

- Fecha: 2026-06-29
- Alcance:
  - preparar estructura para persistir precio manual/descuentos con politica, supervisor, motivo, snapshot y margen;
  - no ejecutar DDL;
  - no insertar datos;
  - no modificar ventas existentes.
- Cambios aplicados:
  - `app/modelos/VentasErpEsquema.php` agrega:
    - `planActualizarExcepcionesComerciales`;
    - `auditarExcepcionesComerciales`.
  - `storage/uat/uat_ventas_pos_excepcion_schema_readonly.php` genera auditoria/plan sin escribir.
  - `storage/uat/uat_ventas_pos_excepcion_schema_apply_authorized.php` prepara aplicador bloqueado por token.
- Estructura propuesta:
  - `erp_ventas_politicas_comerciales`;
  - `erp_ventas_excepciones_comerciales`;
  - columnas en `erp_ventas`:
    - `descuento_motivo`;
    - `id_excepcion_comercial_general`;
    - `autorizado_comercial_por`;
    - `fecha_autorizacion_comercial`.
  - columnas en `erp_ventas_detalle`:
    - `id_excepcion_comercial`;
    - `tipo_excepcion_comercial`;
    - `motivo_excepcion_comercial`;
    - `autorizado_comercial_por`;
    - `fecha_autorizacion_comercial`.
  - indices:
    - `idx_ventas_excepcion_general`;
    - `idx_ventas_detalle_excepcion`.
- Validaciones tecnicas:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErpEsquema.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_excepcion_schema_readonly.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_excepcion_schema_apply_authorized.php`.
- Auditoria read-only:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_schema_readonly.php`;
  - `ok=true`;
  - faltan 2 tablas;
  - faltan 9 columnas;
  - faltan 2 indices.
- Guardrail aplicador:
  - comando sin `--autorizar=VENTAS_POS_EXCEPCION_DDL`;
  - respaldo validado como existente y legible;
  - resultado `ok=false`;
  - mensaje `No se aplico DDL de excepciones comerciales`;
  - no escribio BD.
- Siguiente paso bloqueado por autorizacion:
  - `AUTORIZO APLICAR DDL POS EXCEPCION COMERCIAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`.

## Evidencia tecnica - DDL excepcion comercial POS aplicado

- Fecha: 2026-06-29
- Autorizacion recibida:
  - `AUTORIZO APLICAR DDL POS EXCEPCION COMERCIAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`.
- Script ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_schema_apply_authorized.php --autorizar=VENTAS_POS_EXCEPCION_DDL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`.
- Resultado:
  - `ok=true`;
  - modo `ventas_pos_excepcion_schema_aplicado`;
  - DDL ejecutado correctamente.
- Cambios aplicados:
  - tablas creadas:
    - `erp_ventas_politicas_comerciales`;
    - `erp_ventas_excepciones_comerciales`.
  - columnas agregadas en `erp_ventas`:
    - `descuento_motivo`;
    - `id_excepcion_comercial_general`;
    - `autorizado_comercial_por`;
    - `fecha_autorizacion_comercial`.
  - columnas agregadas en `erp_ventas_detalle`:
    - `id_excepcion_comercial`;
    - `tipo_excepcion_comercial`;
    - `motivo_excepcion_comercial`;
    - `autorizado_comercial_por`;
    - `fecha_autorizacion_comercial`.
  - indices agregados:
    - `idx_ventas_excepcion_general`;
    - `idx_ventas_detalle_excepcion`.
- Verificacion posterior independiente:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_schema_readonly.php`;
  - auditoria confirma:
    - 2 tablas existen;
    - 9 columnas existen;
    - 2 indices existen.
  - el plan posterior reporta `La tabla ya existe`, `La columna ya existe` y `El indice ya existe`.
- Siguiente paso:
  - preparar semilla UAT de politica comercial y flujo real autorizado para registrar excepcion comercial sin tocar inventario ni caja.

## Evidencia tecnica - Politicas UAT excepcion comercial preparadas

- Fecha: 2026-06-29
- Alcance:
  - proponer politicas UAT para precio manual, descuento por partida y descuento general;
  - no escribir BD;
  - no crear excepciones comerciales;
  - no tocar venta, caja ni inventario.
- Scripts agregados:
  - `storage/uat/uat_ventas_pos_excepcion_politicas_readonly.php`;
  - `storage/uat/uat_ventas_pos_excepcion_politicas_apply_authorized.php`.
- Validaciones tecnicas:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_excepcion_politicas_readonly.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_excepcion_politicas_apply_authorized.php`.
- Read-only ejecutado:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_politicas_readonly.php --id_usuario=1 --id_almacen=5`;
  - `ok=true`;
  - `bloqueos=[]`;
  - `politicas_existentes=[]`.
- Politicas propuestas:
  - `POS_PRECIO_MANUAL_UAT`, tipo `precio_manual`, requiere `ventas.autorizar_excepcion_comercial`;
  - `POS_DESCUENTO_PARTIDA_UAT`, tipo `descuento_partida`, maximo `10%` o `50`;
  - `POS_DESCUENTO_GENERAL_UAT`, tipo `descuento_general`, maximo `10%` o `100`.
- Guardrail aplicador:
  - comando sin `--autorizar=VENTAS_POS_EXCEPCION_POLITICAS`;
  - respaldo validado como existente y legible;
  - resultado `ok=false`;
  - no escribio BD.
- Siguiente paso bloqueado por autorizacion:
  - `AUTORIZO SEMBRAR POLITICAS POS EXCEPCION COMERCIAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 para UAT POS`.

## Evidencia tecnica - Politicas UAT excepcion comercial sembradas

- Fecha: 2026-06-29
- Autorizacion recibida:
  - `AUTORIZO SEMBRAR POLITICAS POS EXCEPCION COMERCIAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 para UAT POS`.
- Script ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_politicas_apply_authorized.php --autorizar=VENTAS_POS_EXCEPCION_POLITICAS --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_almacen=5`.
- Resultado:
  - `ok=true`;
  - modo `ventas_pos_excepcion_politicas_ejecutadas`;
  - politicas insertadas:
    - `POS_PRECIO_MANUAL_UAT`;
    - `POS_DESCUENTO_PARTIDA_UAT`;
    - `POS_DESCUENTO_GENERAL_UAT`.
- Verificacion posterior:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_politicas_readonly.php --id_usuario=1 --id_almacen=5`;
  - `ok=true`;
  - `bloqueos=[]`;
  - politicas existentes:
    - `id_politica_comercial=1`, `POS_PRECIO_MANUAL_UAT`, estatus `activa`;
    - `id_politica_comercial=2`, `POS_DESCUENTO_PARTIDA_UAT`, estatus `activa`;
    - `id_politica_comercial=3`, `POS_DESCUENTO_GENERAL_UAT`, estatus `activa`.
- Siguiente paso:
  - preparar registro real autorizado de excepcion comercial en `erp_ventas_excepciones_comerciales` sin crear venta ni mover caja/inventario.

## Evidencia tecnica - Registro real excepcion comercial preparado

- Fecha: 2026-06-29
- Alcance:
  - preparar registro real autorizado de excepcion comercial;
  - no ejecutar escritura real;
  - no crear venta;
  - no mover caja ni inventario.
- Cambios aplicados:
  - `VentasErp::registrarExcepcionComercialAutorizada`;
  - helpers de politica comercial, permiso de autorizador, folio `EXC-*` y partida principal;
  - `storage/uat/uat_ventas_pos_excepcion_registro_preflight_readonly.php`;
  - `storage/uat/uat_ventas_pos_excepcion_registro_apply_authorized.php`;
  - `storage/uat/uat_ventas_pos_excepcion_registro_readonly.php`.
- Validaciones tecnicas:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_excepcion_registro_preflight_readonly.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_excepcion_registro_apply_authorized.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_excepcion_registro_readonly.php`.
- Preflight read-only:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_registro_preflight_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --identificador=5550000000 --tipo=precio_manual --precio_manual=285 --motivo="UAT precio manual autorizado" --codigo_autorizacion=SUP-UAT-001`;
  - `ok=true`;
  - politica `POS_PRECIO_MANUAL_UAT`;
  - autorizador `id_usuario=1` tiene permiso `ventas.autorizar_excepcion_comercial`;
  - cliente `CL-UAT-POS-001`;
  - SKU `TP-40352-500GR`;
  - precio lista `295`;
  - precio manual estimado `285`;
  - descuento estimado `10`;
  - `bloqueos=[]`;
  - conteo excepciones antes `0`.
- Guardrail aplicador:
  - comando sin `--autorizar=VENTAS_POS_EXCEPCION_REAL`;
  - respaldo validado como existente y legible;
  - resultado `ok=false`;
  - no escribio BD.
- Verificacion read-only:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_registro_readonly.php --limite=5`;
  - `total_consultado=0`.
- Siguiente paso bloqueado por autorizacion:
  - `AUTORIZO REGISTRAR EXCEPCION COMERCIAL POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 id_sku=1760 tipo=precio_manual precio_manual=285 motivo="UAT precio manual autorizado" codigo_autorizacion=SUP-UAT-001 para UAT POS`.

## Evidencia tecnica - Registro real excepcion comercial ejecutado

- Fecha: 2026-06-29
- Autorizacion recibida:
  - `AUTORIZO REGISTRAR EXCEPCION COMERCIAL POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 id_sku=1760 tipo=precio_manual precio_manual=285 motivo="UAT precio manual autorizado" codigo_autorizacion=SUP-UAT-001 para UAT POS`.
- Script ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_registro_apply_authorized.php --autorizar=VENTAS_POS_EXCEPCION_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_almacen=5 --id_sku=1760 --identificador=5550000000 --tipo=precio_manual --precio_manual=285 --motivo="UAT precio manual autorizado" --codigo_autorizacion=SUP-UAT-001`.
- Verificacion read-only posterior:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_registro_readonly.php --limite=5`;
  - `ok=true`;
  - `total_consultado=1`;
  - `id_excepcion_comercial=1`;
  - folio `EXC-20260628-000001`;
  - tipo `precio_manual`;
  - estatus `autorizada`;
  - politica `POS_PRECIO_MANUAL_UAT`;
  - cliente `CL-UAT-POS-001`;
  - SKU `TP-40352-500GR`;
  - precio lista `295`;
  - precio solicitado/aplicado `285`;
  - descuento total `10`;
  - motivo `UAT precio manual autorizado`;
  - codigo autorizacion `SUP-UAT-001`.
- Alcance confirmado:
  - no creo venta;
  - no creo detalle de venta;
  - no movio caja;
  - no movio inventario;
  - la excepcion queda lista para la siguiente fase: consumo controlado al cobrar una venta real.

## Evidencia tecnica - Consumo dry-run de excepcion comercial POS

- Fecha: 2026-06-29
- Alcance:
  - simular consumo del folio `EXC-20260628-000001` dentro de una venta POS;
  - no crear venta;
  - no actualizar excepcion;
  - no mover caja;
  - no mover inventario.
- Cambios aplicados:
  - `VentasErp::excepcionComercialConsumoDryRun`;
  - helper `consultarExcepcionComercialConsumo`;
  - helper `normalizarItemsParaPrecioBackend`;
  - script `storage/uat/uat_ventas_pos_excepcion_consumo_dryrun_readonly.php`.
- Validaciones tecnicas:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_excepcion_consumo_dryrun_readonly.php`.
- Prueba ejecutada:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_consumo_dryrun_readonly.php --folio=EXC-20260628-000001 --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=285 --pago=285 --identificador=5550000000`.
- Resultado funcional:
  - folio encontrado `EXC-20260628-000001`;
  - estatus `autorizada`;
  - no tiene `id_venta` ni `id_venta_detalle`;
  - tipo `precio_manual`;
  - SKU `1760`;
  - precio backend/lista `295`;
  - precio final por excepcion `285`;
  - descuento calculado `10`;
  - total con excepcion `285`;
  - pago `285`;
  - saldo `0`;
  - cambio `0`.
- Guardrail validado:
  - el precio enviado por POS no decide el total;
  - el backend normaliza el carrito, recalcula precio lista y aplica el folio autorizado;
  - el contrato real debe bloquear la excepcion con `FOR UPDATE`, validar no consumida, insertar detalle con `id_excepcion_comercial` y actualizar la excepcion a `aplicada`.
- Bloqueos operativos vigentes:
  - no hay turno abierto para caja `2` del almacen `5`;
  - SKU `1760` no tiene existencia disponible en almacen `5`.
- Decision:
  - no conviene solicitar venta real con excepcion hasta abrir turno y cargar stock o elegir SKU con existencia.
- Siguiente paso bloqueado por autorizacion operativa:
  - abrir turno POS y cargar stock UAT antes de probar consumo real;
  - despues preparar/aplicar venta real con folio de excepcion comercial.

## Evidencia tecnica - Prerrequisitos UAT excepcion comercial listos

- Fecha: 2026-06-29
- Autorizaciones recibidas:
  - `AUTORIZO ABRIR TURNO POS UAT usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 y monto_inicial=500`;
  - `AUTORIZO CARGAR STOCK UAT POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1`.
- Apertura de turno ejecutada:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_apertura_apply_authorized.php --autorizar=VENTAS_POS_TURNO_APERTURA --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --monto_inicial=500`;
  - resultado `ok=true`;
  - `id_turno_caja=5`;
  - folio `TUR-20260628-002-001`;
  - `id_movimiento_caja=10`;
  - almacen `5`, caja `2`;
  - monto inicial `500`.
- Carga de stock ejecutada:
  - primer intento bloqueado por referencia automatica `POS-UAT-STOCK-*`, porque Inventario exige referencia `INV-INICIAL-*`;
  - se corrigio el script UAT para generar `INV-INICIAL-POS-UAT-*`;
  - comando final `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_stock_uat_apply_authorized.php --autorizar=VENTAS_POS_STOCK_UAT --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1`;
  - resultado `ok=true`;
  - referencia `INV-INICIAL-POS-UAT-20260628-A5-S1760`;
  - movimientos inventario `1`;
  - SKU `1760`, almacen `5`, cantidad `1`.
- Consumo dry-run posterior:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_consumo_dryrun_readonly.php --folio=EXC-20260628-000001 --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=285 --pago=285 --identificador=5550000000`;
  - resultado `tipo=success`;
  - `bloqueos=[]`;
  - turno detectado `id_turno_caja=5`;
  - existencia disponible `1`;
  - asignacion inventario `id_existencia_inventario=34`;
  - total con excepcion `285`;
  - pago `285`;
  - saldo `0`.
- Preparacion aplicador real:
  - `storage/uat/uat_ventas_pos_venta_apply_authorized.php` acepta `--folio_excepcion=EXC-*`;
  - valida consumo por `VentasErp::excepcionComercialConsumoDryRun` antes de transaccion real;
  - en transaccion real debe bloquear excepcion con `FOR UPDATE`;
  - inserta detalle con `id_excepcion_comercial`, `tipo_excepcion_comercial`, motivo y autorizador;
  - actualiza excepcion a `aplicada` con `id_venta`, `id_venta_detalle`, `aplicado_por` y `fecha_aplicacion`;
  - recalcula `subtotal=295`, `descuento_total=10`, `total=285`.
- Guardrail aplicador real:
  - comando sin `--autorizar=VENTAS_POS_VENTA_REAL`;
  - resultado `ok=false`, modo `guardrail`;
  - no escribio venta real.
- Verificaciones read-only:
  - excepcion `EXC-20260628-000001` sigue `autorizada`, sin `id_venta` ni `id_venta_detalle`;
  - preflight normal de venta POS con precio lista `295` esta `puede_vender_real=true`.
- Siguiente paso bloqueado por autorizacion fuerte:
  - ejecutar venta POS real consumiendo `EXC-20260628-000001`.
  - texto sugerido:
    - `AUTORIZO EJECUTAR VENTA POS UAT REAL CON EXCEPCION COMERCIAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_sku=1760 cantidad=1 precio=285 pago=285 folio_excepcion=EXC-20260628-000001`.

## Evidencia tecnica - Venta POS real con excepcion comercial aplicada

- Fecha: 2026-06-29
- Autorizacion recibida:
  - `AUTORIZO EJECUTAR VENTA POS UAT REAL CON EXCEPCION COMERCIAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_sku=1760 cantidad=1 precio=285 pago=285 folio_excepcion=EXC-20260628-000001`.
- Script ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_apply_authorized.php --autorizar=VENTAS_POS_VENTA_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=285 --pago=285 --folio_excepcion=EXC-20260628-000001 --observaciones="UAT venta POS con excepcion comercial"`.
- Resultado:
  - `ok=true`;
  - folio venta `POS-20260628-000001`;
  - `id_venta=5`;
  - estatus `pagada`;
  - subtotal `295`;
  - descuento total `10`;
  - total `285`;
  - pago efectivo `285`;
  - saldo `0`.
- Excepcion comercial:
  - `EXC-20260628-000001`;
  - `id_excepcion_comercial=1`;
  - paso de `autorizada` a `aplicada`;
  - ligada a `id_venta=5`;
  - ligada a `id_venta_detalle=5`;
  - precio base/lista `295`;
  - precio aplicado `285`;
  - descuento `10`.
- Inventario/kardex:
  - `id_existencia_inventario=34`;
  - `id_movimiento_inventario=59`;
  - salida `venta_pos`;
  - referencia `POS-20260628-000001`;
  - existencia anterior `1`;
  - existencia nueva `0`;
  - disponible actual `0`.
- Caja:
  - pago `id_venta_pago=5`;
  - movimiento caja `id_movimiento_caja=11`;
  - tipo `ingreso`;
  - categoria/motivo `venta_pos`;
  - monto `285`.
- Garantia:
  - snapshot creado `id_venta_detalle_garantia=2`;
  - resumen ticket `Sin garantia`;
  - estatus `vigente`.
- Post-venta read-only:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_venta_readonly.php --folio=POS-20260628-000001`;
  - `hallazgos=[]`;
  - cuadres: detalle `285`, pagos `285`, partidas `1`, pagos `1`, garantias `1`, trazabilidades `1`.
- Ticket formal read-only:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260628-000001`;
  - `hallazgos=[]`;
  - muestra subtotal `$295.00`, descuento `$10.00`, total `$285.00`, pago `$285.00`, saldo `$0.00`;
  - muestra garantia `Sin garantia`;
  - inventario trazado `1 mov.`.
- Turno/caja read-only:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_post_cierre_readonly.php --folio=TUR-20260628-002-001`;
  - turno sigue `abierto`;
  - monto inicial `500`;
  - ventas `285`;
  - movimientos caja `785`;
  - monto esperado `785`;
  - hallazgos esperados por no estar cerrado: turno sin cierre y monto contado pendiente.
- Hallazgo POS-EXC-CLI-001:
  - la excepcion comercial esta ligada al cliente `CL-UAT-POS-001`;
  - la venta `POS-20260628-000001` quedo con `id_cliente=NULL` y `cliente_nombre_publico=Cliente mostrador UAT`;
  - no se actualizo la venta ya escrita sin autorizacion;
  - se corrigio el aplicador UAT para que futuras ventas con excepcion hereden `id_cliente`, nombre e identificador desde la excepcion.
- Siguiente paso bloqueado por autorizacion:
  - cerrar turno POS UAT con monto contado `785`;
  - texto sugerido:
    - `AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 monto_contado=785 observaciones="Cierre UAT POS con excepcion comercial aplicada"`.

## Evidencia tecnica - Cierre turno POS con excepcion comercial

- Fecha: 2026-06-29
- Autorizacion recibida:
  - `AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 monto_contado=785 observaciones="Cierre UAT POS con excepcion comercial aplicada"`.
- Script ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_cierre_apply_authorized.php --autorizar=VENTAS_POS_TURNO_CIERRE --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --monto_contado=785 --observaciones="Cierre UAT POS con excepcion comercial aplicada"`.
- Resultado:
  - `ok=true`;
  - turno `TUR-20260628-002-001`;
  - `id_turno_caja=5`;
  - almacen `5`;
  - caja `2`;
  - monto esperado `785`;
  - monto contado `785`;
  - diferencia `0`;
  - estatus `cerrado`.
- Resumen de caja:
  - monto inicial `500`;
  - venta POS `285`;
  - movimientos caja `785`;
  - pagos efectivo `285`;
  - operaciones venta `1`.
- Verificacion post-cierre:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_post_cierre_readonly.php --folio=TUR-20260628-002-001`;
  - `hallazgos=[]`;
  - no hay turno abierto para la caja;
  - fecha cierre `2026-06-29 00:04:50`.
- Verificaciones cruzadas:
  - venta `POS-20260628-000001` sigue `pagada`, total/pago `285`, saldo `0`;
  - excepcion `EXC-20260628-000001` sigue `aplicada`, ligada a venta/detalle `5`;
  - kardex/existencia y ticket formal ya validados en evidencia previa.
- Decision:
  - flujo UAT completo probado: turno, stock, excepcion comercial, venta, ticket, kardex, caja y cierre con diferencia `0`.
- Siguiente paso:
  - pasar a hardening/UX productivo de excepciones comerciales en POS o avanzar a clientes/apartados/devoluciones segun prioridad.

## Evidencia tecnica - Hardening UI excepcion comercial POS

- Fecha: 2026-06-29
- Alcance:
  - llevar el contrato probado en UAT a la UI del POS en modo read-only;
  - no registrar nuevas excepciones;
  - no crear ventas;
  - no mover caja ni inventario.
- Cambios aplicados:
  - endpoint `Ventas::pos_excepcion_consumo_dryrun_erp`;
  - vista POS agrega campo `Folio autorizado` y boton `Validar folio`;
  - JS POS agrega `excepcionConsumoDryRun`;
  - JS POS agrega `renderExcepcionConsumo`;
  - version asset POS actualizada a `20260629-excepcion1`.
- Comportamiento:
  - cajero captura un folio `EXC-*`;
  - backend valida estatus, no consumo previo, carrito, caja, turno, stock, pagos y totales;
  - UI muestra subtotal original, descuento, total con excepcion, saldo y partidas afectadas;
  - si no hay bloqueos, actualiza totales visuales del POS con el total autorizado;
  - no aplica venta real ni descuenta inventario.
- Validaciones tecnicas:
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`.
- Prueba read-only con folio ya consumido:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_consumo_dryrun_readonly.php --folio=EXC-20260628-000001 --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=285 --pago=285 --identificador=5550000000`;
  - resultado esperado `tipo=warning`;
  - bloqueos relevantes:
    - `La excepcion comercial no esta autorizada`;
    - `La excepcion comercial ya fue consumida por una venta`.
- Decision:
  - el POS ya puede validar folios autorizados en UI sin escribir BD;
  - falta activar flujo real desde UI con un nuevo folio autorizado limpio y permisos de supervisor.
- Siguiente paso bloqueado por autorizacion robusta:
  - preparar una nueva UAT productiva de punta a punta desde POS/UI para excepcion comercial:
    - abrir turno;
    - cargar stock;
    - crear nueva excepcion autorizada;
    - validar folio en UI;
    - ejecutar venta real controlada;
    - cerrar turno;
    - comprobar ticket/kardex/caja.

## Evidencia tecnica - Preflight UAT robusta POS excepcion comercial

- Fecha: 2026-06-29
- Alcance:
  - preparar una UAT robusta nueva de excepcion comercial POS;
  - no escribir BD;
  - no abrir turno;
  - no cargar stock;
  - no registrar excepcion;
  - no vender ni cerrar caja.
- Cambios aplicados:
  - `storage/uat/uat_ventas_pos_excepcion_uat_robusta_preflight_readonly.php`;
  - `storage/uat/uat_ventas_pos_stock_uat_preflight_readonly.php` ahora propone referencia `INV-INICIAL-POS-UAT-*`.
- Validaciones tecnicas:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_excepcion_uat_robusta_preflight_readonly.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_stock_uat_preflight_readonly.php`.
- Preflight ejecutado:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_uat_robusta_preflight_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio_manual=285 --monto_inicial=500 --telefono=5550000000 --codigo_autorizacion=SUP-UAT-002`;
  - `ok=true`;
  - `bloqueos=[]`.
- Parametros validados:
  - usuario `1`;
  - almacen `5`;
  - caja `2`;
  - SKU `1760`, `TP-40352-500GR`;
  - precio lista `295`;
  - precio manual `285`;
  - descuento estimado `10`;
  - total venta esperado `285`;
  - monto inicial `500`;
  - monto contado cierre esperado `785`;
  - referencia stock `INV-INICIAL-POS-UAT-20260629-A5-S1760-EXC2`.
- Prerrequisitos confirmados:
  - respaldo externo existe y es legible;
  - no hay turno abierto actual;
  - usuario tiene asignacion POS activa;
  - politica `POS_PRECIO_MANUAL_UAT` activa;
  - usuario tiene permiso `ventas.autorizar_excepcion_comercial`;
  - dry-run de excepcion comercial sin bloqueos.
- Siguiente paso bloqueado por autorizacion robusta:
  - `AUTORIZO EJECUTAR UAT ROBUSTA POS EXCEPCION COMERCIAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 precio_manual=285 pago=285 monto_inicial=500 monto_contado=785 telefono=5550000000 codigo_autorizacion=SUP-UAT-002`.

- Total casos ejecutados:
- Casos aprobados:
- Casos bloqueados:
- Casos con observacion:
- Decision:
- Siguiente paso:

## Evidencia tecnica - UAT robusta POS excepcion comercial SUP-UAT-002

- Fecha: 2026-06-29
- Alcance:
  - ejecutar UAT real de punta a punta con turno, stock, excepcion comercial autorizada, venta, ticket, kardex, caja y cierre;
  - usar respaldo externo `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`;
  - usuario `1`, almacen `5`, SKU `1760`, cantidad `1`, precio manual `285`, pago `285`, monto inicial `500`, monto contado `785`;
  - codigo autorizacion `SUP-UAT-002`.
- Preflight robusto:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_uat_robusta_preflight_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio_manual=285 --monto_inicial=500 --telefono=5550000000 --codigo_autorizacion=SUP-UAT-002 --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`;
  - `ok=true`;
  - `bloqueos=[]`;
  - referencia stock sugerida `INV-INICIAL-POS-UAT-20260629-A5-S1760-EXC2`;
  - venta esperada `285`, cierre esperado `785`.
- Apertura de turno:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_apertura_apply_authorized.php --autorizar=VENTAS_POS_TURNO_APERTURA --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --monto_inicial=500`;
  - turno `TUR-20260629-002-001`;
  - `id_turno_caja=6`;
  - `id_movimiento_caja=12`;
  - almacen `5`, caja `2`, monto inicial `500`.
- Stock UAT:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_stock_uat_apply_authorized.php --autorizar=VENTAS_POS_STOCK_UAT --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --referencia=INV-INICIAL-POS-UAT-20260629-A5-S1760-EXC2`;
  - `ok=true`;
  - movimiento inventario inicial registrado;
  - referencia `INV-INICIAL-POS-UAT-20260629-A5-S1760-EXC2`.
- Excepcion comercial:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_registro_apply_authorized.php --autorizar=VENTAS_POS_EXCEPCION_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_almacen=5 --id_sku=1760 --identificador=5550000000 --tipo=precio_manual --precio_manual=285 --motivo="UAT UI precio manual autorizado" --codigo_autorizacion=SUP-UAT-002`;
  - folio `EXC-20260629-000001`;
  - `id_excepcion_comercial=2`;
  - estatus inicial `autorizada`;
  - subtotal/lista `295`, descuento `10`, total autorizado `285`.
- Dry-run de consumo:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_consumo_dryrun_readonly.php --folio=EXC-20260629-000001 --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=285 --pago=285 --identificador=5550000000`;
  - `tipo=success`;
  - `bloqueos=[]`;
  - turno `6`, existencia `34`, stock disponible `1`;
  - total con excepcion `285`, pago `285`, saldo `0`.
- Venta real:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_apply_authorized.php --autorizar=VENTAS_POS_VENTA_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=285 --pago=285 --folio_excepcion=EXC-20260629-000001 --observaciones="UAT robusta POS excepcion comercial SUP-UAT-002"`;
  - venta `POS-20260629-000001`;
  - `id_venta=6`;
  - estatus `pagada`;
  - subtotal `295`, descuento `10`, total/pago `285`, saldo `0`;
  - excepcion `EXC-20260629-000001` quedo `aplicada`, ligada a `id_venta=6`, `id_venta_detalle=6`;
  - kardex `id_movimiento_inventario=61`, existencia `34` de `1` a `0`;
  - pago `id_venta_pago=6`, movimiento caja `13`, efectivo `285`;
  - garantia snapshot `id_venta_detalle_garantia=3`, resumen `Sin garantia`.
- Verificacion post-venta:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_venta_readonly.php --folio=POS-20260629-000001`;
  - `ok=true`;
  - `hallazgos=[]`;
  - detalle SKU `TP-40352-500GR`, precio unitario `285`, precio base `295`, descuento `10`;
  - inventario trazado con referencia de venta `POS-20260629-000001`.
- Ticket formal:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260629-000001`;
  - `ok=true`;
  - `hallazgos=[]`;
  - subtotal `$295.00`, descuento `$10.00`, total `$285.00`, pago `$285.00`, saldo `$0.00`;
  - garantia `Sin garantia`;
  - inventario trazado `1 mov.`.
- Cierre de turno:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_cierre_apply_authorized.php --autorizar=VENTAS_POS_TURNO_CIERRE --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --monto_contado=785 --observaciones="Cierre UAT robusta POS excepcion comercial SUP-UAT-002"`;
  - turno `TUR-20260629-002-001`, `id_turno_caja=6`;
  - monto esperado `785`, contado `785`, diferencia `0`;
  - resumen venta total `285`, pagado `285`, saldo `0`, pagos efectivo `285`;
  - movimientos: inicial `500`, venta POS `285`.
- Verificacion post-cierre:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_post_cierre_readonly.php --folio=TUR-20260629-002-001`;
  - estatus `cerrado`;
  - fecha cierre `2026-06-29 00:20:39`;
  - `hallazgos=[]`;
  - no queda turno abierto para la caja.
- Bloqueo de reutilizacion:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_consumo_dryrun_readonly.php --folio=EXC-20260629-000001 --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=285 --pago=285 --identificador=5550000000`;
  - resultado esperado `tipo=warning`;
  - bloqueos esperados:
    - `La excepcion comercial no esta autorizada`;
    - `La excepcion comercial ya fue consumida por una venta`;
    - no hay turno abierto;
    - stock agotado posterior a la venta.
- Hallazgo POS-CRM-EXC-001:
  - la excepcion `EXC-20260629-000001` quedo con `id_cliente=NULL` aunque se envio identificador `5550000000`;
  - la venta se ejecuto correctamente como venta de mostrador, pero la liga CRM/POS por identificador no debe considerarse cerrada;
  - se requiere resolver el contrato canonico CRM antes de consolidar cliente, listas/precios por cliente, recompensas y postventa personalizada desde POS.
- Decision:
  - UAT robusta POS excepcion comercial aprobada en caja, inventario, ticket, kardex, cierre y bloqueo de reutilizacion;
  - queda pendiente integrar cliente CRM canonico en el flujo real de excepcion/venta.
- Siguiente paso:
  - priorizar hardening de cliente CRM/POS y flujo real UI de solicitud/aplicacion de excepcion comercial con supervisor.

## Evidencia tecnica - Contrato CRM/POS read-only

- Fecha: 2026-06-29
- Alcance:
  - auditar por que la UAT robusta con telefono `5550000000` no ligo cliente CRM;
  - preparar contrato de columnas CRM para ventas y excepciones comerciales;
  - no escribir BD;
  - no ejecutar DDL;
  - no crear clientes, ventas, excepciones, caja ni inventario.
- Cambios aplicados:
  - `storage/uat/uat_ventas_pos_crm_contrato_readonly.php`;
  - `VentasErpEsquema::planActualizarContratoCrmPos`;
  - `VentasErpEsquema::auditarContratoCrmPos`;
  - `storage/uat/uat_ventas_pos_crm_contrato_schema_readonly.php`;
  - `storage/uat/uat_ventas_pos_crm_contrato_schema_apply_authorized.php`;
  - `VentasErp::registrarExcepcionComercialAutorizada` queda preparado para guardar `id_cliente_crm` y snapshot cuando existan columnas autorizadas.
- Diagnostico telefono `5550000000`:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_crm_contrato_readonly.php --identificador=5550000000 --id_almacen=5 --id_sku=1760 --folio_excepcion=EXC-20260629-000001`;
  - CRM canonico: `0` coincidencias;
  - POS/UAT legacy: `1` coincidencia, `CL-UAT-POS-001`, `Cliente UAT POS`;
  - excepcion `EXC-20260629-000001`: `id_cliente=NULL`, snapshot sin `id_cliente_crm`;
  - Ventas resuelve como `publico_general` y `requiere_alta_rapida=true`.
- Diagnostico telefono `3312345678`:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_crm_contrato_readonly.php --identificador=3312345678 --id_almacen=5 --id_sku=1760`;
  - CRM canonico: `1` coincidencia;
  - cliente `CRM-POSUAT-20260628-0001`, `id_cliente_crm=1`, `Cliente Express UAT`;
  - POS/UAT legacy: `0` coincidencias;
  - Ventas resuelve `origen_cliente=crm`, `requiere_alta_rapida=false`.
- Dry-run excepcion con cliente CRM:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_registro_preflight_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --identificador=3312345678 --tipo=precio_manual --precio_manual=285 --motivo="UAT CRM contrato precio manual" --codigo_autorizacion=SUP-CRM-001`;
  - `ok=true`;
  - `bloqueos=[]`;
  - cliente resuelto `id_cliente_crm=1`, codigo `CRM-POSUAT-20260628-0001`;
  - total estimado `285`, descuento `10`.
- Auditoria DDL contrato CRM/POS:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_crm_contrato_schema_readonly.php`;
  - tablas base existen: `erp_ventas`, `erp_ventas_excepciones_comerciales`, `crm_clientes_maestro`, `crm_clientes_identificadores`;
  - columnas faltantes: 9;
  - indices faltantes: 2;
  - no se ejecuto DDL.
- Columnas propuestas:
  - `erp_ventas.id_cliente_crm`;
  - `erp_ventas.cliente_codigo_snapshot`;
  - `erp_ventas.cliente_origen_snapshot`;
  - `erp_ventas.cliente_snapshot`;
  - `erp_ventas_excepciones_comerciales.id_cliente_crm`;
  - `erp_ventas_excepciones_comerciales.cliente_codigo_snapshot`;
  - `erp_ventas_excepciones_comerciales.cliente_nombre_snapshot`;
  - `erp_ventas_excepciones_comerciales.cliente_identificador_snapshot`;
  - `erp_ventas_excepciones_comerciales.cliente_origen_snapshot`.
- Indices propuestos:
  - `idx_ventas_cliente_crm_fecha`;
  - `idx_ventas_excepcion_cliente_crm`.
- Guardrail aplicador:
  - comando sin `--autorizar=VENTAS_POS_CRM_CONTRATO_DDL`;
  - respaldo validado como existente y legible;
  - resultado `ok=false`;
  - no escribio BD.
- Decision:
  - el fallo de `5550000000` no fue un problema de busqueda CRM; ese telefono vive en la tabla POS/UAT antigua, no en CRM canonico;
  - para POS robusto, ventas y excepciones deben guardar `id_cliente_crm` y snapshot, no depender de `erp_clientes.id_cliente`.
- Siguiente paso bloqueado por autorizacion:
  - `AUTORIZO APLICAR DDL CONTRATO CRM POS VENTAS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`.

## Evidencia tecnica - DDL contrato CRM/POS aplicado

- Fecha: 2026-06-29
- Autorizacion recibida:
  - `AUTORIZO APLICAR DDL CONTRATO CRM POS VENTAS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`.
- Script ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_crm_contrato_schema_apply_authorized.php --autorizar=VENTAS_POS_CRM_CONTRATO_DDL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`.
- Resultado:
  - `ok=true`;
  - modo `ventas_pos_crm_contrato_schema_aplicado`;
  - respaldo validado y usado como referencia;
  - DDL ejecutado correctamente.
- Columnas agregadas en `erp_ventas`:
  - `id_cliente_crm`;
  - `cliente_codigo_snapshot`;
  - `cliente_origen_snapshot`;
  - `cliente_snapshot`.
- Columnas agregadas en `erp_ventas_excepciones_comerciales`:
  - `id_cliente_crm`;
  - `cliente_codigo_snapshot`;
  - `cliente_nombre_snapshot`;
  - `cliente_identificador_snapshot`;
  - `cliente_origen_snapshot`.
- Indices agregados:
  - `idx_ventas_cliente_crm_fecha`;
  - `idx_ventas_excepcion_cliente_crm`.
- Verificacion posterior independiente:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_crm_contrato_schema_readonly.php`;
  - todas las tablas base existen;
  - las 9 columnas existen;
  - los 2 indices existen;
  - el plan posterior reporta `La columna ya existe` y `El indice ya existe`.
- Dry-run post-DDL:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_registro_preflight_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --identificador=3312345678 --tipo=precio_manual --precio_manual=285 --motivo="UAT CRM contrato post DDL" --codigo_autorizacion=SUP-CRM-002`;
  - `ok=true`;
  - `bloqueos=[]`;
  - cliente CRM resuelto `id_cliente_crm=1`, codigo `CRM-POSUAT-20260628-0001`;
  - total estimado `285`, descuento `10`.
- Decision:
  - el contrato CRM/POS ya puede guardar cliente CRM y snapshot en ventas/excepciones nuevas;
  - no se hizo backfill de ventas/excepciones historicas;
  - `EXC-20260629-000001` y `POS-20260629-000001` permanecen como evidencia historica de venta mostrador.
- Siguiente paso bloqueado por autorizacion:
  - crear una excepcion real nueva con cliente CRM canonico `3312345678`, abrir turno/stock si aplica, ejecutar venta real, validar ticket/kardex/caja y cerrar turno.

## Evidencia tecnica - Preflight UAT robusta post-DDL CRM/POS

- Fecha: 2026-06-29
- Alcance:
  - preparar UAT real con cliente CRM canonico despues del DDL de contrato CRM/POS;
  - no escribir BD;
  - no abrir turno;
  - no cargar stock;
  - no crear excepcion real;
  - no vender ni cerrar caja.
- Ajuste aplicado:
  - `storage/uat/uat_ventas_pos_excepcion_uat_robusta_preflight_readonly.php` permite `--referencia_sufijo` para no reutilizar referencias de stock UAT.
- Validacion tecnica:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_excepcion_uat_robusta_preflight_readonly.php`.
- Comando ejecutado:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_uat_robusta_preflight_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio_manual=285 --monto_inicial=500 --telefono=3312345678 --codigo_autorizacion=SUP-CRM-003 --motivo="UAT CRM POS precio manual post DDL" --referencia_sufijo=CRM1 --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`.
- Resultado:
  - `ok=true`;
  - `bloqueos=[]`;
  - respaldo externo existe y es legible;
  - no hay turno abierto;
  - usuario `1` tiene asignacion POS activa en almacen `5`, caja `2`;
  - politica `POS_PRECIO_MANUAL_UAT` activa;
  - usuario `1` tiene permiso `ventas.autorizar_excepcion_comercial`.
- Cliente CRM:
  - identificador `3312345678`;
  - `id_cliente_crm=1`;
  - codigo `CRM-POSUAT-20260628-0001`;
  - nombre `Cliente Express UAT`;
  - origen `crm`;
  - no requiere alta rapida.
- Parametros esperados:
  - SKU `1760`, `TP-40352-500GR`;
  - cantidad `1`;
  - precio lista `295`;
  - precio manual `285`;
  - descuento estimado `10`;
  - total venta esperado `285`;
  - monto inicial `500`;
  - monto contado cierre esperado `785`;
  - referencia stock `INV-INICIAL-POS-UAT-20260629-A5-S1760-CRM1`.
- Verificacion contrato CRM/POS:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_crm_contrato_schema_readonly.php`;
  - todas las columnas e indices del contrato CRM/POS existen;
  - el plan posterior solo reporta columnas/indices ya existentes.
- Siguiente paso bloqueado por autorizacion robusta:
  - `AUTORIZO EJECUTAR UAT ROBUSTA POS EXCEPCION COMERCIAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 precio_manual=285 pago=285 monto_inicial=500 monto_contado=785 telefono=3312345678 codigo_autorizacion=SUP-CRM-003`.

## Evidencia tecnica - UAT robusta POS CRM excepcion comercial SUP-CRM-003

- Fecha: 2026-06-29
- Autorizacion recibida:
  - `AUTORIZO EJECUTAR UAT ROBUSTA POS EXCEPCION COMERCIAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 precio_manual=285 pago=285 monto_inicial=500 monto_contado=785 telefono=3312345678 codigo_autorizacion=SUP-CRM-003`.
- Ajustes tecnicos previos:
  - `storage/uat/uat_ventas_pos_venta_apply_authorized.php` ahora guarda `id_cliente_crm`, `cliente_codigo_snapshot`, `cliente_origen_snapshot` y `cliente_snapshot` en `erp_ventas` cuando existe el DDL de contrato CRM/POS;
  - `storage/uat/uat_ventas_pos_excepcion_registro_readonly.php` ahora muestra campos CRM de la excepcion;
  - validaciones: `php -l` sin errores.
- Apertura de turno:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_apertura_apply_authorized.php --autorizar=VENTAS_POS_TURNO_APERTURA --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --monto_inicial=500`;
  - turno `TUR-20260629-002-002`;
  - `id_turno_caja=7`;
  - movimiento caja inicial `id_movimiento_caja=14`;
  - monto inicial `500`.
- Stock UAT:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_stock_uat_apply_authorized.php --autorizar=VENTAS_POS_STOCK_UAT --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --referencia=INV-INICIAL-POS-UAT-20260629-A5-S1760-CRM1`;
  - `ok=true`;
  - referencia `INV-INICIAL-POS-UAT-20260629-A5-S1760-CRM1`;
  - movimiento inventario inicial registrado.
- Excepcion comercial:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_registro_apply_authorized.php --autorizar=VENTAS_POS_EXCEPCION_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_almacen=5 --id_sku=1760 --identificador=3312345678 --tipo=precio_manual --precio_manual=285 --motivo="UAT CRM POS precio manual post DDL" --codigo_autorizacion=SUP-CRM-003`;
  - folio `EXC-20260629-000002`;
  - `id_excepcion_comercial=3`;
  - estatus inicial `autorizada`;
  - `id_cliente_crm=1`;
  - cliente snapshot `CRM-POSUAT-20260628-0001`, `Cliente Express UAT`, identificador `3312345678`, origen `crm`;
  - subtotal/lista `295`, descuento `10`, total autorizado `285`.
- Dry-run de consumo:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_consumo_dryrun_readonly.php --folio=EXC-20260629-000002 --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=285 --pago=285 --identificador=3312345678`;
  - `tipo=success`;
  - `bloqueos=[]`;
  - turno `7`, existencia `34`, stock disponible `1`;
  - total con excepcion `285`, pago `285`, saldo `0`.
- Venta real:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_apply_authorized.php --autorizar=VENTAS_POS_VENTA_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=285 --pago=285 --folio_excepcion=EXC-20260629-000002 --observaciones="UAT robusta POS CRM excepcion comercial SUP-CRM-003"`;
  - venta `POS-20260629-000002`;
  - `id_venta=7`;
  - estatus `pagada`;
  - cliente `id_cliente_crm=1`, `Cliente Express UAT`, identificador `3312345678`, origen `crm`;
  - subtotal `295`, descuento `10`, total/pago `285`, saldo `0`;
  - excepcion `EXC-20260629-000002` quedo `aplicada`, ligada a `id_venta=7`, `id_venta_detalle=7`;
  - kardex `id_movimiento_inventario=63`, existencia `34` de `1` a `0`;
  - pago `id_venta_pago=7`, movimiento caja `15`, efectivo `285`;
  - garantia snapshot `id_venta_detalle_garantia=4`, resumen `Sin garantia`.
- Verificacion post-venta:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_venta_readonly.php --folio=POS-20260629-000002`;
  - `ok=true`;
  - `hallazgos=[]`;
  - detalle SKU `TP-40352-500GR`, precio unitario `285`, precio base `295`, descuento `10`;
  - inventario trazado con referencia `POS-20260629-000002`.
- Ticket formal:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260629-000002`;
  - `ok=true`;
  - `hallazgos=[]`;
  - `id_cliente_crm=1`;
  - cliente `Cliente Express UAT`;
  - codigo snapshot `CRM-POSUAT-20260628-0001`;
  - identificador `3312345678`;
  - subtotal `$295.00`, descuento `$10.00`, total `$285.00`, pago `$285.00`, saldo `$0.00`;
  - garantia `Sin garantia`;
  - inventario trazado `1 mov.`.
- Cierre de turno:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_cierre_apply_authorized.php --autorizar=VENTAS_POS_TURNO_CIERRE --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --monto_contado=785 --observaciones="Cierre UAT robusta POS CRM excepcion comercial SUP-CRM-003"`;
  - turno `TUR-20260629-002-002`, `id_turno_caja=7`;
  - monto esperado `785`, contado `785`, diferencia `0`;
  - resumen venta total `285`, pagado `285`, saldo `0`;
  - movimientos: inicial `500`, venta POS `285`.
- Verificacion post-cierre:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_post_cierre_readonly.php --folio=TUR-20260629-002-002`;
  - estatus `cerrado`;
  - fecha cierre `2026-06-29 08:19:07`;
  - `hallazgos=[]`;
  - no queda turno abierto para la caja.
- Bloqueo de reutilizacion:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_consumo_dryrun_readonly.php --folio=EXC-20260629-000002 --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=285 --pago=285 --identificador=3312345678`;
  - resultado esperado `tipo=warning`;
  - bloqueos esperados:
    - `La excepcion comercial no esta autorizada`;
    - `La excepcion comercial ya fue consumida por una venta`;
    - no hay turno abierto;
    - stock agotado posterior a la venta.
- Decision:
  - UAT robusta POS CRM excepcion comercial aprobada;
  - el contrato CRM/POS quedo validado en excepcion, venta, ticket, caja, kardex y cierre;
  - no se hizo backfill a ventas historicas.
- Siguiente paso:
  - preparar flujo UI productivo para solicitar/aplicar excepciones comerciales desde POS con supervisor, o continuar con alta express CRM desde POS y apartados/abonos.

## Evidencia UI POS excepcion activa por cuenta - 2026-06-29

- Tipo de tarea: UI/UX y contrato frontend, sin escrituras de BD.
- Archivos:
  - `app/vistas/paginas/apps/erp/ventas/pos.php`;
  - `public/assets/js/custom/apps/erp/ventas/pos.js`.
- Cambios iniciales:
  - se agrego panel `pos_excepcion_activa` debajo de validaciones/pagos;
  - el folio de excepcion comercial validado queda visible con cliente, total autorizado y descuento;
  - cada cuenta local conserva su propia excepcion comercial activa en `localStorage`;
  - los totales del POS usan `total_con_excepcion` mientras la excepcion siga activa;
  - los botones de pago rapido calculan el sugerido contra el total vigente;
  - cualquier cambio de carrito, cantidad, modo de salida o pagos limpia la excepcion activa para obligar revalidacion;
  - version de asset POS actualizada a `20260629-excepcion2`.
- Cambios de flujo productivo:
  - boton lateral `Cliente/lista` abre el tab de cliente;
  - boton lateral `Autorizacion` abre directo el tab comercial;
  - modal reorganizado en tabs `Cliente`, `Autorizacion` y `Folio`;
  - la solicitud de excepcion permite seleccionar la partida objetivo del carrito;
  - `descuento_general` desactiva selector de partida;
  - `precio_manual` desactiva campos de descuento;
  - `descuento_partida` y `descuento_general` desactivan precio manual;
  - se elimina la regla anterior que tomaba siempre el primer SKU del carrito;
  - version de asset POS actualizada a `20260629-excepcion3`.
- Guardrails:
  - el JS no autoriza ni registra descuentos;
  - el folio solo queda activo despues del dry-run backend sin bloqueos;
  - si cambia la operacion, el cajero debe volver a validar el folio.
- Verificacion:
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js` sin errores;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php` sin errores.
- Pendiente:
  - validar visualmente con navegador autenticado/Playwright cuando haya sesion POS disponible;
  - conectar registro real de solicitud/autorizacion supervisor dentro del POS, con autorizacion de escritura y respaldo.

## Evidencia tecnica - Registro real de excepcion comercial POS UI expuesto

- Fecha: 2026-06-29
- Autorizacion recibida:
  - `AUTORIZO EXPONER REGISTRO REAL DE EXCEPCION COMERCIAL POS UI usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`.
- Alcance aplicado:
  - se expone endpoint POST `/ventas/pos_excepcion_comercial_registrar_erp`;
  - se conecta boton `Registrar folio autorizado` en POS;
  - no se ejecuto registro real desde CLI en esta tarea;
  - no se creo venta, pago, caja, kardex ni movimiento de inventario.
- Archivos:
  - `app/controladores/Ventas.php`;
  - `app/core/Core.php`;
  - `app/vistas/paginas/apps/erp/ventas/pos.php`;
  - `public/assets/js/custom/apps/erp/ventas/pos.js`.
- Guardrails:
  - sesion obligatoria por controlador protegido;
  - CSRF obligatorio por POST via `Core`;
  - permiso `ventas.operar`;
  - permiso `ventas.autorizar_excepcion_comercial`;
  - auditoria explicita `ventas.excepcion_comercial_registrar`;
  - backend recalcula con `VentasErp::registrarExcepcionComercialAutorizada`;
  - el endpoint solo registra `erp_ventas_excepciones_comerciales`;
  - no crea venta;
  - no mueve caja;
  - no mueve inventario.
- UX:
  - boton `Validar` conserva dry-run;
  - boton `Registrar folio autorizado` crea el folio real si no hay bloqueos;
  - al registrar folio, POS llena `pos_excepcion_folio` y cambia al tab `Folio`;
  - despues el cajero debe pulsar `Aplicar folio` para validar el consumo contra carrito/pagos.
- Version asset:
  - `20260629-excepcion4`.
- Verificacion:
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`;
  - `C:\xampp\php\php.exe -l app\core\Core.php`;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`;
  - todas sin errores.
- Pendiente:
  - UAT visual en navegador autenticado;
  - registrar folio real desde UI y validar consumo contra carrito/pagos;
  - si se desea, ejecutar venta real separada con autorizacion especifica.

## Evidencia read-only - Readiness registro excepcion POS UI

- Fecha: 2026-06-29
- Script:
  - `storage/uat/uat_ventas_pos_excepcion_ui_readiness_readonly.php`.
- Comando:
  - `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_ui_readiness_readonly.php --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --identificador=3312345678 --tipo=precio_manual --precio_manual=285 --motivo="UAT POS UI precio manual" --codigo_autorizacion=SUP-UI-001`.
- Resultado:
  - `ok=true`;
  - `read_only=true`;
  - endpoint `/ventas/pos_excepcion_comercial_registrar_erp` existe;
  - respaldo externo existe, es legible y pesa `27216189` bytes;
  - tablas `erp_ventas_politicas_comerciales` y `erp_ventas_excepciones_comerciales` existen;
  - columnas criticas de excepcion existen;
  - usuario `1` tiene `ventas.operar=true`;
  - usuario `1` tiene `ventas.autorizar_excepcion_comercial=true`;
  - politica activa `POS_PRECIO_MANUAL_UAT`;
  - cliente CRM resuelto `id_cliente_crm=1`, `CRM-POSUAT-20260628-0001`, `Cliente Express UAT`;
  - SKU `TP-40352-500GR`;
  - precio lista `295`;
  - precio manual `285`;
  - descuento estimado `10`;
  - total estimado `285`;
  - `bloqueos=[]`.
- Aviso:
  - backend avisa que el precio queda debajo del precio base y debe validarse margen antes de venta real.
- Conteo previo:
  - `erp_ventas_excepciones_comerciales=3`.
- Siguiente paso:
  - probar desde navegador autenticado: `Autorizacion` > `Registrar folio autorizado` > `Aplicar folio`.

## Evidencia tecnica - Verificador post-registro excepcion POS UI

- Fecha: 2026-06-29
- Script reforzado:
  - `storage/uat/uat_ventas_pos_excepcion_registro_readonly.php`.
- Mejora:
  - parametros esperados `--esperar_id_cliente_crm`, `--esperar_total`, `--esperar_estatus`;
  - filtro `--ultimo_autorizado=1` para ubicar el ultimo folio pendiente si no se copio desde UI;
  - filtros `--id_usuario` y `--id_sku`;
  - salida `hallazgos`;
  - valida snapshot CRM;
  - valida total;
  - valida estatus;
  - detecta folio autorizado que ya tiene venta/detalle ligado.
- Validacion tecnica:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_excepcion_registro_readonly.php`.
- Prueba contra folio ya consumido:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_registro_readonly.php --folio=EXC-20260629-000002 --esperar_id_cliente_crm=1 --esperar_total=285 --esperar_estatus=autorizada`;
  - resultado `ok=false`;
  - hallazgo esperado: estatus actual `aplicada`, no `autorizada`;
  - confirma que el verificador distingue folios consumidos antes de aplicar/cobrar.
- Prueba sin folio pendiente:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_registro_readonly.php --ultimo_autorizado=1 --id_usuario=1 --id_sku=1760 --esperar_id_cliente_crm=1 --esperar_total=285 --esperar_estatus=autorizada`;
  - resultado `ok=false`;
  - hallazgo esperado: no existe excepcion autorizada pendiente con esos filtros.
- Uso despues de UAT UI:
  - reemplazar `EXC-YYYYMMDD-000000` por el folio creado desde POS;
  - esperar `ok=true`, `estatus=autorizada`, `id_cliente_crm=1`, total `285`, sin venta/detalle ligado.

## Evidencia UI - Confirmacion registro folio real

- Fecha: 2026-06-29
- Alcance:
  - refuerzo UX para evitar registros accidentales o doble clic sobre `Registrar folio autorizado`;
  - no se ejecuto escritura BD.
- Archivo:
  - `public/assets/js/custom/apps/erp/ventas/pos.js`;
  - `app/vistas/paginas/apps/erp/ventas/pos.php`.
- Cambios:
  - antes de registrar folio real, POS muestra confirmacion;
  - usa `Swal.fire` si esta disponible;
  - cae a `window.confirm` si no hay SweetAlert;
  - mientras registra, el boton queda deshabilitado y muestra estado `Registrando...`;
  - al terminar o fallar, el boton recupera su texto original;
  - asset POS actualizado a `20260629-excepcion5`.
- Verificacion:
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`;
  - ambas sin errores.

## Evidencia backend - Deduplicacion reciente de excepcion comercial

- Fecha: 2026-06-29
- Alcance:
  - proteccion servidor contra doble envio accidental del registro de folio;
  - no cambia esquema;
  - no escribe datos durante esta validacion.
- Archivo:
  - `app/modelos/VentasErp.php`.
- Regla:
  - antes de insertar una excepcion comercial nueva, el backend busca una excepcion `autorizada` equivalente de los ultimos 2 minutos;
  - debe coincidir cliente, SKU, tipo, motivo, codigo de autorizacion, solicitante, autorizador, precio aplicado, descuento, subtotal y total;
  - solo reutiliza si no tiene `id_venta` ni `id_venta_detalle`.
- Resultado esperado:
  - si encuentra duplicado reciente, devuelve el folio existente con `duplicado_reciente=true`;
  - no crea otro folio;
  - conserva `no_crea_venta`, `no_mueve_caja`, `no_mueve_inventario`.
- Verificacion:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php` sin errores;
  - readiness UI read-only sigue `ok=true`, `bloqueos=[]`.

## Evidencia tecnica - Readiness cobro real desde POS UI

- Fecha: 2026-06-29
- Script nuevo:
  - `storage/uat/uat_ventas_pos_cobro_ui_readiness_readonly.php`.
- Proposito:
  - validar si el boton `Cobrar` puede conectarse a venta real sin escribir BD;
  - confirmar usuario, caja, turno, pagos, stock, ticket preview, garantias y excepcion comercial si aplica;
  - confirmar que en la primera medicion el endpoint real aun no estaba expuesto y requeria autorizacion fuerte.
- Validacion tecnica:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_cobro_ui_readiness_readonly.php`;
  - resultado sin errores.
- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cobro_ui_readiness_readonly.php --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295 --identificador_cliente=3312345678
```

- Resultado:
  - `ok=false`;
  - respaldo externo valido;
  - usuario `1` con `ventas.operar=true`;
  - asignacion POS activa `id_almacen=5`, `id_caja=2`;
  - no hay turno abierto;
  - SKU 1760 sin existencia suficiente para la prueba;
  - endpoints de soporte existen:
    - `/ventas/pos_carrito_prevalidar_erp`;
    - `/ventas/pos_confirmar_dryrun_erp`;
    - `/ventas/pos_excepcion_consumo_dryrun_erp`;
    - `/ventas/ticket_preview_dryrun_erp`;
  - en esa primera medicion, endpoint real propuesto `/ventas/pos_confirmar_erp` no existia todavia;
  - conteos antes/despues iguales en tablas criticas, confirmando modo read-only.
- Bloqueos actuales:
  - `No hay turno abierto para esta caja`;
  - `No hay turno abierto para la caja asignada`;
  - `Selecciona turno abierto de caja`;
  - `Existencia insuficiente`.
- Siguiente paso:
  - abrir turno y cargar stock UAT solo con autorizacion explicita;
  - despues de la autorizacion fuerte recibida, repetir readiness y ejecutar UAT de cobro real desde POS UI.

## Evidencia implementacion - Cobro real POS UI expuesto

- Fecha: 2026-06-29
- Autorizacion recibida:
  - `AUTORIZO EXPONER COBRO REAL POS UI usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`.
- Backend:
  - endpoint POST `/ventas/pos_confirmar_erp`;
  - controlador `Ventas::pos_confirmar_erp`;
  - modelo `VentasErp::confirmarVentaPosReal`;
  - requiere sesion, CSRF y `ventas.operar`;
  - auditoria explicita `ventas.pos_cobro_confirmar`;
  - excluido de auditoria generica en `Core`.
- Contrato real:
  - revalida asignacion POS del usuario;
  - exige turno abierto;
  - recalcula carrito, precios, pagos y salida de inventario en backend;
  - no confia en precio enviado por navegador;
  - si hay folio de excepcion, lo valida y lo consume dentro de la misma transaccion;
  - bloquea turno, atencion y excepcion con `FOR UPDATE`;
  - crea venta, detalle, pagos, movimiento de caja, kardex y trazabilidad detalle-inventario;
  - guarda snapshot de garantia por partida;
  - actualiza esperado del turno;
  - rollback completo ante excepcion.
- UI:
  - boton `Cobrar` conectado a `/ventas/pos_confirmar_erp`;
  - confirmacion antes de cobro real;
  - boton deshabilitado mientras cobra;
  - si backend bloquea, conserva carrito/pagos;
  - si backend confirma, muestra folio y limpia la cuenta cobrada;
  - asset POS actualizado a `20260629-cobro1`.
- Validaciones tecnicas:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`;
  - `C:\xampp\php\php.exe -l app\core\Core.php`;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_cobro_ui_readiness_readonly.php`.
- Readiness posterior:
  - endpoint real existe;
  - respaldo externo valido;
  - usuario `1` con `ventas.operar=true`;
  - asignacion POS activa `id_almacen=5`, `id_caja=2`;
  - sigue bloqueado por no tener turno abierto y por existencia insuficiente;
  - conteos antes/despues sin cambios, sin escritura en esta prueba.

## Evidencia tecnica - Preflight turno/stock para UAT cobro UI

- Fecha: 2026-06-29
- Alcance:
  - preparar la primera UAT real de cobro desde POS UI;
  - no abre turno;
  - no carga stock;
  - no crea venta.
- Ajuste:
  - `storage/uat/uat_ventas_pos_turno_preflight_readonly.php` ignoraba incorrectamente el aviso `No hay turno abierto` como bloqueo;
  - para apertura de turno, no tener turno abierto es la condicion esperada;
  - se corrigio para que solo bloquee cuando ya exista turno abierto u otro bloqueo real.
- Validacion:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_turno_preflight_readonly.php` sin errores.
- Preflight turno:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_preflight_readonly.php --id_usuario=1 --monto_inicial=500`;
  - resultado `ok=true`;
  - `puede_abrir_turno=true`;
  - asignacion activa `id_almacen=5`, `id_caja=2`;
  - turno abierto actual `null`;
  - `bloqueos=[]`.
- Preflight stock:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_stock_uat_preflight_readonly.php --id_almacen=5 --id_sku=1760 --cantidad=1`;
  - resultado `ok=true`;
  - SKU `TP-40352-500GR`;
  - almacen `MASCOTAS971`;
  - referencia sugerida `INV-INICIAL-POS-UAT-20260629-A5-S1760`;
  - `bloqueos=[]`.
- Siguiente paso:
  - requiere autorizacion explicita para abrir turno UAT;
  - requiere autorizacion explicita para cargar stock UAT;
  - despues repetir readiness de cobro UI y ejecutar venta real desde navegador.

## Evidencia UAT - Preparacion cobro real POS UI

- Fecha: 2026-06-29
- Autorizaciones recibidas:
  - abrir turno POS UAT con respaldo externo, `id_usuario=1`, `monto_inicial=500`;
  - cargar stock UAT POS con respaldo externo, `id_usuario=1`, `id_almacen=5`, `id_sku=1760`, `cantidad=1`, referencia `INV-INICIAL-POS-UAT-20260629-A5-S1760`.
- Apertura de turno:
  - script `storage/uat/uat_ventas_pos_turno_apertura_apply_authorized.php`;
  - resultado `ok=true`;
  - turno `TUR-20260629-002-003`;
  - `id_turno_caja=8`;
  - `id_movimiento_caja=16`;
  - almacen `5`;
  - caja `2`;
  - monto inicial `500`.
- Carga de stock:
  - script `storage/uat/uat_ventas_pos_stock_uat_apply_authorized.php`;
  - resultado `ok=true`;
  - referencia `INV-INICIAL-POS-UAT-20260629-A5-S1760`;
  - `InventarioErp::aplicarAjuste` aplico inventario inicial ERP;
  - movimientos `1`;
  - origen `inventario_inicial`.
- Readiness cobro UI posterior:
  - script `storage/uat/uat_ventas_pos_cobro_ui_readiness_readonly.php`;
  - resultado `ok=true`;
  - endpoint `/ventas/pos_confirmar_erp` existe;
  - usuario `1` con `ventas.operar=true`;
  - asignacion activa;
  - turno abierto `id_turno_caja=8`;
  - prevalidacion `success`;
  - confirmacion dry-run `success`;
  - ticket preview `success`;
  - `bloqueos=[]`;
  - conteos antes/despues iguales durante readiness.
- Venta real:
  - no ejecutada todavia;
  - requiere autorizacion explicita separada para UAT de cobro POS UI.

## Evidencia UAT - Cobro real POS UI ejecutado

- Fecha: 2026-06-29
- Autorizacion recibida:
  - `AUTORIZO EJECUTAR COBRO REAL POS UI UAT usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_sku=1760 cantidad=1 precio=295 pago=295`.
- Aplicador:
  - script nuevo `storage/uat/uat_ventas_pos_cobro_ui_apply_authorized.php`;
  - invoca `VentasErp::confirmarVentaPosReal`, el mismo contrato del endpoint `/ventas/pos_confirmar_erp`;
  - guardrail `--autorizar=VENTAS_POS_COBRO_UI_REAL`;
  - validacion `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_cobro_ui_apply_authorized.php` sin errores.
- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cobro_ui_apply_authorized.php --autorizar=VENTAS_POS_COBRO_UI_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295 --identificador_cliente=3312345678
```

- Resultado:
  - `ok=true`;
  - folio `POS-20260629-000003`;
  - `id_venta=8`;
  - estatus `pagada`;
  - cliente mostrador UAT, identificador `3312345678`;
  - subtotal `295`;
  - total `295`;
  - pago `295`;
  - saldo `0`.
- Inventario:
  - `id_existencia_inventario=34`;
  - `id_movimiento_inventario=65`;
  - salida `1`;
  - existencia anterior `1`;
  - existencia nueva `0`.
- Caja:
  - pago `id_venta_pago=8`;
  - movimiento caja venta `id_movimiento_caja=17`;
  - metodo `Efectivo`;
  - monto aplicado `295`.
- Garantias:
  - snapshot `id_venta_detalle_garantia=5`;
  - resumen ticket `Sin garantia`.
- Post-venta read-only:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_venta_readonly.php --folio=POS-20260629-000003`;
  - resultado `ok=true`;
  - partidas `1`;
  - pagos `1`;
  - garantias `1`;
  - trazabilidades `1`;
  - `hallazgos=[]`.
- Ticket formal:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260629-000003`;
  - resultado `ok=true`;
  - ticket `28` lineas;
  - `hallazgos=[]`;
  - muestra precio `Lista UAT POS`, garantia `Sin garantia`, pago efectivo e inventario trazado.
- Cierre dry-run:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=795`;
  - resultado `ok=true`;
  - turno `TUR-20260629-002-003`;
  - monto esperado `795`;
  - contado `795`;
  - diferencia `0`;
  - ventas `1`, total `295`;
  - movimientos: apertura `500`, venta `295`.
- Guardrail de doble venta/stock:
  - readiness posterior con el mismo SKU ya queda `ok=false`;
  - bloqueo esperado `Existencia insuficiente`;
  - conteos antes/despues iguales en readiness posterior.
- Siguiente paso:
  - cerrar turno real con monto contado `795` solo con autorizacion explicita separada.

## Evidencia UAT - Cierre turno cobro UI real

- Fecha: 2026-06-29
- Autorizacion recibida:
  - `AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS cobro UI real"`.
- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_cierre_apply_authorized.php --autorizar=VENTAS_POS_TURNO_CIERRE --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --monto_contado=795 --observaciones="Cierre UAT POS cobro UI real"
```

- Resultado:
  - `ok=true`;
  - modo `turno_cerrado`;
  - turno `TUR-20260629-002-003`;
  - `id_turno_caja=8`;
  - almacen `5`;
  - caja `2`;
  - monto esperado `795`;
  - monto contado `795`;
  - diferencia `0`;
  - ventas `1`, total `295`, pagado `295`, saldo `0`;
  - movimientos: apertura `500`, venta POS `295`.
- Post-cierre read-only:
  - comando `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_post_cierre_readonly.php --folio=TUR-20260629-002-003`;
  - resultado `ok=true`;
  - estatus `cerrado`;
  - fecha cierre registrada;
  - ventas count `1`;
  - pagos count `1`;
  - movimientos caja count `2`;
  - `hallazgos=[]`.
- Preflight turno posterior:
  - `puede_abrir_turno=true`;
  - turno abierto actual `null`;
  - `bloqueos=[]`.
- Readiness cobro UI posterior:
  - `ok=false` esperado;
  - bloqueos:
    - `No hay turno abierto para esta caja`;
    - `Selecciona turno abierto de caja`;
    - `Existencia insuficiente`.
- Conclusion UAT:
  - ciclo POS UI real completo validado: apertura, stock, cobro, venta, caja, kardex, garantia snapshot, ticket, cierre y bloqueo posterior.

## Evidencia tecnica - Dry-run devolucion/cancelacion POS

- Fecha: 2026-06-29
- Alcance:
  - fortalecer simulacion de devoluciones/cancelaciones POS sin escribir BD;
  - validar venta original, detalle, cantidad disponible para devolver, motivo, decision de inventario y reembolso estimado;
  - preparar contrato antes de autorizar reversa real.
- Ajustes:
  - `VentasErp::devolucionDryRun` ahora consulta la venta original por folio/id;
  - valida que sea canal `pos`, documento `venta` y estatus reversible;
  - valida que cada detalle pertenezca a la venta;
  - calcula cantidad vendida, devuelta previa, disponible y reembolso estimado con precio historico;
  - para `cancelacion` sin partidas captura automaticamente todas las partidas de la venta;
  - agrega aviso cuando el reembolso real requiere turno/caja abierto o saldo a favor;
  - corrige lectura de `id_usuario` para validar caja/turno en el dry-run.
- Script read-only:
  - `storage/uat/uat_ventas_pos_devolucion_dryrun_readonly.php`;
  - no crea devolucion;
  - no reembolsa caja;
  - no mueve inventario.
- Validaciones tecnicas:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_devolucion_dryrun_readonly.php`.
- Caso positivo devolucion parcial/completa de partida:
  - folio `POS-20260629-000003`;
  - `id_usuario=1`;
  - `id_venta=8`;
  - `id_venta_detalle=8`;
  - SKU `1760`, `TP-40352-500GR`;
  - cantidad vendida `1`;
  - cantidad solicitada `1`;
  - decision inventario `cuarentena`;
  - resultado `ok=true`;
  - `bloqueos=[]`;
  - reembolso estimado `295`;
  - aviso esperado: requiere turno/caja abierto o saldo a favor para reembolso real.
- Caso negativo cantidad excedente:
  - cantidad solicitada `2`;
  - resultado `ok=false`;
  - bloqueo `Partida 1: cantidad excede disponible para devolver`.
- Caso negativo sin motivo:
  - resultado `ok=false`;
  - bloqueo `Captura motivo documentado`.
- Caso cancelacion completa:
  - tipo `cancelacion`;
  - sin enviar partidas manuales;
  - el dry-run toma automaticamente el detalle `8` por cantidad `1`;
  - decision inventario `sin_reingreso`;
  - resultado `ok=true`;
  - reembolso estimado `295`;
  - aviso esperado: requiere turno/caja abierto o saldo a favor para reembolso real.
- Pendiente:
  - no ejecutar devolucion/cancelacion real sin autorizacion explicita y respaldo externo;
  - falta definir si el reembolso real sale de caja, se registra como saldo a favor, se cambia producto o se escala a autorizacion de supervisor.

## Evidencia tecnica - Readiness DDL reversas POS

- Fecha: 2026-06-29
- Alcance:
  - preparar esquema formal para devolucion/cancelacion real;
  - no aplicar DDL;
  - no crear devolucion;
  - no reembolsar caja;
  - no mover inventario.
- Cambios preparados:
  - `VentasErpEsquema::planActualizarReversasPos`;
  - `VentasErpEsquema::auditarReversasPos`;
  - endpoint read-only `/ventas/esquema_auditar_reversas_pos`;
  - endpoint protegido `/ventas/esquema_actualizar_reversas_pos`;
  - script read-only `storage/uat/uat_ventas_pos_reversa_schema_readonly.php`;
  - script read-only `storage/uat/uat_ventas_pos_reversa_readiness_readonly.php`;
  - aplicador protegido `storage/uat/uat_ventas_pos_reversa_schema_apply_authorized.php`.
- Validaciones tecnicas:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErpEsquema.php`;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_reversa_schema_readonly.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_reversa_readiness_readonly.php`;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_reversa_schema_apply_authorized.php`.
- Auditoria read-only:
  - tablas base existentes:
    - `erp_ventas`;
    - `erp_ventas_detalle`;
    - `erp_ventas_pagos`;
    - `erp_pos_movimientos_caja`;
    - `erp_pos_turnos`;
    - `erp_inventario_existencias`;
    - `erp_inventario_movimientos`;
    - `erp_ventas_devoluciones`;
    - `erp_ventas_devoluciones_detalle`.
  - faltantes detectados:
    - columnas de caja/almacen/turno/movimiento en `erp_ventas_devoluciones`;
    - decision financiera;
    - monto reembolso;
    - monto saldo a favor;
    - autorizador/aplicador/fechas;
    - snapshot;
    - existencia/almacen destino/importe por detalle;
    - indices de caja, movimiento caja, existencia y movimiento inventario.
- Readiness operativo read-only:
  - folio probado `POS-20260629-000003`;
  - devolucion por detalle `8`, cantidad `1`;
  - decision inventario `cuarentena`;
  - decision financiera `reembolso_caja`;
  - respaldo externo valido;
  - endpoints disponibles;
  - dry-run devolucion `success`, reembolso estimado `295`;
  - dry-run caja bloquea porque el turno de la venta ya esta cerrado;
  - readiness final `ok=false`.
- Readiness alterno con saldo a favor:
  - decision financiera `saldo_favor`;
  - dry-run devolucion `success`;
  - no exige movimiento de caja inmediato;
  - readiness final sigue `ok=false` unicamente por DDL pendiente.
- Bloqueos esperados:
  - falta aplicar DDL de reversas POS;
  - para reembolso de caja se requiere turno/caja abierto o decidir saldo a favor/cambio.
- Siguiente autorizacion propuesta:

```text
AUTORIZO APLICAR DDL REVERSAS POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_REVERSA_DDL para UAT POS
```

- Documentos soporte:
  - `docs/erp_ventas_pos_reversas_solicitud_autorizacion.md`;
  - `docs/erp_ventas_pos_reversas_runbook_aplicacion.md`.

## Evidencia UAT - DDL reversas POS aplicado

- Fecha: 2026-06-30
- Autorizacion recibida:

```text
AUTORIZO APLICAR DDL REVERSAS POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_REVERSA_DDL para UAT POS
```

- Previo read-only:
  - `storage/uat/uat_ventas_pos_reversa_schema_readonly.php`;
  - tablas base existentes;
  - faltaban columnas/indices de reversa POS.
- Readiness previo con `decision_financiera=saldo_favor`:
  - folio `POS-20260629-000003`;
  - dry-run devolucion `success`;
  - reembolso estimado `295`;
  - bloqueo unico: `Falta aplicar DDL reversas POS antes de reversa real`.
- Aplicador ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_schema_apply_authorized.php --autorizar=VENTAS_POS_REVERSA_DDL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql
```

- Resultado:
  - `ok=true`;
  - respaldo externo existe;
  - 16 columnas agregadas;
  - 4 indices agregados;
  - no creo devoluciones reales;
  - no registro reembolsos;
  - no movio inventario.
- Columnas agregadas a `erp_ventas_devoluciones`:
  - `id_caja`;
  - `id_almacen`;
  - `id_turno_caja`;
  - `id_movimiento_caja`;
  - `decision_financiera`;
  - `monto_reembolso`;
  - `monto_saldo_favor`;
  - `autorizado_por`;
  - `fecha_autorizacion`;
  - `aplicado_por`;
  - `fecha_aplicacion`;
  - `datos_snapshot`.
- Columnas agregadas a `erp_ventas_devoluciones_detalle`:
  - `id_existencia_inventario`;
  - `id_almacen_destino`;
  - `importe_reembolso`;
  - `datos_snapshot`.
- Indices agregados:
  - `idx_ventas_devolucion_caja`;
  - `idx_ventas_devolucion_mov_caja`;
  - `idx_devolucion_detalle_existencia`;
  - `idx_devolucion_detalle_mov_inv`.
- Auditoria posterior:
  - columnas nuevas existentes;
  - indices nuevos existentes;
  - `faltantes=[]`.
- Readiness posterior con `decision_financiera=saldo_favor`:
  - `ok=true`;
  - `bloqueos=[]`;
  - dry-run devolucion `success`;
  - reembolso estimado `295`;
  - no exige movimiento de caja inmediato.
- Readiness posterior con `decision_financiera=reembolso_caja`:
  - `ok=false`;
  - bloqueo esperado: `El turno seleccionado no esta abierto para esa caja y almacen`;
  - confirma que reembolso de caja requiere turno abierto.
- Siguiente paso:
  - implementar/aplicar reversa real POS con autorizacion especifica;
  - este DDL no ejecuta devoluciones reales.

## Evidencia UAT - Aplicador reversa POS real preparado

- Fecha: 2026-06-30
- Objetivo:
  - dejar listo el aplicador UAT de reversa POS real sin ejecutarlo;
  - mantener guardrail de token fuerte, respaldo externo, venta, detalle, cantidad y motivo.
- Archivo creado:
  - `storage/uat/uat_ventas_pos_reversa_apply_authorized.php`.
- Verificador post-reversa creado:
  - `storage/uat/uat_ventas_pos_reversa_post_readonly.php`;
  - valida devolucion, venta original, detalle, pago de reembolso, movimiento de caja y kardex reversa sin escribir BD.
- Modelo preparado:
  - `VentasErp::confirmarReversaPosReal`.
- Contrato implementado:
  - no borra venta original;
  - genera folio `DEV-YYYYMMDD-000001` o `CAN-YYYYMMDD-000001`;
  - crea encabezado y detalle de devolucion/cancelacion;
  - conserva snapshot de venta, partidas, decision de inventario y decision financiera;
  - permite `saldo_favor` sin movimiento de caja;
  - exige turno abierto de la misma caja/almacen para `reembolso_caja`;
  - bloquea reintegro automatico de unidad fisica y exige cuarentena/inspeccion;
  - registra kardex de entrada solo cuando `decision_inventario=reintegrar` y aplica a existencia agregada valida.
- Validaciones ejecutadas:
  - `php -l app\modelos\VentasErp.php`: sin errores;
  - `php -l storage\uat\uat_ventas_pos_reversa_apply_authorized.php`: sin errores;
  - `php -l storage\uat\uat_ventas_pos_reversa_post_readonly.php`: sin errores;
  - ejecucion del aplicador sin parametros: bloqueada por guardrail, no escribio BD.
- Validacion post-reversa antes de ejecutar reversa:
  - comando read-only con `--folio_venta=POS-20260629-000003`;
  - resultado esperado `ok=false`;
  - hallazgo esperado: no existe devolucion/cancelacion todavia;
  - no escribio BD.
- Readiness read-only posterior:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_readiness_readonly.php --folio=POS-20260629-000003 --id_usuario=1 --id_venta_detalle=8 --cantidad=1 --tipo=devolucion --decision_inventario=cuarentena --decision_financiera=saldo_favor --motivo="UAT devolucion POS saldo a favor" --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql
```

- Resultado readiness:
  - `ok=true`;
  - `bloqueos=[]`;
  - esquema de reversas completo;
  - dry-run devolucion `success`;
  - reembolso/saldo estimado `295`;
  - no exige caja al ser `saldo_favor`.
- No ejecutado:
  - no se creo devolucion real;
  - no se modifico la venta;
  - no se genero saldo a favor real;
  - no se movio inventario;
  - no se registro reembolso de caja.
- Siguiente autorizacion necesaria para primera reversa real:

```text
AUTORIZO EJECUTAR REVERSA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_REVERSA_REAL id_usuario=1 folio=POS-20260629-000003 id_venta_detalle=8 cantidad=1 tipo=devolucion decision_inventario=cuarentena decision_financiera=saldo_favor motivo="UAT devolucion POS saldo a favor"
```

## Evidencia UAT - Reversa POS real ejecutada a saldo a favor

- Fecha: 2026-06-30
- Autorizacion recibida:

```text
AUTORIZO EJECUTAR REVERSA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_REVERSA_REAL id_usuario=1 folio=POS-20260629-000003 id_venta_detalle=8 cantidad=1 tipo=devolucion decision_inventario=cuarentena decision_financiera=saldo_favor motivo="UAT devolucion POS saldo a favor"
```

- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_apply_authorized.php --autorizar=VENTAS_POS_REVERSA_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --folio=POS-20260629-000003 --id_venta_detalle=8 --cantidad=1 --tipo=devolucion --decision_inventario=cuarentena --decision_financiera=saldo_favor --motivo="UAT devolucion POS saldo a favor"
```

- Resultado:
  - `ok=true`;
  - folio devolucion `DEV-20260630-000001`;
  - `id_devolucion=1`;
  - venta original `POS-20260629-000003`, `id_venta=8`;
  - detalle original `id_venta_detalle=8`;
  - cantidad devuelta `1`;
  - importe `295`;
  - decision inventario `cuarentena`;
  - decision financiera `saldo_favor`;
  - `monto_saldo_favor=295`;
  - `monto_reembolso=0`;
  - `id_movimiento_caja=null`;
  - `id_venta_pago_reembolso=null`;
  - estatus venta posterior `devuelta`.
- Verificacion post-reversa read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_post_readonly.php --folio_devolucion=DEV-20260630-000001
```

- Resultado post:
  - `ok=true`;
  - `hallazgos=[]`;
  - encabezado devolucion `aplicada`;
  - venta `devuelta`;
  - saldo original de venta conserva `0`;
  - detalle ligado a movimiento inventario origen `65`;
  - existencia origen `34`;
  - sin movimiento de caja;
  - sin pago de reembolso;
  - sin kardex de entrada por decision `cuarentena`.
- Guardrail anti-duplicado/reversa repetida:
  - readiness posterior sobre la misma venta y detalle:
    - `ok=false`;
    - bloqueo `La venta ya esta cancelada/devuelta`;
    - reembolso estimado `0`;
    - confirma que no permite repetir devolucion sobre venta ya devuelta.
- Siguiente paso:
  - preparar flujo de visualizacion/ticket de devolucion;
  - despues probar reembolso de caja con turno abierto;
  - despues modelar inspeccion/cuarentena para decidir reintegro, merma o garantia.

## Evidencia UAT - Ticket formal de devolucion POS read-only

- Fecha: 2026-06-30
- Objetivo:
  - consultar/imprimir comprobante formal de devolucion POS aplicada;
  - no escribir BD;
  - no mover caja;
  - no mover inventario.
- Cambios:
  - `VentasErp::ticketDevolucionFormalReadOnly`;
  - `VentasErp::formatearTicketDevolucion`;
  - endpoint `/ventas/pos_ticket_devolucion_erp`;
  - script `storage/uat/uat_ventas_pos_ticket_devolucion_readonly.php`.
- Validaciones tecnicas:
  - `php -l app\modelos\VentasErp.php`: sin errores;
  - `php -l app\controladores\Ventas.php`: sin errores;
  - `php -l storage\uat\uat_ventas_pos_ticket_devolucion_readonly.php`: sin errores.
- Comando UAT:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_devolucion_readonly.php --folio_devolucion=DEV-20260630-000001
```

- Resultado:
  - `ok=true`;
  - `read_only=true`;
  - `hallazgos=[]`;
  - folio devolucion `DEV-20260630-000001`;
  - venta original `POS-20260629-000003`;
  - turno mostrado `TUR-20260629-002-003`;
  - tienda `Mascotas Mina 971`;
  - caja `CJ-MASCOTAS971-01`;
  - cliente `Cliente mostrador UAT`;
  - SKU `TP-40352-500GR`;
  - cantidad `1.000 kg`;
  - importe `295`;
  - decision inventario `cuarentena`;
  - decision financiera `SALDO_FAVOR`;
  - saldo favor `295`;
  - reembolso `0`;
  - operacion `APLICADA`.
- Hallazgo corregido durante UAT:
  - el ticket quedaba sin turno cuando la devolucion era saldo a favor porque `id_turno_caja` de la devolucion es `NULL`;
  - se ajusto la consulta para tomar `COALESCE(d.id_turno_caja, v.id_turno_caja)`;
  - ticket posterior muestra turno original correctamente.
- Siguiente paso:
  - integrar reimpresion visual desde UI;
  - preparar UAT de reembolso de caja con turno abierto en una venta nueva.

## Evidencia tecnica - Preparacion UAT reembolso de caja POS

- Fecha: 2026-06-30
- Objetivo:
  - preparar siguiente UAT real con `decision_financiera=reembolso_caja`;
  - no escribir BD en esta preparacion.
- Documento creado:
  - `docs/erp_ventas_pos_reembolso_caja_solicitud_autorizacion.md`.
- Preflight turno:
  - `ok=true`;
  - usuario `1` con asignacion POS activa;
  - almacen `5`;
  - caja `2`;
  - tienda `Mascotas Mina 971`;
  - no hay turno abierto;
  - puede abrirse turno autorizado.
- Preflight stock:
  - `ok=true`;
  - SKU `1760`, `TP-40352-500GR`;
  - precio `295`;
  - cantidad recomendada `1`;
  - referencia recomendada `INV-INICIAL-POS-UAT-20260630-A5-S1760`.
- Readiness sobre venta anterior:
  - venta `POS-20260629-000003`;
  - `ok=false`;
  - bloqueo esperado `La venta ya esta cancelada/devuelta`;
  - confirma que no se debe reutilizar venta ya devuelta para probar reembolso.
- Autorizacion recomendada:

```text
AUTORIZO UAT REEMBOLSO CAJA POS REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 precio=295 pago=295 monto_inicial=500 monto_contado=500 referencia_stock=INV-INICIAL-POS-UAT-20260630-A5-S1760 motivo="UAT devolucion POS con reembolso de caja"
```

## Evidencia UAT - Reembolso de caja POS real

- Fecha: 2026-06-30
- Autorizacion recibida:

```text
AUTORIZO UAT REEMBOLSO CAJA POS REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 precio=295 pago=295 monto_inicial=500 monto_contado=500 referencia_stock=INV-INICIAL-POS-UAT-20260630-A5-S1760 motivo="UAT devolucion POS con reembolso de caja"
```

- Turno abierto:
  - folio `TUR-20260630-002-001`;
  - `id_turno_caja=9`;
  - `id_movimiento_caja=18`;
  - almacen `5`;
  - caja `2`;
  - monto inicial `500`.
- Stock UAT cargado:
  - SKU `1760`;
  - cantidad `1`;
  - referencia `INV-INICIAL-POS-UAT-20260630-A5-S1760`;
  - respuesta inventario `success`;
  - movimientos kardex `1`.
- Venta POS real:
  - folio `POS-20260630-000001`;
  - `id_venta=9`;
  - `id_venta_detalle=9`;
  - total `295`;
  - pago `295`;
  - estatus inicial `pagada`;
  - movimiento caja venta `id_movimiento_caja=19`;
  - kardex salida `id_movimiento_inventario=67`;
  - existencia `34` de `1` a `0`;
  - garantia snapshot `id_venta_detalle_garantia=6`, `Sin garantia`.
- Reversa POS real con reembolso:
  - folio devolucion `DEV-20260630-000002`;
  - `id_devolucion=2`;
  - tipo `devolucion`;
  - decision inventario `cuarentena`;
  - decision financiera `reembolso_caja`;
  - monto reembolso `295`;
  - monto saldo favor `0`;
  - movimiento caja reembolso `id_movimiento_caja=20`;
  - pago reembolso `id_venta_pago=10`;
  - venta posterior `devuelta`;
  - detalle devolucion `id_devolucion_detalle=2`;
  - sin kardex entrada por decision `cuarentena`.
- Post-reversa read-only:
  - `ok=true`;
  - `hallazgos=[]`;
  - pago tipo `reembolso` ligado a `DEV-20260630-000002`;
  - movimiento caja `reembolso_cliente` por `295`;
  - requiere autorizacion `1`;
  - requiere evidencia `1`, estado `pendiente`.
- Ticket devolucion read-only:
  - `ok=true`;
  - `hallazgos=[]`;
  - muestra `REEMBOLSO_CAJA`;
  - reembolso `295`;
  - caja movimiento `20`;
  - turno `TUR-20260630-002-001`.
- Cierre dry-run:
  - `ok=true`;
  - monto esperado `500`;
  - monto contado `500`;
  - diferencia `0`;
  - resumen:
    - entrada monto inicial `500`;
    - ingreso venta POS `295`;
    - reembolso cliente `295`.
- Cierre real:
  - `ok=true`;
  - turno `TUR-20260630-002-001`;
  - `id_turno_caja=9`;
  - monto esperado `500`;
  - monto contado `500`;
  - diferencia `0`;
  - estatus `cerrado`.
- Post-cierre read-only:
  - `ok=true`;
  - `hallazgos=[]`;
  - no queda turno abierto para el usuario/caja;
  - preflight posterior permite abrir nuevo turno.
- Guardrail anti-reversa repetida:
  - readiness posterior sobre `POS-20260630-000001` queda `ok=false`;
  - bloqueo `La venta ya esta cancelada/devuelta`;
  - confirma que no se puede devolver dos veces la misma venta.
- Siguiente paso:
  - resolver flujo operativo de evidencia de reembolso de caja;
  - disenar modulo/flujo de cuarentena e inspeccion antes de reintegrar inventario;
  - integrar visualmente reimpresion de ticket de devolucion en UI.

## Evidencia tecnica - Bandeja read-only evidencias de caja

- Fecha: 2026-06-30
- Objetivo:
  - consultar movimientos de caja que requieren comprobante sin escribir BD;
  - detectar reembolsos/gastos pendientes aunque el turno ya este cerrado.
- Cambios:
  - `VentasErp::evidenciasCajaPendientesReadOnly`;
  - endpoint `/ventas/caja_evidencias_pendientes_erp`;
  - script `storage/uat/uat_ventas_pos_caja_evidencias_readonly.php`;
  - documento `docs/erp_ventas_pos_evidencias_caja_plan.md`.
- Validaciones tecnicas:
  - `php -l app\modelos\VentasErp.php`: sin errores;
  - `php -l app\controladores\Ventas.php`: sin errores;
  - `php -l storage\uat\uat_ventas_pos_caja_evidencias_readonly.php`: sin errores.
- Comando UAT:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencias_readonly.php --id_turno_caja=9 --evidencia_estado=pendiente
```

- Resultado:
  - `ok=true`;
  - `read_only=true`;
  - `total_registros=1`;
  - monto total `295`;
  - movimiento caja `20`;
  - tipo `reembolso`;
  - referencia `DEV-20260630-000002`;
  - venta `POS-20260630-000001`;
  - turno `TUR-20260630-002-001`;
  - turno estatus `cerrado`;
  - tienda `Mascotas Mina 971`;
  - caja `CJ-MASCOTAS971-01`;
  - evidencia estado `pendiente`.
- Correccion tecnica durante UAT:
  - el parser CLI recortaba un caracter de `pendiente` y no tomaba `id_turno_caja`;
  - se corrigieron offsets de `--id_turno_caja` y `--evidencia_estado`;
  - consulta posterior encontro correctamente el reembolso pendiente.
- Siguiente paso:
  - DDL formal de evidencias/adjuntos de caja;
  - endpoint para adjuntar evidencia;
  - endpoint para aprobar/rechazar con permiso puntual.

## Evidencia tecnica - Readiness DDL evidencias de caja POS

- Fecha: 2026-06-30
- Objetivo:
  - preparar estructura formal para adjuntos/comprobantes de caja;
  - no aplicar DDL sin autorizacion.
- Cambios preparados:
  - `VentasErpEsquema::planActualizarEvidenciasCajaPos`;
  - `VentasErpEsquema::auditarEvidenciasCajaPos`;
  - endpoint `/ventas/esquema_auditar_evidencias_caja_pos`;
  - endpoint `/ventas/esquema_actualizar_evidencias_caja_pos`;
  - script read-only `storage/uat/uat_ventas_pos_caja_evidencias_schema_readonly.php`;
  - aplicador protegido `storage/uat/uat_ventas_pos_caja_evidencias_schema_apply_authorized.php`;
  - solicitud `docs/erp_ventas_pos_evidencias_caja_schema_solicitud_autorizacion.md`.
- Validaciones tecnicas:
  - `php -l app\modelos\VentasErpEsquema.php`: sin errores;
  - `php -l app\controladores\Ventas.php`: sin errores;
  - `php -l storage\uat\uat_ventas_pos_caja_evidencias_schema_readonly.php`: sin errores;
  - `php -l storage\uat\uat_ventas_pos_caja_evidencias_schema_apply_authorized.php`: sin errores.
- Auditoria read-only:
  - `erp_pos_movimientos_caja` existe;
  - `erp_pos_movimientos_caja_evidencias` no existe;
  - faltan columnas/indices de evidencia;
  - falta indice `idx_pos_movimiento_evidencia`.
- Plan DDL generado sin ejecutar:
  - crear tabla `erp_pos_movimientos_caja_evidencias`;
  - agregar indice `idx_pos_movimiento_evidencia` a `erp_pos_movimientos_caja`.
- Siguiente autorizacion requerida:

```text
AUTORIZO APLICAR DDL EVIDENCIAS CAJA POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIAS_DDL para UAT POS
```

## Evidencia UAT - DDL evidencias de caja POS aplicado

- Fecha: 2026-06-30
- Autorizacion recibida:

```text
AUTORIZO APLICAR DDL EVIDENCIAS CAJA POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIAS_DDL para UAT POS
```

- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencias_schema_apply_authorized.php --autorizar=VENTAS_POS_CAJA_EVIDENCIAS_DDL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql
```

- Resultado:
  - `ok=true`;
  - respaldo externo existe;
  - tabla `erp_pos_movimientos_caja_evidencias` creada;
  - indice `idx_pos_movimiento_evidencia` agregado a `erp_pos_movimientos_caja`;
  - no adjunto archivos;
  - no aprobo evidencias;
  - no modifico movimientos de caja;
  - no movio dinero ni inventario.
- Auditoria posterior:
  - `erp_pos_movimientos_caja_evidencias` existe;
  - columnas principales existen:
    - `id_evidencia_caja`;
    - `id_movimiento_caja`;
    - `tipo_evidencia`;
    - `estatus`;
    - `archivo_ruta`;
    - `archivo_hash`;
    - `referencia_externa`;
    - `datos_snapshot`;
    - `creado_por`;
    - `revisado_por`;
    - `motivo_rechazo`.
  - indices existentes:
    - `idx_caja_evidencia_movimiento`;
    - `idx_caja_evidencia_estado`;
    - `idx_caja_evidencia_hash`;
    - `idx_pos_movimiento_evidencia`.
- Bandeja de pendientes posterior:
  - `ok=true`;
  - sigue mostrando movimiento `20`;
  - referencia `DEV-20260630-000002`;
  - venta `POS-20260630-000001`;
  - turno `TUR-20260630-002-001`;
  - estado `pendiente`;
  - monto `295`.
- Siguiente paso:
  - preparar aplicador autorizado para registrar evidencia UAT sin archivo fisico real o con referencia externa;
  - despues endpoint productivo de adjunto con validacion de archivo y permisos.

## Evidencia tecnica - Aplicador evidencia caja POS preparado

- Fecha: 2026-06-30
- Objetivo:
  - preparar registro real de evidencia para movimiento de caja con guardrail;
  - no ejecutar registro sin autorizacion.
- Cambios:
  - `VentasErp::registrarEvidenciaCajaPosReal`;
  - script `storage/uat/uat_ventas_pos_caja_evidencia_apply_authorized.php`.
- Contrato:
  - requiere token `VENTAS_POS_CAJA_EVIDENCIA_REAL`;
  - requiere respaldo externo;
  - requiere usuario;
  - requiere movimiento de caja;
  - requiere archivo, referencia externa o descripcion;
  - inserta en `erp_pos_movimientos_caja_evidencias`;
  - actualiza `erp_pos_movimientos_caja.evidencia_estado` a `recibida`;
  - no aprueba evidencia;
  - no mueve dinero;
  - no modifica turno.
- Validaciones:
  - `php -l app\modelos\VentasErp.php`: sin errores;
  - `php -l storage\uat\uat_ventas_pos_caja_evidencia_apply_authorized.php`: sin errores;
  - ejecucion sin parametros: bloqueada por guardrail, no escribio BD.
- Siguiente autorizacion requerida:

```text
AUTORIZO REGISTRAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_REAL id_usuario=1 id_movimiento_caja=20 tipo_evidencia=ticket_firmado referencia_externa=DEV-20260630-000002 descripcion="Comprobante UAT de reembolso de caja firmado por cliente"
```

## Evidencia UAT - Registro real evidencia reembolso caja POS

- Fecha: 2026-06-30
- Autorizacion recibida:

```text
AUTORIZO REGISTRAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_REAL id_usuario=1 id_movimiento_caja=20 tipo_evidencia=ticket_firmado referencia_externa=DEV-20260630-000002 descripcion="Comprobante UAT de reembolso de caja firmado por cliente"
```

- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencia_apply_authorized.php --autorizar=VENTAS_POS_CAJA_EVIDENCIA_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_movimiento_caja=20 --tipo_evidencia=ticket_firmado --referencia_externa=DEV-20260630-000002 --descripcion="Comprobante UAT de reembolso de caja firmado por cliente"
```

- Resultado:
  - `ok=true`;
  - `id_evidencia_caja=1`;
  - `id_movimiento_caja=20`;
  - `tipo_evidencia=ticket_firmado`;
  - `referencia_externa=DEV-20260630-000002`;
  - `evidencia_estado=recibida`;
  - `requiere_revision=true`;
  - no aprobo la evidencia;
  - no movio dinero;
  - no modifico inventario;
  - no reabrio ni cerro turno.
- Auditoria posterior:
  - consulta de pendientes turno `9`: `total_registros=0`;
  - consulta de recibidas turno `9`: `total_registros=1`, monto `295`, movimiento caja `20`;
  - consulta total turno `9`: `total_registros=1`, estado `recibida`.
- Siguiente paso:
  - preparar flujo de revision de evidencia con decision `aprobada` o `rechazada`;
  - si se rechaza, exigir motivo y devolver el movimiento a atencion operativa;
  - no permitir aprobacion automatica desde el registro de evidencia.

## Evidencia tecnica - Revision evidencia caja POS preparada

- Fecha: 2026-06-30
- Objetivo:
  - preparar aprobacion/rechazo formal de evidencias de caja POS.
- Cambios:
  - `VentasErp::evidenciasCajaDetalleReadOnly`;
  - `VentasErp::revisarEvidenciaCajaPosReal`;
  - endpoint `/ventas/caja_evidencias_detalle_erp`;
  - endpoint `/ventas/caja_evidencia_revisar_erp`;
  - script read-only `storage/uat/uat_ventas_pos_caja_evidencias_detalle_readonly.php`;
  - script autorizado `storage/uat/uat_ventas_pos_caja_evidencia_revision_apply_authorized.php`.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_caja_evidencias_detalle_readonly.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_caja_evidencia_revision_apply_authorized.php`: sin errores;
  - detalle evidencia `1`: `ok=true`, estado `recibida`, movimiento `20`, venta `POS-20260630-000001`, devolucion `DEV-20260630-000002`;
  - script de revision sin parametros: bloqueado, no escribio BD.
- Reglas implementadas:
  - decision `aprobada` o `rechazada`;
  - rechazo exige motivo;
  - solo permite revisar evidencia `recibida`;
  - por defecto exige usuario distinto al que registro la evidencia;
  - exige permiso `ventas.autorizar_excepcion_comercial`;
  - no mueve dinero ni inventario.
- Siguiente autorizacion recomendada:

```text
AUTORIZO REVISAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_REVISION_REAL id_usuario=ID_REVISOR id_evidencia_caja=1 decision=aprobada
```

## Evidencia UAT - Revision no ejecutada por revisor pendiente

- Fecha: 2026-06-30
- Autorizacion recibida:

```text
AUTORIZO REVISAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_REVISION_REAL id_usuario=ID_REVISOR id_evidencia_caja=1 decision=aprobada
```

- Resultado:
  - no se ejecuto escritura porque `ID_REVISOR` es marcador y no un `id_usuario` numerico;
  - evidencia `1` sigue en estado `recibida`;
  - no se aprobo ni rechazo evidencia;
  - no se movio dinero ni inventario.
- Consulta read-only de candidatos:
  - script `storage/uat/uat_ventas_pos_evidencia_revisores_readonly.php`;
  - `total_candidatos=1`;
  - candidato disponible `id_usuario=1`, nombre `soporte_sistema`;
  - el candidato `1` tambien es `creado_por=1`, por lo que requiere excepcion UAT si se usa como revisor.
- Autorizacion corregida requerida para UAT con mismo usuario:

```text
AUTORIZO REVISAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_REVISION_REAL id_usuario=1 id_evidencia_caja=1 decision=aprobada permitir_mismo_usuario=1
```

## Evidencia UAT - Revision aprobada evidencia reembolso caja POS

- Fecha: 2026-06-30
- Autorizacion recibida:

```text
AUTORIZO REVISAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_REVISION_REAL id_usuario=1 id_evidencia_caja=1 decision=aprobada permitir_mismo_usuario=1
```

- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencia_revision_apply_authorized.php --autorizar=VENTAS_POS_CAJA_EVIDENCIA_REVISION_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_evidencia_caja=1 --decision=aprobada --permitir_mismo_usuario=1
```

- Resultado:
  - `ok=true`;
  - evidencia `id_evidencia_caja=1`;
  - movimiento caja `20`;
  - decision `aprobada`;
  - `revisado_por=1`;
  - `requiere_accion=false`;
  - no movio dinero;
  - no modifico inventario;
  - no modifico importes ni turno.
- Auditoria posterior:
  - detalle evidencia `1`: `estatus=aprobada`, `fecha_revision=2026-06-30 08:42:25`;
  - movimiento caja `20`: `evidencia_estado=aprobada`, `fecha_actualizacion=2026-06-30 08:42:25`;
  - turno `9`, estado `aprobada`: `total_registros=1`, monto `295`;
  - turno `9`, estado `pendiente`: `total_registros=0`.
- Nota UAT:
  - se uso `permitir_mismo_usuario=1` porque el unico candidato con permiso era `id_usuario=1`, tambien creador de la evidencia;
  - en operacion real debe revisarla un usuario distinto o crearse permiso/rol fino de supervisor de caja.

## Evidencia tecnica - Bandeja visual evidencias de caja POS

- Fecha: 2026-06-30
- Objetivo:
  - exponer en el POS una consulta operativa de evidencias de caja;
  - mantener la primera version como read-only para no aprobar/rechazar desde UI sin permiso fino.
- Cambios:
  - vista `app/vistas/paginas/apps/erp/ventas/pos.php`;
  - JS `public/assets/js/custom/apps/erp/ventas/pos.js`;
  - version asset `20260630-evidencias1`.
- Alcance visual:
  - dentro del modal `Caja`;
  - filtro por estado: `pendiente`, `recibida`, `aprobada`, `rechazada`, `todas`;
  - consulta por almacen/caja/turno actuales;
  - resumen de registros y monto;
  - listado con referencia, tipo, categoria, tienda, turno, venta/devolucion, estado y monto;
  - detalle read-only por movimiento de caja.
- Endpoints usados:
  - `/ventas/caja_evidencias_pendientes_erp`;
  - `/ventas/caja_evidencias_detalle_erp`.
- Validaciones:
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: sin errores;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores.
- Restricciones:
  - no aprueba evidencia;
  - no rechaza evidencia;
  - no adjunta archivo;
  - no escribe BD;
  - no mueve dinero ni inventario.
- Pendiente:
  - validacion visual en navegador autenticado;
  - decidir permiso fino `ventas.caja_evidencias.revisar` o equivalente antes de exponer aprobacion/rechazo productivo.

## Evidencia tecnica - Permiso fino evidencias caja POS preparado

- Fecha: 2026-06-30
- Objetivo:
  - separar la revision de evidencias de caja del permiso generico `ventas.autorizar_excepcion_comercial`.
- Cambios:
  - `SeguridadEsquema::permisosBaseERP` incluye `ventas.caja_evidencias.revisar`;
  - roles base `direccion` y `administrador_erp` incluyen el permiso en codigo;
  - `VentasErp::revisarEvidenciaCajaPosReal` acepta permiso fino y mantiene compatibilidad temporal con `ventas.autorizar_excepcion_comercial`;
  - `Ventas::caja_evidencia_revisar_erp` delega la validacion fina al modelo;
  - script read-only `storage/uat/uat_ventas_pos_caja_evidencias_permiso_readonly.php`;
  - script autorizado `storage/uat/uat_ventas_pos_caja_evidencias_permiso_apply_authorized.php`;
  - solicitud `docs/erp_ventas_pos_caja_evidencias_permiso_solicitud_autorizacion.md`.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\SeguridadEsquema.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
  - scripts UAT de permiso: sin errores;
  - aplicador sin parametros: bloqueado, no escribio BD.
- Read-only actual:
  - `permiso_existe=false`;
  - faltan asignaciones a `direccion` y `administrador_erp`;
  - requiere autorizacion.
- Autorizacion requerida:

```text
AUTORIZO SEMBRAR PERMISO EVIDENCIAS CAJA POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIAS_PERMISO id_usuario=1 para UAT POS
```

## Evidencia UAT - Permiso fino evidencias caja POS sembrado

- Fecha: 2026-06-30
- Autorizacion recibida:

```text
AUTORIZO SEMBRAR PERMISO EVIDENCIAS CAJA POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIAS_PERMISO id_usuario=1 para UAT POS
```

- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencias_permiso_apply_authorized.php --autorizar=VENTAS_POS_CAJA_EVIDENCIAS_PERMISO --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1
```

- Resultado:
  - `ok=true`;
  - permiso `ventas.caja_evidencias.revisar`;
  - roles asignados: `direccion`, `administrador_erp`;
  - no aprobo evidencias;
  - no modifico caja;
  - no movio dinero;
  - no movio inventario.
- Auditoria posterior:
  - `permiso_existe=true`;
  - `id_permiso=300`;
  - `modulo=ventas`;
  - `accion=caja_evidencias_revisar`;
  - `estatus=1`;
  - `rol administrador_erp`: asignado;
  - `rol direccion`: asignado;
  - `faltantes=[]`;
  - `requiere_autorizacion=false`.
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_caja_evidencias_permiso_apply_authorized.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\modelos\SeguridadEsquema.php`: sin errores.
- Siguiente paso:
  - refrescar sesion/permisos antes de probar UI;
  - validar visualmente bandeja de evidencias en POS;
  - despues exponer aprobacion/rechazo en UI con este permiso fino.

## Evidencia tecnica - Validacion previsual bandeja evidencias caja POS

- Fecha: 2026-06-30
- Resultado:
  - no se pudo usar navegador integrado porque la sesion no expuso browser `iab`;
  - se valido la integracion por sintaxis y endpoints read-only.
- Validaciones:
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: sin errores;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`: sin errores;
  - `/ventas/caja_evidencias_pendientes_erp` via script UAT:
    - turno `9`;
    - estado `aprobada`;
    - `total_registros=1`;
    - monto `295`;
    - movimiento caja `20`;
    - folio venta `POS-20260630-000001`;
    - folio devolucion `DEV-20260630-000002`.
  - `/ventas/caja_evidencias_detalle_erp` via script UAT:
    - movimiento caja `20`;
    - `total_registros=1`;
    - evidencia `1`;
    - estatus `aprobada`;
    - revisado por `1`.
- UAT manual sugerida:
  1. Cerrar sesion y volver a entrar para refrescar permisos.
  2. Abrir `/ventas/pos`.
  3. Entrar al boton `Caja`.
  4. En `Evidencias de caja`, seleccionar estado `Aprobada`.
  5. Presionar `Consultar`.
  6. Confirmar que aparece referencia `DEV-20260630-000002`, monto `$295.00`, estado `aprobada`.
  7. Presionar `Detalle`.
  8. Confirmar evidencia `ticket_firmado`, descripcion `Comprobante UAT de reembolso de caja firmado por cliente`, revisado por `1`.
- Restriccion:
  - esta vista sigue siendo read-only; no aprueba, rechaza ni adjunta evidencia desde UI.

## Evidencia tecnica - Revision UI evidencias caja POS preparada

- Fecha: 2026-06-30
- Objetivo:
  - permitir que una evidencia en estado `recibida` pueda aprobarse o rechazarse desde el detalle UI;
  - usar el permiso fino `ventas.caja_evidencias.revisar` ya sembrado.
- Cambios:
  - JS `public/assets/js/custom/apps/erp/ventas/pos.js`;
  - version asset `20260630-evidencias2`.
- Comportamiento:
  - los botones `Aprobar` y `Rechazar` aparecen solo en detalle de evidencia con `estatus=recibida`;
  - aprobar pide confirmacion;
  - rechazar exige motivo;
  - usa endpoint POST `/ventas/caja_evidencia_revisar_erp`;
  - el backend valida sesion, CSRF, `ventas.operar` y permiso fino/compatibilidad en modelo;
  - despues de aprobar/rechazar refresca el detalle del movimiento.
- Validaciones:
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: sin errores;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
  - permiso `ventas.caja_evidencias.revisar`: existe, `id_permiso=300`, asignado a `direccion` y `administrador_erp`.
- Nota:
  - la evidencia UAT `1` ya esta `aprobada`, por lo que no mostrara botones de revision;
  - para UAT visual de aprobacion/rechazo desde UI se requiere crear otra evidencia en estado `recibida`.

## Evidencia tecnica - Ticket devolucion desde evidencia caja POS

- Fecha: 2026-06-30
- Objetivo:
  - conectar evidencia de reembolso de caja con ticket formal de devolucion;
  - permitir reimpresion read-only desde el detalle de evidencia.
- Cambios:
  - JS `public/assets/js/custom/apps/erp/ventas/pos.js`;
  - version asset `20260630-evidencias3`.
- Comportamiento:
  - si el detalle de evidencia trae `folio_devolucion`, muestra boton `Ticket devolucion`;
  - llama endpoint read-only `/ventas/pos_ticket_devolucion_erp`;
  - reutiliza el modal de ticket existente;
  - no reembolsa, no modifica caja y no mueve inventario.
- Validaciones:
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: sin errores;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`: sin errores;
  - script `storage\uat\uat_ventas_pos_ticket_devolucion_readonly.php --folio_devolucion=DEV-20260630-000002`:
    - `ok=true`;
    - `hallazgos=[]`;
    - folio devolucion `DEV-20260630-000002`;
    - venta `POS-20260630-000001`;
    - reembolso caja `295`;
    - movimiento caja `20`;
    - decision inventario `cuarentena`.

## Evidencia tecnica - Guardrail evidencia duplicada aprobada

- Fecha: 2026-06-30
- Objetivo:
  - evitar que un movimiento de caja con evidencia ya `aprobada` reciba otra evidencia accidental y regrese a estado `recibida`.
- Cambio:
  - `VentasErp::registrarEvidenciaCajaPosReal` bloquea nuevos registros si `erp_pos_movimientos_caja.evidencia_estado='aprobada'`.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - intento duplicado autorizado sobre movimiento `20`:
    - `ok=false`;
    - mensaje `El movimiento ya tiene evidencia aprobada; usa un flujo de correccion autorizado`;
    - no creo evidencia nueva.
  - lectura posterior:
    - movimiento `20` mantiene `evidencia_estado=aprobada`;
    - total evidencias del movimiento `20`: `1`;
    - evidencia `1` mantiene estatus `aprobada`.
- Pendiente futuro:
  - disenar flujo formal de correccion de evidencia aprobada con permiso superior, motivo obligatorio y auditoria separada.

## Evidencia tecnica - Readiness DDL correcciones evidencias caja POS

- Fecha: 2026-06-30
- Objetivo:
  - preparar estructura formal para corregir evidencias de caja ya aprobadas sin editar ni borrar historia.
- Cambios preparados:
  - `VentasErpEsquema::planActualizarCorreccionesEvidenciasCajaPos`;
  - `VentasErpEsquema::auditarCorreccionesEvidenciasCajaPos`;
  - endpoints:
    - `/ventas/esquema_auditar_correcciones_evidencias_caja_pos`;
    - `/ventas/esquema_actualizar_correcciones_evidencias_caja_pos`;
  - script read-only `storage/uat/uat_ventas_pos_caja_evidencias_correccion_schema_readonly.php`;
  - aplicador protegido `storage/uat/uat_ventas_pos_caja_evidencias_correccion_schema_apply_authorized.php`;
  - solicitud `docs/erp_ventas_pos_caja_evidencias_correccion_schema_solicitud_autorizacion.md`.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErpEsquema.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
  - scripts UAT de correccion schema: sin errores.
- Auditoria read-only:
  - `erp_pos_movimientos_caja_evidencias` existe;
  - `erp_pos_movimientos_caja_evidencias_correcciones` no existe;
  - plan genera `CREATE TABLE` sin ejecutar;
  - no escribio BD.
- Guardrail:
  - aplicador sin parametros bloqueado;
  - no aplico DDL.
- Autorizacion requerida:

```text
AUTORIZO APLICAR DDL CORRECCIONES EVIDENCIAS CAJA POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIAS_CORRECCION_DDL para UAT POS
```

## Evidencia UAT - DDL correcciones evidencias caja POS aplicado

- Fecha: 2026-06-30
- Autorizacion recibida:

```text
AUTORIZO APLICAR DDL CORRECCIONES EVIDENCIAS CAJA POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIAS_CORRECCION_DDL para UAT POS
```

- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencias_correccion_schema_apply_authorized.php --autorizar=VENTAS_POS_CAJA_EVIDENCIAS_CORRECCION_DDL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql
```

- Resultado:
  - `ok=true`;
  - tabla creada `erp_pos_movimientos_caja_evidencias_correcciones`;
  - indices creados:
    - `idx_caja_evidencia_corr_folio`;
    - `idx_caja_evidencia_corr_evidencia`;
    - `idx_caja_evidencia_corr_movimiento`;
    - `idx_caja_evidencia_corr_estado`.
  - no modifico evidencias existentes;
  - no modifico movimientos de caja;
  - no movio dinero;
  - no movio inventario.
- Auditoria posterior:
  - tabla `erp_pos_movimientos_caja_evidencias_correcciones` existe;
  - columnas e indices esperados existen;
  - plan posterior indica `La tabla ya existe`.
- Validacion de no impacto:
  - movimiento caja `20` sigue `evidencia_estado=aprobada`;
  - evidencia `1` sigue `estatus=aprobada`;
  - total evidencias movimiento `20`: `1`;
  - turno `9`, estado `aprobada`: `total_registros=1`, monto `295`.

## Evidencia tecnica - Solicitud correccion evidencia caja POS preparada

- Fecha: 2026-06-30
- Objetivo:
  - crear folio de correccion sobre evidencia aprobada sin editar evidencia historica.
- Cambios:
  - `VentasErp::solicitarCorreccionEvidenciaCajaPosReal`;
  - helper `VentasErp::generarFolioCorreccionEvidenciaCaja`;
  - endpoint `/ventas/caja_evidencia_correccion_solicitar_erp`;
  - script protegido `storage/uat/uat_ventas_pos_caja_evidencia_correccion_apply_authorized.php`.
- Reglas:
  - requiere evidencia `aprobada`;
  - requiere movimiento con `evidencia_estado=aprobada`;
  - requiere motivo obligatorio;
  - requiere permiso `ventas.caja_evidencias.revisar`;
  - bloquea si ya existe correccion abierta para la evidencia;
  - crea folio `COR-EVC-YYYYMMDD-######`;
  - no modifica evidencia original;
  - no modifica movimiento de caja;
  - no mueve dinero ni inventario.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_caja_evidencia_correccion_apply_authorized.php`: sin errores;
  - aplicador sin parametros: bloqueado, no escribio BD.
- Autorizacion requerida para UAT:

```text
AUTORIZO SOLICITAR CORRECCION EVIDENCIA CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_REAL id_usuario=1 id_evidencia_caja=1 tipo_correccion=reemplazo_evidencia motivo="UAT solicitud de correccion de evidencia aprobada"
```

## Evidencia UAT - Solicitud correccion evidencia caja POS creada

- Fecha: 2026-06-30
- Autorizacion recibida:

```text
AUTORIZO SOLICITAR CORRECCION EVIDENCIA CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_REAL id_usuario=1 id_evidencia_caja=1 tipo_correccion=reemplazo_evidencia motivo="UAT solicitud de correccion de evidencia aprobada"
```

- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencia_correccion_apply_authorized.php --autorizar=VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_evidencia_caja=1 --tipo_correccion=reemplazo_evidencia --motivo="UAT solicitud de correccion de evidencia aprobada"
```

- Resultado:
  - `ok=true`;
  - `id_correccion_evidencia_caja=1`;
  - folio `COR-EVC-20260630-000001`;
  - evidencia original `1`;
  - movimiento caja `20`;
  - estatus `solicitada`;
  - requiere resolucion posterior.
- Auditoria posterior:
  - lector read-only `storage/uat/uat_ventas_pos_caja_evidencia_correccion_readonly.php`;
  - folio encontrado `COR-EVC-20260630-000001`;
  - motivo `UAT solicitud de correccion de evidencia aprobada`;
  - evidencia original sigue `estatus=aprobada`;
  - movimiento caja sigue `evidencia_estado=aprobada`;
  - turno `9`, estado `aprobada`: `total_registros=1`, monto `295`.
- Impacto:
  - no modifico evidencia original;
  - no modifico movimiento de caja;
  - no movio dinero;
  - no movio inventario.

## Evidencia UAT - Reembolso mixto caja y saldo CRM registrado

- Fecha: 2026-07-07
- Autorizacion recibida:

```text
AUTORIZO REGISTRAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_EVIDENCIA_REAL id_usuario=1 id_movimiento_caja=41 tipo_evidencia=ticket_firmado referencia_externa=DEV-20260707-000001 descripcion="Comprobante UAT de reembolso mixto caja y saldo CRM firmado por cliente"
```

- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencia_apply_authorized.php --autorizar=VENTAS_POS_CAJA_EVIDENCIA_REAL --respaldo=UAT_POS_VIGENTE --respaldo_uat_vigente=1 --id_usuario=1 --id_movimiento_caja=41 --tipo_evidencia=ticket_firmado --referencia_externa=DEV-20260707-000001 --descripcion="Comprobante UAT de reembolso mixto caja y saldo CRM firmado por cliente"
```

- Resultado:
  - `ok=true`;
  - `id_evidencia_caja=3`;
  - movimiento caja `41`;
  - evidencia en estatus `recibida`;
  - requiere revision `true`;
  - referencia externa `DEV-20260707-000001`.
- Auditoria posterior:
  - detalle evidencia `id_evidencia_caja=3`: `total_registros=1`;
  - movimiento asociado: reembolso cliente por `$195.00`;
  - turno `TUR-20260707-002-002`;
  - venta `POS-20260707-000001`;
  - devolucion `DEV-20260707-000001`;
  - candidato revisor disponible: `id_usuario=1`, con regla UAT de `permitir_mismo_usuario=1` si se autoriza explicitamente.
- Impacto:
  - no movio dinero;
  - no movio saldo CRM;
  - no movio inventario;
  - queda pendiente revision/aprobacion de evidencia.

## Evidencia read-only - Inspeccion fisica devolucion mixta POS

- Fecha: 2026-07-07
- Consulta:
  - bandeja de devoluciones fisicas pendientes `ok=true`;
  - `total_registros=2`;
  - devolucion nueva `DEV-20260707-000001`;
  - `id_devolucion_detalle=3`;
  - SKU `TP-40352-500GR`;
  - cliente CRM `157`;
  - estado inspeccion `pendiente`.
- Dry-run:
  - primer intento sin `id_usuario`: bloqueado por `Usuario inspector obligatorio`;
  - segundo intento con `id_usuario=1`: `ok=true`;
  - decision `mantener_cuarentena`;
  - condicion `pendiente_revision`;
  - bloqueos `[]`;
  - avisos `[]`.
- Guardrails:
  - no escribe BD;
  - no crea kardex;
  - no reintegra inventario;
  - no liga garantia.
- Preparacion tecnica:
  - aplicador de inspeccion fisica acepta `UAT_POS_VIGENTE`;
  - sin token queda bloqueado correctamente.

## Evidencia UAT - Reembolso mixto caja aprobado

- Fecha: 2026-07-07
- Autorizacion recibida:

```text
AUTORIZO REVISAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_EVIDENCIA_REVISION_REAL id_usuario=1 id_evidencia_caja=3 decision=aprobada permitir_mismo_usuario=1
```

- Resultado:
  - `ok=true`;
  - evidencia `id_evidencia_caja=3`;
  - movimiento caja `41`;
  - decision `aprobada`;
  - revisado por `id_usuario=1`;
  - fecha revision `2026-07-07 23:39:10`.
- Auditoria posterior:
  - evidencia `3`: estatus `aprobada`;
  - movimiento caja `41`: `evidencia_estado=aprobada`;
  - devolucion `DEV-20260707-000001`: sin hallazgos en post-readonly;
  - componente caja `$195.00` y componente saldo CRM `$100.00` siguen aplicados correctamente;
  - no quedan pendientes de evidencia para el movimiento `41`.
- Nota:
  - la bandeja general conserva un pendiente historico no relacionado: movimiento caja `5`, gasto `GASTO-UAT-001` por `$50.00`.
- Impacto:
  - no movio dinero;
  - no movio saldo CRM;
  - no movio inventario.

## Evidencia UAT - Inspeccion fisica devolucion mixta registrada

- Fecha: 2026-07-07
- Autorizacion recibida:

```text
AUTORIZO REGISTRAR INSPECCION FISICA DEVOLUCION POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_DEVOLUCION_FISICA_REAL id_usuario=1 id_devolucion_detalle=3 decision_fisica=mantener_cuarentena condicion_producto=pendiente_revision motivo="UAT mantener en cuarentena sin mover inventario" diagnostico="Producto pendiente de inspeccion fisica"
```

- Resultado:
  - `ok=true`;
  - inspeccion `id_inspeccion_fisica=2`;
  - folio `IFD-20260707-000001`;
  - devolucion `DEV-20260707-000001`;
  - detalle `id_devolucion_detalle=3`;
  - decision fisica `mantener_cuarentena`;
  - inspeccion estado `cuarentena_confirmada`;
  - no crea kardex `true`;
  - no mueve inventario `true`;
  - no crea garantia `true`.
- Auditoria posterior:
  - bandeja de devoluciones fisicas pendientes baja de `2` a `1`;
  - `DEV-20260707-000001` ya no queda pendiente;
  - queda pendiente historico `DEV-20260630-000001`;
  - post-readonly reversa: `hallazgos=[]`;
  - detalle `3` muestra `id_inspeccion_fisica=2` y fecha `2026-07-07 23:47:00`;
  - dry-run posterior queda bloqueado correctamente porque la partida ya tiene `cuarentena_confirmada`.
- Impacto:
  - no movio dinero;
  - no movio saldo CRM;
  - no movio inventario;
  - no creo kardex.

## Evidencia read-only - Reversa POS con saldo CRM

- Fecha: 2026-07-07.
- Objetivo:
  - validar que una devolucion de venta pagada con caja + saldo CRM no reembolse desde caja dinero que no entro a caja.
- Script:
  - `storage/uat/uat_ventas_pos_reversa_saldo_crm_readiness_readonly.php`.
- Venta auditada:
  - `POS-20260707-000001`;
  - `id_venta=17`;
  - total `$295.00`;
  - caja pagada `$195.00`;
  - saldo CRM pagado `$100.00`.
- Resultado con `decision_financiera=reembolso_caja`:
  - `ok=false`;
  - bloquea reembolso completo desde caja;
  - diferencia no caja `$100.00`.
- Resultado con `decision_financiera=saldo_favor`:
  - `ok=true` en readiness;
  - avisa que falta movimiento CRM automatico.
- Impacto:
  - no creo devolucion;
  - no movio caja;
  - no movio inventario;
  - no movio saldo CRM.

## Evidencia tecnica - DDL reversas POS con saldo CRM preparado

- Fecha: 2026-07-07.
- Objetivo:
  - preparar estructura para que una devolucion pueda separar caja, saldo CRM y saldo favor CRM.
- Cambios:
  - `VentasErpEsquema::planActualizarReversasSaldoCrmPos`;
  - `VentasErpEsquema::auditarReversasSaldoCrmPos`;
  - `storage/uat/uat_ventas_pos_reversa_saldo_crm_schema_readonly.php`;
  - `storage/uat/uat_ventas_pos_reversa_saldo_crm_schema_apply_authorized.php`.
- Estructura propuesta:
  - columnas resumen en `erp_ventas_devoluciones`;
  - tabla `erp_ventas_devoluciones_finanzas` con componente financiero por reversa.
- Read-only:
  - `ok=true`;
  - detecta tabla financiera pendiente;
  - detecta columnas resumen pendientes;
  - genera SQL sin ejecutar.
- Apply sin token:
  - `ok=false`;
  - bloqueado correctamente;
  - no escribio BD.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErpEsquema.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_reversa_saldo_crm_schema_readonly.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_reversa_saldo_crm_schema_apply_authorized.php`: sin errores.
- Impacto:
  - no aplico DDL;
  - no creo devoluciones;
  - no movio caja;
  - no movio CRM;
  - no movio inventario.

## Evidencia UAT - DDL reversas POS con saldo CRM aplicado

- Fecha: 2026-07-07.
- Autorizacion recibida:

```text
AUTORIZO APLICAR DDL REVERSAS POS CON SALDO CRM usando respaldo UAT POS vigente con token VENTAS_POS_REVERSA_SALDO_CRM_DDL para UAT POS/CRM
```

- Primer intento:
  - `ok=false`;
  - MySQL no estaba aceptando conexion;
  - no ejecuto DDL;
  - no creo tablas ni columnas.
- Accion operativa:
  - se levanto `mysqld` con XAMPP;
  - la conexion de la app volvio a ver tablas ERP/CRM.
- Segundo intento:
  - `ok=true`;
  - columnas agregadas en `erp_ventas_devoluciones`:
    - `id_cliente_crm`;
    - `monto_reintegro_saldo_crm`;
    - `monto_no_caja`;
  - indice agregado:
    - `idx_ventas_devolucion_cliente_crm`;
  - tabla creada:
    - `erp_ventas_devoluciones_finanzas`.
- Auditoria posterior:
  - tablas requeridas `existe=true`;
  - columnas requeridas `existe=true`;
  - indices requeridos `existe=true`.
- Readiness financiero posterior:
  - `reembolso_caja` completo contra `POS-20260707-000001`: `ok=false`;
  - bloquea por exceder caja disponible `$100.00`;
  - `saldo_favor`: `ok=true` read-only, con aviso de que falta ruta real ligada a CRM.
- Impacto:
  - aplico solo DDL;
  - no creo devoluciones reales;
  - no registro reembolsos;
  - no movio saldo CRM;
  - no movio caja;
  - no movio inventario.

## Evidencia tecnica - Modelo real reversas POS con saldo CRM preparado

- Fecha: 2026-07-07.
- Autorizacion recibida:

```text
AUTORIZO PREPARAR MODELO REAL REVERSAS POS CON SALDO CRM usando respaldo UAT POS vigente con token VENTAS_POS_REVERSA_SALDO_CRM_MODELO para UAT POS/CRM
```

- Cambios:
  - `VentasErp::confirmarReversaPosReal` soporta `mixta_saldo_crm` y `reintegro_saldo_crm`;
  - calcula pagos originales por caja y saldo CRM;
  - bloquea reembolso de caja por encima de caja pagada disponible;
  - registra componentes en `erp_ventas_devoluciones_finanzas`;
  - reintegra saldo CRM como movimiento `abono` cuando se ejecute la ruta real autorizada;
  - agrega eventos de venta y CRM para reintegro;
  - recalcula componentes dentro de transaccion despues de bloquear la venta;
  - script real protegido `storage/uat/uat_ventas_pos_reversa_saldo_crm_apply_authorized.php`;
  - post-readonly ampliado para componentes financieros y movimientos CRM.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_reversa_saldo_crm_apply_authorized.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_reversa_post_readonly.php`: sin errores;
  - aplicador real sin token: bloqueado, no escribio BD.
- Readiness:
  - `mixta_saldo_crm` para `POS-20260707-000001`: `ok=true`;
  - detalle correcto `id_venta_detalle=18`;
  - caja propuesta `$195.00`;
  - saldo CRM propuesto `$100.00`;
  - `reintegro_saldo_crm` completo: bloqueado porque excede saldo CRM pagado por `$195.00`.
- Impacto:
  - no ejecuto devolucion real;
  - no registro reembolso;
  - no movio caja;
  - no movio CRM;
  - no movio inventario.

## Evidencia UAT - Reversa POS saldo CRM bloqueada por falta de turno

- Fecha: 2026-07-07.
- Autorizacion recibida:

```text
AUTORIZO EJECUTAR REVERSA POS UAT REAL CON SALDO CRM usando respaldo UAT POS vigente con token VENTAS_POS_REVERSA_SALDO_CRM_REAL id_usuario=1 folio=POS-20260707-000001 id_venta_detalle=18 cantidad=1 tipo=devolucion decision_inventario=cuarentena decision_financiera=mixta_saldo_crm motivo="UAT devolucion mixta caja y saldo CRM"
```

- Readiness previo:
  - `ok=true`;
  - caja propuesta `$195.00`;
  - saldo CRM propuesto `$100.00`;
  - venta `POS-20260707-000001`;
  - detalle `18`.
- Ejecucion real:
  - `ok=false`;
  - bloqueo `turno_caja_reembolso_pendiente`;
  - venta requiere `id_almacen=5`, `id_caja=2`;
  - no habia turno abierto.
- Auditoria posterior:
  - no se encontro devolucion/cancelacion para `POS-20260707-000001`;
  - no se crearon componentes financieros;
  - la venta sigue disponible para reversa.
- Ajuste tecnico:
  - se corrigio el `siguiente_paso` del aplicador para que un `warning` no sugiera validar una reversa inexistente.
- Impacto:
  - no creo devolucion;
  - no movio caja;
  - no movio saldo CRM;
  - no movio inventario.

## Evidencia UAT - Reversa POS real mixta caja y saldo CRM

- Fecha: 2026-07-07.
- Autorizaciones recibidas:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT reversa mixta saldo CRM"

AUTORIZO EJECUTAR REVERSA POS UAT REAL CON SALDO CRM usando respaldo UAT POS vigente con token VENTAS_POS_REVERSA_SALDO_CRM_REAL id_usuario=1 folio=POS-20260707-000001 id_venta_detalle=18 cantidad=1 tipo=devolucion decision_inventario=cuarentena decision_financiera=mixta_saldo_crm motivo="UAT devolucion mixta caja y saldo CRM"

AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=305 observaciones="Cierre UAT reversa mixta saldo CRM"
```

- Apertura:
  - turno `TUR-20260707-002-002`;
  - `id_turno_caja=19`;
  - caja `2`;
  - almacen `5`;
  - monto inicial `$500.00`;
  - movimiento caja apertura `40`.
- Reversa:
  - `ok=true`;
  - devolucion `DEV-20260707-000001`;
  - `id_devolucion=3`;
  - venta original `POS-20260707-000001`;
  - `id_venta=17`;
  - detalle `18`;
  - estatus venta `devuelta`;
  - decision inventario `cuarentena`;
  - sin reintegro inventario;
  - inspeccion fisica pendiente.
- Finanzas:
  - decision `mixta_saldo_crm`;
  - reembolso caja `$195.00`;
  - reintegro saldo CRM `$100.00`;
  - no caja `$100.00`;
  - componente `DFIN-20260707-000001`: `reembolso_caja`, movimiento caja `41`;
  - componente `DFIN-20260707-000002`: `reintegro_saldo_crm`, movimiento CRM `3`;
  - movimiento CRM `CRM-SAL-20260707-000002`, naturaleza `abono`;
  - saldo CRM cliente `157`: `$0.00 -> $100.00`.
- Caja:
  - movimiento caja reembolso `41`;
  - pago reembolso `id_venta_pago=22`;
  - monto esperado turno `500 - 195 = 305`;
  - cierre contado `$305.00`;
  - diferencia `$0.00`.
- Post-readonly:
  - reversa `ok=true`;
  - hallazgos `[]`;
  - turno post-cierre `ok=true`;
  - hallazgos `[]`;
  - no quedo turno abierto.
- Readiness posterior:
  - segunda reversa queda bloqueada porque la venta ya esta `devuelta`;
  - caja disponible para reembolso queda `$0.00`.
- Impacto:
  - caja movida solo por `$195.00`;
  - saldo CRM reintegrado solo por `$100.00`;
  - inventario no se reintegro por cuarentena;
  - queda pendiente evidencia de reembolso de caja y flujo de inspeccion fisica.

## Evidencia tecnica - Ticket devolucion mixta y guardrail evidencia caja

- Fecha: 2026-07-07.
- Objetivo:
  - hacer visible en el ticket de devolucion la separacion caja/saldo CRM;
  - preparar el registro de evidencia caja con respaldo UAT vigente sin ejecutarlo.
- Cambios:
  - `VentasErp::ticketDevolucionFormalReadOnly` ahora carga `erp_ventas_devoluciones_finanzas`;
  - `VentasErp::formatearTicketDevolucion` imprime:
    - `Reint saldo cliente`;
    - `No caja`;
    - componentes `Caja` y `Saldo cliente`;
  - `storage/uat/uat_ventas_pos_caja_evidencia_apply_authorized.php` acepta `--respaldo=UAT_POS_VIGENTE --respaldo_uat_vigente=1`.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_caja_evidencia_apply_authorized.php`: sin errores;
  - ticket `DEV-20260707-000001`: `ok=true`, hallazgos `[]`;
  - ticket muestra caja `$195.00` y saldo cliente `$100.00`;
  - aplicador evidencia sin token: bloqueado correctamente.
- Bandeja evidencias:
  - movimiento caja `41` sigue pendiente;
  - sin evidencias registradas aun.
- Impacto:
  - no registro evidencia;
  - no movio caja;
  - no movio saldo CRM;
  - no movio inventario.

## Evidencia tecnica - Ticket/corte con saldo cliente sin caja

- Fecha: 2026-07-07
- Objetivo:
  - evitar que el pago virtual `saldo_crm` se interprete como efectivo o movimiento de caja en ticket, detalle y corte.
- Cambios:
  - `VentasErp::etiquetaPagoPos`;
  - ticket formal muestra `Saldo cliente no caja`;
  - corte formal separa `Pagos sin caja`;
  - detalle web de venta muestra `Saldo cliente` y `No entra a caja`;
  - corte embebido POS muestra `Saldo cliente` y `Sin caja`.
  - arqueo Caja/Turnos cambia `Vales/saldo` por `Vales/otros` y aclara que saldo cliente CRM no se captura en arqueo.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: sin errores;
  - `node --check public\assets\js\custom\apps\erp\ventas\venta_detalle.js`: sin errores;
  - `node --check public\assets\js\custom\apps\erp\ventas\caja_turnos.js`: sin errores.
- Impacto:
  - no escribe BD;
  - no cambia importes;
  - no cambia kardex;
  - mejora lectura operativa de arqueo y ticket.

## Evidencia UAT - Venta POS real con saldo cliente CRM

- Fecha: 2026-07-07
- Autorizacion recibida:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS saldo CRM"

AUTORIZO EJECUTAR VENTA POS UAT REAL CON SALDO CRM usando respaldo UAT POS vigente con token VENTAS_POS_SALDO_CRM_REAL id_usuario=1 id_cliente_crm=157 id_sku=1760 cantidad=1 precio=295 monto_saldo_crm=100 pago_caja=195

AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=695 observaciones="Cierre UAT POS venta con saldo CRM"
```

- Preflight apertura:
  - `puede_abrir_turno=true`;
  - almacen `5`;
  - caja `2`;
  - turno abierto previo: ninguno.
- Turno abierto:
  - folio `TUR-20260707-002-001`;
  - `id_turno_caja=18`;
  - movimiento inicial caja `38`;
  - monto inicial `$500.00`.
- Readiness venta:
  - cliente CRM `157`, `Luis angel Marquez Sabino`;
  - saldo disponible previo `$100.00`;
  - total venta `$295.00`;
  - pago caja estimado `$195.00`;
  - bloqueos `[]`.
- Venta real:
  - folio `POS-20260707-000001`;
  - `id_venta=17`;
  - total `$295.00`;
  - pagado `$295.00`;
  - saldo venta `$0.00`;
  - pago caja `id_venta_pago=20`, movimiento caja `39`, monto `$195.00`;
  - pago saldo CRM `id_venta_pago=21`, sin movimiento de caja, monto `$100.00`;
  - movimiento CRM `CRM-SAL-20260707-000001`;
  - saldo CRM anterior `$100.00`;
  - saldo CRM resultante `$0.00`;
  - kardex inventario `id_movimiento_inventario=82`;
  - existencia `34`: `1 -> 0`;
  - rollback `false`.
- Auditoria post-venta:
  - pagos total `$295.00`;
  - saldo CRM pagado `$100.00`;
  - caja pagada `$195.00`;
  - movimientos caja total `$195.00`;
  - movimientos saldo CRM total `$100.00`;
  - trazas inventario `1`;
  - eventos venta `1`;
  - hallazgos `[]`.
- Cierre turno:
  - folio `TUR-20260707-002-001`;
  - monto esperado `$695.00`;
  - monto contado `$695.00`;
  - diferencia `$0.00`;
  - ventas `1`;
  - total vendido `$295.00`;
  - pagos por metodo: efectivo `$195.00`, saldo CRM `$100.00`;
  - movimientos de caja: apertura `$500.00`, venta POS `$195.00`.
- Post-cierre:
  - estatus `cerrado`;
  - fecha cierre `2026-07-07 09:05:01`;
  - hallazgos `[]`.
- Ticket formal:
  - `29` lineas;
  - muestra `Efectivo pago $195.00`;
  - muestra `Saldo cliente no caja $100.00`;
  - hallazgos `[]`.
- Saldo CRM cliente:
  - cuenta MXN activa `id_cliente_saldo_cuenta=1`;
  - saldo disponible final `$0.00`;
  - movimiento de cargo `CRM-SAL-20260707-000001` aplicado contra `POS-20260707-000001`.
- Impacto:
  - venta, caja, inventario y saldo CRM quedan trazados;
  - saldo CRM no genero movimiento de caja;
  - cierre de caja usa `500 + 195 = 695`;
  - no uso recompensas.

## Evidencia tecnica - Verificadores ticket/corte saldo cliente

- Fecha: 2026-07-07
- Objetivo:
  - convertir la validacion visual de saldo cliente en checks read-only repetibles.
- Cambios:
  - `storage/uat/uat_ventas_pos_ticket_formal_readonly.php`:
    - agrega `--compact=1`;
    - agrega `--esperar_saldo_crm=1`;
    - valida `Saldo cliente no caja`.
  - `storage/uat/uat_ventas_pos_corte_turno_readonly.php`:
    - agrega `--compact=1`;
    - agrega `--esperar_saldo_crm=1`;
    - valida `Pagos sin caja`;
    - valida `Saldo cliente no caja`.
  - `VentasErp::formatearCorteTurno`:
    - ajusta ancho de etiqueta para no cortar `Saldo cliente no caja`.
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_ticket_formal_readonly.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_corte_turno_readonly.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - ticket `POS-20260707-000001` con `--esperar_saldo_crm=1`: `ok=true`, hallazgos `[]`;
  - corte `TUR-20260707-002-001` con `--esperar_saldo_crm=1`: `ok=true`, hallazgos `[]`.
- Impacto:
  - no escribe BD;
  - no cambia importes;
  - reduce riesgo de que una regresion visual lleve al cajero a contar saldo cliente como caja.

## Evidencia tecnica - Indicador saldo cliente CRM en POS

- Fecha: 2026-07-07
- Objetivo:
  - mostrar saldo CRM disponible al cajero antes de agregar el pago `Saldo cliente`.
- Cambios:
  - `Ventas::cliente_saldo_crm_readonly_erp`;
  - `VentasErp::clienteSaldoCrmReadOnly`;
  - `storage/uat/uat_ventas_pos_cliente_saldo_crm_readonly.php`;
  - indicador `#pos_cliente_saldo_crm` en `/ventas/pos`;
  - `pos.js` consulta saldo al seleccionar/activar cliente CRM;
  - boton `Saldo cliente` usa como maximo el saldo disponible conocido y bloquea si es `$0.00`.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`: sin errores;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_cliente_saldo_crm_readonly.php`: sin errores;
  - consulta read-only para cliente `157`: saldo disponible `$0.00`, movimientos recientes `2`, contrato sin escritura.
- Impacto:
  - no escribe BD;
  - no cambia el cobro real;
  - reduce errores de cajero al intentar aplicar saldo inexistente o mayor al disponible.

## Evidencia UAT visual - Saldo cliente CRM en POS

- Fecha: 2026-07-07
- Script:
  - `storage/uat/uat_ventas_pos_saldo_crm_playwright_uat.js`.
- Objetivo:
  - validar en navegador que POS muestra saldo cliente y bloquea uso si el saldo disponible es `$0.00`.
- Caso:
  - URL `http://dashboard.com.local/ventas/pos`;
  - cliente CRM `Luis angel Marquez Sabino`;
  - identificador `2871085474`;
  - saldo CRM disponible `$0.00`.
- Resultado:
  - `ok=true`;
  - login `ok`;
  - entro a POS;
  - indicador visible `Sin saldo cliente disponible`;
  - saldo `$0.00` visible;
  - boton `Saldo cliente` deshabilitado;
  - no agrego pago saldo cliente;
  - no cobro;
  - no abrio turno;
  - no cerro turno;
  - no movio caja;
  - no movio inventario.
- Evidencia visual:
  - `public/storage/uat/pos_saldo_cliente_uat.png`.
- Observacion tecnica:
  - consola reporta recursos externos bloqueados con `ERR_NETWORK_ACCESS_DENIED`;
  - no bloquean el flujo POS ni los checks de saldo cliente.
- Validaciones:
  - `node --check storage\uat\uat_ventas_pos_saldo_crm_playwright_uat.js`: sin errores.

## Evidencia tecnica - Preparacion apply saldo CRM en POS

- Fecha: 2026-07-06
- Autorizacion recibida:

```text
AUTORIZO PREPARAR APPLY REAL USO SALDO CRM EN POS usando respaldo UAT POS vigente con token VENTAS_POS_SALDO_CRM_APPLY_PREP para UAT POS
```

- Cambios:
  - nuevo script `storage/uat/uat_ventas_pos_saldo_crm_apply_prep_authorized.php`;
  - `VentasErp::prevalidarPagosPos` acepta `saldo_crm` como pago virtual sin caja;
  - `VentasErp::registrarPagosPosReal` bloquea `saldo_crm` en la ruta real actual para evitar movimiento de caja incorrecto.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_saldo_crm_apply_prep_authorized.php`: sin errores;
  - sin token: bloqueado, no escribio BD.
- Comando de preparacion ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_saldo_crm_apply_prep_authorized.php --autorizar=VENTAS_POS_SALDO_CRM_APPLY_PREP --respaldo="UAT POS vigente" --id_usuario=1 --id_cliente_crm=157 --monto_saldo_crm=100 --id_sku=1760 --cantidad=1 --precio=295 --pago_caja=195 --compact=1
```

- Resultado:
  - `read_only=true`;
  - cliente `Luis angel Marquez Sabino`;
  - saldo disponible `100`;
  - monto saldo CRM `100`;
  - saldo resultante estimado `0`;
  - total venta `295`;
  - caja estimada `195`;
  - bloqueo restante: no hay turno abierto para la caja asignada.
- Impacto:
  - no creo venta;
  - no desconto saldo CRM;
  - no movio caja;
  - no movio inventario.
- Siguiente paso bloqueado por autorizacion fuerte y preparacion operativa:
  - abrir turno y cargar stock si hace falta;
  - ejecutar venta real con token `VENTAS_POS_SALDO_CRM_REAL`.

## Evidencia tecnica - Ruta real saldo CRM en POS preparada

- Fecha: 2026-07-07
- Objetivo:
  - dejar lista la ejecucion real de venta POS con pago mixto caja + saldo CRM;
  - evitar que saldo CRM se registre como efectivo o ingreso de caja.
- Cambios:
  - `VentasErp::registrarPagosPosReal` acepta pagos normales y saldo CRM;
  - `VentasErp::registrarPagoSaldoCrmPosReal` descuenta saldo CRM dentro de la transaccion de venta;
  - `VentasErp::registrarEventosSaldoCrmPosReal` deja evento en ventas y CRM;
  - `VentasErp::generarFolioSaldoCrmPosReal` crea folio `CRM-SAL-YYYYMMDD-######`;
  - nuevo script `storage/uat/uat_ventas_pos_saldo_crm_apply_authorized.php`.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_saldo_crm_apply_authorized.php`: sin errores;
  - script real sin token: bloqueado, no escribio BD.
- Guardrails:
  - requiere token `VENTAS_POS_SALDO_CRM_REAL`;
  - requiere respaldo UAT vigente;
  - requiere cliente CRM;
  - requiere cuenta CRM MXN activa;
  - saldo CRM no genera cambio;
  - `id_movimiento_caja=NULL` para pago saldo CRM;
  - `monto_esperado` del turno solo suma pagos que mueven caja.
- Verificacion read-only posterior:
  - no pudo consultar BD porque la conexion esta no disponible;
  - `Get-Process` no mostro proceso MySQL activo.
- Impacto:
  - no se ejecuto venta real;
  - no se desconto saldo CRM;
  - no se movio caja;
  - no se movio inventario.

## Evidencia tecnica - Verificador post venta saldo CRM

- Fecha: 2026-07-07
- Script:
  - `storage/uat/uat_ventas_pos_saldo_crm_post_readonly.php`.
- Objetivo:
  - revisar una venta POS ya ejecutada con saldo CRM;
  - validar pago POS, caja, ledger CRM, inventario y eventos sin escribir BD.
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_saldo_crm_post_readonly.php`: sin errores;
  - sin `--folio` o `--id_venta`: bloquea en modo read-only.
- Hallazgos que detecta:
  - venta sin pago `saldo_crm`;
  - pago `saldo_crm` con movimiento de caja;
  - monto saldo CRM pagado distinto al movimiento CRM;
  - monto caja pagado distinto a movimientos de caja;
  - movimiento CRM que no sea cargo.
- Impacto:
  - no corrige diferencias;
  - no mueve caja;
  - no mueve inventario;
  - no modifica saldo CRM.

## Evidencia tecnica - Runbook UAT venta con saldo CRM

- Fecha: 2026-07-07
- Script:
  - `storage/uat/uat_ventas_pos_saldo_crm_runbook_readonly.php`.
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_saldo_crm_runbook_readonly.php`: sin errores;
  - ejecucion `--compact=1`: `ok=true`, read-only.
- Resultado:
  - total venta `295`;
  - saldo CRM a usar `100`;
  - pago caja `195`;
  - monto inicial turno `500`;
  - monto contado esperado cierre `695`.
- Decision contable:
  - caja no debe esperar `795`;
  - saldo CRM no representa efectivo recibido en turno;
  - cierre correcto: `500 + 195 = 695`.
- Autorizaciones generadas:
  - abrir turno POS UAT saldo CRM;
  - venta POS real con token `VENTAS_POS_SALDO_CRM_REAL`;
  - cierre turno con `monto_contado=695`;
  - carga de stock opcional solo si readiness marca insuficiencia.
- Impacto:
  - no abrio turno;
  - no cargo stock;
  - no vendio;
  - no desconto saldo CRM.

## Evidencia tecnica - UI pago saldo cliente POS

- Fecha: 2026-07-07
- Cambios:
  - boton rapido `Saldo cliente` en POS;
  - pago `saldo_crm` renderizado como virtual sin selector de metodo de caja;
  - validacion UI de cliente CRM obligatorio;
  - mensaje de cobro separa caja y saldo cliente;
  - bloqueo UI si saldo cliente intenta generar cambio.
- Backend:
  - `VentasErp::prevalidarPagosPos` bloquea saldo CRM mayor al pendiente de cobro.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`: sin errores;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: sin errores.
- Readiness:
  - caja `195` + saldo CRM `100`: bloqueos solo por falta de turno abierto;
  - caja `250` + saldo CRM `100`: bloquea `Pago 2: Saldo CRM no puede generar cambio`.
- Impacto:
  - no ejecuto venta;
  - no desconto saldo;
  - no movio caja ni inventario.

## Evidencia tecnica - UX pedidos/apartados con partidas explicitas

- Fecha: 2026-07-06
- Objetivo:
  - evitar que Pedidos/Apartados envie una partida capturada pero no visible en tabla;
  - hacer mas clara la captura multipartida antes de simular o crear apartado real.
- Cambios:
  - resumen bajo tabla de partidas;
  - validacion local antes de `pedido_reserva_dryrun_erp`;
  - validacion local antes de `pedido_guardar_erp`;
  - `items` se arma exclusivamente desde partidas visibles en tabla.
- Validaciones:
  - `node --check public/assets/js/custom/apps/erp/ventas/pedidos.js`: sin errores;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pedidos.php`: sin errores.
- Impacto:
  - no escribio BD;
  - no cambio reglas de reserva ni cobro en backend;
  - reduce errores operativos en pedidos/apartados multipartida.

## Evidencia UAT bloqueada - Preparacion apartado multipartida

- Fecha: 2026-07-06
- Autorizacion recibida:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS apartado multipartida"

AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=2 referencia=INV-INICIAL-POS-UAT-MULTIPARTIDA-1760
```

- Resultado:
  - no se abrio turno;
  - no se cargo stock;
  - ambos scripts fallaron antes de escribir porque la conexion PDO fue `null`.
- Diagnostico:
  - `mysqld` no quedo activo;
  - arranque por consola reporto `InnoDB: Missing MLOG_CHECKPOINT`;
  - MariaDB aborto con `Unknown/unsupported storage engine: InnoDB`.
- Preflight read-only de recuperacion:
  - `storage/uat/uat_mysql_pos_recovery_preflight_readonly.php --compact=1`;
  - `ok=true`;
  - respaldo disponible: `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`;
  - tamano: `27216189` bytes;
  - bloqueos: `[]`.
- Impacto:
  - no hubo escritura BD;
  - la UAT real multipartida queda bloqueada hasta recuperar MySQL;
  - la autorizacion de apertura/stock debera repetirse despues de recuperar el motor.

## Evidencia UAT - Recuperacion MySQL y preparacion apartado multipartida

- Fecha: 2026-07-06
- Autorizacion recibida:

```text
AUTORIZO RECUPERAR MYSQL UAT POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token MYSQL_UAT_POS_RECOVERY permitiendo respaldo previo de C:\xampp\mysql\data, arranque controlado de MariaDB, diagnostico InnoDB y restauracion/importacion solo si es necesario para continuar UAT POS
```

- Recuperacion ejecutada:
  - fase `diagnostico` con `storage/uat/uat_mysql_pos_recovery_apply_authorized.php`;
  - `mysqladmin ping`: `mysqld is alive`;
  - no fue necesario importar SQL;
  - no fue necesario modificar `my.ini`;
  - no fue necesario copiar/restaurar carpeta `data`.
- Preparacion UAT retomada con autorizacion previa no consumida por falla de conexion:
  - apertura turno POS UAT;
  - carga stock UAT SKU `1760`.
- Resultado apertura:
  - `ok=true`;
  - `id_turno_caja=16`;
  - folio `TUR-20260706-002-001`;
  - `id_movimiento_caja=33`;
  - almacen `5`;
  - caja `2`;
  - monto inicial `500`.
- Resultado stock:
  - `ok=true`;
  - almacen `5`;
  - SKU `1760`;
  - cantidad `2`;
  - referencia `INV-INICIAL-POS-UAT-MULTIPARTIDA-1760`;
  - inventario inicial aplicado;
  - movimientos `1`.
- Readiness posterior:
  - `ok=true`;
  - turno abierto `true`;
  - stock disponible total `2`;
  - stock suficiente `true`;
  - reservas propuestas `2`;
  - anticipo minimo `118`;
  - pagado simulado `120`;
  - saldo estimado `470`;
  - bloqueos `[]`;
  - hallazgos `[]`.
- Impacto:
  - MySQL quedo disponible para UAT;
  - ambiente listo para crear apartado multipartida real;
  - aun no se creo apartado, abono, entrega ni cierre.

## Evidencia UAT - Apartado multipartida real creado

- Fecha: 2026-07-06
- Autorizacion recibida:

```text
AUTORIZO CREAR APARTADO MULTIPARTIDA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDO_REAL id_usuario=1 telefono=3312345678 cliente="Cliente UAT Apartado Multipartida POS" anticipo=120 item=1760,1,295 item=1760,1,295
```

- Resultado:
  - `ok=true`;
  - folio `APT-20260706-000001`;
  - `id_venta=15`;
  - estatus `pendiente_pago`;
  - partidas enviadas `2`;
  - total `590`;
  - anticipo/pagado `120`;
  - saldo `470`.
- Reservas generadas:
  - `RES-PED-20260706-000001`, `id_reserva_inventario=3`, `id_venta_detalle=15`, cantidad `1`;
  - `RES-PED-20260706-000002`, `id_reserva_inventario=4`, `id_venta_detalle=16`, cantidad `1`.
- Pago generado:
  - `id_venta_pago=17`;
  - `id_movimiento_caja=34`;
  - tipo `anticipo`;
  - metodo `Efectivo`;
  - monto `120`.
- Verificacion post-UAT read-only:
  - `storage/uat/uat_ventas_pos_pedido_multipartida_post_readonly.php --folio=APT-20260706-000001 --compact=1 --esperar_partidas=2 --esperar_total=590 --esperar_pagado=120 --esperar_saldo=470`;
  - estatus `pendiente_pago`;
  - partidas `2`;
  - cantidad detalle `2`;
  - total detalle `590`;
  - reservas `2`;
  - cantidad reservada `2`;
  - cantidad consumida `0`;
  - pagos `1`;
  - pagos total `120`;
  - trazas/kardex `0`;
  - hallazgos `[]`.
- Readiness posterior:
  - stock disponible `0`;
  - bloqueo esperado `Stock insuficiente para multipartida`;
  - motivo: las dos piezas cargadas ya quedaron reservadas por el apartado.
- Impacto:
  - se creo apartado real;
  - se registro anticipo en caja;
  - se reservaron dos unidades;
  - no se genero kardex de salida todavia porque el apartado no esta entregado.

## Evidencia UAT - Abono/liquidacion apartado multipartida

- Fecha: 2026-07-06
- Autorizacion recibida:

```text
AUTORIZO REGISTRAR ABONO APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_APARTADO_ABONO_REAL id_usuario=1 folio=APT-20260706-000001 monto=470 referencia=UAT-LIQ-APT-20260706-000001
```

- Resultado:
  - `ok=true`;
  - folio `APT-20260706-000001`;
  - `id_venta=15`;
  - estatus `pagado`;
  - pagado total `590`;
  - saldo total `0`.
- Pago generado:
  - `id_venta_pago=18`;
  - `id_movimiento_caja=35`;
  - tipo `liquidacion`;
  - metodo `Efectivo`;
  - monto aplicado `470`.
- Verificacion post-UAT read-only:
  - partidas `2`;
  - cantidad detalle `2`;
  - total detalle `590`;
  - reservas `2`;
  - cantidad reservada `2`;
  - cantidad consumida `0`;
  - pagos `2`;
  - pagos total `590`;
  - trazas/kardex `0`;
  - hallazgos `[]`.
- Turno:
  - folio `TUR-20260706-002-001`;
  - estatus `abierto`;
  - monto inicial `500`;
  - monto esperado `1090`;
  - ventas `590`;
  - pagos `590`;
  - movimientos caja `1090`.
- Impacto:
  - apartado liquidado y listo para entrega;
  - no se genero kardex de salida todavia;
  - no se cerro turno.

## Evidencia UAT - Entrega apartado multipartida

- Fecha: 2026-07-06
- Autorizacion recibida:

```text
AUTORIZO ENTREGAR APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDO_ENTREGA_REAL id_usuario=1 folio=APT-20260706-000001
```

- Resultado:
  - `ok=true`;
  - folio `APT-20260706-000001`;
  - mensaje `Pedido/apartado entregado`.
- Reservas consumidas:
  - `id_reserva_inventario=3`, folio `RES-PED-20260706-000001`, `id_movimiento_inventario=79`, cantidad `1`;
  - `id_reserva_inventario=4`, folio `RES-PED-20260706-000002`, `id_movimiento_inventario=80`, cantidad `1`.
- Verificacion post-UAT read-only:
  - estatus `entregado`;
  - tipo documento `apartado`;
  - `id_turno_caja=16`;
  - total `590`;
  - pagado total `590`;
  - saldo total `0`;
  - partidas `2`;
  - cantidad detalle `2`;
  - reservas `2`;
  - cantidad reservada `2`;
  - cantidad consumida `2`;
  - pagos `2`;
  - trazas/kardex `2`;
  - hallazgos `[]`.
- Turno:
  - `TUR-20260706-002-001` sigue abierto;
  - monto esperado `1090`;
  - movimientos caja `1090`;
  - ventas `590`;
  - pagos `590`.
- Impacto:
  - apartado multipartida completado hasta entrega;
  - inventario descontado con kardex y trazabilidad;
  - queda pendiente cerrar turno con monto contado esperado `1090`.

## Evidencia UAT - Cierre turno apartado multipartida

- Fecha: 2026-07-06
- Autorizacion recibida:

```text
AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=1090 observaciones="Cierre UAT POS apartado multipartida APT-20260706-000001"
```

- Resultado:
  - `ok=true`;
  - `id_turno_caja=16`;
  - folio `TUR-20260706-002-001`;
  - almacen `5`;
  - caja `2`;
  - monto esperado `1090`;
  - monto contado `1090`;
  - diferencia `0`.
- Resumen:
  - operaciones venta `1`;
  - ventas total `590`;
  - pagado `590`;
  - saldo `0`;
  - anticipo efectivo `120`;
  - liquidacion efectivo `470`;
  - monto inicial `500`;
  - movimientos caja total `1090`.
- Verificacion post-cierre read-only:
  - estatus turno `cerrado`;
  - fecha cierre `2026-07-06 21:47:29`;
  - ventas `590`;
  - pagos `590`;
  - movimientos caja `1090`;
  - ventas count `1`;
  - pagos count `2`;
  - movimientos caja count `3`;
  - hallazgos `[]`.
- Verificacion apartado:
  - folio `APT-20260706-000001`;
  - estatus `entregado`;
  - partidas `2`;
  - reservas `2`;
  - cantidad consumida `2`;
  - trazas/kardex `2`;
  - hallazgos `[]`.
- Readiness posterior esperado:
  - sin turno abierto;
  - stock disponible `0`;
  - bloqueo esperado para nuevo apartado: `Sin turno abierto`, `Stock insuficiente para multipartida`.
- Impacto:
  - ciclo UAT apartado multipartida completo;
  - caja cerrada sin diferencia;
  - inventario reservado y consumido correctamente;
  - kardex/trazabilidad verificados.

## Evidencia UAT - Preparacion cancelacion apartado antes de entrega

- Fecha: 2026-07-06
- Autorizacion recibida:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS cancelacion apartado"

AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-UAT-CANCEL-APT-1760
```

- Resultado apertura:
  - `ok=true`;
  - `id_turno_caja=17`;
  - folio `TUR-20260706-002-002`;
  - `id_movimiento_caja=36`;
  - almacen `5`;
  - caja `2`;
  - monto inicial `500`.
- Resultado stock:
  - `ok=true`;
  - almacen `5`;
  - SKU `1760`;
  - cantidad `1`;
  - referencia `INV-INICIAL-POS-UAT-CANCEL-APT-1760`;
  - inventario inicial aplicado;
  - movimientos `1`.
- Prevalidacion read-only:
  - turno abierto `true`;
  - `id_turno_caja=17`;
  - partidas enviadas `1`;
  - reservas propuestas `1`;
  - anticipo minimo `59`;
  - pagado simulado `100`;
  - saldo estimado `195`;
  - bloqueos `[]`.
- Impacto:
  - ambiente listo para crear apartado de prueba;
  - aun no se creo apartado ni se cancelo;
  - no se genero kardex de salida.

## Evidencia UAT - Apartado creado para cancelacion antes de entrega

- Fecha: 2026-07-06
- Autorizacion recibida:

```text
AUTORIZO CREAR APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDO_REAL id_usuario=1 telefono=3312345678 cliente="Cliente UAT Cancelacion Apartado POS" anticipo=100 item=1760,1,295
```

- Resultado:
  - `ok=true`;
  - folio `APT-20260706-000002`;
  - `id_venta=16`;
  - estatus `pendiente_pago`;
  - partidas enviadas `1`;
  - total `295`;
  - anticipo/pagado `100`;
  - saldo `195`.
- Reserva:
  - `id_reserva_inventario=5`;
  - folio `RES-PED-20260706-000003`;
  - `id_existencia_inventario=34`;
  - `id_venta_detalle=17`;
  - cantidad reservada `1`.
- Pago:
  - `id_venta_pago=19`;
  - `id_movimiento_caja=37`;
  - tipo `anticipo`;
  - metodo `Efectivo`;
  - monto `100`.
- Verificacion post-UAT read-only:
  - partidas `1`;
  - cantidad detalle `1`;
  - reservas `1`;
  - cantidad reservada `1`;
  - cantidad consumida `0`;
  - pagos `1`;
  - pagos total `100`;
  - trazas/kardex `0`;
  - hallazgos `[]`.
- Readiness posterior esperado:
  - reserva dry-run de otro apartado bloqueada por `Existencia insuficiente`;
  - motivo: la unidad cargada ya quedo reservada.
- Impacto:
  - apartado listo para prueba real de cancelacion antes de entrega;
  - no se genero kardex de salida.

## Evidencia UAT - Cancelacion apartado antes de entrega

- Fecha: 2026-07-06
- Autorizacion recibida:

```text
AUTORIZO CANCELAR APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDO_CANCELAR_REAL id_usuario=1 folio=APT-20260706-000002 motivo="UAT cancelacion apartado antes de entrega"
```

- Resultado:
  - `ok=true`;
  - folio `APT-20260706-000002`;
  - mensaje `Pedido/apartado cancelado`.
- Reserva liberada:
  - `id_reserva_inventario=5`;
  - folio `RES-PED-20260706-000003`;
  - cantidad liberada `1`.
- Verificacion post-UAT read-only:
  - estatus `cancelado`;
  - tipo documento `apartado`;
  - `id_turno_caja=17`;
  - total `295`;
  - pagado total `100`;
  - saldo total `195`;
  - partidas `1`;
  - reservas `1`;
  - cantidad reservada `1`;
  - cantidad consumida `0`;
  - pagos `1`;
  - pagos total `100`;
  - trazas/kardex `0`;
  - hallazgos `[]`.
- Readiness posterior:
  - reserva propuesta `1`;
  - bloqueos `[]`;
  - confirma que la unidad volvio a estar disponible para reservar.
- Turno:
  - folio `TUR-20260706-002-002`;
  - estatus `abierto`;
  - monto esperado `600`;
  - movimientos caja `600`;
  - pagos `100`.
- Impacto:
  - cancelacion antes de entrega validada;
  - reserva liberada;
  - no se genero kardex de salida;
  - no se borro anticipo;
  - anticipo queda para decision financiera futura.

## Evidencia UAT - Cierre turno cancelacion apartado

- Fecha: 2026-07-06
- Autorizacion recibida:

```text
AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=600 observaciones="Cierre UAT POS cancelacion apartado APT-20260706-000002"
```

- Resultado:
  - `ok=true`;
  - `id_turno_caja=17`;
  - folio `TUR-20260706-002-002`;
  - almacen `5`;
  - caja `2`;
  - monto esperado `600`;
  - monto contado `600`;
  - diferencia `0`.
- Resumen:
  - operaciones venta `1`;
  - ventas total `295`;
  - pagado `100`;
  - saldo `195`;
  - anticipo efectivo `100`;
  - monto inicial `500`;
  - movimientos caja total `600`.
- Verificacion post-cierre read-only:
  - estatus turno `cerrado`;
  - fecha cierre `2026-07-06 22:09:00`;
  - ventas `295`;
  - pagos `100`;
  - movimientos caja `600`;
  - ventas count `1`;
  - pagos count `1`;
  - movimientos caja count `2`;
  - hallazgos `[]`.
- Verificacion apartado cancelado:
  - folio `APT-20260706-000002`;
  - estatus `cancelado`;
  - reservas `1`;
  - cantidad consumida `0`;
  - trazas/kardex `0`;
  - pagos total `100`;
  - hallazgos `[]`.
- Readiness posterior esperado:
  - sin turno abierto;
  - reserva propuesta `1`;
  - bloqueo esperado: `Selecciona turno abierto de caja`.
- Impacto:
  - ciclo UAT cancelacion apartado antes de entrega completo;
  - caja cerrada sin diferencia;
  - reserva liberada verificada;
  - no hubo salida de inventario por kardex.

## Evidencia tecnica - Esquema propuesto decision financiera pedidos

- Fecha: 2026-07-06
- Objetivo:
  - preparar estructura formal para resolver anticipos de pedidos/apartados cancelados.
- Artefactos:
  - `docs/erp_ventas_pos_pedidos_finanzas_plan.md`;
  - `storage/uat/uat_ventas_pos_pedidos_finanzas_schema_readonly.php`.
- Resultado auditor read-only:
  - tabla `erp_ventas_pedidos_decisiones_financieras`: no existe;
  - columnas faltantes `29`;
  - indices faltantes `7`;
  - hallazgo: `Falta tabla erp_ventas_pedidos_decisiones_financieras.`
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedidos_finanzas_schema_readonly.php`: sin errores;
  - ejecucion `--compact=1`: correcta;
  - no escribio BD.
- Contrato:
  - no crea tablas;
  - no altera tablas;
  - no resuelve decision;
  - no mueve caja;
  - no mueve inventario.
- Siguiente paso:
  - solicitar autorizacion DDL si se decide implementar el expediente financiero formal.

## Evidencia UAT - DDL decisiones financieras pedidos aplicado

- Fecha: 2026-07-06
- Autorizacion recibida:

```text
AUTORIZO APLICAR DDL DECISIONES FINANCIERAS PEDIDOS POS usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDOS_FINANZAS_DDL para UAT POS
```

- Script:
  - `storage/uat/uat_ventas_pos_pedidos_finanzas_schema_apply_authorized.php`.
- Guardrail:
  - sin token/respaldo: bloqueado correctamente;
  - no mueve caja;
  - no genera saldo a favor;
  - no penaliza;
  - no mueve inventario.
- Resultado:
  - `ok=true`;
  - tabla creada `erp_ventas_pedidos_decisiones_financieras`;
  - columnas existentes `29/29`;
  - indices existentes `7/7`.
- Auditoria read-only posterior:
  - `tabla_existe=true`;
  - `columnas_faltantes=0`;
  - `indices_faltantes=0`;
  - hallazgos `[]`.
- Conteo inicial:
  - decisiones registradas `0`.
- Folio pendiente revalidado:
  - `APT-20260706-000002`;
  - estatus `cancelado`;
  - monto pendiente decision `100`;
  - hallazgos `[]`.
- Impacto:
  - estructura lista para registrar expedientes financieros;
  - no se resolvio dinero todavia.

## Evidencia tecnica - Dry-run decision financiera pedido cancelado

- Fecha: 2026-07-06
- Script:
  - `storage/uat/uat_ventas_pos_pedidos_finanzas_decision_dryrun.php`.
- Objetivo:
  - validar decision financiera antes de registrar expediente real.
- Caso valido:
  - folio `APT-20260706-000002`;
  - decision `saldo_favor`;
  - monto `100`;
  - resultado `ok=true`;
  - bloqueos `[]`;
  - propuesta con `monto_saldo_favor=100`;
  - sin turno abierto requerido.
- Bloqueos validados:
  - `reembolso_caja` sin turno abierto: bloqueado;
  - folio entregado `APT-20260706-000001`: bloqueado;
  - decision invalida: bloqueada;
  - monto mayor al pagado: bloqueado;
  - `sin_reembolso` con pago registrado: bloqueado.
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedidos_finanzas_decision_dryrun.php`: sin errores.
- Impacto:
  - no escribio BD;
  - no creo decision;
  - no movio caja;
  - no genero saldo favor real.

## Evidencia UAT - Decision financiera saldo a favor registrada

- Fecha: 2026-07-06
- Autorizacion recibida:

```text
AUTORIZO REGISTRAR DECISION FINANCIERA APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDOS_FINANZAS_REAL id_usuario=1 folio=APT-20260706-000002 decision=saldo_favor monto=100 motivo="UAT saldo a favor por cancelacion apartado"
```

- Script:
  - `storage/uat/uat_ventas_pos_pedidos_finanzas_decision_apply_authorized.php`.
- Guardrail:
  - sin token/respaldo: bloqueado correctamente;
  - no mueve caja;
  - no crea saldo CRM real;
  - no penaliza;
  - no mueve inventario.
- Resultado:
  - `ok=true`;
  - folio venta `APT-20260706-000002`;
  - `id_decision_financiera=1`;
  - folio decision `PFIN-20260706-000001`;
  - decision `saldo_favor`;
  - monto base `100`;
  - monto saldo favor `100`;
  - estatus `pendiente_ledger_crm`.
- Evento registrado:
  - `pedido_decision_financiera_registrada`;
  - monto `100`;
  - referencia `PFIN-20260706-000001`;
  - observaciones `decision_financiera=saldo_favor`.
- Verificacion posterior:
  - decisiones financieras `1`;
  - monto pendiente decision `0`;
  - monto con decision `100`;
  - `id_movimiento_caja=NULL`;
  - `id_saldo_cliente_movimiento=NULL`;
  - hallazgos `[]`.
- Duplicado:
  - dry-run posterior bloquea por `Ya existe decision financiera activa para este folio.`
- Impacto:
  - expediente financiero registrado;
  - saldo a favor aun pendiente de ledger CRM;
  - no hubo salida de caja ni movimiento de inventario.

## Evidencia tecnica - Dry-run liga cliente CRM y saldo favor POS

- Fecha: 2026-07-06
- Objetivo:
  - validar que una decision financiera POS pendiente puede ligarse a cliente CRM antes de crear saldo real;
  - evitar saldos anonimos;
  - mantener separado saldo favor de recompensas.
- Scripts:
  - `storage/uat/uat_ventas_pos_pedidos_cliente_crm_link_dryrun.php`;
  - `storage/uat/uat_ventas_pos_pedidos_cliente_crm_link_apply_authorized.php`.
- Caso validado:
  - folio decision `PFIN-20260706-000001`;
  - folio venta `APT-20260706-000002`;
  - cliente CRM candidato `157`;
  - monto saldo favor `100`;
  - resultado dry-run `ok=true`.
- Propuesta:
  - actualizar venta con `id_cliente_crm=157`;
  - actualizar decision financiera con `id_cliente_crm=157`;
  - crear cuenta CRM saldos MXN si falta;
  - crear movimiento `abono_saldo_favor`;
  - marcar decision como `aplicada`;
  - registrar evento en ventas y CRM.
- Bloqueos comprobados:
  - cliente CRM inexistente;
  - folio decision inexistente.
- Guardrail:
  - apply sin token/respaldo bloqueado correctamente;
  - no escribio BD.
- Aviso:
  - cliente `157` proviene de legacy migrado; requiere confirmacion operativa antes de aplicar.
- Autorizacion requerida:

```text
AUTORIZO APLICAR LIGA CLIENTE CRM Y SALDO FAVOR POS usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDOS_CLIENTE_CRM_LINK_REAL id_usuario=1 folio_decision=PFIN-20260706-000001 id_cliente_crm=157 motivo="UAT aplicar saldo favor POS a cliente CRM"
```

## Evidencia UAT - Saldo favor POS aplicado a CRM

- Fecha: 2026-07-06
- Autorizacion recibida:

```text
AUTORIZO APLICAR LIGA CLIENTE CRM Y SALDO FAVOR POS usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDOS_CLIENTE_CRM_LINK_REAL id_usuario=1 folio_decision=PFIN-20260706-000001 id_cliente_crm=157 motivo="UAT aplicar saldo favor POS a cliente CRM"
```

- Script ejecutado:
  - `storage/uat/uat_ventas_pos_pedidos_cliente_crm_link_apply_authorized.php`.
- Resultado:
  - `ok=true`;
  - folio decision `PFIN-20260706-000001`;
  - folio venta `APT-20260706-000002`;
  - cliente CRM `157`;
  - cuenta saldo CRM `1`;
  - movimiento saldo CRM `1`;
  - folio movimiento `CRM-SAL-20260706-000001`;
  - monto `100`;
  - saldo anterior `0`;
  - saldo resultante `100`.
- Verificacion posterior:
  - `erp_ventas.id_cliente_crm=157`;
  - decision financiera `estatus=aplicada`;
  - decision financiera `id_saldo_cliente_movimiento=1`;
  - cuenta CRM MXN con saldo disponible `100`;
  - movimiento CRM aplicado por `100`;
  - evento venta `pedido_saldo_favor_crm_aplicado`;
  - evento CRM `saldo_favor_abonado`.
- Guardrail posterior:
  - dry-run vuelve a bloquear duplicado porque la decision ya esta aplicada y enlazada.
- Impacto:
  - no movio caja;
  - no movio inventario;
  - no uso recompensas;
  - creo saldo monetario real en CRM.
- Lector agregado:
  - `storage/uat/uat_crm_clientes_saldos_cliente_readonly.php`;
  - cliente `157`: saldo total MXN `100`.

## Evidencia tecnica - Dry-run uso saldo CRM en POS

- Fecha: 2026-07-06
- Autorizacion recibida:

```text
AUTORIZO PREPARAR DRY-RUN USO SALDO CRM EN POS usando respaldo UAT POS vigente con token VENTAS_POS_SALDO_CRM_USO_DRYRUN id_usuario=1 id_cliente_crm=157 monto=100 para UAT POS
```

- Script:
  - `storage/uat/uat_ventas_pos_saldo_crm_uso_dryrun.php`.
- Plan:
  - `docs/erp_ventas_pos_saldo_crm_plan.md`.
- Caso validado:
  - cliente CRM `157`;
  - saldo disponible `100`;
  - monto `100`;
  - saldo resultante simulado `0`;
  - `ok=true`.
- Bloqueos comprobados:
  - monto `150`: saldo insuficiente;
  - cliente `999999`: cliente/cuenta inexistente;
  - `total_venta=80` con monto `100`: bloquea cambio desde saldo CRM.
- Contrato:
  - read-only;
  - no crea venta;
  - no crea pago;
  - no descuenta saldo CRM;
  - no mueve caja;
  - no mueve inventario;
  - no usa recompensas.
- Decision:
  - saldo CRM debe registrarse en POS como pago sin caja;
  - el descuento real del saldo debe ocurrir en la misma transaccion que la venta POS.

## Evidencia tecnica - UX Pedidos/Apartados con buscador de producto

- Fecha: 2026-07-05
- Objetivo:
  - eliminar valores UAT precargados de la pantalla operativa;
  - permitir seleccionar producto desde el buscador existente del POS;
  - reducir captura manual de `id_sku` y precio.
- Cambios:
  - `app/vistas/paginas/apps/erp/ventas/pedidos.php`;
  - `public/assets/js/custom/apps/erp/ventas/pedidos.js`;
  - `app/controladores/Ventas.php` solo actualizo comentario de contrato.
- Comportamiento:
  - buscar por codigo, SKU o nombre;
  - consultar `/ventas/pos_buscar_skus_erp`;
  - mostrar imagen, existencia, precio y badges;
  - seleccionar producto llena SKU/precio y recalcula total, anticipo y saldo.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pedidos.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
  - `node --check public\assets\js\custom\apps\erp\ventas\pedidos.js`: sin errores.
- Impacto:
  - no escribio BD;
  - no movio caja;
  - no movio inventario;
  - prepara UAT manual de alta de apartado desde UI.

## Evidencia tecnica - Pedidos/Apartados multipartida en UI

- Fecha: 2026-07-05
- Objetivo:
  - permitir que un apartado/pedido POS tenga varias partidas desde pantalla;
  - evitar capturar pedidos reales producto por producto como operaciones separadas;
  - aprovechar el contrato backend existente de `items`.
- Cambios:
  - `app/vistas/paginas/apps/erp/ventas/pedidos.php`;
  - `public/assets/js/custom/apps/erp/ventas/pedidos.js`.
- Comportamiento:
  - agregar partida desde producto seleccionado o SKU capturado;
  - editar cantidad/precio por renglon;
  - quitar/vaciar partidas;
  - recalcular total, anticipo y saldo;
  - enviar `items` con todas las partidas al dry-run y a la accion real.
- Validaciones:
  - `node --check public\assets\js\custom\apps\erp\ventas\pedidos.js`: sin errores;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pedidos.php`: sin errores.
- Impacto:
  - no escribio BD;
  - no movio caja;
  - no movio inventario;
  - deja listo el siguiente UAT real de apartado con multiples partidas.

## Evidencia tecnica - UAT multipartida Pedidos/Apartados preparada

- Fecha: 2026-07-06
- Objetivo:
  - preparar UAT real de apartado/pedido con multiples partidas;
  - mantener un solo script autorizado reutilizable;
  - evitar fatal si la conexion de BD no esta disponible durante dry-run.
- Cambios:
  - `storage/uat/uat_ventas_pos_pedido_apartado_apply_authorized.php`;
  - `storage/uat/uat_ventas_pos_pedidos_apartados_readonly.php`;
  - `app/modelos/VentasErp.php`.
- Entradas nuevas:
  - `--items_json=[{"id_sku":1760,"cantidad":1,"precio_unitario":295}]`;
  - `--item=1760,1,295` repetible.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedido_apartado_apply_authorized.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedidos_apartados_readonly.php`: sin errores.
- Seguridad:
  - aplicador real sin token quedo bloqueado;
  - no escribio BD;
  - no creo pedido;
  - no registro pago;
  - no reservo inventario.
- Read-only ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pedidos_apartados_readonly.php --compact=1 --item=1760,1,295 --item=1760,0.5,295
```

- Resultado:
  - JSON controlado;
  - `partidas_enviadas=2`;
  - `reservas_propuestas=0`;
  - bloqueo `conexion_bd_no_disponible`.
- Reintento con MySQL levantado:
  - PHP conecto correctamente a BD;
  - asignacion POS activa;
  - almacen `5`;
  - caja `2`;
  - turno abierto `false`;
  - apartados en muestra `1`;
  - `partidas_enviadas=2`;
  - `anticipo_minimo=88.5`;
  - `pagado_total=100`;
  - `saldo_estimado=342.5`;
  - bloqueos:
    - `Selecciona turno abierto de caja`;
    - `Existencia insuficiente`;
    - `La cantidad no respeta el incremento minimo de venta`;
    - `Existencia insuficiente`.
- Impacto:
  - se reemplazo fatal por bloqueo legible;
  - no hubo escritura de base de datos;
  - queda pendiente abrir turno y cargar stock suficiente antes de pedir UAT real multipartida.

## Evidencia tecnica - Guardrail dry-run sobre-reserva multipartida

- Fecha: 2026-07-06
- Objetivo:
  - evitar que el dry-run de POS/Pedidos valide dos renglones contra la misma disponibilidad sin sumar consumo acumulado;
  - alinear la simulacion con el flujo real, que bloquea existencia con `FOR UPDATE` al reservar.
- Cambio:
  - `VentasErp::validarPlanSalidaAcumuladoPos`;
  - llamada desde `VentasErp::prevalidarCarritoPos`.
- Regla:
  - suma cantidades propuestas por `id_existencia_inventario`;
  - suma cantidades propuestas por `id_inventario_unidad`;
  - bloquea si el acumulado supera la disponibilidad inicial del plan.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - read-only multipartida con dos partidas enteras devolvio JSON controlado.
- Read-only adicional:
  - comando con `--monto_abono=120 --item=1760,1,295 --item=1760,1,295`;
  - anticipo minimo `118`;
  - pagado total `120`;
  - saldo estimado `470`;
  - bloqueo por anticipo minimo eliminado;
  - bloqueos restantes: turno abierto pendiente y existencia insuficiente.
- Impacto:
  - no escribio BD;
  - no movio caja;
  - no reservo inventario;
  - reduce riesgo de UAT/productivo con prevalidacion optimista.

## Evidencia tecnica - Verificador post-UAT apartado multipartida

- Fecha: 2026-07-06
- Objetivo:
  - verificar apartado/pedido multipartida por folio despues de UAT real;
  - revisar venta, partidas, reservas, pagos, caja y trazas/kardex;
  - producir hallazgos sin escribir BD.
- Archivo:
  - `storage/uat/uat_ventas_pos_pedido_multipartida_post_readonly.php`.
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedido_multipartida_post_readonly.php`: sin errores;
  - ejecucion sin `--folio`: bloqueada;
  - ejecucion con folio inexistente: `no_encontrado`.
- Prueba con folio real previo:
  - `APT-20260705-000001`;
  - estatus `entregado`;
  - total `295`;
  - pagado `295`;
  - saldo `0`;
  - partidas `1`;
  - reservas `1`;
  - reserva consumida `1`;
  - pagos `2`;
  - trazas/kardex `1`;
  - hallazgo esperado: `La UAT multipartida esperaba al menos 2 partidas.`
- Contrato:
  - `no_escribe_bd`;
  - `no_crea_pedido`;
  - `no_registra_pago`;
  - `no_mueve_caja`;
  - `no_reserva_inventario`;
  - `no_mueve_kardex`.

## Evidencia tecnica - Runbook read-only apartado multipartida

- Fecha: 2026-07-06
- Objetivo:
  - preparar ciclo completo UAT de apartado multipartida;
  - calcular totales esperados;
  - listar autorizaciones y comandos sin ejecutar escrituras.
- Archivo:
  - `storage/uat/uat_ventas_pos_pedido_multipartida_runbook_readonly.php`.
- Valores default:
  - total `590`;
  - anticipo `120`;
  - saldo `470`;
  - monto inicial `500`;
  - monto contado final sugerido `1090`.
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedido_multipartida_runbook_readonly.php`: sin errores;
  - ejecucion compacta devolvio JSON con autorizaciones sugeridas y contrato read-only.
- Contrato:
  - no abre turno;
  - no carga stock;
  - no crea apartado;
  - no registra pago;
  - no entrega;
  - no cierra turno;
  - no escribe BD.

## Evidencia tecnica - Readiness UAT apartado multipartida

- Fecha: 2026-07-06
- Objetivo:
  - validar prerequisitos actuales para UAT real multipartida;
  - evitar autorizaciones incompletas.
- Archivo:
  - `storage/uat/uat_ventas_pos_pedido_multipartida_readiness_readonly.php`.
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedido_multipartida_readiness_readonly.php`: sin errores;
  - ejecucion compacta devolvio JSON read-only.
- Resultado actual:
  - asignacion POS activa;
  - caja `2`;
  - turno abierto `false`;
  - stock disponible `0`;
  - stock necesario `2`;
  - dry-run bloqueado por turno y existencia insuficiente;
  - anticipo minimo `118`;
  - anticipo configurado `120`;
  - saldo estimado `470`.
- Impacto:
  - no escribio BD;
  - confirma que la siguiente autorizacion debe abrir turno y cargar stock.

## Evidencia tecnica - Verificador post-UAT parametrizable

- Fecha: 2026-07-06
- Objetivo:
  - permitir validar folios con expectativas explicitas;
  - reutilizar el verificador para UAT multipartida y folios previos.
- Archivo:
  - `storage/uat/uat_ventas_pos_pedido_multipartida_post_readonly.php`.
- Nuevos parametros:
  - `--esperar_partidas`;
  - `--esperar_total`;
  - `--esperar_pagado`;
  - `--esperar_saldo`.
- Validaciones:
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedido_multipartida_post_readonly.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedido_multipartida_runbook_readonly.php`: sin errores;
  - folio `APT-20260705-000001` con expectativas de 1 partida y total `295` devolvio `hallazgos=[]`.
- Impacto:
  - no escribio BD;
  - mejora evidencia posterior a creacion, abono y entrega.

## Evidencia UAT - Pedidos/Apartados POS read-only UX

- Fecha: 2026-07-05
- Cambios implementados:
  - `/ventas/pedidos` muestra contexto POS operativo:
    - tienda/almacen;
    - caja asignada;
    - turno;
    - modo actual.
  - la simulacion de apartado muestra total, anticipo y saldo antes de enviar la validacion.
  - el mensaje de estado superior aclara que esta pantalla consulta/simula sin escribir caja ni inventario.
- Validaciones:
  - `php -l app/vistas/paginas/apps/erp/ventas/pedidos.php`: sin errores;
  - `node --check public/assets/js/custom/apps/erp/ventas/pedidos.js`: sin errores.
- UAT read-only:
  - comando: `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pedidos_apartados_readonly.php --compact=1`;
  - resultado `ok=true`;
  - asignacion activa `true`;
  - almacen `5`;
  - caja `2`;
  - turno abierto `false`;
  - pedidos `0`;
  - apartados `0`;
  - reserva dry-run bloqueada por `Selecciona turno abierto de caja` y `Existencia insuficiente`;
  - abono dry-run bloqueado por `Selecciona turno abierto de caja` y `Pedido/apartado no encontrado`.
- Contrato:
  - no escribe BD;
  - no crea pedido;
  - no registra abono;
  - no mueve caja;
  - no reserva inventario;
  - no mueve kardex;
  - no modifica ecommerce ni legacy.

## Evidencia tecnica - Flujo real Pedidos/Apartados POS preparado

- Fecha: 2026-07-05
- Autorizacion recibida:

```text
AUTORIZO PREPARAR FLUJO REAL PEDIDOS APARTADOS POS usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDOS_APARTADOS_REAL para UAT POS
```

- Cambios backend:
  - `VentasErp::pedidoGuardarReal`;
  - `VentasErp::apartadoAbonoReal`;
  - `VentasErp::pedidoEntregarReal`;
  - `VentasErp::pedidoCancelarReal`;
  - `InventarioErp::consumirReserva`.
- Endpoints:
  - `/ventas/pedido_guardar_erp`;
  - `/ventas/apartado_abono_erp`;
  - `/ventas/pedido_entregar_erp`;
  - `/ventas/pedido_cancelar_erp`.
- Scripts UAT:
  - `uat_ventas_pos_pedido_apartado_apply_authorized.php`;
  - `uat_ventas_pos_apartado_abono_apply_authorized.php`;
  - `uat_ventas_pos_pedido_entrega_apply_authorized.php`;
  - `uat_ventas_pos_pedido_cancelar_apply_authorized.php`.
- Validaciones:
  - `php -l app/modelos/VentasErp.php`: sin errores;
  - `php -l app/modelos/InventarioErp.php`: sin errores;
  - `php -l app/controladores/Ventas.php`: sin errores;
  - scripts UAT: sin errores.
- Guardia de escritura:
  - scripts ejecutados sin token devuelven `modo=bloqueado`;
  - no crean pedido;
  - no registran abono;
  - no reservan inventario;
  - no entregan;
  - no cancelan.
- Preflight final:
  - `uat_ventas_pos_pedidos_real_preflight_readonly.php --compact=1`;
  - `ok=true`;
  - metodos reales presentes;
  - politica activa de apartado `1`;
  - asignacion POS activa `true`;
  - turno abierto `false`;
  - stock UAT suficiente `false`.
- Contrato operativo:
  - crear apartado reserva inventario y registra anticipo;
  - abonar/liquidar registra caja y saldo sin mover inventario;
  - entregar consume reserva y genera kardex;
  - cancelar libera reserva y no reembolsa automaticamente.

## Evidencia UAT - Preparacion datos Pedidos/Apartados POS

- Fecha: 2026-07-05
- Autorizacion recibida:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT Pedidos/Apartados POS"

AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-UAT-PEDIDOS-01
```

- Apertura de turno:
  - `ok=true`;
  - turno `id_turno_caja=15`;
  - folio `TUR-20260705-002-001`;
  - movimiento caja inicial `id_movimiento_caja=30`;
  - usuario `1`;
  - almacen `5`;
  - caja `2`;
  - monto inicial `500`.
- Carga de stock:
  - `ok=true`;
  - SKU `1760`;
  - almacen `5`;
  - cantidad `1`;
  - referencia `INV-INICIAL-POS-UAT-PEDIDOS-01`;
  - movimientos inventario `1`.
- Auditoria read-only posterior:
  - preflight real pedidos/apartados `ok=true`;
  - asignacion activa `true`;
  - turno abierto `true`;
  - `id_turno_caja=15`;
  - existencias disponibles suficientes `1`;
  - cantidad disponible `1`;
  - hallazgos `[]`.
- Dry-run apartado:
  - `ok=true`;
  - anticipo minimo `59`;
  - pagado simulado `100`;
  - saldo estimado `195`;
  - bloqueos de reserva `[]`.
- Estado:
  - listo para autorizar creacion de apartado real;
  - abono dry-run sigue bloqueado hasta que exista folio real de apartado.

## Evidencia UAT - Apartado POS real creado

- Fecha: 2026-07-05
- Autorizacion recibida:

```text
AUTORIZO CREAR APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDO_REAL id_usuario=1 id_sku=1760 cantidad=1 precio=295 anticipo=100 telefono=3312345678 cliente="Cliente UAT Apartado POS"
```

- Resultado:
  - `ok=true`;
  - folio apartado `APT-20260705-000001`;
  - `id_venta=14`;
  - estatus `pendiente_pago`;
  - total `295`;
  - pagado `100`;
  - saldo `195`.
- Reserva:
  - `id_reserva_inventario=2`;
  - folio `RES-PED-20260705-000001`;
  - existencia `34`;
  - cantidad reservada `1`;
  - estatus `activa`.
- Pago/caja:
  - `id_venta_pago=15`;
  - `id_movimiento_caja=31`;
  - tipo pago `anticipo`;
  - metodo `Efectivo`;
  - monto `100`;
  - turno `15` monto esperado `600`.
- Inventario:
  - existencia `34`;
  - cantidad `1`;
  - cantidad apartada `1`;
  - cantidad disponible `0`.
- Auditoria read-only posterior:
  - apartados listados `1`;
  - abono dry-run para `APT-20260705-000001` por `195`: bloqueos `[]`;
  - reserva dry-run para otro apartado queda bloqueada por `Existencia insuficiente`, esperado porque la unica pieza disponible ya quedo apartada.
- Contrato cumplido:
  - creo apartado;
  - creo reserva;
  - registro anticipo en caja;
  - no genero kardex de salida;
  - no entrego mercancia;
  - no modifico ecommerce ni legacy.

## Evidencia UAT - Liquidacion apartado POS real

- Fecha: 2026-07-05
- Autorizacion recibida:

```text
AUTORIZO REGISTRAR ABONO APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_APARTADO_ABONO_REAL id_usuario=1 folio=APT-20260705-000001 monto=195 referencia=UAT-LIQ-APT-20260705-000001
```

- Resultado:
  - `ok=true`;
  - folio `APT-20260705-000001`;
  - `id_venta=14`;
  - estatus `pagado`;
  - pagado total `295`;
  - saldo total `0`.
- Pagos:
  - anticipo `id_venta_pago=15`, monto `100`, movimiento caja `31`;
  - liquidacion `id_venta_pago=16`, monto `195`, movimiento caja `32`.
- Caja:
  - turno `15`;
  - movimientos:
    - apertura `500`;
    - anticipo `100`;
    - liquidacion `195`;
  - monto esperado `795`;
  - estatus turno `abierto`.
- Inventario:
  - reserva `2` sigue `activa`;
  - cantidad reservada `1`;
  - consumida `0`;
  - liberada `0`;
  - existencia `34`: cantidad `1`, apartada `1`, disponible `0`.
- Validacion:
  - abono adicional queda bloqueado por `El pedido/apartado no tiene saldo pendiente`;
  - no se genero kardex de salida;
  - la mercancia sigue apartada hasta entrega.

## Evidencia UAT - Entrega apartado POS real

- Fecha: 2026-07-05
- Autorizacion recibida:

```text
AUTORIZO ENTREGAR APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDO_ENTREGA_REAL id_usuario=1 folio=APT-20260705-000001
```

- Resultado:
  - `ok=true`;
  - folio `APT-20260705-000001`;
  - `id_venta=14`;
  - estatus venta `entregado`;
  - detalle `14` estatus `entregada`.
- Reserva:
  - `id_reserva_inventario=2`;
  - folio `RES-PED-20260705-000001`;
  - estatus `consumida`;
  - cantidad reservada `1`;
  - cantidad consumida `1`;
  - cantidad liberada `0`.
- Inventario:
  - existencia `34`;
  - cantidad anterior `1`;
  - cantidad nueva `0`;
  - apartada `0`;
  - disponible `0`;
  - estatus `agotada`.
- Kardex:
  - movimiento inventario `77`;
  - tipo `salida`;
  - origen `pedido_pos_entrega`;
  - origen id `14`;
  - cantidad `1`;
  - referencia `APT-20260705-000001`.
- Trazabilidad:
  - `erp_ventas_detalle_inventario.id_venta_detalle_inventario=14`;
  - venta `14`;
  - detalle `14`;
  - reserva `2`;
  - movimiento inventario `77`;
  - cantidad `1`;
  - estatus `confirmada`.
- Caja:
  - turno `15`;
  - monto esperado se mantiene `795`;
  - no se registraron pagos nuevos en entrega.

## Evidencia UAT - Cierre turno apartado POS entregado

- Fecha: 2026-07-05
- Autorizacion recibida:

```text
AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS apartado entregado APT-20260705-000001"
```

- Resultado:
  - `ok=true`;
  - turno `id_turno_caja=15`;
  - folio `TUR-20260705-002-001`;
  - estatus `cerrado`;
  - monto esperado `795`;
  - monto contado `795`;
  - diferencia `0`.
- Resumen:
  - ventas operaciones `1`;
  - venta total `295`;
  - pagado `295`;
  - saldo `0`;
  - pagos:
    - efectivo anticipo `100`;
    - efectivo liquidacion `195`;
  - movimientos caja:
    - entrada monto inicial `500`;
    - ingreso anticipo `100`;
    - ingreso liquidacion `195`.
- Auditoria post-cierre:
  - venta `14` / `APT-20260705-000001`: `entregado`;
  - reserva `2`: `consumida`;
  - existencia `34`: cantidad `0`, apartada `0`, disponible `0`, ultimo movimiento `77`;
  - turnos abiertos `0`;
  - hallazgos `[]`.
- Corte imprimible read-only:
  - folio `TUR-20260705-002-001`;
  - lineas `37`;
  - hallazgos `[]`;
  - incluye esperado `795`, contado `795`, diferencia `0`.
- Nota tecnica:
  - una consulta inicial del corte por `id_turno_caja` devolvio el turno anterior por limitacion del script UAT;
  - se repitio por folio `TUR-20260705-002-001` y devolvio el corte correcto.

## Evidencia tecnica - UI Pedidos/Apartados conectada a acciones reales

- Fecha: 2026-07-05
- Cambios:
  - `/ventas/pedidos` mantiene simulacion antes de ejecutar acciones reales;
  - muestra accion real de crear pedido/apartado solo si el dry-run no tiene bloqueos;
  - muestra accion real de abono solo si el dry-run no tiene bloqueos;
  - listado muestra acciones por estado para abonar, entregar o cancelar;
  - cada accion real requiere confirmacion por frase exacta.
- Confirmaciones:
  - crear: `CREAR`;
  - abonar: `ABONAR`;
  - entregar: `ENTREGAR`;
  - cancelar: motivo obligatorio + `CANCELAR`.
- Endpoints:
  - `/ventas/pedido_guardar_erp`;
  - `/ventas/apartado_abono_erp`;
  - `/ventas/pedido_entregar_erp`;
  - `/ventas/pedido_cancelar_erp`.
- Validaciones:
  - `php -l app/vistas/paginas/apps/erp/ventas/pedidos.php`: sin errores;
  - `node --check public/assets/js/custom/apps/erp/ventas/pedidos.js`: sin errores;
  - read-only posterior `ok=true`.
- Estado posterior:
  - no hay turno abierto;
  - apartado `APT-20260705-000001` ya esta entregado;
  - abonos adicionales quedan bloqueados por estatus/saldo;
  - no se ejecuto escritura nueva durante esta integracion UI.

## Evidencia read-only - Configuracion POS final preparada

- Fecha: 2026-07-04
- Alcance:
  - preparar UI CRUD de configuracion POS;
  - cerrar permisos finos en codigo;
  - no sembrar permisos ni modificar BD.
- Archivos modificados:
  - `app/modelos/SeguridadEsquema.php`;
  - `app/controladores/Ventas.php`;
  - `app/core/Core.php`;
  - `app/vistas/includes/header/sidebar.php`;
  - `app/vistas/paginas/apps/erp/ventas/pos_configuracion.php`;
  - `public/assets/js/custom/apps/erp/ventas/pos_configuracion.js`;
  - `storage/uat/uat_ventas_pos_configuracion_permisos_readonly.php`;
  - `storage/uat/uat_ventas_pos_configuracion_permisos_apply_authorized.php`.
- Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l app\controladores\Ventas.php
C:\xampp\php\php.exe -l app\modelos\SeguridadEsquema.php
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos_configuracion.php
node --check public\assets\js\custom\apps\erp\ventas\pos_configuracion.js
C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_configuracion_permisos_readonly.php
C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_configuracion_permisos_apply_authorized.php
```

- Resultado:
  - sintaxis PHP sin errores;
  - sintaxis JS sin errores;
  - `uat_ventas_pos_configuracion_readonly.php --compact=1 --id_usuario=1`: `ok=true`;
  - contexto POS: usuario `1`, almacen `5`, caja `2`, terminal `2`, asignacion activa `true`, turno abierto `false`;
  - resumen: 2 cajas, 2 terminales, 2 asignaciones, 0 turnos abiertos.
- Auditoria permisos:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_configuracion_permisos_readonly.php --compact=1 --id_usuario=1
```

- Resultado permisos:
  - `ventas.pos_config.ver=false`;
  - `ventas.pos_config.crear=false`;
  - `ventas.pos_config.editar=false`;
  - `ventas.pos_config.desactivar=false`;
  - `ventas.pos_config.asignar_usuario=false`.
- Contrato:
  - no se escribio BD;
  - no se asignaron roles;
  - no se abrio turno;
  - no se movio caja;
  - no se movio inventario.
- Siguiente autorizacion:

```text
AUTORIZO SEMBRAR PERMISOS CONFIGURACION POS usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_PERMISOS para UAT POS
```

## Evidencia UAT - Permisos Configuracion POS sembrados

- Fecha: 2026-07-04
- Autorizacion recibida:

```text
AUTORIZO SEMBRAR PERMISOS CONFIGURACION POS usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_PERMISOS para UAT POS
```

- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_configuracion_permisos_apply_authorized.php --autorizar=VENTAS_POS_CONFIG_PERMISOS --respaldo="UAT POS vigente"
```

- Resultado:
  - `ok=true`;
  - permisos sembrados `5`;
  - roles detectados: `administrador_erp`, `auditor`, `direccion`;
  - relaciones intentadas `11`;
  - no asigno usuarios directo.
- Permisos confirmados:
  - `ventas.pos_config.ver=true`;
  - `ventas.pos_config.crear=true`;
  - `ventas.pos_config.editar=true`;
  - `ventas.pos_config.desactivar=true`;
  - `ventas.pos_config.asignar_usuario=true`.
- Roles por permiso:
  - `administrador_erp` y `direccion`: ver, crear, editar, desactivar, asignar usuario;
  - `auditor`: solo ver.
- Usuario UAT `1`:
  - tiene los 5 permisos `ventas.pos_config.*`.
- Auditoria Configuracion POS posterior:
  - `ok=true`;
  - usuario `1`;
  - almacen `5`;
  - caja `2`;
  - terminal `2`;
  - asignacion activa `true`;
  - turno abierto `false`;
  - cajas `2`;
  - terminales `2`;
  - asignaciones `2`;
  - hallazgos `[]`.
- Contrato:
  - no se abrio turno;
  - no se movio caja;
  - no se movio inventario;
  - no se crearon cajas, terminales ni asignaciones en esta accion.

## Evidencia UAT visual - Configuracion POS CRUD preparada

- Fecha: 2026-07-04
- Script:

```powershell
node storage\uat\uat_ventas_pos_configuracion_playwright_uat.js
```

- Resultado:
  - `ok=true`;
  - URL final `/ventas/pos_configuracion`;
  - status HTTP `200`;
  - login UAT correcto;
  - entro a Configuracion POS;
  - muestra botones Guardar;
  - filas cajas `2`;
  - filas terminales `2`;
  - filas asignaciones `2`;
  - botones editar `6`;
  - botones desactivar `6`;
  - editar caja llena formulario `true`;
  - editar terminal llena formulario `true`;
  - editar asignacion llena formulario `true`.
- Evidencia visual:
  - `public/storage/uat/pos_configuracion_crud_uat.png`.
- Consola:
  - se observaron errores `ERR_NETWORK_ACCESS_DENIED` de recursos externos bloqueados por el entorno;
  - no bloquearon el flujo POS.
- Contrato cumplido:
  - no presiono Guardar;
  - no presiono Desactivar;
  - no abrio turno;
  - no movio caja;
  - no movio inventario.
- Auditoria posterior read-only:
  - configuracion sigue con 2 cajas, 2 terminales, 2 asignaciones;
  - turnos abiertos `0`;
  - permisos `ventas.pos_config.*` vigentes y sin faltantes.
- Siguiente autorizacion sugerida:

```text
AUTORIZO EJECUTAR CRUD REAL CONFIGURACION POS UAT usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_CRUD id_usuario=1 para UAT POS
```

## Evidencia UAT - CRUD real Configuracion POS

- Fecha: 2026-07-04
- Autorizacion recibida:

```text
AUTORIZO EJECUTAR CRUD REAL CONFIGURACION POS UAT usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_CRUD id_usuario=1 para UAT POS
```

- Acciones ejecutadas:
  - alta caja UAT;
  - alta terminal UAT;
  - alta asignacion usuario/caja/terminal UAT;
  - baja logica de asignacion UAT;
  - baja logica de terminal UAT;
  - baja logica de caja UAT.
- Registros:
  - caja `id_caja=3`, codigo `CJ-UAT-20260704-01`;
  - terminal `id_terminal_pos=3`, codigo `TERM-UAT-20260704-01`;
  - asignacion `id_usuario_caja=3`, usuario `1`, almacen `5`, caja `3`, terminal `3`.
- Resultado altas:
  - caja creada `activa`;
  - terminal creada `activa`;
  - asignacion creada `activo`.
- Resultado bajas:
  - caja `inactiva`;
  - terminal `inactiva`;
  - asignacion `inactivo`;
  - motivo: `UAT cierre de prueba CRUD configuracion POS`.
- Auditoria read-only:
  - `storage/uat/uat_ventas_pos_configuracion_crud_auditoria_readonly.php`: `ok=true`;
  - turnos abiertos `0`;
  - registros UAT encontrados con baja logica;
  - observaciones contienen usuario y motivo.
- Configuracion base posterior:
  - contexto usuario `1`: almacen `5`, caja `2`, terminal `2`;
  - asignacion activa `true`;
  - turno abierto `false`;
  - hallazgos `[]`.
- Contrato:
  - no abrio turno;
  - no movio caja;
  - no creo venta;
  - no movio inventario.
- Ajuste UX posterior:
  - las filas historicas/inactivas en Configuracion POS muestran etiqueta `Historico`;
  - las filas inactivas ya no muestran boton Desactivar.
- UAT visual posterior:
  - `/ventas/pos_configuracion` `ok=true`;
  - filas cajas `2`;
  - filas terminales `3`;
  - filas asignaciones `3`;
  - botones editar `8`;
  - botones desactivar `6`;
  - formularios siguen llenandose al editar;
  - no se presiono Guardar ni Desactivar.

## Evidencia UAT visual - Filtros de historicos Configuracion POS

- Fecha: 2026-07-04
- Alcance:
  - mejora UI sin escritura BD;
  - agregar filtros `Activos`, `Historial`, `Todos` en cajas, terminales y asignaciones;
  - evitar ruido visual de registros UAT inactivos.
- Cambios:
  - `/ventas/pos_configuracion` muestra grupos de filtros por tabla;
  - KPIs muestran activos y conteo de historicos cuando existen;
  - filas historicas conservan etiqueta `Historico`;
  - filas inactivas no muestran boton Desactivar.
- Validaciones:
  - `node --check public\assets\js\custom\apps\erp\ventas\pos_configuracion.js`: OK;
  - `node --check storage\uat\uat_ventas_pos_configuracion_playwright_uat.js`: OK;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos_configuracion.php`: OK.
- UAT Playwright:
  - `ok=true`;
  - filas cajas activas `2`;
  - filas terminales activas `2`;
  - filas asignaciones activas `2`;
  - terminales en `Todos` `3`;
  - asignaciones en `Todos` `3`;
  - terminales en `Historial` `1`;
  - asignaciones en `Historial` `1`;
  - muestra `Historico=true`;
  - botones editar `6`;
  - botones desactivar `6`;
  - formularios siguen llenandose al editar.
- Contrato:
  - no presiono Guardar;
  - no presiono Desactivar;
  - no abrio turno;
  - no movio caja;
  - no movio inventario.

## Evidencia UAT visual - Caja/Turnos apertura dry-run

- Fecha: 2026-07-04
- Alcance:
  - agregar bloque de apertura de turno a `/ventas/caja_turnos`;
  - validar apertura sin crear turno ni movimiento de caja;
  - mostrar autorizacion sugerida para ejecutar apertura real desde chat.
- Cambios:
  - vista `caja_turnos.php` incluye seccion `Apertura de turno`;
  - JS `caja_turnos.js` llama `/ventas/turno_apertura_dryrun_erp`;
  - genera texto `AUTORIZO ABRIR TURNO POS UAT...`;
  - no ejecuta apertura real desde UI.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\caja_turnos.php`: OK;
  - `node --check public\assets\js\custom\apps\erp\ventas\caja_turnos.js`: OK;
  - `node --check storage\uat\uat_ventas_pos_caja_turnos_playwright_uat.js`: OK.
- UAT visual:
  - `ok=true`;
  - URL final `/ventas/caja_turnos`;
  - status `200`;
  - apertura dry-run visible;
  - autorizacion de apertura visible;
  - opciones de caja apertura `2`;
  - KPI turnos abiertos `0`;
  - evidencia `public/storage/uat/pos_caja_turnos_uat.png`.
- Auditoria posterior:
  - configuracion POS `turnos_abiertos=0`;
  - preflight apertura `puede_abrir_turno=true`;
  - asignacion activa usuario `1`: almacen `5`, caja `2`, terminal `2`;
  - `turno_abierto_actual=null`;
  - bloqueos `[]`.
- Autorizacion sugerida:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS desde Caja/Turnos"
```

- Contrato:
  - no se abrio turno;
  - no se cerro turno;
  - no se movio caja;
  - no se movio inventario.

## Evidencia UAT - Apertura real turno Caja/Turnos

- Fecha: 2026-07-04
- Autorizacion recibida:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS desde Caja/Turnos"
```

- Primer intento:
  - bloqueado sin escritura porque MySQL no estaba activo;
  - mensaje: `Usuario sin asignacion POS activa`;
  - auditorias posteriores confirmaron conexion nula;
  - se levanto `mysqld` local y se repitio auditoria.
- Auditoria previa tras levantar MySQL:
  - asignacion activa `true`;
  - usuario `1`;
  - almacen `5`;
  - caja `2`;
  - terminal `2`;
  - turno abierto `false`.
- Ejecucion autorizada:
  - `ok=true`;
  - turno `id_turno_caja=13`;
  - folio `TUR-20260704-002-001`;
  - movimiento caja inicial `id_movimiento_caja=27`;
  - monto inicial `500`;
  - almacen `5`;
  - caja `2`.
- Auditoria posterior:
  - `turno_abierto=true`;
  - turnos abiertos `1`;
  - preflight apertura ahora bloquea doble apertura;
  - `turno_abierto_actual.id_turno_caja=13`;
  - bloqueo esperado: `Ya existe turno abierto para esta caja`.
- Correccion aplicada:
  - `VentasErp::aperturaTurnoDryRun` ahora valida doble turno abierto;
  - `/ventas/caja_turnos` consulta asignacion actual del usuario para amarrar apertura a caja asignada;
  - los selectores de apertura quedan visualmente deshabilitados para evitar selector libre.
- UAT visual posterior:
  - `ok=true`;
  - KPI turnos abiertos `1`;
  - apertura dry-run bloqueada;
  - no muestra autorizacion de nueva apertura;
  - no presiono apertura real desde UI;
  - no cerro turno;
  - no movio inventario.

## Evidencia UAT - Venta POS bloqueada por stock

- Fecha: 2026-07-04
- Autorizacion recibida:

```text
AUTORIZO EJECUTAR VENTA POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 id_sku=1760 cantidad=1 precio=295 pago=295 cliente="Cliente UAT POS Caja Turnos"
```

- Preflight venta:
  - asignacion activa `true`;
  - turno abierto `true`;
  - contexto: almacen `5`, caja `2`, turno `13`;
  - total estimado `295`;
  - pago `295`;
  - saldo `0`.
- Bloqueo:
  - `Existencia insuficiente`;
  - plan de salida `existencia_agregada`;
  - faltante `1`;
  - `puede_vender_real=false`.
- Decision:
  - no se ejecuto venta real;
  - no se creo venta;
  - no se creo pago;
  - no se movio caja por venta;
  - no se movio inventario por venta.
- Preflight stock:
  - SKU `1760`;
  - `TP-40352-500GR`;
  - almacen `5`;
  - cantidad sugerida `1`;
  - bloqueos `[]`.
- Autorizacion sugerida:

```text
AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-UAT-20260704-A5-S1760-CAJA-TURNOS
```

## Evidencia UAT - Stock y venta real con turno Caja/Turnos

- Fecha: 2026-07-04
- Stock autorizado:

```text
AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-UAT-20260704-A5-S1760-CAJA-TURNOS
```

- Resultado stock:
  - `ok=true`;
  - almacen `5`;
  - SKU `1760`;
  - cantidad `1`;
  - referencia `INV-INICIAL-POS-UAT-20260704-A5-S1760-CAJA-TURNOS`;
  - inventario inicial aplicado;
  - movimientos kardex `1`.
- Preflight venta posterior:
  - `puede_vender_real=true`;
  - turno abierto `13`;
  - caja `2`;
  - almacen `5`;
  - total `295`;
  - pagado `295`;
  - saldo `0`;
  - existencia asignada `id_existencia_inventario=34`, antes `1`, despues `0`;
  - bloqueos `[]`.
- Venta real ejecutada con autorizacion previa:
  - `ok=true`;
  - folio `POS-20260704-000001`;
  - `id_venta=13`;
  - estatus `pagada`;
  - cliente `Cliente UAT POS Caja Turnos`;
  - total `295`;
  - pago `295`.
- Inventario venta:
  - `id_movimiento_inventario=75`;
  - existencia `34`;
  - cantidad base `1`;
  - existencia anterior `1`;
  - existencia nueva `0`.
- Pago/caja:
  - `id_venta_pago=14`;
  - metodo `Efectivo`;
  - `id_movimiento_caja=28`;
  - monto `295`.
- Garantia:
  - `id_venta_detalle_garantia=10`;
  - resumen ticket `Sin garantia`.
- Ticket formal read-only:
  - generado correctamente;
  - 28 lineas;
  - sin hallazgos;
  - incluye tienda, caja, turno, cliente, pago e inventario trazado.
- Turno posterior:
  - turno `TUR-20260704-002-001`;
  - monto inicial `500`;
  - monto esperado `795`;
  - movimientos caja: apertura `500` + venta `295`;
  - sigue abierto.
- Preflight cierre:
  - monto contado `795`;
  - diferencia `0`;
  - bloqueos `[]`.
- Autorizacion sugerida:

```text
AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS Caja Turnos con venta POS-20260704-000001"
```

## Evidencia UAT - Cierre real turno Caja/Turnos

- Fecha: 2026-07-04
- Autorizacion recibida:

```text
AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS Caja Turnos con venta POS-20260704-000001"
```

- Resultado cierre:
  - `ok=true`;
  - turno `id_turno_caja=13`;
  - folio `TUR-20260704-002-001`;
  - almacen `5`;
  - caja `2`;
  - monto esperado `795`;
  - monto contado `795`;
  - diferencia `0`;
  - estatus `cerrado`.
- Resumen operativo:
  - ventas `1`;
  - total ventas `295`;
  - pagos `295`;
  - saldo `0`;
  - movimientos caja `2`;
  - apertura `500`;
  - ingreso venta POS `295`.
- Auditoria post-cierre read-only:
  - fecha cierre registrada `2026-07-04 08:18:30`;
  - usuario cierre `1`;
  - hallazgos `[]`;
  - asignacion actual usuario sigue activa en almacen `5`, caja `2`, terminal `2`;
  - no hay turno abierto para esta caja.
- Configuracion POS read-only:
  - `turno_abierto=false`;
  - `turnos_abiertos=0`;
  - hallazgos `[]`.
- Ticket formal posterior:
  - venta `POS-20260704-000001`;
  - ticket generado sin hallazgos;
  - incluye fecha de cierre del turno;
  - conserva trazabilidad de pago, caja, garantia e inventario.
- Contrato:
  - no creo ventas nuevas;
  - no creo pagos nuevos;
  - no movio inventario adicional;
  - no modifico ecommerce ni legacy.

## Evidencia UAT - UI arqueo Caja/Turnos sin escritura

- Fecha: 2026-07-04
- Cambios implementados:
  - `/ventas/caja_turnos` agrega bloque de arqueo rapido;
  - efectivo por denominaciones: `1000`, `500`, `200`, `100`, `50`, `20`, `10`, `5`, `2`, `1`, `0.50`;
  - otros metodos: tarjeta, transferencia, vales/saldo a favor;
  - el total calculado alimenta `monto_contado` para el dry-run de cierre;
  - el campo `monto_contado` queda readonly para evitar descuadre entre arqueo y simulacion;
  - el resultado del dry-run muestra resumen por efectivo/tarjeta/transferencia/vales;
  - el cierre real sigue requiriendo autorizacion escrita fuera de UI.
- Archivos:
  - `app/vistas/paginas/apps/erp/ventas/caja_turnos.php`;
  - `public/assets/js/custom/apps/erp/ventas/caja_turnos.js`;
  - `storage/uat/uat_ventas_pos_caja_turnos_playwright_uat.js`.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\caja_turnos.php`: sin errores;
  - `node --check public\assets\js\custom\apps\erp\ventas\caja_turnos.js`: sin errores;
  - `node --check storage\uat\uat_ventas_pos_caja_turnos_playwright_uat.js`: sin errores.
- Auditoria MySQL directa posterior:
  - tablas `erp_pos_%` presentes;
  - `erp_pos_turnos` abiertos: `0`;
  - turno `13` / `TUR-20260704-002-001` sigue `cerrado`;
  - monto esperado `795`;
  - monto contado `795`;
  - diferencia `0`.
- Auditoria configuracion read-only posterior:
  - `schema_pendiente=false`;
  - asignacion activa usuario `1`: almacen `5`, caja `2`, terminal `2`;
  - `turno_abierto=false`;
  - `turnos_abiertos=0`;
  - hallazgos `[]`.
- Incidencia UAT visual:
  - Playwright local se quedo en timeout al abrir/cerrar navegador headless;
  - se ajusto el script a `domcontentloaded`, timeouts cortos y `finally`;
  - no se usa como evidencia funcional hasta que vuelva a completar;
  - no se presiono autorizacion real desde UI;
  - no se abrio turno;
  - no se cerro turno;
  - no se movio caja;
  - no se movio inventario.

## Evidencia UAT - Cierre real Caja/Turnos POS UI expuesto con guardas

- Fecha: 2026-07-04
- Autorizacion recibida:

```text
AUTORIZO EXPONER CIERRE REAL CAJA TURNOS POS UI usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_CIERRE_UI para UAT POS
```

- Cambios backend:
  - `VentasErp::cerrarTurnoRealPos`;
  - endpoint `/ventas/turno_cierre_real_erp`;
  - permiso requerido `ventas.operar`;
  - usuario tomado desde sesion;
  - auditoria explicita `ventas / pos_turno_cerrar`.
- Guardas de cierre real:
  - requiere conexion MySQL;
  - requiere tablas POS base;
  - requiere asignacion POS activa del usuario;
  - requiere turno abierto de la caja asignada;
  - bloquea si el turno solicitado no coincide con el turno abierto;
  - requiere confirmacion exacta `CERRAR TURNO`;
  - bloquea montos negativos;
  - hace `SELECT ... FOR UPDATE`;
  - re-ejecuta `cierreTurnoDryRun`;
  - actualiza solo `erp_pos_turnos`;
  - no crea ventas;
  - no crea pagos;
  - no mueve inventario;
  - permite diferencia y la conserva para reportes.
- Cambios UI:
  - el dry-run de corte ya no solo sugiere frase para chat;
  - muestra panel `Cierre real de turno`;
  - pide observaciones;
  - pide confirmacion `CERRAR TURNO`;
  - manda el total calculado por arqueo;
  - recarga Caja/Turnos despues de cierre exitoso.
- Script de guardia:
  - `storage/uat/uat_ventas_pos_cierre_real_ui_guard_readonly.php`;
  - usa confirmacion incorrecta a proposito;
  - resultado `ok=true`;
  - respuesta `warning`;
  - bloqueos:
    - `Escribe CERRAR TURNO para confirmar`;
    - `Usuario sin turno abierto`.
- Validaciones:
  - `php -l app/controladores/Ventas.php`: sin errores;
  - `php -l app/modelos/VentasErp.php`: sin errores;
  - `php -l app/vistas/paginas/apps/erp/ventas/caja_turnos.php`: sin errores;
  - `node --check public/assets/js/custom/apps/erp/ventas/caja_turnos.js`: sin errores;
  - `php -l storage/uat/uat_ventas_pos_cierre_real_ui_guard_readonly.php`: sin errores.
- Auditoria MySQL posterior:
  - turnos abiertos `0`;
  - turno `TUR-20260704-002-001` sigue `cerrado`;
  - esperado `795`;
  - contado `795`;
  - diferencia `0`.
- Pendiente para probar escritura real desde UI:
  - abrir turno nuevo;
  - cargar stock si se probara venta;
  - simular corte;
  - confirmar `CERRAR TURNO` desde pantalla;
  - validar auditoria, estado cerrado y reportes de diferencia.

## Evidencia UAT - Apertura turno para cierre real desde UI

- Fecha: 2026-07-04
- Autorizacion recibida:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS para cierre real desde UI"
```

- Resultado:
  - `ok=true`;
  - turno `id_turno_caja=14`;
  - folio `TUR-20260704-002-002`;
  - movimiento caja inicial `id_movimiento_caja=29`;
  - usuario `1`;
  - almacen `5`;
  - caja `2`;
  - monto inicial `500`;
  - estatus `abierto`.
- Auditoria posterior:
  - configuracion POS read-only `turno_abierto=true`;
  - turnos abiertos `1`;
  - preflight cierre read-only con monto contado `500`;
  - monto esperado `500`;
  - diferencia `0`;
  - bloqueos `[]`.
- Prueba manual esperada:
  - entrar a `/ventas/caja_turnos`;
  - confirmar KPI `Turnos abiertos = 1`;
  - capturar arqueo con total `500`;
  - presionar `Simular corte sin cerrar`;
  - escribir `CERRAR TURNO`;
  - presionar `Cerrar turno real`;
  - confirmar que el turno queda cerrado y `Turnos abiertos = 0`.

## Evidencia UAT - Script cierre real UI preparado y bloqueado por token

- Fecha: 2026-07-04
- Script creado:
  - `storage/uat/uat_ventas_pos_cierre_real_ui_playwright_authorized.js`.
- Contrato:
  - bloqueado por defecto;
  - requiere `POS_UAT_AUTORIZAR=VENTAS_POS_CAJA_CIERRE_UI_REAL`;
  - navega a `/ventas/caja_turnos`;
  - captura arqueo por denominaciones;
  - simula corte;
  - escribe `CERRAR TURNO`;
  - presiona cierre real;
  - toma captura posterior;
  - no debe ejecutarse sin autorizacion fuerte.
- Validacion sin token:
  - `ok=false`;
  - modo `bloqueado`;
  - no abrio turno;
  - no cerro turno;
  - no movio caja;
  - no movio inventario.
- Auditoria posterior:
  - turnos abiertos `1`;
  - turno `14` / `TUR-20260704-002-002` sigue `abierto`;
  - monto esperado `500`;
  - monto contado `0`;
  - diferencia `0`.
- Autorizacion fuerte pendiente para ejecutarlo:

```text
AUTORIZO EJECUTAR CIERRE REAL CAJA TURNOS POS UI UAT usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_CIERRE_UI_REAL id_usuario=1 id_turno_caja=14 monto_contado=500
```

## Evidencia UAT - Intento cierre real UI bloqueado por entorno web local

- Fecha: 2026-07-04
- Autorizacion recibida:

```text
AUTORIZO EJECUTAR CIERRE REAL CAJA TURNOS POS UI UAT usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_CIERRE_UI_REAL id_usuario=1 id_turno_caja=14 monto_contado=500
```

- Intento 1:
  - URL `http://dashboard.com.local/ventas/caja_turnos`;
  - resultado Playwright: `ERR_CONNECTION_REFUSED`;
  - causa operativa: Apache no estaba escuchando en puerto 80.
- Intento 2:
  - se intento levantar Apache local;
  - Apache no quedo vivo aunque `httpd -t` reporto `Syntax OK`.
- Intento 3:
  - se levanto PHP server temporal en `127.0.0.1:8000`;
  - puerto respondio;
  - URL `http://127.0.0.1:8000/ventas/caja_turnos`;
  - resultado: `ERR_TOO_MANY_REDIRECTS`.
- Intento 4:
  - URL `http://dashboard.com.local:8000/ventas/caja_turnos`;
  - resultado: `ERR_TOO_MANY_REDIRECTS`.
- Decision:
  - no se forzo cierre fuera de UI con esta autorizacion;
  - se detuvo el servidor PHP temporal;
  - el turno queda abierto para prueba manual o nueva autorizacion de cierre por aplicador.
- Auditoria posterior:
  - turnos abiertos `1`;
  - turno `14` / `TUR-20260704-002-002` sigue `abierto`;
  - monto inicial `500`;
  - monto esperado `500`;
  - monto contado `0`;
  - diferencia `0`.
- Preflight posterior:
  - asignacion activa usuario `1`;
  - turno abierto `true`;
  - monto contado propuesto `500`;
  - diferencia `0`;
  - bloqueos `[]`.
- Contrato conservado:
  - no se cerro turno;
  - no se movio caja;
  - no se movio inventario;
  - no se creo venta ni pago.

## Evidencia UAT - Cierre turno tras bloqueo UI local

- Fecha: 2026-07-04
- Autorizacion recibida:

```text
AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=500 observaciones="Cierre UAT POS turno TUR-20260704-002-002 tras bloqueo UI local"
```

- Resultado cierre:
  - `ok=true`;
  - turno `id_turno_caja=14`;
  - folio `TUR-20260704-002-002`;
  - almacen `5`;
  - caja `2`;
  - monto esperado `500`;
  - monto contado `500`;
  - diferencia `0`;
  - estatus `cerrado`.
- Resumen operativo:
  - ventas `0`;
  - pagos `0`;
  - movimientos caja `1`;
  - movimiento inicial `id_movimiento_caja=29`;
  - monto movimiento inicial `500`.
- Auditoria post-cierre:
  - usuario apertura `1`;
  - usuario cierre `1`;
  - fecha cierre registrada `2026-07-04 23:17:32`;
  - observacion cierre `Cierre UAT POS turno TUR-20260704-002-002 tras bloqueo UI local`;
  - hallazgos `[]`.
- Configuracion POS posterior:
  - asignacion activa usuario `1`: almacen `5`, caja `2`, terminal `2`;
  - `turno_abierto=false`;
  - turnos abiertos `0`.
- Contrato:
  - no creo venta;
  - no creo pago;
  - no movio inventario;
  - no modifico ecommerce ni legacy.

## Evidencia UAT - Corte imprimible Caja/Turnos read-only

- Fecha: 2026-07-04
- Cambios implementados:
  - modelo `VentasErp::corteTurnoFormalReadOnly`;
  - endpoint `/ventas/corte_turno_readonly_erp`;
  - panel `Corte imprimible` en `/ventas/caja_turnos`;
  - consulta por folio o `id_turno_caja`;
  - impresion desde ventana separada;
  - script `storage/uat/uat_ventas_pos_corte_turno_readonly.php`.
- Turno probado:
  - `TUR-20260704-002-002`;
  - `id_turno_caja=14`;
  - estatus `cerrado`;
  - esperado `500`;
  - contado `500`;
  - diferencia `0`.
- Resultado UAT read-only:
  - `ok=true`;
  - lineas corte `34`;
  - hallazgos `[]`;
  - preview incluye:
    - `CORTE DE CAJA POS`;
    - folio turno;
    - tienda/caja;
    - usuarios apertura/cierre;
    - inicial/esperado/contado/diferencia.
- Validaciones:
  - `php -l app/controladores/Ventas.php`: sin errores;
  - `php -l app/modelos/VentasErp.php`: sin errores;
  - `php -l app/vistas/paginas/apps/erp/ventas/caja_turnos.php`: sin errores;
  - `node --check public/assets/js/custom/apps/erp/ventas/caja_turnos.js`: sin errores;
  - `php -l storage/uat/uat_ventas_pos_corte_turno_readonly.php`: sin errores.
- Contrato:
  - no escribe BD;
  - no cierra turno;
  - no ajusta caja;
  - no mueve inventario;
  - no modifica ecommerce ni legacy.

## Evidencia UAT - Reportes ejecutivos caja con corte

- Fecha: 2026-07-04
- Cambios implementados:
  - KPIs adicionales en `/ventas/reportes`:
    - ventas acumuladas;
    - movimientos de caja;
    - faltante promedio;
    - sobrante promedio.
  - Cada turno del reporte ahora tiene accion de corte imprimible.
  - Modal de corte read-only con boton imprimir.
  - Script `storage/uat/uat_ventas_pos_reportes_caja_readonly.php`.
- Backend ajustado:
  - `VentasErp::reporteCajaPosReadOnly` agrega:
    - `ventas_total`;
    - `ventas_operaciones`;
    - `movimientos_count`;
    - `faltante_promedio`;
    - `sobrante_promedio`.
- UAT read-only:
  - filtros:
    - fecha desde `2026-06-25`;
    - fecha hasta `2026-07-05`;
    - almacen `5`;
    - caja `2`.
  - resultado `ok=true`;
  - turnos `14`;
  - turnos con diferencia `1`;
  - faltantes total `10`;
  - sobrantes total `0`;
  - diferencia neta `-10`;
  - ventas total `3805`;
  - ventas operaciones `13`;
  - movimientos caja `29`;
  - corte `TUR-20260704-002-002` generado con `34` lineas y hallazgos `[]`.
- Validaciones:
  - `php -l app/modelos/VentasErp.php`: sin errores;
  - `php -l app/vistas/paginas/apps/erp/ventas/reportes.php`: sin errores;
  - `node --check public/assets/js/custom/apps/erp/ventas/reportes.js`: sin errores;
  - `php -l storage/uat/uat_ventas_pos_reportes_caja_readonly.php`: sin errores.
- Contrato:
  - no escribe BD;
  - no cierra turno;
  - no resuelve diferencias;
  - no mueve inventario;
  - no modifica ecommerce ni legacy.

## Evidencia UAT - Permisos diferencias caja POS sembrados

- Fecha: 2026-07-03
- Autorizacion recibida:

```text
AUTORIZO SEMBRAR PERMISOS DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_PERMISOS para UAT POS
```

- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_diferencias_permisos_apply_authorized.php --autorizar=VENTAS_POS_CAJA_DIFERENCIAS_PERMISOS --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql
```

- Resultado:
  - `ok=true`;
  - permisos total `3`;
  - relaciones intentadas `11`;
  - roles detectados: `administrador_erp`, `auditor`, `direccion`, `finanzas_contabilidad`, `ventas`;
  - no asigna usuarios directos.
- Auditoria posterior:
  - `ventas.caja_diferencias.ver`: existe;
  - `ventas.caja_diferencias.revisar`: existe;
  - `ventas.caja_diferencias.resolver`: existe;
  - faltantes `[]`.
- Impacto:
  - no toca turnos;
  - no mueve caja;
  - no mueve inventario.
- Nota:
  - la sesion actual puede requerir cierre de sesion o refresco de permisos para reflejar los nuevos permisos.

## Evidencia UAT visual - Permisos diferencias caja POS

- Fecha: 2026-07-03
- Resultado:
  - `ok=true`;
  - login local correcto;
  - URL final `/ventas/reportes`;
  - filtro estado revision existe;
  - estado seleccionado `todos`;
  - diferencia explicada visible;
  - filas seguimiento `1`;
  - botones resolver visibles `0`, esperado porque el expediente esta cerrado;
  - auditoria read-only confirma permisos existentes y faltantes `[]`.
- Impacto:
  - no ejecuta resolucion;
  - no mueve caja;
  - no mueve inventario.
- Evidencia:
  - `public/storage/uat/pos_reportes_diferencias_uat.png`.

## Evidencia tecnica - Cierre permiso resolver diferencias caja POS

- Fecha: 2026-07-03
- Cambio:
  - endpoint resolver deja de aceptar compatibilidad temporal con `ventas.operar`;
  - controlador requiere `ventas.caja_diferencias.resolver`;
  - modelo requiere `ventas.caja_diferencias.resolver`.
- Validaciones:
  - usuario UAT `1` tiene `ventas.caja_diferencias.ver=true`;
  - usuario UAT `1` tiene `ventas.caja_diferencias.revisar=true`;
  - usuario UAT `1` tiene `ventas.caja_diferencias.resolver=true`;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - UAT visual reportes `ok=true`.
- Impacto:
  - no ejecuta resolucion;
  - no mueve caja;
  - no mueve inventario.

## Evidencia UAT - Expediente revision diferencia caja POS registrado

- Fecha: 2026-07-03
- Autorizacion recibida:

```text
AUTORIZO REGISTRAR REVISION DIFERENCIA CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIA_REVISION_REAL id_usuario=1 id_turno_caja=12 motivo="UAT faltante de caja controlado" responsable="Supervisor UAT" evidencia_referencia=FALTANTE-UAT-001 para UAT POS
```

- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_diferencia_revision_apply_authorized.php --autorizar=VENTAS_POS_CAJA_DIFERENCIA_REVISION_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_turno_caja=12 --motivo="UAT faltante de caja controlado" --responsable="Supervisor UAT" --evidencia_referencia=FALTANTE-UAT-001
```

- Resultado:
  - `ok=true`;
  - `id_diferencia_revision=1`;
  - folio `DIF-CAJ-20260703-000001`;
  - turno `TUR-20260703-002-002`;
  - tipo `faltante`;
  - monto `-10`;
  - estatus `pendiente_revision`;
  - responsable `Supervisor UAT`;
  - evidencia referencia `FALTANTE-UAT-001`.
- Verificacion posterior:
  - `storage/uat/uat_ventas_pos_diferencias_caja_readonly.php --compact=1` devuelve `1` diferencia;
  - resumen: faltantes total `10`, diferencia neta `-10`;
  - primer registro: `id_diferencia_revision=1`, estado `pendiente_revision`;
  - `storage/uat/uat_ventas_pos_reportes_caja_readonly.php --solo_diferencias=1 --compact=1` conserva `1` turno con diferencia;
  - reporte: faltantes total `10`, diferencia neta `-10`.
- Impacto:
  - no modifica `erp_pos_turnos`;
  - no mueve caja;
  - no mueve inventario;
  - deja trazabilidad administrativa para resolver el faltante.

## Evidencia tecnica - Resolucion diferencia caja POS preparada

- Fecha: 2026-07-03
- Objetivo:
  - resolver administrativamente un expediente de diferencia de caja sin alterar el cierre historico.
- Cambios:
  - `VentasErp::resolverRevisionDiferenciaCajaPosReal`;
  - `storage/uat/uat_ventas_pos_diferencia_revision_resolver_apply_authorized.php`.
- Reglas:
  - requiere usuario;
  - requiere folio o expediente;
  - decisiones permitidas: `explicada`, `aceptada`, `ajustada`, `escalada`, `cancelada`;
  - requiere motivo;
  - solo permite resolver desde `pendiente_revision` o `en_revision`;
  - no modifica turno;
  - no mueve caja;
  - no mueve inventario.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_diferencia_revision_resolver_apply_authorized.php`: sin errores;
  - aplicador sin parametros: bloqueado, no escribio BD;
  - bandeja read-only posterior conserva `DIF-CAJ-20260703-000001` en `pendiente_revision`.
- Autorizacion requerida:

```text
AUTORIZO RESOLVER REVISION DIFERENCIA CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIA_RESOLVER_REAL id_usuario=1 folio=DIF-CAJ-20260703-000001 decision=explicada motivo="UAT faltante explicado sin ajuste de caja" evidencia_referencia=FALTANTE-UAT-001 para UAT POS
```

## Evidencia UAT - Resolucion diferencia caja POS ejecutada

- Fecha: 2026-07-03
- Autorizacion recibida:

```text
AUTORIZO RESOLVER REVISION DIFERENCIA CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIA_RESOLVER_REAL id_usuario=1 folio=DIF-CAJ-20260703-000001 decision=explicada motivo="UAT faltante explicado sin ajuste de caja" evidencia_referencia=FALTANTE-UAT-001 para UAT POS
```

- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_diferencia_revision_resolver_apply_authorized.php --autorizar=VENTAS_POS_CAJA_DIFERENCIA_RESOLVER_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --folio=DIF-CAJ-20260703-000001 --decision=explicada --motivo="UAT faltante explicado sin ajuste de caja" --evidencia_referencia=FALTANTE-UAT-001
```

- Resultado:
  - `ok=true`;
  - `id_diferencia_revision=1`;
  - folio `DIF-CAJ-20260703-000001`;
  - turno `TUR-20260703-002-002`;
  - decision `explicada`;
  - estatus `explicada`;
  - monto diferencia `-10`;
  - turno diferencia historica `-10`;
  - `no_modifica_turno=true`;
  - `no_mueve_caja=true`;
  - `no_mueve_inventario=true`.
- Verificacion posterior:
  - bandeja `estado_revision=todos`: `1` registro explicado;
  - bandeja `estado_revision=pendiente_revision`: `0` registros;
  - reporte caja `solo_diferencias=1`: `1` turno;
  - faltantes total `10`;
  - diferencia neta `-10`.
- Impacto:
  - el expediente queda cerrado administrativamente;
  - el faltante no desaparece de reportes;
  - queda listo para UI de supervision.

## Evidencia tecnica - UI resolucion diferencias caja POS

- Fecha: 2026-07-03
- Objetivo:
  - permitir que supervision resuelva expedientes de diferencia desde `/ventas/reportes`.
- Cambios:
  - endpoint `/ventas/reportes_diferencia_caja_resolver_erp`;
  - auditoria explicita `pos_diferencia_caja_resolver`;
  - filtro de estado en seccion `Seguimiento de diferencias`;
  - accion `Resolver` solo para expedientes abiertos;
  - modal con decision, motivo y evidencia.
- Reglas:
  - requiere permiso `ventas.operar`;
  - requiere CSRF;
  - no modifica turno;
  - no mueve caja;
  - no mueve inventario.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\core\Core.php`: sin errores;
  - `node --check public\assets\js\custom\apps\erp\ventas\reportes.js`: sin errores;
  - read-only diferencias devuelve `folio_revision=DIF-CAJ-20260703-000001`, estado `explicada`.

## Evidencia UAT visual - Reportes POS diferencias

- Fecha: 2026-07-03
- Script:

```powershell
C:\Users\aleja\AppData\Local\Programs\nodejs-portable-v24.16.0\node.exe storage\uat\uat_ventas_pos_reportes_playwright_uat.js
```

- Resultado:
  - `ok=true`;
  - login local correcto;
  - URL final `http://dashboard.com.local/ventas/reportes`;
  - filtro de estado existe;
  - estado seleccionado `todos`;
  - muestra diferencia explicada;
  - filas seguimiento `1`;
  - botones resolver visibles `0`, esperado al estar cerrada;
  - no ejecuta resolucion;
  - no mueve caja;
  - no mueve inventario.
- Evidencia visual:
  - `public/storage/uat/pos_reportes_diferencias_uat.png`.
- Hallazgo menor:
  - consola reporta recursos externos bloqueados por red (`ERR_NETWORK_ACCESS_DENIED`);
  - no bloquea carga de reportes ni validacion del flujo.
- Ajuste posterior:
  - copy del reporte aclara que los importes son solo lectura y que el cierre administrativo no mueve caja ni inventario.

## Evidencia tecnica - Permisos diferencias caja POS preparados

- Fecha: 2026-07-03
- Objetivo:
  - separar permisos para ver, revisar y resolver diferencias de caja.
- Permisos:
  - `ventas.caja_diferencias.ver`;
  - `ventas.caja_diferencias.revisar`;
  - `ventas.caja_diferencias.resolver`.
- Cambios:
  - permisos declarados en `SeguridadEsquema.php`;
  - endpoint resolver requiere `ventas.caja_diferencias.resolver`;
  - modelo valida `ventas.caja_diferencias.resolver`;
  - script read-only de auditoria;
  - script protegido de siembra.
- Auditoria actual:
  - tablas de seguridad existen;
  - permisos no existen en BD;
  - relaciones por rol no existen;
  - aplicador sin parametros queda bloqueado.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\modelos\SeguridadEsquema.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_diferencias_permisos_readonly.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_diferencias_permisos_apply_authorized.php`: sin errores.
- Autorizacion requerida:

```text
AUTORIZO SEMBRAR PERMISOS DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_PERMISOS para UAT POS
```

## Evidencia UAT - Reportes POS por empleado y caja

- Fecha: 2026-07-03
- Alcance:
  - endpoint read-only `/ventas/reportes_caja_erp`;
  - vista `/ventas/reportes`;
  - agregado por empleado;
  - agregado por sucursal/caja.
- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reportes_caja_readonly.php --fecha_desde=2026-06-01 --fecha_hasta=2026-07-04 --limite=20
```

- Resultado:
  - `ok=true`;
  - `read_only=true`;
  - turnos consultados `11`;
  - turnos con diferencia `0`;
  - faltantes total `0`;
  - sobrantes total `0`;
  - primer usuario: `Usuario 1`, `11` turnos, `0` diferencias;
  - primera caja: almacen `MASCOTAS971`, caja `CJ-MASCOTAS971-01`, `11` turnos, `0` diferencias.
- Contrato:
  - no escribe BD;
  - no cierra turno;
  - no mueve caja;
  - no mueve inventario.

## Evidencia UAT - Reportes POS filtrados por sucursal/caja

- Fecha: 2026-07-03
- Alcance:
  - filtros UI por sucursal y caja;
  - consulta read-only por `id_almacen` e `id_caja`;
  - descarga CSV local de turnos filtrados.
- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reportes_caja_readonly.php --fecha_desde=2026-06-01 --fecha_hasta=2026-07-04 --id_almacen=5 --id_caja=2 --limite=20
```

- Resultado:
  - `ok=true`;
  - turnos consultados `11`;
  - almacen `MASCOTAS971`;
  - caja `CJ-MASCOTAS971-01`;
  - turnos con diferencia `0`.
- Consulta `solo_diferencias=1`:
  - `ok=true`;
  - turnos consultados `0`;
  - esperado porque la muestra actual no tiene faltantes ni sobrantes.
- Contrato:
  - no escribe BD;
  - no cierra turno;
  - no mueve caja;
  - no mueve inventario.

## Evidencia UAT - Apertura turno y stock para prueba diferencia caja

- Fecha: 2026-07-03
- Autorizacion recibida:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS para prueba diferencia caja"

AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-UAT-DIFERENCIA-01
```

- Prevalidaciones:
  - apertura turno `puede_abrir_turno=true`;
  - asignacion activa usuario `1`, almacen `5`, caja `2`, terminal `2`;
  - stock SKU `1760` sin bloqueos;
  - SKU `TP-40352-500GR`, precio `295`, permite venta fraccionaria.
- Apertura ejecutada:
  - `ok=true`;
  - `id_turno_caja=12`;
  - folio `TUR-20260703-002-002`;
  - movimiento caja `25`;
  - monto inicial `500`.
- Stock ejecutado:
  - `ok=true`;
  - SKU `1760`;
  - cantidad `1`;
  - referencia `INV-INICIAL-POS-UAT-DIFERENCIA-01`;
  - movimientos inventario `1`;
  - etiquetas generadas `0`.
- Revalidacion venta:
  - `puede_vender_real=true`;
  - turno abierto `12`;
  - total `295`;
  - pago `295`;
  - salida existencia `34`, antes `1`, despues `0`;
  - bloqueos `[]`.
- Simulacion cierre diferencias:
  - esperado `500` antes de venta;
  - cuadrado contado `500`, diferencia `0`;
  - faltante contado `490`, diferencia `-10`;
  - sobrante contado `510`, diferencia `10`;
  - todos sin bloqueos;
  - faltante/sobrante generan aviso y alimentan reportes.
- Contrato:
  - la apertura y stock fueron escrituras autorizadas;
  - las simulaciones posteriores fueron read-only.

## Evidencia UAT - Venta y cierre con faltante POS

- Fecha: 2026-07-03
- Autorizacion recibida:

```text
AUTORIZO EJECUTAR VENTA POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 id_sku=1760 cantidad=1 precio=295 pago=295 cliente="Cliente UAT POS diferencia caja"

AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=785 observaciones="Cierre UAT POS ciclo TUR-20260703-002-002"
```

- Prevalidacion venta:
  - `puede_vender_real=true`;
  - asignacion activa `true`;
  - turno abierto `12`;
  - subtotal `295`;
  - pago `295`;
  - saldo `0`;
  - existencia `34` de `1` a `0`;
  - bloqueos `[]`.
- Venta ejecutada:
  - `ok=true`;
  - folio `POS-20260703-000002`;
  - `id_venta=12`;
  - estatus `pagada`;
  - cliente `Cliente UAT POS diferencia caja`;
  - total `295`;
  - pagado `295`;
  - saldo `0`.
- Inventario:
  - `id_existencia_inventario=34`;
  - `id_movimiento_inventario=73`;
  - cantidad `1`;
  - existencia anterior `1`;
  - existencia nueva `0`.
- Caja/pago:
  - `id_venta_pago=13`;
  - `id_movimiento_caja=26`;
  - metodo `Efectivo`;
  - monto `295`.
- Garantia:
  - `id_venta_detalle_garantia=9`;
  - resumen ticket `Sin garantia`.
- Prevalidacion cierre:
  - turno `TUR-20260703-002-002`;
  - esperado `795`;
  - contado `785`;
  - diferencia `-10`;
  - bloqueos `[]`.
- Cierre ejecutado:
  - `ok=true`;
  - `id_turno_caja=12`;
  - folio `TUR-20260703-002-002`;
  - monto esperado `795`;
  - monto contado `785`;
  - diferencia `-10`;
  - ventas `1`;
  - total ventas `295`;
  - pagos `295`;
  - movimientos caja:
    - entrada inicial `500`;
    - ingreso venta POS `295`.
- Reporte caja read-only:
  - filtro `solo_diferencias=1`;
  - turnos `1`;
  - turnos con diferencia `1`;
  - faltantes total `10`;
  - sobrantes total `0`;
  - diferencia neta `-10`;
  - turno clasificado como `faltante`;
  - empleado `Usuario 1`, `100%` turnos con diferencia en el filtro;
  - caja `CJ-MASCOTAS971-01`, `100%` turnos con diferencia en el filtro.
- Configuracion posterior:
  - turno abierto `false`;
  - turnos abiertos `0`;
  - hallazgos `[]`.
- Ticket formal read-only:
  - folio `POS-20260703-000002`;
  - `28` lineas;
  - hallazgos `[]`;
  - inventario trazado `1` movimiento;
  - ticket no fiscal.
- Post-venta read-only:
  - detalle suma `295`;
  - pagos registrados `295`;
  - garantias `1`;
  - trazabilidades `1`;
  - hallazgos `[]`.

## Evidencia UAT - Bandeja read-only de diferencias de caja

- Fecha: 2026-07-03
- Alcance:
  - endpoint `/ventas/reportes_diferencias_caja_erp`;
  - modelo `VentasErp::diferenciasCajaPendientesReadOnly`;
  - script `storage/uat/uat_ventas_pos_diferencias_caja_readonly.php`;
  - seccion `Seguimiento de diferencias` en `/ventas/reportes`.
- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_diferencias_caja_readonly.php --fecha_desde=2026-06-01 --fecha_hasta=2026-07-04 --id_almacen=5 --id_caja=2 --limite=20
```

- Resultado:
  - `ok=true`;
  - `read_only=true`;
  - `schema_revision_pendiente=true`;
  - total registros `1`;
  - faltantes total `10`;
  - sobrantes total `0`;
  - diferencia neta `-10`;
  - primer turno `TUR-20260703-002-002`;
  - tipo `faltante`;
  - estado `pendiente_revision`.
- Contrato:
  - no escribe BD;
  - no resuelve diferencias;
  - no ajusta caja;
  - no crea evidencia.

## Evidencia tecnica - DDL revision diferencias caja preparado

- Fecha: 2026-07-03
- Autorizacion recibida:

```text
AUTORIZO PREPARAR DDL REVISION DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_REVISION_DDL para UAT POS
```

- Cambios preparados:
  - `VentasErpEsquema::planActualizarRevisionDiferenciasCajaPos`;
  - `VentasErpEsquema::auditarRevisionDiferenciasCajaPos`;
  - endpoint `/ventas/esquema_auditar_revision_diferencias_caja_pos`;
  - endpoint `/ventas/esquema_actualizar_revision_diferencias_caja_pos`;
  - `storage/uat/uat_ventas_pos_diferencias_revision_schema_readonly.php`;
  - `storage/uat/uat_ventas_pos_diferencias_revision_schema_apply_authorized.php`.
- Auditoria read-only:
  - tabla `erp_pos_turnos_diferencias_revision`: no existe;
  - columnas esperadas: no existen;
  - indices esperados: no existen;
  - plan devuelve SQL `CREATE TABLE` sin ejecutar.
- Prueba negativa:
  - aplicador sin parametros queda bloqueado;
  - no crea tabla;
  - no modifica turnos;
  - no mueve caja.
- Validaciones:
  - `php -l app/modelos/VentasErpEsquema.php`: sin errores;
  - `php -l app/controladores/Ventas.php`: sin errores;
  - scripts read-only/apply: sin errores.
- Siguiente autorizacion:

```text
AUTORIZO APLICAR DDL REVISION DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_REVISION_DDL para UAT POS
```

## Evidencia UAT - DDL revision diferencias caja aplicado

- Fecha: 2026-07-03
- Autorizacion recibida:

```text
AUTORIZO APLICAR DDL REVISION DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_REVISION_DDL para UAT POS
```

- Resultado:
  - `ok=true`;
  - tabla `erp_pos_turnos_diferencias_revision` creada;
  - columnas esperadas existen;
  - indices esperados existen;
  - respaldo UAT POS vigente validado.
- Reglas cumplidas:
  - no modifica `erp_pos_turnos`;
  - no mueve caja;
  - no resuelve diferencias;
  - no crea movimientos de dinero ni inventario.
- Verificacion posterior:
  - bandeja de diferencias `schema_revision_pendiente=false`;
  - turno `TUR-20260703-002-002`;
  - faltante total `10`;
  - diferencia neta `-10`;
  - estado `pendiente_revision`;
  - `id_diferencia_revision=null`, esperado hasta crear expediente formal.
- Reporte caja posterior:
  - `solo_diferencias=1`;
  - turnos `1`;
  - faltantes total `10`;
  - sobrantes total `0`;
  - diferencia neta `-10`.

## Evidencia UAT 2026-07-03 - revalidacion read-only post venta POS-20260703-000001

- Se revalido sin escritura el turno `TUR-20260703-002-001`.
- Preflight de cierre:
  - turno abierto `true`;
  - esperado `795`;
  - contado simulado `795`;
  - diferencia `0`;
  - bloqueos `[]`.
- Ticket formal `POS-20260703-000001`:
  - `28` lineas;
  - hallazgos `[]`;
  - pago efectivo `295`;
  - garantia snapshot `Sin garantia`;
  - kardex/movimiento inventario `71`;
  - existencia `34` de `1` a `0`.
- Venta nueva SKU `1760`:
  - `puede_vender_real=false`;
  - bloqueo `Existencia insuficiente`;
  - comportamiento esperado porque el stock UAT quedo en `0`.
- Readiness compacto:
  - cierre diferencia `0`;
  - reserva/apartado bloqueada por stock;
  - abono fake bloqueado por `Pedido/apartado no encontrado`;
  - devoluciones fisicas pendientes `1`.
- Siguiente autorizacion operativa si se desea cerrar caja:
  - `AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS post venta POS-20260703-000001"`.

## Evidencia UAT 2026-07-03 - configuracion POS read-only preparada

- Se ejecuto `uat_ventas_pos_configuracion_readonly.php --compact=1 --id_usuario=1`.
- Resultado:
  - `ok=true`;
  - `read_only=true`;
  - almacen `5`;
  - caja `2`;
  - terminal `2`;
  - asignacion activa `true`;
  - turno abierto `true`;
  - cajas `2`;
  - terminales `2`;
  - asignaciones `2`;
  - hallazgos `[]`.
- La asignacion dry-run muestra `Ya existe una asignacion activa...`, tratado como OK porque valida la asignacion actual.
- El preflight ahora devuelve autorizacion sugerida para CRUD real:
  - `AUTORIZO PREPARAR CRUD REAL CONFIGURACION POS usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_CRUD para UAT POS`.
- Ajuste UX:
  - `/ventas/pos_configuracion` muestra `Validar sin crear = no guarda registros`.
- No hubo escrituras de BD.

## Evidencia UAT 2026-07-03 - cierre real turno TUR-20260703-002-001

- Autorizacion recibida:

```text
AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS post venta POS-20260703-000001"
```

- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_cierre_apply_authorized.php --autorizar=VENTAS_POS_TURNO_CIERRE --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --monto_contado=795 --observaciones="Cierre UAT POS post venta POS-20260703-000001"
```

- Resultado:
  - `ok=true`;
  - turno `TUR-20260703-002-001`;
  - `id_turno_caja=11`;
  - almacen `5`;
  - caja `2`;
  - monto esperado `795`;
  - monto contado `795`;
  - diferencia `0`.
- Post-cierre read-only:
  - estatus `cerrado`;
  - fecha cierre `2026-07-03 21:32:47`;
  - ventas `295`;
  - pagos `295`;
  - movimientos caja `795`;
  - ventas count `1`;
  - pagos count `1`;
  - movimientos count `2`;
  - hallazgos `[]`.
- Readiness posterior:
  - turno abierto `false`;
  - reserva bloqueada por `Selecciona turno abierto de caja` y `Existencia insuficiente`;
  - abono bloqueado por `Selecciona turno abierto de caja` y `Pedido/apartado no encontrado`;
  - devoluciones fisicas pendientes `1`.

## Evidencia tecnica 2026-07-03 - CRUD real Configuracion POS preparado

- Autorizacion recibida:

```text
AUTORIZO PREPARAR CRUD REAL CONFIGURACION POS usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_CRUD para UAT POS
```

- Cambios preparados:
  - `VentasErp::configuracionCajaGuardarReal`;
  - `VentasErp::configuracionTerminalGuardarReal`;
  - `VentasErp::configuracionAsignacionGuardarReal`;
  - `VentasErp::configuracionPosDesactivarReal`;
  - endpoints protegidos en `Ventas.php`;
  - script protegido `storage/uat/uat_ventas_pos_configuracion_apply_authorized.php`.
- Guardrails:
  - no abre turno;
  - no mueve caja;
  - no crea venta;
  - no mueve inventario;
  - desactivacion es baja logica;
  - bloquea desactivar caja/terminal/asignacion con turno abierto;
  - valida tienda/almacen vendible, caja, terminal, duplicados y usuario activo.
- Prueba negativa:
  - script sin parametros: bloqueado, no escribe;
  - script con token pero sin datos de caja: warning por campos faltantes, no inserta.
- Conteos posteriores:
  - cajas `2`;
  - terminales `2`;
  - asignaciones `2`;
  - turnos abiertos `0`.
- Pendiente:
  - probar alta real controlada de caja/terminal/asignacion con datos UAT concretos;
  - conectar botones `Guardar` en UI solo despues de esa UAT y permisos finos.

## Evidencia tecnica 2026-07-03 - reportes caja y cierre con diferencia

- Regla operativa documentada:
  - la caja puede cerrarse con diferencia distinta de `0`;
  - faltante/sobrante no bloquea cierre;
  - la diferencia alimenta reportes y supervision.
- Cambios:
  - `VentasErp::cierreTurnoDryRun` agrega aviso de faltante/sobrante;
  - contrato de cierre declara `permite_cerrar_con_diferencia=true`;
  - `readinessPosReadOnly` expone `cierre_bloqueos` y `cierre_requiere_revision`;
  - `caja_turnos.js` muestra advertencia si hay diferencia y permite copiar autorizacion;
  - `/ventas/caja_turnos` muestra badge `Diferencias quedan en reportes`.
- Area de reportes iniciada:
  - vista `/ventas/reportes`;
  - endpoint `/ventas/reportes_caja_erp`;
  - modelo `VentasErp::reporteCajaPosReadOnly`;
  - JS `public/assets/js/custom/apps/erp/ventas/reportes.js`;
  - script CLI `storage/uat/uat_ventas_pos_reportes_caja_readonly.php`;
  - plan `docs/erp_ventas_pos_reportes_caja_plan.md`.
- UAT read-only:
  - rango `2026-06-01` a `2026-07-04`;
  - turnos consultados `11`;
  - turnos con diferencia `0`;
  - faltantes total `0`;
  - sobrantes total `0`;
  - primer turno `TUR-20260703-002-001`, diferencia `0`, estado `cuadrado`;
  - filtro `solo_diferencias=1` devuelve `0`, correcto para la muestra actual.
- No hubo escrituras de BD en reportes.

## Evidencia tecnica 2026-07-03 - reportes por empleado

- Se amplio `VentasErp::reporteCajaPosReadOnly` para agregar `por_usuario`.
- La vista `/ventas/reportes` ahora muestra tabla `Diferencias por empleado`.
- El JS `reportes.js` renderiza:
  - empleado;
  - turnos;
  - turnos con diferencia;
  - porcentaje de turnos con diferencia;
  - faltantes;
  - sobrantes;
  - neto.
- UAT read-only:
  - primer usuario `Usuario 1`;
  - turnos `11`;
  - turnos con diferencia `0`;
  - faltantes total `0`;
  - sobrantes total `0`;
  - neto `0`;
  - porcentaje con diferencia `0`.
- No hubo escrituras de BD.

## Evidencia tecnica - Readiness POS compacto

- Fecha: 2026-07-03
- Objetivo:
  - consolidar el estado de POS antes de pedir nuevas autorizaciones reales;
  - dejar una salida corta para UAT sin volcar todo el detalle interno del ticket.
- Cambios:
  - `storage/uat/uat_ventas_pos_readiness_readonly.php` acepta `--compact=1`;
  - `/ventas/caja_turnos` permite copiar la autorizacion sugerida de cierre cuando la diferencia es `0`.
- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_readiness_readonly.php --compact=1 --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio=295 --monto_contado=795 --monto_abono=100 --folio_venta=POS-20260701-000001 --folio_apartado=APT-UAT-000001
```

- Resultado:
  - `ok=true`;
  - asignacion activa `true`;
  - turno abierto `true`;
  - `id_turno_caja=10`;
  - `id_caja=2`;
  - cierre diferencia `0`;
  - ticket formal `28` lineas;
  - reserva bloqueada por `Existencia insuficiente`;
  - abono bloqueado por `Pedido/apartado no encontrado`;
  - devoluciones fisicas pendientes `1`.
- Contrato:
  - no escribio BD;
  - no cerro turno;
  - no creo pedido;
  - no registro abono;
  - no reservo inventario;
  - no movio kardex.

## Evidencia tecnica - Configuracion POS read-only

- Fecha: 2026-07-03
- Objetivo:
  - confirmar que el POS ya abre amarrado a usuario, almacen, caja y terminal;
  - validar configuracion base antes de preparar CRUD real.
- Cambios:
  - se agrego `storage/uat/uat_ventas_pos_configuracion_readonly.php`;
  - se sincronizaron selectores de Configuracion POS para mantener combinaciones tienda/caja/terminal coherentes;
  - se normalizo navegacion superior entre Turnos, Movimientos, Evidencias, Configuracion, POS y Ventas.
- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_configuracion_readonly.php --compact=1 --id_usuario=1
```

- Resultado:
  - `ok=true`;
  - `read_only=true`;
  - usuario `1`;
  - almacen `5`;
  - caja `2`;
  - terminal `2`;
  - asignacion activa `true`;
  - turno abierto `true`;
  - esquema pendiente `false`;
  - cajas `2`;
  - terminales `2`;
  - asignaciones `2`;
  - turnos abiertos `1`;
  - movimientos recientes `22`;
  - hallazgos `[]`.
- Contrato:
  - no escribio BD;
  - no creo caja;
  - no creo terminal;
  - no asigno usuario;
  - no abrio turno.

## Evidencia tecnica - Preflight cierre turno POS

- Fecha: 2026-07-03
- Objetivo:
  - confirmar si el turno abierto puede cerrarse de forma real antes de solicitar autorizacion;
  - generar autorizacion sugerida y comando aplicador sin escribir BD.
- Cambios:
  - se agrego `storage/uat/uat_ventas_pos_turno_cierre_preflight_readonly.php`.
- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_cierre_preflight_readonly.php --id_usuario=1 --monto_contado=795 --observaciones="Cierre UAT POS preflight TUR-20260630-002-002"
```

- Resultado:
  - `ok=true`;
  - `read_only=true`;
  - turno `TUR-20260630-002-002`;
  - `id_turno_caja=10`;
  - almacen `5`;
  - caja `2`;
  - asignacion activa `true`;
  - turno abierto `true`;
  - monto esperado `795`;
  - monto contado `795`;
  - diferencia `0`;
  - bloqueos `[]`.
- Autorizacion sugerida:

```text
AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS preflight TUR-20260630-002-002"
```

- Contrato:
  - no escribio BD;
  - no cerro turno;
  - no modifico caja;
  - no movio dinero.

## Evidencia tecnica - Post-cierre y stock siguiente UAT preparados

- Fecha: 2026-07-03
- Objetivo:
  - dejar preparada la verificacion posterior al cierre real;
  - dejar preparada la siguiente carga de stock UAT para venta/pedido despues de cerrar turno.
- Cambios:
  - `storage/uat/uat_ventas_pos_turno_post_cierre_readonly.php` acepta `--compact=1`;
  - `storage/uat/uat_ventas_pos_stock_uat_preflight_readonly.php` acepta `--id_usuario`, `--respaldo` y `--referencia`.
- Post-cierre antes de autorizacion real:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_post_cierre_readonly.php --compact=1 --id_turno_caja=10
```

- Resultado post-cierre actual:
  - turno `TUR-20260630-002-002`;
  - estatus `abierto`;
  - fecha cierre `null`;
  - ventas `295`;
  - pagos `295`;
  - movimientos caja `795`;
  - hallazgos esperados porque aun no se autorizo el cierre real:
    - `El turno no esta cerrado.`;
    - `El turno no tiene fecha de cierre.`;
    - `Monto esperado y contado no coinciden.`
- Preflight stock siguiente UAT:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_stock_uat_preflight_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --referencia=INV-INICIAL-POS-UAT-20260703-A5-S1760-CIERRE1
```

- Resultado stock:
  - `ok=true`;
  - almacen `5`, `Mascotas Mina 971`;
  - SKU `1760`, `TP-40352-500GR`;
  - cantidad `1`;
  - precio general `295`;
  - permite venta fraccionaria `1`;
  - referencia `INV-INICIAL-POS-UAT-20260703-A5-S1760-CIERRE1`;
  - bloqueos `[]`.
- Autorizacion sugerida para despues de cerrar turno:

```text
AUTORIZO CARGAR STOCK UAT POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-UAT-20260703-A5-S1760-CIERRE1
```

- Contrato:
  - no escribio BD;
  - no cerro turno;
  - no cargo stock;
  - no movio kardex.

## Evidencia tecnica - Secuencia UAT siguiente preparada

- Fecha: 2026-07-03
- Objetivo:
  - dejar listos los preflights compactos para continuar despues del cierre;
  - confirmar que los bloqueos actuales son esperados y no errores de flujo.
- Preflight apertura siguiente turno:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_preflight_readonly.php --id_usuario=1 --monto_inicial=500 --observaciones="Apertura UAT POS posterior a cierre"
```

- Resultado apertura:
  - asignacion activa `true`;
  - turno abierto actual `TUR-20260630-002-002`;
  - `puede_abrir_turno=false`;
  - bloqueo esperado: `Ya existe turno abierto para esta caja`.
- Preflight venta siguiente UAT:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_preflight_readonly.php --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295 --cliente="Cliente UAT POS post cierre"
```

- Resultado venta:
  - asignacion activa `true`;
  - turno abierto `true`;
  - total `295`;
  - pago `295`;
  - `puede_vender_real=false`;
  - bloqueo esperado: `Existencia insuficiente`.
- Pedidos/apartados compacto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pedidos_apartados_readonly.php --compact=1 --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio=295 --monto_abono=100 --folio=APT-UAT-000001
```

- Resultado pedidos/apartados:
  - pedidos `0`;
  - apartados `0`;
  - anticipo minimo `59`;
  - pagado simulado `100`;
  - saldo estimado `195`;
  - reserva bloqueada por `Existencia insuficiente`;
  - abono bloqueado por `Pedido/apartado no encontrado`.
- Contrato:
  - no escribio BD;
  - no abrio turno;
  - no creo venta;
  - no creo pedido;
  - no cargo stock;
  - no movio caja ni kardex.

## Evidencia UAT - Cierre real turno POS

- Fecha: 2026-07-03
- Autorizacion recibida:

```text
AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS preflight TUR-20260630-002-002"
```

- Prevalidaciones antes de escribir:
  - `mysqladmin ping`: `mysqld is alive`;
  - readiness compacto: OK;
  - preflight cierre: OK, diferencia `0`, bloqueos `[]`.
- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_cierre_apply_authorized.php --autorizar=VENTAS_POS_TURNO_CIERRE --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --monto_contado=795 --observaciones="Cierre UAT POS preflight TUR-20260630-002-002"
```

- Resultado:
  - `ok=true`;
  - modo `turno_cerrado`;
  - turno `TUR-20260630-002-002`;
  - `id_turno_caja=10`;
  - almacen `5`;
  - caja `2`;
  - monto esperado `795`;
  - monto contado `795`;
  - diferencia `0`;
  - ventas `1`;
  - total ventas `295`;
  - pagos `295`;
  - movimientos caja:
    - entrada inicial `500`;
    - ingreso venta POS `295`.
- Auditoria post-cierre:
  - turno estatus `cerrado`;
  - fecha cierre `2026-07-03 21:03:05`;
  - hallazgos `[]`;
  - configuracion POS: turnos abiertos `0`;
  - preflight apertura siguiente turno: `puede_abrir_turno=true`.

## Evidencia UAT - Apertura turno y carga stock posterior al cierre

- Fecha: 2026-07-03
- Autorizaciones recibidas:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS posterior a cierre"

AUTORIZO CARGAR STOCK UAT POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-UAT-20260703-A5-S1760-CIERRE1
```

- Prevalidaciones:
  - MySQL vivo;
  - apertura turno: `puede_abrir_turno=true`, bloqueos `[]`;
  - stock SKU `1760`: bloqueos `[]`.
- Apertura ejecutada:
  - modo `turno_abierto`;
  - `id_turno_caja=11`;
  - folio `TUR-20260703-002-001`;
  - movimiento caja apertura `23`;
  - almacen `5`;
  - caja `2`;
  - monto inicial `500`.
- Stock ejecutado:
  - modo `stock_uat_cargado`;
  - SKU `1760`;
  - cantidad `1`;
  - referencia `INV-INICIAL-POS-UAT-20260703-A5-S1760-CIERRE1`;
  - movimientos inventario `1`;
  - etiquetas generadas `0`.
- Verificacion posterior:
  - configuracion POS: turno abierto `true`, turnos abiertos `1`;
  - venta preflight SKU `1760`: `puede_vender_real=true`, bloqueos `[]`, total `295`, pago `295`, salida existencia `34` de `1` a `0`;
  - pedidos/apartados dry-run: reserva apartado sin bloqueos de stock, anticipo minimo `59`, pagado `100`, saldo `195`;
  - abono fake sigue bloqueado por `Pedido/apartado no encontrado`, esperado hasta crear apartado real.

## Evidencia UAT - Venta real POS posterior al cierre

- Fecha: 2026-07-03
- Autorizacion recibida:

```text
AUTORIZO EJECUTAR VENTA POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 id_sku=1760 cantidad=1 precio=295 pago=295 cliente="Cliente UAT POS post cierre"
```

- Respaldo UAT POS vigente:
  - `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`.
- Prevalidaciones:
  - MySQL vivo;
  - venta preflight `puede_vender_real=true`;
  - bloqueos `[]`;
  - turno `11`;
  - stock existencia `34`, antes `1`, despues estimado `0`.
- Resultado:
  - `ok=true`;
  - modo `venta_real_uat`;
  - folio `POS-20260703-000001`;
  - `id_venta=11`;
  - estatus `pagada`;
  - cliente `Cliente UAT POS post cierre`;
  - total `295`;
  - pagado `295`;
  - saldo `0`.
- Inventario:
  - `id_existencia_inventario=34`;
  - `id_movimiento_inventario=71`;
  - cantidad `1`;
  - existencia anterior `1`;
  - existencia nueva `0`.
- Caja/pago:
  - `id_venta_pago=12`;
  - `id_movimiento_caja=24`;
  - metodo `Efectivo`;
  - monto `295`.
- Garantia:
  - `id_venta_detalle_garantia=8`;
  - resumen ticket `Sin garantia`.
- Ticket formal read-only:
  - ticket `28` lineas;
  - hallazgos `[]`;
  - inventario trazado `1` movimiento;
  - turno `TUR-20260703-002-001`.
- Readiness posterior:
  - cierre diferencia `0`;
  - reserva/apartado vuelve a bloquear por `Existencia insuficiente` porque la venta consumio el stock UAT;
  - abono fake bloquea por `Pedido/apartado no encontrado`;
  - devoluciones fisicas pendientes `1`.

## Evidencia UAT - Venta POS UI real confirmada

- Fecha: 2026-07-01
- Contexto:
  - el operador reporto que antes de cobrar el POS mostro el texto antiguo `Inventario suficiente en esta prevalidacion. Falta autorizacion para reservar, cobrar y descontar con kardex.`;
  - despues pulso `Cobrar` y el POS confirmo la venta;
  - se reviso backend para validar que no fuera venta parcial ni simulacion.
- Resultado de venta:
  - folio `POS-20260701-000001`;
  - `id_venta=10`;
  - estatus `pagada`;
  - canal `pos`;
  - turno `TUR-20260630-002-002`, `id_turno_caja=10`;
  - caja `CJ-MASCOTAS971-01`, almacen `5`;
  - cliente CRM `id_cliente_crm=1`, codigo `CRM-POSUAT-20260628-0001`, nombre `Cliente Express UAT`;
  - total `295`, pagado `295`, saldo `0`.
- Partida:
  - `id_venta_detalle=10`;
  - SKU `1760`, codigo `TP-40352-500GR`;
  - descripcion `Alimento churro rojo para peces 500 gr`;
  - cantidad `1 kg`;
  - precio/lista `Lista UAT POS`;
  - regla `lista_canal_sucursal`;
  - total partida `295`.
- Caja:
  - pago efectivo `id_venta_pago=11`;
  - movimiento caja `22`;
  - monto `295`.
- Inventario y trazabilidad:
  - salida por existencia agregada;
  - `id_venta_detalle_inventario=10`;
  - movimiento kardex `69`;
  - existencia `34`;
  - referencia `POS-20260701-000001`;
  - existencia anterior `1`;
  - existencia nueva `0`;
  - candidatos stock almacen `5` despues de venta: `total=0`.
- Garantia:
  - snapshot `Sin garantia`;
  - `id_venta_detalle_garantia=7`.
- Ticket:
  - ticket formal read-only generado con 28 lineas;
  - sin hallazgos;
  - incluye folio, tienda, caja, turno, cliente, producto, garantia, pago y trazabilidad.
- Corte dry-run posterior:
  - comando read-only `storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=795`;
  - resultado `ok=true`;
  - monto esperado `795`;
  - monto contado `795`;
  - diferencia `0`;
  - resumen: apertura `500` + venta POS `295`;
  - no cerro turno real.
- Correccion aplicada:
  - se actualizo el texto de prevalidacion en `public/assets/js/custom/apps/erp/ventas/pos.js`;
  - nuevo texto: `Inventario suficiente. Si el pago cubre el total y el turno sigue abierto, puedes cobrar; el backend registrara caja, kardex, garantia y trazabilidad.`;
  - asset POS actualizado a `20260701-pos-ux-prevalidar1`.

## Evidencia UAT - Bateria read-only sin autorizacion

- Fecha: 2026-07-02
- Alcance:
  - pruebas de sintaxis;
  - consultas read-only;
  - simulaciones sin escritura;
  - validacion de bloqueos esperados.
- Sintaxis PHP:
  - `app/controladores/Ventas.php`: OK;
  - `app/modelos/VentasErp.php`: OK;
  - `app/vistas/paginas/apps/erp/ventas/pos.php`: OK;
  - `app/vistas/paginas/apps/erp/ventas/listado.php`: OK;
  - `app/vistas/paginas/apps/erp/ventas/caja_turnos.php`: OK;
  - `app/vistas/paginas/apps/erp/ventas/devoluciones.php`: OK;
  - `app/vistas/paginas/apps/erp/ventas/pos_configuracion.php`: OK;
  - `app/vistas/includes/header/sidebar.php`: OK.
- Sintaxis JS:
  - `public/assets/js/custom/apps/erp/ventas/pos.js`: OK;
  - `public/assets/js/custom/apps/erp/ventas/listado.js`: OK;
  - `public/assets/js/custom/apps/erp/ventas/caja_turnos.js`: OK;
  - `public/assets/js/custom/apps/erp/ventas/devoluciones.js`: OK;
  - `public/assets/js/custom/apps/erp/ventas/pos_configuracion.js`: OK.
- Corte simulado:
  - turno `TUR-20260630-002-002`;
  - `id_turno_caja=10`;
  - esperado `795`;
  - contado `795`;
  - diferencia `0`;
  - bloqueos `[]`;
  - no cerro turno real.
- Postventa `POS-20260701-000001`:
  - venta `pagada`;
  - detalle `295`;
  - pago `295`;
  - saldo `0`;
  - garantia `Sin garantia`;
  - kardex/trazabilidad movimiento `69`;
  - hallazgos `[]`.
- Ticket formal `POS-20260701-000001`:
  - generado en modo read-only;
  - 28 lineas;
  - hallazgos `[]`;
  - incluye cliente CRM, caja, turno, garantia e inventario trazado.
- Configuracion POS:
  - schema completo;
  - cajas `2`;
  - terminales `2`;
  - asignaciones `2`;
  - turnos abiertos `1`.
- Dry-runs Configuracion POS:
  - caja nueva `CJ-UAT-DRY-02`: valida, sin bloqueos;
  - terminal nueva `TERM-UAT-DRY-02`: valida, sin bloqueos;
  - asignacion duplicada usuario `1`, almacen `5`, caja `2`, terminal `2`: bloqueada correctamente.
- Preflight venta SKU `1760`:
  - asignacion activa `true`;
  - turno abierto `true`;
  - venta real bloqueada correctamente por `Existencia insuficiente`;
  - stock candidatos almacen `5`: `0`.
- Devoluciones:
  - pendientes fisicos: `1`;
  - folio pendiente `DEV-20260630-000001`;
  - decision inventario `cuarentena`;
  - inspeccion fisica pendiente;
  - ticket devolucion `DEV-20260630-000001` generado en modo read-only;
  - devolucion simulada sin partidas bloquea con `Agrega partidas a devolver`.
- Hallazgos:
  - `UAT-POS-20260702-001`: turno `10` sigue abierto, esperado hasta autorizacion de cierre real.
  - `UAT-POS-20260702-002`: nueva venta SKU `1760` bloquea por stock `0`, comportamiento correcto.
  - `UAT-POS-20260702-003`: prueba visual con navegador queda pendiente porque el navegador integrado no estuvo disponible en esta sesion.

## Evidencia UAT - Complemento read-only POS

- Fecha: 2026-07-02
- Alcance:
  - validar devoluciones, ticket devolucion, ultimas ventas, cliente/precio y atenciones sin escritura.
- Devoluciones inventario:
  - script `uat_ventas_pos_devoluciones_inventario_pendientes_readonly.php`;
  - resultado `ok=true`;
  - pendientes fisicos `1`;
  - folio `DEV-20260630-000001`;
  - decision inventario `cuarentena`;
  - inspeccion fisica `pendiente`;
  - contrato confirma que no escribe BD, no crea kardex y no reintegra inventario.
- Ticket devolucion:
  - script `uat_ventas_pos_ticket_devolucion_readonly.php`;
  - folio devolucion `DEV-20260630-000001`;
  - venta origen `POS-20260629-000003`;
  - hallazgos `[]`;
  - decision financiera `saldo_favor`;
  - saldo favor `$295.00`;
  - reembolso `$0.00`;
  - contrato confirma que no mueve inventario ni reembolsa caja.
- Ultimas ventas:
  - script `uat_ventas_pos_ultimas_ventas_readonly.php`;
  - resultado `ok=true`;
  - ultima venta `POS-20260701-000001`;
  - venta con cliente CRM `CRM-POSUAT-20260628-0001`;
  - total `295`;
  - estatus `pagada`.
- Cliente/precio/apartado:
  - script `uat_ventas_pos_cliente_precio_apartado_readonly.php`;
  - resultado `ok=true`;
  - precio SKU `1760` resuelto por backend con lista `Lista UAT POS`;
  - precio aplicado `295`;
  - contrato confirma que JS no decide descuentos y backend guarda snapshot de precio/lista;
  - dry-run de abono bloqueado correctamente por falta de caja y turno seleccionados.
- Atenciones POS persistentes:
  - script `uat_ventas_pos_atenciones_readonly.php`;
  - resultado `ok=true`;
  - tablas `erp_pos_atenciones`, `erp_pos_atenciones_detalle` y `erp_pos_atenciones_pagos_temporales` existen;
  - bandeja sin atenciones abiertas;
  - contrato confirma que atenciones no reservan ni descuentan inventario y caja debe revalidar al cobrar.
- Incidencia tecnica de prueba:
  - llamadas directas con `php -r` a metodos de modelo fallaron por bootstrap/autoload de CLI;
  - se sustituyeron por scripts UAT read-only existentes;
  - no hubo escrituras de BD en estas pruebas.

## Evidencia UAT - Caja movimientos/evidencias separados

- Fecha: 2026-07-02
- Alcance:
  - exponer pantallas dedicadas sin escrituras reales;
  - validar endpoints read-only/dry-run de caja y evidencias.
- Cambios UI:
  - ruta `/ventas/caja_movimientos`;
  - ruta `/ventas/caja_evidencias`;
  - menu `Movimientos caja`;
  - menu `Evidencias caja`.
- Contratos:
  - movimientos caja: dry-run, no registra dinero, no cambia corte, no adjunta evidencia;
  - evidencias caja: read-only, no aprueba, no rechaza, no corrige y no reemplaza comprobantes.
- Validaciones de sintaxis:
  - `app/controladores/Ventas.php`: OK;
  - `app/vistas/paginas/apps/erp/ventas/caja_movimientos.php`: OK;
  - `app/vistas/paginas/apps/erp/ventas/caja_evidencias.php`: OK;
  - `app/vistas/includes/header/sidebar.php`: OK;
  - `public/assets/js/custom/apps/erp/ventas/caja_movimientos.js`: OK;
  - `public/assets/js/custom/apps/erp/ventas/caja_evidencias.js`: OK.
- Dry-run movimiento caja:
  - script `uat_ventas_pos_caja_movimiento_dryrun_readonly.php`;
  - tipo `gasto_caja`;
  - monto `50`;
  - referencia `DRY-MOV-UI-001`;
  - resultado `ok=true`;
  - bloqueos `[]`;
  - impacto esperado `-50`;
  - avisos: requiere autorizacion y evidencia/comprobante en flujo real.
- Evidencias pendientes:
  - script `uat_ventas_pos_caja_evidencias_readonly.php`;
  - estado `todos`;
  - resultado `ok=true`;
  - movimientos `2`;
  - monto total `345`;
  - estados:
    - `aprobada`: 1 operacion, monto `295`;
    - `pendiente`: 1 operacion, monto `50`.
- Detalle evidencia:
  - script `uat_ventas_pos_caja_evidencias_detalle_readonly.php`;
  - movimiento caja `20`;
  - resultado `ok=true`;
  - evidencias `2`;
  - correccion `COR-EVC-20260630-000001`;
  - correccion `resuelta`;
  - decision `aprobada`;
  - contrato confirma no escritura, no aprobacion nueva, no dinero y no inventario.
- Correcciones:
  - script `uat_ventas_pos_caja_evidencia_correccion_readonly.php`;
  - evidencia `1`;
  - resultado `ok=true`;
  - total registros `1`;
  - contrato confirma no modifica evidencia ni caja.
- Hallazgos:
  - `UAT-POS-20260702-004`: gasto UAT antiguo `GASTO-UAT-001` sigue con evidencia pendiente; queda visible en nueva pantalla de evidencias.
  - `UAT-POS-20260702-005`: movimientos reales de caja deben seguir solicitando autorizacion fuerte y evidencia cuando aplique.

## Evidencia tecnica - POS principal aligerado

- Fecha: 2026-07-02
- Objetivo:
  - reducir botones y modales sensibles dentro del POS mostrador;
  - dirigir caja sensible a modulos dedicados.
- Cambios:
  - `Movimientos` en POS abre `/ventas/caja_movimientos`;
  - `Evidencias` en POS abre `/ventas/caja_evidencias`;
  - `Corte` en POS abre `/ventas/caja_turnos`;
  - atajo `F8` abre `/ventas/caja_movimientos`;
  - listeners de modales legacy Caja/Corte quedan opcionales para evitar errores durante transicion.
- No cambia:
  - cobro real;
  - kardex;
  - ticket;
  - inventario;
  - caja real;
  - permisos;
  - ecommerce/legacy.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`: OK;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: OK.

## Evidencia tecnica - Detalle venta read-only

- Fecha: 2026-07-02
- Objetivo:
  - crear una vista de detalle por folio que concentre ticket, pagos, garantias y trazabilidad.
- Cambios:
  - controlador `Ventas::venta_detalle`;
  - vista `app/vistas/paginas/apps/erp/ventas/venta_detalle.php`;
  - asset `public/assets/js/custom/apps/erp/ventas/venta_detalle.js`;
  - accion `Detalle` en `/ventas/mostrar`;
  - asset listado versionado como `20260702-detalle-venta1`.
- Fuente:
  - reutiliza `/ventas/ticket_venta_readonly_erp`;
  - no duplica reglas de negocio.
- Contrato:
  - no escribe BD;
  - no cobra;
  - no cancela;
  - no reembolsa;
  - no mueve inventario;
  - no recalcula garantia historica.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: OK;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\venta_detalle.php`: OK;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\listado.php`: OK;
  - `node --check public\assets\js\custom\apps\erp\ventas\venta_detalle.js`: OK;
  - `node --check public\assets\js\custom\apps\erp\ventas\listado.js`: OK;
  - `uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260701-000001`: OK, 28 lineas, hallazgos `[]`.

## Evidencia tecnica - Limpieza POS Caja/Corte legacy

- Fecha: 2026-07-02
- Objetivo:
  - retirar del POS mostrador los modales antiguos de Caja/Corte que ya tienen modulos dedicados.
- Cambios:
  - eliminado HTML `pos_caja_modal` de `app/vistas/paginas/apps/erp/ventas/pos.php`;
  - eliminado HTML `pos_corte_modal` de `app/vistas/paginas/apps/erp/ventas/pos.php`;
  - post-cobro `Ver venta` apunta a `/ventas/venta_detalle?folio=...`;
  - asset POS versionado como `20260702-detalle-limpieza1`.
- No cambia:
  - cobro real;
  - pagos;
  - kardex;
  - ticket;
  - garantia;
  - trazabilidad;
  - reglas de caja;
  - ecommerce/legacy.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`: OK;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: OK;
  - `uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260701-000001`: OK, hallazgos `[]`.

## Evidencia tecnica - Modulo Pedidos/Apartados dedicado

- Fecha: 2026-07-02
- Objetivo:
  - separar pedidos, apartados y abonos del POS mostrador y del listado general.
- Cambios:
  - `Ventas::pedidos` abre `apps/erp/ventas/pedidos`;
  - nueva vista `app/vistas/paginas/apps/erp/ventas/pedidos.php`;
  - nuevo asset `public/assets/js/custom/apps/erp/ventas/pedidos.js`.
- Pantalla:
  - lista pedidos/apartados en modo consulta;
  - filtra por tipo, estatus y texto;
  - enlaza a detalle read-only;
  - simula abono con almacen/caja/turno/metodo;
  - no crea registros reales.
- Contrato:
  - no escribe BD;
  - no crea pedido/apartado;
  - no registra pago;
  - no mueve caja;
  - no reserva ni descuenta inventario.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: OK;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pedidos.php`: OK;
  - `node --check public\assets\js\custom\apps\erp\ventas\pedidos.js`: OK;
  - `uat_ventas_pos_cliente_precio_apartado_readonly.php --id_almacen=5 --id_sku=1760 --identificador=3312345678`: OK;
  - abono dry-run bloqueado correctamente por falta de caja/turno cuando no se indican.

## Evidencia tecnica - UAT y guardrail abonos Pedidos/Apartados

- Fecha: 2026-07-02
- Objetivo:
  - validar Pedidos/Apartados con una UAT dedicada;
  - evitar que un abono dry-run parezca valido para folios inexistentes.
- Cambios:
  - nuevo script `storage/uat/uat_ventas_pos_pedidos_apartados_readonly.php`;
  - `VentasErp::apartadoAbonoDryRun` valida existencia de folio/id;
  - valida tipo `pedido` o `apartado`;
  - bloquea estatus no abonables;
  - bloquea saldo pendiente cero;
  - agrega aviso si abono excede saldo.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: OK;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedidos_apartados_readonly.php`: OK;
  - `uat_ventas_pos_pedidos_apartados_readonly.php --id_usuario=1 --id_almacen=5 --folio=APT-UAT-000001 --monto_abono=100 --id_metodo_pago=1`: OK.
- Resultado UAT:
  - contexto POS: almacen `5`, caja `2`, turno `10`;
  - asignacion activa `true`;
  - turno abierto `true`;
  - pedidos muestra `0`;
  - apartados muestra `0`;
  - abono dry-run bloqueado por `Pedido/apartado no encontrado`;
  - no escribio BD, no movio caja, no reservo inventario y no genero kardex.

## Evidencia documental - Checklist de cierre POS UAT

- Fecha: 2026-07-03.
- Documento creado:
  - `docs/erp_ventas_pos_cierre_checklist.md`.
- Proposito:
  - resumir el estado actual del modulo;
  - separar pruebas disponibles sin escritura;
  - dejar visibles autorizaciones fuertes pendientes;
  - ordenar el cierre UAT del POS sin mezclar legacy/ecommerce.
- Siguiente paso recomendado:
  - cierre real del turno UAT abierto con monto contado `795`, sujeto a autorizacion explicita.
- Revalidacion sin escritura 2026-07-03:
  - ticket formal `POS-20260701-000001`: OK, 28 lineas, hallazgos `[]`;
  - cierre dry-run turno `10`: esperado `795`, contado `795`, diferencia `0`, bloqueos `[]`;
  - pedidos/apartados: `0` pedidos, `0` apartados, reserva dry-run bloqueada por `Existencia insuficiente`, abono fake bloqueado por `Pedido/apartado no encontrado`;
  - apartado dry-run: politica `POS_APARTADO_UAT`, anticipo minimo `59`, pagado simulado `100`, saldo estimado `195`, fecha maxima compromiso `2026-08-01`;
  - devoluciones fisicas pendientes: `1` partida, folio `DEV-20260630-000001`, decision `cuarentena`, inspeccion pendiente;
  - no hubo escrituras de BD.

## Evidencia tecnica - Dry-run robusto Pedidos/Apartados

- Fecha: 2026-07-03.
- Cambios:
  - `VentasErp::pedidoReservaDryRun` valida politica activa de apartado;
  - calcula anticipo minimo por porcentaje/monto;
  - valida fecha compromiso, vigencia maxima y permisos de abonos;
  - genera propuesta de reserva desde el plan de salida de inventario;
  - `uat_ventas_pos_pedidos_apartados_readonly.php` ahora simula reserva y abono.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: OK;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedidos_apartados_readonly.php`: OK;
  - UAT read-only de pedidos/apartados: OK.
- Resultado:
  - almacen `5`, caja `2`, turno `10`;
  - politica activa `POS_APARTADO_UAT`;
  - anticipo minimo `59`;
  - pago simulado `100`;
  - saldo estimado `195`;
  - reserva bloqueada por `Existencia insuficiente`;
  - abono bloqueado por `Pedido/apartado no encontrado`;
  - no escribio BD, no movio caja, no reservo inventario y no genero kardex.
- Documento preparado:
  - `docs/erp_ventas_pos_pedidos_apartados_real_runbook.md`.

## Evidencia UX - Simulador reserva Pedidos/Apartados

- Fecha: 2026-07-03.
- Cambios:
  - `/ventas/pedidos` ahora incluye panel `Simular pedido/apartado`;
  - `/ventas/pos` ahora incluye acceso directo `Pedidos`;
  - asset versionado como `pedidos.js?v=20260703-reserva-ux2`;
  - el panel consume `/ventas/pedido_reserva_dryrun_erp`.
- UX adicional:
  - turno sincroniza almacen/caja;
  - almacen selecciona caja/turno compatible;
  - caja selecciona turno compatible;
  - fecha compromiso por defecto usa fecha local.
  - en POS, `Simular pedido` queda separado del acceso `Pedidos`;
  - `F10` abre `/ventas/pedidos`.
- Muestra:
  - politica activa;
  - anticipo minimo;
  - pago simulado;
  - saldo estimado;
  - fecha maxima;
  - bloqueos y avisos;
  - reservas tentativas que se crearian.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php`: OK;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: OK;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pedidos.php`: OK;
  - `node --check public\assets\js\custom\apps\erp\ventas\pedidos.js`: OK;
  - UAT read-only de pedidos/apartados: OK.
- Resultado esperado actual:
  - apartado SKU `1760`, cantidad `1`, precio `295`, anticipo `100`;
  - politica `POS_APARTADO_UAT`;
  - anticipo minimo `59`;
  - saldo estimado `195`;
  - bloqueo por `Existencia insuficiente`;
  - no escribio BD.

## Evidencia tecnica - Readiness consolidado POS

- Fecha: 2026-07-03.
- Script creado:
  - `storage/uat/uat_ventas_pos_readiness_readonly.php`.
- Endpoint creado:
  - `/ventas/pos_readiness_readonly_erp`.
- UI:
  - `/ventas/caja_turnos` muestra panel `Readiness POS`.
  - readiness muestra autorizacion sugerida para cierre real cuando la diferencia es `0`.
  - la UI no ejecuta cierre real.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: OK;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: OK;
  - `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\caja_turnos.php`: OK;
  - `node --check public\assets\js\custom\apps\erp\ventas\caja_turnos.js`: OK;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_readiness_readonly.php`: OK;
  - ejecucion read-only: OK.
- Resultado:
  - asignacion activa `true`;
  - turno abierto `true`;
  - cierre diferencia `0`;
  - ticket formal `28` lineas;
  - reserva dry-run bloqueada por `Existencia insuficiente`;
  - abono dry-run bloqueado por `Pedido/apartado no encontrado`;
  - devoluciones fisicas pendientes `1`.
- Contrato:
  - no escribio BD;
  - no cerro turno;
  - no creo pedido;
  - no registro abono;
  - no reservo inventario;
  - no genero kardex.

## Evidencia tecnica - UI detalle correcciones evidencia caja POS

- Fecha: 2026-06-30
- Objetivo:
  - mostrar en POS el historial de correccion ligado a evidencias de caja;
  - evitar que el operador dependa de scripts UAT para ver si una evidencia tuvo correccion.
- Cambios:
  - `VentasErp::evidenciasCajaDetalleReadOnly` incluye datos de correccion si existe `erp_pos_movimientos_caja_evidencias_correcciones`;
  - `public/assets/js/custom/apps/erp/ventas/pos.js` muestra folio, tipo, relacion, estatus y decision de correccion;
  - `app/vistas/paginas/apps/erp/ventas/pos.php` actualiza version de asset a `20260630-evidencias4`.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: sin errores;
  - `storage\uat\uat_ventas_pos_caja_evidencias_detalle_readonly.php --id_movimiento_caja=20`: `total_registros=2`.
- Resultado read-only:
  - evidencia original `1`: `estatus=aprobada`, correccion `COR-EVC-20260630-000001`, relacion `evidencia_original`;
  - evidencia correctiva `2`: `estatus=aprobada_correccion`, correccion `COR-EVC-20260630-000001`, relacion `evidencia_correctiva`;
  - correccion `estatus=resuelta`, `decision=aprobada`.
- Impacto:
  - no escribe BD;
  - no aprueba/rechaza evidencia;
  - no mueve dinero;
  - no mueve inventario.

## Evidencia tecnica - Correcciones evidencia caja POS expuestas en UI

- Fecha: 2026-06-30
- Autorizacion recibida:

```text
AUTORIZO EXPONER CORRECCIONES EVIDENCIA CAJA POS UI usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_UI para UAT POS
```

- Objetivo:
  - permitir que el supervisor opere desde POS el ciclo de correccion de evidencias;
  - mantener el historial auditable y las validaciones del modelo.
- Cambios:
  - `public/assets/js/custom/apps/erp/ventas/pos.js` agrega acciones en el detalle de evidencias:
    - `Solicitar correccion`;
    - `Evidencia correctiva`;
    - `Aprobar correccion`;
    - `Rechazar correccion`.
  - `app/vistas/paginas/apps/erp/ventas/pos.php` actualiza version de asset a `20260630-evidencias5`.
  - `docs/erp_ventas_pos_uat_manual.md` agrega `Prueba 47`.
- Endpoints usados:
  - `/ventas/caja_evidencia_correccion_solicitar_erp`;
  - `/ventas/caja_evidencia_correccion_evidencia_erp`;
  - `/ventas/caja_evidencia_correccion_resolver_erp`.
- Validaciones:
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: sin errores;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores.
- Impacto:
  - usa CSRF/sesion/permisos del controlador y modelo;
  - no cambia esquema;
  - no cambia reglas de caja;
  - no mueve dinero;
  - no mueve inventario.
- Pendiente:
  - validar visualmente en navegador autenticado;
  - subida fisica de archivos queda pendiente para etapa posterior.

## Evidencia tecnica - Bandeja devoluciones fisicas POS

- Fecha: 2026-06-30
- Objetivo:
  - consultar devoluciones POS con decision fisica pendiente;
  - separar reversa comercial de inspeccion de Almacen/Inventario.
- Cambios:
  - `VentasErp::devolucionesInventarioPendientesReadOnly`;
  - endpoint `/ventas/devoluciones_inventario_pendientes_erp`;
  - script `storage/uat/uat_ventas_pos_devoluciones_inventario_pendientes_readonly.php`;
  - panel read-only `Devoluciones fisicas` en modal `Caja` del POS;
  - asset POS `20260630-devoluciones-fisicas1`.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_devoluciones_inventario_pendientes_readonly.php`: sin errores.
- Resultado read-only:
  - `total_registros=2`;
  - `cantidad_total=2`;
  - `importe_total=590`;
  - decision `cuarentena`: 2 partidas;
  - folios `DEV-20260630-000002` y `DEV-20260630-000001`;
  - SKU `TP-40352-500GR`.
- Impacto:
  - no escribe BD;
  - no crea kardex;
  - no reintegra inventario;
  - no resuelve garantia.
- Hallazgo operativo:
  - el producto devuelto ya esta cerrado comercialmente, pero falta flujo formal de inspeccion fisica para decidir cuarentena final, reintegro, merma o garantia/proveedor.

## Evidencia UAT - DDL inspeccion fisica devoluciones POS

- Fecha: 2026-06-30
- Autorizacion recibida:

```text
AUTORIZO PREPARAR DDL INSPECCION FISICA DEVOLUCIONES POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_DEVOLUCIONES_FISICAS_DDL para UAT POS
```

- Cambios de esquema:
  - tabla `erp_ventas_devoluciones_inspecciones`;
  - columnas en `erp_ventas_devoluciones_detalle`:
    - `inspeccion_estado`;
    - `id_inspeccion_fisica`;
    - `fecha_inspeccion_fisica`;
  - indices:
    - `idx_devolucion_detalle_inspeccion`;
    - `idx_devolucion_detalle_id_inspeccion`;
    - indices de folio, devolucion, detalle, decision, movimiento inventario y garantia en inspecciones.
- Scripts:
  - `storage/uat/uat_ventas_pos_devoluciones_fisicas_schema_readonly.php`;
  - `storage/uat/uat_ventas_pos_devoluciones_fisicas_schema_apply_authorized.php`;
  - `storage/uat/uat_ventas_pos_devolucion_inspeccion_fisica_dryrun_readonly.php`.
- Resultado DDL:
  - `ok=true`;
  - auditoria posterior con tabla, columnas e indices existentes;
  - no registro inspecciones;
  - no creo kardex;
  - no movio inventario;
  - no creo reclamos de garantia.
- Dry-runs:
  - `id_devolucion_detalle=2`, decision `mantener_cuarentena`: valido, sin kardex futuro;
  - `id_devolucion_detalle=2`, decision `reintegrar_disponible`: valido, contrato futuro indica kardex requerido.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\modelos\VentasErpEsquema.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
  - `node --check public\assets\js\custom\apps\erp\ventas\pos.js`: sin errores.

## Evidencia UAT - Inspeccion fisica devolucion POS real sin kardex

- Fecha: 2026-06-30
- Autorizacion recibida:

```text
AUTORIZO REGISTRAR INSPECCION FISICA DEVOLUCION POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_DEVOLUCION_FISICA_REAL id_usuario=1 id_devolucion_detalle=2 decision_fisica=mantener_cuarentena condicion_producto=pendiente_revision motivo="UAT mantener en cuarentena sin mover inventario" diagnostico="Producto pendiente de inspeccion fisica"
```

- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_devolucion_inspeccion_fisica_apply_authorized.php --autorizar=VENTAS_POS_DEVOLUCION_FISICA_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_devolucion_detalle=2 --decision_fisica=mantener_cuarentena --condicion_producto=pendiente_revision --motivo="UAT mantener en cuarentena sin mover inventario" --diagnostico="Producto pendiente de inspeccion fisica"
```

- Resultado:
  - `ok=true`;
  - `id_inspeccion_fisica=1`;
  - folio `IFD-20260630-000001`;
  - `id_devolucion_detalle=2`;
  - devolucion `DEV-20260630-000002`;
  - decision `mantener_cuarentena`;
  - detalle paso a `inspeccion_estado=cuarentena_confirmada`.
- Verificacion posterior:
  - pendientes fisicos bajaron a `1`;
  - `DEV-20260630-000002` aparece en filtro `todos` con folio de inspeccion `IFD-20260630-000001`;
  - dry-run posterior para la misma partida queda bloqueado por `La partida ya tiene inspeccion en estado cuarentena_confirmada`.
- Impacto:
  - no creo kardex;
  - no movio inventario;
  - no reintegro existencia;
  - no creo reclamo de garantia.

## Evidencia tecnica - Resolucion correccion evidencia caja POS preparada

- Fecha: 2026-06-30
- Objetivo:
  - cerrar una correccion en revision aprobando o rechazando la evidencia correctiva.
- Cambios:
  - `VentasErp::resolverCorreccionEvidenciaCajaPosReal`;
  - endpoint `/ventas/caja_evidencia_correccion_resolver_erp`;
  - script protegido `storage/uat/uat_ventas_pos_caja_evidencia_correccion_resolver_apply_authorized.php`.
- Reglas:
  - requiere correccion en estatus `en_revision`;
  - requiere evidencia correctiva en `recibida_correccion`;
  - requiere evidencia original y movimiento en `aprobada`;
  - decision permitida: `aprobada` o `rechazada`;
  - exige motivo de resolucion;
  - si se aprueba, correccion queda `resuelta` y evidencia nueva `aprobada_correccion`;
  - si se rechaza, correccion queda `rechazada` y evidencia nueva `rechazada_correccion`;
  - no modifica evidencia original;
  - no modifica movimiento de caja;
  - no mueve dinero ni inventario.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_caja_evidencia_correccion_resolver_apply_authorized.php`: sin errores;
  - aplicador sin parametros: bloqueado, no escribio BD.
- Autorizacion requerida:

```text
AUTORIZO RESOLVER CORRECCION EVIDENCIA CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_RESOLVER_REAL id_usuario=1 folio=COR-EVC-20260630-000001 decision=aprobada motivo="UAT correccion validada y aceptada"
```

## Evidencia UAT - Correccion evidencia caja POS resuelta

- Fecha: 2026-06-30
- Autorizacion recibida:

```text
AUTORIZO RESOLVER CORRECCION EVIDENCIA CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_RESOLVER_REAL id_usuario=1 folio=COR-EVC-20260630-000001 decision=aprobada motivo="UAT correccion validada y aceptada"
```

- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencia_correccion_resolver_apply_authorized.php --autorizar=VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_RESOLVER_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --folio=COR-EVC-20260630-000001 --decision=aprobada --motivo="UAT correccion validada y aceptada"
```

- Resultado:
  - `ok=true`;
  - folio `COR-EVC-20260630-000001`;
  - decision `aprobada`;
  - correccion `estatus=resuelta`;
  - evidencia original `1`;
  - evidencia nueva `2`;
  - evidencia nueva `estatus=aprobada_correccion`;
  - `movimiento_caja_intacto=true`.
- Auditoria posterior:
  - correccion `COR-EVC-20260630-000001`: `estatus=resuelta`;
  - `resuelto_por=1`;
  - `fecha_resolucion=2026-06-30 10:00:45`;
  - `motivo_resolucion=UAT correccion validada y aceptada`;
  - evidencia `1`: `aprobada`;
  - evidencia `2`: `aprobada_correccion`;
  - movimiento caja `20`: `evidencia_estado=aprobada`;
  - turno `9`, estado `aprobada`: `total_registros=1`, monto `295`.
- Impacto:
  - no modifico evidencia original;
  - no modifico movimiento de caja;
  - no movio dinero;
  - no movio inventario.

## Evidencia tecnica - Evidencia correctiva caja POS preparada

- Fecha: 2026-06-30
- Objetivo:
  - registrar una evidencia nueva ligada al folio de correccion `COR-EVC-20260630-000001`;
  - mantener la evidencia original aprobada sin cambios.
- Cambios:
  - `VentasErp::registrarEvidenciaCorrectivaCajaPosReal`;
  - endpoint `/ventas/caja_evidencia_correccion_evidencia_erp`;
  - script protegido `storage/uat/uat_ventas_pos_caja_evidencia_correctiva_apply_authorized.php`.
- Reglas:
  - requiere correccion `solicitada` o `en_revision`;
  - requiere evidencia original `aprobada`;
  - requiere movimiento con `evidencia_estado=aprobada`;
  - requiere permiso `ventas.caja_evidencias.revisar`;
  - bloquea si la correccion ya tiene evidencia nueva;
  - inserta evidencia nueva con estatus `recibida_correccion`;
  - cambia correccion a `en_revision`;
  - no cambia evidencia original;
  - no cambia movimiento de caja;
  - no mueve dinero ni inventario.
- Validaciones:
  - `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
  - `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
  - `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_caja_evidencia_correctiva_apply_authorized.php`: sin errores;
  - aplicador sin parametros: bloqueado, no escribio BD.
- Autorizacion requerida:

```text
AUTORIZO REGISTRAR EVIDENCIA CORRECTIVA CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_CORRECTIVA_REAL id_usuario=1 folio=COR-EVC-20260630-000001 tipo_evidencia=ticket_firmado_correccion referencia_externa=DEV-20260630-000002-CORR descripcion="Comprobante correctivo UAT para evidencia aprobada"
```

## Evidencia UAT - Evidencia correctiva caja POS registrada

- Fecha: 2026-06-30
- Autorizacion recibida:

```text
AUTORIZO REGISTRAR EVIDENCIA CORRECTIVA CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_CORRECTIVA_REAL id_usuario=1 folio=COR-EVC-20260630-000001 tipo_evidencia=ticket_firmado_correccion referencia_externa=DEV-20260630-000002-CORR descripcion="Comprobante correctivo UAT para evidencia aprobada"
```

- Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencia_correctiva_apply_authorized.php --autorizar=VENTAS_POS_CAJA_EVIDENCIA_CORRECTIVA_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --folio=COR-EVC-20260630-000001 --tipo_evidencia=ticket_firmado_correccion --referencia_externa=DEV-20260630-000002-CORR --descripcion="Comprobante correctivo UAT para evidencia aprobada"
```

- Resultado:
  - `ok=true`;
  - correccion `id_correccion_evidencia_caja=1`;
  - folio `COR-EVC-20260630-000001`;
  - evidencia original `1`;
  - evidencia nueva `2`;
  - movimiento caja `20`;
  - correccion en estatus `en_revision`;
  - evidencia nueva en estatus `recibida_correccion`;
  - evidencia original intacta.
- Auditoria posterior:
  - folio `COR-EVC-20260630-000001`: `estatus=en_revision`;
  - `id_evidencia_caja_nueva=2`;
  - detalle movimiento `20`: total evidencias `2`;
  - evidencia `1`: `aprobada`;
  - evidencia `2`: `recibida_correccion`;
  - movimiento caja `20`: `evidencia_estado=aprobada`;
  - turno `9`, estado `aprobada`: `total_registros=1`, monto `295`.
- Impacto:
  - no modifico evidencia original;
  - no modifico movimiento de caja;
  - no movio dinero;
  - no movio inventario.
