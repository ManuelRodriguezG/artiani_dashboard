# ERP Ventas/POS - Guia manual de pruebas

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-27  
Estado: Pruebas manuales para POS base UAT, caja dry-run y corte dry-run.

## Antes de empezar

- Entrar con el usuario ERP que va a operar el POS.
- Abrir `/ventas/pos`.
- Recordar: ya existe una venta real UAT validada por folio, pero la UI sigue usando simulaciones para movimientos de caja y cierre hasta nueva autorizacion.
- No registrar pruebas informales sin folio/evidencia cuando se ejecute una accion real autorizada.

## Prueba 1 - Operador visible

Pasos:

1. Abre `/ventas/pos`.
2. Mira la parte superior del area de productos.

Resultado esperado:

- Debe aparecer una etiqueta `Operador: ...` con el usuario de la sesion actual.
- Esto ayuda a detectar si alguien esta usando una sesion de otra persona.

## Prueba 2 - Fijar terminal a sucursal

Pasos:

1. En `Punto de venta`, pulsa el boton de engrane.
2. Selecciona `ACUARIO967` o `MASCOTAS971`.
3. Pulsa `Fijar terminal`.

Resultado esperado:

- El selector de punto de venta queda bloqueado.
- Debe aparecer `Terminal: ACUARIO967` o `Terminal: MASCOTAS971`.
- La venta queda visualmente amarrada a esa sucursal en este navegador/equipo.

Nota:

- Esta configuracion queda en el navegador. La asignacion oficial por usuario/caja debe guardarse en BD cuando se autorice el esquema POS.
- En operacion real, el cajero no deberia elegir la sucursal libremente; el POS debe abrir con la terminal, caja y almacen asignados a su usuario.

## Prueba 3 - Liberar terminal

Pasos:

1. Pulsa el engrane.
2. Pulsa `Liberar terminal`.

Resultado esperado:

- El selector de punto de venta vuelve a estar libre.
- Debe aparecer `Terminal libre`.

## Prueba 4 - Buscar productos con imagen

Pasos:

1. Busca `TP-40372`.
2. Observa las tarjetas.

Resultado esperado:

- Deben aparecer imagenes en producto base y variantes.
- Si un SKU variante no tiene imagen propia, debe usar la imagen del producto base.

## Prueba 5 - Agregar al carrito

Pasos:

1. Agrega un producto desde una tarjeta.
2. Revisa el carrito.

Resultado esperado:

- Debe aparecer imagen pequena, SKU, descripcion, cantidad, modo de salida y subtotal.

## Prueba 6 - Prevalidar carrito

Pasos:

1. Agrega `TP-40311` o `TP-40372`.
2. Agrega un pago si quieres probar saldo/cambio.
3. Pulsa `Prevalidar carrito`.

Resultado esperado:

- Si no hay caja/turno configurado, debe bloquear con mensajes de caja/turno pendiente.
- Si el almacen no tiene stock, debe mostrar `Existencia insuficiente`.
- Si hay plan de salida, debe mostrar lote, ubicacion o unidad sugerida.

## Prueba 7 - Cuentas en atencion simultaneas

Pasos:

1. Abre `/ventas/pos`.
2. Agrega un producto a `Cuenta 1`.
3. Pulsa `Nueva cuenta`.
4. Captura otro producto en la nueva cuenta.
5. Cambia entre las cuentas usando las tarjetas de cuentas.
6. Captura un nombre o telefono express en una cuenta.
7. Cierra una cuenta con el boton `x`.

Resultado esperado:

- Cada cuenta conserva su propio carrito, pagos temporales y datos express.
- El total de cada cuenta se ve en su tarjeta.
- Al cambiar de cuenta, el carrito inferior cambia sin mezclar partidas.
- Al cerrar una cuenta, las demas no se modifican.
- Si hay muchas cuentas, la fila se desplaza horizontalmente.
- No se genera folio, no se descuenta inventario y no se crea reserva.

Notas:

- Las cuentas actuales son locales al navegador y al usuario ERP.
- Si se inicia sesion con otro usuario en el mismo navegador, debe usar otra llave local.
- Si se abre otro navegador/equipo, estas cuentas no se comparten todavia.
- Para compartir cuentas entre usuarios/equipos se requiere evolucionar a borradores persistentes con esquema autorizado.

Evidencia visual:

- `public/storage/uat/pos_playwright_cuentas_ux.png`

## Prueba 8 - Simular confirmacion

Pasos:

1. Con carrito cargado, pulsa `Simular confirmacion`.

Resultado esperado:

- Debe mostrar `Dry-run bloqueado` mientras falte esquema/caja/turno/stock.
- No debe generar folio real.
- No debe descontar inventario.

## Prueba 9 - Simular pedido/reserva

Pasos:

1. Cambia `Documento` a `Pedido` o `Apartado`.
2. Captura cliente y fecha compromiso.
3. Pulsa `Simular pedido/reserva`.

Resultado esperado:

- No exige pago completo.
- Valida cliente, fecha compromiso, stock, caja/turno y esquema.
- No aparta inventario todavia.

## Prueba 10 - Ticket preview

Pasos:

1. Con carrito cargado, pulsa `Vista previa ticket`.

Resultado esperado:

- Se abre modal con ticket temporal.
- Debe decir `NO ES VENTA CONFIRMADA` si hay bloqueos.
- No es folio real ni comprobante.

## Prueba 11 - Tablero Ventas ERP

Pasos:

1. Abre `/ventas/mostrar`.
2. Revisa `Preparacion POS`.
3. Revisa `Turno de caja dry-run`.
4. Revisa `Cancelacion / devolucion dry-run`.

Resultado esperado:

- Debe mostrar cajas sugeridas por tienda.
- Debe permitir simular apertura/cierre, bloqueando por esquema pendiente.
- Debe permitir simular devolucion/cancelacion, bloqueando por falta de folio/motivo/esquema.

## Prueba 12 - Caja: simular gasto

Pasos:

1. Abre `/ventas/pos`.
2. Pulsa `Caja`.
3. En `Tipo`, selecciona `Gasto de caja`.
4. En `Monto`, captura `50`.
5. En `Motivo`, captura `Compra menor UAT`.
6. Pulsa `Simular movimiento`.

Resultado esperado:

- Debe mostrar `Dry-run de movimiento de caja valido`.
- Debe mostrar impacto esperado `-$50.00`.
- Debe indicar que requiere autorizacion.
- Debe indicar que requiere evidencia/comprobante.
- No debe registrar gasto real.
- No debe cambiar el corte real del turno.

## Prueba 13 - Caja: simular retiro

Pasos:

1. Pulsa `Caja`.
2. En `Tipo`, selecciona `Retiro de efectivo`.
3. En `Monto`, captura `100`.
4. En `Motivo`, captura `Retiro UAT`.
5. En `Referencia`, captura `RET-UAT-001`.
6. En `Responsable`, captura tu nombre.
7. Pulsa `Simular movimiento`.

Resultado esperado:

- Debe validar referencia y responsable.
- Debe mostrar impacto esperado `-$100.00`.
- Debe indicar autorizacion requerida.
- No debe registrar retiro real.

## Prueba 14 - Corte: simular cierre cuadrado

Pasos:

1. Abre `/ventas/pos`.
2. Pulsa `Corte`.
3. En `Monto contado`, captura `795`.
4. Pulsa `Simular corte`.

Resultado esperado:

- Esperado: `$795.00`.
- Contado: `$795.00`.
- Diferencia: `$0.00`.
- Ventas: `1`.
- Total vendido: `$295.00`.
- Pagos por metodo: `Efectivo $295.00`.
- Movimientos de caja:
  - entrada/monto_inicial `$500.00`;
  - ingreso/venta_pos `$295.00`.
- No debe cerrar turno real.

## Prueba 15 - Corte: simular diferencia

Pasos:

1. Pulsa `Corte`.
2. En `Monto contado`, captura `790`.
3. Pulsa `Simular corte`.

Resultado esperado:

- Esperado: `$795.00`.
- Contado: `$790.00`.
- Diferencia: `-$5.00`.
- Debe permitir detectar faltante antes de cierre real.
- No debe cerrar turno real.

## Prueba 16 - Atenciones: consultar bandeja

Pasos:

1. Abre `/ventas/pos`.
2. Pulsa `Atenciones`.
3. Pulsa `Consultar bandeja`.

Resultado esperado:

- Debe mostrar que la bandeja esta pendiente de esquema.
- Debe indicar que se requiere DDL expandido antes de compartir cuentas entre dispositivos.
- No debe crear registros.

## Prueba 17 - Atenciones: simular compartir cuenta actual

Pasos:

1. Agrega un producto al carrito.
2. Pulsa `Atenciones`.
3. Pulsa `Simular compartir cuenta actual`.

Resultado esperado:

- Debe sugerir folio temporal `ATN-*`.
- Debe mostrar partidas y total estimado.
- Debe bloquear por esquema pendiente.
- No debe crear atencion real.
- No debe reservar inventario.
- No debe descontar inventario.

## Prueba 18 - Asignacion oficial usuario/caja/terminal

Pasos:

1. Entra con el usuario ERP que va a operar caja.
2. Abre `/ventas/pos`.
3. Confirma que aparece `Operador: ...`.
4. Pulsa el engrane de terminal y revisa la sucursal configurada en este equipo.
5. Anota usuario, sucursal, caja esperada y terminal fisica.

Resultado esperado:

- En modo actual, la fijacion es local del navegador y sirve para pruebas controladas.
- Despues de autorizar BD, esta relacion debe vivir en `erp_pos_usuarios_cajas` y `erp_pos_terminales`.
- El POS real debe cargar automaticamente la asignacion activa del operador; cambiar sucursal/caja debe quedar como accion de configuracion autorizada.
- Cuando exista asignacion oficial, el selector de tienda y caja debe quedar bloqueado por la configuracion del usuario.

## Prueba 19 - Cliente/precios: resolver sin crear cliente

Pasos:

1. Abre `/ventas/pos`.
2. Agrega el SKU UAT `1760` al carrito.
3. Captura un telefono o nombre en los campos de cliente.
4. Pulsa `Cliente/precios`.
5. Confirma que el identificador se precarga.
6. Pulsa `Resolver cliente/precio`.

Resultado esperado:

- Debe mostrar cliente `Publico general` o el identificador capturado.
- Si el esquema de clientes no esta aplicado, debe indicar que podria requerir alta rapida futura.
- Debe mostrar SKU, regla de precio, lista, precio base, precio aplicado e importe.
- Debe indicar contrato: backend resuelve precios, venta guarda snapshot y JS no decide descuentos.
- No debe crear cliente.
- No debe modificar precios reales.
- No debe escribir BD.

## Prueba 20 - Atencion persistente real UAT

Estado:

- Ejecutada con autorizacion.
- Folio temporal UAT: `ATN-20260627-094828-088`.

Pasos:

1. Abrir `/ventas/pos`.
2. Pulsar `Atenciones`.
3. Pulsar `Consultar bandeja`.
4. Confirmar que aparece `ATN-20260627-094828-088`.
5. Confirmar cliente `Cliente UAT POS`.
6. Confirmar total `$295.00`.
7. Confirmar que el stock no se desconto.
8. Confirmar que no se creo venta ni pago nuevo.

Resultado esperado:

- Atencion visible para caja.
- Partida SKU `1760`.
- Sin movimiento de inventario.
- Sin movimiento de caja.
- Caja debe revalidar al cobrar.

## Prueba 21 - Preflight cobrar atencion

Pasos:

1. Ejecutar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_atencion_convertir_preflight_readonly.php --id_atencion=1 --id_usuario=1 --pago=295
```

Resultado esperado:

- `ok=true`.
- Atencion `ATN-20260627-094828-088`.
- Estatus `lista_para_cobro`.
- Cliente `Cliente UAT POS`.
- Total `295`.
- Pago `295`.
- SKU `1760`.
- Disponibilidad `1`.
- Bloqueos `[]`.
- No crea venta.
- No descuenta inventario.

## Prueba 22 - Atencion cobrada y convertida

Estado:

- Ejecutada con autorizacion.
- Venta creada: `POS-20260627-000001`.
- Atencion `1` convertida.

Validaciones esperadas:

- La bandeja de atenciones ya no debe mostrar `ATN-20260627-094828-088` como pendiente.
- La venta debe existir con total `295`, pagado `295` y saldo `0`.
- Debe existir pago efectivo con referencia `ATN-1`.
- Debe existir movimiento de caja por `295`.
- Debe existir kardex de salida por `1`.
- La existencia del SKU `1760` debe quedar en `0`.
- Si se intenta cobrar otra vez, debe bloquear por atencion `convertida`.

## Prueba 23 - Cierre de turno dry-run final

Pasos:

1. Ejecutar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=1090
```

Resultado esperado:

- `ok=true`.
- Monto esperado `1090`.
- Monto contado `1090`.
- Diferencia `0`.
- Ventas `2`.
- Total vendido `590`.
- Pagado `590`.
- Movimientos:
  - monto inicial `500`;
  - ventas POS `590`.
- No debe cerrar turno real.

## Prueba 24 - Cierre de turno real ejecutado

Estado:

- Ejecutada con autorizacion.
- Turno cerrado: `TUR-20260626-002-001`.
- `id_turno_caja=1`.

Validaciones esperadas:

- El turno debe quedar en estatus `cerrado`.
- Monto esperado `1090`.
- Monto contado `1090`.
- Diferencia `0`.
- Usuario de cierre `1`.
- Observaciones `Cierre UAT POS con dos ventas`.
- Deben existir 2 ventas pagadas en el turno.
- Deben existir 2 pagos por `295`.
- Caja debe sumar monto inicial `500` + ventas `590`.
- El POS debe bloquear nuevas ventas hasta abrir un nuevo turno.

Comando de verificacion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_post_cierre_readonly.php --id_turno_caja=1
```

Comando de bloqueo esperado posterior:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=1090
```

Resultado esperado:

- `ok=false`.
- Mensaje: no hay turno abierto.
- Asignacion POS activa.
- `turno_abierto=null`.

## Prueba 25 - DDL caja completa aplicado

Estado:

- Ejecutada con autorizacion.
- Tabla afectada: `erp_pos_movimientos_caja`.

Validaciones esperadas:

- La auditoria de caja completa debe responder `ok=true`.
- Las 17 columnas nuevas deben existir.
- Los 3 indices nuevos deben existir.
- El plan posterior no debe proponer DDL pendiente.

Comando de verificacion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_schema_readonly.php
```

Resultado esperado:

- `categoria`, `estatus`, `id_caja`, `id_almacen`, `id_venta`, `id_proveedor`, `responsable`, autorizacion, evidencia, cancelacion y fecha de actualizacion existentes.
- Indices `idx_pos_movimiento_caja_estado`, `idx_pos_movimiento_categoria` e `idx_pos_movimiento_venta` existentes.

## Prueba 26 - Movimiento de caja bloqueado sin turno abierto

Pasos:

1. Ejecutar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_movimiento_dryrun_readonly.php --id_usuario=1 --tipo=gasto_caja --motivo="Gasto UAT caja" --monto=50 --referencia=GASTO-UAT-001 --responsable="Usuario UAT" --observaciones="Simulacion posterior a cierre"
```

Resultado esperado:

- `ok=false`.
- No hay turno abierto.
- Asignacion POS activa.
- No crea movimiento real.
- No modifica corte.

## Prueba 27 - Nuevo turno UAT abierto para caja completa

Estado:

- Ejecutada con autorizacion.
- Turno abierto: `TUR-20260627-002-001`.
- `id_turno_caja=2`.
- Movimiento inicial: `id_movimiento_caja=4`.

Validaciones esperadas:

- El turno debe estar abierto.
- Monto inicial `500`.
- Caja `2`.
- Almacen `5`.
- El movimiento inicial debe tener trazabilidad completa:
  - `id_caja=2`;
  - `id_almacen=5`;
  - tipo `entrada`;
  - categoria `apertura_turno`;
  - motivo `monto_inicial`;
  - estatus `registrado`.
- Intentar abrir otro turno debe bloquear por turno abierto existente.

Comando de verificacion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_preflight_readonly.php --id_usuario=1
```

## Prueba 28 - Gasto de caja dry-run con turno abierto

Pasos:

1. Ejecutar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_movimiento_dryrun_readonly.php --id_usuario=1 --tipo=gasto_caja --motivo="Gasto UAT caja" --monto=50 --referencia=GASTO-UAT-001 --responsable="Usuario UAT" --observaciones="Simulacion con turno nuevo"
```

Resultado esperado:

- `ok=true`.
- Tipo caja `gasto`.
- Categoria `gasto_caja`.
- Impacto esperado `-50`.
- Bloqueos `[]`.
- Avisos de autorizacion y evidencia.
- No crea movimiento real.
- No modifica corte.

## Prueba 29 - Gasto de caja real

Estado:

- Ejecutada con autorizacion.
- Movimiento real: `id_movimiento_caja=5`.
- Referencia: `GASTO-UAT-001`.

Validaciones esperadas:

- Turno `TUR-20260627-002-001` sigue abierto.
- Monto inicial `500`.
- Gasto registrado `50`.
- Monto esperado del turno `450`.
- Tipo `gasto`.
- Categoria `gasto_caja`.
- `requiere_autorizacion=1`.
- `autorizado_por=1`.
- `requiere_evidencia=1`.
- `evidencia_estado=pendiente`.
- No crea venta.
- No mueve inventario.

## Prueba 30 - Cierre dry-run con gasto

Pasos:

1. Ejecutar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=450
```

Resultado esperado:

- `ok=true`.
- Monto esperado `450`.
- Monto contado `450`.
- Diferencia `0`.
- Ventas `0`.
- Movimientos:
  - entrada inicial `500`;
  - gasto caja `50`.

Control adicional:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=500
```

Resultado esperado:

- Diferencia `50`.
- Sirve para validar que el corte detecta sobrante si el gasto no se resta fisicamente.

## Prueba 31 - Cierre real con gasto de caja

Estado:

- Ejecutada con autorizacion.
- Turno cerrado: `TUR-20260627-002-001`.
- `id_turno_caja=2`.

Validaciones esperadas:

- Estatus `cerrado`.
- Monto inicial `500`.
- Gasto caja `50`.
- Monto esperado `450`.
- Monto contado `450`.
- Diferencia `0`.
- Ventas `0`.
- Pagos `0`.
- Movimientos firmados `450`.
- Ya no debe existir turno abierto para la caja.

Comando de verificacion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_post_cierre_readonly.php --id_turno_caja=2
```

## Prueba 32 - Recarga stock UAT POS

Estado:

- Ejecutada con autorizacion.
- SKU `1760`.
- Almacen `5`.
- Cantidad `2`.
- Referencia `INV-INICIAL-POS-UAT-20260627-A5-S1760-R2`.

Validaciones esperadas:

- Existencia `34`.
- Codigo `EXI-1016-34`.
- Cantidad `2`.
- Disponible `2`.
- Apartada `0`.
- Kardex `55`.
- Tipo movimiento `entrada`.
- Origen `inventario_inicial`.
- Existencia anterior `0`.
- Existencia nueva `2`.
- No crea venta.
- No crea pago.

Comando de preflight venta:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_preflight_readonly.php --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295
```

Resultado esperado:

- Stock suficiente.
- Plan salida por existencia `34`.
- Faltante `0`.
- Bloqueo esperado: no hay turno abierto.

## Prueba 33 - Turno abierto para venta/ticket

Estado:

- Ejecutada con autorizacion.
- Turno abierto: `TUR-20260627-002-002`.
- `id_turno_caja=3`.
- Movimiento inicial: `id_movimiento_caja=6`.

Validaciones esperadas:

- Turno abierto en caja `2`, almacen `5`.
- Monto inicial `500`.
- Movimiento inicial con categoria `apertura_turno`.
- Preflight de venta SKU `1760` debe responder `puede_vender_real=true`.
- Plan salida desde existencia `34`.
- Existencia antes `2`.
- Existencia despues estimada `1`.
- Bloqueos `[]`.

Comando:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_preflight_readonly.php --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295
```

## Prueba 34 - Venta POS UAT real post-caja

Estado:

- Ejecutada con autorizacion.
- Venta `POS-20260627-000002`.
- `id_venta=3`.
- Turno `TUR-20260627-002-002`.

Validaciones esperadas:

- Estatus `pagada`.
- Total `295`.
- Pagado `295`.
- Saldo `0`.
- SKU `1760`.
- Cantidad `1`.
- Precio aplicado `295`.
- Lista precio snapshot `Lista UAT POS`.
- Regla precio `lista_canal_sucursal`.
- Pago efectivo `295`.
- Movimiento caja `7`.
- Movimiento inventario `56`.
- Existencia `34` pasa de `2` a `1`.
- Trazabilidad detalle-inventario `1`.
- Hallazgos funcionales post-venta `[]`.

Comando post-venta:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_venta_readonly.php --folio=POS-20260627-000002
```

Comando cierre dry-run:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=795
```

Resultado esperado:

- Monto esperado `795`.
- Monto contado `795`.
- Diferencia `0`.
- Ventas `1`.
- Total vendido `295`.
- Pagos efectivo `295`.

Observacion:

- Hallazgo `VENTAS-POS-UAT-002`: movimiento caja `7` de esta venta quedo sin `id_caja`, `id_almacen` y `categoria` en caja completa.
- El aplicador UAT fue corregido despues para futuras ventas.
- No corregir historico sin autorizacion especifica.

## Prueba 35 - Cierre real turno venta/ticket

Estado:

- Ejecutada con autorizacion.
- Turno cerrado: `TUR-20260627-002-002`.
- `id_turno_caja=3`.

Validaciones esperadas:

- Estatus `cerrado`.
- Monto inicial `500`.
- Venta POS `295`.
- Monto esperado `795`.
- Monto contado `795`.
- Diferencia `0`.
- Ventas `1`.
- Pagos `1`.
- Movimientos firmados `795`.
- Ya no debe existir turno abierto para la caja.
- Existencia SKU `1760` queda disponible `1`.

Comando de verificacion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_post_cierre_readonly.php --id_turno_caja=3
```

## Prueba 36 - Ticket formal read-only

Estado:

- Ejecutada sin escritura BD.
- Venta `POS-20260627-000002`.

Comando:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260627-000002
```

Validaciones esperadas:

- `ok=true`.
- Ticket con 28 lineas.
- Folio `POS-20260627-000002`.
- Tienda `Mascotas Mina 971`.
- Caja `CJ-MASCOTAS971-01`.
- Turno `TUR-20260627-002-002`.
- Total `295`.
- Pagado `295`.
- Saldo `0`.
- Pago `Efectivo`.
- Precio snapshot `Lista UAT POS`.
- Inventario trazado `1` movimiento.
- Leyenda `No fiscal`.

Hallazgo esperado:

- `VENTAS-TICKET-001`.
- La venta no tiene snapshot de garantia guardado.
- El ticket debe mostrar `Garantia: pendiente snapshot`.
- No recalcular garantia viva para ventas historicas.

## Prueba 37 - Snapshot garantia preparado para siguiente venta

Estado:

- Ejecutada con venta real autorizada.
- Venta `POS-20260627-000003`.
- Snapshot guardado correctamente.

Validaciones esperadas:

- La venta guarda un registro en `erp_ventas_detalle_garantias` por partida.
- Si el SKU no tiene politica configurada, guarda `Sin garantia`.
- El ticket de la venta nueva ya no muestra `Garantia: pendiente snapshot`.
- La venta debe hacer rollback si el snapshot de garantia se bloquea.

Caso UAT actual:

- SKU `1760`.
- Almacen `5`.
- Canal `pos`.
- Folio `POS-20260627-000003`.
- Resultado: `Sin garantia`, sin bloqueos.
- `id_venta_detalle_garantia=1`.
- Ticket formal sin hallazgos.
- No aplica backfill a `POS-20260627-000002` sin autorizacion especifica.

Comando de verificacion posterior a la proxima venta:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_venta_readonly.php --folio=FOLIO_NUEVO
```

Resultado para la venta nueva:

- `ok=true`.
- `garantias=1`.
- Sin hallazgo `VENTAS-POST-009`.
- Ticket formal sin `VENTAS-TICKET-001`.

Resultado esperado para venta historica `POS-20260627-000002`:

- `ok=false`.
- `garantias=0`.
- Hallazgo `VENTAS-POST-009`.
- Esto es correcto porque esa venta fue creada antes de integrar snapshot.

## Prueba 38 - Reimpresion ticket en tablero Ventas

Estado:

- Preparada en UI.
- Pendiente de validacion visual con sesion web real.

Pasos:

1. Entrar a `Ventas ERP`.
2. Buscar folio `POS-20260627-000002`.
3. Presionar el boton con icono de recibo.
4. Revisar que abra el modal `Ticket POS`.
5. Confirmar que muestre el hallazgo `VENTAS-TICKET-001` para esta venta historica.
6. Presionar `Imprimir`.

Validaciones esperadas:

- No crea venta.
- No mueve inventario.
- No reabre turno.
- El ticket mostrado coincide con el endpoint read-only.
- La impresion usa formato monoespaciado.

## Prueba 39 - Cliente alta rapida dry-run

Estado:

- Preparada en backend, CLI y UI POS.
- No escribe BD.
- Pendiente aplicador real autorizado.

Caso telefono nuevo:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cliente_alta_rapida_dryrun_readonly.php --id_usuario=1 --id_almacen=5 --nombre="Cliente Nuevo UAT POS" --identificador=5551112222 --consentimiento=1
```

Validaciones esperadas:

- `puede_crear=true`.
- Codigo sugerido `CL-POS-20260628-0001` o consecutivo del dia.
- Identificador tipo `telefono`.
- Bloqueos `[]`.
- No crea cliente real.

Caso duplicado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cliente_alta_rapida_dryrun_readonly.php --id_usuario=1 --id_almacen=5 --nombre="Cliente Duplicado UAT" --identificador=5550000000 --consentimiento=1
```

Validaciones esperadas:

- `puede_crear=false`.
- Bloqueo por identificador existente.
- Coincidencia `CL-UAT-POS-001`.
- No crea cliente duplicado.

Prueba UI:

1. Abrir POS.
2. Abrir `Cliente/precios`.
3. Capturar identificador.
4. Capturar nombre o alias.
5. Presionar `Validar alta`.

Resultado esperado:

- Muestra propuesta o duplicado.
- No crea cliente real.
- Indica que requiere autorizacion para aplicar.

Aplicador autorizado preparado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cliente_alta_rapida_apply_authorized.php --autorizar=VENTAS_POS_CLIENTE_ALTA_RAPIDA --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_almacen=5 --nombre="Cliente Nuevo UAT POS" --identificador=5551112222 --consentimiento=1
```

No ejecutar sin autorizacion explicita del dueno.

## Que sigue despues de estas pruebas

Estado actual:

- DDL base aplicado.
- Semillas POS aplicadas.
- Turno UAT `TUR-20260626-002-001` cerrado.
- Stock UAT cargado.
- Venta real UAT generada: `POS-20260626-000001`.
- Atencion real convertida a venta: `POS-20260627-000001`.
- Cierre real cuadrado con monto contado `1090`.
- Caja completa DDL aplicado.
- Turno nuevo UAT abierto: `TUR-20260627-002-001`.
- Gasto caja real registrado: `GASTO-UAT-001` por `50`.
- Cierre del turno nuevo ejecutado con monto contado `450` y diferencia `0`.
- Stock UAT SKU `1760` recargado a disponible `2`.
- Turno UAT abierto para venta/ticket: `TUR-20260627-002-002`.
- Venta POS UAT real `POS-20260627-000002` ejecutada; existencia SKU `1760` queda disponible `1`.
- Cierre del turno venta/ticket ejecutado con monto contado `795` y diferencia `0`.
- Ticket formal read-only generado para `POS-20260627-000002`.
- Snapshot de garantia preparado para la proxima venta POS real autorizada.
- Reimpresion UI del ticket preparada en tablero Ventas.
- Alta rapida cliente POS preparada en dry-run.
- DDL expandido de atenciones/clientes/listas/apartados/eventos ya fue aplicado.
- Atenciones persistentes ya cuentan con escritura real UAT validada.
- Cliente/precios ya tiene simulacion visible en POS, sin crear clientes ni aplicar descuentos.
- Cliente/precios ya no reporta esquema pendiente y ya cuenta con semilla UAT de cliente/lista; falta flujo real de alta/edicion de clientes y listas.
- Semillas expandidas aplicadas: cliente UAT, telefono, lista UAT, precio SKU `1760` y politica de apartado.
- Cliente/precios con telefono `5550000000` debe resolver `Cliente UAT POS` y regla `lista_cliente`.
- No hay turno abierto posterior al cierre `TUR-20260627-002-002`.
- Turno nuevo abierto para validar snapshot de garantia: `TUR-20260627-002-003`, `id_turno_caja=4`, monto inicial `500`.
- Venta POS UAT real `POS-20260627-000003` ejecutada con snapshot `Sin garantia`.
- SKU `1760` tiene disponibilidad UAT `0`.
- Cierre real del turno `TUR-20260627-002-003`: esperado `795`, contado `795`, diferencia `0`.
- No hay turno abierto posterior al cierre.

Si visualmente todo funciona, el siguiente bloque recomendado es:

1. Validar visualmente reimpresion UI del ticket formal.
2. Autorizar o implementar aplicador real de alta rapida cliente POS.
3. Para apartados: implementar abonos reales y pedido/apartado.
4. Validar devoluciones y garantias contra venta POS.
5. Preparar stock nuevo o SKU alterno para la siguiente UAT de venta.

Nota: no hay turno abierto. SKU `1760` quedo sin disponibilidad UAT.

## Prueba 37 - Excepcion comercial dry-run

Objetivo:

- Validar precio manual/descuento sin escribir BD.
- Confirmar que falta motivo/autorizacion bloquea.
- Confirmar que el backend resuelve precio base/lista y no el JS.

Pasos en POS:

1. Abrir `/ventas/pos`.
2. Agregar una partida al carrito.
3. Abrir `Cliente/precios`.
4. En `Excepcion comercial dry-run`, elegir `Precio manual`.
5. Capturar `Precio manual` menor o igual al precio actual.
6. Dejar `Motivo` y `Autorizacion` vacios.
7. Pulsar `Validar excepcion`.
8. Confirmar bloqueo por motivo y autorizacion.
9. Capturar `Motivo`: `UAT precio manual`.
10. Capturar `Autorizacion`: `SUP-UAT-001`.
11. Pulsar `Validar excepcion`.

Resultado esperado:

- Debe mostrar precio lista, precio final estimado, descuento estimado y total.
- Debe indicar contrato `backend precio OK`, `JS sin precio final OK` y autorizacion de venta real.
- No debe modificar el carrito.
- No debe generar venta, pago, movimiento de caja ni kardex.

Prueba CLI read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_comercial_dryrun_readonly.php --id_almacen=5 --id_sku=1760 --identificador=5550000000 --precio_manual=285 --descuento_monto=20
```

Resultado esperado:

- `sin_autorizacion` debe devolver bloqueos por motivo/autorizacion.
- `precio_manual_ok` debe simular total con precio manual.
- `descuento_general_ok` debe simular descuento general.
- No escribe BD.

## Prueba 39 - Guardrail precio POS contra navegador

Objetivo:

- Confirmar que el backend no acepta como verdad el `precio_unitario` enviado por el navegador.
- Confirmar que el total prevalidado usa precio backend/lista, no precio alterado.

Prueba CLI read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_precio_guardrail_readonly.php --id_almacen=5 --id_sku=1760 --identificador=5550000000 --precio_correcto=295 --precio_alterado=285
```

Resultado esperado:

- `ok=true`.
- En `precio_correcto`, `precio_enviado_pos=295`, `precio_aplicado=295`.
- En `precio_alterado`, `precio_enviado_pos=285`, pero `precio_unitario=295` y `subtotal=295`.
- Debe aparecer bloqueo `Precio enviado por POS no coincide con el precio autorizado por backend; usa excepcion comercial autorizada`.
- No escribe BD.

Nota:

- Si SKU `1760` sigue sin stock UAT, tambien aparecera `Existencia insuficiente`; eso es esperado y no invalida la prueba de precio.

Comando recomendado para revisar DDL de caja sin aplicar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_schema_readonly.php
```

Comando recomendado para revisar DDL expandido sin aplicar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_config_readonly.php --alcance=expandido --id_usuario=1
```

## Prueba 40 - UX autorizacion comercial POS por partida

Objetivo:

- Validar que el POS permite seleccionar la partida objetivo para precio manual/descuento.
- Confirmar que el flujo de cliente, autorizacion y folio esta separado.
- Confirmar que no se escribe BD desde esta prueba.

Pasos en POS:

1. Abrir `/ventas/pos`.
2. Agregar dos productos o dos partidas al carrito si hay stock visible.
3. Pulsar `Autorizacion`.
4. Confirmar que abre el tab `Autorizacion`.
5. En `Tipo`, elegir `Precio manual`.
6. Confirmar que `Partida` esta habilitado y muestra las partidas del carrito.
7. Elegir la segunda partida.
8. Confirmar que `Precio manual` esta habilitado y `Desc. monto` / `Desc. %` estan deshabilitados.
9. Cambiar `Tipo` a `Descuento partida`.
10. Confirmar que `Partida` sigue habilitado y `Precio manual` queda deshabilitado.
11. Cambiar `Tipo` a `Descuento general`.
12. Confirmar que `Partida` queda deshabilitado y los campos de descuento quedan habilitados.
13. Capturar motivo y supervisor.
14. Pulsar `Validar`.
15. Revisar resultado de dry-run.
16. Cambiar al tab `Folio`.
17. Capturar un folio autorizado existente solo si fue creado por UAT autorizada.
18. Pulsar `Aplicar folio`.

Resultado esperado:

- El modal abre rapido desde el boton `Autorizacion`.
- El POS no toma implicitamente el primer SKU cuando se trata de precio manual o descuento por partida.
- El resultado muestra subtotal, descuento estimado y total estimado.
- Si se valida un folio autorizado sin bloqueos, aparece el panel `Excepcion validada` en el cobro.
- Si despues cambias cantidad, partida, modo de salida o pagos, el panel de excepcion se limpia.
- No se crea excepcion real, venta, pago, movimiento de caja ni kardex desde esta prueba visual.

Validaciones tecnicas ya realizadas:

```powershell
node --check public\assets\js\custom\apps\erp\ventas\pos.js
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php
```

Nota:

- El registro real de excepcion desde UI todavia no esta habilitado como endpoint productivo.
- Para registrar excepcion real se requiere autorizacion de escritura con respaldo externo.

## Prueba 41 - Registro real de folio de excepcion comercial desde POS UI

Objetivo:

- Registrar un folio real `EXC-*` desde el POS.
- Confirmar que el folio no crea venta ni mueve caja/inventario.
- Aplicar el folio contra el carrito y dejar visible la excepcion activa.

Precondiciones:

- Usuario con permisos:
  - `ventas.operar`;
  - `ventas.autorizar_excepcion_comercial`.
- POS con sucursal/caja configurada.
- Carrito con al menos una partida.
- Politica comercial activa para el tipo de excepcion.
- Si se quiere aplicar folio contra pago completo, agregar pago suficiente antes de `Aplicar folio`.

Readiness read-only recomendado antes de probar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_ui_readiness_readonly.php --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --identificador=3312345678 --tipo=precio_manual --precio_manual=285 --motivo="UAT POS UI precio manual" --codigo_autorizacion=SUP-UI-001
```

Resultado readiness esperado:

- `ok=true`;
- endpoint existe;
- permisos `ventas.operar` y `ventas.autorizar_excepcion_comercial` en true;
- politica `POS_PRECIO_MANUAL_UAT` activa;
- `bloqueos=[]`.

Pasos:

1. Abrir `/ventas/pos`.
2. Agregar producto al carrito.
3. Abrir `Autorizacion`.
4. En tab `Autorizacion`, seleccionar tipo de excepcion.
5. Si aplica por partida, seleccionar la partida correcta.
6. Capturar precio manual o descuento.
7. Capturar `Motivo`.
8. Capturar `Supervisor`.
9. Pulsar `Validar`.
10. Confirmar que no hay bloqueos.
11. Pulsar `Registrar folio autorizado`.
12. Confirmar el aviso de registro real de folio.
13. Confirmar que el boton muestra estado de registro y no permite doble clic.
14. Confirmar que el sistema muestra folio `EXC-*`.
15. Confirmar que el POS cambia al tab `Folio` y llena el campo.
16. Agregar o ajustar pago si hace falta.
17. Pulsar `Aplicar folio`.
18. Confirmar que aparece el panel `Excepcion validada`.

Verificacion read-only posterior:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_registro_readonly.php --folio=EXC-YYYYMMDD-000000 --esperar_id_cliente_crm=1 --esperar_total=285 --esperar_estatus=autorizada
```

Si no copiaste el folio, buscar el ultimo autorizado pendiente:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_registro_readonly.php --ultimo_autorizado=1 --id_usuario=1 --id_sku=1760 --esperar_id_cliente_crm=1 --esperar_total=285 --esperar_estatus=autorizada
```

Resultado esperado antes de vender:

- `ok=true`;
- estatus `autorizada`;
- `id_cliente_crm=1`;
- total `285`;
- `id_venta` y `id_venta_detalle` vacios.

Dry-run CLI opcional para validar consumo del folio contra carrito/pago:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_excepcion_consumo_dryrun_readonly.php --folio=EXC-YYYYMMDD-000000 --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=285 --pago=285 --identificador=3312345678
```

Resultado esperado del dry-run de consumo:

- `tipo=success`;
- `bloqueos=[]`;
- total con excepcion `285`;
- pago `285`;
- saldo `0`.

Resultado esperado:

- Se registra folio real `EXC-*`.
- No se crea venta.
- No se mueve caja.
- No se mueve inventario.
- Al aplicar folio sin bloqueos, el total del POS usa el total autorizado.
- Si despues se cambia cantidad, modo de salida, producto o pago, la excepcion activa se limpia y debe validarse de nuevo.

Validaciones tecnicas de implementacion:

```powershell
C:\xampp\php\php.exe -l app\controladores\Ventas.php
C:\xampp\php\php.exe -l app\core\Core.php
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php
node --check public\assets\js\custom\apps\erp\ventas\pos.js
```

Nota:

- El endpoint productivo expuesto es `/ventas/pos_excepcion_comercial_registrar_erp`.
- El endpoint requiere CSRF de navegador; no se recomienda probarlo fuera de sesion POS.
- Para cobrar la venta real se requiere el flujo/autorizacion de venta real correspondiente.

## Prueba 42 - Readiness de cobro real desde POS UI

Objetivo:

- Confirmar si el POS esta listo para habilitar el boton `Cobrar` contra venta real.
- Validar caja, turno, pagos, stock, ticket preview y contrato de kardex sin escribir BD.
- Detectar si falta autorizacion para exponer el endpoint real.

Comando read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cobro_ui_readiness_readonly.php --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295 --identificador_cliente=3312345678
```

Resultado esperado cuando este listo:

- `ok=true`;
- respaldo externo valido;
- usuario con `ventas.operar=true`;
- asignacion POS activa;
- turno abierto;
- existencia suficiente;
- prevalidacion sin bloqueos;
- ticket preview sin bloqueos;
- conteos antes/despues iguales;
- aviso de que `/ventas/pos_confirmar_erp` requiere autorizacion si aun no existe.

Resultado actual 2026-06-29:

- `ok=false`;
- no hay turno abierto;
- SKU 1760 sin existencia suficiente;
- endpoint real `/ventas/pos_confirmar_erp` ya esta expuesto;
- los endpoints de soporte si existen;
- el script no modifico conteos.

Autorizacion ya aplicada para exponer endpoint:

```text
AUTORIZO EXPONER COBRO REAL POS UI usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS
```

Notas de seguridad:

- El endpoint real debe requerir sesion, CSRF y `ventas.operar`.
- El backend debe recalcular precios, descuentos, pagos y salida de inventario.
- El navegador no debe decidir precio final ni descuento.
- Si hay folio de excepcion comercial, debe validarse y consumirse dentro de la misma transaccion de venta.
- La venta real debe dejar folio, ticket, pagos, caja, kardex, garantia snapshot y trazabilidad detalle-inventario.

## Prueba 43 - Cobro real POS UI

Objetivo:

- Confirmar una venta real desde el boton `Cobrar`.
- Validar que el backend cree venta, caja, kardex, garantia snapshot y trazabilidad.
- Confirmar que la cuenta local se limpia solo si la venta fue exitosa.

Precondiciones:

- Usuario con `ventas.operar`.
- POS asignado a tienda/caja.
- Turno abierto.
- Stock suficiente del SKU de prueba.
- Pago completo capturado.
- Si se usa precio manual/descuento, folio de excepcion comercial autorizado y aplicado al carrito.

Preflight para preparar turno y stock:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_preflight_readonly.php --id_usuario=1 --monto_inicial=500
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_stock_uat_preflight_readonly.php --id_almacen=5 --id_sku=1760 --cantidad=1
```

Resultado actual 2026-06-29:

- turno: `puede_abrir_turno=true`, asignacion `id_almacen=5`, `id_caja=2`, `bloqueos=[]`;
- stock: `ok=true`, SKU `TP-40352-500GR`, referencia sugerida `INV-INICIAL-POS-UAT-20260629-A5-S1760`, `bloqueos=[]`.

Autorizaciones requeridas antes de probar cobro real:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 y monto_inicial=500

AUTORIZO CARGAR STOCK UAT POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-UAT-20260629-A5-S1760
```

Readiness antes de probar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cobro_ui_readiness_readonly.php --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295 --identificador_cliente=3312345678
```

Resultado esperado antes de cobrar:

- `ok=true`;
- endpoint `/ventas/pos_confirmar_erp` existe;
- turno abierto;
- existencia suficiente;
- `bloqueos=[]`;
- conteos antes/despues iguales.

Pasos en navegador:

1. Abrir `/ventas/pos`.
2. Confirmar operador correcto en pantalla.
3. Confirmar sucursal/caja asignada y turno abierto.
4. Agregar SKU al carrito.
5. Capturar cantidad/peso.
6. Agregar pago completo.
7. Si aplica excepcion comercial, validar y aplicar folio antes de cobrar.
8. Pulsar `Cobrar`.
9. Confirmar el aviso de venta real.
10. Esperar respuesta.

Resultado esperado si falta turno o stock:

- POS muestra bloqueo.
- No se limpia el carrito.
- No se crea venta.

Resultado esperado si todo esta correcto:

- POS muestra `Venta confirmada POS-*`.
- La cuenta cobrada queda limpia.
- Se conserva el resto de cuentas/atenciones locales.

Verificacion post-venta:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_venta_readonly.php --folio=POS-YYYYMMDD-000000
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_formal_readonly.php --folio=POS-YYYYMMDD-000000
```

Validar:

- venta en `erp_ventas`;
- detalle en `erp_ventas_detalle`;
- pago en `erp_ventas_pagos`;
- movimiento caja `venta_pos`;
- kardex `origen_tipo=venta_pos`;
- trazabilidad en `erp_ventas_detalle_inventario`;
- garantia snapshot;
- ticket formal sin hallazgos.

## Prueba 44 - Dry-run devolucion/cancelacion POS

Objetivo:

- Validar si una venta POS puede devolverse o cancelarse sin escribir BD.
- Confirmar cantidad disponible, motivo, decision de inventario y reembolso estimado.
- Preparar la autorizacion de reversa real con evidencia clara.

Venta de referencia UAT:

- folio `POS-20260629-000003`;
- `id_venta=8`;
- `id_venta_detalle=8`;
- SKU `1760`;
- cantidad vendida `1`;
- total pagado `295`.

Caso positivo devolucion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_devolucion_dryrun_readonly.php --folio=POS-20260629-000003 --id_usuario=1 --id_venta_detalle=8 --cantidad=1 --tipo=devolucion --motivo="UAT devolucion POS cobro UI" --decision_inventario=cuarentena
```

Resultado esperado:

- `ok=true`;
- `bloqueos=[]`;
- reembolso estimado `295`;
- aviso de que el reembolso real requiere turno/caja abierto o saldo a favor.

Caso negativo cantidad excedente:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_devolucion_dryrun_readonly.php --folio=POS-20260629-000003 --id_usuario=1 --id_venta_detalle=8 --cantidad=2 --tipo=devolucion --motivo="UAT devolucion excedente" --decision_inventario=cuarentena
```

Resultado esperado:

- `ok=false`;
- bloqueo `Partida 1: cantidad excede disponible para devolver`.

Caso negativo sin motivo:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_devolucion_dryrun_readonly.php --folio=POS-20260629-000003 --id_usuario=1 --id_venta_detalle=8 --cantidad=1 --tipo=devolucion --motivo="" --decision_inventario=cuarentena
```

Resultado esperado:

- `ok=false`;
- bloqueo `Captura motivo documentado`.

Caso cancelacion completa:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_devolucion_dryrun_readonly.php --folio=POS-20260629-000003 --id_usuario=1 --tipo=cancelacion --motivo="UAT cancelacion completa" --decision_inventario=sin_reingreso
```

Resultado esperado:

- `ok=true`;
- el dry-run auto-selecciona todas las partidas de la venta;
- reembolso estimado `295`;
- no mueve inventario y no cambia caja.

Pendiente antes de operacion real:

- Definir politica por escenario:
  - devolucion con reintegro a inventario disponible;
  - devolucion a cuarentena;
  - merma;
  - sin reingreso;
  - reembolso de caja;
  - saldo a favor;
  - cambio de producto.
- Requiere autorizacion explicita para aplicar escritura real con respaldo externo.

## Prueba 45 - Readiness DDL reversas POS

Objetivo:

- Revisar si el esquema ya puede soportar devolucion/cancelacion real.
- Confirmar que no falten columnas de caja, reembolso, saldo a favor, autorizacion e inventario destino.
- No ejecutar DDL ni registrar devoluciones.

Auditoria de esquema:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_schema_readonly.php
```

Resultado actual 2026-06-29:

- `ok=true` como auditoria;
- tablas base existen;
- faltan columnas/indices para reversa POS completa;
- el plan DDL se genera sin ejecutar.

Readiness operativo:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_readiness_readonly.php --folio=POS-20260629-000003 --id_usuario=1 --id_venta_detalle=8 --cantidad=1 --tipo=devolucion --motivo="UAT readiness reversa POS" --decision_inventario=cuarentena --decision_financiera=reembolso_caja
```

Resultado actual 2026-06-29:

- respaldo externo valido;
- endpoints disponibles;
- dry-run devolucion `success`;
- reembolso estimado `295`;
- readiness final `ok=false`;
- bloqueos:
  - falta aplicar DDL de reversas POS;
  - el turno de la venta esta cerrado para reembolso de caja.

Autorizacion requerida para el siguiente paso:

```text
AUTORIZO APLICAR DDL REVERSAS POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_REVERSA_DDL para UAT POS
```

Notas:

- El aplicador autorizado preparado es `storage/uat/uat_ventas_pos_reversa_schema_apply_authorized.php`.
- Ese aplicador solo modifica esquema.
- No crea devoluciones reales.
- No reembolsa caja.
- No mueve inventario.

## Prueba 46 - Bandeja POS de evidencias y correcciones de caja

Objetivo:

- Validar visualmente que el POS muestra movimientos de caja que requieren evidencia.
- Confirmar que el detalle presenta evidencia original, evidencia correctiva y folio de correccion.
- Revisar que la consulta no mueve caja, dinero ni inventario.

Precondiciones UAT:

- Usuario con acceso a POS y permiso `ventas.ver`.
- Caja/tienda/turno configurados para la sesion.
- Movimiento de caja UAT con evidencia aprobada:
  - `id_movimiento_caja=20`;
  - venta `POS-20260630-000001`;
  - devolucion `DEV-20260630-000002`;
  - correccion `COR-EVC-20260630-000001`.

Pasos visuales:

1. Entrar al POS con el usuario ERP autorizado.
2. Confirmar que el encabezado muestra la sucursal/caja esperada.
3. Abrir `Caja`.
4. En `Evidencias de caja`, seleccionar estado `Aprobada`.
5. Pulsar `Consultar`.
6. Localizar el movimiento con referencia `DEV-20260630-000002`.
7. Pulsar `Detalle`.
8. Revisar que aparezcan dos evidencias:
   - `ticket_firmado`, estado `aprobada`;
   - `ticket_firmado_correccion`, estado `aprobada_correccion`.
9. Confirmar que ambas muestran el folio `COR-EVC-20260630-000001`.
10. Confirmar que la correccion muestra estado `resuelta` y decision `aprobada`.
11. Pulsar `Ticket devolucion` y revisar que se abra el ticket formal.

Resultado esperado:

- El listado muestra monto `295`.
- El detalle muestra historial de correccion sin editar la evidencia original.
- La evidencia original sigue aprobada.
- La evidencia correctiva queda identificada como correccion.
- No debe aparecer ningun cambio de caja, pago, kardex o inventario por consultar.

Verificacion tecnica opcional:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencias_detalle_readonly.php --id_movimiento_caja=20
```

Resultado esperado tecnico:

- `total_registros=2`;
- evidencia `1`: `estatus=aprobada`, relacion `evidencia_original`;
- evidencia `2`: `estatus=aprobada_correccion`, relacion `evidencia_correctiva`;
- correccion `COR-EVC-20260630-000001`: `estatus=resuelta`, `decision=aprobada`;
- contrato `no_escribe_bd=true`, `no_mueve_dinero=true`, `no_mueve_inventario=true`.

Pendiente:

- Exponer desde UI la solicitud/registro/resolucion de correccion solo despues de autorizacion explicita.
- Validar visualmente con navegador autenticado; la validacion automatizada no se ejecuto porque el navegador integrado no estuvo disponible en esta sesion.

## Prueba 47 - Correcciones de evidencias de caja desde POS UI

Objetivo:

- Validar que la UI puede operar el ciclo de correccion autorizado.
- Confirmar que cada accion usa endpoints reales con permiso, CSRF y validaciones del modelo.
- Confirmar que ninguna accion de correccion cambia caja, dinero, venta, kardex ni inventario.

Precondiciones:

- Autorizacion recibida:

```text
AUTORIZO EXPONER CORRECCIONES EVIDENCIA CAJA POS UI usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_UI para UAT POS
```

- Usuario con permiso operativo y permiso fino `ventas.caja_evidencias.revisar`.
- Evidencia de caja aprobada para probar solicitud de correccion.
- Para probar resolucion completa, debe existir una correccion en estado `solicitada` o `en_revision`.

Pasos visuales:

1. Entrar al POS con usuario autorizado.
2. Abrir `Caja`.
3. En `Evidencias de caja`, consultar estado `Aprobada`.
4. Abrir `Detalle` de un movimiento con evidencia aprobada.
5. Pulsar `Solicitar correccion`.
6. Capturar un motivo claro.
7. Confirmar que el detalle se refresca y muestra folio de correccion.
8. Pulsar `Evidencia correctiva` cuando el folio este `solicitada` o `en_revision`.
9. Capturar referencia externa o descripcion.
10. Confirmar que aparece evidencia `recibida_correccion`.
11. Pulsar `Aprobar correccion` o `Rechazar correccion`.
12. Capturar motivo de resolucion.
13. Confirmar que el detalle se refresca con `resuelta` o `rechazada`.

Resultado esperado:

- Las acciones solo aparecen cuando el estado lo permite.
- Si el usuario no tiene permiso, el backend responde bloqueo.
- Si falta motivo o evidencia, la UI bloquea antes de enviar o el backend rechaza.
- Al aprobar, la evidencia correctiva queda `aprobada_correccion`.
- Al rechazar, la evidencia correctiva queda `rechazada_correccion`.
- La evidencia original se conserva.
- El movimiento de caja conserva su importe y estado financiero.
- No hay movimientos de inventario.

Notas de UX:

- La UI usa referencia externa/descripcion; subida real de archivos queda pendiente.
- La operacion debe usarse como flujo supervisor, no como captura de cajero comun.

## Prueba 48 - Devoluciones fisicas pendientes de inventario

Objetivo:

- Validar que POS muestra partidas devueltas que requieren inspeccion fisica.
- Confirmar que la consulta es solo lectura y no reintegra mercancia.
- Separar claramente reversa comercial de decision de Almacen/Inventario.

Pasos visuales:

1. Entrar al POS con usuario autorizado.
2. Abrir `Caja`.
3. En `Devoluciones fisicas`, elegir `Pendientes`.
4. Pulsar `Consultar`.
5. Revisar folio de devolucion, folio de venta, SKU, cantidad, importe y decision fisica.
6. Confirmar que el panel indica que el cierre fisico corresponde a Almacen/Inventario.

Resultado esperado UAT actual:

- Deben aparecer 2 partidas.
- Cantidad total `2`.
- Importe total `$590`.
- Decision `cuarentena`:
  - `DEV-20260630-000002`, venta `POS-20260630-000001`, reembolso caja `295`;
  - `DEV-20260630-000001`, venta `POS-20260629-000003`, saldo a favor `295`.
- Movimiento de inventario de devolucion: `pendiente`.

Verificacion tecnica opcional:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_devoluciones_inventario_pendientes_readonly.php --decision_inventario=pendientes --limite=20
```

Resultado esperado tecnico:

- `ok=true`;
- `total_registros=2`;
- `cantidad_total=2`;
- `importe_total=590`;
- `por_decision.cuarentena.partidas=2`;
- contrato:
  - `no_escribe_bd=true`;
  - `no_crea_kardex=true`;
  - `no_reintegra_inventario=true`.

Pendiente:

- Crear flujo formal de Almacen/Inventario para inspeccionar y resolver cada partida:
  - permanecer en cuarentena;
  - reintegrar a disponible;
  - enviar a merma;
  - ligar a reclamo de garantia/proveedor.

## Prueba 49 - Dry-run inspeccion fisica de devolucion

Objetivo:

- Validar una decision fisica antes de autorizar escritura real.
- Confirmar si la decision futura requerira kardex o solo cierre documental.
- No registrar inspeccion ni mover inventario.

Comando mantener cuarentena:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_devolucion_inspeccion_fisica_dryrun_readonly.php --id_usuario=1 --id_devolucion_detalle=2 --decision_fisica=mantener_cuarentena --condicion_producto=pendiente_revision --motivo="UAT mantener en cuarentena sin mover inventario" --diagnostico="Producto pendiente de inspeccion fisica"
```

Resultado esperado:

- `ok=true`;
- `bloqueos=[]`;
- contrato futuro:
  - `registrar_inspeccion=true`;
  - `crear_kardex_si_reintegra_o_merma=false`;
  - `no_escribe_bd_en_dry_run=true`.

Comando reintegrar disponible:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_devolucion_inspeccion_fisica_dryrun_readonly.php --id_usuario=1 --id_devolucion_detalle=2 --decision_fisica=reintegrar_disponible --condicion_producto=apto --motivo="UAT reintegro dryrun" --diagnostico="Producto en buen estado"
```

Resultado esperado:

- `ok=true`;
- `bloqueos=[]`;
- contrato futuro:
  - `registrar_inspeccion=true`;
  - `actualizar_devolucion_detalle=true`;
  - `crear_kardex_si_reintegra_o_merma=true`;
  - `no_escribe_bd_en_dry_run=true`.

Pendiente:

- La escritura real por partida requiere autorizacion separada.
- Reintegro/merma deben crear kardex y dejar trazabilidad en `erp_ventas_devoluciones_inspecciones`.

## Prueba 50 - Inspeccion fisica real mantener cuarentena

Objetivo:

- Registrar una inspeccion fisica real sin mover inventario.
- Confirmar que la partida deja de aparecer como pendiente.
- Confirmar que la operacion queda trazada por folio de inspeccion.

Autorizacion UAT ejecutada:

```text
AUTORIZO REGISTRAR INSPECCION FISICA DEVOLUCION POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_DEVOLUCION_FISICA_REAL id_usuario=1 id_devolucion_detalle=2 decision_fisica=mantener_cuarentena condicion_producto=pendiente_revision motivo="UAT mantener en cuarentena sin mover inventario" diagnostico="Producto pendiente de inspeccion fisica"
```

Resultado esperado:

- Se crea inspeccion `IFD-20260630-000001`.
- `id_devolucion_detalle=2` queda `cuarentena_confirmada`.
- No se crea kardex.
- No se mueve inventario.
- Pendientes fisicos bajan de 2 a 1.

Verificacion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_devoluciones_inventario_pendientes_readonly.php --decision_inventario=pendientes --limite=20
```

Debe devolver:

- `total_registros=1`;
- pendiente restante: `DEV-20260630-000001`.

Consulta completa:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_devoluciones_inventario_pendientes_readonly.php --decision_inventario=todos --limite=20
```

Debe mostrar:

- `DEV-20260630-000002` con `folio_inspeccion=IFD-20260630-000001`;
- `inspeccion_estado=cuarentena_confirmada`;
- `decision_fisica=mantener_cuarentena`.

## Prueba 51 - UX POS cliente, modulos y atajos

Objetivo:

- Validar que el POS sea mas agil visualmente.
- Confirmar que cliente CRM se busca sin carrito y que lista/precio se resuelve con carrito.
- Confirmar que el POS abre con asignacion oficial, no como seleccion libre de tienda/caja.

Precondiciones:

- Iniciar sesion web con usuario que tenga `ventas.operar`.
- Usar host local `http://dashboard.com.local`; `localhost` puede apuntar a otro proyecto.
- Para `id_usuario=1`, la auditoria read-only indica:
  - asignacion oficial activa;
  - 2 cajas POS;
  - 2 terminales POS;
  - 0 turnos abiertos.

Pasos:

1. Abrir `/ventas/pos`.
2. Confirmar que la pantalla muestra buscador de producto, resultados, cuentas y carrito.
3. Confirmar que las acciones estan como modulos arriba del carrito:
   `Prevalidar`, `Simular`, `Pedido`, `Ticket`, `Cliente`, `Autorizar`, `Atenciones`, `Caja`, `Corte`.
4. Confirmar que abajo solo quedan pagos, totales y boton `Cobrar`.
5. Abrir `Cliente`.
6. Capturar `3312345678` en identificador.
7. Presionar `Buscar cliente`.
8. Si existe una coincidencia exacta, debe seleccionarse automaticamente.
9. Confirmar que `Validar alta` cambie a `Cliente seleccionado`.
10. Agregar un producto al carrito.
11. Volver a `Cliente` y presionar `Precios/lista`.
11. Probar atajos:
    - `F2` o `Ctrl+K`: enfoca buscador;
    - `F4`: abre cliente;
    - `Alt+1`: agrega efectivo;
    - `Alt+2`: agrega tarjeta;
    - `Alt+3`: agrega transferencia;
    - `F6`: enfoca pago;
    - `F8`: abre caja;
    - `F9`: prevalidar;
    - `Ctrl+Enter`: intentar cobrar.

Resultado esperado:

- `Buscar` cliente no exige carrito.
- `Precios/lista` exige carrito y no aplica descuentos desde JS.
- Si el cliente ya existe, no se usa `Validar alta`.
- Si no hay turno abierto, el cobro real debe bloquearse con mensaje operativo.
- Los selectores de tienda/caja deben reflejar asignacion oficial; si aparecen libres en operacion real, registrar hallazgo UX/POS.
- No se crea cliente real desde esta prueba.
- No se mueve inventario salvo que exista turno abierto y se confirme cobro real autorizado.

Verificacion tecnica opcional:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_config_readonly.php --id_usuario=1 --alcance=expandido
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_acceso_readonly.php --id_usuario=1
```

Estado actual observado por Playwright:

- `http://dashboard.com.local/ventas/pos` carga POS autenticado.
- Evidencias:
  - `public/storage/uat/pos_ux_cliente2_authenticated_dashboard.png`;
  - `public/storage/uat/pos_ux_cliente_busqueda_crm.png`;
  - `public/storage/uat/pos_ux_cliente_seleccionado_v3.png`;
  - `public/storage/uat/pos_ux_busqueda_producto_1760.png`.
- Busqueda CRM con `3312345678` selecciona `Cliente Express UAT`.
- Boton de cliente cambia de `Usar` a `Seleccionado`.
- `Validar alta` cambia a `Cliente seleccionado` y queda deshabilitado.

## Prueba 53 - Venta POS UI real confirmada y corte pendiente

Fecha: 2026-07-01

Resultado observado:

- Venta confirmada desde UI con folio `POS-20260701-000001`.
- Turno `TUR-20260630-002-002`, `id_turno_caja=10`.
- Total venta `295`, pago efectivo `295`, saldo `0`.
- Kardex generado con movimiento `69`, referencia `POS-20260701-000001`.
- Stock UAT del SKU `1760` paso de `1` a `0`.
- Ticket formal read-only generado sin hallazgos.

Nota UX corregida:

- El mensaje antiguo de prevalidacion decia que faltaba autorizacion para cobrar y kardex.
- El copy correcto debe indicar que, si el pago cubre el total y el turno sigue abierto, se puede cobrar y el backend registrara caja, kardex, garantia y trazabilidad.
- Asset POS vigente: `20260701-pos-ux-prevalidar1`.

Verificacion de corte sin cerrar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=795
```

Resultado esperado:

- `ok=true`.
- Monto esperado `795`.
- Monto contado `795`.
- Diferencia `0`.
- El turno sigue abierto hasta recibir autorizacion de cierre real.

## Prueba 54 - Venta POS post-cierre y cierre pendiente

Fecha: 2026-07-03

Objetivo:

- Validar la venta real mas reciente.
- Confirmar que el turno actual puede cerrarse con diferencia `0`.
- Confirmar que no queda stock UAT para repetir venta sin recarga.

Precondiciones:

- Usuario con permisos POS.
- Turno actual `TUR-20260703-002-001`, `id_turno_caja=11`.
- Folio de venta `POS-20260703-000001`.

Pasos:

1. Abrir `/ventas/venta_detalle?folio=POS-20260703-000001`.
2. Confirmar total `295`, pago efectivo `295` y saldo `0`.
3. Confirmar que la trazabilidad muestre turno `TUR-20260703-002-001`, caja `2`, almacen `5`.
4. Confirmar kardex/movimiento inventario `71` y existencia `34` de `1` a `0`.
5. Confirmar garantia snapshot `Sin garantia`.
6. Abrir `/ventas/pos`, buscar SKU `1760` e intentar una venta nueva.
7. Resultado esperado: bloqueo por `Existencia insuficiente`.
8. Abrir `/ventas/caja_turnos`.
9. Simular cierre con monto contado `795`.
10. Resultado esperado: esperado `795`, contado `795`, diferencia `0`.
11. Copiar la autorizacion sugerida solo si se desea cerrar el turno real.

Autorizacion real pendiente:

```text
AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS post venta POS-20260703-000001"
```

Nota:

- Esta prueba no debe mover inventario ni caja si solo se revisa detalle, POS y cierre simulado.
- El cierre real requiere autorizacion explicita porque escribe caja/turno.

## Prueba 55 - Configuracion POS separada del mostrador

Fecha: 2026-07-03

Objetivo:

- Confirmar que la configuracion de tienda/caja/terminal no vive dentro del cobro.
- Validar que la pantalla actual solo consulta y simula, sin crear registros.

Precondiciones:

- Usuario con `ventas.ver`.
- Para usuario `1`, asignacion activa a almacen `5`, caja `2`, terminal `2`.

Pasos:

1. Abrir `/ventas/pos_configuracion`.
2. Confirmar KPIs:
   - cajas `2`;
   - terminales `2`;
   - asignaciones `2`.
3. Revisar listados de cajas, terminales y asignaciones.
4. En el bloque de caja, capturar un codigo nuevo temporal como `CJ-UAT-READONLY-2`.
5. Presionar `Validar caja`.
6. Confirmar que el resultado diga que es validacion sin crear.
7. En terminal, elegir la misma tienda/caja y capturar codigo temporal `TERM-UAT-READONLY-2`.
8. Presionar `Validar terminal`.
9. En asignacion, capturar usuario `1`, tienda/caja/terminal actuales.
10. Presionar `Validar asignacion`.

Resultado esperado:

- La pantalla no crea cajas, terminales ni asignaciones.
- La asignacion actual de usuario `1` puede avisar que ya existe; eso es correcto.
- El POS mostrador debe seguir abriendo con asignacion oficial, no con selector libre.
- Cualquier boton futuro `Guardar` debe quedar fuera de esta prueba hasta autorizacion de CRUD real.

Autorizacion futura para activar CRUD real:

```text
AUTORIZO PREPARAR CRUD REAL CONFIGURACION POS usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_CRUD para UAT POS
```

## Prueba 56 - Caja permite cierre con diferencia

Fecha: 2026-07-03

Objetivo:

- Validar que el cierre de caja no se bloquee solo por diferencia.
- Confirmar que faltantes y sobrantes quedan visibles para supervision/reportes.

Precondiciones:

- Debe existir un turno abierto.
- El turno debe tener monto esperado mayor a cero.
- Esta prueba puede ejecutarse en simulacion sin cerrar turno.

Pasos:

1. Abrir `/ventas/caja_turnos`.
2. Seleccionar turno abierto.
3. En `Monto contado`, capturar el monto exacto esperado.
4. Presionar `Simular corte sin cerrar`.
5. Confirmar diferencia `0` y autorizacion sugerida.
6. Cambiar `Monto contado` a un monto menor al esperado.
7. Presionar `Simular corte sin cerrar`.
8. Confirmar que aparece faltante y que aun se muestra autorizacion sugerida.
9. Cambiar `Monto contado` a un monto mayor al esperado.
10. Presionar `Simular corte sin cerrar`.
11. Confirmar que aparece sobrante y que aun se muestra autorizacion sugerida.

Resultado esperado:

- Diferencia `0`: cierre normal.
- Diferencia negativa: cierre permitido con advertencia de faltante.
- Diferencia positiva: cierre permitido con advertencia de sobrante.
- La pantalla no cierra el turno por si sola.
- El cierre real sigue requiriendo autorizacion explicita.

Regla:

- Las diferencias no bloquean cierre; alimentan reportes de caja, empleado, sucursal y revision gerencial.

Validacion CLI read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_diferencias_readonly.php --id_usuario=1 --delta=10
```

Si no hay turno abierto:

- debe responder `No hay turno abierto para simular diferencias`;
- no debe escribir BD.

Despues de cerrar un turno con diferencia real:

1. Abrir `/ventas/reportes`.
2. Activar `Solo con diferencia`.
3. Confirmar que el turno aparezca como `faltante` o `sobrante`.
4. Confirmar que el monto de diferencia coincida con `contado - esperado`.

### Prueba 57 - Reportes POS por sucursal/caja

Objetivo: confirmar que supervision pueda aislar cierres por ubicacion operativa sin editar datos.

1. Abrir `/ventas/reportes`.
2. Confirmar que los filtros de fecha cargan automaticamente.
3. Seleccionar la sucursal `Francisco Javier Mina 971 - Mascotas frontal` o equivalente.
4. Confirmar que el filtro `Caja` solo muestra cajas de esa sucursal.
5. Seleccionar `CJ-MASCOTAS971-01`.
6. Presionar `Consultar`.
7. Confirmar que los KPIs, turnos, empleado y caja reflejan solo esa sucursal/caja.
8. Presionar el boton de descarga.
9. Confirmar que se descarga un CSV con los turnos filtrados.

Resultado esperado UAT actual:

- `11` turnos;
- `0` turnos con diferencia;
- almacen `MASCOTAS971`;
- caja `CJ-MASCOTAS971-01`;
- sin escrituras de BD.

### Prueba 58 - Validar faltante real en reportes

Fecha: 2026-07-03

Objetivo: confirmar desde UI que un cierre con diferencia queda visible para supervision.

Datos UAT:

- Venta: `POS-20260703-000002`.
- Turno: `TUR-20260703-002-002`.
- Esperado: `795`.
- Contado: `785`.
- Diferencia: `-10`.

Pasos:

1. Abrir `/ventas/reportes`.
2. Seleccionar rango que incluya `2026-07-03`.
3. Seleccionar sucursal `Francisco Javier Mina 971 - Mascotas frontal`.
4. Seleccionar caja `CJ-MASCOTAS971-01`.
5. Activar `Solo con diferencia`.
6. Presionar `Consultar`.
7. Confirmar que aparece el turno `TUR-20260703-002-002`.
8. Confirmar estado `faltante`.
9. Confirmar faltantes total `$10.00` y neto `-$10.00`.
10. Revisar `Diferencias por empleado`: Usuario `1`, `1` turno con diferencia.
11. Revisar `Diferencias por sucursal y caja`: `MASCOTAS971`, caja `CJ-MASCOTAS971-01`, `1` turno con diferencia.
12. Presionar exportar CSV y confirmar que incluye ese turno.
13. Abrir detalle/ticket de venta `POS-20260703-000002`.
14. Confirmar ticket de `28` lineas, pago efectivo `295` e inventario trazado.

Resultado esperado:

- El faltante no impidio cerrar caja.
- El faltante quedo reportado por turno, empleado y caja.
- No queda turno abierto despues del cierre.

### Prueba 59 - Pedidos/Apartados multipartida desde UI

Fecha: 2026-07-06

Objetivo: confirmar que la pantalla de Pedidos/Apartados solo reserve productos visibles en la tabla de partidas.

Precondiciones:

- Usuario con POS asignado a almacen/caja.
- Turno abierto.
- Stock suficiente para el SKU de prueba.
- Politica de apartado activa.

Pasos:

1. Abrir `/ventas/pedidos`.
2. En `Simular pedido/apartado`, seleccionar `Apartado`.
3. Capturar cliente e identificador.
4. Buscar el producto por SKU, codigo o nombre.
5. Seleccionar el producto.
6. Confirmar que SKU y precio se llenan en la captura temporal.
7. Sin presionar `Agregar partida`, intentar `Simular reserva`.
8. Confirmar que la pantalla bloquea con mensaje para agregar partida.
9. Presionar `Agregar partida`.
10. Confirmar que la partida aparece en la tabla y el resumen marca `1 partida`.
11. Buscar/agregar una segunda partida.
12. Confirmar total, anticipo y saldo.
13. Presionar `Simular reserva`.
14. Si no hay bloqueos, confirmar que aparece `Crear apartado/pedido real`.
15. No confirmar accion real salvo que exista autorizacion vigente.

Resultado esperado:

- Las partidas invisibles no se envian al backend.
- El total se calcula desde la tabla.
- La simulacion bloquea si no hay partidas agregadas.
- La simulacion no escribe BD.
- La accion real sigue requiriendo confirmacion y autorizacion operativa.

Validacion CLI read-only relacionada:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pedido_multipartida_readiness_readonly.php --compact=1
```

### Prueba 60 - Cancelar apartado antes de entrega

Fecha: 2026-07-06

Objetivo: confirmar que un apartado reservado puede cancelarse antes de entrega liberando inventario reservado, sin generar kardex de salida.

Precondiciones:

- Usuario con POS asignado a almacen/caja.
- Turno abierto.
- Stock suficiente para el SKU de prueba.
- Apartado creado con anticipo y reserva activa.
- Apartado sin entrega.

Pasos:

1. Abrir `/ventas/pedidos`.
2. Crear o localizar un apartado en estatus `reservado` o `pendiente_pago`.
3. Confirmar que el apartado tiene reserva activa.
4. Presionar la accion de cancelar.
5. Capturar motivo operativo claro.
6. Confirmar la accion real solo si existe autorizacion vigente.
7. Verificar que el apartado quede `cancelado`.
8. Verificar que la reserva quede liberada.
9. Verificar que no se genere kardex de salida.
10. Verificar que cualquier pago previo quede pendiente de decision financiera, no borrado.

Resultado esperado:

- Apartado cancelado.
- Reserva liberada.
- Disponible vuelve a quedar utilizable, segun regla de inventario.
- No hay entrega.
- No hay kardex de salida.
- No se borra el anticipo.
- La decision financiera posterior debe manejar reembolso, saldo a favor o penalizacion.

Runbook CLI read-only relacionado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pedido_cancelacion_runbook_readonly.php --compact=1
```

### Prueba 61 - Decision financiera pendiente de apartado cancelado

Fecha: 2026-07-06

Objetivo: confirmar que un apartado cancelado con anticipo no borre el pago y quede visible para decidir saldo a favor, reembolso o penalizacion.

Datos UAT:

- Folio cancelado: `APT-20260706-000002`.
- Anticipo registrado: `$100.00`.
- Reserva liberada: `1`.
- Kardex de salida: `0`.

Pasos:

1. Abrir o consultar el apartado cancelado.
2. Confirmar estatus `cancelado`.
3. Confirmar que el pago/anticipo sigue registrado.
4. Confirmar que la reserva quedo liberada.
5. Confirmar que no hay salida por kardex.
6. Revisar la decision financiera pendiente.
7. Elegir una ruta operativa futura:
   - saldo a favor;
   - reembolso de caja;
   - penalizacion autorizada;
   - sin reembolso solo si no hubo pago.

Resultado esperado:

- El anticipo no se borra.
- El sistema no reembolsa automaticamente al cancelar.
- La decision financiera queda pendiente y trazable.
- No se debe resolver con nota manual fuera de ERP.

Validacion CLI read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pedido_cancelado_finanzas_readonly.php --folio=APT-20260706-000002 --compact=1
```

### Prueba 62 - Venta POS con saldo cliente CRM

Fecha: 2026-07-07

Objetivo: confirmar que una venta POS puede cobrarse con pago mixto caja + saldo cliente, descontando inventario, registrando ledger CRM y sin inflar el arqueo de caja.

Datos UAT sugeridos:

- Usuario: `1`.
- Almacen: `5`.
- Caja asignada por terminal POS.
- SKU: `1760`.
- Precio: `$295.00`.
- Cliente CRM: `157`.
- Saldo cliente a usar: `$100.00`.
- Pago caja: `$195.00`.
- Monto inicial turno: `$500.00`.
- Monto contado esperado al cerrar: `$695.00`.

Precondiciones:

- Cliente CRM seleccionado en POS antes de usar saldo cliente.
- Cliente con cuenta de saldo MXN activa y saldo disponible suficiente.
- Turno POS abierto.
- Stock disponible para el SKU.

Pasos UI:

1. Abrir `/ventas/pos`.
2. Verificar que arriba aparezca la terminal/caja/sucursal asignada al usuario.
3. Buscar o seleccionar el cliente CRM `157`.
4. Agregar el SKU `1760` al carrito.
5. Confirmar total de venta `$295.00`.
6. En pagos, agregar `Efectivo` o metodo de caja por `$195.00`.
7. Presionar `Saldo cliente`.
8. Ajustar el monto de saldo cliente a `$100.00` si no queda asi.
9. Confirmar que el pago de saldo cliente muestre que no entra a caja.
10. Prevalidar o cobrar solo si existe autorizacion real vigente.
11. En el ticket, confirmar que pagos muestre `Saldo cliente no caja`.
12. En detalle de venta, confirmar que el pago salga como `Saldo cliente` y tipo `No entra a caja`.
13. En corte/cierre, confirmar que caja esperada sea `$695.00`, no `$795.00`.

Resultado esperado:

- Venta confirmada.
- Inventario descontado con kardex.
- Pago de caja por `$195.00` con movimiento de caja.
- Pago de saldo cliente por `$100.00` sin movimiento de caja.
- Movimiento CRM `uso_saldo_pos` por `$100.00`.
- Ticket y corte distinguen saldo cliente de caja.
- Cierre de turno cuadra con `500 + 195 = 695`.

Autorizaciones preparadas:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS saldo CRM"

AUTORIZO EJECUTAR VENTA POS UAT REAL CON SALDO CRM usando respaldo UAT POS vigente con token VENTAS_POS_SALDO_CRM_REAL id_usuario=1 id_cliente_crm=157 id_sku=1760 cantidad=1 precio=295 monto_saldo_crm=100 pago_caja=195

AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=695 observaciones="Cierre UAT POS venta con saldo CRM"
```

Validaciones CLI read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_saldo_crm_runbook_readonly.php --compact=1
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_saldo_crm_post_readonly.php --folio=POS-YYYYMMDD-###### --compact=1
```

### Prueba 63 - POS inventario pendiente desde UI, solo simulacion

Fecha: 2026-07-13

Objetivo: confirmar que el POS puede detectar una venta que requiere mini inventario sin cobrar, sin descontar stock y sin crear alerta real hasta que exista autorizacion productiva.

Contexto:

- Esta prueba es read-only desde la UI.
- No abre turno.
- No crea venta.
- No crea movimiento de caja.
- No crea kardex.
- No crea pendiente real de inventario.
- No aplica a ecommerce.

Precondiciones:

- Usuario con asignacion POS activa a tienda/almacen/caja/terminal.
- SKU con politica activa de inventario pendiente POS, por ejemplo SKU `1760` en almacen `5` para UAT.
- El carrito debe tener una sola partida.
- La partida debe salir por existencia agregada, no por unidad fisica cerrada ni unidad abierta.

Pasos UI:

1. Abrir `/ventas/pos`.
2. Confirmar que el encabezado muestre usuario, tienda, almacen, caja y terminal.
3. Buscar el SKU `1760` o un SKU equivalente con politica activa.
4. Agregar una sola partida al carrito.
5. Dejar cantidad `1` para UAT basico.
6. Presionar `Prevalidar`.
7. Si el backend detecta que el cobro normal no puede continuar pero existe politica POS, usar `Revisar inventario pendiente`.
8. Alternativamente, usar el boton superior `Inventario pendiente`.
9. Revisar el resultado mostrado en el panel de validacion.

Resultado esperado:

- El panel debe mostrar estado `pendiente_autorizable` si la politica permite vender con faltante controlado.
- Debe mostrar disponible actual.
- Debe mostrar cantidad cubierta con kardex.
- Debe mostrar cantidad pendiente para Inventario/Existencias.
- Debe mostrar politica POS aplicada.
- Debe mostrar total estimado.
- Debe advertir que el cobro real requiere autorizacion operativa.
- No debe aparecer folio de venta nuevo.
- No debe cambiar caja.
- No debe cambiar inventario.

Resultado bloqueado esperado:

- Si el carrito tiene mas de una partida, la simulacion debe pedir probar una partida por vez.
- Si la partida es unidad fisica cerrada o unidad abierta, no debe convertirse a inventario pendiente.
- Si no hay politica activa, debe bloquear.
- Si la cantidad supera politica, debe bloquear.

Validacion CLI read-only relacionada:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_productivo_readiness_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1
```

Dictamen esperado UAT:

- POS normal queda listo para pruebas operativas con turno abierto.
- Inventario pendiente queda visible como simulacion segura.
- Para habilitar cobro real productivo falta sembrar permiso fino y exponer endpoint real sin token UAT.

Siguiente autorizacion robusta recomendada:

```text
AUTORIZO SEMBRAR PERMISO INVENTARIO PENDIENTE POS PRODUCTIVO usando respaldo UAT POS vigente con token VENTAS_POS_INVENTARIO_PENDIENTE_PERMISO id_usuario=1 para UAT POS
```
